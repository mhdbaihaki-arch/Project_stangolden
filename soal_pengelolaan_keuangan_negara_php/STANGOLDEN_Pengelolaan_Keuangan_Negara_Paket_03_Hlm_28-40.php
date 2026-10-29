<?php
/**
 * STANGOLDEN.exam • Pengelolaan Keuangan Negara Paket 03 (Halaman 28–40)
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
// $DB->get_record("stgpkn_p03_rank") => tabel fisik "mdlhv_stgpkn_p03_rank".
// Jangan menambahkan prefix secara manual di konstanta berikut.
// Penyimpanan bersama melalui database Moodle. Nama tabel pendek, kompatibel dengan batas Moodle.
const STG_LB_T = 'stgpkn_p03_rank';
const STG_REVIEW_T = 'stgpkn_p03_rev';
const STG_ANNO_T = 'stgpkn_p03_anno';
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
            error_log('STANGOLDEN PKN P03 HLM28-40 SETUP: ' . $e->getMessage());
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
    }catch(Throwable $e){error_log('STANGOLDEN PKN P03 HLM28-40: '.$e->getMessage());stgtak_json(['success'=>false,'message'=>($e instanceof RuntimeException ? $e->getMessage() : 'Penyimpanan belum tersedia. Hubungi administrator.')],500);}
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
<title>Latihan Pengelolaan Keuangan Negara Paket 03 (Hlm. 28–40) • STANGOLDEN</title>
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
      <span class="series-tag">Paket 03 • Hlm. 28–40</span>
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
<h1>Latihan Nilai-Nilai Kemenkeu <span class="series-tag">Paket 03 • Hlm. 28–40</span></h1>
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
        <h2 class="leaderboard-title" id="rankingModalTitle">🏆 Lihat Ranking Pengelolaan Keuangan Negara Paket 03</h2>
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
const STG_LOCAL_KEY="stg_pkn_p03_review_user_<?= (int)$USER->id ?>";
const HAS_FULL_REVIEW=true;
const IS_SITE_ADMIN=<?= is_siteadmin($USER) ? "true" : "false" ?>;
const LEADERBOARD_ENDPOINT=window.location.href.split("?")[0].split("#")[0];
const questions=[{"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Tahap paling awal dalam rangkaian siklus pengelolaan APBN secara keseluruhan diawali dengan...", "options": ["Tahap perencanaan dan penganggaran APBN", "Tahap pencairan dana langsung kepada rekanan", "Tahap audit investigasi oleh lembaga pemeriksa", "Tahap penyusunan laporan keuangan pemerintah pusat", "Tahap pemungutan suara penolakan oleh fraksi"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tahap Awal Siklus APBN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pengelolaan APBN secara keseluruhan dilakukan melalui 7 tahapan yang diawali dengan <strong>1. Perencanaan APBN</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena perencanaan merupakan fondasi awal penentuan arah kebijakan makro dan prioritas belanja sebelum dituangkan ke dokumen anggaran. Oleh karena itu, opsi A benar.</p><p>Jawaban tepat: <strong>A. Tahap perencanaan dan penganggaran APBN</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Pencairan dana dilakukan pada tahap pelaksanaan anggaran.</li><li><strong>C</strong>: Audit investigasi dilakukan pada tahap pemeriksaan akhir.</li><li><strong>D</strong>: Penyusunan LKPP dilakukan pada tahap pelaporan dan pencatatan.</li><li><strong>E</strong>: Pemungutan suara merupakan dinamika tahap pembahasan di DPR.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 28) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pengelolaan APBN secara keseluruhan dilakukan melalui: • 1.Perencanaan APBN</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Setelah tahapan penyusunan rancangan anggaran selesai, dua tahapan berurutan berikutnya dalam siklus APBN adalah...", "options": ["Pemeriksaan keuangan daerah kemudian Pelaporan BPK", "Pembahasan APBN bersama DPR kemudian Penetapan APBN", "Penerbitan surat utang komersial kemudian Likuidasi kas", "Pencairan pinjaman luar negeri kemudian Restrukturisasi bank", "Penyerahan aset kepada swasta kemudian Audit investigatif"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Urutan Pembahasan dan Penetapan APBN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Urutan siklus pengelolaan APBN setelah penyusunan adalah <strong>3. Pembahasan APBN dan 4. Penetapan APBN</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena rancangan anggaran wajib dibahas bersama dewan legislatif untuk memperoleh persetujuan sebelum ditetapkan menjadi undang-undang resmi. Maka dari itu, opsi A benar.</p><p>Jawaban tepat: <strong>B. Pembahasan APBN bersama DPR kemudian Penetapan APBN</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pemeriksaan keuangan daerah bukan tahapan inti penetapan APBN pusat.</li><li><strong>C</strong>: Penerbitan utang adalah mekanisme pembiayaan pada tahap pelaksanaan.</li><li><strong>D</strong>: Restrukturisasi bank bukan tahapan siklus anggaran reguler.</li><li><strong>E</strong>: Penyerahan aset kepada swasta bukan tahapan dalam siklus APBN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 28) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>3.Pembahasan APBN • 4.Penetapan APBN</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Dalam siklus APBN, kegiatan pembukuan transaksi keuangan yang berjalan seiring dengan realisasi belanja berada pada tahap...", "options": ["Pembahasan alokasi pagu indikatif pada komisi parlemen", "Perencanaan strategis dan penentuan batas defisit", "Pelaksanaan APBN serta Pelaporan dan Pencatatan APBN", "Pengesahan nota keuangan oleh majelis permusyawaratan", "Pemberian opini wajar tanpa pengecualian oleh auditor"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tahap Pelaksanaan dan Pelaporan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Tahap kelima dan keenam dalam siklus APBN adalah <strong>5. Pelaksanaan APBN dan 6. Pelaporan dan Pencatatan APBN</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena saat anggaran dibelanjakan (pelaksanaan), seluruh aliran kas masuk dan keluar dicatat secara akrual dalam sistem akuntansi (pelaporan). Oleh sebab itu, opsi A benar.</p><p>Jawaban tepat: <strong>C. Pelaksanaan APBN serta Pelaporan dan Pencatatan APBN</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pembahasan komisi merupakan tahap legislasi politik anggaran.</li><li><strong>B</strong>: Perencanaan strategis mendahului tahun pelaksanaan anggaran.</li><li><strong>D</strong>: Nota keuangan disahkan oleh Presiden dan DPR, bukan MPR.</li><li><strong>E</strong>: Opini audit diberikan pada tahap akhir pemeriksaan BPK.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 28) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>5.Pelaksanaan APBN • 6.Pelaporan dan Pencatatan APBN</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Tahap penutup dalam satu rangkaian siklus utuh pengelolaan APBN ditandai oleh...", "options": ["Penetapan pagu belanja dinas lima tahun berikutnya", "Penandatanganan nota kesepahaman pinjaman perbankan baru", "Pelelangan seluruh sisa persediaan barang dinas kantor", "Pemeriksaan oleh BPK dan pertanggungjawaban kepada DPR", "Pembagian sisa lebih pembiayaan anggaran kepada kementerian"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tahap Penutup Siklus APBN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Tahap terakhir dalam siklus pengelolaan APBN adalah <strong>7. Pemeriksaan dan Pertanggungjawaban APBN</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena akuntabilitas publik menuntut hasil audit eksternal disampaikan kepada parlemen dalam bentuk RUU Pertanggungjawaban Pelaksanaan APBN. Oleh karena itu, opsi A benar.</p><p>Jawaban tepat: <strong>D. Pemeriksaan oleh BPK dan pertanggungjawaban kepada DPR</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penetapan pagu lima tahunan adalah dokumen perencanaan RPJM.</li><li><strong>B</strong>: Pinjaman baru merupakan bagian dari siklus anggaran tahun berjalan.</li><li><strong>C</strong>: Pelelangan sisa barang merupakan tata kelola barang milik negara.</li><li><strong>E</strong>: SiLPA dibukukan kembali ke kas negara, bukan dibagi ke kementerian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 28) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>7.Pemeriksaan dan Pertanggungjawaban APBN • Pengelolaan APBN secara keseluruhan dilakukan melalui:</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Tahap perencanaan dan penganggaran APBN untuk suatu tahun anggaran secara resmi dimulai pada...", "options": ["Setelah laporan hasil pemeriksaan audit diserahkan BPK", "Akhir bulan Desember pada saat penutupan buku tahun berjalan", "Pertengahan tahun anggaran berkenaan saat realisasi belanja", "Tepat tanggal satu Januari saat dokumen DIPA disahkan", "Awal tahun sebelum tahun anggaran yang bersangkutan dilaksanakan"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Waktu Dimulainya Perencanaan APBN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Tahap perencanaan dan penganggaran dimulai pada awal tahun sebelum tahun anggaran dilaksanakan (t-1)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena penyusunan anggaran membutuhkan waktu persiapan sepanjang satu tahun sebelum tahun eksekusi anggaran dimulai. Maka dari itu, opsi A benar.</p><p>Jawaban tepat: <strong>E. Awal tahun sebelum tahun anggaran yang bersangkutan dilaksanakan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penyerahan hasil audit BPK terjadi setelah tahun anggaran berakhir.</li><li><strong>B</strong>: Akhir Desember adalah masa penutupan buku anggaran berjalan.</li><li><strong>C</strong>: Pertengahan tahun anggaran adalah jadwal evaluasi semester satu.</li><li><strong>D</strong>: Tanggal satu Januari adalah awal masa berlaku DIPA tahun berjalan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 29) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Tahap perencanaan dan penganggaran dimulai pada awal tahun sebelum tahun • anggaran dilaksanakan.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Inisiasi awal proses perencanaan APBN diawali dengan agenda Presiden yang menyampaikan...", "options": ["Arah kebijakan untuk satu tahun ke depan pada sidang kabinet", "Daftar pemenang lelang tender proyek infrastruktur nasional", "Vonis sanksi disiplin bagi pimpinan kementerian yang lalai", "Instruksi penarikan pinjaman komersial luar negeri baru", "Penetapan batas minimum saldo tabungan nasabah perbankan"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Inisiasi Arah Kebijakan oleh Presiden</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Tahap perencanaan <strong>diawali dengan presiden menyampaikan arah kebijakan untuk satu tahun ke depan pada sidang kabinet</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena arahan Presiden pada sidang kabinet menjadi rujukan tunggal bagi kementerian dalam menyusun prioritas dan sasaran kerja nasional. Oleh sebab itu, opsi A benar.</p><p>Jawaban tepat: <strong>A. Arah kebijakan untuk satu tahun ke depan pada sidang kabinet</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Pemenang lelang proyek ditetapkan oleh pokja pemilihan penyedia.</li><li><strong>C</strong>: Sanksi disiplin aparatur diproses melalui majelis penegakan kode etik.</li><li><strong>D</strong>: Pinjaman luar negeri dirumuskan melalui analisis kesinambungan fiskal.</li><li><strong>E</strong>: Saldo minimum tabungan merupakan kebijakan operasional perbankan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 29) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>diawali dengan presiden menyampaikan • arah kebijakan untuk satu tahun ke depan pada sidang kabinet.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Kapasitas fiskal (resource envelope) yang disusun oleh Menteri Keuangan didefinisikan sebagai...", "options": ["Batas simpanan kas darurat pada lemari brankas kementerian", "Kemampuan keuangan negara yang dihimpun guna mendanai belanja negara", "Jumlah pinjaman valas yang diberikan kepada negara tetangga", "Daftar aset tanah sitaan tindak pidana korporasi komersial", "Total modal kerja perseroan terbatas milik badan usaha swasta"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Kapasitas Fiskal (Resource Envelope)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Kapasitas fiskal (resource envelope) adalah kemampuan keuangan negara yang dihimpun dari pendapatan negara untuk mendanai anggaran belanja negara yang meliputi Belanja K/L & Belanja Non K/L</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena ruang fiskal menggambarkan total sumber daya pendapatan yang realistis dapat dimobilisasi pemerintah untuk membiayai program pembangunan. Oleh karena itu, opsi A benar.</p><p>Jawaban tepat: <strong>B. Kemampuan keuangan negara yang dihimpun guna mendanai belanja negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Batas brankas kementerian adalah urusan brankas kas kecil dinas.</li><li><strong>C</strong>: Pinjaman ke negara lain merupakan instrumen hubungan luar negeri.</li><li><strong>D</strong>: Aset sitaan tindak pidana merupakan barang rampasan kejaksaan.</li><li><strong>E</strong>: Modal kerja korporasi swasta merupakan dana usaha bisnis privat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 29) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Kapasitas fiskal (resource envelope) adalah kemampuan • keuangan negara yang dihimpun dari pendapatan negara untuk mendanai</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Setelah kapasitas fiskal tersusun, pejabat yang bersama-sama menetapkan ancar-ancar pagu anggaran bagi kementerian/lembaga adalah...", "options": ["Pimpinan Komisi Anggaran dan Jaksa Agung", "Ketua BPK dan Gubernur Bank Indonesia", "Menteri Keuangan dan Kepala Bappenas", "Menteri Dalam Negeri dan Ketua Kadin", "Sekretaris Kabinet dan Dewan Perseroan"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penetapan Pagu Anggaran K/L</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dengan tersusunnya kapasitas fiskal, <strong>Menteri Keuangan dan Kepala Bappenas menetapkan ancar-ancar pagu anggaran yg disampaikan kepada K/L sebagai pedoman penyusunan Renja K/L</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena Kemenkeu bertindak dari sisi alokasi fiskal makro dan Bappenas bertindak dari sisi prioritas rencana pembangunan nasional. Maka dari itu, opsi A benar.</p><p>Jawaban tepat: <strong>C. Menteri Keuangan dan Kepala Bappenas</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Jaksa Agung bertugas di bidang penegakan hukum negara.</li><li><strong>B</strong>: Ketua BPK mengaudit dan Gubernur BI mengelola moneter.</li><li><strong>D</strong>: Ketua Kadin memimpin organisasi kamar dagang dan industri.</li><li><strong>E</strong>: Sekretaris Kabinet memfasilitasi administrasi kabinet kepresidenan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 29) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Menteri Keuangan dan Kepala Bappenas • menetapkan ancar- ancar pagu anggaran yg disampaikan kepada K/L sebagai</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Nota Keuangan dan RUU APBN beserta himpunan RKA-KL wajib disampaikan oleh pemerintah kepada DPR paling lambat pada...", "options": ["Pertengahan bulan Maret", "Akhir bulan Desember", "Awal bulan Januari", "Pertengahan bulan Agustus", "Akhir bulan Oktober"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Batas Waktu Penyampaian RUU APBN ke DPR</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>RUU APBN dan Nota Keuangan disampaikan pemerintah kepada DPR <strong>selambat-lambatnya pertengahan Agustus (tradisi pidato Presiden 16 Agustus)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena batas waktu konstitusional pertengahan Agustus memberikan waktu yang cukup bagi parlemen untuk membahas RUU sebelum disahkan pada bulan Oktober. Oleh sebab itu, opsi A benar.</p><p>Jawaban tepat: <strong>D. Pertengahan bulan Agustus</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pertengahan Maret merupakan tahap pembicaraan pendahuluan kapasitas fiskal.</li><li><strong>B</strong>: Akhir Desember adalah batas waktu penerbitan DIPA siap salur.</li><li><strong>C</strong>: Awal Januari adalah tanggal dimulainya tahun anggaran baru.</li><li><strong>E</strong>: Akhir Oktober adalah batas waktu persetujuan bersama RUU APBN di DPR.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 29) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Nota Keuangan dan RUU APBN beserta Himpunan RKA-KL yang telah dibahas • dalam Sidang Kabinet disampaikan pemerintah kepada DPR selambatlambatnya pertengahan Agustus.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Pembahasan RUU APBN dan Nota Keuangan antara pemerintah dan DPR diselenggarakan melalui dua forum rapat kerja, yaitu...", "options": ["Rapat gabungan dengan serikat buruh industri manufaktur daerah", "Rapat kerja dengan Mahkamah Konstitusi dan Dewan Perwakilan Daerah", "Rapat koordinasi dengan asosiasi perbankan dan bursa pasar efek", "Rapat dengar pendapat dengan konsultan perencana keuangan swasta", "Rapat kerja dengan Badan Anggaran dan rapat kerja dengan Komisi DPR"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dua Tahapan Rapat Kerja Pembahasan RUU APBN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pembahasan RUU APBN dilakukan pemerintah dengan DPR melalui dua tahapan yaitu <strong>rapat kerja dengan Badan Anggaran dan rapat kerja dengan Komisi DPR</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena Banggar membahas asumsi makro dan postur fiskal agregat, sedangkan Komisi I sampai XI membahas rincian anggaran sektoral kementerian mitra kerja. Oleh karena itu, opsi A benar.</p><p>Jawaban tepat: <strong>E. Rapat kerja dengan Badan Anggaran dan rapat kerja dengan Komisi DPR</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Serikat buruh menyampaikan aspirasi ketenagakerjaan melalui audiensi.</li><li><strong>B</strong>: Mahkamah Konstitusi menguji konstitusionalitas undang-undang yudikatif.</li><li><strong>C</strong>: Asosiasi perbankan bukan lembaga perwakilan rakyat pembahas undang-undang.</li><li><strong>D</strong>: Konsultan swasta bukan mitra resmi penetapan undang-undang APBN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 29) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pembahasan RUU APBN dan Nota Keuangan dilakuan pemerintah dengan DPR • melalui dua tahapan yaitu rapat kerja dengan Badan Anggaran dan rapat kerja</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Dalam rapat kerja pembahasan anggaran dengan Badan Anggaran DPR, pemerintah secara resmi diwakili oleh...", "options": ["Menteri Keuangan dan Kepala Bappenas", "Menteri Pertahanan dan Panglima Militer", "Gubernur Bank Indonesia dan Ketua OJK", "Menteri Koordinator Bidang Politik dan Hukum", "Ketua Badan Pemeriksa Keuangan Republik Indonesia"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Wakil Pemerintah dalam Rapat Badan Anggaran</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam rapat kerja dengan Badan Anggaran DPR, <strong>Pemerintah diwakili oleh Menteri Keuangan dan Bappenas</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena Badan Anggaran menelaah kerangka ekonomi makro dan pokok-pokok kebijakan fiskal yang menjadi domain otoritas Kemenkeu dan Bappenas. Maka dari itu, opsi A benar.</p><p>Jawaban tepat: <strong>A. Menteri Keuangan dan Kepala Bappenas</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Menhan dan Panglima militer membahas anggaran pertahanan di Komisi I.</li><li><strong>C</strong>: Gubernur BI dan OJK hadir sebagai otoritas moneter dan jasa keuangan.</li><li><strong>D</strong>: Menko Polhukam mengoordinasikan bidang politik dan hukum kabinet.</li><li><strong>E</strong>: Ketua BPK bertindak sebagai auditor eksternal pemeriksa anggaran.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 29) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Rapat kerja dengan Badan Anggaran, Pemerintah diwakili oleh Menteri • Keuangan dan Bappenas.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Pada rapat kerja komisi I sampai komisi XI DPR untuk membahas RKA-KL, pihak pemerintah diwakili oleh...", "options": ["Direktur jenderal anggaran kementerian keuangan seorang diri", "Menteri atau Kepala Lembaga sesuai bidang mitra kerja komisi", "Pejabat perbendaharaan perwakilan dari seluruh kantor dinas", "Kepala badan pendapatan daerah seluruh wilayah provinsi", "Direktur operasional bank sentral perwakilan daerah kota"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Wakil Pemerintah dalam Rapat Komisi DPR</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pada rapat kerja Komisi I sampai Komisi XI, <strong>Pemerintah diwakili oleh Menteri/Ketua Lembaga sesuai mitra kerja komisi</strong> untuk membahas RKA-K/L.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena pimpinan kementerian teknis bertanggung jawab mempertahankan program kerja dan keluaran anggaran instansi yang dipimpinnya. Oleh sebab itu, opsi A benar.</p><p>Jawaban tepat: <strong>B. Menteri atau Kepala Lembaga sesuai bidang mitra kerja komisi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Dirjen Anggaran mengawal substansi fiskal, bukan memimpin seluruh kementerian.</li><li><strong>C</strong>: Pejabat perbendaharaan daerah mengurus eksekusi kas operasional.</li><li><strong>D</strong>: Bapenda daerah mengurus pendapatan asli daerah dalam APBD.</li><li><strong>E</strong>: Direktur bank sentral daerah mengurus sistem pembayaran moneter.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 29) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Rapat kerja komisi I sampai komisi XI, Pemerintah • diwakili oleh Menteri/Ketua Lembaga sesuai mitra kerja komisi.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Setelah menerima dokumen RUU APBN dari Presiden, pimpinan DPR menyampaikan pemberitahuan pembahasan anggaran kepada...", "options": ["Majelis Kehormatan Kode Etik Aparatur Kementerian", "Dewan Pertimbangan Perbankan Komersial Nasional", "Dewan Perwakilan Daerah (DPD) Republik Indonesia", "Lembaga Arbitrase Sengketa Industri Ekspor Impor", "Asosiasi Pengusaha Mineral Tambang dan Batubara"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pemberitahuan kepada DPD RI</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Pimpinan DPR menyampaikan pemberitahuan kepada DPD rencana pembahasan RUU APBN</strong> untuk memberikan pertimbangan terutama terkait perimbangan keuangan daerah.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena konstitusi memberikan hak kepada DPD untuk memberikan pertimbangan tertulis atas RUU APBN yang berkaitan dengan daerah. Oleh karena itu, opsi A benar.</p><p>Jawaban tepat: <strong>C. Dewan Perwakilan Daerah (DPD) Republik Indonesia</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Majelis kehormatan etik mengawasi disiplin perilaku pegawai.</li><li><strong>B</strong>: Dewan pertimbangan bank komersial bukan lembaga perwakilan kenegaraan.</li><li><strong>D</strong>: Lembaga arbitrase menangani perselisihan kontrak bisnis perdata.</li><li><strong>E</strong>: Asosiasi pengusaha tambang merupakan wadah pelaku usaha industri.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 29) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pimpinan DPR menyampaikan pemberitahuan • kepada DPD rencana pembahasan RUU APBN.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Kewenangan DPR untuk memberikan usulan dan penyesuaian terhadap materi RKA-KL dalam RUU APBN bersumber dari...", "options": ["Hak prerogatif eksekutif dalam mencairkan kas belanja", "Hak imunitas perdata direksi badan usaha milik negara", "Kewenangan diskresi perorangan anggota dewan pembina", "Hak budget (hak anggaran) yang dimiliki parlemen", "Kewenangan yudisial peninjauan kembali keputusan kasir"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Hak Budget Dewan Perwakilan Rakyat</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>DPR dapat memberikan usulan sesuai dengan hak budget yang dimilikinya</strong>, sehingga RKA-KL sebagai bahan RUU APBN dapat disesuaikan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena hak budget merupakan kewenangan konstitusional DPR untuk membahas, menyetujui, atau menolak rencana pengeluaran kas negara. Maka dari itu, opsi A benar.</p><p>Jawaban tepat: <strong>D. Hak budget (hak anggaran) yang dimiliki parlemen</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Hak prerogatif pencairan kas berada pada ranah eksekutif perbendaharaan.</li><li><strong>B</strong>: Hak imunitas perdata direksi BUMN bukan dasar penetapan APBN.</li><li><strong>C</strong>: Kewenangan diskresi perorangan bukan dasar hukum kelembagaan DPR.</li><li><strong>E</strong>: Peninjauan yudisial adalah kewenangan lembaga peradilan yudikatif.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 29) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>DPR dapat memberikan usulan sesuai • dengan hak budget yang dimilikinya.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Persetujuan atau penolakan resmi atas RUU APBN oleh DPR diambil dalam forum tertinggi parlemen, yaitu...", "options": ["Rapat kerja pimpinan satker kementerian teknis dinas", "Rapat konsultasi informal pimpinan komisi dengan asosiasi", "Sidang mediasi perdata sengketa perbankan komersial daerah", "Pertemuan tahunan dewan gubernur bank sentral regional", "Rapat Paripurna DPR setelah penyampaian pandangan akhir fraksi"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengambilan Keputusan di Rapat Paripurna DPR</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam siklus penetapan, <strong>rapat paripurna DPR menyatakan persetujuan/penolakan dari setiap fraksi serta penyampaian pendapat akhir Pemerintah</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena keputusan sah pengambilan persetujuan undang-undang APBN secara tata tertib parlemen hanya dapat diketok dalam forum rapat paripurna. Oleh sebab itu, opsi A benar.</p><p>Jawaban tepat: <strong>E. Rapat Paripurna DPR setelah penyampaian pandangan akhir fraksi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Rapat kerja pimpinan satker adalah koordinasi operasional dinas kementerian.</li><li><strong>B</strong>: Rapat konsultasi informal tidak memiliki kekuatan hukum penetapan undang-undang.</li><li><strong>C</strong>: Mediasi perdata sengketa bank adalah proses peradilan perdata niaga.</li><li><strong>D</strong>: Pertemuan dewan gubernur bank sentral merupakan forum otoritas moneter.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 30) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Selanjutnnya rapat paripurna DPR untuk • menyatakan persetujuan/penolakan dari setiap fraksi serta penyampaian</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Agar RUU APBN yang telah disetujui dalam rapat paripurna DPR dapat berlaku efektif sebagai dasar pelaksanaan anggaran, langkah hukumnya adalah...", "options": ["Presiden mengesahkan RUU APBN tersebut menjadi Undang-Undang APBN", "Menteri Keuangan mendaftarkannya pada lembaga bursa efek swasta", "Pemerintah menerbitkan surat utang komersial jangka pendek baru", "Gubernur Bank Indonesia menandatangani lembar persetujuan giro", "Ketua komisi dewan membagikan dokumen pagu kepada rekanan dinas"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Keberlakuan Efektif UU APBN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Agar RUU APBN dapat berlaku efektif, <strong>presiden mengesahkan RUU APBN itu menjadi UU APBN</strong> sesuai ketentuan perundang-undangan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena pengesahan Presiden memberikan kekuatan hukum mengikat bagi seluruh penyelenggara negara untuk mengeksekusi anggaran APBN. Oleh karena itu, opsi A benar.</p><p>Jawaban tepat: <strong>A. Presiden mengesahkan RUU APBN tersebut menjadi Undang-Undang APBN</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Pendaftaran pada bursa efek merupakan prosedur perdagangan instrumen saham.</li><li><strong>C</strong>: Penerbitan surat utang dilakukan saat pembiayaan defisit anggaran berjalan.</li><li><strong>D</strong>: Gubernur BI tidak menandatangani pengesahan undang-undang negara.</li><li><strong>E</strong>: Pembagian dokumen kepada rekanan dinas bukan pengesahan legal undang-undang.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 30) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>agar RUU APBN yang telah disetujui DPR dapat • berlaku efektif maka presiden mengesahkan RUU APBN itu menjadi UU APBN</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Setelah UU APBN disetujui DPR dan disahkan oleh Presiden, langkah teknis pemerintah selanjutnya adalah...", "options": ["Mencairkan dana belanja langsung tanpa bukti verifikasi tagihan", "Menetapkan rincian Alokasi Anggaran Kementerian/Lembaga", "Menjual seluruh gedung kantor kementerian kepada konsorsium", "Menghapuskan sistem pengawasan internal pada inspektorat", "Mewajibkan pegawai aparatur menyumbangkan gaji ke kasir"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penetapan Alokasi Anggaran K/L</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Setelah UU APBN disahkan, <strong>tugas pemerintah selanjutnya adalah menetapkan Alokasi Anggaran KL</strong> berpedoman pada hasil pembahasan APBN.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena penetapan alokasi anggaran K/L merinci plafon belanja definitif setiap instansi sebelum dokumen DIPA diterbitkan. Maka dari itu, opsi A benar.</p><p>Jawaban tepat: <strong>B. Menetapkan rincian Alokasi Anggaran Kementerian/Lembaga</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pencairan dana tanpa verifikasi melanggar tertib perbendaharaan negara.</li><li><strong>C</strong>: Gedung kementerian adalah barang milik negara yang dilindungi undang-undang.</li><li><strong>D</strong>: Pengawasan internal inspektorat mutlak dipertahankan secara berkelanjutan.</li><li><strong>E</strong>: Gaji aparatur merupakan hak pegawai yang dilindungi undang-undang ASN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 30) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Setelah UU APBN disetujui DPR dan disahkan oleh presiden, tugas pemerintah • selanjutnya adalah menetapkan Alokasi Anggaran KL.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan dokumen alokasi anggaran definitif, kementerian/lembaga segera melakukan penyesuaian RKA-KL yang digunakan sebagai dasar...", "options": ["Penarikan simpanan dana nasabah perorangan dari bank devisa", "Penerbitan surat izin usaha komersial bagi perbankan swasta", "Penyusunan Daftar Isian Pelaksanaan Anggaran (DIPA)", "Pelunasan seluruh pokok pinjaman obligasi jangka panjang", "Pengalihan sebagian aset tanah kantor kepada rekanan bisnis"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penyesuaian RKA-KL sebagai Dasar DIPA</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Berdasarkan alokasi anggaran, <strong>Kementerian/Lembaga menyesuaikan RKA-K/L sebagai dasar penyusunan Daftar Isian Pelaksanaan Anggaran (DIPA)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena DIPA merupakan dokumen eksekusi anggaran operasional yang diturunkan dari RKA-KL yang telah diselaraskan dengan undang-undang APBN. Oleh sebab itu, opsi A benar.</p><p>Jawaban tepat: <strong>C. Penyusunan Daftar Isian Pelaksanaan Anggaran (DIPA)</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penarikan simpanan nasabah adalah hak perdata pemilik rekening bank.</li><li><strong>B</strong>: Izin usaha bank komersial merupakan kewenangan Otoritas Jasa Keuangan.</li><li><strong>D</strong>: Pelunasan obligasi dilakukan sesuai jadwal jatuh tempo surat utang.</li><li><strong>E</strong>: Aset tanah kantor negara tidak boleh dialihkan sembarangan kepada rekanan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 30) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Berdasarkan alokasi anggaran, maka Kementerian/Lembaga • menyesuaikan RKA-K/L sebagai dasar penyusunan Daftar Isian Pelaksanaan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Awal mula tahapan pelaksanaan APBN secara resmi ditandai oleh peristiwa hukum berupa...", "options": ["Pemberian dana pinjaman tanpa agunan bagi korporasi swasta", "Pengumuman suku bunga pinjaman oleh bank sentral daerah", "Pelelangan aset gedung perkantoran dinas kementerian teknis", "Pengesahan dokumen pelaksanaan anggaran (DIPA) oleh Menteri Keuangan", "Penyerahan laporan hasil pemeriksaan berkala dari auditor BPK"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengesahan DIPA oleh Menteri Keuangan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Pelaksanaan anggaran diawali dengan disahkannya dokumen pelaksanaan anggaran (DIPA) oleh Menteri Keuangan</strong> selaku Bendahara Umum Negara.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena pengesahan DIPA oleh Menteri Keuangan memberikan dasar legalitas bagi satuan kerja untuk mulai mengadakan perikatan belanja. Oleh karena itu, opsi A benar.</p><p>Jawaban tepat: <strong>D. Pengesahan dokumen pelaksanaan anggaran (DIPA) oleh Menteri Keuangan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pinjaman tanpa agunan swasta merupakan produk kredit perbankan.</li><li><strong>B</strong>: Pengumuman suku bunga bank sentral merupakan ranah kebijakan moneter.</li><li><strong>C</strong>: Lelang gedung kantor kementerian bukan penanda pelaksanaan APBN.</li><li><strong>E</strong>: Penyerahan hasil audit BPK adalah tahap akhir pertanggungjawaban APBN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 30) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pelaksanaan anggaran diawali dengan disahkannya dokumen pelaksanaan • anggaran (DIPA) oleh Menteri Keuangan.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Dokumen DIPA yang telah disahkan oleh Menteri Keuangan wajib disampaikan kepada pihak-pihak terkait, antara lain...", "options": ["Asosiasi pialang properti perumahan tapak kawasan kota", "Direksi bursa efek, konsultan hukum swasta, dan pemodal asing", "Pengurus paguyuban pasar malam dan serikat pedagang keliling", "Dewan penasihat lembaga perbankan komersial internasional", "Menteri/Pimpinan Lembaga, BPK, Gubernur, Dirjen Perbendaharaan, dan KPPN"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Distribusi Dokumen DIPA yang Telah Disahkan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>DIPA disampaikan kepada <strong>menteri/pimpinan lembaga, BPK, Gubernur, Dirjen Perbendaharaan, Kepala Kanwil DJPb, KPPN terkait, dan Kuasa Pengguna Anggaran (KPA)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena distribusi dokumen DIPA memastikan seluruh lini pengawasan, perbendaharaan, dan eksekutor anggaran memegang rujukan pagu yang sama. Maka dari itu, opsi A benar.</p><p>Jawaban tepat: <strong>E. Menteri/Pimpinan Lembaga, BPK, Gubernur, Dirjen Perbendaharaan, dan KPPN</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Asosiasi pialang properti merupakan organisasi usaha perantara swasta.</li><li><strong>B</strong>: Bursa efek dan pemodal asing bukan instansi penerima distribusi DIPA resmi.</li><li><strong>C</strong>: Paguyuban pasar malam merupakan perhimpunan informal masyarakat.</li><li><strong>D</strong>: Dewan penasihat bank swasta bukan pengelola keuangan publik negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 30) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Dokumen anggaran yang telah • disahkan oleh Menteri Keuangan disampaikan kepada menteri/pimpinan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Bagi kementerian/lembaga dan Bendahara Umum Negara, kedudukan dokumen DIPA berfungsi sebagai...", "options": ["Acuan dan dasar hukum pelaksanaan APBN yang mengikat secara operasional", "Saran rekomendasi yang bebas diabaikan oleh para pejabat perbendaharaan", "Dokumen promosi penanaman modal komersial pada pasar saham luar negeri", "Katalog penawaran harga barang elektronik dari penyedia lelang dinas", "Pedoman pinjaman komersial tanpa bunga dari perbankan daerah"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kedudukan Yuridis DIPA</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>DIPA <strong>merupakan acuan dan dasar hukum pelaksanaan APBN yang dilakukan oleh Kementerian/Lembaga dan Bendahara Umum Negara</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena satker tidak boleh mengeluarkan kas negara sepeser pun tanpa adanya alokasi kredit anggaran yang sah di dalam DIPA. Oleh sebab itu, opsi A benar.</p><p>Jawaban tepat: <strong>A. Acuan dan dasar hukum pelaksanaan APBN yang mengikat secara operasional</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: DIPA mengikat secara hukum dan tidak boleh diabaikan oleh aparatur.</li><li><strong>C</strong>: DIPA bukan sarana promosi penanaman modal komersial bursa saham.</li><li><strong>D</strong>: Katalog harga barang lelang dikelola dalam e-katalog LKPP.</li><li><strong>E</strong>: DIPA memuat belanja negara resmi, bukan pedoman pinjaman bank daerah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 30) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Dokumen tersebut merupakan acuan dan dasar hukum pelaksanaan APBN yang • dilakukan oleh Kementerian/Lembaga dan Bendahara Umum Negara.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Instrumen utama yang berfungsi sebagai dokumen pelaksanaan anggaran dan dokumen yang dipersamakan dengannya adalah...", "options": ["Surat Izin Mengemudi Kendaraan Dinas Operasional", "Daftar Isian Pelaksanaan Anggaran (DIPA)", "Buku Tabungan Sukarela Pegawai Aparatur Kementerian", "Kuitansi Pembelian Bahan Makanan Pribadi Pejabat", "Sertifikat Deposito Berjangka Perbankan Swasta"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dokumen Penting Pelaksanaan Anggaran</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dokumen-dokumen penting dalam pelaksanaan anggaran adalah <strong>Daftar Isian Pelaksanaan Anggaran (DIPA) dan dokumen lain yang dipersamakan dengan DIPA</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena DIPA adalah dokumen otorisasi anggaran pokok yang memuat rincian sasaran, fungsi, program, dan pagu dana satuan kerja. Oleh karena itu, opsi A benar.</p><p>Jawaban tepat: <strong>B. Daftar Isian Pelaksanaan Anggaran (DIPA)</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: SIM dinas adalah lisensi mengemudi kepolisian, bukan dokumen anggaran.</li><li><strong>C</strong>: Buku tabungan sukarela adalah rekening perorangan milik individu.</li><li><strong>D</strong>: Kuitansi makanan pribadi pejabat bukan dokumen pelaksanaan APBN dinas.</li><li><strong>E</strong>: Sertifikat deposito adalah instrumen penempatan dana perbankan komersial.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 30) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Dokumendokumen • penting • dalam • pelaksanaan • anggaran • adalah</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Rangkaian dokumen pembayaran tagihan belanja negara yang diproses secara berjenjang terdiri dari...", "options": ["Surat Perjanjian Utang, Cek Perorangan, dan Bilyet Giro Dinas", "Nota Debit, Nota Kredit, dan Lembar Kliring Perbankan Swasta", "Surat Permintaan Pembayaran, Surat Perintah Membayar, dan SP2D", "Faktur Pajak Standar, Kuitansi Konsinyasi, dan Polis Asuransi Jiwa", "Kupon Obligasi Komersial, Surat Saham Emiten, dan Buku Rekening Kasir"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tiga Dokumen Pembayaran APBN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dokumen pembayaran terdiri dari <strong>Surat Permintaan Pembayaran (SPP), Surat Perintah Membayar (SPM), dan Surat Perintah Pencairan Dana (SP2D)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena ketiganya mencerminkan pemisahan fungsi: PPK menerbitkan SPP, PPSPM menguji dan menerbitkan SPM, lalu KPPN mencairkan via SP2D. Maka dari itu, opsi A benar.</p><p>Jawaban tepat: <strong>C. Surat Permintaan Pembayaran, Surat Perintah Membayar, dan SP2D</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Cek perorangan dilarang dalam sistem pencairan langsung belanja APBN.</li><li><strong>B</strong>: Nota debit dan kredit merupakan dokumen penyesuaian transaksi bank.</li><li><strong>D</strong>: Polis asuransi jiwa adalah kontrak proteksi jiwa individu asuransi.</li><li><strong>E</strong>: Kupon obligasi dan saham merupakan efek bursa pasar modal.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 30) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Sedangkan dokumen pembayaran antara lain terdiri dari Surat Permintaan • Pembayaran (SPP), Surat Perintah Membayar (SPM), dan Surat Perintah</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Dokumen perintah pembayaran yang diterbitkan oleh KPPN selaku Kuasa Bendahara Umum Negara untuk memindahbukukan kas adalah...", "options": ["Surat Bukti Penerimaan Setoran Pajak", "Surat Izin Prinsip Pelaksanaan Pengadaan", "Surat Perjanjian Kerja Kontrak Rekanan", "Surat Perintah Pencairan Dana (SP2D)", "Surat Tugas Perjalanan Dinas Pejabat"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penerbitan Surat Perintah Pencairan Dana (SP2D)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Surat Perintah Pencairan Dana (SP2D)</strong> adalah dokumen pencairan akhir yang diterbitkan KPPN kepada bank persepsi/operasional untuk mentransfer kas negara kepada penerima hak.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena SP2D merupakan surat perintah eksekusi pemindahbukuan uang dari Rekening Kas Umum Negara kepada rekening pihak ketiga yang sah. Oleh sebab itu, opsi A benar.</p><p>Jawaban tepat: <strong>D. Surat Perintah Pencairan Dana (SP2D)</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Bukti penerimaan pajak adalah dokumen kas masuk penerimaan negara.</li><li><strong>B</strong>: Izin prinsip adalah persetujuan awal perencanaan pengadaan dinas.</li><li><strong>C</strong>: Surat perjanjian kerja adalah kontrak perikatan antara PPK dan rekanan.</li><li><strong>E</strong>: Surat tugas adalah dasar penugasan operasional kepegawaian dinas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 30) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pencairan Dana (SP2D). • Sedangkan dokumen pembayaran antara lain terdiri dari Surat Permintaan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Penetapan alokasi anggaran kementerian/lembaga disusun oleh pemerintah dengan berpedoman pada...", "options": ["Catatan pembukuan kas darurat bendahara pengeluaran tahun sebelumnya", "Hasil wawancara media massa dengan perwakilan pimpinan lembaga swasta", "Laporan omzet penjualan tahunan para pelaku usaha ritel modern daerah", "Daftar rekomendasi investasi saham dari konsultan manajemen asing", "Berita Acara Hasil Kesepakatan Pembahasan RUU APBN antara Pemerintah dan DPR"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dasar Penetapan Alokasi Anggaran K/L</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Alokasi Anggaran KL berpedoman pada hasil pembahasan APBN yang dituangkan dalam <strong>Berita Acara Hasil Kesepakatan Pembahasan Rancangan APBN antara Pemerintah dan DPR</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena berita acara kesepakatan memuat komitmen angka hasil pembahasan resmi antara Banggar DPR dan pemerintah yang mengikat secara yuridis. Oleh karena itu, opsi A benar.</p><p>Jawaban tepat: <strong>E. Berita Acara Hasil Kesepakatan Pembahasan RUU APBN antara Pemerintah dan DPR</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kas darurat bendahara bukan dasar perhitungan alokasi pagu K/L.</li><li><strong>B</strong>: Wawancara media massa bukan dokumen hukum administrasi keuangan negara.</li><li><strong>C</strong>: Omzet usaha ritel merupakan data kegiatan ekonomi bisnis swasta.</li><li><strong>D</strong>: Rekomendasi investasi asing bukan dasar alokasi APBN resmi negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 30) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Alokasi Anggaran KL ini • berpedoman pada hasil pembahasan APBN yang dituangkan dalam Berita</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan Pasal 17 UU Perbendaharaan Negara, Pengguna Anggaran atau Kuasa Pengguna Anggaran berwenang untuk...", "options": ["Mengadakan ikatan atau perjanjian dengan pihak lain dalam batas anggaran DIPA", "Mencetak mata uang baru secara mandiri demi mendanai kegiatan operasional", "Menyimpan seluruh penerimaan negara pada rekening pribadi tanpa pencatatan", "Mengubah pagu alokasi belanja tanpa melalui proses revisi anggaran resmi", "Menyerahkan tanggung jawab keuangan kepada rekanan penyedia swasta"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kewenangan PA/KPA menurut Pasal 17 UU 1/2004</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pasal 17 UU Perbendaharaan Negara menyatakan <strong>PA/KPA melaksanakan kegiatan dalam dokumen DIPA dan berwenang mengadakan ikatan/perjanjian dengan pihak lain dalam batas anggaran yang telah ditetapkan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena PA/KPA adalah ordonatur yang memegang kewenangan administratif untuk membuat komitmen pengeluaran atas beban APBN. Maka dari itu, opsi A benar.</p><p>Jawaban tepat: <strong>A. Mengadakan ikatan atau perjanjian dengan pihak lain dalam batas anggaran DIPA</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Pencetakan mata uang merupakan kewenangan eksklusif Bank Sentral.</li><li><strong>C</strong>: Menyimpan dana negara di rekening pribadi melanggar hukum perbendaharaan.</li><li><strong>D</strong>: Perubahan pagu belanja wajib melalui prosedur revisi anggaran resmi.</li><li><strong>E</strong>: Tanggung jawab keuangan negara melekat mutlak pada pejabat PA/KPA.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 31) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pasal 17 Undang-Undang Perbendaharaan Negara menyatakan bahwa • Pengguna Anggaran/Kuasa Pengguna Anggaran melaksanakan kegiatan yang</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Pedoman tata cara teknis pelaksanaan Anggaran Pendapatan dan Belanja Negara diatur secara komprehensif dalam...", "options": ["Peraturan Asosiasi Pedagang Pasar Tradisional Indonesia", "PP Nomor 45 Tahun 2013 dan perubahannya PP Nomor 50 Tahun 2018", "Instruksi Khusus Lembaga Pemeringkat Obligasi Multinasional", "Keputusan Dewan Penasihat Perbankan Syariah Komersial", "Pedoman Penyelenggaraan Lelang Seni dan Barang Antik Daerah"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Regulasi Pelaksanaan APBN (PP 45/2013 jo PP 50/2018)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Tata cara pelaksanaan APBN diatur dalam <strong>Peraturan Pemerintah Nomor 45 Tahun 2013 tentang Tata Cara Pelaksanaan APBN dan perubahannya PP Nomor 50 Tahun 2018</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena PP tersebut merupakan pedoman operasional utama perbendaharaan dalam manajemen komitmen, pengujian tagihan, serta pembayaran belanja negara. Oleh sebab itu, opsi A benar.</p><p>Jawaban tepat: <strong>B. PP Nomor 45 Tahun 2013 dan perubahannya PP Nomor 50 Tahun 2018</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Asosiasi pedagang pasar bukan pembentuk regulasi pelaksanaan APBN.</li><li><strong>C</strong>: Lembaga pemeringkat asing memberikan analisis risiko kredit obligasi.</li><li><strong>D</strong>: Dewan perbankan syariah mengawasi kepatuhan syariah produk bank.</li><li><strong>E</strong>: Lelang seni barang antik merupakan kegiatan niaga perdata khusus.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 31) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Peraturan Pemerintah Nomor 45 Tahun 2013 • tentang Tata Cara Pelaksanaan Anggaran Pendapatan dan Belanja Negara dan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Pelaporan dan pencatatan keuangan negara yang dilakukan kementerian/lembaga dan BUN wajib berpedoman pada...", "options": ["Sistem pembukuan kasir sederhana paguyuban warga daerah", "Prinsip akuntansi komersial usaha perbankan swasta murni", "Standar Akuntansi Pemerintah (SAP) untuk menghasilkan LKPP", "Tata cara pembagian laba perseroan komanditer perdata", "Pedoman pembukuan keuangan organisasi lembaga nirlaba asing"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penerapan Standar Akuntansi Pemerintah (SAP)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pelaporan dan pencatatan dilakukan <strong>sesuai Standar Akuntansi Pemerintah (SAP) sehingga menghasilkan Laporan Keuangan Pemerintah Pusat (LKPP)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena SAP memberikan keseragaman prinsip, basis akrual, dan keterbandingan laporan keuangan seluruh entitas sektor publik pemerintah. Oleh karena itu, opsi A benar.</p><p>Jawaban tepat: <strong>C. Standar Akuntansi Pemerintah (SAP) untuk menghasilkan LKPP</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pembukuan kasir paguyuban bukan standar akuntansi kenegaraan.</li><li><strong>B</strong>: Akuntansi bank swasta berpedoman pada standar akuntansi keuangan (SAK).</li><li><strong>D</strong>: Pembagian laba perseroan adalah urusan dividen usaha komersial.</li><li><strong>E</strong>: Pedoman nirlaba asing tidak berlaku bagi pertanggungjawaban APBN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 31) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>melakukan pelaporan dan pencatatan sesuai dengan Standar Akuntansi • Pemerintah (SAP) sehingga menghasilkan Laporan Keuangan Pemerintah Pusat</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Komponen pokok Laporan Keuangan Pemerintah Pusat (LKPP) sebagaimana ditegaskan dalam aturan perundangan terdiri atas...", "options": ["Daftar Pemegang Polis Asuransi, Sertifikat Tanah, dan Cek Giro", "Buku Kas Kasir, Daftar Piutang Pajak, dan Rekening Tabungan", "Kuitansi Belanja Barang, Faktur Pajak, dan Slip Gaji Pegawai", "Laporan Realisasi Anggaran, Neraca, Laporan Arus Kas, dan CaLK", "Laporan Penjualan Efek Saham, Rekening Koran, dan Nota Kliring"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Empat Komponen Pokok LKPP</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Laporan Keuangan Pemerintah Pusat (LKPP) <strong>terdiri atas Laporan Realisasi Anggaran (LRA), Neraca, Laporan Arus Kas (LAK), dan Catatan Atas Laporan Keuangan (CALK)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena keempat laporan ini menyajikan potret komprehensif realisasi kinerja anggaran, posisi aset-kewajiban, arus likuiditas, dan rincian catatan pengungkapan. Maka dari itu, opsi A benar.</p><p>Jawaban tepat: <strong>D. Laporan Realisasi Anggaran, Neraca, Laporan Arus Kas, dan CaLK</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Polis asuransi dan cek giro adalah instrumen keuangan perdata.</li><li><strong>B</strong>: Buku kas kasir merupakan buku pembantu bendahara operasional.</li><li><strong>C</strong>: Kuitansi dan faktur adalah bukti fisik transaksi pembukuan belanja.</li><li><strong>E</strong>: Penjualan efek saham adalah laporan transaksi pasar modal komersial.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 31) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>(LKPP) yang terdiri atas Laporan Realisasi Anggaran (LRA), Neraca, Laporan • Arus Kas (LAK), dan Catatan Atas Laporan Keuangan (CALK).</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Pejabat negara yang bertanggung jawab mengonsolidasikan dan menyusun Laporan Keuangan Pemerintah Pusat (LKPP) adalah...", "options": ["Ketua Komisi Perbendaharaan Dewan Perwakilan Rakyat", "Ketua Badan Pemeriksa Keuangan Republik Indonesia", "Gubernur Bank Indonesia selaku pimpinan bank sentral", "Menteri Koordinator Bidang Kemaritiman dan Investasi", "Menteri Keuangan selaku pengelola fiskal negara"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penyusunan LKPP oleh Menteri Keuangan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Semua laporan keuangan tersebut disusun oleh Menteri Keuangan selaku pengelola fiskal</strong> sebagai wujud laporan keuangan pemerintah pusat.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena Menteri Keuangan menggabungkan laporan keuangan kementerian/lembaga dan BUN menjadi satu kesatuan LKPP untuk diserahkan kepada Presiden. Oleh sebab itu, opsi A benar.</p><p>Jawaban tepat: <strong>E. Menteri Keuangan selaku pengelola fiskal negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Komisi DPR menelaah dan mengawasi laporan pertanggungjawaban APBN.</li><li><strong>B</strong>: Ketua BPK memeriksa laporan keuangan, bukan menyusun LKPP.</li><li><strong>C</strong>: Gubernur BI menyusun laporan tahunan bank sentral moneter.</li><li><strong>D</strong>: Menko Marves mengoordinasikan bidang kemaritiman dan investasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 31) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Semua laporan keuangan tersebut disusun oleh Menteri Keuangan selaku • pengelola</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Setelah selesai disusun oleh Menteri Keuangan, LKPP disampaikan kepada Presiden guna memenuhi kewajiban...", "options": ["Pertanggungjawaban pelaksanaan APBN kepada lembaga perwakilan", "Pembagian bonus kinerja bagi seluruh pimpinan perusahaan daerah", "Penarikan modal saham dari bursa perdagangan internasional", "Penghapusan seluruh sistem pelaporan audit tahun mendatang", "Pemberian dana pinjaman bebas bunga bagi korporasi swasta"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penyerahan LKPP kepada Presiden</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>LKPP disampaikan kepada presiden <strong>dalam memenuhi pertanggungjawaban pelaksanaan APBN</strong> sebelum diserahkan kepada BPK untuk diaudit.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena Presiden adalah pemegang kekuasaan umum pemerintahan yang wajib mempertanggungjawabkan pelaksanaan undang-undang APBN kepada DPR. Oleh karena itu, opsi A benar.</p><p>Jawaban tepat: <strong>A. Pertanggungjawaban pelaksanaan APBN kepada lembaga perwakilan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Bonus kinerja direksi BUMD diatur melalui rapat umum pemegang saham.</li><li><strong>C</strong>: Penarikan modal saham bukan tujuan penyerahan laporan pertanggungjawaban.</li><li><strong>D</strong>: Pelaporan audit wajib dijalankan setiap tahun secara berkelanjutan.</li><li><strong>E</strong>: Keuangan negara tidak meminjamkan dana bebas bunga ke korporasi swasta.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 31) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>disampaikan • kepada • presiden • dalam • memenuhi • pertanggungjawaban • pelaksanaan APBN.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Selain sebagai sarana pertanggungjawaban, hasil audit laporan keuangan dan pencatatan APBN berfungsi strategis sebagai...", "options": ["Pedoman lelang aset properti perkantoran swasta di bursa", "Bahan evaluasi dan penyusunan rancangan APBN tahun berikutnya", "Dasar pembagian dividen bagi penanam modal asing komersial", "Ketetapan batasan harga sewa perumahan pegawai aparatur", "Rujukan penetapan tarif premi perlindungan asuransi jiwa"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Fungsi Hasil Audit bagi APBN Berikutnya</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>BPK melakukan audit atas laporan keuangan, dan <strong>hasil dari laporan serta pencatatan APBN ini merupakan bahan untuk menyusun APBN tahun berikutnya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena siklus APBN bersifat berkesinambungan, di mana temuan dan realisasi anggaran tahun lalu menjadi basis perbaikan perencanaan anggaran masa depan. Maka dari itu, opsi A benar.</p><p>Jawaban tepat: <strong>B. Bahan evaluasi dan penyusunan rancangan APBN tahun berikutnya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Lelang aset properti swasta bukan produk dari audit laporan APBN.</li><li><strong>C</strong>: Dividen penanam modal asing dibayarkan dari laba korporasi swasta.</li><li><strong>D</strong>: Sewa perumahan pegawai diatur dalam standar biaya masukan dinas.</li><li><strong>E</strong>: Premi asuransi jiwa ditetapkan oleh aktuaria industri asuransi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 31) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>BPK melakukan audit atas laporan keuangan pemerintah. Hasil dari laporan dan • pencatatan APBN ini merupakan bahan untuk menyusun APBN tahun</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Pemeriksaan atas Laporan Keuangan Pemerintah Pusat (LKPP) secara konstitusional dilakukan oleh...", "options": ["Lembaga Akreditasi Konsultan Perencana Keuangan Privat", "Badan Pengawasan Keuangan dan Pembangunan Internasional", "Badan Pemeriksa Keuangan (BPK) Republik Indonesia", "Komite Pengawas Kepatuhan Perbankan Devisa Nasional", "Dewan Arbitrase Perselisihan Dagang Sektor Maritim"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Audit LKPP oleh BPK RI</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Atas LKPP, Badan Pemeriksa Keuangan (BPK) melakukan pemeriksaan</strong> independen untuk memberikan opini profesional atas kewajaran laporan keuangan negara.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena BPK RI adalah satu-satunya lembaga audit eksternal negara yang memegang mandat UUD 1945 memeriksa laporan keuangan pemerintah. Oleh sebab itu, opsi A benar.</p><p>Jawaban tepat: <strong>C. Badan Pemeriksa Keuangan (BPK) Republik Indonesia</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Konsultan privat tidak memiliki wewenang konstitusional mengaudit LKPP.</li><li><strong>B</strong>: BPKP merupakan aparat pengawasan intern pemerintah (APIP) eksekutif.</li><li><strong>D</strong>: Komite kepatuhan bank mengawasi tata kelola internal perbankan.</li><li><strong>E</strong>: Dewan arbitrase maritim menyelesaikan sengketa pengapalan niaga.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 31) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Atas LKPP, Badan Pemeriksa Keuangan (BPK) melakukan pemeriksaan dan • LKPP yang telah diaudit oleh BPK tersebut disampaikan oleh presiden kepada</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Setelah LKPP diaudit oleh Badan Pemeriksa Keuangan, dokumen tersebut disampaikan oleh Presiden kepada...", "options": ["Forum perhimpunan pialang efek pasar modal internasional", "Direksi asosiasi pengusaha industri manufaktur dalam negeri", "Pimpinan bank komersial swasta pemegang rekening giro dinas", "Dewan Perwakilan Rakyat dalam bentuk RUU Pertanggungjawaban", "Konsultan perencana tata ruang perkotaan wilayah daerah"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penyampaian LKPP Hasil Audit ke Parlemen</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>LKPP yang telah diaudit oleh BPK disampaikan oleh presiden kepada <strong>DPR dalam bentuk rancangan undang-undang pertanggungjawaban pelaksanaan APBN untuk dibahas dan disetujui</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena penyampaian RUU Pertanggungjawaban merupakan puncak akuntabilitas formal eksekutif kepada rakyat melalui perwakilannya di parlemen. Oleh karena itu, opsi A benar.</p><p>Jawaban tepat: <strong>D. Dewan Perwakilan Rakyat dalam bentuk RUU Pertanggungjawaban</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pialang pasar modal menganalisis perdagangan instrumen saham bursa.</li><li><strong>B</strong>: Asosiasi industri manufaktur bukan lembaga perwakilan negara pemutus APBN.</li><li><strong>C</strong>: Bank komersial swasta adalah mitra operasional perbankan semata.</li><li><strong>E</strong>: Konsultan tata ruang mengurus rancangan arsitektur fisik wilayah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 31) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>LKPP yang telah diaudit oleh BPK tersebut disampaikan oleh presiden kepada • DPR</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Selain pemeriksaan eksternal oleh BPK, pengawasan intern dalam pelaksanaan APBN di lingkungan kementerian/lembaga dilakukan oleh...", "options": ["Dewan komisaris independen dari badan usaha milik perbankan swasta", "Konsultan akuntan publik komersial yang disewa oleh rekanan pemborong", "Panitia pengadaan barang dari perwakilan perkumpulan pedagang swasta", "Pimpinan lembaga swadaya masyarakat bidang investigasi perbankan", "Atasan langsung pengelola kas dan Aparat Pengawas Intern Pemerintah"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengawasan Internal Pelaksanaan APBN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pengawasan pelaksanaan APBN dilakukan oleh <strong>atasan langsung pengelola keuangan dan Aparat Pengawas Internal Pemerintah (APIP) masing-masing K/L (Inspektorat Jenderal)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena pengawasan melekat atasan langsung dan audit internal APIP mengawal kepatuhan dan mendeteksi penyimpangan sejak dini dalam operasional harian. Maka dari itu, opsi A benar.</p><p>Jawaban tepat: <strong>E. Atasan langsung pengelola kas dan Aparat Pengawas Intern Pemerintah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Komisaris bank swasta mengawasi korporasi bisnis perbankan komersial.</li><li><strong>B</strong>: Akuntan publik yang disewa rekanan bukan auditor resmi pemerintah.</li><li><strong>C</strong>: Perkumpulan pedagang adalah kelompok perhimpunan pelaku usaha.</li><li><strong>D</strong>: LSM melakukan kontrol sosial publik, bukan fungsi APIP kedinasan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 31) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pengawasan tersebut dilakukan oleh atasan langsung pengelola keuangan dan • Aparat Pengawas Internal Pemerintah (APIP) masing-masing K/L.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Pada periode tahun anggaran 1969/1970 hingga 1999/2000, struktur dokumen APBN Indonesia disusun dalam bentuk format...", "options": ["Rekening scontro bertimbal (T-account)", "Format digital terpadu berbasis komputasi awan", "Rekening stafel tunggal berbasis aliran kas murni", "Buku kas harian model tunggal tanpa pencatatan debet", "Daftar neraca komersial perseroan terbatas terbuka"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Format Rekening Scontro (T-Account)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sejak tahun anggaran 1969/70 sampai dengan 1999/2000, <strong>APBN disusun dalam bentuk rekening scontro (T-account)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena format T-account menyajikan penerimaan di sisi debet (kiri) dan pengeluaran di sisi kredit (kanan) secara berdampingan. Oleh sebab itu, opsi A benar.</p><p>Jawaban tepat: <strong>A. Rekening scontro bertimbal (T-account)</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Sistem komputasi awan merupakan teknologi informasi modern mutakhir.</li><li><strong>C</strong>: Format stafel (I-account) baru diterapkan mulai tahun anggaran 2000.</li><li><strong>D</strong>: Buku kas tanpa debet bertentangan dengan prinsip pembukuan akuntansi.</li><li><strong>E</strong>: Neraca perseroan terbuka adalah laporan keuangan korporasi emiten bursa.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 32) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Sejak tahun anggaran 1969/70 sampai dengan 1999/2000 APBN disusun dalam • bentuk rekening scontro (T-account).</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Penyajian pos-pos keuangan dalam format T-account (scontro) diatur dengan menempatkan...", "options": ["Pengeluaran di sebelah debet dan penerimaan di sebelah kredit", "Penerimaan di sebelah debet dan pengeluaran di sebelah kredit", "Pinjaman luar negeri di luar pembukuan anggaran pendapatan", "Seluruh belanja kementerian pada kolom catatan kaki dokumen", "Saldo kas negara di halaman lampiran tersendiri tanpa angka"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Struktur Debet dan Kredit Format T-Account</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam format T-account, <strong>di sebelah debet dicantumkan semua penerimaan dan di sebelah kredit dicantumkan semua pengeluaran</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena tradisi format scontro lama mensejajarkan dua kolom berhadapan sehingga angka total kedua belah pihak selalu seimbang. Oleh karena itu, opsi A benar.</p><p>Jawaban tepat: <strong>B. Penerimaan di sebelah debet dan pengeluaran di sebelah kredit</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penerimaan dicatat di debet, bukan kredit pada format scontro APBN lama.</li><li><strong>C</strong>: Pinjaman luar negeri saat itu dimasukkan sebagai penerimaan pembangunan.</li><li><strong>D</strong>: Belanja kementerian dicatat di kolom kredit pengeluaran, bukan catatan kaki.</li><li><strong>E</strong>: Saldo kas memiliki akun resmi dalam pembukuan perbendaharaan negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 32) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Di sebelah debet, dicantumkan semua • penerimaan dan di sebelah kredit dicantumkan semua pengeluaran.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Perubahan mendasar format penyusunan APBN Indonesia dari bentuk T-account ke bentuk stafel (I-account) mulai diberlakukan pada...", "options": ["Tahun anggaran 1985", "Tahun anggaran 1969", "Tahun anggaran 2000", "Tahun anggaran 2015", "Tahun anggaran 2024"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Transisi ke Format Stafel (I-Account) Tahun 2000</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Mulai tahun anggaran 2000 struktur dan format APBN disusun dalam bentuk stafel (I-Account)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena tahun 2000 menjadi tonggak reformasi format anggaran vertikal (I-Account) guna membedakan secara tegas antara pendapatan, belanja, dan pembiayaan defisit. Maka dari itu, opsi A benar.</p><p>Jawaban tepat: <strong>C. Tahun anggaran 2000</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Tahun 1985 adalah era penataan pajak modern pertama (tax reform).</li><li><strong>B</strong>: Tahun 1969 adalah awal mula penggunaan format T-account Orde Baru.</li><li><strong>D</strong>: Tahun 2015 adalah era pengalihan subsidi bahan bakar minyak ke infrastruktur.</li><li><strong>E</strong>: Tahun 2024 adalah tahun pelaksanaan anggaran modern terkini.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 32) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Mulai tahun • anggaran 2000 struktur dan format APBN disusun dalam bentuk stafel (I-Account).</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Penerapan format stafel (I-account) disesuaikan dengan standar pelaporan statistik fiskal internasional yang dikenal sebagai...", "options": ["Standardized Private Corporate Manual", "International Commercial Trade System", "Global Banking Insurance Protocol", "Government Finance Statistics (GFS)", "Worldwide Securities Settlement Code"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Standar Government Finance Statistics (GFS)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Struktur APBN stafel <strong>disesuaikan dengan standar yang berlaku secara internasional sebagaimana digunakan dalam statistik keuangan pemerintah (Government Finance Statistics - GFS)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena GFS memandu klasifikasi akun ekonomi pemerintah secara universal sehingga statistik operasi keuangan antarnegara dapat dibandingkan secara akurat. Oleh sebab itu, opsi A benar.</p><p>Jawaban tepat: <strong>D. Government Finance Statistics (GFS)</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Private Corporate Manual adalah panduan operasional korporasi privat.</li><li><strong>B</strong>: Commercial Trade System adalah sistem statistik perdagangan barang swasta.</li><li><strong>C</strong>: Banking Insurance Protocol merupakan protokol industri asuransi bank.</li><li><strong>E</strong>: Securities Settlement Code adalah kode penyelesaian transaksi efek bursa.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 32) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>internasional sebagaimana digunakan dalam statistik keuangan pemerintah • (Government Finance Statistics).</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Dua tujuan utama dari perubahan struktur format APBN menjadi model I-account adalah...", "options": ["Memberikan hak monopoli perbankan komersial atas kas negara", "Menutup angka defisit riil serta membatasi akses masyarakat", "Menghapuskan fungsi audit eksternal dari badan pemeriksa resmi", "Mewajibkan penggunaan mata uang asing dalam transaksi dinas kantor", "Meningkatkan transparansi dan mempermudah analisis komparasi operasi fiskal"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuan Pertama dan Kedua Format I-Account</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Tujuan format I-Account: <strong>1. Meningkatkan transparansi APBN, dan 2. Mempermudah analisis komparasi operasi fiskal Pemerintah dengan negara lain</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena format vertikal memperlihatkan defisit murni secara jujur dan memudahkan perbandingan performa fiskal Indonesia dengan praktik terbaik global. Oleh karena itu, opsi A benar.</p><p>Jawaban tepat: <strong>E. Meningkatkan transparansi dan mempermudah analisis komparasi operasi fiskal</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pengelolaan kas umum negara dimonopoli oleh Bendahara Umum Negara.</li><li><strong>B</strong>: Format I-Account justru membuka angka defisit riil secara transparan.</li><li><strong>C</strong>: Audit eksternal BPK tetap berjalan secara independen dan profesional.</li><li><strong>D</strong>: Transaksi dinas di dalam negeri wajib menggunakan mata uang rupiah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 32) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Untuk meningkatkan transparansi dalam penyusunan APBN. • Mempermudah melakukan analisis komparasi mengenai perkembangan operasi</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Tujuan ketiga dari penerapan format I-account dalam pengelolaan APBN adalah mempermudah pengendalian guna...", "options": ["Memperkecil diskrepansi selisih data dengan pembiayaan Bank Indonesia", "Menaikkan suku bunga pinjaman komersial bagi sektor perbankan", "Menghapus pencatatan saldo rekening kas negara di perbankan", "Mengurangi alokasi belanja infrastruktur fisik di kawasan daerah", "Mewajibkan bank swasta menanggung seluruh beban utang dinas"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuan Ketiga: Menekan Diskrepansi Pembiayaan BI</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Tujuan ketiga format I-Account adalah <strong>mempermudah pemantauan dan pengendalian pengelolaan APBN sehingga dapat diambil langkah memperkecil diskrepansi dengan data pembiayaan Bank Indonesia</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena keselarasan antara data penarikan pembiayaan pemerintah dan catatan moneter bank sentral sangat vital untuk akurasi statistik makroekonomi. Maka dari itu, opsi A benar.</p><p>Jawaban tepat: <strong>A. Memperkecil diskrepansi selisih data dengan pembiayaan Bank Indonesia</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Suku bunga komersial merupakan domain transmisi kebijakan moneter.</li><li><strong>C</strong>: Pencatatan kas negara wajib dilakukan secara akurat tanpa penghapusan.</li><li><strong>D</strong>: Belanja infrastruktur dialokasikan berdasarkan prioritas pembangunan.</li><li><strong>E</strong>: Beban utang dinas negara dibiayai dari anggaran pendapatan belanja negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 32) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>memperkecil • diskrepansi dengan data pembiayaan Bank Indonesia.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Tujuan keempat dari format I-account adalah mempersiapkan tata kelola APBN dalam menghadapi pelaksanaan...", "options": ["Sentralisasi penarikan seluruh pajak daerah menjadi wewenang pusat", "Desentralisasi fiskal sesuai perundangan perimbangan keuangan pusat dan daerah", "Penghapusan otonomi pengelolaan belanja dinas bagi pemerintah daerah", "Pemberian dana pinjaman bebas bunga bagi korporasi multinasional", "Penutupan seluruh kantor perwakilan dinas perbendaharaan di daerah"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuan Keempat: Menghadapi Desentralisasi Fiskal</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Tujuan keempat format I-Account adalah <strong>menghadapi pelaksanaan desentralisasi fiskal sesuai dengan UU No. 33 Tahun 2004 tentang Perimbangan Keuangan antara Pemerintah Pusat dan Daerah</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena format I-Account menyediakan pos khusus transfer ke daerah secara transparan yang memisahkan belanja kementerian pusat dan alokasi transfer dana otonomi daerah. Oleh sebab itu, opsi A benar.</p><p>Jawaban tepat: <strong>B. Desentralisasi fiskal sesuai perundangan perimbangan keuangan pusat dan daerah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pajak daerah diatur terpisah dan dipungut mandiri oleh pemerintah daerah.</li><li><strong>C</strong>: Otonomi daerah memberikan keleluasaan pengelolaan APBD kepada pemda.</li><li><strong>D</strong>: Keuangan negara tidak memberikan pinjaman cuma-cuma ke swasta multinasional.</li><li><strong>E</strong>: Kantor vertikal perbendaharaan (KPPN) justru diperkuat layanannya di daerah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 32) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Menghadapi pelaksanaan desentralisasi fiskal sesuai dengan dengan UU No. 33 • tahun 2004, tentang Perimbangan Keuangan antara Pemerintah Pusat dan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Undang-Undang Nomor 17 Tahun 2003 mengamanatkan format baru anggaran terpadu (unified budget) yang pada intinya...", "options": ["Menggabungkan rekening perbankan komersial ke dalam kas perbendaharaan", "Menyatukan kas negara dengan kas pribadi para menteri kabinet", "Menghapus pemisahan antara anggaran rutin dan anggaran belanja pembangunan", "Membebankan seluruh pengeluaran dinas ke pos dana cadangan darurat", "Menghapus kewajiban pengawasan oleh aparat pemeriksa keuangan negara"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Konsep Anggaran Terpadu (Unified Budget) UU 17/2003</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>UU Keuangan Negara mengamanatkan <strong>format anggaran terpadu (unified budget), yakni tidak ada pemisahan antara anggaran belanja rutin dan anggaran belanja pembangunan, tetapi digabungkan menjadi satu</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena penyatuan anggaran rutin dan pembangunan ke dalam satu dokumen terpadu mengakhiri tumpang tindih mata anggaran dan mempermudah pengawasan kinerja keluaran. Oleh karena itu, opsi A benar.</p><p>Jawaban tepat: <strong>C. Menghapus pemisahan antara anggaran rutin dan anggaran belanja pembangunan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kas perbankan swasta merupakan aset korporasi perbankan yang terpisah.</li><li><strong>B</strong>: Penyatuan kas negara dengan kas pribadi melanggar hukum perbendaharaan.</li><li><strong>D</strong>: Belanja dinas dialokasikan sesuai mata anggaran pengeluaran yang sah.</li><li><strong>E</strong>: Pengawasan keuangan negara tetap dijalankan oleh aparat pengawas resmi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 32) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>UU Keuangan Negara mengamanatkan format baru yang • disebut format anggaran terpadu (unified budget), yakni tidak ada pemisahan antara</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Sebelum reformasi keuangan negara tahun 2003, anggaran belanja pemerintah dikelompokkan ke dalam dua pos terpisah, yaitu...", "options": ["Anggaran belanja darurat dan anggaran belanja spekulatif", "Anggaran belanja swasta dan anggaran belanja perbankan", "Anggaran belanja tunai dan anggaran belanja piutang komersial", "Anggaran belanja rutin dan anggaran belanja pembangunan", "Anggaran belanja internal dan anggaran belanja perdagangan bebas"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penghapusan Dikotomi Rutin dan Pembangunan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sebelum reformasi unified budget, anggaran belanja pemerintah terpecah antara <strong>anggaran belanja rutin (DIK) dan anggaran belanja pembangunan (DIP)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena dikotomi klasik rutin vs pembangunan tersebut sering menimbulkan duplikasi pembiayaan kegiatan dinas sebelum disatukan dalam sistem DIPA terpadu. Maka dari itu, opsi A benar.</p><p>Jawaban tepat: <strong>D. Anggaran belanja rutin dan anggaran belanja pembangunan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Belanja spekulatif tidak dikenal dalam tata kelola perbendaharaan.</li><li><strong>B</strong>: Belanja swasta dan bank bukan klasifikasi dalam dokumen APBN resmi.</li><li><strong>C</strong>: Belanja tunai dan piutang bukan pembagian nomenklatur anggaran negara.</li><li><strong>E</strong>: Belanja perdagangan bebas merupakan transaksi sektor komersial.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 32) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>anggaran belanja rutin dan anggaran belanja pembangunan, tetapi digabungkan • menjadi satu.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Seiring disahkannya UU Nomor 17 Tahun 2003 pada Maret 2003, format I-Account RAPBN disempurnakan strukturnya dengan menerapkan prinsip...", "options": ["Penghapusan seluruh pos transfer dana perimbangan ke daerah", "Pengembalian format T-account scontro model masa Orde Baru", "Pencatatan pendapatan negara dari pos sektor penerimaan migas saja", "Penyerahan tata kelola penerimaan cukai kepada badan usaha swasta", "Penganggaran terpadu tanpa pemisahan pos rutin dan pembangunan"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penyempurnaan Format I-Account Pasca UU 17/2003</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Mulai Maret 2003 seiring diundangkannya UU 17/2003, <strong>format RAPBN mengadopsi format anggaran terpadu (unified budget) di dalam struktur I-Account</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena integrasi belanja operasional dan belanja modal ke dalam kerangka belanja terpadu melengkapi modernisasi format I-Account di Indonesia. Oleh sebab itu, opsi A benar.</p><p>Jawaban tepat: <strong>E. Penganggaran terpadu tanpa pemisahan pos rutin dan pembangunan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Transfer ke daerah justru menjadi pilar pokok desentralisasi fiskal.</li><li><strong>B</strong>: Format T-account sudah ditinggalkan secara permanen sejak tahun 2000.</li><li><strong>C</strong>: Pendapatan negara mencakup pajak, PNBP, dan hibah secara komprehensif.</li><li><strong>D</strong>: Tata kelola cukai merupakan kewenangan negara di bawah DJBC.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 32) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Mulai Maret 2003 seiring dengan diundangkannya Undang-Undang Nomor 17 Tahun • 2003, format RAPBN meski menggunakan I-Account mengalami perubahan format</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C4 - Analisis", "question": "Struktur dan format I-Account APBN dirancang untuk memenuhi empat tujuan strategis berikut, KECUALI...", "options": ["Memudahkan pergeseran kas negara ke rekening komersial pribadi", "Meningkatkan transparansi dalam penyusunan dokumen APBN", "Mempermudah analisis komparasi fiskal standar internasional", "Memperkecil diskrepansi data pembiayaan dengan Bank Indonesia", "Mendukung kesiapan pelaksanaan desentralisasi fiskal ke daerah"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuan Empat Pilar Format I-Account (Kecuali)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Empat tujuan format I-Account adalah <strong>transparansi, komparasi internasional GFS, pengendalian diskrepansi pembiayaan BI, dan kesiapan desentralisasi fiskal</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat sebagai pengecualian karena memindahkan kas negara ke rekening pribadi merupakan perbuatan melanggar hukum dan bertentangan dengan tujuan transparansi APBN. Oleh karena itu, opsi A benar.</p><p>Jawaban tepat: <strong>A. Memudahkan pergeseran kas negara ke rekening komersial pribadi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Transparansi merupakan tujuan resmi angka 1 format I-Account.</li><li><strong>C</strong>: Komparasi GFS merupakan tujuan resmi angka 2 format I-Account.</li><li><strong>D</strong>: Pengendalian diskrepansi pembiayaan BI adalah tujuan resmi angka 3.</li><li><strong>E</strong>: Kesiapan desentralisasi fiskal adalah tujuan resmi angka 4.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 32) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Struktur dan format APBN seperti ini dapat • digunakan untuk beberapa tujuan yaitu:</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Kemudahan dalam melakukan perbandingan operasi fiskal Indonesia dengan negara-negara lain di dunia terwujud berkat adopsi...", "options": ["Pedoman penetapan harga barang ekspor dari asosiasi swasta", "Klasifikasi statistik keuangan pemerintah standar internasional (GFS)", "Tata cara pencairan kredit mikro perbankan komersial daerah", "Standar penilaian agunan pinjaman perorangan milik nasabah", "Sistem lelang barang sitaan bea cukai pada bandar udara"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Komparabilitas Statistik Keuangan Antarnegara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Format I-Account mempermudah <strong>analisis komparasi mengenai perkembangan operasi fiskal Pemerintah dengan berbagai negara lain sesuai standar internasional GFS</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena keselarasan definisi pos pendapatan dan belanja dengan manual GFS memungkinkan lembaga pemeringkat dan investor membandingkan postur fiskal Indonesia secara adil. Maka dari itu, opsi A benar.</p><p>Jawaban tepat: <strong>B. Klasifikasi statistik keuangan pemerintah standar internasional (GFS)</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Harga barang ekspor asosiasi swasta adalah urusan perniagaan dagang.</li><li><strong>C</strong>: Pencairan kredit mikro bank daerah adalah operasional kredit perbankan.</li><li><strong>D</strong>: Penilaian agunan pinjaman adalah prosedur analis kredit bank.</li><li><strong>E</strong>: Lelang sitaan bea cukai adalah penegakan hukum kepabeanan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 32) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>transparansi • Mempermudah • analisis • komparasi</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Kesiapan format I-account dalam mendukung desentralisasi fiskal berkaitan erat dengan implementasi regulasi...", "options": ["Undang-Undang Nomor 40 Tahun 2007 tentang Perseroan Terbatas", "Undang-Undang Nomor 8 Tahun 1999 tentang Perlindungan Konsumen", "Undang-Undang Nomor 33 Tahun 2004 tentang Perimbangan Keuangan", "Undang-Undang Nomor 5 Tahun 1999 tentang Larangan Praktik Monopoli", "Undang-Undang Nomor 10 Tahun 1998 tentang Perbankan Komersial"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Keterkaitan I-Account dan UU 33/2004</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Format I-Account mendukung desentralisasi fiskal <strong>sesuai UU No. 33 Tahun 2004 tentang Perimbangan Keuangan antara Pemerintah Pusat dan Daerah</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena undang-undang perimbangan keuangan mengatur pembagian dana bagi hasil, DAU, dan DAK yang disajikan secara terinci dalam format I-Account. Oleh sebab itu, opsi A benar.</p><p>Jawaban tepat: <strong>C. Undang-Undang Nomor 33 Tahun 2004 tentang Perimbangan Keuangan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: UU Perseroan Terbatas mengatur badan usaha perseroan privat.</li><li><strong>B</strong>: UU Perlindungan Konsumen mengatur hak-hak konsumen perniagaan.</li><li><strong>D</strong>: UU Larangan Praktik Monopoli mengatur persaingan usaha niaga.</li><li><strong>E</strong>: UU Perbankan mengatur kelembagaan dan operasional bank komersial.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 32) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pelaksanaan desentralisasi fiskal sesuai dengan dengan UU No. 33 • tahun 2004,</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C4 - Analisis", "question": "Keunggulan utama format I-account dibandingkan format T-account scontro dalam menyajikan posisi defisit anggaran terletak pada...", "options": ["Penggabungan seluruh saldo tabungan perbankan swasta ke dalam kas", "Pencatatan pinjaman utang luar negeri langsung sebagai pendapatan", "Peniadaan pencatatan beban pembayaran bunga pinjaman pemerintah", "Penyajian selisih murni pendapatan dan belanja sebelum pos pembiayaan", "Penghapusan kewajiban penyusunan laporan neraca perbendaharaan"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Transparansi Posisi Defisit Riil pada I-Account</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Format I-Account menyajikan pendapatan dikurangi belanja untuk <strong>menghasilkan posisi surplus/defisit secara murni, yang kemudian ditutup melalui pos pembiayaan netral</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena format stafel memperlihatkan secara transparan berapa kekurangan kas riil pemerintah yang harus ditutup melalui utang, berbeda dari format lama yang menyamarkan utang sebagai pendapatan. Oleh karena itu, opsi A benar.</p><p>Jawaban tepat: <strong>D. Penyajian selisih murni pendapatan dan belanja sebelum pos pembiayaan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Tabungan swasta bukan bagian dari kas perbendaharaan negara.</li><li><strong>B</strong>: Mencatat utang sebagai pendapatan adalah kelemahan format lama scontro.</li><li><strong>C</strong>: Beban bunga utang tetap dicatat rinci dalam pos belanja operasional.</li><li><strong>E</strong>: Laporan neraca tetap wajib disusun sesuai standar akuntansi SAP.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 32) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>penyusunan APBN. • Mempermudah melakukan analisis komparasi mengenai perkembangan operasi</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C3 - Aplikasi", "question": "Upaya memperkecil diskrepansi data pembiayaan antara Kementerian Keuangan dan Bank Indonesia melalui format I-account dilakukan dengan menyinkronkan...", "options": ["Jumlah kepemilikan sertifikat deposito perorangan pegawai di daerah", "Daftar hadir seluruh staf kantor perbendaharaan dengan pegawai bank", "Jadwal jam buka kantor pelayanan dinas dengan kantor cabang perbankan", "Tarif sewa mobil dinas para pimpinan kementerian dengan direksi bank", "Catatan penarikan pembiayaan kas negara dengan data mutasi moneter BI"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sinkronisasi Fiskal dan Moneter via I-Account</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Format I-Account mempermudah analisis dan pengendalian pelaksanaan APBN sehingga <strong>dapat memperkecil diskrepansi dengan data pembiayaan Bank Indonesia</strong> melalui rekonsiliasi berkala.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena rekonsiliasi data aliran kas penarikan utang dan pergerakan saldo rekening kas negara di bank sentral menjamin integritas data fiskal-moneter makro. Maka dari itu, opsi A benar.</p><p>Jawaban tepat: <strong>E. Catatan penarikan pembiayaan kas negara dengan data mutasi moneter BI</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Sertifikat deposito perorangan pegawai adalah aset finansial privat.</li><li><strong>B</strong>: Daftar hadir staf kantor merupakan administrasi disiplin absensi SDM.</li><li><strong>C</strong>: Jam buka kantor dinas adalah tata tertib jam kerja pelayanan publik.</li><li><strong>D</strong>: Sewa mobil dinas adalah standar biaya masukan fasilitas kantor.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 32) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>memperkecil • diskrepansi • pembiayaan • Bank Indonesia.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Penyusunan APBN pada hakikatnya dimaksudkan sebagai bentuk penjabaran dari...", "options": ["Rencana kerja pemerintah kurun waktu satu tahun", "Pengawasan menyeluruh terhadap pelaksanaan anggaran", "Pengesahan penetapan tarif penerimaan perpajakan", "Penentuan alokasi dana transfer ke daerah otonom", "Pengendalian cadangan devisa dan peredaran moneter"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Maksud Penyusunan APBN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Penyusunan APBN dimaksudkan sebagai <strong>penjabaran rencana kerja pemerintah</strong> untuk kurun waktu <strong>satu tahun</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena secara prinsipil penyusunan anggaran negara berfungsi menerjemahkan sasaran program kerja tahunan pemerintah ke dalam angka-angka penerimaan dan pengeluaran. Hal ini memastikan setiap target pembangunan didukung oleh ketersediaan alokasi dana yang terencana.</p><p>Jawaban tepat: <strong>A. Rencana kerja pemerintah kurun waktu satu tahun</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Pengawasan pelaksanaan merupakan fungsi tahap pelaksanaan dan pertanggungjawaban anggaran.</li><li><strong>C</strong>: Penetapan tarif pajak adalah instrumen regulasi kebijakan fiskal pendapatan.</li><li><strong>D</strong>: Alokasi dana transfer daerah merupakan salah satu pos belanja dalam APBN.</li><li><strong>E</strong>: Pengendalian cadangan devisa merupakan wewenang kebijakan moneter bank sentral.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 35) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>penjabaran rencana kerja pemerintah untuk</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Penyusunan anggaran pendapatan dan belanja negara disesuaikan dengan dasar...", "options": ["Permintaan alokasi anggaran belanja dari setiap lembaga", "Kebutuhan penyelenggaraan negara dan kemampuan menghimpun pendapatan", "Proyeksi target pertumbuhan ekonomi jangka panjang nasional", "Kapasitas penyerapan anggaran belanja operasional kementerian", "Batas maksimum pinjaman luar negeri pemerintah pusat"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dasar Penyesuaian Penyusunan APBN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Penyusunan APBN disesuaikan dengan <strong>kebutuhan penyelenggaraan negara</strong> dan <strong>kemampuan dalam menghimpun pendapatan negara</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena perumusan postur APBN wajib menyeimbangkan antara tuntutan pembiayaan tugas operasional negara dengan kapasitas riil penerimaan kas negara. Keseimbangan ini menjaga stabilitas fiskal agar pemerintah tidak mengalami defisit berlebihan.</p><p>Jawaban tepat: <strong>B. Kebutuhan penyelenggaraan negara dan kemampuan menghimpun pendapatan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Usulan instansi harus disaring sesuai batas kemampuan pendanaan negara.</li><li><strong>C</strong>: Pertumbuhan jangka panjang adalah asumsi makro, bukan tolok ukur penyesuaian belanja.</li><li><strong>D</strong>: Daya serap kementerian merupakan indikator kinerja serapan, bukan dasar penyusunan.</li><li><strong>E</strong>: Batas pinjaman luar negeri adalah instrumen pengendalian defisit pembiayaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 35) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>penyelenggaraan negara dan kemampuan dalam menghimpun</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Dalam penyusunan APBN, belanja operasional pemerintah diupayakan agar...", "options": ["Mengutamakan alokasi belanja modal proyek fisik daerah", "Selalu seimbang dengan penerimaan pembiayaan utang pemerintah", "Tidak melampaui pendapatan dalam tahun anggaran bersangkutan", "Menghabiskan seluruh sisa lebih perhitungan anggaran tahunan", "Ditetapkan sama persis dengan pagu anggaran tahun sebelumnya"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Batasan Belanja Operasional APBN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam penyusunan APBN diupayakan agar <strong>belanja operasional tidak melampaui pendapatan</strong> dalam tahun anggaran yang bersangkutan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena prinsip kehati-hatian fiskal menuntut agar kebutuhan konsumtif rutin birokrasi sepenuhnya tertutupi oleh pendapatan tahun berjalan. Hal ini mencegah penggunaan pembiayaan utang untuk membiayai belanja operasional rutin pemerintah.</p><p>Jawaban tepat: <strong>C. Tidak melampaui pendapatan dalam tahun anggaran bersangkutan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Belanja modal merupakan jenis belanja terpisah dari belanja operasional aparatur.</li><li><strong>B</strong>: Pembiayaan utang diarahkan untuk belanja produktif, bukan menyamai belanja operasional.</li><li><strong>D</strong>: Menghabiskan sisa anggaran bertentangan dengan prinsip efisiensi belanja negara.</li><li><strong>E</strong>: Pagu belanja disesuaikan dinamis sesuai kebutuhan riil dan target kinerja tahunan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 35) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>belanja operasional tidak melampaui • pendapatan dalam tahun anggaran</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Sesuai ketentuan UU Nomor 17 Tahun 2003, defisit anggaran dibatasi maksimal...", "options": ["10 persen dari Produk Domestik Bruto", "5 persen dari Produk Domestik Bruto", "7 persen dari Produk Domestik Bruto", "3 persen dari Produk Domestik Bruto", "15 persen dari Produk Domestik Bruto"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Batas Maksimal Defisit Anggaran</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam hal anggaran defisit, <strong>defisit yang terjadi dibatasi maksimal 3 % dari Produk Domestik Bruto</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena batas angka tiga persen dari PDB adalah ambang batas disiplin fiskal yang ditetapkan undang-undang. Pembatasan ini bertujuan menjaga kredibilitas APBN dan mencegah risiko krisis utang publik.</p><p>Jawaban tepat: <strong>D. 3 persen dari Produk Domestik Bruto</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Sepuluh persen merupakan rasio yang membahayakan kesinambungan fiskal nasional.</li><li><strong>B</strong>: Batas lima persen melampaui ketentuan undang-undang disiplin keuangan negara.</li><li><strong>C</strong>: Tujuh persen merupakan angka yang terlampau tinggi untuk batas defisit normal.</li><li><strong>E</strong>: Lima belas persen bukan batas yang sah dalam ketentuan keuangan negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 35) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>maksimal 3 % dari Produk Domestik Bruto</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Batas maksimal jumlah pinjaman untuk membiayai defisit APBN ditetapkan sebesar...", "options": ["20 persen dari Produk Domestik Bruto", "50 persen dari Produk Domestik Bruto", "40 persen dari Produk Domestik Bruto", "30 persen dari Produk Domestik Bruto", "60 persen dari Produk Domestik Bruto"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Batas Maksimal Rasio Utang Pinjaman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Jumlah pinjaman untuk membiayai defisit anggaran <strong>maksimal adalah 60 % dari Produk Domestik Bruto</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena UU Keuangan Negara menggariskan batas aman stok utang pemerintah maksimal enam puluh persen terhadap PDB nasional. Batasan ini diadopsi dari standar internasional untuk menjaga kesehatan neraca moneter dan fiskal negara.</p><p>Jawaban tepat: <strong>E. 60 persen dari Produk Domestik Bruto</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Dua puluh persen bukan ambang batas maksimum pinjaman yang diatur UU.</li><li><strong>B</strong>: Lima puluh persen adalah target kehati-hatian internal namun bukan batas legal UU.</li><li><strong>C</strong>: Empat puluh persen merupakan rasio konservatif, bukan batas maksimum regulasi.</li><li><strong>D</strong>: Tiga puluh persen merupakan rasio rendah dan bukan batas undang-undang.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 35) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>maksimal adalah 60 % dari Produk Domestik Bruto</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Rencana penggunaan surplus anggaran diajukan kepada DPR dengan mempertimbangkan...", "options": ["Prinsip pertanggungjawaban antar generasi", "Prinsip efisiensi penyerapan belanja tahunan", "Prinsip keseimbangan fiskal daerah otonom", "Prinsip percepatan likuidasi piutang macet", "Prinsip optimalisasi penerimaan bukan pajak"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Prinsip Penggunaan Surplus Anggaran</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pemerintah mengajukan penggunaan surplus anggaran kepada DPR dengan <strong>mempertimbangkan prinsip pertanggungjawaban antar generasi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena dana surplus tidak boleh dihabiskan untuk konsumsi tahun berjalan saja, melainkan harus memberi manfaat bagi generasi masa depan. Konsep ini menjamin keadilan antargenerasi dalam menikmati hasil kekayaan negara.</p><p>Jawaban tepat: <strong>A. Prinsip pertanggungjawaban antar generasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Efisiensi belanja merupakan tolok ukur operasional pengadaan dan penyerapan kas.</li><li><strong>C</strong>: Keseimbangan fiskal daerah diatur dalam mekanisme transfer perimbangan keuangan.</li><li><strong>D</strong>: Likuidasi piutang merupakan urusan teknis penagihan piutang perbendaharaan.</li><li><strong>E</strong>: Optimalisasi PNBP adalah sasaran peningkatan penerimaan kas negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 35) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pertanggungjawaban antar generasi</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan prinsip pertanggungjawaban antar generasi, penggunaan surplus anggaran diutamakan untuk...", "options": ["Penambahan belanja operasional dan tunjangan kinerja", "Pengurangan utang, dana cadangan, dan jaminan sosial", "Peningkatan modal penyertaan badan usaha swasta", "Pembiayaan perjalanan dinas kementerian lembaga", "Pelunasan bantuan hibah luar negeri jangka pendek"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Prioritas Alokasi Surplus APBN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Penggunaan surplus anggaran diutamakan untuk <strong>pengurangan utang, pembentukan dana cadangan, dan peningkatan jaminan sosial</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena tiga pos alokasi ini secara langsung memperkuat ketahanan fiskal masa depan dan kesejahteraan sosial masyarakat. Pengurangan beban utang meringankan kewajiban generasi penerus bangsa.</p><p>Jawaban tepat: <strong>B. Pengurangan utang, dana cadangan, dan jaminan sosial</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Belanja operasional konsumtif tidak boleh ditambah dari surplus anggaran.</li><li><strong>C</strong>: Penyertaan modal negara ditujukan pada BUMN, bukan badan usaha swasta.</li><li><strong>D</strong>: Perjalanan dinas aparatur merupakan belanja rutin yang wajib dibatasi.</li><li><strong>E</strong>: Bantuan hibah luar negeri merupakan penerimaan, bukan kewajiban pelunasan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 35) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pengurangan utang, pembentukan dana cadangan, dan peningkatan jaminan sosial</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Mulai APBN tahun 2005, format penyusunan anggaran negara menerapkan sistem...", "options": ["Format anggaran kinerja berbasis kuota", "Format anggaran tradisional berimbang", "Format anggaran terpadu (unified budget)", "Format anggaran kas terpusat daerah", "Format kerangka pengeluaran berjenjang"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penerapan Format Unified Budget</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Mulai APBN tahun 2005, format penyusunan APBN menggunakan <strong>format anggaran terpadu (unified budget)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena pembaruan sistem anggaran tahun 2005 menyatukan pembiayaan operasional rutin dan pembangunan ke dalam satu wadah terpadu. Sistem ini menghapus dikotomi anggaran yang lama.</p><p>Jawaban tepat: <strong>C. Format anggaran terpadu (unified budget)</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Format kuota bukan istilah standar dalam sistem penganggaran modern.</li><li><strong>B</strong>: Anggaran tradisional berimbang adalah sistem lama era sebelum reformasi keuangan.</li><li><strong>D</strong>: Kas terpusat daerah merupakan urusan penatausahaan kas daerah.</li><li><strong>E</strong>: Kerangka pengeluaran berjenjang bukan nama format resmi anggaran negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 35) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>format anggaran terpadu (unified budget)</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Tujuan utama melebur anggaran rutin dan pembangunan ke dalam satu format adalah...", "options": ["Meningkatkan proporsi belanja modal luar negeri", "Mempercepat pencairan dana bantuan sosial darurat", "Menambah kewenangan kuasa pengguna anggaran daerah", "Mengurangi alokasi belanja yang tumpang tindih", "Menyederhanakan penarikan pinjaman jangka menengah"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuan Penggabungan Anggaran Rutin dan Pembangunan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Penggabungan belanja rutin dengan belanja pembangunan diharapkan akan <strong>mengurangi alokasi yang tumpang tindih</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena pada sistem dual budgeting sebelumnya banyak pos kegiatan rutin yang didanai melalui proyek pembangunan sehingga terjadi duplikasi pendanaan. Peleburan anggaran mengeliminasi pemborosan alokasi tersebut.</p><p>Jawaban tepat: <strong>D. Mengurangi alokasi belanja yang tumpang tindih</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Peningkatan belanja modal luar negeri bukan maksud unifikasi anggaran.</li><li><strong>B</strong>: Pencairan bansos memiliki regulasi mekanisme tersendiri.</li><li><strong>C</strong>: Peleburan anggaran tidak bertujuan memperluas kewenangan administratif KPA.</li><li><strong>E</strong>: Penarikan pinjaman merupakan urusan manajemen utang pada Ditjen PPR.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 35) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>melebur anggaran rutin dan • mengurangi alokasi yang tumpang tindih</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Komponen belanja rutin yang dilebur dalam anggaran terpadu mencakup pengeluaran...", "options": ["Cicilan pokok utang, sewa guna usaha, dan obligasi negara", "Belanja modal tanah, gedung, serta infrastruktur jalan", "Subsidi energi listrik, bahan bakar, dan pupuk subsidi", "Dana bagi hasil, dana alokasi umum, dan hibah daerah", "Gaji, pemeliharaan, perjalanan dinas dan belanja barang"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Komponen Belanja Rutin</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Belanja rutin yang digabung meliputi <strong>gaji, pemeliharaan, perjalanan dinas dan belanja barang</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena keempat kelompok biaya tersebut merupakan biaya operasional mendasar bagi berjalannya birokrasi kementerian. Penyatuan komponen ini dengan belanja modal menghasilkan rencana kerja yang utuh.</p><p>Jawaban tepat: <strong>E. Gaji, pemeliharaan, perjalanan dinas dan belanja barang</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Cicilan pokok dan obligasi masuk dalam kelompok pos pembiayaan anggaran.</li><li><strong>B</strong>: Tanah, gedung, dan infrastruktur digolongkan ke dalam belanja modal.</li><li><strong>C</strong>: Subsidi energi dan pupuk merupakan pos pengeluaran subsidi negara.</li><li><strong>D</strong>: Dana bagi hasil dan alokasi umum termasuk pos transfer ke daerah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 35) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>gaji, pemeliharaan, perjalanan dinas dan belanja barang</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Penerapan anggaran terpadu secara tegas dimaksudkan untuk menghilangkan duplikasi...", "options": ["Antara kegiatan operasional dengan proyek non-fisik", "Antara belanja pegawai pusat dengan pegawai daerah", "Antara penerimaan pajak dengan bea cukai impor", "Antara belanja pemeliharaan dengan pengadaan tanah", "Antara subsidi bunga utang dengan penerbitan obligasi"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Eliminasi Duplikasi Proyek Non-Fisik</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Format terpadu diterapkan untuk menghilangkan duplikasi <strong>antara kegiatan operasional dengan proyek, khususnya proyek-proyek non-fisik</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena sebelum reformasi sering terjadi kegiatan seminar atau kajian rutin birokrasi dibiayai ganda melalui pos operasional rutin sekaligus proyek non-fisik pembangunan. Anggaran terpadu menutup celah duplikasi pengeluaran ini.</p><p>Jawaban tepat: <strong>A. Antara kegiatan operasional dengan proyek non-fisik</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Belanja pegawai pusat dan daerah dipisahkan menurut kewenangan APBN dan APBD.</li><li><strong>C</strong>: Pajak dan bea cukai merupakan jenis penerimaan perpajakan yang berbeda pos.</li><li><strong>D</strong>: Pemeliharaan dan tanah dibedakan secara tegas dalam klasifikasi jenis belanja.</li><li><strong>E</strong>: Bunga utang dan obligasi adalah transaksi pembiayaan terpisah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 36) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>antara kegiatan operasional dengan proyek, khususnya proyek-proyek non-fisik</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Penyusunan anggaran berbasis kinerja dimudahkan oleh format terpadu untuk memperjelas kaitan antara...", "options": ["Target penerimaan pajak dengan biaya operasional pemungutan", "Output atau outcome yang dicapai dengan penganggaran organisasi", "Realisasi fisik proyek dengan jadwal pencairan dana kas", "Beban kerja aparatur dengan tunjangan remunerasi bulanan", "Nilai aset persediaan dengan liabilitas jangka pendek satuan"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuan Keterkaitan Output/Outcome</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Format terpadu memudahkan penyusunan ABK guna memperjelas keterkaitan antara <strong>output/outcome yang dicapai dengan penganggaran organisasi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena setiap rupiah yang dialokasikan dalam APBN wajib dapat dipertanggungjawabkan hasilnya secara nyata berupa keluaran dan dampak bagi masyarakat. Hal ini mewujudkan transparansi alokasi belanja publik.</p><p>Jawaban tepat: <strong>B. Output atau outcome yang dicapai dengan penganggaran organisasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Biaya pemungutan pajak bukan fokus utama pengukuran output kinerja belanja.</li><li><strong>C</strong>: Jadwal pencairan kas merupakan bagian dari manajemen likuiditas kas negara.</li><li><strong>D</strong>: Remunerasi aparatur diatur melalui sistem kompensasi pegawai sipil.</li><li><strong>E</strong>: Persediaan dan liabilitas merupakan komponen neraca laporan akuntansi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 36) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>output/outcome yang dicapai • penganggaran organisasi</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Penerapan anggaran terpadu dan reklasifikasi belanja bertujuan memberikan gambaran yang...", "options": ["Sederhana tanpa mencantumkan rincian belanja operasional", "Menguntungkan bagi penilaian kreditur lembaga donor asing", "Objektif dan proporsional mengenai kegiatan keuangan pemerintah", "Seragam di seluruh tingkatan satuan kerja perangkat daerah", "Mengutamakan perluasan belanja barang operasional kementerian"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Objektivitas Kegiatan Keuangan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Penerapan anggaran terpadu bertujuan <strong>memberikan gambaran yang objektif dan proporsional mengenai kegiatan keuangan pemerintah</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena reklasifikasi belanja menyajikan data alokasi dana secara faktual tanpa distorsi klasifikasi lama. Publik dan parlemen dapat melihat secara proporsional porsi pengeluaran untuk operasional versus pembangunan.</p><p>Jawaban tepat: <strong>C. Objektif dan proporsional mengenai kegiatan keuangan pemerintah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penyajian anggaran justru semakin terperinci dan transparan hingga level keluaran.</li><li><strong>B</strong>: Penyusunan anggaran negara ditujukan untuk akuntabilitas publik, bukan memikat donor.</li><li><strong>D</strong>: Penerapan pada level APBN fokus pada struktur kementerian negara pemerintah pusat.</li><li><strong>E</strong>: Tujuan reformasi adalah efisiensi belanja, bukan memperbesar belanja barang.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 36) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Memberikan gambaran yang objektif dan proporsional</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Peningkatan kredibilitas statistik keuangan pemerintah dilakukan dengan mengacu pada...", "options": ["Kebijakan fiskal gabungan negara-negara berkembang", "Sistem akuntansi perusahaan perbankan multinasional", "Rekomendasi audit badan akreditasi pendidikan tinggi", "Format keuangan pemerintah sesuai standar internasional", "Pedoman tata kelola perseroan terbatas terbuka nasional"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kredibilitas Statistik Keuangan Internasional</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Statistik keuangan pemerintah ditingkatkan kredibilitasnya dengan mengacu kepada <strong>format keuangan Pemerintah sesuai standar internasional</strong> (Government Finance Statistics/GFS).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena adopsi standar internasional GFS memungkinkan laporan keuangan pemerintah Indonesia dapat diperbandingkan secara global. Hal ini memperkuat kepercayaan investor dan lembaga internasional terhadap postur fiskal Indonesia.</p><p>Jawaban tepat: <strong>D. Format keuangan pemerintah sesuai standar internasional</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Format statistik mengikuti kerangka GFS IMF, bukan sekadar kompromi regional.</li><li><strong>B</strong>: Sistem perbankan mengacu pada standar PSAK korporasi komersial.</li><li><strong>C</strong>: Akreditasi pendidikan tinggi tidak mengatur format statistik keuangan negara.</li><li><strong>E</strong>: Tata kelola perseroan berlaku untuk entitas bisnis privat dan pasar modal.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 36) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>standar internasional</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Anggaran Berbasis Kinerja (ABK) pada hakikatnya didefinisikan sebagai penyiapan anggaran yang...", "options": ["Mendasarkan perhitungan biaya pada batas pagu pinjaman luar negeri", "Menentukan besaran pagu berdasarkan realisasi penyerapan tahun lalu", "Membagi rata seluruh pagu dana kepada satuan kerja kementerian", "Menetapkan alokasi belanja menurut usulan lisan pimpinan satker", "Mengaitkan setiap biaya dengan keluaran dan manfaat yang dihasilkan"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Anggaran Berbasis Kinerja</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Anggaran Berbasis Kinerja (ABK)</strong> adalah penyiapan anggaran negara di mana <strong>mengaitkan setiap biaya</strong> yang dituangkan dalam kegiatan dengan <strong>keluaran (outputs) dan manfaat yang dihasilkan (outcomes)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena inti dari pendekatan ABK adalah menghubungkan input rupiah dengan prestasi kerja nyata berupa output dan outcome. Pendekatan ini menggeser fokus administrasi dari sekadar input menjadi hasil yang terukur.</p><p>Jawaban tepat: <strong>E. Mengaitkan setiap biaya dengan keluaran dan manfaat yang dihasilkan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Batas pinjaman luar negeri adalah plafon pembiayaan, bukan konsep penyiapan ABK.</li><li><strong>B</strong>: Mendasarkan alokasi pada belanja masa lalu merupakan ciri anggaran inkremental kuno.</li><li><strong>C</strong>: Membagi rata pagu mengabaikan prioritas program dan target kinerja.</li><li><strong>D</strong>: Usulan tanpa tolok ukur kinerja bertentangan dengan prinsip penganggaran modern.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 36) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Anggaran Berbasis Kinerja (ABK) adalah penyiapan anggaran negara</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Dalam sistem ABK, program dan kegiatan terintegrasi dari rencana kinerja tahunan serta...", "options": ["Rencana operasional rencana strategis kementerian", "Laporan pengawasan internal inspektorat daerah", "Daftar susunan pegawai dan formasi jabatan fungsional", "Hasil rekapitulasi surat bukti tagihan perbendaharaan", "Kajian akademis lembaga swadaya masyarakat independen"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Integrasi Program dengan Renja dan Renstra</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Anggaran negara dituangkan dalam program dan kegiatan untuk mencapai kinerja tahunan dan terintegrasi dari <strong>rencana kinerja tahunan (Renja)/operasional Renstra</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena kesinambungan rencana pembangunan lima tahunan (Renstra) dijabarkan setiap tahun ke dalam Renja dan tercermin dalam alokasi DIPA. Keterpaduan dokumen perencanaan dan penganggaran menjamin pencapaian target jangka menengah.</p><p>Jawaban tepat: <strong>A. Rencana operasional rencana strategis kementerian</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Laporan pengawasan inspektorat merupakan umpan balik evaluasi pelaksanaan.</li><li><strong>C</strong>: Formasi pegawai merupakan urusan teknis administrasi kepegawaian aparatur.</li><li><strong>D</strong>: Rekapitulasi tagihan adalah dokumen penatausahaan transaksi kas harian.</li><li><strong>E</strong>: Kajian LSM merupakan aspirasi publik, bukan dokumen perencanaan resmi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 36) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>tahunan (Renja)/operasional Renstra</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Konsistensi penganggaran tahunan dari tahun ke tahun dijaga dengan menerapkan pedoman...", "options": ["Format neraca penutupan kas umum bendahara", "Kerangka belanja jangka menengah (MTEF)", "Standar operasional prosedur pengadaan rekanan", "Instruksi menteri terkait pembatasan belanja modal", "Pedoman verifikasi dokumen bukti penagihan daerah"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Medium Term Expenditure Framework (MTEF)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Anggaran tahunan disusun secara konsisten dari tahun ke tahun sesuai dengan <strong>kerangka belanja jangka menengah (Medium Term Expenditure Framework)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena MTEF menyajikan proyeksi alokasi belanja untuk kurun waktu 3 hingga 5 tahun ke depan dengan memperhitungkan implikasi biaya masa datang. Hal ini menjamin program strategis tidak terhenti akibat pergantian tahun anggaran.</p><p>Jawaban tepat: <strong>B. Kerangka belanja jangka menengah (MTEF)</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Neraca kas adalah instrumen penatausahaan likuiditas jangka pendek.</li><li><strong>C</strong>: SOP pengadaan merupakan aturan teknis operasional tender barang dan jasa.</li><li><strong>D</strong>: Instruksi pembatasan belanja adalah kebijakan penghematan temporer fiskal.</li><li><strong>E</strong>: Pedoman verifikasi tagihan merupakan standar kepatuhan administrasi pencairan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 36) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>kerangka belanja jangka menengah</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Perumusan seluruh program dan kegiatan dalam ABK secara terarah difokuskan untuk...", "options": ["Meningkatkan sisa lebih pembiayaan anggaran tahun berjalan", "Menghabiskan pagu dana kas sebelum penutupan buku", "Mencapai kinerja tahunan yang telah ditetapkan", "Memperluas struktur jenjang birokrasi kementerian lembaga", "Mengurangi alokasi belanja transfer bagi pemerintah daerah"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Fokus Program Penganggaran Kinerja</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Anggaran negara dituangkan dalam program dan kegiatan untuk <strong>mencapai kinerja tahunan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena orientasi utama ABK adalah pencapaian sasaran program kerja nyata dalam satu tahun anggaran. Penilaian keberhasilan satuan kerja tidak lagi semata didasarkan pada tingkat serapan dana melainkan capaian target indikator kinerjanya.</p><p>Jawaban tepat: <strong>C. Mencapai kinerja tahunan yang telah ditetapkan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Menaikkan SiLPA bukan tujuan perumusan program kerja dan kegiatan.</li><li><strong>B</strong>: Menghabiskan pagu secara terburu-buru merupakan praktik negatif yang dihilangkan.</li><li><strong>D</strong>: Perluasan birokrasi bertentangan dengan kebijakan penataan organisasi yang ramping.</li><li><strong>E</strong>: Pengurangan alokasi transfer daerah bukan sasaran penerapan sistem ABK.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 36) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>mencapai kinerja tahunan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Efektivitas belanja pemerintah dalam kerangka ABK dinilai dari keluaran serta...", "options": ["Jumlah panitia pengadaan yang dilibatkan satker", "Kecepatan pencairan uang persediaan bendahara", "Kerapian fisik map dokumen bukti pengeluaran", "Manfaat yang dihasilkan bagi masyarakat (outcomes)", "Besaran sisa kas yang belum terpakai di satker"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tolok Ukur Kemanfaatan Belanja</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Tolok ukur keberhasilan penyiapan anggaran mengaitkan biaya dengan keluaran dan <strong>manfaat yang dihasilkan (outcomes)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena manfaat jangka panjang bagi penerima layanan merupakan tolok ukur sejati dari efektivitas uang rakyat yang dibelanjakan negara. Output fisik yang dibangun harus menghasilkan perbaikan kualitas hidup masyarakat.</p><p>Jawaban tepat: <strong>D. Manfaat yang dihasilkan bagi masyarakat (outcomes)</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Jumlah panitia adalah urusan administratif pengorganisasian kerja.</li><li><strong>B</strong>: Kecepatan perputaran kas persediaan adalah indikator operasional kasir.</li><li><strong>C</strong>: Kerapian berkas bukti merupakan syarat administratif pertanggungjawaban.</li><li><strong>E</strong>: Sisa kas merupakan variabel likuiditas pada akhir siklus anggaran.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 36) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>manfaat yang dihasilkan (outcomes)</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Reklasifikasi belanja negara dalam reformasi penganggaran dikelompokkan menurut...", "options": ["Tingkat risiko audit, waktu lelang, dan jenis rekanan", "Lokasi daerah, status otonomi, dan pendapatan asli", "Masa berlaku ikatan, suku bunga, dan jenis valuta", "Pangkat aparatur, eselon jabatan, dan masa tugas", "Fungsi, organisasi, dan jenis belanja negara"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tiga Dimensi Reklasifikasi Belanja</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Reklasifikasi belanja negara dilakukan <strong>menurut fungsi, organisasi dan jenis</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena tiga dimensi ini merupakan standar klasifikasi anggaran modern internasional. Klasifikasi fungsi mencerminkan tujuan pelayanan publik, klasifikasi organisasi menunjukkan penanggung jawab program, dan klasifikasi jenis merinci sifat ekonomi belanjanya.</p><p>Jawaban tepat: <strong>E. Fungsi, organisasi, dan jenis belanja negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Risiko audit dan rekanan adalah parameter teknis pengadaan dan pemeriksa.</li><li><strong>B</strong>: Lokasi dan PAD merupakan dasar klasifikasi anggaran keuangan daerah APBD.</li><li><strong>C</strong>: Masa berlaku dan bunga adalah parameter klasifikasi portofolio pinjaman utang.</li><li><strong>D</strong>: Pangkat dan eselon merupakan variabel data administrasi kepegawaian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 36) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menurut fungsi, organisasi dan jenis</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Ketentuan pokok perbendaharaan negara disahkan pada tanggal 14 Januari 2004 melalui...", "options": ["UU Nomor 1 Tahun 2004 tentang Perbendaharaan Negara", "UU Nomor 17 Tahun 2003 tentang Keuangan Negara", "UU Nomor 15 Tahun 2004 tentang Pemeriksaan Keuangan", "UU Nomor 25 Tahun 2004 tentang Perencanaan Pembangunan", "UU Nomor 33 Tahun 2004 tentang Perimbangan Keuangan"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dasar Hukum Perbendaharaan Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pemerintah bersama DPR pada tanggal 14 Januari 2004 mengesahkan <strong>UU Nomor 1 Tahun 2004 tentang Perbendaharaan Negara</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena UU 1/2004 merupakan landasan hukum formil tertinggi bagi operasionalisasi perbendaharaan di seluruh instansi pemerintah. Regulasi ini menjadi pilar kedua dalam paket undang-undang reformasi keuangan negara.</p><p>Jawaban tepat: <strong>A. UU Nomor 1 Tahun 2004 tentang Perbendaharaan Negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: UU 17/2003 disahkan pada tahun 2003 dan merupakan payung umum keuangan negara.</li><li><strong>C</strong>: UU 15/2004 mengatur pemeriksaan pengelolaan dan tanggung jawab keuangan negara.</li><li><strong>D</strong>: UU 25/2004 mengatur sistem perencanaan pembangunan nasional.</li><li><strong>E</strong>: UU 33/2004 mengatur perimbangan keuangan antara pusat dan daerah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 37) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>UU Nomor 1 • tentang Perbendaharaan Negara</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "UU Perbendaharaan Negara berkedudukan sebagai ketentuan pelaksanaan tindak lanjut dari...", "options": ["Peraturan Pemerintah tentang Standar Akuntansi", "UU Nomor 17 Tahun 2003 tentang Keuangan Negara", "Undang-Undang Administrasi Pemerintahan Tata Usaha", "Ketetapan Majelis Permusyawaratan Rakyat", "Undang-Undang Badan Usaha Milik Negara"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kedudukan UU Perbendaharaan Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>UU Perbendaharaan Negara merupakan <strong>ketentuan pelaksanaan sebagai tindak lanjut atas disahkannya UU Nomor 17 Tahun 2003</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena prinsip-prinsip umum pengelolaan fiskal yang dirumuskan dalam UU 17/2003 diturunkan ke dalam pedoman teknis pelaksanaan kebendaharaan melalui UU 1/2004. Keduanya saling melengkapi dan terikat secara hierarki perundang-undangan.</p><p>Jawaban tepat: <strong>B. UU Nomor 17 Tahun 2003 tentang Keuangan Negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Standar Akuntansi Pemerintahan diatur di bawah undang-undang melalui Peraturan Pemerintah.</li><li><strong>C</strong>: UU Administrasi Pemerintahan mengatur tata kelola keputusan badan atau pejabat negara.</li><li><strong>D</strong>: TAP MPR merupakan produk hukum ketetapan lembaga legislatif era sebelumnya.</li><li><strong>E</strong>: UU BUMN mengatur tata kelola perseroan dan perum milik negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 37) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>17 Tahun 2003. Menurut UU Nomor 1 Tahun 2004</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Perbendaharaan Negara didefinisikan sebagai pengelolaan dan pertanggungjawaban keuangan negara termasuk...", "options": ["Seluruh cadangan modal badan usaha swasta murni", "Aset tanah milik perorangan pegawai negeri sipil", "Investasi dan kekayaan yang dipisahkan dalam APBN/APBD", "Kekayaan lembaga swadaya masyarakat dan yayasan", "Penerimaan laba kotor korporasi pemegang izin tambang"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Perbendaharaan Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Perbendaharaan Negara adalah pengelolaan dan pertanggungjawaban keuangan negara, <strong>termasuk investasi dan kekayaan yang dipisahkan</strong>, yang ditetapkan dalam APBN dan APBD.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena batasan yuridis perbendaharaan negara mencakup modal negara yang disertakan pada BUMN maupun badan hukum lainnya. Pengelolaan modal tersebut tetap berada dalam pengawasan pertanggungjawaban publik.</p><p>Jawaban tepat: <strong>C. Investasi dan kekayaan yang dipisahkan dalam APBN/APBD</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Cadangan modal swasta murni berada di luar ruang lingkup perbendaharaan negara.</li><li><strong>B</strong>: Aset pribadi pegawai adalah kepemilikan privat di luar ranah keuangan negara.</li><li><strong>D</strong>: Kekayaan yayasan non-pemerintah merupakan milik privat berbadan hukum yayasan.</li><li><strong>E</strong>: Laba korporasi tambang bukan aset negara sebelum disetor berupa pajak dan royalti.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 37) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>investasi dan kekayaan yang dipisahkan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Salah satu rincian ruang lingkup perbendaharaan negara dalam undang-undang adalah...", "options": ["Penyelenggaraan pemilihan umum kepala daerah", "Penetapan vonis sengketa tata usaha", "Pengawasan izin impor industri otomotif", "Pengelolaan kas negara atau daerah", "Pemberian status akreditasi fasilitas medis"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Ruang Lingkup Pengelolaan Kas</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Ruang lingkup Perbendaharaan Negara meliputi <strong>Pengelolaan kas negara/daerah</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena penatausahaan dan pengendalian arus likuiditas uang tunai negara merupakan fungsi inti dari institusi perbendaharaan. Pengelolaan kas menjamin ketersediaan dana saat kewajiban pembayaran jatuh tempo.</p><p>Jawaban tepat: <strong>D. Pengelolaan kas negara atau daerah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penyelenggaraan pilkada adalah mandat komisi pemilihan umum.</li><li><strong>B</strong>: Vonis sengketa TUN adalah kompetensi lembaga peradilan tata usaha negara.</li><li><strong>C</strong>: Izin impor industri otomotif merupakan kewenangan kementerian perindustrian.</li><li><strong>E</strong>: Akreditasi fasilitas medis adalah wewenang kementerian kesehatan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 37) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pengelolaan kas negara/daerah</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Bidang perbendaharaan yang mengatur hak tagih serta kewajiban pinjaman pemerintah adalah...", "options": ["Penyusunan kurikulum pelatihan teknis kedinasan", "Penatausahaan sertifikat hak milik tanah adat", "Penetapan standar gaji tenaga kerja sektor swasta", "Pengawasan tingkat inflasi harga komoditas pokok", "Pengelolaan piutang dan utang negara atau daerah"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Ruang Lingkup Piutang dan Utang</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Ruang lingkup perbendaharaan negara mencakup <strong>Pengelolaan piutang dan utang negara/daerah</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena penagihan piutang macet dan pembayaran cicilan utang negara memerlukan tata kelola yang transparan dan akuntabel. Pengelolaan ini menjaga solvabilitas neraca keuangan publik.</p><p>Jawaban tepat: <strong>E. Pengelolaan piutang dan utang negara atau daerah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurikulum pelatihan adalah tugas unit pembina kedinasan SDM.</li><li><strong>B</strong>: Sertifikat tanah adat dikelola oleh kementerian agraria dan tata ruang.</li><li><strong>C</strong>: Upah minimum tenaga kerja swasta ditetapkan oleh dewan pengupahan daerah.</li><li><strong>D</strong>: Pengendalian inflasi komoditas adalah domain tim pengendalian inflasi daerah dan BI.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 37) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pengelolaan piutang dan utang negara/daerah</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Satuan kerja pemerintah yang diberikan fleksibilitas pengelolaan keuangan diatur dalam ruang lingkup...", "options": ["Pengelolaan keuangan badan layanan umum (BLU)", "Penetapan batasan modal perseroan tertutup daerah", "Pengawasan permodalan koperasi simpan pinjam desa", "Pengendalian operasional bank swasta nasional", "Tata kelola organisasi kemasyarakatan non-profit"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Ruang Lingkup Keuangan BLU</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Cakupan perbendaharaan negara meliputi <strong>Pengelolaan keuangan badan layanan umum</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena BLU merupakan instansi pemerintah yang menerapkan pola pengelolaan keuangan khusus untuk meningkatkan pelayanan umum. Pendapatan layanannya dapat digunakan langsung tanpa harus disetor ke kas negara lebih dahulu.</p><p>Jawaban tepat: <strong>A. Pengelolaan keuangan badan layanan umum (BLU)</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Perseroan tertutup daerah merupakan entitas BUMD berbadan hukum PT.</li><li><strong>C</strong>: Koperasi simpan pinjam berada di bawah pembinaan dinas koperasi.</li><li><strong>D</strong>: Pengawasan perbankan swasta merupakan fungsi Otoritas Jasa Keuangan.</li><li><strong>E</strong>: Organisasi kemasyarakatan diatur dalam undang-undang keormasan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 37) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pengelolaan keuangan badan layanan umum</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Dalam pelaksanaan anggaran, menteri teknis atau pimpinan lembaga berkedudukan sebagai pemegang...", "options": ["Kewenangan kebendaharaan umum kas negara", "Kewenangan administratif pelaksanaan anggaran", "Kewenangan yudikatif persidangan sengketa", "Kewenangan audit investigasi tindak pidana", "Kewenangan perumusan standar akuntansi publik"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pembagian Kewenangan Menteri Teknis</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pelaksanaan anggaran membagi tugas antara Menteri Keuangan selaku pemegang kewenangan kebendaharaan dengan Menteri/Pimpinan Lembaga selaku pemegang <strong>kewenangan administratif</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena menteri teknis bertindak sebagai pengguna anggaran yang berwenang menetapkan kegiatan operasional dan membuat perikatan. Pemisahan fungsi ini menerapkan asas saling uji dalam birokrasi negara.</p><p>Jawaban tepat: <strong>B. Kewenangan administratif pelaksanaan anggaran</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kewenangan kebendaharaan umum negara dipegang oleh Menteri Keuangan selaku BUN.</li><li><strong>C</strong>: Kewenangan yudikatif berada pada ranah kekuasaan kehakiman.</li><li><strong>D</strong>: Audit investigasi pidana merupakan wewenang aparat penegak hukum dan BPKP.</li><li><strong>E</strong>: Standar akuntansi pemerintahan dirumuskan oleh komite standar independen KSAP.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 37) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>kewenangan administratif</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Kewenangan administratif menteri negara mencakup wewenang untuk melakukan...", "options": ["Penempatan dana cadangan kas umum di perbankan sentral", "Penerbitan surat utang negara di bursa efek internasional", "Perikatan yang mengakibatkan penerimaan atau pengeluaran negara", "Pemeriksaan forensik terhadap rekening penampungan pajak", "Pencairan langsung kas negara tanpa melalui kuasa BUN"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Unsur Perikatan dalam Kewenangan Administratif</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kewenangan administratif mencakup kewenangan untuk <strong>melakukan perikatan atau tindakan lain yang mengakibatkan terjadinya penerimaan atau pengeluaran negara</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena pengikatan kontrak kerja pengadaan barang/jasa dengan pihak ketiga merupakan wewenang substantif kementerian pengguna anggaran. Realisasi perikatan inilah yang nantinya melahirkan hak tagih terhadap kas negara.</p><p>Jawaban tepat: <strong>C. Perikatan yang mengakibatkan penerimaan atau pengeluaran negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penempatan kas umum negara adalah mandat Bendahara Umum Negara.</li><li><strong>B</strong>: Penerbitan surat utang negara merupakan kewenangan eksklusif Menteri Keuangan.</li><li><strong>D</strong>: Pemeriksaan forensik adalah domain aparat pemeriksa fungsional.</li><li><strong>E</strong>: Pencairan kas wajib melewati verifikasi dan penerbitan SP2D oleh KPPN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 37) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>melakukan perikatan atau tindakan lain</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Kewenangan pimpinan kementerian sehubungan dengan realisasi perikatan meliputi...", "options": ["Pengurangan tarif bea masuk impor komoditas strategis", "Penetapan tingkat suku bunga acuan pinjaman perbankan", "Pemberian grasi pembebasan tuntutan kerugian keuangan", "Pengujian dan pembebanan tagihan yang diajukan pihak ketiga", "Pengalihan saldo kas antar kementerian secara mandiri"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengujian dan Pembebanan Tagihan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Menteri negara memiliki <strong>kewenangan melakukan pengujian dan pembebanan tagihan yang diajukan</strong> sehubungan dengan realisasi perikatan tersebut.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena sebelum tagihan diajukan ke perbendaharaan, pejabat kementerian wajib memeriksa kelengkapan prestasi pekerjaan rekanan. Setelah terbukti sah, tagihan tersebut dibebankan pada pagu DIPA yang tersedia.</p><p>Jawaban tepat: <strong>D. Pengujian dan pembebanan tagihan yang diajukan pihak ketiga</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penetapan tarif bea masuk diatur oleh Menteri Keuangan dan kementerian teknis.</li><li><strong>B</strong>: Suku bunga acuan merupakan kewenangan dewan gubernur bank sentral.</li><li><strong>C</strong>: Pemberian grasi adalah hak prerogatif presiden di bidang hukum pidana.</li><li><strong>E</strong>: Pengalihan anggaran antar lembaga dilarang tanpa persetujuan DPR dan Menkeu.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 37) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>dan pembebanan tagihan yang diajukan kepada menteri negara/lembaga</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Tindakan akhir dari pelaksanaan kewenangan administratif kementerian negara adalah...", "options": ["Membatalkan undang-undang anggaran pendapatan tahun berjalan", "Mencetak uang kertas dan logam untuk pembayaran rekanan", "Menyita barang jaminan utang debitur perbankan komersial", "Menutup rekening kas umum daerah di bank pembangunan", "Memerintahkan pembayaran atau menagih penerimaan anggaran"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Perintah Pembayaran dan Penagihan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kewenangan administratif mencakup wewenang untuk <strong>memerintahkan pembayaran atau menagih penerimaan yang timbul sebagai akibat pelaksanaan anggaran</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena penerbitan Surat Perintah Membayar (SPM) kepada KPPN merupakan muara dari proses administratif di kementerian teknis. Demikian pula hak penagihan penerimaan negara wajib dijalankan demi mengamankan kas negara.</p><p>Jawaban tepat: <strong>E. Memerintahkan pembayaran atau menagih penerimaan anggaran</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pembatalan undang-undang adalah kewenangan Mahkamah Konstitusi.</li><li><strong>B</strong>: Mencetak uang rupiah adalah hak eksklusif Bank Indonesia bersama Perum Peruri.</li><li><strong>C</strong>: Penyitaan jaminan perbankan komersial diatur melalui hukum perdata dan PUPN.</li><li><strong>D</strong>: Rekening kas daerah di bawah kewenangan kepala daerah dan bendahara daerah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 37) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>memerintahkan pembayaran • menagih penerimaan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Dalam melaksanakan kewenangan kebendaharaan, Menteri Keuangan menjalankan tiga fungsi yaitu...", "options": ["Sebagai kasir, pengawas keuangan, dan manajer keuangan", "Sebagai perencana proyek, penguji kuitansi, dan penilai aset", "Sebagai pembuat komitmen, penagih piutang, dan bendahara barang", "Sebagai pengarah teknis, pelaksana pekerjaan, dan auditor intern", "Sebagai penyidik pajak, jaksa penuntut, dan bendahara kasir"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tiga Fungsi Menteri Keuangan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Menteri Keuangan merupakan pengelola keuangan yang berfungsi <strong>sebagai kasir, pengawas keuangan, dan sekaligus sebagai manajer keuangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena tri-fungsi kebendaharaan ini mencakup peran operasional penyimpanan kas (kasir), pengendalian kepatuhan hukum pembayaran (pengawas), dan pengelolaan portofolio aset/utang negara (manajer). Ketiganya menyatu pada Menteri Keuangan selaku BUN.</p><p>Jawaban tepat: <strong>A. Sebagai kasir, pengawas keuangan, dan manajer keuangan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Perencana proyek adalah peran kementerian teknis pengusul kegiatan.</li><li><strong>C</strong>: Pembuat komitmen adalah peran PPK di masing-masing satuan kerja.</li><li><strong>D</strong>: Pelaksana pekerjaan fisik adalah penyedia barang/jasa rekanan pemerintah.</li><li><strong>E</strong>: Jaksa penuntut adalah aparat penegak hukum pada kejaksaan agung.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 38) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>berfungsi sebagai kasir, pengawas • keuangan, dan sekaligus sebagai manajer keuangan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Fungsi pengawasan yang dimiliki Menteri Keuangan secara spesifik dibatasi pada aspek...", "options": ["Kelayakan teknis kontruksi bangunan dan spesifikasi mesin", "Rechmatigheid dan wetmatigheid pada saat penerimaan atau pengeluaran", "Kinerja manajerial kepegawaian pejabat satuan kerja", "Dampak politis penetapan alokasi anggaran kementerian", "Keadilan sosiologis pembagian bantuan sosial masyarakat"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Batasan Pengawasan Menteri Keuangan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Fungsi pengawasan Menteri Keuangan terbatas pada <strong>aspek rechmatigheid (ketaatan pada aturan hukum) dan wetmatigheid (ketaatan pada aturan perundangan)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena Menkeu bertindak mengawasi legalitas dokumen transaksi dari sisi perundang-undangan formal pada saat kas bergerak. Menkeu tidak menguji kelayakan teknis proyek yang menjadi kewenangan menteri terkait.</p><p>Jawaban tepat: <strong>B. Rechmatigheid dan wetmatigheid pada saat penerimaan atau pengeluaran</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Spesifikasi teknik mesin diuji dan diawasi oleh pejabat teknis kementerian.</li><li><strong>C</strong>: Kinerja kepegawaian satker diawasi oleh pimpinan instansi dan BKN.</li><li><strong>D</strong>: Dampak politis alokasi anggaran adalah dinamika pembahasan di DPR.</li><li><strong>E</strong>: Keadilan sosiologis bansos merupakan ranah kebijakan sosial pemerintah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 38) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>aspek rechmatigheid • ketaatan pada aturan hukum</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Pengawasan aspek legalitas oleh bendahara umum negara hanya dilakukan pada saat...", "options": ["Pelaksanaan lelang tender terbuka di unit layanan pengadaan", "Penyusunan kerangka acuan kerja pengadaan barang satker", "Terjadinya peristiwa penerimaan atau pengeluaran kas negara", "Pemeriksaan tahunan selesai dilakukan oleh Badan Pemeriksa Keuangan", "Pengesahan rencana strategis kementerian negara oleh Bappenas"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Waktu Pelaksanaan Pengawasan Kebendaharaan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pengawasan bendahara umum negara <strong>hanya dilakukan pada saat terjadinya penerimaan atau pengeluaran</strong> kas.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena peran Kuasa BUN adalah penjaga pintu kasir negara yang menguji keabsahan perintah bayar sebelum mencairkan dana. Uji kepatuhan dilakukan secara langsung saat SPM masuk dan kas hendak keluar.</p><p>Jawaban tepat: <strong>C. Terjadinya peristiwa penerimaan atau pengeluaran kas negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pelaksanaan lelang diawasi oleh panitia lelang dan Pokja Pemilihan.</li><li><strong>B</strong>: Kerangka acuan kerja disusun oleh PPK sebelum tender dimulai.</li><li><strong>D</strong>: Pemeriksaan BPK dilakukan setelah tahun anggaran berakhir (post-audit).</li><li><strong>E</strong>: Pengesahan renstra merupakan tahap awal dalam sistem perencanaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 38) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pada saat terjadinya penerimaan atau pengeluaran</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Pemeriksaan post-audit yang berbeda dari pengawasan kebendaharaan dilaksanakan oleh...", "options": ["Panitia pengadaan barang jasa saat proses evaluasi rekanan", "Pejabat pembuat komitmen sebelum penandatanganan kontrak", "Kuasa bendahara umum negara pada saat meneliti SPM", "Aparat pengawasan fungsional setelah kegiatan selesai", "Bendahara pengeluaran pada saat menerima uang persediaan"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Perbedaan dengan Post-Audit Fungsional</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pengawasan Menkeu berbeda dengan pre-audit menteri teknis atau <strong>post-audit yang dilaksanakan oleh aparat pengawasan fungsional</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena post-audit bersifat komprehensif atas seluruh bukti dan fisik pekerjaan setelah kegiatan tuntas oleh auditor internal (Itjen/BPKP) atau eksternal (BPK). Sementara pengawasan Menkeu hanya bersifat verifikasi kasir saat transaksi kas.</p><p>Jawaban tepat: <strong>D. Aparat pengawasan fungsional setelah kegiatan selesai</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Evaluasi penawaran rekanan adalah urusan panitia pemilihan penyedia.</li><li><strong>B</strong>: Pengujian oleh PPK sebelum kontrak adalah bagian dari pre-audit administratif.</li><li><strong>C</strong>: Penelitian SPM oleh Kuasa BUN adalah verifikasi pembayaran kasir.</li><li><strong>E</strong>: Penerimaan UP oleh bendahara merupakan pencatatan kas persediaan kerja.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 38) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>aparat pengawasan fungsional</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Pada tahapan pembuatan komitmen, Pengguna Anggaran melaksanakan kegiatan menurut...", "options": ["Draf awal rancangan rencana kerja satuan kerja", "Permintaan tertulis rekanan pengadaan barang jasa", "Estimasi penerimaan kas kementerian bulan berjalan", "Arahan lisan pimpinan satker di luar batas pagu", "Dokumen pelaksanaan anggaran yang telah disahkan"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dasar Pelaksanaan Kegiatan Komitmen</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pada tahapan ini, Pengguna Anggaran/KPA melaksanakan kegiatan sebagaimana tersebut dalam <strong>dokumen pelaksanaan anggaran yang telah disahkan</strong> (DIPA).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena DIPA yang sah merupakan dasar otorisasi dan alokasi resmi bagi satuan kerja untuk bertindak. Tindakan tanpa dasar DIPA yang disahkan adalah pelanggaran hukum keuangan negara.</p><p>Jawaban tepat: <strong>E. Dokumen pelaksanaan anggaran yang telah disahkan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Draf awal renja belum memiliki kekuatan hukum untuk membebani keuangan negara.</li><li><strong>B</strong>: Permintaan rekanan tidak boleh mendasari pelaksanaan kegiatan dinas.</li><li><strong>C</strong>: Estimasi penerimaan kas adalah variabel arus kas, bukan dokumen otorisasi.</li><li><strong>D</strong>: Arahan lisan di luar pagu bertentangan dengan asas legalitas anggaran.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 38) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>dokumen pelaksanaan anggaran yang telah • disahkan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Pengguna Anggaran berwenang mengadakan perjanjian dengan pihak lain dengan batasan...", "options": ["Dalam batas anggaran yang telah ditetapkan", "Disesuaikan dengan persetujuan lisan pihak rekanan", "Berdasarkan estimasi potensi keuntungan masa depan", "Bebas menentukan nilai ikatan tanpa batasan pagu", "Mengutamakan nilai kontrak di atas alokasi DIPA"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Batasan Wewenang Perikatan KPA</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>PA/KPA berwenang <strong>mengadakan ikatan/perjanjian dengan pihak lain dalam batas anggaran yang telah ditetapkan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena asas batas anggaran melarang pejabat membuat komitmen perikatan melebihi plafon pagu yang disetujui DPR. Komitmen yang melampaui pagu memicu utang liar yang membebani kas negara.</p><p>Jawaban tepat: <strong>A. Dalam batas anggaran yang telah ditetapkan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Persetujuan rekanan tidak bisa mengesampingkan batas pagu undang-undang.</li><li><strong>C</strong>: Estimasi keuntungan swasta bukan pertimbangan batas perikatan dinas.</li><li><strong>D</strong>: Nilai ikatan dibatasi ketat oleh pagu mata anggaran pengeluaran.</li><li><strong>E</strong>: Membuat ikatan melampaui alokasi DIPA merupakan pelanggaran hukum berat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 38) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>ikatan/ perjanjian dengan pihak lain dalam batas anggaran</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Dalam rangka melaksanakan kewenangannya, KPA menetapkan pejabat yang bertindak sebagai...", "options": ["Auditor investigasi eksternal badan pemeriksa keuangan", "Pejabat Pembuat Komitmen (PPK) pada satuan kerja", "Pengelola rekening penampungan pajak kantor pos", "Pejabat lelang negara pada kantor perbankan sentral", "Anggota dewan pengawas badan usaha milik swasta"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penetapan Pejabat Pembuat Komitmen</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam rangka melaksanakan kewenangannya KPA menetapkan <strong>Pejabat Pembuat Komitmen (PPK)</strong>, serta menetapkan panitia/pejabat yang terlibat.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena KPA melimpahkan sebagian kewenangan perikatannya kepada PPK untuk menandatangani kontrak pengadaan. PPK memikul tanggung jawab teknis operasional atas ikatan yang dibuatnya.</p><p>Jawaban tepat: <strong>B. Pejabat Pembuat Komitmen (PPK) pada satuan kerja</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Auditor BPK diangkat oleh negara melalui mekanisme undang-undang BPK.</li><li><strong>C</strong>: Rekening kantor pos dikelola oleh instansi pos dan perbankan penerima.</li><li><strong>D</strong>: Pejabat lelang negara berada di bawah Direktorat Jenderal Kekayaan Negara.</li><li><strong>E</strong>: Dewan pengawas swasta ditunjuk melalui rapat umum pemegang saham perseroan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 38) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pembuat Komitmen (PPK), serta menetapkan panitia/pejabat</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Setelah kegiatan dilaksanakan, Pengguna Anggaran berhak melakukan pengujian serta...", "options": ["Menandatangani cek pencairan dana kas umum negara", "Mengalihkan saldo belanja ke kas operasional pribadi", "Memerintahkan pembayaran tagihan atas beban APBN/APBD", "Menghapuskan bukti pertanggungjawaban fisik dokumen", "Mengubah pagu DIPA kementerian secara mandiri di satker"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Hak KPA Setelah Kegiatan Dilaksanakan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Setelah kegiatan selesai, PA/KPA berhak melakukan pengujian, <strong>memerintahkan pembayaran tagihan-tagihan atas beban APBN/APBD</strong>, dan membebankan pada mata anggaran.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena hak memerintahkan pembayaran merupakan tindak lanjut sah atas penyelesaian prestasi kerja oleh pihak penyedia jasa. Perintah ini dituangkan dalam penerbitan Surat Perintah Membayar.</p><p>Jawaban tepat: <strong>C. Memerintahkan pembayaran tagihan atas beban APBN/APBD</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pencairan kas umum adalah mandat eksklusif Kuasa BUN melalui SP2D.</li><li><strong>B</strong>: Mengalihkan belanja dinas ke pos pribadi merupakan perbuatan melanggar hukum perbendaharaan.</li><li><strong>D</strong>: Menghilangkan bukti pertanggungjawaban adalah perbuatan melawan hukum.</li><li><strong>E</strong>: Revisi DIPA wajib melalui mekanisme pengesahan Direktorat Jenderal Anggaran.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 38) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>memerintahkan pembayaran tagihan-tagihan atas beban APBN/APBD</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Kewenangan PA/KPA dalam meneliti tagihan dari pihak rekanan mencakup tindakan...", "options": ["Menyerahkan verifikasi dokumen bukti kepada pihak rekanan", "Menentukan harga pasar barang tanpa mengacu pada kontrak", "Mengurangi nominal tagihan rekanan untuk kas satker", "Menguji kebenaran material surat bukti hak pihak penagih", "Mencairkan dana belanja tanpa meneliti berita acara fisik"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Wewenang Pengujian Material KPA</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>PA/KPA berwenang <strong>menguji kebenaran material surat-surat bukti mengenai hak pihak penagih</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena keabsahan tagihan tidak hanya dilihat dari formalitas tanda tangan, tetapi juga kebenaran fakta bahwa barang atau jasa telah diterima secara nyata dan bermutu sesuai klausul kontrak.</p><p>Jawaban tepat: <strong>D. Menguji kebenaran material surat bukti hak pihak penagih</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Verifikasi bukti adalah tanggung jawab pejabat perbendaharaan instansi.</li><li><strong>B</strong>: Harga barang wajib mengacu ketat pada nilai kontrak yang telah disepakati.</li><li><strong>C</strong>: Mengurangi hak penagih secara sewenang-wenang melanggar perjanjian hukum.</li><li><strong>E</strong>: Membayar tanpa berita acara serah terima melanggar prosedur perbendaharaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 38) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menguji kebenaran material surat-surat bukti mengenai hak pihak penagih</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Sebelum memerintahkan pembayaran, Pengguna Anggaran wajib membebankan pengeluaran pada...", "options": ["Dana talangan pribadi pimpinan satuan kerja satker", "Rekening penampungan dana cadangan kementerian lain", "Pos belanja tak terduga pemerintah daerah setempat", "Pagu anggaran tahun berikutnya secara mendahului", "Mata anggaran pengeluaran yang bersangkutan dan tersedia"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pembebanan Mata Anggaran yang Sesuai</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>PA/KPA berwenang <strong>membebankan pengeluaran sesuai dengan mata anggaran pengeluaran yang bersangkutan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena prinsip spesialisasi mata anggaran mengharuskan setiap jenis belanja dicatat tepat pada akun mata anggirannya (MAK) dan memiliki saldo pagu yang mencukupi. Pembebanan yang salah akun mengakibatkan salah saji laporan keuangan.</p><p>Jawaban tepat: <strong>E. Mata anggaran pengeluaran yang bersangkutan dan tersedia</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penggunaan dana talangan pribadi menyalahi tata kelola keuangan negara.</li><li><strong>B</strong>: Membebankan pengeluaran pada kementerian lain dilarang keras undang-undang.</li><li><strong>C</strong>: Belanja APBN tidak boleh dibebankan pada pos APBD pemerintah daerah.</li><li><strong>D</strong>: Pengeluaran tahun berjalan dilarang membebani pagu tahun depan tanpa dasar sah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 38) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pengeluaran sesuai dengan mata anggaran pengeluaran yang • bersangkutan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Pejabat yang menandatangani atau mengesahkan dokumen surat bukti pengeluaran bertanggung jawab atas...", "options": ["Kebenaran material dan akibat hukum yang timbul dari dokumen", "Kelancaran penghimpunan penerimaan perpajakan secara nasional", "Penyediaan likuiditas cadangan kas umum di bank sentral", "Tingkat penyerapan pagu belanja di kementerian lain", "Perubahan regulasi standar akuntansi sektor pemerintahan"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tanggung Jawab Kebenaran Material Dokumen</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pejabat yang menandatangani dan/atau mengesahkan surat bukti pengeluaran <strong>bertanggung jawab atas kebenaran material dan akibat yang timbul dari penggunaan surat bukti dimaksud</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena penandatangan bukti pembayaran memikul konsekuensi hukum perdata maupun pidana atas fakta substansi transaksi tersebut. Tanggung jawab ini melekat pada integritas pejabat penandatangan.</p><p>Jawaban tepat: <strong>A. Kebenaran material dan akibat hukum yang timbul dari dokumen</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Penerimaan perpajakan nasional adalah tanggung jawab Direktorat Jenderal Pajak.</li><li><strong>C</strong>: Penyediaan likuiditas kas umum negara merupakan mandat Kuasa BUN Pusat.</li><li><strong>D</strong>: Penyerapan kementerian lain berada di luar lingkup tanggung jawab satker.</li><li><strong>E</strong>: Perubahan standar akuntansi adalah kewenangan komite independen KSAP.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 39) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>kebenaran material dan akibat yang timbul dari • penggunaan surat bukti</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Pembayaran atas tagihan yang menjadi beban APBN secara definitif dilakukan oleh...", "options": ["Pejabat Pembuat Komitmen pada masing-masing unit satker", "Bendahara Umum Negara atau Kuasa Bendahara Umum Negara", "Auditor pengawas internal inspektorat kementerian", "Petugas administrasi bagian kepegawaian kantor cabang", "Pimpinan asosiasi rekanan penyedia jasa konstruksi"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pelaksana Pembayaran Beban APBN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pembayaran atas tagihan yang menjadi beban APBN dilakukan oleh <strong>Bendahara Umum Negara (BUN)/Kuasa BUN</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena Kuasa BUN (KPPN) memegang rekening kas umum negara dan memiliki otoritas tunggal mencairkan dana dari kas negara melalui penerbitan SP2D. Hal ini menjamin sentralisasi pengelolaan kas negara.</p><p>Jawaban tepat: <strong>B. Bendahara Umum Negara atau Kuasa Bendahara Umum Negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: PPK hanya berwenang menerbitkan SPP untuk diuji oleh pejabat penerbit SPM.</li><li><strong>C</strong>: Auditor internal bertugas mengawasi dan membina, bukan juru bayar kasir.</li><li><strong>D</strong>: Petugas kepegawaian mengurus administrasi data aparatur negara.</li><li><strong>E</strong>: Asosiasi rekanan adalah organisasi eksternal penyedia barang swasta.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 39) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Bendahara Umum • Negara (BUN)/Kuasa BUN</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Dalam rangka pelaksanaan pembayaran, kewajiban awal Kuasa BUN terhadap SPM adalah...", "options": ["Memeriksa fisik pekerjaan bangunan di lokasi proyek satker", "Mengubah nomor rekening penerima tagihan secara manual", "Meneliti kelengkapan perintah pembayaran yang diterbitkan PA/KPA", "Menegosiasi ulang besaran harga kontrak kerja rekanan", "Menambah beban potongan pajak di luar ketentuan hukum"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kewajiban Meneliti Kelengkapan SPM</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam rangka pembayaran BUN/Kuasa BUN berkewajiban <strong>meneliti kelengkapan perintah pembayaran yang diterbitkan oleh Pengguna Anggaran/Kuasa Pengguna Anggaran</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena KPPN wajib memastikan surat perintah bayar memenuhi seluruh persyaratan formal yang sah sebelum diproses. Pengujian kelengkapan dokumen merupakan garda depan pengamanan belanja APBN.</p><p>Jawaban tepat: <strong>C. Meneliti kelengkapan perintah pembayaran yang diterbitkan PA/KPA</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pemeriksaan fisik di lapangan merupakan tanggung jawab teknis KPA dan PPK.</li><li><strong>B</strong>: Mengubah rekening penerima tanpa dasar sah merupakan pelanggaran berat.</li><li><strong>D</strong>: Negosiasi kontrak kerja selesai pada tahap pengadaan oleh PPK.</li><li><strong>E</strong>: Potongan pajak wajib sesuai dengan tarif resmi ketentuan perpajakan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 39) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>meneliti kelengkapan perintah pembayaran yang diterbitkan oleh</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Selain meneliti kelengkapan surat bukti, Kuasa BUN berkewajiban melakukan...", "options": ["Pengawasan kedisiplinan jam kerja harian pegawai satker", "Penetapan jadwal libur kerja rekanan penyedia barang", "Pemeriksaan latar belakang riwayat keluarga penyedia jasa", "Pengujian ketersediaan dana dan kebenaran perhitungan tagihan", "Penyitaan kendaraan operasional dinas milik kementerian"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengujian Ketersediaan Dana oleh Kuasa BUN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>BUN/Kuasa BUN berkewajiban <strong>menguji kebenaran perhitungan tagihan</strong> serta <strong>menguji ketersediaan dana yang bersangkutan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena penerbitan SP2D tidak boleh mengakibatkan terjadinya saldo minus pada mata anggaran terkait di kas negara. Pengujian perhitungan memastikan tidak ada kelebihan bayar akibat kesalahan hitung.</p><p>Jawaban tepat: <strong>D. Pengujian ketersediaan dana dan kebenaran perhitungan tagihan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Disiplin jam kerja pegawai satker diawasi atasan langsung dan inspektorat.</li><li><strong>B</strong>: Jadwal kerja rekanan merupakan urusan internal manajemen penyedia.</li><li><strong>C</strong>: Riwayat keluarga penyedia berada di luar ranah pengujian keuangan perbendaharaan.</li><li><strong>E</strong>: Penyitaan kendaraan dinas menyalahi prosedur pengelolaan aset negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 39) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menguji ketersediaan dana yang bersangkutan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Tindakan yang wajib diambil Kuasa BUN apabila perintah pembayaran tidak memenuhi syarat adalah...", "options": ["Melimpahkan sisa tagihan ke kas daerah pemerintah kota", "Menerbitkan SP2D bersyarat dengan jaminan lisan satker", "Mengoreksi dokumen sendiri tanpa memberitahu satker", "Membayarkan separuh dari total nominal tagihan SPM", "Menolak pencairan dana yang diajukan oleh PA atau KPA"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penolakan Pencairan Dana Tidak Memenuhi Syarat</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>BUN/Kuasa BUN berwenang <strong>menolak pencairan dana, apabila perintah pembayaran yang diterbitkan oleh Pengguna Anggaran/Kuasa Pengguna Anggaran tidak memenuhi persyaratan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena undang-undang secara tegas melarang kasir negara mencairkan dana atas dokumen pembayaran yang cacat hukum atau tidak lengkap. Penolakan ini disertai surat pengembalian berkas untuk diperbaiki satker.</p><p>Jawaban tepat: <strong>E. Menolak pencairan dana yang diajukan oleh PA atau KPA</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kas daerah APBD terpisah mutlak dari pengeluaran APBN kementerian.</li><li><strong>B</strong>: Menerbitkan SP2D bersyarat atas jaminan lisan melanggar hukum perbendaharaan.</li><li><strong>C</strong>: KPPN dilarang mengoreksi sepihak dokumen yang diterbitkan kementerian.</li><li><strong>D</strong>: Pembayaran separuh tanpa dasar SPM revisi menyalahi prinsip kasir.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 39) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menolak pencairan dana, apabila perintah pembayaran</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Prinsip dasar pengeluaran kas negara menetapkan bahwa pembayaran beban APBN...", "options": ["Tidak boleh dilakukan sebelum barang dan jasa diterima", "Wajib dilunasi secara penuh pada saat penandatanganan kontrak", "Dapat dibayarkan di awal sebelum penyedia memulai pekerjaan", "Diberikan berdasarkan estimasi kesanggupan kerja rekanan", "Dilunasi separuh sebelum ada jaminan pelaksanaan tender"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Prinsip Pembayaran Setelah Barang Diterima</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pembayaran atas beban APBN/APBD <strong>tidak boleh dilakukan sebelum barang dan/atau jasa diterima</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena prinsip prestasi mendahului pembayaran bertujuan mengamankan uang negara dari risiko gagal serah oleh pihak penyedia jasa. Kas negara hanya boleh dibayarkan atas manfaat nyata yang sudah terbukti diterima negara.</p><p>Jawaban tepat: <strong>A. Tidak boleh dilakukan sebelum barang dan jasa diterima</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Pelunasan penuh di muka saat kontrak bertentangan dengan prinsip kehati-hatian.</li><li><strong>C</strong>: Uang muka kerja diperbolehkan terbatas hanya dengan jaminan uang muka bank.</li><li><strong>D</strong>: Estimasi kesanggupan bukan bukti prestasi kerja yang sah menurut hukum.</li><li><strong>E</strong>: Jaminan pelaksanaan adalah syarat kontrak, bukan dasar pembayaran tanpa prestasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 39) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>tidak boleh dilakukan sebelum barang • jasa diterima</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Untuk kelancaran pelaksanaan tugas kementerian, kepada PA/KPA dapat diberikan...", "options": ["Pinjaman modal kerja jangka pendek dari bank komersial", "Uang persediaan yang dikelola oleh Bendahara Pengeluaran", "Kredit tanpa agunan dari kas daerah pemerintah provinsi", "Otorisasi pencairan valuta asing langsung dari pasar modal", "Dana cadangan devisa langsung dari kas simpanan bank sentral"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pemberian Uang Persediaan Satker</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Untuk kelancaran pelaksanaan tugas, kepada PA/KPA <strong>dapat diberikan uang persediaan yang dikelola oleh Bendahara Pengeluaran</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena uang persediaan (UP) adalah dana talangan resmi dari kas negara untuk membiayai operasional harian satker yang tidak efektif jika dibayar dengan mekanisme pembayaran langsung (LS). UP dikelola oleh bendahara pengeluaran.</p><p>Jawaban tepat: <strong>B. Uang persediaan yang dikelola oleh Bendahara Pengeluaran</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Instansi pemerintah dilarang mengambil pinjaman dari bank komersial.</li><li><strong>C</strong>: Kas daerah tidak berwenang memberikan kredit bagi satuan kerja pusat.</li><li><strong>D</strong>: Valuta asing pasar modal bukan instrumen operasional harian satker.</li><li><strong>E</strong>: Cadangan devisa bank sentral ditujukan untuk stabilitas moneter makro.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 39) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>uang persediaan yang dikelola oleh Bendahara • Pengeluaran</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Sebelum membayarkan uang persediaan, Bendahara Pengeluaran berkewajiban untuk...", "options": ["Menandatangani kontrak pengadaan barang dengan pihak ketiga", "Mengesahkan penambahan pagu belanja pada DIPA kementerian", "Meneliti kelengkapan perintah bayar dan kebenaran perhitungan", "Menetapkan tarif pajak penghasilan atas transaksi rekanan", "Menyusun kerangka acuan kerja pengadaan bersama dinas luar"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengujian oleh Bendahara Pengeluaran</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Bendahara Pengeluaran melaksanakan pembayaran dari uang persediaan setelah <strong>meneliti kelengkapan perintah pembayaran, menguji kebenaran perhitungan tagihan, dan menguji ketersediaan dana</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena bendahara bertindak sebagai juru bayar yang wajib melakukan verifikasi kepatuhan internal terhadap kuitansi dan dokumen pendukung. Pembayaran dari UP dilarang jika berkas perintah bayar belum lengkap.</p><p>Jawaban tepat: <strong>C. Meneliti kelengkapan perintah bayar dan kebenaran perhitungan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penandatanganan kontrak pengadaan merupakan wewenang PPK.</li><li><strong>B</strong>: Penambahan pagu DIPA adalah kewenangan Direktorat Jenderal Anggaran.</li><li><strong>D</strong>: Tarif pajak ditetapkan dalam undang-undang perpajakan, bukan oleh bendahara.</li><li><strong>E</strong>: Kerangka acuan kerja disusun oleh pejabat pembuat komitmen kegiatan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 39) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>meneliti kelengkapan perintah pembayaran yang diterbitkan oleh • Pengguna Anggaran/Kuasa Pengguna Anggaran</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Atas pembayaran yang dilaksanakannya, Bendahara Pengeluaran memikul tanggung jawab...", "options": ["Bebas dari tuntutan ganti rugi apabila ada perintah atasan", "Tanggung jawab administratif terbatas pada jam kerja kantor", "Tanggung jawab yang dialihkan sepenuhnya kepada Kuasa BUN", "Bertanggung jawab secara pribadi atas pembayaran tersebut", "Tanggung jawab renteng bersama rekanan penyedia barang"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tanggung Jawab Pribadi Bendahara Pengeluaran</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Bendahara Pengeluaran bertanggung jawab secara pribadi atas pembayaran yang dilaksanakannya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena undang-undang membebankan tanggung jawab pribadi fungsional kepada bendahara atas setiap rupiah uang negara yang dibayarkannya. Jika terjadi kerugian negara akibat kelalaiannya, bendahara wajib menggantinya dari harta pribadinya.</p><p>Jawaban tepat: <strong>D. Bertanggung jawab secara pribadi atas pembayaran tersebut</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Perintah atasan yang salah tidak melepaskan bendahara dari tuntutan perbendaharaan.</li><li><strong>B</strong>: Tanggung jawab kebendaharaan melekat penuh, tidak terbatas jam kerja kantor.</li><li><strong>C</strong>: Tanggung jawab bendahara tidak dapat dilimpahkan kepada Kuasa BUN.</li><li><strong>E</strong>: Tanggung jawab fungsional jabatan bendahara bersifat mandiri dan personal.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 40) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Bendahara Pengeluaran bertanggung jawab secara pribadi</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Pengecualian dari ketentuan pokok pembayaran perbendaharaan negara diatur melalui...", "options": ["Nota dinas edaran pejabat eselon tiga satuan kerja", "Surat keputusan kepala seksi pencairan dana setempat", "Petunjuk teknis internal unit pengadaan barang jasa", "Kesepakatan bersama antar pimpinan asosiasi profesi", "Ketentuan yang diatur dalam Peraturan Pemerintah"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengecualian Ketentuan Pembayaran Beban Kas</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Pengecualian dari ketentuan ini diatur dalam Peraturan Pemerintah</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena pengecualian terhadap prinsip pembayaran setelah barang diterima (misalnya sewa gedung, langganan daya dan jasa, atau pengadaan luar negeri) hanya sah apabila diatur dengan Peraturan Pemerintah. Ketentuan ini menjaga kepastian hukum pelaksanaan APBN.</p><p>Jawaban tepat: <strong>E. Ketentuan yang diatur dalam Peraturan Pemerintah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Nota dinas internal satker tidak memiliki kekuatan hukum regulasi nasional.</li><li><strong>B</strong>: Kepala seksi KPPN tidak berwenang membuat pengecualian prinsip perbendaharaan.</li><li><strong>C</strong>: Petunjuk teknis pengadaan tidak boleh bertentangan dengan undang-undang.</li><li><strong>D</strong>: Asosiasi profesi tidak memiliki dasar hukum mengatur tata kelola keuangan negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 40) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pengecualian dari ketentuan ini diatur dalam Peraturan Pemerintah</strong></div></div>"}];
let current=0;const answers=new Array(questions.length).fill(null);const letters=["A","B","C","D","E"];let seconds=65*60;let submitted=false;


// Anotasi hanya dalam pembahasan. Satu set coretan untuk setiap nomor soal.
const ANNO_KEY='stangolden_pkn_p03_review_annot_v1_user_<?= (int)$USER->id ?>';
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
 try{localStorage.setItem('stg_pkn_p03_map_visible',show?'1':'0')}catch(e){}
}
try{if(localStorage.getItem('stg_pkn_p03_map_visible')==='0')toggleQuestionMap(false)}catch(e){}
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

  if(modalTitle) modalTitle.textContent="🏆 Lihat Ranking Pengelolaan Keuangan Negara Paket 03";
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
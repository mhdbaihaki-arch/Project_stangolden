<?php
/**
 * STANGOLDEN.exam • Pengelolaan Keuangan Negara Paket 05 (Halaman 55–64)
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
// $DB->get_record("stgpkn_p05_rank") => tabel fisik "mdlhv_stgpkn_p05_rank".
// Jangan menambahkan prefix secara manual di konstanta berikut.
// Penyimpanan bersama melalui database Moodle. Nama tabel pendek, kompatibel dengan batas Moodle.
const STG_LB_T = 'stgpkn_p05_rank';
const STG_REVIEW_T = 'stgpkn_p05_rev';
const STG_ANNO_T = 'stgpkn_p05_anno';
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
            error_log('STANGOLDEN PKN P05 HLM55-64 SETUP: ' . $e->getMessage());
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
    }catch(Throwable $e){error_log('STANGOLDEN PKN P05 HLM55-64: '.$e->getMessage());stgtak_json(['success'=>false,'message'=>($e instanceof RuntimeException ? $e->getMessage() : 'Penyimpanan belum tersedia. Hubungi administrator.')],500);}
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
<title>Latihan Pengelolaan Keuangan Negara Paket 05 (Hlm. 55–64) • STANGOLDEN</title>
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
      <span class="series-tag">Paket 05 • Hlm. 55–64</span>
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
<h1>Latihan Nilai-Nilai Kemenkeu <span class="series-tag">Paket 05 • Hlm. 55–64</span></h1>
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
        <h2 class="leaderboard-title" id="rankingModalTitle">🏆 Lihat Ranking Pengelolaan Keuangan Negara Paket 05</h2>
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
const STG_LOCAL_KEY="stg_pkn_p05_review_user_<?= (int)$USER->id ?>";
const HAS_FULL_REVIEW=true;
const IS_SITE_ADMIN=<?= is_siteadmin($USER) ? "true" : "false" ?>;
const LEADERBOARD_ENDPOINT=window.location.href.split("?")[0].split("#")[0];
const questions=[{"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Anggaran Belanja Pemerintah Pusat menurut klasifikasi jenis belanja terdiri atas...", "options": ["Belanja pegawai, barang, modal, bunga, subsidi, hibah, bansos, dan lain-lain", "Belanja rutin operasional, belanja pembangunan, dan belanja tak terduga daerah", "Dana bagi hasil, dana alokasi umum, dan dana alokasi khusus daerah", "Belanja dividen BUMN, penerbitan obligasi, dan pelunasan pinjaman luar negeri", "Penerimaan perpajakan, pendapatan bukan pajak, dan bantuan pinjaman lunak"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Delapan Jenis Belanja Pemerintah Pusat</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Anggaran Belanja Pemerintah Pusat terdiri atas <strong>belanja pegawai, belanja barang, belanja modal, pembayaran bunga utang, subsidi, belanja hibah, bantuan sosial dan belanja lain-lain</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena delapan klasifikasi ekonomi belanja ini merupakan standar baku postur pengeluaran pemerintah pusat dalam APBN. Setiap jenis belanja memiliki peruntukan spesifik sesuai tugas fungsi kementerian negara.</p><p>Jawaban tepat: <strong>A. Belanja pegawai, barang, modal, bunga, subsidi, hibah, bansos, dan lain-lain</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Pemisahan belanja rutin dan pembangunan merupakan format lama sebelum tahun 2005.</li><li><strong>C</strong>: DBH, DAU, dan DAK merupakan komponen belanja transfer ke daerah (TKD).</li><li><strong>D</strong>: Penerbitan obligasi dan pinjaman adalah komponen pembiayaan anggaran negara.</li><li><strong>E</strong>: Penerimaan pajak dan PNBP adalah komponen pendapatan negara, bukan belanja.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 55) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>belanja pegawai, belanja barang, • belanja modal, pembayaran bunga utang, subsidi, belanja hibah, bantuan sosial dan • belanja lain-lain</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Anggaran Belanja Daerah dalam kerangka pengeluaran negara terdiri atas...", "options": ["Pajak daerah, retribusi daerah, dan pendapatan asli daerah", "Dana Perimbangan, Dana Otonomi Khusus, dan Dana Penyeimbang", "Pinjaman obligasi daerah dan dana talangan bank pembangunan", "Belanja pegawai daerah dan belanja modal kantor dinas", "Sisa lebih perhitungan anggaran dan dana cadangan daerah"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Komponen Belanja Daerah APBN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Anggaran Belanja Daerah terdiri atas <strong>Dana Perimbangan dan Dana Otonomi Khusus dan Penyeimbang</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena pos belanja daerah dalam APBN mengalokasikan transfer dana dari pusat ke daerah guna mendukung pelaksanaan desentralisasi fiskal dan otonomi daerah di seluruh pelosok Indonesia.</p><p>Jawaban tepat: <strong>B. Dana Perimbangan, Dana Otonomi Khusus, dan Dana Penyeimbang</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pajak dan retribusi daerah adalah komponen pendapatan APBD, bukan transfer APBN.</li><li><strong>C</strong>: Obligasi daerah dan pinjaman bank adalah instrumen pembiayaan APBD daerah.</li><li><strong>D</strong>: Belanja pegawai dan dinas daerah adalah belanja operasional dalam APBD.</li><li><strong>E</strong>: SiLPA daerah adalah saldo sisa anggaran dalam neraca penutupan kas pemda.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 55) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Dana Perimbangan dan Dana • Otonomi Khusus dan Penyeimbang</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Pelaksanaan seluruh belanja negara pada dasarnya wajib berlandaskan pada...", "options": ["Prinsip penyerapan anggaran secara merata tanpa tolok ukur hasil", "Prinsip menghabiskan pagu dana sebelum tahun anggaran berakhir", "Prinsip optimalisasi pemanfaatan dana untuk mencapai sasaran", "Prinsip pembagian pagu secara proporsional kepada semua rekanan", "Prinsip penundaan pembayaran tagihan hingga batas jatuh tempo"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Prinsip Pelaksanaan Belanja Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Belanja negara dilakukan dengan berlandaskan pada <strong>prinsip optimalisasi pemanfaatan dana untuk mencapai sasaran-sasaran yang telah ditetapkan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena setiap alokasi anggaran belanja publik harus berorientasi pada pencapaian output dan outcome yang nyata (*value for money*). Prinsip ini menuntut efisiensi, efektivitas, dan ekonomis dalam belanja.</p><p>Jawaban tepat: <strong>C. Prinsip optimalisasi pemanfaatan dana untuk mencapai sasaran</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penyerapan tanpa tolok ukur bertentangan dengan prinsip penganggaran kinerja.</li><li><strong>B</strong>: Menghabiskan pagu terburu-buru adalah praktik pemborosan yang dilarang.</li><li><strong>D</strong>: Penganggaran didasarkan pada prioritas program, bukan bagi-bagi ke rekanan.</li><li><strong>E</strong>: Menunda tagihan yang sah melanggar tata tertib perbendaharaan negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 55) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>prinsip optimalisasi pemanfaatan dana untuk • mencapai sasaran-sasaran yang telah ditetapkan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Kondisi defisit anggaran dalam keuangan negara mengandung arti bahwa...", "options": ["Seluruh kewajiban pembayaran cicilan utang telah jatuh tempo", "Jumlah penerimaan perpajakan melebihi seluruh belanja rutin", "Posisi kas umum negara mengalami kekosongan dana simpanan", "Anggaran belanja pemerintah lebih besar daripada pendapatannya", "Pemerintah pusat tidak mampu menyalurkan dana transfer daerah"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Arti Defisit Anggaran</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Defisit anggaran mengandung arti bahwa <strong>anggaran belanja pemerintah lebih besar jika dibandingkan dengan anggaran pendapatannya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena postur anggaran dirancang defisit ketika kebutuhan belanja negara untuk stimulus ekonomi sengaja dipatok melampaui kemampuan pendapatan tahun berjalan. Selisih inilah yang ditutup pembiayaan.</p><p>Jawaban tepat: <strong>D. Anggaran belanja pemerintah lebih besar daripada pendapatannya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Jatuh tempo utang adalah jadwal amortisasi kewajiban perbendaharaan.</li><li><strong>B</strong>: Penerimaan melebihi belanja merupakan kondisi surplus anggaran negara.</li><li><strong>C</strong>: Defisit anggaran bukan berarti kas kosong melainkan postur belanja melebihi pendapatan.</li><li><strong>E</strong>: Transfer ke daerah tetap disalurkan sesuai alokasi undang-undang APBN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 55) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>anggaran belanja pemerintah lebih besar • jika dibandingkan dengan anggaran pendapatannya</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Perluasan belanja negara dalam postur defisit dimaksudkan karena belanja pemerintah memiliki...", "options": ["Fasilitas pembebasan pajak bagi seluruh aparatur pemerintah", "Kewenangan mutlak dalam menetapkan harga komoditas pasar", "Kemampuan menghapus seluruh kewajiban utang luar negeri", "Hak monopoli dalam pengelolaan seluruh sektor industri privat", "Efek ganda dalam menumbuhkan ekonomi dan lapangan kerja"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Efek Ganda Belanja Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Belanja negara diperbesar karena <strong>belanja pemerintah mempunyai efek ganda dalam menumbuhkan kegiatan ekonomi yang sekaligus meningkatkan lapangan kerja</strong> (*multiplier effect*).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena pengeluaran pemerintah (infrastruktur, bansos, belanja modal) menggerakkan sektor riil, menciptakan permintaan agregat, dan membuka kesempatan kerja baru bagi masyarakat.</p><p>Jawaban tepat: <strong>E. Efek ganda dalam menumbuhkan ekonomi dan lapangan kerja</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Aparatur pemerintah tetap dikenakan pemotongan pajak penghasilan resmi.</li><li><strong>B</strong>: Harga pasar komoditas ditentukan oleh mekanisme pasar dan daya beli.</li><li><strong>C</strong>: Belanja negara tidak otomatis menghapuskan kewajiban pokok utang.</li><li><strong>D</strong>: Negara tidak memonopoli industri privat yang berdaya saing bebas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 55) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>efek ganda dalam menumbuhkan • kegiatan eknomi yang sekaligus meningkatkan lapangan kerja</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Kebutuhan pendanaan atas defisit anggaran belanja negara dipenuhi dari...", "options": ["Berbagai sumber pembiayaan anggaran termasuk utang", "Penarikan paksa tabungan giro simpanan nasabah perbankan", "Pencetakan uang rupiah tanpa batas oleh kementerian keuangan", "Pemotongan dana bagi hasil daerah secara sewenang-wenang", "Penyitaan dividen keuntungan perusahaan swasta nasional"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pendanaan Defisit Anggaran</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Defisit anggaran belanja negara <strong>perlu didanai dari berbagai sumber pembiayaan, termasuk utang</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena defisit APBN ditutup melalui pos pembiayaan yang sah (Surat Berharga Negara, pinjaman program/proyek, dan pemanfaatan SiLPA) yang disetujui bersama DPR.</p><p>Jawaban tepat: <strong>A. Berbagai sumber pembiayaan anggaran termasuk utang</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Penarikan tabungan nasabah perbankan melanggar hak milik konstitusional.</li><li><strong>C</strong>: Kemenkeu tidak mencetak uang; uang dicetak BI sesuai kebutuhan moneter.</li><li><strong>D</strong>: Pemotongan DBH melanggar perimbangan keuangan pusat dan daerah.</li><li><strong>E</strong>: Penyitaan dividen swasta melanggar asas perlindungan kepemilikan modal.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 55) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>didanai dari berbagai sumber pembiayaan, termasuk utang</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Belanja negara pada hakikatnya didefinisikan sebagai pengeluaran negara untuk...", "options": ["Menjamin keuntungan komersial bagi badan usaha milik swasta", "Membiayai tugas-tugas umum pemerintahan dan pembangunan", "Membeli surat berharga komersial di bursa saham luar negeri", "Menambah porsi kepemilikan saham pada korporasi perbankan", "Membayar kompensasi kerugian spekulasi pasar uang perbankan"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Belanja Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Belanja negara adalah pengeluaran negara untuk membiayai tugas-tugas umum pemerintahan dan pembangunan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena uang rakyat yang dikelola dalam belanja APBN ditujukan untuk membiayai roda birokrasi pelayanan publik serta penyediaan fasilitas pembangunan infrastruktur bagi kemakmuran rakyat.</p><p>Jawaban tepat: <strong>B. Membiayai tugas-tugas umum pemerintahan dan pembangunan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Belanja negara ditujukan untuk kemakmuran publik, bukan profit swasta.</li><li><strong>C</strong>: Membeli saham spekulatif asing dilarang dalam pengelolaan keuangan publik.</li><li><strong>D</strong>: Penyertaan modal perbankan adalah pos investasi pembiayaan tersendiri.</li><li><strong>E</strong>: Spekulasi perbankan swasta bukan tanggung jawab pengeluaran negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 55) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Belanja negara adalah pengeluaran negara • untuk</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Pembiayaan tugas-tugas umum pemerintahan melalui belanja negara mencakup...", "options": ["Penyelenggaraan ekspedisi perdagangan spekulatif luar negeri", "Pemberian dana pinjaman bebas bunga bagi pengusaha besar", "Pelayanan publik, penegakan hukum, dan pertahanan keamanan", "Pemberian subsidi dividen pemegang saham korporasi terbuka", "Talangan pelunasan utang kredit macet perseroan swasta"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tugas Umum Pemerintahan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pengeluaran negara ditujukan untuk <strong>membiayai tugas-tugas umum pemerintahan</strong> seperti pelayanan administrasi publik, keamanan, keadilan, dan ketertiban umum.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena fungsi pokok keberadaan negara (*public goods provision*) diwujudkan melalui belanja tugas umum pemerintahan yang dirasakan manfaatnya langsung oleh seluruh lapisan masyarakat.</p><p>Jawaban tepat: <strong>C. Pelayanan publik, penegakan hukum, dan pertahanan keamanan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Ekspedisi perdagangan spekulatif bukan tugas umum pemerintahan.</li><li><strong>B</strong>: Pinjaman bebas bunga bagi korporasi besar menyalahi peruntukan belanja publik.</li><li><strong>D</strong>: Subsidi dividen saham swasta bertentangan dengan prinsip keadilan fiskal.</li><li><strong>E</strong>: Talangan utang macet swasta melanggar undang-undang perbendaharaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 55) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>membiayai • tugas-tugas • umum</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Struktur Belanja Negara dalam postur APBN terbagi ke dalam dua pilar utama yaitu...", "options": ["Penerimaan dividen kekayaan negara dan penarikan utang", "Belanja rutin operasional dinas dan belanja proyek fisik", "Penerimaan pajak dalam negeri dan pajak perdagangan luar", "Anggaran Belanja Pemerintah Pusat dan Transfer ke Daerah", "Dana cadangan umum kasir dan rekening dana investasi"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dua Pilar Belanja Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Belanja Negara terdiri atas <strong>Anggaran Belanja Pemerintah Pusat dan Transfer ke Daerah</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena belanja negara dieksekusi melalui kementerian/lembaga di tingkat pusat serta disalurkan kepada pemerintah daerah (TKD) untuk mendanai urusan desentralisasi di daerah.</p><p>Jawaban tepat: <strong>D. Anggaran Belanja Pemerintah Pusat dan Transfer ke Daerah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Dividen dan utang adalah pos penerimaan PNBP dan pembiayaan.</li><li><strong>B</strong>: Pemisahan rutin dan proyek fisik sudah ditiadakan dalam unifikasi APBN.</li><li><strong>C</strong>: Pajak dalam negeri dan perdagangan internasional adalah pos pendapatan.</li><li><strong>E</strong>: Cadangan kasir dan RDI adalah penatausahaan likuiditas kas negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 55) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Anggaran Belanja Pemerintah Pusat dan • Transfer ke Daerah</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Kelompok Dana Perimbangan dalam belanja transfer daerah terdiri atas...", "options": ["Dana desa, dana kelurahan, dan bantuan sosial rukun warga", "Dana otonomi khusus, dana keistimewaan, dan dana penyeimbang", "Retribusi daerah, pajak daerah, dan pendapatan asli daerah", "Pinjaman obligasi daerah, hibah daerah, dan kredit perbankan", "Dana Bagi Hasil, Dana Alokasi Umum, dan Dana Alokasi Khusus"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tiga Komponen Dana Perimbangan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dana Perimbangan terdiri atas <strong>Dana Bagi Hasil, Dana Alokasi Umum dan Dana Alokasi Khusus</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena ketiga dana ini merupakan pilar transfer antar-pemerintah (*intergovernmental transfers*) yang dirancang untuk mengatasi ketimpangan vertikal dan horizontal keuangan daerah.</p><p>Jawaban tepat: <strong>E. Dana Bagi Hasil, Dana Alokasi Umum, dan Dana Alokasi Khusus</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Dana desa dialokasikan tersendiri dalam pos transfer ke daerah.</li><li><strong>B</strong>: Dana otsus dan keistimewaan dialokasikan di luar dana perimbangan umum.</li><li><strong>C</strong>: Retribusi dan pajak daerah adalah penerimaan asli dalam APBD pemda.</li><li><strong>D</strong>: Obligasi dan pinjaman adalah instrumen pembiayaan utang daerah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 55) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Dana Bagi Hasil, • Dana Alokasi Umum dan Dana Alokasi Khusus</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Pembaruan mendasar dalam format penganggaran belanja pemerintah pusat adalah...", "options": ["Tidak dikenal lagi pemisahan belanja rutin dan pembangunan", "Pemisahan ketat rekening kas belanja pegawai dan barang", "Penghapusan seluruh belanja modal proyek infrastruktur fisik", "Pembatasan pengeluaran belanja bantuan sosial masyarakat", "Sentralisasi seluruh kewenangan pencairan dana pada menteri"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Peniadaan Dikotomi Belanja Rutin dan Pembangunan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam format baru APBN, di sisi Belanja Pemerintah Pusat <strong>tidak dikenal lagi pemisahan antara belanja rutin dengan belanja pembangunan</strong> (Unified Budget).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena penggabungan anggaran rutin dan pembangunan ke dalam satu anggaran terpadu menghapus sistem penganggaran ganda (*dual budgeting*) yang selama bertahun-tahun menimbulkan inefisiensi pengeluaran.</p><p>Jawaban tepat: <strong>A. Tidak dikenal lagi pemisahan belanja rutin dan pembangunan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Rekening kasir disatukan dalam Treasury Single Account (TSA).</li><li><strong>C</strong>: Belanja modal tetap ada dan menjadi prioritas penyediaan sarpras fisik.</li><li><strong>D</strong>: Bansos dialokasikan sesuai kebutuhan perlindungan sosial masyarakat.</li><li><strong>E</strong>: Kewenangan pencairan didelegasikan kepada KPA dan PPK satuan kerja.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 56) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>tidak dikenal lagi • pemisahan antara belanja rutin dengan belanja pembangunan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Pemisahan belanja rutin dan belanja pembangunan pada masa lalu dihilangkan karena...", "options": ["Menghambat penarikan dana pinjaman luar negeri pemerintah", "Menimbulkan peluang duplikasi, penumpukan, dan penyimpangan", "Mempersulit pemeriksaan kuitansi fisik oleh aparat pemeriksa", "Menurunkan jumlah penerimaan perpajakan kas umum negara", "Membatasi penambahan jumlah formasi aparatur sipil negara"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kelemahan Sistem Dual Budgeting Terdahulu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pemisahan belanja rutin dan pembangunan dihilangkan karena dalam pelaksanaannya <strong>menimbulkan peluang terjadinya duplikasi, penumpukan dan penyimpangan anggaran</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena kegiatan operasional sering kali disamarkan sebagai proyek pembangunan non-fisik sehingga satu kegiatan didanai ganda dari pos rutin sekaligus pos pembangunan.</p><p>Jawaban tepat: <strong>B. Menimbulkan peluang duplikasi, penumpukan, dan penyimpangan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penarikan pinjaman luar negeri mengikuti tata cara perjanjian pinjaman.</li><li><strong>C</strong>: Pemeriksaan kuitansi tetap dilakukan sesuai standar audit kepatuhan.</li><li><strong>D</strong>: Format belanja tidak memengaruhi penerimaan perpajakan kas negara.</li><li><strong>E</strong>: Formasi ASN ditetapkan berdasarkan analisis jabatan dan beban kerja.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 56) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menimbulkan peluang terjadinya duplikasi, penumpukan • dan penyimpangan anggaran</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Belanja Pegawai pada hakikatnya didefinisikan sebagai pengeluaran untuk...", "options": ["Penyediaan sarana gedung perkantoran dan fasilitas komputer", "Pembelian perlengkapan alat tulis kantor habis pakai dinas", "Kompensasi uang atau barang yang diberikan kepada aparatur", "Pembayaran ganti rugi pembebasan tanah proyek jalan raya", "Pemberian dana bantuan pendidikan bagi masyarakat kurang mampu"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Belanja Pegawai</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Belanja pegawai, merupakan kompensasi, baik berupa uang ataupun barang yang diberikan kepada aparatur negara</strong>, yang bertugas di dalam maupun luar negeri sebagai imbalan kerja.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena belanja pegawai menampung hak finansial aparatur sipil negara, prajurit TNI, anggota Polri, dan pejabat negara atas pelaksanaan tugas kedinasannya berupa gaji, tunjangan, dan honorarium.</p><p>Jawaban tepat: <strong>C. Kompensasi uang atau barang yang diberikan kepada aparatur</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Gedung dan komputer dikelompokkan ke dalam pos belanja modal.</li><li><strong>B</strong>: Alat tulis kantor habis pakai tergolong dalam pos belanja barang.</li><li><strong>D</strong>: Ganti rugi tanah masuk dalam komponen belanja modal pengadaan tanah.</li><li><strong>E</strong>: Bantuan pendidikan masyarakat tergolong pos bantuan sosial.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 56) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>kompensasi, baik berupa uang ataupun barang • yang diberikan kepada aparatur negara</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Imbalan kerja aparatur dimasukkan dalam belanja pegawai dengan pengecualian untuk...", "options": ["Pelaksanaan tugas perbantuan pada badan usaha milik negara", "Penugasan dinas ke luar wilayah yurisdiksi Republik Indonesia", "Pekerjaan lembur yang dilakukan pada hari libur kedinasan", "Pekerjaan yang berkaitan langsung dengan pembentukan modal", "Pemberian penghargaan tanda kehormatan masa pengabdian kerja"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengecualian dalam Belanja Pegawai</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Belanja pegawai adalah imbalan atas pekerjaan yang telah dilaksanakan <strong>kecuali pekerjaan yang berkaitan dengan pembentukan modal</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena upah tenaga kerja yang terlibat langsung dalam pembangunan fisik aset tetap (misalnya upah tukang swakelola gedung) dikapitalisasi ke dalam nilai perolehan aset pada belanja modal.</p><p>Jawaban tepat: <strong>D. Pekerjaan yang berkaitan langsung dengan pembentukan modal</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Tugas perbantuan tetap diatur dalam status hak kepegawaian dinas.</li><li><strong>B</strong>: Aparatur di luar negeri tetap menerima gaji dari belanja pegawai.</li><li><strong>C</strong>: Uang lembur hari libur termasuk dalam rincian belanja pegawai.</li><li><strong>E</strong>: Tanda kehormatan adalah bentuk penghargaan moral kepegawaian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 56) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>kecuali pekerjaan yang berkaitan dengan pembentukan modal</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Pengeluaran negara yang dialokasikan untuk pembelian barang dan jasa habis pakai disebut...", "options": ["Belanja lain-lain untuk kebutuhan tanggap darurat bencana", "Belanja modal untuk menambah nilai aset tetap kementerian", "Belanja subsidi untuk menutup selisih harga komoditas pasar", "Belanja bantuan sosial untuk meringankan beban masyarakat", "Belanja barang untuk memproduksi barang dan jasa pemerintah"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Belanja Barang</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Belanja barang, merupakan belanja negara yang digunakan untuk pembelian barang dan jasa yang habis pakai untuk memproduksi barang dan jasa yang dipasarkan maupun yang tidak dipasarkan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena belanja barang menampung pengeluaran operasional sehari-hari yang masa manfaatnya kurang dari satu tahun anggaran, seperti daya listrik, telekomunikasi, ATK, dan jasa pemeliharaan kantor.</p><p>Jawaban tepat: <strong>E. Belanja barang untuk memproduksi barang dan jasa pemerintah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Belanja lain-lain dicadangkan untuk keperluan darurat tak terduga.</li><li><strong>B</strong>: Belanja modal menghasilkan aset tetap bermasa manfaat lebih dari setahun.</li><li><strong>C</strong>: Belanja subsidi ditujukan untuk menstabilkan harga barang kebutuhan.</li><li><strong>D</strong>: Bansos disalurkan langsung kepada masyarakat penerima manfaat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 56) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pembelian • barang dan jasa yang habis pakai untuk memproduksi barang dan jasa</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Klasifikasi belanja barang dalam penganggaran negara dirinci ke dalam pos...", "options": ["Belanja barang, belanja jasa, belanja pemeliharaan, dan belanja perjalanan", "Gaji pokok, tunjangan keluarga, tunjangan jabatan, dan uang makan", "Pengadaan tanah, peralatan mesin, gedung bangunan, dan jaringan irigasi", "Subsidi bahan bakar, subsidi listrik, subsidi pupuk, dan benih padi", "Bantuan pangan, bantuan beasiswa, bantuan hukum, dan dana santunan"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Empat Rincian Belanja Barang</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Belanja barang dirinci dalam <strong>belanja barang, belanja jasa, belanja pemeliharaan, dan belanja perjalanan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena keempat sub-pos ini merangkum seluruh spektrum pengeluaran operasional non-pegawai dan non-aset di lingkungan satuan kerja pemerintah.</p><p>Jawaban tepat: <strong>A. Belanja barang, belanja jasa, belanja pemeliharaan, dan belanja perjalanan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Gaji dan tunjangan merupakan rincian pos belanja pegawai.</li><li><strong>C</strong>: Tanah, mesin, dan gedung merupakan rincian pos belanja modal.</li><li><strong>D</strong>: Subsidi BBM dan pupuk merupakan rincian pos belanja subsidi.</li><li><strong>E</strong>: Bantuan pangan dan beasiswa tergolong pos bantuan sosial.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 56) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>belanja barang, belanja jasa, belanja pemeliharaan, dan belanja perjalanan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Karakteristik utama belanja modal yang membedakannya dari belanja barang adalah...", "options": ["Seluruh barangnya habis dipakai dalam kurun waktu satu bulan", "Manfaat fisik asetnya dapat dinikmati lebih dari satu tahun", "Diberikan secara tunai langsung kepada masyarakat kurang mampu", "Dikhususkan guna membiayai perjalanan dinas luar daerah", "Tidak menambah nilai kekayaan aset neraca pemerintah pusat"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Karakteristik Utama Belanja Modal</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Belanja modal adalah belanja yang diarahkan untuk penyediaan sarana prasarana fisik yang <strong>manfaatnya dapat dinikmati lebih dari satu tahun anggaran</strong> dan menambah aset tetap.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena pengeluaran belanja modal dikapitalisasi ke dalam neraca pemerintah sebagai aset tetap karena memiliki masa manfaat ekonomis jangka panjang melebihi satu tahun buku.</p><p>Jawaban tepat: <strong>B. Manfaat fisik asetnya dapat dinikmati lebih dari satu tahun</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Barang habis pakai bulanan merupakan karakteristik belanja barang.</li><li><strong>C</strong>: Bantuan tunai kepada masyarakat merupakan karakteristik bantuan sosial.</li><li><strong>D</strong>: Perjalanan dinas dibiayai melalui pos belanja barang perjalanan.</li><li><strong>E</strong>: Belanja modal secara nyata menambah nilai aset tetap pada neraca negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 56) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>manfaatnya dapat dinikmati • lebih dari satu tahun anggaran</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Investasi pemerintah melalui belanja modal diwujudkan dalam penyediaan sarana...", "options": ["Honorarium narasumber dan uang saku peserta bimbingan teknis", "Kertas kantor, tinta printer, amplop dinas, dan map arsip", "Tanah, peralatan mesin, gedung bangunan, jaringan, dan modal fisik", "Bahan bakar minyak dan pelumas kendaraan dinas operasional", "Tiket pesawat udara dan biaya penginapan hotel perjalanan dinas"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Bentuk Sarana Prasarana Belanja Modal</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Belanja modal dipergunakan untuk penyediaan sarana prasarana pembangunan dalam bentuk <strong>tanah, peralatan dan mesin, gedung dan bangunan, jaringan, serta belanja modal fisik lainnya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena kelima kelompok aset tersebut merupakan barang milik negara berwujud fisik yang membentuk infrastruktur penunjang pelayanan publik dan pertumbuhan ekonomi nasional.</p><p>Jawaban tepat: <strong>C. Tanah, peralatan mesin, gedung bangunan, jaringan, dan modal fisik</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Honorarium dan uang saku bimbingan teknis masuk pos belanja barang jasa.</li><li><strong>B</strong>: Kertas, tinta, dan map adalah barang persediaan habis pakai (belanja barang).</li><li><strong>D</strong>: BBM dan pelumas kendaraan dinas adalah pos belanja barang habis pakai.</li><li><strong>E</strong>: Tiket pesawat dan hotel dibiayai melalui belanja barang perjalanan dinas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 56) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>tanah, peralatan dan mesin, gedung dan • bangunan, jaringan, serta belanja modal fisik lainnya</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Pengeluaran belanja pemerintah pusat untuk memenuhi kewajiban atas pinjaman disebut...", "options": ["Penyertaan modal negara pada perseroan terbatas perbankan", "Belanja subsidi harga komoditas pangan pokok masyarakat", "Belanja hibah kepada lembaga non-pemerintah internasional", "Pembayaran bunga utang atas pinjaman dalam dan luar negeri", "Pelunasan cicilan pokok pinjaman program luar negeri netto"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pembayaran Bunga Utang</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Pembayaran bunga utang, merupakan belanja pemerintah pusat untuk memenuhi sebagian kewajiban dalam negeri maupun kewajiban luar negeri</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena imbal hasil bunga atas Surat Berharga Negara (SUN/SBSN) dan bunga pinjaman luar negeri dicatat dalam pos belanja negara. Pembayaran bunga wajib dianggarkan untuk menjaga kredibilitas surat utang negara.</p><p>Jawaban tepat: <strong>D. Pembayaran bunga utang atas pinjaman dalam dan luar negeri</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penyertaan modal negara adalah pos pengeluaran investasi pembiayaan.</li><li><strong>B</strong>: Subsidi pangan adalah belanja penstabilan harga, bukan beban bunga pinjaman.</li><li><strong>C</strong>: Belanja hibah adalah transfer sukarela kepada pihak luar negeri.</li><li><strong>E</strong>: Pelunasan cicilan pokok (amortisasi) masuk dalam pos pembiayaan anggaran.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 56) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pembayaran bunga utang, merupakan belanja pemerintah pusat untuk • memenuhi sebagian kewajiban</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Secara strategis, alokasi anggaran belanja modal diarahkan pemerintah untuk...", "options": ["Membeli surat saham perusahaan swasta yang mengalami gagal bayar", "Menaikkan tunjangan operasional pejabat pimpinan satuan kerja", "Menambah porsi konsumsi barang impor di kalangan aparatur sipil", "Menutup kekurangan saldo kas operasional bendahara pengeluaran", "Mempercepat penyediaan sarana dan prasarana fisik pembangunan"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuan Strategis Belanja Modal</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Belanja modal diarahkan untuk <strong>mempercepat penyediaan sarana dan prasarana fisik</strong> pembangunan nasional.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena ketersediaan infrastruktur (jalan, jembatan, pelabuhan, bendungan) memangkas biaya logistik nasional dan mendongkrak daya saing ekonomi antarwilayah secara berkelanjutan.</p><p>Jawaban tepat: <strong>E. Mempercepat penyediaan sarana dan prasarana fisik pembangunan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pembelian saham gagal bayar swasta dilarang dalam pengelolaan APBN.</li><li><strong>B</strong>: Tunjangan operasional dibiayai melalui pos belanja pegawai.</li><li><strong>C</strong>: Pengadaan pemerintah justru memprioritaskan produk dalam negeri (P3DN).</li><li><strong>D</strong>: Kekurangan kas operasional dikelola melalui manajemen likuiditas KPPN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 56) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>mempercepat • penyediaan sarana dan prasarana fisik</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Belanja subsidi dialokasikan pemerintah pusat dengan tujuan untuk...", "options": ["Menjaga stabilitas harga dan membantu masyarakat kurang mampu", "Membiayai pembangunan gedung perkantoran dinas kementerian", "Membayar kompensasi uang pesangon pensiun aparatur sipil", "Menambah laba dividen bersih pemegang saham korporasi terbuka", "Mengganti kerugian transaksi perdagangan valas bank swasta"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuan Pengalokasian Subsidi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Subsidi, adalah belanja pemerintah pusat sebagai upaya pemerintah untuk menjaga stabilitas harga, membantu masyarakat kurang mampu, dan usaha kecil dan menengah dalam memenuhi sebagian kebutuhannya, serta membantu BUMN yang melaksanakan tugas pelayanan umum</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena subsidi energi dan non-energi menurunkan harga jual barang esensial (BBM, tarif listrik, pupuk) agar daya beli masyarakat berpenghasilan rendah tetap terlindungi dari lonjakan inflasi.</p><p>Jawaban tepat: <strong>A. Menjaga stabilitas harga dan membantu masyarakat kurang mampu</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Pembangunan gedung perkantoran dibiayai dari belanja modal.</li><li><strong>C</strong>: Uang pensiun aparatur negara dialokasikan dalam pos belanja pegawai.</li><li><strong>D</strong>: Subsidi ditujukan untuk kepentingan rakyat, bukan laba pemegang saham.</li><li><strong>E</strong>: Kerugian transaksi valas swasta bukan beban anggaran belanja negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 57) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Subsidi, adalah belanja pemerintah pusat sebagai upaya pemerintah untuk • menjaga stabilitas harga, membantu masyarakat kurang mampu</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Pengeluaran negara berupa transfer yang sifatnya tidak wajib ke luar negeri adalah...", "options": ["Pembayaran cicilan pokok pinjaman luar negeri pemerintah", "Belanja hibah kepada negara lain atau organisasi internasional", "Setoran modal pemerintah pada lembaga keuangan multilateral", "Pembayaran premi asuransi aset gedung kedutaan besar luar", "Belanja barang perjalanan dinas perwakilan diplomatik pusat"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Belanja Hibah</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Belanja hibah, merupakan transfer yang sifatnya tidak wajib kepada negara lain atau kepada organisasi internasional</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena sebagai wujud solidaritas antarbangsa, pemerintah Indonesia dapat menyalurkan bantuan hibah luar negeri (misalnya bantuan kemanusiaan bencana alam di negara sahabat) melalui pos belanja hibah.</p><p>Jawaban tepat: <strong>B. Belanja hibah kepada negara lain atau organisasi internasional</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Cicilan pokok utang merupakan kewajiban kontraktual di pos pembiayaan.</li><li><strong>C</strong>: Penyertaan modal lembaga multilateral masuk pos investasi pembiayaan.</li><li><strong>D</strong>: Premi asuransi gedung kedutaan tergolong dalam pos belanja barang jasa.</li><li><strong>E</strong>: Perjalanan dinas diplomatik dibiayai melalui pos belanja barang perjalanan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 57) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Belanja hibah, merupakan transfer yang sifatnya tidak wajib kepada negara • lain atau kepada organisasi internasional</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Pengeluaran berupa transfer uang atau barang kepada masyarakat melalui K/L disebut...", "options": ["Belanja barang untuk menunjang operasional dinas kantor", "Belanja pegawai untuk meningkatkan kinerja aparatur", "Bantuan sosial untuk melindungi dari risiko sosial", "Belanja modal untuk menambah kepemilikan aset kementerian", "Belanja bunga untuk melunasi kupon surat utang negara"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Bantuan Sosial</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Bantuan sosial, merupakan transfer uang atau barang yang diberikan kepada masyarakat melalui kementerian negara/lembaga guna melindungi dari kemungkinan terjadinya resiko sosial</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena bansos disalurkan secara terarah (seperti Program Keluarga Harapan, sembako, KIP) guna memberikan perlindungan sosial dasar bagi warga miskin dan rentan dari kerentanan ekonomi.</p><p>Jawaban tepat: <strong>C. Bantuan sosial untuk melindungi dari risiko sosial</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Belanja barang digunakan untuk operasional instansi pemerintah.</li><li><strong>B</strong>: Belanja pegawai diberikan kepada aparatur negara, bukan masyarakat umum.</li><li><strong>D</strong>: Belanja modal menghasilkan aset tetap milik negara, bukan untuk warga.</li><li><strong>E</strong>: Belanja bunga adalah pelunasan kupon utang kepada pemegang obligasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 57) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Bantuan sosial, merupakan transfer uang atau barang yang diberikan kepada • masyarakat</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Penyaluran bantuan sosial kepada masyarakat secara terencana ditujukan guna...", "options": ["Menaikkan batas tarif bea keluar hasil perkebunan kelapa sawit", "Membiayai kegiatan kampanye politik pemilihan kepala daerah", "Menambah porsi kepemilikan modal badan usaha swasta rekanan", "Melindungi masyarakat dari kemungkinan terjadinya risiko sosial", "Menutup selisih kerugian perdagangan komoditas pasar modal"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuan Penyaluran Bantuan Sosial</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Bantuan sosial diberikan <strong>guna melindungi dari kemungkinan terjadinya resiko sosial</strong>, seperti kemiskinan ekstrem, musibah bencana alam, keterbelakangan pendidikan, dan kerentanan kesehatan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena kehadiran bansos berfungsi sebagai jaring pengaman sosial (*social safety net*) agar masyarakat rentan tidak jatuh ke dalam jurang kemiskinan yang lebih dalam saat terjadi gejolak ekonomi.</p><p>Jawaban tepat: <strong>D. Melindungi masyarakat dari kemungkinan terjadinya risiko sosial</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Bea keluar sawit adalah kebijakan instrumen perdagangan luar negeri.</li><li><strong>B</strong>: Penggunaan dana bansos untuk kampanye politik adalah tindak pidana korupsi.</li><li><strong>C</strong>: Bansos disalurkan untuk masyarakat miskin, bukan modal korporasi swasta.</li><li><strong>E</strong>: Kerugian pasar modal merupakan risiko investasi komersial investor.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 57) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>guna melindungi dari • kemungkinan terjadinya resiko sosial</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Pos belanja negara yang menampung pengeluaran tidak terduga dan cadangan umum adalah...", "options": ["Belanja modal pembangunan jalan tol lintas antarprovinsi", "Belanja barang jasa pemeliharaan gedung perkantoran pusat", "Belanja subsidi bahan bakar minyak dan tarif tenaga listrik", "Belanja pegawai untuk membayar tunjangan kinerja kedinasan", "Belanja lain-lain yang tidak dapat diklasifikasikan ke pos lain"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Fungsi Belanja Lain-lain</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Belanja lain-lain, merupakan pos untuk menampung belanja Pemerintah Pusat yang tidak dapat diklasifikasikan ke dalam jenis-jenis belanja yang telah disebut di atas, dan dana cadangan umum</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena belanja lain-lain berfungsi sebagai pos bantalan fiskal (*fiscal cushion*) darurat yang siap dicairkan pemerintah saat menghadapi situasi darurat bencana, pengeluaran tak terduga, atau kebutuhan mendesak negara.</p><p>Jawaban tepat: <strong>E. Belanja lain-lain yang tidak dapat diklasifikasikan ke pos lain</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pembangunan jalan tol ditampung dalam pos belanja modal jaringan/fisik.</li><li><strong>B</strong>: Pemeliharaan gedung ditampung dalam pos belanja barang pemeliharaan.</li><li><strong>C</strong>: Subsidi BBM dan listrik ditampung dalam pos belanja subsidi negara.</li><li><strong>D</strong>: Tunjangan kinerja pegawai ditampung dalam pos belanja pegawai.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 57) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Belanja lain-lain, merupakan pos untuk menampung belanja Pemerintah • Pusat yang tidak dapat diklasifikasikan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Hubungan Keuangan Pemerintah Pusat dan Pemerintahan Daerah diatur dalam...", "options": ["UU Nomor 1 Tahun 2022 tentang HKPD", "UU Nomor 33 Tahun 2004 tentang Perimbangan Lama", "UU Nomor 17 Tahun 2003 tentang Keuangan Negara", "UU Nomor 1 Tahun 2004 tentang Perbendaharaan Negara", "UU Nomor 15 Tahun 2004 tentang Pemeriksaan BPK"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dasar Regulasi Hubungan Keuangan Pusat Daerah</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Hubungan Keuangan Pemerintah Pusat dan Pemerintah Daerah diatur dalam <strong>Undang-Undang Nomor 1 Tahun 2022 tentang Hubungan Keuangan Antara Pemerintah Pusat dan Pemerintah Daerah (UU HKPD)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena UU 1/2022 mereformasi sistem desentralisasi fiskal dengan menyelaraskan kebijakan pajak daerah, transfer ke daerah, dan belanja daerah guna menekan ketimpangan antardaerah di Indonesia.</p><p>Jawaban tepat: <strong>A. UU Nomor 1 Tahun 2022 tentang HKPD</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: UU 33/2004 telah dicabut dan digantikan penuh oleh UU HKPD 1/2022.</li><li><strong>C</strong>: UU 17/2003 mengatur asas umum pengelolaan keuangan negara.</li><li><strong>D</strong>: UU 1/2004 mengatur administrasi perbendaharaan negara.</li><li><strong>E</strong>: UU 15/2004 mengatur tata cara pemeriksaan keuangan negara oleh BPK.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 57) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Undang-Undang Nomor 1 Tahun 2022 • tentang Hubungan Keuangan Antara Pemerintah Pusat dan Pemerintah Daerah</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Pilar pertama dalam reformasi hubungan keuangan pusat dan daerah berlandaskan pada...", "options": ["Peningkatan kuota pinjaman luar negeri bagi pemerintah kabupaten", "Pengembangan sistem pajak yang mendukung alokasi sumber daya efisien", "Penyerahan seluruh urusan perpajakan pusat kepada pemerintah desa", "Pembatasan pengalokasian dana alokasi umum bagi daerah terluar", "Penghapusan pungutan retribusi jasa umum di seluruh Indonesia"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pilar Pertama Hubungan Keuangan Pusat Daerah</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pilar pertama HKPD adalah <strong>mengembangkan sistem Pajak yang mendukung alokasi sumber daya nasional yang efisien</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena integrasi sistem perpajakan pusat dan daerah menurunkan biaya kepatuhan (*compliance cost*) bagi dunia usaha serta mengoptimalkan penerimaan daerah tanpa menimbulkan ekonomi biaya tinggi.</p><p>Jawaban tepat: <strong>B. Pengembangan sistem pajak yang mendukung alokasi sumber daya efisien</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pinjaman pemda diatur secara selektif untuk menjaga kesinambungan utang.</li><li><strong>C</strong>: Pajak pusat tetap dikelola terpusat oleh Direktorat Jenderal Pajak.</li><li><strong>D</strong>: DAU justru diprioritaskan bagi daerah dengan kapasitas fiskal rendah.</li><li><strong>E</strong>: Retribusi daerah tetap dipertahankan dengan rasionalisasi jenis layanan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 57) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>mengembangkan sistem Pajak yang mendukung alokasi sumber • daya nasional yang efisien</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Pilar kedua hubungan keuangan pusat dan daerah secara terarah ditujukan untuk...", "options": ["Memusatkan seluruh kas daerah ke dalam rekening perbendaharaan", "Menghapuskan otonomi daerah dalam menetapkan anggaran belanja", "Meminimalkan ketimpangan vertikal dan horizontal melalui TKD", "Mewajibkan pemda membiayai belanja operasional kementerian pusat", "Membatasi penerimaan bagi hasil sumber daya alam bagi daerah"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pilar Kedua Meminimalkan Ketimpangan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pilar kedua HKPD adalah <strong>mengembangkan Hubungan Keuangan dalam meminimalkan ketimpangan vertikal dan horizontal melalui kebijakan TKD dan Pembiayaan Utang Daerah</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena ketimpangan vertikal (pusat vs daerah) dan horizontal (antardaerah kaya SDA vs miskin SDA) diseimbangkan melalui formula transfer DBH, DAU, dan DAK yang berkeadilan.</p><p>Jawaban tepat: <strong>C. Meminimalkan ketimpangan vertikal dan horizontal melalui TKD</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kas daerah dikelola mandiri oleh bendahara umum daerah pemda.</li><li><strong>B</strong>: Otonomi daerah tetap dijamin konstitusi dalam kerangka NKRI.</li><li><strong>D</strong>: Belanja kementerian pusat sepenuhnya dibiayai dari APBN pemerintah.</li><li><strong>E</strong>: Daerah penghasil tetap memperoleh hak bagi hasil SDA sesuai persentase.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 57) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>meminimalkan ketimpangan • vertikal dan horizontal melalui kebijakan TKD</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Pilar ketiga hubungan keuangan pusat dan daerah difokuskan pada upaya...", "options": ["Menghapuskan belanja modal penyediaan infrastruktur daerah", "Memperbesar porsi belanja aparatur dan perjalanan dinas daerah", "Menghabiskan alokasi dana perimbangan sebelum batas semester", "Mendorong peningkatan kualitas dan efektivitas belanja daerah", "Menyerahkan urusan belanja publik daerah kepada pihak swasta"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pilar Ketiga Kualitas Belanja Daerah</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pilar ketiga HKPD adalah <strong>mendorong peningkatan kualitas Belanja Daerah</strong> (*spending better*).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena UU HKPD membatasi belanja pegawai daerah maksimal 30% dan mewajibkan belanja modal infrastruktur minimal 40% dari APBD guna memastikan uang rakyat benar-benar menghasilkan pelayanan publik.</p><p>Jawaban tepat: <strong>D. Mendorong peningkatan kualitas dan efektivitas belanja daerah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Belanja infrastruktur daerah justru diwajibkan minimal 40% dari APBD.</li><li><strong>B</strong>: Belanja pegawai daerah justru dibatasi maksimal 30% agar efisien.</li><li><strong>C</strong>: Penyerapan belanja wajib berorientasi hasil, bukan sekadar kecepatan.</li><li><strong>E</strong>: Pelayanan publik daerah merupakan tanggung jawab konstitusional pemda.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 57) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>mendorong peningkatan kualitas Belanja Daerah</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Pilar keempat hubungan keuangan pusat dan daerah menitikberatkan pada...", "options": ["Pemisahan permanen rencana pembangunan daerah dari RPJMN pusat", "Pemberian kebebasan mutlak daerah menentukan defisit anggaran", "Penyeragaman besaran tarif pajak hotel dan restoran nasional", "Pelepasan kepemilikan bank pembangunan daerah kepada asing", "Harmonisasi kebijakan fiskal antara pemerintah pusat dan daerah"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pilar Keempat Harmonisasi Fiskal</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pilar keempat HKPD adalah <strong>harmonisasi kebijakan fiskal antara Pemerintah dan Daerah untuk penyelenggaraan layanan publik yang optimal dan menjaga kesinambungan fiskal</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena arah pembangunan APBD wajib selaras dengan prioritas pembangunan nasional APBN. Sinergi fiskal ini mencegah kebijakan kontra-produktif antartingkatan pemerintahan.</p><p>Jawaban tepat: <strong>E. Harmonisasi kebijakan fiskal antara pemerintah pusat dan daerah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Rencana daerah (RPJMD) wajib sinkron dengan rencana nasional (RPJMN).</li><li><strong>B</strong>: Defisit APBD dibatasi ketat oleh Kementerian Keuangan.</li><li><strong>C</strong>: Pajak daerah diatur dalam batas tarif maksimal undang-undang.</li><li><strong>D</strong>: BPD adalah BUMD milik pemerintah daerah untuk pembangunan daerah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 57) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>harmonisasi kebijakan • fiskal antara Pemerintah dan Daerah</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Transfer ke Daerah (TKD) pada hakikatnya didefinisikan sebagai dana yang...", "options": ["Bersumber dari APBN yang disalurkan guna mendanai urusan daerah", "Dihimpun dari pungutan retribusi dan pajak asli pemerintah daerah", "Berasal dari pinjaman obligasi daerah di bursa efek domestik", "Dikelola oleh lembaga perbankan swasta untuk modal kredit mikro", "Diperoleh dari sumbangan sukarela masyarakat di wilayah perkotaan"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Transfer ke Daerah (TKD)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Transfer ke Daerah yang selanjutnya disingkat TKD adalah dana yang bersumber dari APBN dan merupakan bagian dari belanja negara yang dialokasikan dan disalurkan kepada Daerah untuk dikelola oleh Daerah dalam rangka mendanai penyelenggaraan Urusan Pemerintahan yang menjadi kewenangan Daerah</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena TKD adalah instrumen utama pendanaan desentralisasi di mana sebagian belanja APBN diserahkan kepada daerah otonom untuk membiayai pelayanan dasar masyarakat di daerah.</p><p>Jawaban tepat: <strong>A. Bersumber dari APBN yang disalurkan guna mendanai urusan daerah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Pajak dan retribusi daerah membentuk Pendapatan Asli Daerah (PAD).</li><li><strong>C</strong>: Obligasi daerah adalah instrumen pinjaman pembiayaan utang daerah.</li><li><strong>D</strong>: TKD dikelola oleh APBD pemda, bukan oleh bank swasta.</li><li><strong>E</strong>: TKD bersumber resmi dari kas APBN negara, bukan sumbangan sukarela.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 58) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Transfer ke Daerah yang selanjutnya disingkat TKD adalah dana yang • bersumber dari APBN</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Ruang lingkup Transfer ke Daerah menurut UU HKPD meliputi pos...", "options": ["Belanja pegawai, belanja barang, belanja modal, dan belanja bunga", "DBH, DAU, DAK, Dana Otsus, Dana Keistimewaan, dan Dana Desa", "Pajak penghasilan, pajak pertambahan nilai, dan bea meterai dokumen", "Surat utang negara, obligasi ritel, dan sukuk tabungan syariah", "Dana cadangan umum kasir, rekening dana investasi, dan giro BI"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Enam Komponen TKD</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>TKD meliputi DBH, DAU, DAK, Dana Otonomi Khusus dan Dana Keistimewaan, serta Dana Desa</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena keenam komponen transfer ini mencakup seluruh saluran penyaluran belanja APBN kepada pemerintah daerah provinsi, kabupaten/kota, hingga pemerintahan desa.</p><p>Jawaban tepat: <strong>B. DBH, DAU, DAK, Dana Otsus, Dana Keistimewaan, dan Dana Desa</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Belanja pegawai, barang, modal adalah jenis belanja pemerintah pusat.</li><li><strong>C</strong>: PPh, PPN, dan bea meterai merupakan pos penerimaan perpajakan.</li><li><strong>D</strong>: SUN, ORI, dan Sukuk adalah instrumen pembiayaan utang negara.</li><li><strong>E</strong>: RDI dan giro BI adalah rekening perbendaharaan kas negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 58) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>TKD meliputi DBH, DAU, DAK, Dana Otonomi Khusus • dan Dana Keistimewaan, serta Dana Desa</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Dana Bagi Hasil (DBH) dialokasikan kepada pemerintah daerah berdasarkan...", "options": ["Tingkat suku bunga pinjaman komersial perbankan daerah", "Jumlah proposal permohonan bantuan yang diajukan oleh bupati", "Persentase atas pendapatan tertentu dalam APBN dan kinerja tertentu", "Hasil lelang barang sitaan kejahatan tindak pidana peradilan", "Besaran sisa lebih anggaran belanja tahun anggaran sebelumnya"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Dana Bagi Hasil (DBH)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Dana Bagi Hasil yang selanjutnya disingkat DBH adalah bagian dari TKD yang dialokasikan berdasarkan persentase atas pendapatan tertentu dalam APBN dan kinerja tertentu</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena pembagian DBH mengikuti formula persentase bagi hasil legal undang-undang atas penerimaan pajak dan sumber daya alam yang dihasilkan dari wilayah daerah yang bersangkutan.</p><p>Jawaban tepat: <strong>C. Persentase atas pendapatan tertentu dalam APBN dan kinerja tertentu</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Suku bunga perbankan tidak menjadi dasar pembagian bagi hasil APBN.</li><li><strong>B</strong>: DBH bukan dana hibah proposal melainkan alokasi berbasis persentase.</li><li><strong>D</strong>: Barang sitaan peradilan disetor ke kas negara sebagai PNBP kejaksaan.</li><li><strong>E</strong>: SiLPA daerah adalah saldo kas daerah yang tidak memengaruhi DBH.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 58) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Dana Bagi Hasil yang selanjutnya disingkat DBH adalah bagian dari TKD • yang dialokasikan berdasarkan persentase</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Penyaluran DBH kepada daerah penghasil dan non-penghasil bertujuan untuk...", "options": ["Menutup kerugian operasional badan usaha milik swasta daerah", "Menghapuskan kewajiban penyusunan rencana kerja anggaran daerah", "Menjamin pembiayaan belanja perjalanan dinas pejabat pemda", "Mengurangi ketimpangan fiskal dan menanggulangi eksternalitas", "Menyeragamkan struktur besaran tunjangan aparatur antardaerah"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuan Penyaluran DBH</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>DBH dibagikan dengan tujuan untuk <strong>mengurangi ketimpangan fiskal antara Pemerintah dan Daerah, serta kepada Daerah lain nonpenghasil dalam rangka menanggulangi eksternalitas negatif dan/atau meningkatkan pemerataan dalam satu wilayah</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena daerah sekitar penghasil tambang/migas sering kali menanggung dampak kerusakan lingkungan (eksternalitas negatif). Alokasi DBH pemerataan membantu menjaga stabilitas kewilayahan.</p><p>Jawaban tepat: <strong>D. Mengurangi ketimpangan fiskal dan menanggulangi eksternalitas</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: DBH tidak boleh digunakan untuk menalangi kerugian swasta.</li><li><strong>B</strong>: Penyusunan RKA-SKPD tetap wajib dilaksanakan secara transparan.</li><li><strong>C</strong>: DBH ditujukan untuk pembangunan dan layanan publik, bukan perjalanan.</li><li><strong>E</strong>: Tunjangan aparatur daerah disesuaikan dengan kemampuan fiskal daerah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 58) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>mengurangi ketimpangan fiskal antara • Pemerintah dan Daerah</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Penerimaan negara yang dibagihasilkan dalam kelompok DBH Pajak mencakup...", "options": ["Pajak penerangan jalan dan pajak reklame perkotaan daerah", "Pajak pertambahan nilai barang mewah dan bea meterai dokumen", "Bea masuk impor komoditas dan bea keluar mineral tambang", "Pajak kendaraan bermotor dan retribusi perizinan tertentu", "Pajak Penghasilan, PBB sektor P3, dan Cukai Hasil Tembakau"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Komponen DBH Pajak</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>DBH Pajak terdiri dari Pajak Penghasilan, Pajak Bumi dan Bangunan, dan cukai hasil tembakau</strong> (DBH CHT).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena PPh (Pasal 25/29 orang pribadi dan PPh 21), PBB sektor P3 (perkebunan, perhutanan, pertambangan), serta Cukai Hasil Tembakau memiliki persentase bagi hasil resmi yang ditransfer kembali ke daerah.</p><p>Jawaban tepat: <strong>E. Pajak Penghasilan, PBB sektor P3, dan Cukai Hasil Tembakau</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pajak penerangan jalan dan reklame adalah pajak asli kabupaten/kota.</li><li><strong>B</strong>: PPN dan bea meterai adalah pajak pusat yang tidak dibagihasilkan.</li><li><strong>C</strong>: Bea masuk dan bea keluar bukan komponen dana bagi hasil pajak.</li><li><strong>D</strong>: Pajak kendaraan bermotor adalah pajak asli pemerintah provinsi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 58) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>DBH Pajak • terdiri dari Pajak Penghasilan, Pajak Bumi dan Bangunan, dan cukai hasil • tembakau</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Sektor penerimaan yang termasuk dalam kelompok DBH Sumber Daya Alam meliputi...", "options": ["Kehutanan, minerba, migas, panas bumi, dan perikanan", "Industri perbankan, pasar modal, asuransi, dan dana pensiun", "Transportasi darat, penerbangan perintis, dan perkeretaapian", "Telekomunikasi digital, informatika, dan pos logistik paket", "Pariwisata perhotelan, hiburan malam, dan restoran kuliner"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Komponen DBH Sumber Daya Alam</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>DBH Sumber Daya Alam terdiri dari kehutanan, mineral dan batu bara, minyak bumi dan gas bumi, panas bumi, dan perikanan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena royalti dan iuran eksploitasi dari lima sektor ekstraktif kekayaan alam ini dibagikan kembali kepada daerah penghasil dan daerah pemerataan dalam satu provinsi.</p><p>Jawaban tepat: <strong>A. Kehutanan, minerba, migas, panas bumi, dan perikanan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Industri perbankan dan asuransi adalah sektor jasa keuangan komersial.</li><li><strong>C</strong>: Transportasi adalah sektor perhubungan logistik, bukan SDA.</li><li><strong>D</strong>: Telekomunikasi adalah sektor infrastruktur teknologi informasi.</li><li><strong>E</strong>: Pariwisata dan hotel adalah sektor pajak jasa daerah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 58) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>DBH Sumber Daya Alam terdiri dari kehutanan, mineral dan • batu bara, minyak bumi dan gas bumi, panas bumi, dan perikanan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Dana Alokasi Umum (DAU) merupakan instrumen transfer ke daerah yang bersifat...", "options": ["Pinjaman modal kerja yang wajib dikembalikan beserta bunga", "Bagian dari TKD untuk pemerataan kemampuan keuangan daerah", "Dana talangan darurat sementara kasir perbankan sentral", "Bantuan keuangan bersyarat penyerahan aset milik daerah", "Penerimaan hibah terikat dari lembaga internasional donor"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Dana Alokasi Umum (DAU)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Dana Alokasi Umum yang selanjutnya disingkat DAU adalah bagian dari TKD yang dialokasikan dengan tujuan mengurangi ketimpangan kemampuan keuangan dan layanan publik antar-daerah</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena DAU adalah hibah umum tanpa syarat (*block grant*) yang memberikan kepastian pendanaan dasar bagi pemda untuk menjalankan pelayanan publik dan pembayaran gaji pegawai daerah.</p><p>Jawaban tepat: <strong>B. Bagian dari TKD untuk pemerataan kemampuan keuangan daerah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: DAU adalah dana transfer cuma-cuma, bukan pinjaman berbunga.</li><li><strong>C</strong>: DAU bukan dana talangan kasir melainkan hak transfer daerah legal.</li><li><strong>D</strong>: Penyaluran DAU tidak menuntut penyerahan aset milik pemda.</li><li><strong>E</strong>: DAU bersumber dari APBN pemerintah pusat, bukan donor asing.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 58) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Dana Alokasi Umum yang selanjutnya disingkat DAU adalah bagian dari • TKD</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Tujuan fundamental dari pengalokasian Dana Alokasi Umum (DAU) adalah...", "options": ["Membiayai pembentukan perusahaan patungan daerah dengan swasta", "Menghambat pembangunan sarana prasarana fisik di daerah kaya", "Mengurangi ketimpangan kemampuan keuangan dan layanan publik", "Menambah porsi belanja perjalanan dinas luar negeri pemda", "Menyita kelebihan saldo kas daerah untuk disetor ke kas negara"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuan Pemerataan Layanan Publik DAU</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>DAU dialokasikan dengan tujuan <strong>mengurangi ketimpangan kemampuan keuangan dan layanan publik antar-daerah</strong> (*horizontal equalization*).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena kapasitas pendapatan asli antardaerah sangat berbeda jauh. DAU memastikan daerah dengan PAD rendah tetap mampu menyelenggarakan standar pelayanan minimal (SPM) pendidikan dan kesehatan.</p><p>Jawaban tepat: <strong>C. Mengurangi ketimpangan kemampuan keuangan dan layanan publik</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: DAU ditujukan untuk layanan dasar publik, bukan investasi swasta.</li><li><strong>B</strong>: DAU tidak menghambat daerah kaya melainkan mengangkat daerah miskin.</li><li><strong>D</strong>: Penggunaan DAU diprioritaskan untuk belanja publik dasar SPM.</li><li><strong>E</strong>: Saldo kas daerah merupakan hak pemda dan tidak disita kas negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 58) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>mengurangi ketimpangan • kemampuan keuangan dan layanan publik antar-daerah</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Pagu nasional DAU ditetapkan pemerintah dengan mempertimbangkan faktor...", "options": ["Luas area kawasan industri manufaktur kepemilikan modal asing", "Jumlah kantor cabang perbankan komersial swasta di provinsi", "Tingkat suku bunga deposito valuta asing di bursa regional", "Kebutuhan pelayanan publik pelaksanaan urusan kewenangan daerah", "Total laba bersih korporasi pertambangan batubara swasta"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pertimbangan Kebutuhan Pelayanan Publik</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pagu nasional DAU ditetapkan dengan mempertimbangkan: <strong>kebutuhan pelayanan publik sebagai bagian dari pelaksanaan urusan pemerintahan yang menjadi kewenangan daerah</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena besaran pagu DAU harus selaras dengan beban urusan pemerintahan wajib yang diemban daerah (layanan pendidikan, kesehatan, pekerjaan umum) agar pemda mampu melaksanakannya.</p><p>Jawaban tepat: <strong>D. Kebutuhan pelayanan publik pelaksanaan urusan kewenangan daerah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Luas kawasan industri asing adalah indikator investasi manufaktur.</li><li><strong>B</strong>: Jumlah kantor bank komersial bukan pertimbangan kebutuhan layanan daerah.</li><li><strong>C</strong>: Suku bunga deposito valas adalah variabel pasar moneter perbankan.</li><li><strong>E</strong>: Laba korporasi batubara adalah pendapatan bisnis privat swasta.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 58) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>kebutuhan pelayanan publik sebagai bagian dari pelaksanaan urusan • pemerintahan yang menjadi kewenangan daerah</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Selain kebutuhan daerah, penentuan batas pagu nasional DAU dibatasi oleh...", "options": ["Cadangan wajib minimum likuiditas giro di bank sentral", "Kemampuan pembiayaan utang luar negeri perbankan daerah", "Permintaan alokasi anggaran dari dewan perwakilan rakyat daerah", "Tingkat perolehan laba kotor seluruh BUMN perseroan terbuka", "Kemampuan keuangan negara dan kesinambungan fiskal APBN"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pertimbangan Kemampuan Keuangan Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pagu nasional DAU ditetapkan dengan mempertimbangkan kebutuhan pelayanan publik, <strong>kemampuan keuangan negara</strong>, pagu TKD secara keseluruhan, dan target pembangunan nasional.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena besaran transfer DAU wajib disesuaikan dengan kapasitas pendapatan neto APBN. Pemaksaan DAU yang melampaui kemampuan fiskal negara berisiko memicu krisis likuiditas kas negara.</p><p>Jawaban tepat: <strong>E. Kemampuan keuangan negara dan kesinambungan fiskal APBN</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Giro wajib minimum adalah kebijakan likuiditas moneter Bank Indonesia.</li><li><strong>B</strong>: Utang perbankan daerah bukan pembatas penetapan pagu nasional APBN.</li><li><strong>C</strong>: Permintaan DPRD tidak bisa mengabaikan kemampuan pendapatan negara.</li><li><strong>D</strong>: Laba BUMN adalah pendapatan PNBP, bukan pembatas langsung DAU.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 58) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>kemampuan keuangan negara</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Pembagian proporsi pagu DAU antara provinsi dan kabupaten/kota mendasarkan pada...", "options": ["Kebutuhan pendanaan pelaksanaan urusan kewenangan masing-masing", "Jumlah kendaraan dinas yang dimiliki pejabat pemerintah daerah", "Luas bangunan gedung kantor bupati dan dewan perwakilan rakyat", "Kedekatan jarak geografis ibu kota daerah dengan ibu kota negara", "Hasil musyawarah lisan antar pimpinan asosiasi kepala daerah"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Proporsi Pagu DAU Provinsi dan Kabupaten</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Proporsi pagu DAU antara Daerah provinsi dan Daerah kabupaten/kota mempertimbangkan kebutuhan pendanaan dalam rangka pelaksanaan Urusan Pemerintahan yang menjadi kewenangan Daerah antara provinsi dan kabupaten/kota</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena beban urusan operasional antara tingkat provinsi (koordinasi antardaerah) berbeda dengan kabupaten/kota (pelayanan langsung masyarakat). Proporsi dihitung objektif berdasarkan beban urusannya.</p><p>Jawaban tepat: <strong>A. Kebutuhan pendanaan pelaksanaan urusan kewenangan masing-masing</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Jumlah mobil dinas bukan parameter alokasi anggaran transfer negara.</li><li><strong>C</strong>: Luas fisik kantor bupati tidak mencerminkan kebutuhan fiskal daerah.</li><li><strong>D</strong>: Jarak ke ibu kota negara bukan dasar perhitungan keadilan fiskal.</li><li><strong>E</strong>: Alokasi DAU dihitung secara saintifik melalui rumus, bukan lisan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 59) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Proporsi • pagu • DAU • antara • Daerah • provinsi</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Dana Alokasi Khusus (DAK) dalam kerangka belanja transfer ke daerah merupakan...", "options": ["Dana pinjaman darurat jangka pendek berbunga rendah bagi pemda", "Bagian TKD untuk mendanai program prioritas nasional daerah", "Penerimaan bagi hasil pajak penjualan barang mewah kementerian", "Bantuan keuangan sukarela tanpa pengawasan dari kantor wilayah", "Cadangan kas umum pemerintah untuk pembayaran bunga utang"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Dana Alokasi Khusus (DAK)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Dana Alokasi Khusus yang selanjutnya disingkat DAK adalah bagian dari TKD yang dialokasikan dengan tujuan untuk mendanai program, kegiatan, dan/atau kebijakan tertentu yang menjadi prioritas nasional dan membantu operasionalisasi layanan publik</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena berbeda dengan DAU yang bersifat umum, DAK bersifat terarah (*specific grant/earmarked*) untuk memastikan kegiatan di daerah mendukung tercapainya target-target prioritas pembangunan nasional.</p><p>Jawaban tepat: <strong>B. Bagian TKD untuk mendanai program prioritas nasional daerah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: DAK adalah dana transfer APBN resmi, bukan pinjaman berbunga.</li><li><strong>C</strong>: DAK bukan pos bagi hasil melainkan alokasi pendanaan kegiatan khusus.</li><li><strong>D</strong>: Penggunaan DAK diawasi ketat dan terikat petunjuk teknis kementerian.</li><li><strong>E</strong>: Pembayaran bunga utang ditanggung melalui APBN pusat, bukan DAK.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 59) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Dana Alokasi Khusus yang selanjutnya disingkat DAK adalah bagian dari • TKD</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Kriteria utama pemanfaatan Dana Alokasi Khusus (DAK) oleh pemerintah daerah adalah...", "options": ["Membiayai kegiatan seremonial peringatan hari ulang tahun daerah", "Menambah anggaran belanja perjalanan dinas luar daerah pejabat", "Mendukung prioritas nasional dan operasionalisasi layanan publik", "Memberikan suntikan modal kerja perseroan terbatas milik swasta", "Membayar sisa tagihan denda keterlambatan proyek gedung pemda"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuan Alokasi DAK</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>DAK dialokasikan untuk mendanai kebijakan yang <strong>menjadi prioritas nasional dan membantu operasionalisasi layanan publik, yang penggunaannya telah ditentukan oleh Pemerintah</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena dana DAK tidak boleh digunakan di luar menu kegiatan prioritas yang ditetapkan pemerintah pusat (seperti penurunan stunting, renovasi sekolah, jalan daerah, dan operasional puskesmas).</p><p>Jawaban tepat: <strong>C. Mendukung prioritas nasional dan operasionalisasi layanan publik</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kegiatan seremonial bukan menu kegiatan prioritas nasional DAK.</li><li><strong>B</strong>: Perjalanan dinas pejabat dilarang keras didanai menggunakan dana DAK.</li><li><strong>D</strong>: Pemberian modal swasta melanggar petunjuk teknis pemanfaatan DAK.</li><li><strong>E</strong>: Denda keterlambatan rekanan adalah sanksi kontrak pengadaan dinas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 59) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>kebijakan tertentu yang menjadi prioritas nasional dan • membantu operasionalisasi layanan publik</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Dalam pembagian jenisnya, Dana Alokasi Khusus (DAK) terdiri atas...", "options": ["Belanja operasional kantor, belanja modal gedung, dan perjadin", "Dana bagi hasil pajak, bagi hasil sumber daya alam, dan royalti", "Dana otonomi khusus, dana keistimewaan, dan dana penyeimbang", "DAK fisik, DAK nonfisik, dan hibah kepada daerah", "Pajak daerah provinsi, retribusi daerah, dan pendapatan asli"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tiga Komponen Pembagian DAK</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>DAK terdiri atas: <strong>DAK fisik, DAK nonfisik, dan hibah kepada daerah</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena tiga cabang DAK ini memfasilitasi pembangunan sarana fisik (sekolah/RS/jalan), pendanaan operasional (BOS/BOK layanan kesehatan), serta hibah program khusus berdasarkan perjanjian formal pemda.</p><p>Jawaban tepat: <strong>D. DAK fisik, DAK nonfisik, dan hibah kepada daerah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Belanja operasional dan perjadin adalah klasifikasi belanja operasional.</li><li><strong>B</strong>: Bagi hasil pajak dan SDA merupakan komponen Dana Bagi Hasil (DBH).</li><li><strong>C</strong>: Dana otsus dan keistimewaan merupakan jenis transfer terpisah dari DAK.</li><li><strong>E</strong>: Pajak dan retribusi daerah adalah komponen pendapatan asli pemda.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 59) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>DAK • fisik,</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Pemanfaatan DAK Nonfisik secara spesifik diperuntukkan bagi...", "options": ["Pemasangan tiang pancang jembatan penyeberangan sungai kota", "Pembangunan konstruksi fisik gedung rumah sakit umum daerah", "Pembelian alat berat eskavator pemeliharaan jalan kabupaten", "Pengadaan tanah perluasan terminal bus antarkota di daerah", "Mendukung operasionalisasi layanan publik di daerah otonom"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pemanfaatan DAK Nonfisik</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>DAK nonfisik, yang digunakan untuk mendukung operasionalisasi layanan publik daerah</strong> (seperti Bantuan Operasional Sekolah/BOS dan Bantuan Operasional Kesehatan/BOK).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena DAK Nonfisik membiayai kebutuhan operasional pelayanan langsung tanpa menghasilkan aset tetap fisik, sehingga sekolah dan puskesmas dapat beroperasi secara optimal melayani masyarakat.</p><p>Jawaban tepat: <strong>E. Mendukung operasionalisasi layanan publik di daerah otonom</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Konstruksi jembatan merupakan kegiatan fisik belanja DAK Fisik jalan.</li><li><strong>B</strong>: Pembangunan gedung RSUD dibiayai melalui pos DAK Fisik bidang kesehatan.</li><li><strong>C</strong>: Pembelian alat berat eskavator masuk dalam menu belanja DAK Fisik.</li><li><strong>D</strong>: Pengadaan tanah didanai melalui belanja modal tanah pemda.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 59) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>DAK nonfisik, yang digunakan untuk mendukung operasionalisasi • layanan publik daerah</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Penyaluran hibah kepada daerah dalam kelompok DAK didasarkan pada...", "options": ["Perjanjian tertulis antara pemerintah pusat dan pemerintah daerah", "Instruksi lisan menteri koordinator pada saat rapat kabinet", "Permintaan mandiri dari dewan perwakilan rakyat daerah setempat", "Surat pernyataan kesanggupan dari asosiasi pengusaha swasta", "Tingkat perolehan suara pada pemilihan umum kepala daerah"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Hibah kepada Daerah Berbasis Perjanjian</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Hibah kepada daerah digunakan untuk mendukung pembangunan fisik atau layanan publik tertentu yang <strong>didasarkan pada perjanjian antara pemerintah dan pemerintah daerah</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena hibah daerah merupakan pengalokasian dana berbasis kinerja (*grant agreement*) di mana daerah yang memenuhi capaian indikator tertentu berhak menerima pencairan dana hibah pemerintah.</p><p>Jawaban tepat: <strong>A. Perjanjian tertulis antara pemerintah pusat dan pemerintah daerah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Instruksi lisan tidak sah sebagai dasar perikatan hukum penyaluran hibah.</li><li><strong>C</strong>: Permintaan sepihak DPRD tidak menggantikan naskah perjanjian resmi.</li><li><strong>D</strong>: Asosiasi pengusaha bukan pihak dalam perjanjian hibah antarpemerintah.</li><li><strong>E</strong>: Pilkada adalah proses politik yang terpisah mutlak dari tata kelola hibah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 59) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>hibah • kepada • daerah,</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Dana Otonomi Khusus merupakan bagian dari TKD yang dialokasikan kepada...", "options": ["Seluruh daerah kabupaten dan kota di kepulauan Jawa dan Bali", "Daerah tertentu untuk mendanai pelaksanaan otonomi khusus", "Pemerintah daerah yang mengalami kekosongan kas pendapatan asli", "Kawasan industri ekonomi khusus yang dikelola korporasi swasta", "Wilayah perbatasan antarnegara yang berstatus darurat militer"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Dana Otonomi Khusus</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Dana Otonomi Khusus adalah bagian dari TKD yang dialokasikan kepada Daerah tertentu untuk mendanai pelaksanaan otonomi khusus sebagaimana ditetapkan dalam Undang-Undang mengenai otonomi khusus</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena dana otsus merupakan amanat undang-undang yang diberikan secara afirmatif kepada Provinsi Papua (dan daerah pemekarannya) serta Provinsi Aceh untuk percepatan pembangunan kesejahteraan masyarakat.</p><p>Jawaban tepat: <strong>B. Daerah tertentu untuk mendanai pelaksanaan otonomi khusus</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Daerah Jawa dan Bali tidak memiliki status otonomi khusus otsus.</li><li><strong>C</strong>: Kekosongan kas diatasi melalui instrumen kas daerah dan DAU.</li><li><strong>D</strong>: Kawasan ekonomi khusus dikelola badan pengelola KEK, bukan penerima otsus.</li><li><strong>E</strong>: Status darurat militer bukan dasar pengalokasian dana otonomi khusus.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 59) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Dana Otonomi Khusus adalah bagian dari TKD yang dialokasikan kepada • Daerah • tertentu</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Pengalokasian dan pemanfaatan Dana Otonomi Khusus wajib berpedoman pada...", "options": ["Petunjuk teknis internal unit layanan pengadaan barang daerah", "Surat edaran kepala dinas pendapatan daerah provinsi setempat", "Ketentuan yang diatur dalam Undang-Undang Otonomi Khusus", "Kesepakatan bersama antar pimpinan organisasi kemasyarakatan", "Hasil musyawarah kerja tahunan kamar dagang dan industri daerah"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dasar Alokasi Dana Otonomi Khusus</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dana Otonomi Khusus dialokasikan kepada daerah yang memiliki otonomi khusus <strong>sesuai dengan Undang-Undang mengenai otonomi khusus</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena undang-undang otsus mengikat secara yuridis peruntukan anggarannya, seperti kewajiban porsi minimal anggaran untuk sektor pendidikan, kesehatan, dan pemberdayaan ekonomi masyarakat asli.</p><p>Jawaban tepat: <strong>C. Ketentuan yang diatur dalam Undang-Undang Otonomi Khusus</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Petunjuk teknis pengadaan hanya mengatur tata cara tender barang jasa.</li><li><strong>B</strong>: Surat edaran dinas berada di bawah undang-undang dan tidak boleh menyimpang.</li><li><strong>D</strong>: Kesepakatan ormas bukan dasar hukum alokasi transfer keuangan negara.</li><li><strong>E</strong>: Kadin adalah wadah asosiasi pengusaha swasta, bukan pembuat regulasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 59) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Undang-Undang mengenai • otonomi khusus</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Pembagian Dana Otonomi Khusus dalam satu wilayah daerah otonom dilaksanakan...", "options": ["Dititipkan pada rekening simpanan pribadi pimpinan daerah", "Dikuasai penuh oleh pemerintah provinsi tanpa membagi kabupaten", "Diberikan seutuhnya kepada pemerintah kota tanpa perikatan", "Antara provinsi dan kabupaten/kota secara adil dan transparan", "Dikonversi menjadi pinjaman subordinasi badan usaha milik pemda"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pembagian Dana Otsus Provinsi dan Kabupaten</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dana Otonomi Khusus <strong>dibagi antara provinsi dan kabupaten/kota di wilayah provinsi yang bersangkutan secara adil dan transparan</strong> sesuai dengan ketentuan perundang-undangan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena pembagian yang adil dan transparan memastikan manfaat dana otsus dapat menyentuh masyarakat hingga ke tingkat distrik, kampung, dan pelosok pedalaman di kabupaten/kota.</p><p>Jawaban tepat: <strong>D. Antara provinsi dan kabupaten/kota secara adil dan transparan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Menyimpan dana dinas di rekening pribadi adalah tindak pidana korupsi.</li><li><strong>B</strong>: Pemerintah provinsi dilarang memonopoli seluruh dana otsus daerah.</li><li><strong>C</strong>: Dana otsus dibagi antara provinsi dan seluruh kabupaten/kota.</li><li><strong>E</strong>: Dana otsus bukan modal pinjaman bisnis melainkan transfer belanja publik.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 59) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>dibagi antara provinsi dan • kabupaten/kota di wilayah provinsi yang bersangkutan secara adil dan • transparan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Bagian dari transfer ke daerah yang dialokasikan khusus mendukung urusan keistimewaan DIY adalah...", "options": ["Dana penyeimbang subsidi ketahanan pangan perkotaan", "Dana alokasi khusus fisik pengadaan tanah perumahan", "Dana bagi hasil cukai tembakau industri perkebunan", "Bantuan keuangan kemanusiaan darurat pasca bencana", "Dana Keistimewaan Daerah Istimewa Yogyakarta"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Dana Keistimewaan DIY</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Dana Keistimewaan Daerah Istimewa Yogyakarta yang selanjutnya disebut Dana Keistimewaan adalah bagian dari TKD yang dialokasikan untuk mendukung urusan keistimewaan Daerah Istimewa Yogyakarta</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena sesuai UU 13/2012 tentang Keistimewaan DIY, pemerintah pusat mengalokasikan Dana Keistimewaan untuk mendanai urusan kelembagaan, kebudayaan, pertanahan, tata ruang, dan pengisian jabatan gubernur/wagub.</p><p>Jawaban tepat: <strong>E. Dana Keistimewaan Daerah Istimewa Yogyakarta</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Dana penyeimbang pangan bukan instrumen resmi keistimewaan DIY.</li><li><strong>B</strong>: DAK fisik perumahan dialokasikan umum kepada daerah yang memenuhi syarat.</li><li><strong>C</strong>: DBH CHT dialokasikan kepada daerah penghasil dan pengolah tembakau.</li><li><strong>D</strong>: Bantuan bencana adalah bantuan sosial darurat, bukan dana keistimewaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 59) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Dana Keistimewaan Daerah Istimewa Yogyakarta yang selanjutnya • disebut Dana Keistimewaan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Penyelenggaraan urusan keistimewaan DIY didanai sesuai ketentuan pokok...", "options": ["Undang-Undang mengenai keistimewaan Daerah Istimewa Yogyakarta", "Peraturan Daerah Khusus mengenai otonomi daerah provinsi", "Surat Keputusan Bersama menteri dalam negeri dan keuangan", "Petunjuk teknis operasional penarikan dana cadangan umum", "Ketetapan majelis musyawarah adat kesultanan keraton"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dasar Hukum Dana Keistimewaan DIY</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dana Keistimewaan dialokasikan <strong>sebagaimana ditetapkan dalam Undang-Undang mengenai keistimewaan Yogyakarta</strong> (UU Nomor 13 Tahun 2012).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena status keistimewaan DIY diakui secara konstitusional dan diatur khusus dalam undang-undang keistimewaan. Alokasi transfer APBN diberikan untuk memelihara warisan budaya dan kelembagaan istimewa tersebut.</p><p>Jawaban tepat: <strong>A. Undang-Undang mengenai keistimewaan Daerah Istimewa Yogyakarta</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Perda khusus otonomi provinsi berada di bawah hierarki undang-undang.</li><li><strong>C</strong>: SKB menteri adalah aturan pelaksana, bukan undang-undang pokok.</li><li><strong>D</strong>: Petunjuk teknis penarikan dana mengatur administrasi kasir.</li><li><strong>E</strong>: Ketetapan adat keraton bukan produk legislasi keuangan negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 60) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Undang-Undang mengenai keistimewaan • Yogyakarta</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Dana Keistimewaan dalam kerangka belanja transfer ke daerah dialokasikan kepada...", "options": ["Seluruh kantor perwakilan kementerian di wilayah Jawa Tengah", "Pemerintah Daerah Provinsi Daerah Istimewa Yogyakarta", "Badan usaha milik daerah sektor transportasi umum kota", "Lembaga perbankan syariah pengelola dana amanah umat", "Kamar dagang dan industri kawasan pariwisata perdesaan"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penerima Alokasi Dana Keistimewaan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Dana Keistimewaan dialokasikan kepada Pemerintah Daerah Provinsi Daerah Istimewa Yogyakarta</strong> sesuai dengan ketentuan peraturan perundang-undangan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena Pemda DIY bertindak sebagai entitas pengelola utama dana keistimewaan. Pemda DIY selanjutnya dapat menyerahkan sebagian pendanaan kepada kabupaten/kota untuk urusan yang didelegasikan.</p><p>Jawaban tepat: <strong>B. Pemerintah Daerah Provinsi Daerah Istimewa Yogyakarta</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kantor kementerian di Jawa Tengah dibiayai melalui DIPA kementerian.</li><li><strong>C</strong>: BUMD transportasi bukan entitas penerima transfer dana keistimewaan.</li><li><strong>D</strong>: Bank syariah adalah lembaga keuangan intermediasi komersial.</li><li><strong>E</strong>: Kadin adalah organisasi swasta pelaku usaha niaga dan industri.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 60) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Dana Keistimewaan dialokasikan kepada Pemerintah Daerah • Provinsi Daerah Istimewa Yogyakarta</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Pendanaan urusan keistimewaan yang dilaksanakan di tingkat kabupaten/kota diusulkan oleh...", "options": ["Lembaga swadaya masyarakat kepada kementerian keuangan pusat", "Asosiasi pelaku usaha pariwisata kepada dewan gubernur BI", "Pemerintah kabupaten/kota kepada Pemerintah Daerah Provinsi DIY", "Pengurus yayasan cagar budaya kepada kantor lelang negara", "Camat dan kepala desa langsung kepada presiden republik"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Usulan Pendanaan Keistimewaan Kabupaten/Kota</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Pendanaan atas urusan keistimewaan diusulkan oleh Pemerintah kabupaten/kota kepada Pemerintah Daerah Provinsi Daerah Istimewa Yogyakarta</strong> dengan memperhatikan kebutuhan dan prioritas.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena mekanisme koordinasi vertikal menuntut kabupaten/kota di DIY menyusun rencana kerja urusan keistimewaan untuk disetujui dan dialokasikan anggarannya oleh Pemda Provinsi DIY.</p><p>Jawaban tepat: <strong>C. Pemerintah kabupaten/kota kepada Pemerintah Daerah Provinsi DIY</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: LSM tidak memiliki kewenangan mengusulkan anggaran transfer resmi.</li><li><strong>B</strong>: Pelaku wisata bukan instansi pengusul anggaran keistimewaan daerah.</li><li><strong>D</strong>: Yayasan cagar budaya bukan entitas pemerintah pengusul DIPA pemda.</li><li><strong>E</strong>: Pemerintah desa mengusulkan kegiatan melalui musrenbang kecamatan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 60) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pendanaan atas urusan keistimewaan diusulkan oleh Pemerintah • kabupaten/kota</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Dana Desa dalam postur APBN didefinisikan sebagai bagian TKD yang diperuntukkan bagi...", "options": ["Badan usaha milik swasta yang mendirikan pabrik di perdesaan", "Kelurahan perkotaan padat penduduk untuk penataan kawasan kumuh", "Koperasi simpan pinjam desa untuk tambahan modal kredit usaha", "Desa guna mendukung penyelenggaraan pemerintahan dan pembangunan", "Penyedia jasa konstruksi pemenang lelang proyek jembatan desa"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Dana Desa</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Dana Desa adalah bagian dari TKD yang diperuntukkan bagi desa dengan tujuan untuk mendukung pendanaan penyelenggaraan Pemerintahan, pelaksanaan pembangunan, pemberdayaan masyarakat, dan kemasyarakatan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena Dana Desa disalurkan langsung dari APBN ke Rekening Kas Desa (RKD) guna memberikan otonomi dan daya dorong nyata bagi kemajuan perdesaan di seluruh tanah air.</p><p>Jawaban tepat: <strong>D. Desa guna mendukung penyelenggaraan pemerintahan dan pembangunan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Dana Desa dilarang dialokasikan untuk modal perusahaan swasta.</li><li><strong>B</strong>: Kelurahan perkotaan didanai melalui pos DAU kelurahan pada APBD.</li><li><strong>C</strong>: Dana Desa bukan pinjaman modal koperasi melainkan belanja publik desa.</li><li><strong>E</strong>: Penyedia jasa menerima pembayaran atas kontrak kerja, bukan penerima dana.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 60) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Dana Desa adalah bagian dari TKD yang diperuntukkan bagi desa</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Pemanfaatan Dana Desa secara regulasi difokuskan untuk mendanai empat bidang yaitu...", "options": ["Penyelenggaraan pesta rakyat dan perayaan tahun baru di desa", "Perjalanan dinas aparatur kementerian, studi banding, dan seminar", "Pembelian saham obligasi korporasi di bursa efek domestik", "Talangan pelunasan utang macet pengurus badan usaha daerah", "Pemerintahan, pembangunan, pemberdayaan, dan kemasyarakatan desa"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Empat Bidang Utama Penggunaan Dana Desa</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Tujuan Dana Desa adalah <strong>mendukung pendanaan penyelenggaraan Pemerintahan, pelaksanaan pembangunan, pemberdayaan masyarakat, dan kemasyarakatan</strong> desa.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena undang-undang desa menggariskan empat pilar pemanfaatan tersebut untuk meningkatkan layanan dasar, infrastruktur desa, ketahanan pangan, dan pengembangan BUMDes.</p><p>Jawaban tepat: <strong>E. Pemerintahan, pembangunan, pemberdayaan, dan kemasyarakatan desa</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kegiatan seremonial hiburan bukan prioritas pembangunan desa.</li><li><strong>B</strong>: Dana Desa dilarang digunakan untuk perjalanan dinas aparatur pusat.</li><li><strong>C</strong>: Investasi saham komersial bukan bidang penggunaan Dana Desa.</li><li><strong>D</strong>: Dana Desa bukan dana talangan utang komersial badan usaha.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 60) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pendanaan penyelenggaraan Pemerintahan, • pelaksanaan • pembangunan,</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Formula pengalokasian Dana Desa kepada tiap-tiap desa dihitung berdasarkan parameter...", "options": ["Kinerja desa, jumlah penduduk, kemiskinan, luas, dan kesulitan geografis", "Banyaknya kendaraan bermotor roda empat yang dimiliki warga desa", "Jumlah suara partai politik pemenang pemilihan kepala desa setempat", "Luas bangunan balai pertemuan desa dan kantor sekretariat rukun warga", "Besaran pendapatan asli daerah kabupaten tempat desa tersebut berada"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Parameter Formula Pengalokasian Dana Desa</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dana Desa dialokasikan dengan mempertimbangkan pemerataan dan keadilan berdasarkan <strong>kinerja desa, jumlah desa, jumlah penduduk, angka kemiskinan, luas wilayah, dan tingkat kesulitan geografis</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena formula ini menerapkan prinsip alokasi dasar, alokasi afirmasi bagi desa tertinggal, alokasi kinerja bagi desa berprestasi, dan alokasi formula berbasis kebutuhan riil wilayah.</p><p>Jawaban tepat: <strong>A. Kinerja desa, jumlah penduduk, kemiskinan, luas, dan kesulitan geografis</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kepemilikan mobil bukan parameter formula transfer keuangan desa.</li><li><strong>C</strong>: Politik praktis pilkades dilarang memengaruhi formula Dana Desa.</li><li><strong>D</strong>: Fisik balai pertemuan bukan indikator rumus pengalokasian transfer.</li><li><strong>E</strong>: PAD kabupaten adalah indikator keuangan pemda, bukan desa.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 60) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>kinerja desa, jumlah desa, jumlah penduduk, angka kemiskinan, luas • wilayah, dan tingkat kesulitan geografis</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Pemerintah pusat menetapkan pedoman fokus penggunaan Dana Desa setiap tahun sesuai...", "options": ["Keinginan pribadi kepala desa tanpa melalui musyawarah warga", "Prioritas nasional dalam perencanaan nasional dan alokasi TKD", "Permintaan rekanan penyedia bahan bangunan pengadaan proyek", "Rekomendasi dari pengurus partai politik di tingkat kecamatan", "Instruksi lisan kepala dinas pariwisata pemerintah kabupaten"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Fokus Penggunaan Dana Desa Tahunan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pemerintah dapat menentukan <strong>fokus penggunaan Dana Desa setiap tahunnya sesuai dengan prioritas nasional yang ditetapkan dalam peraturan perundang-undangan mengenai perencanaan nasional dan alokasi TKD</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena prioritas nasional tahunan (seperti penanganan kemiskinan ekstrem, ketahanan pangan hewani/nabati, dan pencegahan stunting) wajib diadopsi dalam APBDes desa.</p><p>Jawaban tepat: <strong>B. Prioritas nasional dalam perencanaan nasional dan alokasi TKD</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kepala desa wajib menyusun APBDes melalui Musyawarah Desa terbuka.</li><li><strong>C</strong>: Rekanan penyedia tidak berhak mengarahkan fokus belanja desa.</li><li><strong>D</strong>: Pengurus partai dilarang mengintervensi alokasi dana perdesaan.</li><li><strong>E</strong>: Instruksi lisan tanpa dasar regulasi tidak mengikat hukum perbendaharaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 60) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>fokus penggunaan Dana Desa setiap tahunnya sesuai dengan prioritas • nasional</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Dalam mewujudkan prioritas pembangunan nasional, pemerintah mengambil arah kebijakan...", "options": ["Kebijakan penghentian total seluruh proyek infrastruktur publik", "Kebijakan pengetatan anggaran secara drastis di semua sektor", "Kebijakan fiskal yang bersifat ekspansif guna memacu ekonomi", "Kebijakan pembekuan pencairan dana transfer ke seluruh daerah", "Kebijakan devaluasi mata uang rupiah secara berkala bulanan"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kebijakan Fiskal Ekspansif</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam rangka mewujudkan prioritas pembangunan nasional, Pemerintah mengambil <strong>arah kebijakan fiskal yang bersifat ekspansif</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena kebijakan fiskal ekspansif dirancang untuk menginjeksi stimulus likuiditas ke dalam perekonomian melalui belanja negara, sehingga kapasitas produksi dan kesempatan kerja dapat tumbuh optimal.</p><p>Jawaban tepat: <strong>C. Kebijakan fiskal yang bersifat ekspansif guna memacu ekonomi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Infrastruktur merupakan pendorong utama efek ganda ekonomi nasional.</li><li><strong>B</strong>: Pengetatan anggaran drastis (kontraktif) menekan laju pertumbuhan ekonomi.</li><li><strong>D</strong>: Pembekuan TKD melanggar undang-undang perimbangan keuangan.</li><li><strong>E</strong>: Devaluasi rupiah adalah instrumen moneter yang dapat memicu inflasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 60) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>arah kebijakan fiskal yang bersifat ekspansif</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Kebijakan fiskal ekspansif didefinisikan sebagai langkah strategis pemerintah untuk...", "options": ["Menghapus seluruh fasilitas pembebasan bea masuk barang modal", "Menaikkan tarif pajak penghasilan setinggi-tingginya bagi UMKM", "Mengurangi alokasi belanja subsidi pangan bagi masyarakat miskin", "Menaikkan belanja untuk mendorong pertumbuhan yang berkelanjutan", "Menahan sisa saldo kas umum negara tanpa dibelanjakan ke publik"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Kebijakan Fiskal Ekspansif</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Kebijakan fiskal ekspansif adalah kebijakan menaikkan belanja dengan harapan untuk mendorong pertumbuhan ekonomi yang berkelanjutan dan berkeadilan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena perluasan belanja pemerintah meningkatkan permintaan agregat dalam perekonomian nasional. Kebijakan ini menciptakan lapangan kerja baru dan mempercepat pengentasan kemiskinan.</p><p>Jawaban tepat: <strong>D. Menaikkan belanja untuk mendorong pertumbuhan yang berkelanjutan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Menghapus fasilitas bea masuk merugikan investasi industri manufaktur.</li><li><strong>B</strong>: Menaikkan tarif pajak tinggi adalah kebijakan fiskal kontraktif.</li><li><strong>C</strong>: Mengurangi subsidi menekan daya beli masyarakat berpenghasilan rendah.</li><li><strong>E</strong>: Menahan kas tanpa belanja mengurangi stimulus peredaran uang sektor riil.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 60) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>kebijakan menaikkan belanja dengan harapan untuk mendorong pertumbuhan • ekonomi yang berkelanjutan dan berkeadilan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Pelaksanaan kebijakan ekspansif dengan memperbesar belanja pemerintah dilakukan untuk...", "options": ["Menghapuskan keterlibatan dunia usaha swasta dalam proyek tender", "Memperbesar cadangan devisa emas simpanan di bank sentral asing", "Menambah beban potongan pajak penghasilan bagi pekerja pabrik", "Menurunkan nilai tukar rupiah terhadap mata uang dolar amerika", "Meningkatkan daya beli masyarakat dan menggairahkan sektor riil"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuan Peningkatan Daya Beli Masyarakat</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kebijakan fiskal ekspansif dilakukan untuk <strong>meningkatkan daya beli masyarakat</strong>. Dengan melaksanakan kebijakan ini maka yang terjadi adalah jumlah belanja lebih besar dari pendapatan (defisit).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena bantuan sosial, subsidi, dan belanja infrastruktur padat karya menyalurkan likuiditas ke masyarakat. Peningkatan daya beli masyarakat mendorong konsumsi rumah tangga yang menjadi motor ekonomi.</p><p>Jawaban tepat: <strong>E. Meningkatkan daya beli masyarakat dan menggairahkan sektor riil</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Dunia usaha swasta justru digandeng sebagai mitra pelaksana pembangunan.</li><li><strong>B</strong>: Cadangan devisa emas adalah instrumen stabilitas moneter bank sentral.</li><li><strong>C</strong>: Tujuan kebijakan bukan membebani pekerja melainkan meningkatkan daya beli.</li><li><strong>D</strong>: Pelemahan rupiah adalah risiko pasar yang wajib dihindari stabilitasnya.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 60) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>meningkatkan daya beli masyarakat</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Kondisi di mana jumlah pendapatan negara lebih besar daripada jumlah belanja disebut...", "options": ["Surplus anggaran pada postur anggaran pendapatan belanja negara", "Defisit anggaran yang wajib ditutup melalui penerbitan utang", "Krisis likuiditas kas operasional pada rekening kas umum negara", "Gagal bayar kewajiban pokok pinjaman luar negeri pemerintah", "Keseimbangan semu akibat keterlambatan pencairan dana transfer"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Surplus Anggaran</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Apabila jumlah pendapatan negara lebih besar dari jumlah belanja, maka terdapat surplus anggaran</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena surplus anggaran menandakan penerimaan negara mampu menutup seluruh pengeluaran belanja bahkan menyisakan saldo kas berlebih yang dapat dialokasikan untuk tabungan masa depan.</p><p>Jawaban tepat: <strong>A. Surplus anggaran pada postur anggaran pendapatan belanja negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Defisit terjadi apabila belanja melebihi pendapatan negara.</li><li><strong>C</strong>: Krisis likuiditas terjadi jika kas negara tidak mampu membayar tagihan.</li><li><strong>D</strong>: Gagal bayar adalah keadaan wanprestasi kewajiban utang jatuh tempo.</li><li><strong>E</strong>: Keseimbangan semu bukan istilah resmi klasifikasi anggaran negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 61) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pendapatan negara lebih besar dari jumlah belanja, maka terdapat • surplus anggaran</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Apabila belanja negara lebih besar dari pendapatan negara, konsekuensi yang timbul adalah...", "options": ["Pemerintah membatalkan seluruh undang-undang anggaran tahun berjalan", "Terjadi defisit anggaran dan pemerintah mencari sumber pembiayaan", "Pemerintah membekukan operasional seluruh kantor dinas kementerian", "Terjadi surplus anggaran yang dialokasikan ke belanja pembangunan", "Seluruh pegawai negeri sipil diberhentikan sementara tanpa gaji"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Defisit dan Kebutuhan Pendanaan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Apabila belanja negara lebih besar dari pendapatan negara, maka terdapat defisit anggaran</strong>, dan Pemerintah perlu mencari sumber-sumber pendanaan untuk menutup defisit tersebut.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena defisit anggaran merupakan konsekuensi dari kebijakan ekspansif yang telah disetujui bersama DPR. Selisih defisit ini ditutup secara terencana melalui pos pembiayaan anggaran.</p><p>Jawaban tepat: <strong>B. Terjadi defisit anggaran dan pemerintah mencari sumber pembiayaan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: APBN tetap berjalan sah sesuai persetujuan undang-undang anggaran.</li><li><strong>C</strong>: Operasional kantor kementerian tetap berjalan menggunakan dana DIPA.</li><li><strong>D</strong>: Surplus hanya terjadi jika pendapatan lebih besar dari belanja.</li><li><strong>E</strong>: Gaji pegawai negeri sipil tetap dibayar penuh dari alokasi belanja pegawai.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 61) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>belanja negara lebih besar dari • pendapatan negara, maka terdapat defisit anggaran</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Landasan hukum formil yang memuat rumusan definisi pembiayaan anggaran adalah...", "options": ["Undang-Undang Nomor 1 Tahun 2004 tentang Kas Daerah Otonom", "Pasal 33 Ayat 3 Undang-Undang Dasar Negara Republik Indonesia", "Pasal 1 Angka 17 UU Nomor 17 Tahun 2003 tentang Keuangan Negara", "Undang-Undang Nomor 15 Tahun 2004 tentang Standar Akuntansi", "Undang-Undang Nomor 25 Tahun 2004 tentang Sistem Perencanaan"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Pembiayaan Menurut UU 17/2003</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Berdasarkan <strong>pasal 1 Angka 17 UU Nomor 17 Tahun 2003 tentang Keuangan Negara</strong>, pembiayaan adalah setiap penerimaan yang perlu dibayar kembali dan/atau pengeluaran yang akan diterima kembali.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena pasal ini secara yuridis memberikan batasan tegas antara pos pendapatan/belanja murni dengan pos transaksi modal pembiayaan yang menimbulkan hak tagih atau kewajiban bayar kembali.</p><p>Jawaban tepat: <strong>C. Pasal 1 Angka 17 UU Nomor 17 Tahun 2003 tentang Keuangan Negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: UU 1/2004 mengatur perbendaharaan negara secara teknis administratif.</li><li><strong>B</strong>: Pasal 33 UUD 1945 mengatur penguasaan bumi, air, dan kekayaan alam.</li><li><strong>D</strong>: UU 15/2004 mengatur pemeriksaan pengelolaan keuangan oleh BPK.</li><li><strong>E</strong>: UU 25/2004 mengatur sistem perencanaan pembangunan nasional.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 61) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pasal 1 Angka 17 UU Nomor 17 Tahun 2003 tentang Keuangan Negara</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Karakter pokok dari transaksi yang dikelompokkan ke dalam pos pembiayaan adalah...", "options": ["Pungutan memaksa berdasarkan undang-undang tanpa kontraprestasi", "Penerimaan sukarela cuma-cuma tanpa ikatan pengembalian utang", "Pengeluaran konsumtif habis pakai yang tidak menambah aset neraca", "Penerimaan yang perlu dibayar kembali atau pengeluaran diterima kembali", "Penyerahan santunan tunai kepada warga yang mengalami musibah"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Substansi Transaksi Pembiayaan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pembiayaan adalah <strong>setiap penerimaan yang perlu dibayar kembali dan/atau pengeluaran yang akan diterima kembali</strong>, baik pada tahun anggaran bersangkutan maupun tahun berikutnya.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena transaksi pembiayaan (seperti penarikan pinjaman utang atau penyertaan modal negara) bersifat perputaran modal yang memunculkan kewajiban pelunasan atau hak perolehan kembali kas negara.</p><p>Jawaban tepat: <strong>D. Penerimaan yang perlu dibayar kembali atau pengeluaran diterima kembali</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pungutan memaksa tanpa imbalan langsung adalah karakteristik pajak.</li><li><strong>B</strong>: Penerimaan sukarela cuma-cuma merupakan karakteristik penerimaan hibah.</li><li><strong>C</strong>: Pengeluaran konsumtif habis pakai merupakan karakteristik belanja barang.</li><li><strong>E</strong>: Santunan tunai musibah merupakan karakteristik bantuan sosial.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 61) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>setiap penerimaan yang perlu dibayar • kembali dan/atau pengeluaran yang akan diterima kembali</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Pembiayaan dalam negeri untuk menutup defisit anggaran bersumber dari dua sektor yaitu...", "options": ["Sektor kelautan perikanan dan sektor kehutanan kayu lindung", "Sektor industri manufaktur berat dan sektor jasa perdagangan", "Sektor perkebunan kelapa sawit dan sektor pertambangan migas", "Sektor pariwisata perhotelan dan sektor transportasi angkutan", "Sektor perbankan dalam negeri dan sektor nonperbankan"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dua Sektor Pembiayaan Dalam Negeri</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pembiayaan dalam negeri adalah pembiayaan defisit yang bersumber dari dalam negeri yaitu dari <strong>sektor perbankan dan sektor nonperbankan dalam negeri</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena pasar keuangan domestik terbagi atas institusi perbankan (kredit bank/saldo kas) dan institusi non-perbankan (pasar modal/SBN, privatisasi BUMN, dan penjualan aset restrukturisasi).</p><p>Jawaban tepat: <strong>E. Sektor perbankan dalam negeri dan sektor nonperbankan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Perikanan dan kehutanan adalah sektor sumber daya alam lingkungan.</li><li><strong>B</strong>: Manufaktur dan perdagangan adalah sektor industri ekonomi riil.</li><li><strong>C</strong>: Perkebunan dan pertambangan adalah sektor primer penghasil komoditas.</li><li><strong>D</strong>: Pariwisata dan transportasi adalah sektor jasa perekonomian publik.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 61) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>sektor perbankan dan sektor nonperbankan • dalam negeri</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Pembiayaan defisit yang berasal dari sektor perbankan dalam negeri dapat berupa...", "options": ["Pinjaman kredit bank umum atau bank sentral serta saldo rekening RDI", "Penjualan saham mayoritas perseroan terbatas terbuka di bursa", "Pelepasan aset tanah dan bangunan milik kementerian negara", "Penarikan iuran sukarela dari masyarakat di lingkungan kelurahan", "Penerimaan bea masuk barang kiriman melalui jasa pos logistik"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Bentuk Pembiayaan Sektor Perbankan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pembiayaan sektor perbankan terdiri atas: <strong>Pinjaman atau kredit bank, baik dari Bank Umum maupun Bank Sentral</strong> serta penggunaan saldo rekening pemerintah (RDI dan non-RDI).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena fasilitas kredit langsung dari sistem perbankan dan penarikan saldo dana investasi pemerintah di bank sentral/umum merupakan instrumen perbankan untuk menutup kebutuhan defisit.</p><p>Jawaban tepat: <strong>A. Pinjaman kredit bank umum atau bank sentral serta saldo rekening RDI</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Penjualan saham BUMN tergolong dalam pos privatisasi non-perbankan.</li><li><strong>C</strong>: Pelepasan aset BMN tanah masuk pos PNBP pengelolaan BMN.</li><li><strong>D</strong>: Iuran sukarela warga bukan instrumen pembiayaan APBN negara.</li><li><strong>E</strong>: Bea masuk pos adalah penerimaan perpajakan kepabeanan negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 61) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pinjaman atau kredit bank, baik dari Bank Umum maupun Bank Sentral</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Pemanfaatan saldo rekening pemerintah di perbankan dilakukan melalui penggunaan...", "options": ["Rekening tabungan perorangan milik bendahara pengeluaran satker", "Rekening dana investasi (RDI) dan rekening non-RDI pemerintah", "Rekening penampungan dana bantuan sosial di dinas kabupaten", "Rekening giro perbankan swasta yang mengalami pembekuan operasi", "Rekening cadangan devisa emas milik bank sentral luar negeri"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penggunaan Saldo Rekening RDI/Non-RDI</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pembiayaan perbankan mencakup penggunaan saldo pemerintah di Bank Umum atau Bank Sentral yang berbentuk <strong>rekening dana investasi (RDI), dan rekening non-RDI</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena RDI merupakan rekening khusus yang menampung dana pengembalian pinjaman dan hasil investasi pemerintah untuk disalurkan kembali atau digunakan menutup defisit fiskal.</p><p>Jawaban tepat: <strong>B. Rekening dana investasi (RDI) dan rekening non-RDI pemerintah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Menyimpan dana dinas di rekening perorangan bendahara melanggar hukum.</li><li><strong>C</strong>: Rekening bansos dinas daerah adalah rekening penyalur APBD pemda.</li><li><strong>D</strong>: Rekening bank beku operasi disita dan dilikuidasi oleh badan penjamin.</li><li><strong>E</strong>: Cadangan devisa luar negeri dikelola oleh otoritas moneter asing.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 61) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>rekening dana investasi (RDI), dan rekening non-RDI</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Pinjaman pemerintah secara langsung dari sistem perbankan domestik dibatasi karena berpotensi...", "options": ["Menghambat pencairan gaji bulanan seluruh aparatur sipil negara", "Menurunkan harga seluruh komoditas bahan pokok pangan masyarakat", "Menambah likuiditas perekonomian yang memicu timbulnya inflasi", "Mengurangi kepemilikan modal saham badan usaha milik negara", "Memaksa pembekuan izin operasional seluruh bank umum nasional"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Alasan Menghindari Pembiayaan Perbankan Langsung</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pembiayaan defisit dari perbankan dalam negeri dihindari karena dapat <strong>menambah likuiditas perekonomian yang berpotensi memicu timbulnya inflasi</strong>, sehingga pemerintah memilih sektor nonperbankan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena monetisasi defisit melalui kredit perbankan/pencetakan uang membanjiri pasar dengan uang beredar tanpa diimbangi pertambahan barang (*too much money chasing too few goods*), yang memicu hiperinflasi.</p><p>Jawaban tepat: <strong>C. Menambah likuiditas perekonomian yang memicu timbulnya inflasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Gaji ASN dibayar dari pos belanja APBN, tidak terhambat likuiditas bank.</li><li><strong>B</strong>: Ekspansi likuiditas justru memicu kenaikan harga (inflasi), bukan penurunan.</li><li><strong>D</strong>: Kredit perbankan tidak mengalihkan kepemilikan saham ekuitas BUMN.</li><li><strong>E</strong>: Operasional bank umum diatur terpisah oleh regulasi Otoritas Jasa Keuangan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 61) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menambah likuiditas perekonomian yang berpotensi memicu • timbulnya inflasi</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Sumber pembiayaan defisit anggaran dari sektor nonperbankan dalam negeri meliputi...", "options": ["Penerimaan retribusi pelayanan kesehatan di rumah sakit daerah", "Penerimaan pajak pertambahan nilai penyerahan barang mewah", "Pungutan cukai rokok dan alkohol impor dari daerah pabean", "Penerimaan divestasi atau privatisasi BUMN serta penjualan aset", "Bantuan dana hibah langsung tanpa perjanjian dari pihak swasta"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Bentuk Pembiayaan Non-Perbankan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pembiayaan sektor nonperbankan berupa <strong>penerimaan hasil divestasi saham pemerintah pada berbagai badan usaha milik negara (BUMN) atau penerimaan hasil privatisasi BUMN, dan penjualan aset perbankan dalam rangka program restrukturisasi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena dana hasil penjualan saham BUMN dan likuidasi aset eks-BPPN masuk ke kas negara sebagai penerimaan pembiayaan non-utang yang efektif menutup defisit APBN.</p><p>Jawaban tepat: <strong>D. Penerimaan divestasi atau privatisasi BUMN serta penjualan aset</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Retribusi rumah sakit daerah adalah pendapatan asli daerah APBD.</li><li><strong>B</strong>: PPnBM adalah pos penerimaan perpajakan, bukan pos pembiayaan.</li><li><strong>C</strong>: Cukai rokok dan alkohol adalah penerimaan pajak dalam negeri.</li><li><strong>E</strong>: Hibah langsung tanpa perjanjian tergolong pos penerimaan hibah APBN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 61) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>divestasi saham pemerintah pada berbagai badan usaha milik negara • (BUMN) atau penerimaan hasil privatisasi BUMN</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Privatisasi BUMN pada hakikatnya didefinisikan sebagai langkah pembiayaan melalui...", "options": ["Pengambilalihan paksa kepemilikan saham perusahaan swasta murni", "Pembelian kembali seluruh saham BUMN yang beredar di bursa efek", "Pemberian pinjaman dana talangan tanpa bunga kepada BUMN rugi", "Peleburan seluruh badan usaha milik negara menjadi satu induk BUMN", "Penjualan atau pelepasan sebagian saham BUMN kepada pihak swasta"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Privatisasi BUMN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pembiayaan privatisasi BUMN yaitu <strong>pembiayaan dari hasil penjualan atau pelepasan sebagian saham BUMN yang dimiliki pemerintah kepada pihak swasta dalam/luar negeri</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena melalui initial public offering (IPO) atau strategic sales, pemerintah melepas porsi minoritas saham BUMN ke publik, menghasilkan dana segar bagi APBN sekaligus meningkatkan tata kelola BUMN (*good corporate governance*).</p><p>Jawaban tepat: <strong>E. Penjualan atau pelepasan sebagian saham BUMN kepada pihak swasta</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Nasionalisasi paksa swasta melanggar hukum perlindungan modal usaha.</li><li><strong>B</strong>: Pembelian kembali (*buyback*) saham adalah pengeluaran investasi pembiayaan.</li><li><strong>C</strong>: Talangan tanpa bunga menyalahi prinsip pengelolaan investasi negara.</li><li><strong>D</strong>: Pembentukan *holding* BUMN adalah restrukturisasi korporasi BUMN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 61) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>penjualan atau pelepasan sebagian saham BUMN yang dimiliki • pemerintah kepada pihak swasta</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Konsekuensi struktural dari pelepasan sebagian saham BUMN kepada swasta adalah...", "options": ["Hak kontrol monopolistik negara terhadap BUMN tersebut berkurang", "Pemerintah kehilangan seluruh hak suara dalam rapat umum pemegang", "Status badan hukum BUMN secara otomatis berubah menjadi yayasan", "Seluruh aset gedung dan peralatan BUMN disita oleh pihak kreditor", "BUMN tersebut dibebaskan mutlak dari kewajiban pembayaran pajak"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dampak Pelepasan Saham Privatisasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Dengan pelepasan sebagian saham yang dimiliki pemerintah tersebut maka hak kontrol monopolistik negara terhadap BUMN tersebut berkurang</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena masuknya pemegang saham publik/swasta menuntut manajemen BUMN tunduk pada transparansi pasar modal dan persaingan sehat, sehingga kendali sepihak monopoli negara berkurang.</p><p>Jawaban tepat: <strong>A. Hak kontrol monopolistik negara terhadap BUMN tersebut berkurang</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Pemerintah tetap memegang saham pengendali atau saham seri A dwiwarna.</li><li><strong>C</strong>: BUMN privatisasi tetap berbadan hukum perseroan terbatas (PT Tbk).</li><li><strong>D</strong>: Penyitaan aset hanya terjadi pada kasus eksekusi pailit peradilan.</li><li><strong>E</strong>: BUMN privatisasi tetap berstatus wajib pajak badan yang taat pajak.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 62) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>hak kontrol monopolistik negara terhadap BUMN tersebut • berkurang</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "BUMN yang diperkenankan untuk diprivatisasi harus memenuhi syarat mutlak yaitu...", "options": ["Menguasai seluruh cabang produksi yang penting bagi hajat hidup", "BUMN tersebut tidak bersifat strategis bagi pertahanan negara", "Mengalami kerugian operasional menahun tanpa prospek pemulihan", "Beroperasi khusus di bidang pengadaan senjata dan amunisi militer", "Memiliki monopoli tunggal atas distribusi bahan pokok makanan"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Syarat BUMN yang Boleh Diprivatisasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kriteria BUMN yang dapat diprivatisasi adalah <strong>BUMN tersebut tidak bersifat strategis</strong> dan tidak menguasai hajat hidup orang banyak.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena konstitusi Pasal 33 UUD 1945 mengamanatkan cabang produksi strategis wajib dikuasai negara. Privatisasi hanya diizinkan untuk sektor komersial kompetitif.</p><p>Jawaban tepat: <strong>B. BUMN tersebut tidak bersifat strategis bagi pertahanan negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Sektor yang menguasai hajat hidup orang banyak dilarang diprivatisasi penuh.</li><li><strong>C</strong>: BUMN rugi tanpa prospek sulit menarik minat investor pasar modal.</li><li><strong>D</strong>: Industri persenjataan militer adalah sektor strategis pertahanan vital.</li><li><strong>E</strong>: Distribusi bahan pangan esensial diawasi ketat oleh negara (Bulog).</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 62) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>BUMN tersebut tidak bersifat strategis</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Hasil penjualan aset eks-perbankan dalam program penyehatan perbankan dimanfaatkan untuk...", "options": ["Pembayaran tunjangan hari raya bagi direksi perbankan swasta", "Pemberian dana hibah luar negeri bagi negara-negara berkembang", "Dana pembiayaan defisit anggaran dalam pendapatan belanja negara", "Menambah porsi belanja perjalanan dinas kementerian luar negeri", "Penyediaan fasilitas kredit tanpa jaminan bagi korporasi besar"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penjualan Aset Restrukturisasi Perbankan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pembiayaan defisit anggaran lainnya berasal dari <strong>hasil penjualan asset dalam rangka program restrukturisasi perbankan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena pemerintah telah mengucurkan dana talangan obligasi rekapitulasi saat krisis. Hasil pemulihan aset (asset recovery) melalui lelang disetorkan kembali untuk menutup defisit APBN.</p><p>Jawaban tepat: <strong>C. Dana pembiayaan defisit anggaran dalam pendapatan belanja negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Dana penyehatan tidak boleh digunakan untuk bonus direksi perbankan.</li><li><strong>B</strong>: Hasil penjualan aset ditujukan untuk APBN dalam negeri, bukan hibah asing.</li><li><strong>D</strong>: Pemulihan aset bukan untuk membiayai perjalanan dinas kementerian.</li><li><strong>E</strong>: Kredit perbankan wajib menerapkan prinsip kehati-hatian (*prudential banking*).</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 62) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>penjualan asset • dalam rangka program restrukturisasi perbankan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Aset perbankan yang disita dan dijual pemerintah pasca krisis moneter berasal dari...", "options": ["Lembaga koperasi simpan pinjam di tingkat rukun tetangga", "Kantor cabang pembantu bank milik pemerintah daerah otonom", "Seluruh bank umum syariah yang berstatus sehat dan berizin", "Bank beku operasi (BBO) dan bank yang diambil alih BPPN (BTO)", "Unit usaha pegadaian syariah dan perusahaan pembiayaan mikro"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Aset Bank Beku Operasi dan Bank Take Over</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Aset perbankan yang dijual berasal dari <strong>bank beku operasi (BBO) dan bank yang diambil alih pengelolaannya oleh BPPN (bank take over-BTO)</strong> yang asetnya menjadi milik pemerintah.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena Badan Penyehatan Perbankan Nasional (BPPN) dibentuk untuk mengelola dan menjual aset debitur macet serta aset fisik bank bermasalah guna memulihkan keuangan negara.</p><p>Jawaban tepat: <strong>D. Bank beku operasi (BBO) dan bank yang diambil alih BPPN (BTO)</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Koperasi simpan pinjam desa berada di bawah dinas koperasi daerah.</li><li><strong>B</strong>: BPD yang beroperasi sehat tidak dibekukan atau diambil alih BPPN.</li><li><strong>C</strong>: Bank syariah yang berstatus sehat tidak disita aset kepemilikannya.</li><li><strong>E</strong>: Pegadaian syariah adalah entitas usaha pembiayaan gadai resmi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 62) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>bank beku operasi (BBO) dan bank yang diambil alih • pengelolaannya oleh BPPN (bank take over-BTO)</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Salah satu kriteria utama BUMN yang memenuhi syarat diprivatisasi adalah...", "options": ["Beroperasi di sektor pertahanan militer senjata perang", "Terus menerus bergantung pada subsidi anggaran pemerintah pusat", "Memiliki utang jatuh tempo melebihi total seluruh asetnya", "Tidak memiliki laporan keuangan audit selama lima tahun", "Mempunyai daya saing yang kuat di pasar global"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kriteria BUMN Berdaya Saing Global</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>BUMN yang diprivatisasi adalah BUMN yang <strong>mempunyai daya saing yang kuat di pasar global</strong>, mempunyai nilai menarik bagi investor, serta dalam kondisi sehat dan menguntungkan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena privatisasi ditujukan untuk mendorong BUMN menjadi pemain kelas dunia (*world class company*). Daya saing global menjamin saham BUMN diminati oleh pasar.</p><p>Jawaban tepat: <strong>E. Mempunyai daya saing yang kuat di pasar global</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Industri pertahanan militer dilarang keras diprivatisasi kepada swasta.</li><li><strong>B</strong>: BUMN yang bergantung pada subsidi bukan target privatisasi komersial.</li><li><strong>C</strong>: Utang melebihi aset adalah kondisi insolvensi menuju kepailitan.</li><li><strong>D</strong>: Ketiadaan audit laporan keuangan melanggar aturan pasar modal.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 62) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Mempunyai daya saing yang kuat di pasar global</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Dari sudut pandang pasar modal, BUMN yang diprivatisasi harus memiliki karakteristik...", "options": ["Mempunyai nilai dan menarik terutama bagi investor dalam negeri", "Membatasi porsi kepemilikan saham bagi masyarakat pemodal lokal", "Menyimpan sebagian informasi kinerja di luar laporan berkala bursa", "Membatasi pembagian dividen keuntungan kepada pemilik saham", "Memilih pencatatan saham di luar bursa efek nasional resmi"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kriteria Menarik Bagi Investor Domestik</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>BUMN yang diprivatisasi harus <strong>mempunyai nilai dan menarik bagi investor, terutama investor dalam negeri</strong>, guna memperluas basis kepemilikan modal nasional.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena privatisasi diutamakan untuk memperkuat partisipasi investor domestik (termasuk dana pensiun dan reksa dana lokal) agar kepemilikan aset strategis tetap berakar kuat di tanah air.</p><p>Jawaban tepat: <strong>A. Mempunyai nilai dan menarik terutama bagi investor dalam negeri</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Privatisasi justru membuka akses kepemilikan luas bagi investor publik.</li><li><strong>C</strong>: Keterbukaan informasi (*disclosure*) adalah kewajiban mutlak emiten bursa.</li><li><strong>D</strong>: Pembagian dividen yang sehat merupakan daya tarik utama investor.</li><li><strong>E</strong>: Pencatatan di Bursa Efek Indonesia adalah sarana utama privatisasi IPO.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 62) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Mempunyai nilai dan menarik bagi investor, terutama investor dalam • negeri</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Kelayakan finansial BUMN yang diajukan dalam program privatisasi ditandai oleh...", "options": ["Kerugian kas operasional yang berulang setiap akhir tahun", "Kondisi keuangan perusahaan yang sehat dan menguntungkan", "Ketergantungan penuh pada pinjaman talangan dari perbankan", "Tingkat rasio kredit macet yang melampaui ambang batas aman", "Penurunan penjualan produk secara drastis di pasar domestik"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kriteria Kondisi Sehat dan Menguntungkan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kriteria BUMN privatisasi mensyaratkan perusahaan berada <strong>dalam kondisi sehat dan menguntungkan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena hanya perusahaan yang membukukan laba bersih dan memiliki prospek pertumbuhan bisnis yang baik yang dapat dijual dengan valuasi harga optimal guna mendatangkan pendapatan bagi negara.</p><p>Jawaban tepat: <strong>B. Kondisi keuangan perusahaan yang sehat dan menguntungkan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Perusahaan rugi menahun harus direstrukturisasi lebih dahulu sebelum IPO.</li><li><strong>C</strong>: Ketergantungan pinjaman talangan menandakan kelemahan likuiditas internal.</li><li><strong>D</strong>: Kredit macet tinggi merusak kredibilitas dan menurunkan harga saham.</li><li><strong>E</strong>: Penurunan penjualan membuat saham tidak diminati oleh analis pasar modal.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 62) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Dalam kondisi sehat dan menguntungkan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Instrumen pembiayaan defisit yang diterbitkan pemerintah dalam rupiah dan valas adalah...", "options": ["Penerbitan surat izin usaha pertambangan umum daerah", "Penerbitan sertifikat deposito berjangka oleh bank swasta", "Penerbitan Surat Utang Negara (SUN) di pasar keuangan", "Penerbitan kuitansi penerimaan retribusi kebersihan pemda", "Penerbitan faktur pajak pertambahan nilai penyerahan barang"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penerbitan Surat Utang Negara (SUN)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Penerbitan Surat Utang Negara (SUN)</strong> merupakan sumber pembiayaan defisit anggaran yang diterbitkan pemerintah dalam bentuk mata uang rupiah dan valuta asing.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena SUN (Surat Perbendaharaan Negara dan Obligasi Negara) merupakan surat pengakuan utang yang dijamin pembayaran bunga dan pokoknya oleh negara sesuai UU 24/2002.</p><p>Jawaban tepat: <strong>C. Penerbitan Surat Utang Negara (SUN) di pasar keuangan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Izin tambang adalah produk izin administratif, bukan instrumen utang.</li><li><strong>B</strong>: Sertifikat deposito bank swasta adalah produk simpanan perbankan komersial.</li><li><strong>D</strong>: Kuitansi retribusi adalah tanda bukti bayar PAD pemda.</li><li><strong>E</strong>: Faktur pajak adalah bukti pungutan PPN pengusaha kena pajak.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 62) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Penerbitan Surat Utang Negara (SUN)</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Risiko penambahan beban bunga utang akibat kenaikan suku bunga pasar disebut...", "options": ["Risiko hukum pembatalan perjanjian kontrak karya pertambangan", "Risiko likuiditas penarikan dana giro nasabah di bank", "Risiko operasional kesalahan pencatatan buku kas perbendaharaan", "Risiko tingkat bunga dalam penerbitan surat utang negara", "Risiko reputasi penurunan peringkat akreditasi rumah sakit dinas"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Risiko Tingkat Bunga Penerbitan SUN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Penerbitan SUN mengandung <strong>Resiko tingkat bunga, yaitu potensi penambahan beban bunga akibat kenaikan suku bunga</strong> pasar.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena SUN dengan kupon mengambang (*floating rate*) atau penerbitan surat utang baru di era pengetatan moneter membebankan yield yang lebih tinggi pada belanja bunga APBN.</p><p>Jawaban tepat: <strong>D. Risiko tingkat bunga dalam penerbitan surat utang negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Risiko hukum pembatalan kontrak adalah risiko perdata sengketa hukum.</li><li><strong>B</strong>: Risiko likuiditas giro adalah risiko internal bank dalam melayani penarikan.</li><li><strong>C</strong>: Risiko operasional buku kas adalah risiko kesalahan tata usaha dinas.</li><li><strong>E</strong>: Risiko reputasi rumah sakit adalah risiko penilaian layanan medis.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 62) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Resiko tingkat bunga, yaitu potensi penambahan beban bunga akibat • kenaikan suku bunga</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Risiko pelunasan kewajiban pokok obligasi jatuh tempo dengan biaya penerbitan baru mahal adalah...", "options": ["Risiko sengketa batas wilayah administratif antardaerah", "Risiko nilai tukar akibat depresiasi mata uang asing", "Risiko gagal serah barang pada pengadaan barang jasa", "Risiko kebangkrutan badan usaha milik negara penerima subsidi", "Risiko pembiayaan kembali (refinancing risk) surat utang"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Risiko Pembiayaan Kembali (Refinancing Risk)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Resiko pembiayaan kembali, yaitu resiko yang dihadapi pemerintah untuk membiayai kewajiban pokok yang jatuh tempo dari hasil penerbitan baru dengan biaya yang mahal</strong> (*refinancing/rollover risk*).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena apabila kondisi pasar keuangan sedang lesu atau suku bunga global sedang melonjak, penerbitan utang baru untuk melunasi utang lama membebankan biaya bunga yang sangat tinggi bagi APBN.</p><p>Jawaban tepat: <strong>E. Risiko pembiayaan kembali (refinancing risk) surat utang</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Sengketa batas wilayah adalah urusan tata kelola pemerintahan dalam negeri.</li><li><strong>B</strong>: Risiko nilai tukar terjadi akibat fluktuasi kurs rupiah terhadap valas.</li><li><strong>C</strong>: Risiko gagal serah barang adalah risiko wanprestasi kontrak pengadaan.</li><li><strong>D</strong>: Risiko kebangkrutan BUMN adalah risiko solvabilitas korporasi penerima PSO.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 62) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Resiko pembiayaan kembali, yaitu resiko yang dihadapi pemerintah • untuk membiayai kewajiban pokok yang jatuh tempo</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Pembiayaan luar negeri pada hakikatnya didefinisikan sebagai pembiayaan defisit yang...", "options": ["Sumber pendanaannya berasal dari pihak kreditor luar negeri", "Dihimpun dari penjualan surat berharga ritel di pasar domestik", "Berasal dari saldo kas rekening dana investasi kementerian", "Diperoleh dari hasil penagihan tunggakan pajak dalam negeri", "Disetorkan oleh wajib pajak badan usaha penanaman modal dalam"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Pembiayaan Luar Negeri</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Pembiayaan luar negeri merupakan pembiayaan defisit anggaran yang sumbernya berasal dari luar negeri</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena pos ini menampung seluruh aliran masuk pinjaman luar negeri yang disepakati dengan lembaga donor internasional, negara sahabat, maupun investor global di pasar modal internasional.</p><p>Jawaban tepat: <strong>A. Sumber pendanaannya berasal dari pihak kreditor luar negeri</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Penjualan SBN ritel domestik adalah pembiayaan dalam negeri nonperbankan.</li><li><strong>C</strong>: Saldo RDI adalah komponen pembiayaan dalam negeri sektor perbankan.</li><li><strong>D</strong>: Tunggakan pajak masuk pos pendapatan perpajakan dalam negeri.</li><li><strong>E</strong>: Pajak penanaman modal adalah penerimaan perpajakan, bukan utang.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 63) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pembiayaan luar negeri merupakan pembiayaan defisit anggaran yang • sumbernya berasal dari luar negeri</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Pemerintah memanfaatkan sumber pembiayaan luar negeri mengingat kondisi di mana...", "options": ["Pemerintah diwajibkan menghabiskan seluruh cadangan pinjaman dunia", "Sumber pembiayaan dalam negeri yang dihimpun masih terbatas", "Tingkat suku bunga pinjaman luar negeri selalu dipatok nol persen", "Pemerintah dilarang menerbitkan surat berharga negara di dalam negeri", "Seluruh pendapatan perpajakan dialihkan untuk membiayai belanja daerah"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Alasan Penarikan Pinjaman Luar Negeri</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sumber luar negeri masih diperlukan mengingat <strong>sumber-sumber pembiayaan dalam negeri yang dapat dihimpun masih sangat terbatas menutup seluruh pengeluaran negara yang dibutuhkan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena kapasitas tabungan domestik (*domestic saving-investment gap*) belum mampu mendanai seluruh kebutuhan investasi infrastruktur skala besar, sehingga modal luar negeri ditarik secara terukur.</p><p>Jawaban tepat: <strong>B. Sumber pembiayaan dalam negeri yang dihimpun masih terbatas</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pinjaman luar negeri ditarik selektif sesuai batas kebutuhan riil.</li><li><strong>C</strong>: Pinjaman luar negeri memiliki biaya bunga dan persyaratan komersial.</li><li><strong>D</strong>: Pemerintah sangat aktif menerbitkan SBN di pasar keuangan domestik.</li><li><strong>E</strong>: Pajak membiayai belanja pemerintah pusat sekaligus transfer ke daerah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 63) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>sumber-sumber pembiayaan dalam negeri yang dapat dihimpun • masih sangat terbatas</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Pembiayaan luar negeri bersih yang digunakan untuk menutup defisit dihitung dari...", "options": ["Jumlah pinjaman luar negeri ditambah seluruh penerimaan hibah", "Total seluruh penerimaan devisa ekspor dikurangi impor komoditas", "Selisih penarikan pinjaman dengan cicilan pokok utang luar negeri", "Selisih kurs valuta asing pada rekening penampungan kas negara", "Total cicilan pokok utang luar negeri dikurangi belanja bunga"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pembiayaan Luar Negeri Bersih</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pembiayaan luar negeri bersih yaitu <strong>selisih antara penarikan pinjaman luar negeri dengan pembayaran cicilan utang pokok luar negeri</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena angka neto inilah yang menunjukkan tambahan dana segar riil yang masuk ke kas APBN untuk menutup defisit setelah memperhitungkan pengeluaran amortisasi cicilan utang luar negeri yang jatuh tempo.</p><p>Jawaban tepat: <strong>C. Selisih penarikan pinjaman dengan cicilan pokok utang luar negeri</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Hibah adalah pos pendapatan, bukan pengurang cicilan pokok utang.</li><li><strong>B</strong>: Ekspor dikurangi impor adalah neraca perdagangan (*trade balance*).</li><li><strong>D</strong>: Selisih kurs adalah transaksi pembukuan revaluasi nilai aset kas.</li><li><strong>E</strong>: Cicilan pokok dan bunga adalah komponen arus kas keluar pembayaran utang.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 63) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>selisih antara penarikan • pinjaman luar negeri dengan pembayaran cicilan utang pokok luar negeri</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Pemerintah Indonesia secara terencana mulai mencari pembiayaan luar negeri sejak...", "options": ["Tahun 1998 saat dimulainya krisis moneter perbankan nasional", "Tahun 1945 bersamaan dengan masa awal kemerdekaan nasional", "Tahun 1983 pada saat momentum reformasi perpajakan modern", "Tahun 1969 terutama untuk pembiayaan kegiatan pembangunan", "Tahun 2004 setelah pengesahan undang-undang perbendaharaan"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Linimasa Pinjaman Luar Negeri Sejak 1969</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Pemerintah Indonesia sejak tahun 1969, mencari sumber pembiayaan dari luar negeri dalam bentuk pinjaman</strong> terutama untuk pembiayaan pembangunan (awal Repelita I).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena pada awal era pembangunan lima tahunan (1969), penerimaan domestik sangat minim sehingga pemerintah menjalin kerja sama pinjaman pembangunan jangka panjang dengan negara donor.</p><p>Jawaban tepat: <strong>D. Tahun 1969 terutama untuk pembiayaan kegiatan pembangunan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Tahun 1998 adalah puncak krisis ekonomi dan restrukturisasi utang.</li><li><strong>B</strong>: Tahun 1945 belum ada program pinjaman pembangunan terencana luar negeri.</li><li><strong>C</strong>: Tahun 1983 adalah era reformasi perpajakan nasional (tax reform).</li><li><strong>E</strong>: Tahun 2004 adalah momentum pembaruan paket perbendaharaan negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 63) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pemerintah • Indonesia sejak tahun 1969, mencari sumber pembiayaan dari luar negeri</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Pinjaman luar negeri pemerintah pada garis besarnya terbagi atas dua macam yaitu...", "options": ["Pinjaman jangka sangat pendek dan dana talangan kas operasional", "Pinjaman komersial berbunga harian dan pinjaman gadai berjangka", "Pinjaman obligasi daerah dan kredit likuiditas bank sentral", "Pinjaman modal kerja industri swasta dan kredit konsumsi ritel", "Pinjaman Program dan Pinjaman Proyek dari kreditor luar negeri"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dua Macam Pinjaman Luar Negeri</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pinjaman luar negeri pada garis besarnya ada dua macam yaitu <strong>Pinjaman Program dan Pinjaman Proyek</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena klasifikasi ini membedakan pinjaman tunai untuk mendukung reformasi kebijakan anggaran (program) dengan pinjaman barang/jasa untuk mendanai fisik proyek infrastruktur tertentu (proyek).</p><p>Jawaban tepat: <strong>E. Pinjaman Program dan Pinjaman Proyek dari kreditor luar negeri</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Dana talangan kas operasional dikelola melalui rekening perbendaharaan.</li><li><strong>B</strong>: Pinjaman harian dan gadai adalah instrumen kredit perorangan.</li><li><strong>C</strong>: Obligasi daerah dan kredit bank sentral adalah pinjaman domestik.</li><li><strong>D</strong>: Kredit modal swasta dan konsumsi ritel adalah kredit perbankan komersial.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 63) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pinjaman Program dan Pinjaman Proyek</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Pada masa awal pelaksanaannya, Pinjaman Program diterima pemerintah dalam bentuk...", "options": ["Bahan pangan seperti komoditas beras, terigu, dan sejenisnya", "Mata uang emas batangan simpanan cadangan bank sentral", "Peralatan mesin berat pengeboran minyak lepas pantai", "Sertifikat saham kepemilikan korporasi industri multinasional", "Tenaga ahli konsultan manajemen keuangan luar negeri"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Evolusi Pinjaman Program Era Awal</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pada mulanya <strong>Pinjaman Program diberikan dalam bentuk bahan pangan seperti beras, terigu dan sejenisnya</strong>; kemudian berkembang menjadi bentuk uang/devisa yang dapat dirupiahkan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena pada awal Repelita Indonesia masih mengalami kerawanan pangan. Bantuan pangan luar negeri dijual di dalam negeri dan hasilnya (dana tandingan) digunakan membiayai anggaran pembangunan.</p><p>Jawaban tepat: <strong>A. Bahan pangan seperti komoditas beras, terigu, dan sejenisnya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Pinjaman program tidak disalurkan dalam bentuk emas batangan fisik.</li><li><strong>C</strong>: Peralatan mesin pengeboran disalurkan melalui skema pinjaman proyek.</li><li><strong>D</strong>: Sertifikat saham bukan bentuk bantuan pinjaman program pemerintah.</li><li><strong>E</strong>: Tenaga ahli disediakan dalam kerangka pinjaman proyek atau hibah teknis.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 63) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>diberikan dalam bentuk bahan pangan • seperti beras, terigu</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Karakteristik penyaluran Pinjaman Proyek adalah bantuan yang diterima dalam bentuk...", "options": ["Uang tunai devisa yang bebas dibelanjakan untuk belanja pegawai", "Peralatan proyek dan tenaga ahli yang diperlukan proyek bersangkutan", "Komoditas bahan makanan pokok beras impor untuk konsumsi dinas", "Surat utang komersial yang diperdagangkan di pasar bursa saham", "Cadangan kas darurat yang disimpan di brankas kantor satker"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Bentuk Penyaluran Pinjaman Proyek</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pinjaman Proyek adalah pinjaman untuk membiayai proyek Pemerintah yang diterima bukan dalam bentuk devisa tetapi <strong>dalam bentuk peralatan proyek dan tenaga ahli yang diperlukan proyek yang bersangkutan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena pinjaman proyek terikat langsung pada fisik pekerjaan proyek tertentu (misalnya pembangunan PLTA atau jalan tol). Kreditor mencairkan dana langsung kepada vendor penyedia alat dan konsultan proyek.</p><p>Jawaban tepat: <strong>B. Peralatan proyek dan tenaga ahli yang diperlukan proyek bersangkutan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Uang tunai devisa bebas adalah karakteristik pinjaman program.</li><li><strong>C</strong>: Bahan makanan pokok beras adalah bentuk pinjaman program era awal.</li><li><strong>D</strong>: Surat utang komersial adalah instrumen pasar modal obligasi.</li><li><strong>E</strong>: Dana pinjaman proyek tidak disimpan di brankas kantor satker.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 63) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>peralatan proyek dan tenaga • ahli yang diperlukan proyek yang bersangkutan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Salah satu wadah kelompok konsorsium negara pemberi pinjaman kepada Indonesia adalah...", "options": ["Aliansi pertahanan militer keamanan negara kawasan pasifik", "Organisasi federasi serikat pekerja buruh manufaktur global", "Kelompok konsorsium Consultative Group on Indonesia (CGI)", "Asosiasi lembaga pemeringkat kredit perbankan internasional", "Komite penentu tarif pajak pertambahan nilai antarnegara"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Konsorsium Pemberi Pinjaman Luar Negeri (CGI)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Bantuan luar negeri disalurkan dalam kerangka <strong>kelompok pemberi pinjaman kepada Indonesia (misalnya, CGI)</strong> di samping bantuan yang bersifat bilateral.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena CGI (menggantikan IGGI) merupakan konsorsium negara-negara donor dan lembaga multilateral (Bank Dunia, ADB) yang rutin mengoordinasikan komitmen pinjaman pembangunan bagi Indonesia.</p><p>Jawaban tepat: <strong>C. Kelompok konsorsium Consultative Group on Indonesia (CGI)</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Aliansi militer mengurusi pakta pertahanan, bukan konsorsium pinjaman.</li><li><strong>B</strong>: Serikat buruh global adalah organisasi pekerja, bukan lembaga pemberi pinjaman.</li><li><strong>D</strong>: Lembaga pemeringkat menilai risiko kredit, bukan penyedia pinjaman anggaran.</li><li><strong>E</strong>: Komite pajak internasional tidak menyalurkan pinjaman pembiayaan negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 63) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>kelompok pemberi pinjaman • kepada Indonesia (misalnya, CGI)</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Selain pinjaman resmi bilateral/multilateral, pemerintah menghimpun pembiayaan dengan...", "options": ["Menjual pulau-pulau terluar wilayah perbatasan kedaulatan negara", "Menyita aset perusahaan swasta asing yang beroperasi lokal", "Mengajukan pinjaman bunga tinggi kepada rentenir keuangan luar", "Menjual obligasi pemerintah di pasar modal internasional", "Menghapuskan hak otonomi daerah bagi seluruh provinsi perbatasan"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penjualan Obligasi di Pasar Modal Internasional</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pemerintah melaksanakan kebijaksanaan <strong>mengusahakan untuk memperoleh bantuan luar negeri dengan cara menjual obligasi Indonesia di pasar modal internasional</strong> (misalnya di pasar modal Jepang).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena penerbitan Global Bond (seperti Samurai Bond di Jepang atau Sukuk Global) menjangkau investor institusi internasional di pasar modal dunia dengan persyaratan yang transparan dan kompetitif.</p><p>Jawaban tepat: <strong>D. Menjual obligasi pemerintah di pasar modal internasional</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Wilayah kedaulatan negara tidak boleh diperjualbelikan demi alasan apa pun.</li><li><strong>B</strong>: Menyita aset swasta asing melanggar hukum perlindungan investasi internasional.</li><li><strong>C</strong>: Pemerintah dilarang meminjam dana pada rentenir non-institusi.</li><li><strong>E</strong>: Otonomi daerah dilindungi oleh konstitusi undang-undang dasar.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 63) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menjual obligasi Indonesia di pasar • modal internasional</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Pinjaman melalui penerbitan obligasi di pasar internasional dinamakan pinjaman komersil karena...", "options": ["Dikelola oleh lembaga perbankan swasta tanpa pengawasan kementerian", "Diberikan secara sukarela cuma-cuma tanpa kewajiban bayar bunga", "Pencairannya mensyaratkan izin dari menteri perdagangan negara donor", "Dikhususkan untuk membiayai unit usaha perdagangan eceran", "Syarat-syarat pinjamannya mengikuti ketentuan pasar modal internasional"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Karakter Pinjaman Komersil Pasar Modal</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pinjaman obligasi luar negeri dinamakan <strong>pinjaman komersil karena syarat-syarat pinjaman ini sesuai dengan persyaratan yang berlaku di pasar modal internasional</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena tingkat suku bunga, tenor, dan tata cara perdagangan obligasi komersil mengikuti mekanisme penawaran dan permintaan pasar modal global tanpa persyaratan politis atau ikatan proyek tertentu.</p><p>Jawaban tepat: <strong>E. Syarat-syarat pinjamannya mengikuti ketentuan pasar modal internasional</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pengelolaan utang obligasi diawasi ketat oleh Direktorat Jenderal PPR.</li><li><strong>B</strong>: Pinjaman komersil berbunga pasar, bukan bantuan hibah cuma-cuma.</li><li><strong>C</strong>: Obligasi komersil tunduk pada aturan bursa efek, bukan izin kemendag donor.</li><li><strong>D</strong>: Dana obligasi digunakan untuk menutup defisit APBN, bukan eceran swasta.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 63) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pinjaman komersil karena syarat-syarat • pinjaman ini sesuai dengan persyaratan yang berlaku</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Sesuai isi perjanjian pinjaman, setiap tahun anggaran pemerintah berkewajiban...", "options": ["Membayar bunga dan cicilan utang pokok secara tepat waktu", "Mengajukan permohonan pembebasan utang tanpa persetujuan donor", "Mengalihkan kewajiban pelunasan utang kepada pemerintah daerah", "Menunda pembayaran amortisasi hingga batas waktu pergantian kabinet", "Membayar cicilan menggunakan cadangan komoditas pangan mentah"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kewajiban Pembayaran Amortisasi dan Bunga</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sesuai dengan isi perjanjian pinjaman maka setiap tahun Indonesia harus <strong>membayar bunga dan cicilan utang pokok (amortisasi)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena kepatuhan pembayaran kewajiban bunga dan amortisasi cicilan pokok merupakan kunci menjaga reputasi kedaulatan kredit (*sovereign credit rating*) Indonesia di mata lembaga keuangan dunia.</p><p>Jawaban tepat: <strong>A. Membayar bunga dan cicilan utang pokok secara tepat waktu</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Permohonan pembebasan tanpa persetujuan merusak peringkat utang dan memicu wanprestasi.</li><li><strong>C</strong>: Utang luar negeri pemerintah pusat merupakan tanggung jawab APBN pusat.</li><li><strong>D</strong>: Jadwal amortisasi bersifat kontraktual dan tidak boleh ditunda tanpa kesepakatan.</li><li><strong>E</strong>: Pelunasan utang luar negeri wajib dalam mata uang valuta asing yang disepakati.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 64) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>membayar bunga dan cicilan • utang pokok (amortisasi)</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Indikator kemampuan suatu negara dalam melunasi kewajiban utang luar negeri adalah...", "options": ["Return on Investment pada pengelolaan belanja modal dinas", "Debt Service Ratio (DSR) pada neraca pembayaran negara", "Current Ratio pada likuiditas kas operasional bendahara", "Net Profit Margin pada badan usaha milik swasta daerah", "Capital Adequacy Ratio pada lembaga perbankan komersial"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Indikator Debt Service Ratio (DSR)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Jumlah pembayaran cicilan dan bunga utang sangat berkaitan erat dengan besar kecilnya <strong>Debt Service Ratio (DSR)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena DSR merupakan indikator makroekonomi standar internasional untuk mengukur beban kewajiban layanan utang luar negeri terhadap kapasitas perolehan devisa ekspor negara.</p><p>Jawaban tepat: <strong>B. Debt Service Ratio (DSR) pada neraca pembayaran negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: ROI adalah rasio profitabilitas investasi modal perusahaan.</li><li><strong>C</strong>: Current ratio adalah rasio likuiditas jangka pendek neraca korporasi.</li><li><strong>D</strong>: NPM adalah persentase laba bersih dari total penjualan bisnis.</li><li><strong>E</strong>: CAR adalah rasio kecukupan modal institusi perbankan regulasi BI/OJK.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 64) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Debt Service Ratio (DSR)</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Debt Service Ratio (DSR) secara matematis menunjukkan perbandingan rasio antara...", "options": ["Jumlah utang luar negeri dengan total cadangan devisa emas", "Total penerimaan perpajakan dengan total belanja pegawai pusat", "Angsuran plus bunga pinjaman dengan net ekspor migas dan nonmigas", "Belanja operasional kementerian dengan transfer dana desa", "Penerimaan deviden BUMN dengan subsidi energi listrik nasional"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Formula Perhitungan DSR</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>DSR menunjukkan ratio antara Angsuran plus Bunga Pinjaman dengan Net Ekspor Migas plus Non Migas</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena kemampuan membayar utang luar negeri bertumpu pada perolehan devisa neto dari ekspor barang dan jasa. Formula ini membandingkan beban pembayaran tahunan dengan pasokan devisa ekspor.</p><p>Jawaban tepat: <strong>C. Angsuran plus bunga pinjaman dengan net ekspor migas dan nonmigas</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Utang dibanding devisa adalah rasio kecukupan cadangan devisa moneter.</li><li><strong>B</strong>: Pajak dibanding belanja pegawai adalah rasio rigiditas belanja APBN.</li><li><strong>D</strong>: Belanja pusat dibanding transfer desa adalah rasio alokasi desentralisasi.</li><li><strong>E</strong>: Dividen BUMN dibanding subsidi adalah rasio pos fiskal tersendiri.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 64) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>ratio antara • Angsuran plus Bunga Pinjaman dengan Net Ekspor Migas plus Non Migas</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Implikasi fiskal apabila angka Debt Service Ratio (DSR) semakin meningkat adalah...", "options": ["Menurunkan risiko nilai tukar rupiah terhadap valuta asing global", "Makin mempermudah pemerintah dalam menarik pinjaman lunak baru", "Meningkatkan kemampuan pembiayaan belanja bantuan sosial daerah", "Makin menyulitkan posisi keuangan dan beban anggaran negara", "Menyebabkan surplus anggaran pendapatan negara meningkat drastis"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dampak Kenaikan Rasio DSR</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Makin besar ratio itu berarti makin menyulitkan negara yang bersangkutan</strong>, karena porsi devisa hasil ekspor yang terserap untuk membayar utang semakin besar.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena tingginya DSR mengindikasikan sebagian besar devisa hasil ekspor habis tersedot hanya untuk melunasi cicilan dan bunga utang, menyisakan sedikit devisa untuk impor barang modal pembangunan.</p><p>Jawaban tepat: <strong>D. Makin menyulitkan posisi keuangan dan beban anggaran negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: DSR tinggi memperlemah cadangan devisa dan menekan kurs rupiah.</li><li><strong>B</strong>: DSR tinggi menurunkan peringkat kredit dan mempersulit penarikan utang baru.</li><li><strong>C</strong>: Beban cicilan yang besar justru memangkas alokasi belanja sosial.</li><li><strong>E</strong>: DSR tinggi adalah beban pengeluaran pembiayaan, bukan penambah surplus.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 64) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Makin besar ratio itu berarti makin menyulitkan negara yang bersangkutan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Sumber pembiayaan luar negeri yang riil menutup defisit anggaran dihitung dari...", "options": ["Jumlah devisa hibah yang belum dicairkan oleh lembaga donor asing", "Seluruh komitmen pinjaman yang tercantum dalam naskah perjanjian", "Pagu maksimal pinjaman luar negeri yang diusulkan oleh Bappenas", "Estimasi bunga pinjaman luar negeri yang akan jatuh tempo nanti", "Pinjaman luar negeri netto setelah dikurangi pembayaran cicilan"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pinjaman Luar Negeri Netto Penutup Defisit</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Selisih antara jumlah penarikan pinjaman dengan cicilan utang luar negeri merupakan <strong>jumlah pinjaman luar negeri netto pada tahun yang bersangkutan yang merupakan sumber pembiayaan defisit anggaran</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena pembiayaan defisit memperhitungkan arus kas masuk bersih (*net financing*). Penarikan kotor (*gross disbursement*) yang dikurangi amortisasi menghasilkan likuiditas penutup defisit belanja.</p><p>Jawaban tepat: <strong>E. Pinjaman luar negeri netto setelah dikurangi pembayaran cicilan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Devisa hibah yang belum cair tidak dapat menutup defisit kas berjalan.</li><li><strong>B</strong>: Komitmen pinjaman adalah plafon perjanjian, bukan pencairan riil.</li><li><strong>C</strong>: Usulan Bappenas adalah perencanaan alokasi, bukan realisasi kas.</li><li><strong>D</strong>: Estimasi bunga adalah kewajiban belanja bunga, bukan pinjaman netto.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 64) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pinjaman luar negeri netto pada tahun yang bersangkutan • yang merupakan sumber pembiayaan defisit anggaran</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Komitmen kebijakan fiskal yang dicanangkan pemerintah sejak tahun anggaran 2000 adalah...", "options": ["Mengurangi peranan pinjaman luar negeri dalam pembiayaan APBN", "Menghentikan seluruh pemungutan pajak pertambahan nilai barang", "Menghapuskan keterlibatan investor swasta dalam penerbitan obligasi", "Meningkatkan rasio stok utang pemerintah hingga seratus persen PDB", "Memusatkan seluruh penarikan pinjaman melalui kredit bank sentral"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Komitmen Tahun 2000 Mengurangi Utang Luar Negeri</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Mulai tahun anggaran 2000 pemerintah berkomitmen untuk mengurangi peranan pinjaman luar negeri sebagai sumber pembiayaan anggaran negara</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena pelajaran dari krisis moneter 1998 mendorong pemerintah membangun kemandirian fiskal. Pemerintah menggeser fokus pembiayaan ke pasar modal domestik melalui Surat Berharga Negara (SBN).</p><p>Jawaban tepat: <strong>A. Mengurangi peranan pinjaman luar negeri dalam pembiayaan APBN</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Pajak pertambahan nilai justru terus dioptimalkan penerimaannya.</li><li><strong>C</strong>: Investor swasta dan publik domestik sangat didorong membeli obligasi SUN.</li><li><strong>D</strong>: UU Keuangan Negara membatasi rasio utang maksimal 60% dari PDB.</li><li><strong>E</strong>: Pinjaman langsung bank sentral dihindari untuk mencegah inflasi likuiditas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 64) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Mulai tahun anggaran 2000 pemerintah berkomitmen untuk mengurangi • peranan pinjaman luar negeri</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Dalam tata kelola pembiayaan, penggunaan pinjaman luar negeri secara optimal diarahkan pada...", "options": ["Pembiayaan belanja konsumtif rutin operasional aparatur kantor", "Kegiatan ekonomi produktif yang menghasilkan nilai tambah", "Pembayaran tunjangan hari raya dan bonus tahunan pejabat dinas", "Penyelenggaraan jamuan makan malam resmi perwakilan diplomatik", "Pengadaan cinderamata promosi pariwisata daerah di luar negeri"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pemanfaatan Utang untuk Kegiatan Produktif</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Penggunaan pinjaman luar negeri <strong>akan diupayakan secara optimal dalam kegiatan ekonomi produktif</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena kaidah pengelolaan utang yang sehat (*golden rule of fiscal policy*) mengharuskan setiap rupiah utang menghasilkan aset atau kapasitas ekonomi produktif yang kelak mampu membiayai pelunasan utang tersebut.</p><p>Jawaban tepat: <strong>B. Kegiatan ekonomi produktif yang menghasilkan nilai tambah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Belanja konsumtif rutin wajib dibiayai dari penerimaan pendapatan negara.</li><li><strong>C</strong>: Bonus dan THR dibiayai dari pos belanja pegawai APBN.</li><li><strong>D</strong>: Jamuan diplomatik dibiayai dari belanja barang operasional kementerian.</li><li><strong>E</strong>: Cinderamata pariwisata adalah pos belanja barang promosi dinas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 64) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>penggunaannya akan diupayakan secara optimal dalam kegiatan • ekonomi produktif</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Pelaksanaan penarikan dan pemanfaatan pinjaman luar negeri wajib berlandaskan asas...", "options": ["Memberikan hak konsesi pertambangan eksklusif kepada kreditor luar", "Rahasia, fleksibel, menguntungkan kreditor, dan bebas pemeriksaan", "Transparan, efektif, dan efisien dalam mendukung pembangunan", "Menempatkan penasihat militer asing pada unit pengelolaan kasir", "Meniadakan pencatatan dokumen perjanjian pada buku perbendaharaan"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Prinsip Transparan, Efektif, Efisien Pengelolaan Utang</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pemanfaatan pinjaman luar negeri wajib <strong>dilaksanakan secara transparan, efektif, dan efisien</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena transparansi pengelolaan utang menjamin akuntabilitas kepada publik dan parlemen. Efektivitas dan efisiensi menjamin dana pinjaman terserap tepat sasaran tanpa kebocoran anggaran.</p><p>Jawaban tepat: <strong>C. Transparan, efektif, dan efisien dalam mendukung pembangunan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pinjaman luar negeri dilarang mengorbankan kedaulatan tambang nasional.</li><li><strong>B</strong>: Pengelolaan utang terbuka dan wajib diaudit secara rutin oleh BPK.</li><li><strong>D</strong>: Penasihat militer asing melanggar kedaulatan pertahanan negara Republik.</li><li><strong>E</strong>: Seluruh naskah perjanjian utang wajib dibukukan resmi dalam DMFAS Kemenkeu.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 64) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>dilaksanakan secara transparan, efektif, dan efisien</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Kedudukan pokok instrumen pinjaman dalam sistem APBN adalah berfungsi sebagai...", "options": ["Instrumen pembebasan kewajiban audit fungsional aparat pemeriksa", "Sumber pendapatan perpajakan tetap tahunan kementerian negara", "Pos pengeluaran belanja operasional pegawai dinas perbendaharaan", "Sumber pembiayaan defisit anggaran pendapatan dan belanja negara", "Sarana penjaminan likuiditas operasional badan perbankan swasta"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Fungsi Pembiayaan Menutup Defisit</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pinjaman luar negeri netto berfungsi sebagai <strong>sumber pembiayaan defisit anggaran</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena pinjaman bukan pos pendapatan melainkan instrumen penyeimbang (*balancing item*) pada pos pembiayaan yang bertugas menutup kesenjangan antara total belanja dengan total pendapatan negara.</p><p>Jawaban tepat: <strong>D. Sumber pembiayaan defisit anggaran pendapatan dan belanja negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pengelolaan utang tetap wajib diperiksa dan diaudit oleh BPK.</li><li><strong>B</strong>: Pinjaman bukan penerimaan pajak karena menimbulkan kewajiban pelunasan.</li><li><strong>C</strong>: Pinjaman adalah aliran kas masuk pembiayaan, bukan pos belanja dinas.</li><li><strong>E</strong>: Penjaminan likuiditas perbankan swasta adalah wewenang LPS dan BI.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 64) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>sumber pembiayaan defisit anggaran</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Arah kebijakan jangka panjang pengelolaan utang pemerintah bermuara pada komitmen...", "options": ["Menghapus seluruh alokasi dana transfer ke daerah dan dana desa", "Menghentikan seluruh investasi infrastruktur fisik strategis nasional", "Menggantungkan seluruh belanja negara pada bantuan lembaga donor asing", "Membatasi penerimaan pajak penghasilan dari dunia usaha manufaktur", "Mengurangi peranan pinjaman luar negeri sebagai pembiayaan anggaran"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kemandirian Fiskal dan Reduksi Ketergantungan Utang Luar Negeri</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pemerintah berkomitmen untuk <strong>mengurangi peranan pinjaman luar negeri sebagai sumber pembiayaan anggaran negara</strong> demi menjaga kesinambungan dan kemandirian fiskal bangsa.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena kemandirian fiskal menjauhkan perekonomian nasional dari kerentanan gejolak geopolitik global dan risiko fluktuasi kurs mata uang asing, sehingga APBN menjadi instrumen peredam kejut (*shock absorber*) yang mandiri.</p><p>Jawaban tepat: <strong>E. Mengurangi peranan pinjaman luar negeri sebagai pembiayaan anggaran</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Transfer ke daerah dan dana desa tetap menjadi komitmen pemerataan wilayah.</li><li><strong>B</strong>: Investasi infrastruktur tetap digenjot melalui pembiayaan kreatif mandiri.</li><li><strong>C</strong>: Ketergantungan donor asing bertentangan dengan komitmen kedaulatan fiskal.</li><li><strong>D</strong>: Penerimaan pajak justru terus diperkuat sebagai tulang punggung pendapatan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 64) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>mengurangi • peranan pinjaman luar negeri sebagai sumber pembiayaan anggaran negara</strong></div></div>"}];
let current=0;const answers=new Array(questions.length).fill(null);const letters=["A","B","C","D","E"];let seconds=65*60;let submitted=false;


// Anotasi hanya dalam pembahasan. Satu set coretan untuk setiap nomor soal.
const ANNO_KEY='stangolden_pkn_p05_review_annot_v1_user_<?= (int)$USER->id ?>';
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
 try{localStorage.setItem('stg_pkn_p05_map_visible',show?'1':'0')}catch(e){}
}
try{if(localStorage.getItem('stg_pkn_p05_map_visible')==='0')toggleQuestionMap(false)}catch(e){}
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

  if(modalTitle) modalTitle.textContent="🏆 Lihat Ranking Pengelolaan Keuangan Negara Paket 05";
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
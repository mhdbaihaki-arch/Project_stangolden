<?php
/**
 * STANGOLDEN.exam • Nilai-Nilai Kemenkeu Paket 01 (Halaman 5–14)
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
// $DB->get_record("stgnk_p01_rank") => tabel fisik "mdlhv_stgnk_p01_rank".
// Jangan menambahkan prefix secara manual di konstanta berikut.
// Penyimpanan bersama melalui database Moodle. Nama tabel pendek, kompatibel dengan batas Moodle.
const STG_LB_T = 'stgnk_p01_rank';
const STG_REVIEW_T = 'stgnk_p01_rev';
const STG_ANNO_T = 'stgnk_p01_anno';
const STG_KEY = [1, 3, 0, 4, 2, 1, 0, 3, 4, 2, 3, 0, 2, 1, 4, 3, 1, 0, 2, 4, 0, 2, 4, 1, 3, 0, 4, 2, 1, 3, 2, 4, 1, 0, 3, 2, 0, 4, 1, 3, 1, 0, 3, 2, 4, 1, 3, 0, 4, 2, 4, 1, 0, 3, 2, 4, 0, 2, 3, 1, 3, 2, 4, 0, 1, 3, 4, 1, 0, 2, 0, 4, 1, 3, 2, 0, 1, 4, 2, 3, 2, 1, 3, 4, 0, 2, 3, 0, 1, 4, 1, 3, 0, 2, 4, 4, 1, 3, 2, 0];
// Tabel belum ada dibuat otomatis pada submit pertama (bukan saat membuka halaman).
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
            error_log('STANGOLDEN NK P01 HLM05-14 SETUP: ' . $e->getMessage());
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
            // Buat tabel ranking, review, dan anotasi secara otomatis hanya ketika submit.
            // Moodle menangani prefix database berdasarkan konfigurasi $CFG->prefix.
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
    }catch(Throwable $e){error_log('STANGOLDEN NK P01 HLM05-14: '.$e->getMessage());stgnk_json(['success'=>false,'message'=>($e instanceof RuntimeException ? $e->getMessage() : 'Penyimpanan belum tersedia. Hubungi administrator.')],500);}
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
<title>Latihan Nilai-Nilai Kemenkeu Paket 01 (Hlm. 05–14) • STANGOLDEN</title>
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
      <span class="series-tag">Paket 01 • Hlm. 05–14</span>
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
<h1>Latihan Nilai-Nilai Kemenkeu <span class="series-tag">Paket 01 • Hlm. 05–14</span></h1>
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
        <h2 class="leaderboard-title" id="rankingModalTitle">🏆 Lihat Ranking Paket 01</h2>
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
const STG_LOCAL_KEY="stg_nk_p01_review_user_<?= (int)$USER->id ?>";
const HAS_FULL_REVIEW=true;
const IS_SITE_ADMIN=<?= is_siteadmin($USER) ? "true" : "false" ?>;
const LEADERBOARD_ENDPOINT=window.location.href.split("?")[0].split("#")[0];
const questions=[{"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Secara mendasar, nilai didefinisikan sebagai suatu keyakinan mengenai apa yang...", "options": ["Diinginkan dalam menentukan cara dan tujuan", "Dianggap baik dan apa yang dianggap buruk", "Membuat seseorang bertindak sesuai pilihannya", "Membedakan identitas organisasi dari pihak lain", "Menjadi gabungan kode etik, visi, serta misi"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengertian Dasar Nilai</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Nilai</strong> adalah suatu <strong>keyakinan</strong> mengenai <strong>apa yang dianggap baik dan apa yang dianggap buruk</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat untuk melengkapi pengertian dasar nilai. Sebelum dibahas dalam konteks kelembagaan, nilai berakar dari keyakinan manusia dalam membedakan kualitas baik dan buruk suatu hal. Secara konseptual, nilai didefinisikan tegas sebagai suatu keyakinan mengenai apa yang dianggap baik dan apa yang dianggap buruk. Dengan demikian, tolok ukur anggapan baik dan buruk menjadi inti pengertian dasar nilai.</p><p>Jawaban tepat: <strong>B. Dianggap baik dan apa yang dianggap buruk</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan pengertian nilai menurut Kluckhohn mengenai konsepsi yang memengaruhi cara dan tujuan.</li><li><strong>C</strong>: Merupakan pengertian nilai menurut Gordon Allport mengenai keyakinan bertindak berdasarkan pilihan.</li><li><strong>D</strong>: Merupakan pengertian budaya organisasi menurut Stephen P. Robbins sebagai sistem makna pembeda.</li><li><strong>E</strong>: Merupakan gambaran umum wujud budaya organisasi yang merangkum nilai, kode etik, visi, dan misi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 5) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Nilai adalah suatu keyakinan • apa yang dianggap baik • apa yang dianggap buruk</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Selain memengaruhi seseorang dalam bertindak, nilai secara mendasar berfungsi menjadi...", "options": ["Alat ukur capaian kinerja organisasi", "Aturan pembagian wewenang kerja unit", "Pembeda organisasi dalam berkompetisi", "Pedoman dalam kehidupan bermasyarakat", "Rencana strategis pencapaian visi misi"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Peran Dasar Nilai dalam Kehidupan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Nilai</strong> berfungsi <strong>memengaruhi seseorang dalam bertindak</strong> dan menjadi <strong>pedoman dalam kehidupan bermasyarakat</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat mengenai peran dasar nilai bagi manusia. Keyakinan tentang hal baik dan buruk tidak berhenti di dalam pikiran, melainkan mengarahkan tindakan nyata sekaligus menuntun pergaulan sosial. Sesuai konsep dasar tata nilai, nilai memengaruhi seseorang dalam bertindak dan menjadi pedoman dalam kehidupan bermasyarakat. Oleh karena itu, kedudukan nilai sebagai pedoman bermasyarakat melengkapi perannya terhadap tindakan individu.</p><p>Jawaban tepat: <strong>D. Pedoman dalam kehidupan bermasyarakat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan fungsi spesifik standar evaluasi kinerja di dalam manajemen organisasi.</li><li><strong>B</strong>: Merupakan prinsip pengorganisasian manajemen untuk memperjelas batas otoritas kerja.</li><li><strong>C</strong>: Merupakan fungsi nilai perusahaan sebagai DNA pembeda kompetisi menurut Roope Heinila.</li><li><strong>E</strong>: Merupakan instrumen perencanaan manajerial pada institusi formal, bukan peran dasar nilai sosial.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 5) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>memengaruhi seseorang dalam bertindak • pedoman dalam kehidupan bermasyarakat</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Pada konteks organisasi, nilai-nilai organisasi merupakan keyakinan dan kepercayaan yang dimiliki oleh...", "options": ["Individu, kelompok, dan seluruh pemangku kepentingan", "Pimpinan puncak, manajer menengah, dan para penyelia", "Eksekutif perumus kebijakan dan pengawas kepatuhan", "Pejabat struktural, staf ahli, dan konsultan manajemen", "Pengelola kepegawaian pusat dan kepala satuan kerja"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Subjek Pemilik Nilai-Nilai Organisasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Nilai-nilai organisasi</strong> merupakan keyakinan dan kepercayaan yang dimiliki oleh <strong>individu, kelompok, dan seluruh pemangku kepentingan</strong> dalam organisasi tersebut.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat karena mencakup seluruh subjek pemilik nilai-nilai organisasi secara utuh. Dalam kehidupan organisasi, tata nilai harus diyakini bersama melampaui satu jenjang jabatan agar tercipta kesatuan langkah menuju tujuan bersama. Secara konseptual, nilai-nilai organisasi dimiliki oleh individu, kelompok, dan seluruh pemangku kepentingan di dalam organisasi tersebut. Dengan demikian, kepemilikan nilai organisasi mengikat unsur individu, kelompok, hingga pemangku kepentingan.</p><p>Jawaban tepat: <strong>A. Individu, kelompok, dan seluruh pemangku kepentingan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Belum mencakup pegawai pelaksana individu serta pemangku kepentingan organisasi secara menyeluruh.</li><li><strong>C</strong>: Terlalu sempit karena menyisakan kelompok kerja operasional dan pemangku kepentingan lainnya.</li><li><strong>D</strong>: Tidak mencerminkan cakupan seluruh anggota kelompok dan pemangku kepentingan di dalam organisasi.</li><li><strong>E</strong>: Baru menyebutkan unsur pimpinan unit dan pengelola SDM tanpa menyertakan seluruh elemen organisasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 5) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>nilai-nilai organisasi • individu, kelompok, dan seluruh pemangku kepentingan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Pegawai menyelaraskan pola pikir, langkah kerja, dan sikap keseharian demi tujuan instansi sebagai wujud penggunaan nilai-nilai organisasi dalam...", "options": ["Menggali, mempelajari, dan mendiskusikan", "Menilai, bersikap, dan berargumentasi", "Merencanakan, menata, dan mengendalikan", "Memahami, menghayati, dan menyebarkan", "Berpikir, bertindak, dan berperilaku"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tiga Ranah Penggunaan Nilai Organisasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Nilai-nilai organisasi</strong> digunakan oleh seluruh elemen organisasi dalam <strong>berpikir, bertindak, dan berperilaku</strong> untuk <strong>mencapai tujuan organisasi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat sesuai ilustrasi penyelarasan pola pikir, langkah kerja, dan sikap pegawai tersebut. Pola pikir mewakili aspek berpikir, langkah kerja mewakili aspek bertindak, sedangkan sikap keseharian mewakili aspek berperilaku. Dalam tata kelola organisasi, nilai-nilai organisasi digunakan dalam berpikir, bertindak, dan berperilaku untuk mencapai tujuan organisasi. Oleh sebab itu, ketiga ranah tersebut menggambarkan penggunaan nilai-nilai organisasi secara tepat.</p><p>Jawaban tepat: <strong>E. Berpikir, bertindak, dan berperilaku</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan langkah awal identifikasi nilai-nilai organisasi menurut Susan M. Heathfield.</li><li><strong>B</strong>: Merupakan sebagian rincian fungsi nilai organisasi sebagai kriteria atau standar penilaian.</li><li><strong>C</strong>: Merupakan tahapan fungsi prinsip manajemen umum dari perencanaan hingga pengendalian.</li><li><strong>D</strong>: Merupakan proses penyesuaian integrasi budaya organisasi menurut Moeljono Djokosantoso.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 5) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>berpikir, bertindak, dan berperilaku • mencapai tujuan organisasi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Saat berdialog dengan rekan kerja, tim lintas seksi, maupun pengguna layanan, pegawai wajib menghadirkan nilai-nilai organisasi di dalam...", "options": ["Penataan fasilitas fisik ruang pelayanan terpadu instansi", "Pendelegasian wewenang penandatanganan naskah dinas harian", "Komunikasi individu, kelompok, dan pemangku kepentingan", "Pemutakhiran aplikasi penyimpanan arsip surat elektronik", "Penghitungan rincian alokasi anggaran operasional kantor"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kehadiran Nilai Organisasi dalam Komunikasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Nilai-nilai organisasi</strong> perlu hadir dalam <strong>komunikasi antarindividu, kelompok, dan para pemangku kepentingan organisasi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat untuk menggambarkan penerapan nilai pada aktivitas dialog lintas pihak tersebut. Interaksi antara rekan kerja sebagai individu, tim lintas seksi sebagai kelompok, dan pengguna layanan sebagai pemangku kepentingan membutuhkan landasan nilai yang selaras. Sesuai prinsip nilai organisasi, nilai-nilai organisasi perlu ada dalam komunikasi antarindividu, kelompok, dan para pemangku kepentingan organisasi. Dengan demikian, kehadiran nilai memperkokoh kesepahaman komunikasi organisasi.</p><p>Jawaban tepat: <strong>C. Komunikasi individu, kelompok, dan pemangku kepentingan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Berfokus pada pengelolaan sarana prasarana fisik, bukan interaksi penyampaian pesan lintas pihak.</li><li><strong>B</strong>: Berkaitan dengan pengaturan otoritas struktural internal dalam administrasi persuratan.</li><li><strong>D</strong>: Merupakan pemanfaatan teknologi informasi untuk efisiensi pengarsipan dokumen kerja.</li><li><strong>E</strong>: Merupakan kegiatan perencanaan keuangan internal satuan kerja, bukan medium komunikasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 5) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>komunikasi antar individu • para pemangku kepentingan organisasi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Ketika rapat pimpinan unit hendak memilih solusi atas kendala layanan, nilai-nilai organisasi wajib dijadikan pedoman bersama dalam...", "options": ["Penilaian hasil akhir di atas proses teknis", "Setiap pengambilan keputusan di organisasi", "Pemeliharaan stabilitas rutinitas kerja harian", "Pemenuhan kenyamanan pribadi anggota kelompok", "Penggalian gagasan awal pelatihan manajerial"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Nilai Organisasi sebagai Pedoman Pengambilan Keputusan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Nilai-nilai organisasi</strong> harus dijadikan <strong>pedoman bersama</strong> dalam <strong>setiap pengambilan keputusan di organisasi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat sesuai tuntutan penentuan solusi pada rapat pimpinan tersebut. Agar putusan yang diambil konsisten dengan arah tujuan institusi, seluruh pengambil keputusan membutuhkan satu rujukan normatif bersama. Prinsip tata nilai menegaskan bahwa nilai-nilai organisasi harus dijadikan pedoman bersama dalam setiap pengambilan keputusan di organisasi. Oleh karena itu, setiap penetapan putusan kerja wajib berpijak pada nilai-nilai organisasi.</p><p>Jawaban tepat: <strong>B. Setiap pengambilan keputusan di organisasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan ciri dimensi outcome orientation dalam budaya organisasi menurut Stephen P. Robbins.</li><li><strong>C</strong>: Merupakan karakteristik dimensi stability yang menitikberatkan kemapanan kegiatan organisasi.</li><li><strong>D</strong>: Merupakan fokus dimensi people orientation yang menimbang dampak hasil pada anggota.</li><li><strong>E</strong>: Merupakan teknik brainstorming pada tahap identifikasi awal pembentukan nilai organisasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 5) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pedoman bersama • setiap pengambilan keputusan di organisasi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Sebagai organisasi modern, maju, dan terus berkembang, Kementerian Keuangan menjalankan roda organisasinya dengan mengadopsi dan mengadaptasi...", "options": ["Praktik-praktik manajemen yang sehat (best practices)", "Sistem makna pembeda identitas perusahaan kompetitor", "Nilai-nilai pribadi masing-masing anggota masyarakat", "Prinsip orientasi hasil akhir di atas proses kerja", "Karakteristik stabilitas organisasi yang bersifat tetap"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Praktik Manajemen Sehat di Kementerian Keuangan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Kementerian Keuangan</strong> sebagai organisasi <strong>modern, maju, dan terus berkembang</strong> mengadopsi dan mengadaptasi <strong>praktik-praktik manajemen yang sehat (<em>best practices</em>)</strong> dalam menjalankan roda organisasinya.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat mengenai cara Kementerian Keuangan menggerakkan roda organisasinya. Langkah mengadopsi sekaligus mengadaptasi menunjukkan bahwa tata kelola unggulan diserap serta disesuaikan dengan kebutuhan institusi publik yang terus berkembang. Kementerian Keuangan secara eksplisit telah dan sedang mengadopsi serta mengadaptasi praktik-praktik manajemen yang sehat (best practices) dalam menjalankan roda organisasinya. Dengan demikian, penerapan best practices menjadi ciri tata kelola modern Kementerian Keuangan.</p><p>Jawaban tepat: <strong>A. Praktik-praktik manajemen yang sehat (best practices)</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan definisi budaya organisasi sebagai pembeda antarorganisasi menurut Stephen P. Robbins.</li><li><strong>C</strong>: Merupakan tataran nilai personal individu sebelum disepakati menjadi nilai-nilai organisasi.</li><li><strong>D</strong>: Merupakan salah satu karakteristik spesifik budaya organisasi, yaitu outcome orientation.</li><li><strong>E</strong>: Kurang selaras dengan jati diri Kementerian Keuangan yang maju dan terus berkembang.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 5) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>organisasi modern, maju, dan terus berkembang • praktik-praktik manajemen yang sehat</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Seluruh unit kerja pusat dan vertikal Kementerian Keuangan terus mengimplementasikan nilai-nilai organisasi dalam keputusan kerja sehingga hidup sebagai...", "options": ["Sebuah struktur kewenangan", "Sebuah sasaran strategis", "Sebuah kode kehormatan", "Sebuah budaya organisasi", "Sebuah jenjang kepangkatan"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Implementasi Nilai sebagai Budaya Organisasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kementerian Keuangan menetapkan <strong>nilai-nilai organisasi</strong> dalam setiap pengambilan keputusan dan terus <strong>diimplementasikan oleh seluruh unit kerjanya</strong> sebagai sebuah <strong>budaya organisasi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat atas wujud implementasi nilai oleh seluruh unit kerja Kementerian Keuangan. Nilai organisasi yang dipraktikkan secara berkesinambungan di setiap lini kerja akan menyatu menjadi kebiasaan dan identitas bersama. Di Kementerian Keuangan, nilai-nilai organisasi terus diimplementasikan oleh seluruh unit kerjanya sebagai sebuah budaya organisasi. Oleh sebab itu, konsistensi penerapan nilai lintas unit tersebut melahirkan budaya organisasi.</p><p>Jawaban tepat: <strong>D. Sebuah budaya organisasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Berkaitan dengan pembagian wewenang jabatan formal dalam tata kerja manajemen.</li><li><strong>B</strong>: Merupakan target capaian kinerja perencanaan, bukan wujud pengamalan nilai bersama.</li><li><strong>C</strong>: Merupakan sebagian unsur normatif etik, sedangkan implementasi utuh nilai membentuk budaya organisasi.</li><li><strong>E</strong>: Merupakan sistem tingkatan karier kepegawaian aparatur sipil negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 5) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>diimplementasikan oleh seluruh unit kerjanya • sebagai sebuah budaya organisasi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C4 - Analisis", "question": "Pernyataan berikut yang TIDAK TEPAT mengenai tata kelola nilai dan budaya organisasi di Kementerian Keuangan adalah...", "options": ["Kementerian Keuangan merupakan organisasi modern yang maju", "Nilai organisasi ditetapkan dalam pengambilan keputusan", "Nilai organisasi diimplementasikan seluruh unit kerjanya", "Alat manajemen dikembangkan melalui budaya organisasi", "Visi dan misi dibentuk mengikuti perubahan alat manajemen"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Keterkaitan Alat Manajemen, Budaya Organisasi, dan Visi Misi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Alat-alat manajemen</strong> di Kementerian Keuangan dibentuk dan dikembangkan melalui <strong>budaya organisasi</strong> dengan tujuan akhir <strong>mencapai kinerja sesuai visi dan misi Kementerian Keuangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang benar karena memuat pernyataan yang tidak tepat mengenai hubungan alat manajemen dengan visi dan misi. Visi dan misi merupakan arah tujuan strategis institusi, bukan disusun mengikuti perubahan teknis alat manajemen. Dalam tata kelola Kementerian Keuangan, alat-alat manajemen justru dibentuk dan dikembangkan melalui budaya organisasi demi mencapai kinerja sesuai visi dan misi Kementerian Keuangan. Dengan demikian, pernyataan pada opsi E membalik hubungan kausalitas yang sebenarnya.</p><p>Jawaban tepat: <strong>E. Visi dan misi dibentuk mengikuti perubahan alat manajemen</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pernyataan tepat karena Kementerian Keuangan merupakan organisasi modern, maju, dan terus berkembang.</li><li><strong>B</strong>: Pernyataan tepat karena Kementerian Keuangan menetapkan nilai-nilai organisasi dalam setiap pengambilan keputusan.</li><li><strong>C</strong>: Pernyataan tepat karena nilai-nilai organisasi terus diimplementasikan oleh seluruh unit kerjanya.</li><li><strong>D</strong>: Pernyataan tepat karena alat-alat manajemen dibentuk dan dikembangkan melalui budaya organisasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 5) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Alat-alat manajemen ini dibentuk • visi dan misi Kementerian Keuangan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Dalam pembelajaran mandiri menghadapi Ujian Penyesuaian Kenaikan Pangkat Tingkat V di Kementerian Keuangan, penjelasan Nilai-Nilai Kementerian Keuangan dipadukan dengan...", "options": ["Standar Kompetensi Jabatan Fungsional", "Indikator Kinerja Utama Kementerian", "Nilai-Nilai Dasar (Core Values) ASN", "Pedoman Umum Tata Naskah Pemerintah", "Sistem Manajemen Talenta Kepegawaian"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Cakupan Nilai pada UPKP Tingkat V Kementerian Keuangan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pembelajaran mandiri dalam rangka <strong>Ujian Penyesuaian Kenaikan Pangkat Tingkat V</strong> di Kementerian Keuangan Republik Indonesia memuat penjelasan <strong>Nilai-Nilai Dasar (<em>Core Values</em>) ASN</strong> serta <strong>Nilai-Nilai Kementerian Keuangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat mengenai pasangan materi tata nilai bagi peserta ujian tersebut. Pegawai Kementerian Keuangan perlu menguasai landasan nilai nasional aparatur negara secara selaras dengan nilai institusinya. Dalam rangka Ujian Penyesuaian Kenaikan Pangkat Tingkat V di Kementerian Keuangan Republik Indonesia, substansi pembelajaran mandiri mencakup penjelasan Nilai-Nilai Dasar (Core Values) ASN serta Nilai-Nilai Kementerian Keuangan. Oleh karena itu, Core Values ASN menjadi pasangan terpadu Nilai-Nilai Kementerian Keuangan.</p><p>Jawaban tepat: <strong>C. Nilai-Nilai Dasar (Core Values) ASN</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan acuan kualifikasi keahlian teknis pada jabatan fungsional tertentu.</li><li><strong>B</strong>: Merupakan instrumen pengukuran target capaian kinerja strategis unit organisasi.</li><li><strong>D</strong>: Merupakan aturan teknis administrasi surat-menyurat dan naskah kedinasan.</li><li><strong>E</strong>: Merupakan kerangka pemetaan potensi dan pengembangan karier sumber daya manusia.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 5) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Nilai-nilai Dasar (Core Values) ASN • Ujian Penyesuaian Kenaikan Pangkat Tingkat V</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Pembahasan awal kerangka tata nilai kelembagaan memaparkan pengertian dasar nilai yang disandingkan langsung dengan...", "options": ["Pembagian wewenang kerja", "Tingkat kepuasan pegawai", "Strategi penarikan talenta", "Nilai-nilai organisasi", "Kerapian tata administrasi"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Fondasi Pengertian Dasar Nilai dan Nilai Organisasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kerangka pemahaman tata nilai diawali dengan memaparkan <strong>pengertian dasar nilai</strong> dan <strong>nilai-nilai organisasi</strong> sebagai fondasi konseptual.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat mengenai konsep yang dipaparkan bersama pengertian dasar nilai pada tahap awal. Pemahaman nilai secara umum pada diri manusia menjadi jembatan langsung untuk memahami kesepakatan nilai dalam suatu institusi. Secara sistematis, pembahasan diawali dengan memaparkan pengertian dasar nilai dan nilai-nilai organisasi sebelum mengulas pengembangannya. Dengan demikian, nilai-nilai organisasi menjadi pasangan fondasi dari pengertian dasar nilai.</p><p>Jawaban tepat: <strong>D. Nilai-nilai organisasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan syarat kejelasan bertindak dalam ilmu manajemen pada tahap implementasi nilai.</li><li><strong>B</strong>: Merupakan dampak positif dari penerapan budaya organisasi yang kuat menurut riset peneliti.</li><li><strong>C</strong>: Merupakan salah satu peran budaya organisasi dalam menarik bibit unggul atau talenta berkualitas.</li><li><strong>E</strong>: Merupakan contoh nyata perwujudan budaya organisasi dalam kegiatan operasional sehari-hari.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 6) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pengertian dasar nilai dan nilai-nilai organisasi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Urutan pengembangan nilai-nilai dalam sebuah organisasi digambarkan secara runtut mulai dari...", "options": ["Proses pembentukan, fungsi, hingga cara menerapkan nilai", "Fungsi nilai, cara menerapkan, hingga proses pembentukan", "Cara menerapkan nilai, evaluasi kerja, hingga pembentukan", "Pembagian wewenang, fungsi nilai, hingga pembentukan", "Pengukuran kepuasan, pembentukan, hingga fungsi nilai"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Urutan Pengembangan Nilai dalam Organisasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Pengembangan nilai-nilai dalam sebuah organisasi</strong> berlangsung mulai dari <strong>proses pembentukan</strong>, <strong>fungsi</strong>, hingga <strong>cara menerapkan (menginternalisasi) nilai</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat karena menyajikan alur pengembangan nilai organisasi secara berurutan. Nilai organisasi perlu dirumuskan terlebih dahulu melalui proses pembentukan, dipahami fungsinya bagi anggota, lalu diterapkan dalam aktivitas nyata. Kerangka pengembangan nilai-nilai dalam sebuah organisasi digambarkan mulai dari proses pembentukan, fungsi, hingga cara menerapkan atau menginternalisasi nilai melalui budaya organisasi. Oleh sebab itu, urutan pada opsi A sesuai sepenuhnya dengan konsep pengembangan nilai.</p><p>Jawaban tepat: <strong>A. Proses pembentukan, fungsi, hingga cara menerapkan nilai</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Keliru urutan karena menempatkan proses pembentukan nilai pada bagian paling akhir.</li><li><strong>C</strong>: Keliru urutan karena mendahulukan cara menerapkan nilai sebelum proses pembentukannya dilakukan.</li><li><strong>D</strong>: Keliru karena mengawali alur dari pembagian wewenang dan melewatkan tahap cara menerapkan nilai.</li><li><strong>E</strong>: Keliru karena menempatkan pengukuran kepuasan di awal sebelum nilai dibentuk dan diterapkan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 6) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pengembangan nilai-nilai dalam sebuah organisasi • mulai dari proses pembentukan, fungsi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Dalam tahapan pengembangan nilai organisasi, cara menerapkan nilai ke dalam kegiatan organisasi disebut...", "options": ["Mengidentifikasi nilai", "Memproklamasikan nilai", "Menginternalisasi nilai", "Mengklasifikasikan nilai", "Mendokumentasikan nilai"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Makna Menginternalisasi Nilai Organisasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Cara menerapkan nilai</strong> ke dalam seluruh kegiatan organisasi melalui budaya organisasi secara konseptual disebut <strong>menginternalisasi nilai</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan istilah yang tepat untuk menyebut cara menerapkan nilai di dalam organisasi. Rumusan nilai yang telah dibentuk dan dipahami fungsinya harus dihidupkan ke dalam praktik kerja nyata agar tidak berhenti sebagai tulisan. Dalam alur pengembangan nilai organisasi, tahap cara menerapkan nilai dipadankan secara langsung dengan istilah menginternalisasi nilai dalam seluruh kegiatan organisasi. Dengan demikian, menginternalisasi nilai bermakna menerapkan nilai ke dalam kegiatan kerja.</p><p>Jawaban tepat: <strong>C. Menginternalisasi nilai</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan tahap penggalian dan diskusi nilai pada proses pembentukan menurut Susan M. Heathfield.</li><li><strong>B</strong>: Merupakan pernyataan resmi penetapan nilai organisasi sebagai pedoman bersama pemangku kepentingan.</li><li><strong>D</strong>: Berkaitan dengan pengelompokan karakteristik atau sifat-sifat dasar nilai secara teoretis.</li><li><strong>E</strong>: Berkaitan dengan penulisan rumusan kesepakatan eksekutif pada tahap perumusan nilai.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 6) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>cara menerapkan (menginternalisasi) nilai</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Penginternalisasian nilai ke dalam seluruh kegiatan organisasi dilaksanakan secara menyeluruh melalui...", "options": ["Struktur wewenang", "Budaya organisasi", "Sistem remunerasi", "Target kuantitatif", "Hierarki struktural"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Budaya Organisasi sebagai Wahana Internalisasi Nilai</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Cara menerapkan (<strong>menginternalisasi</strong>) nilai dalam seluruh kegiatan organisasi dilaksanakan <strong>melalui budaya organisasi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat mengenai saluran utama penginternalisasian nilai di lingkungan kerja. Agar nilai dapat menjiwai seluruh aktivitas anggota secara konsisten, dibutuhkan atmosfer dan kebiasaan bersama yang mewadahinya. Sesuai kerangka pengembangan nilai dalam organisasi, cara menerapkan atau menginternalisasi nilai dalam seluruh kegiatan organisasi dilakukan melalui budaya organisasi. Oleh karena itu, budaya organisasi berperan sebagai wahana penginternalisasian nilai.</p><p>Jawaban tepat: <strong>B. Budaya organisasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan pembagian batas kekuasaan formal antarjabatan, bukan saluran kultural internalisasi nilai.</li><li><strong>C</strong>: Merupakan mekanisme imbalan finansial kepegawaian dalam manajemen sumber daya manusia.</li><li><strong>D</strong>: Merupakan ukuran capaian angka pada instrumen evaluasi kinerja organisasi.</li><li><strong>E</strong>: Merupakan susunan tingkatan garis komando pada bagan struktur organisasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 6) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>dalam seluruh kegiatan organisasi • melalui budaya organisasi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Sebuah kantor pelayanan menghidupkan tata nilai mulai dari penataan surat-menyurat hingga layanan publik karena internalisasi nilai mencakup...", "options": ["Seluruh rapat pimpinan puncak", "Seluruh loket layanan masyarakat", "Seluruh audit laporan keuangan", "Seluruh diklat manajer menengah", "Seluruh kegiatan organisasi"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Cakupan Internalisasi Nilai di Seluruh Kegiatan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Penerapan (<strong>internalisasi</strong>) nilai melalui budaya organisasi wajib mencakup <strong>seluruh kegiatan organisasi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat untuk menjelaskan luasnya jangkauan penerapan nilai pada kantor pelayanan tersebut. Budaya organisasi yang utuh menuntut kehadiran nilai pada fungsi pendukung internal maupun pelayanan eksternal secara serentak. Dalam pengembangan nilai-nilai organisasi, penginternalisasian nilai melalui budaya organisasi ditegaskan mencakup seluruh kegiatan organisasi. Dengan demikian, baik tata persuratan maupun layanan publik sama-sama termasuk dalam cakupan seluruh kegiatan organisasi.</p><p>Jawaban tepat: <strong>E. Seluruh kegiatan organisasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Baru mencakup forum pengambilan kebijakan pimpinan, belum merangkum aktivitas operasional harian.</li><li><strong>B</strong>: Baru mencakup lini pelayanan depan tanpa menyertakan kegiatan administrasi pendukung internal.</li><li><strong>C</strong>: Baru mencakup aktivitas pemeriksaan dan pengawasan akuntabilitas keuangan.</li><li><strong>D</strong>: Baru mencakup kegiatan pelatihan pengembangan kompetensi bagi pejabat tingkat menengah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 6) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>cara menerapkan (menginternalisasi) nilai • dalam seluruh kegiatan organisasi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C4 - Analisis", "question": "Alasan utama penyandingan nilai-nilai ASN dengan nilai-nilai Kementerian Keuangan dalam satu pedoman perilaku terpadu adalah karena...", "options": ["Nilai instansi memperbarui rumusan nilai aparatur daerah", "Kedua nilai membagi wewenang kantor pusat dan vertikal", "Nilai dasar negara dikhususkan bagi pejabat struktural", "Tata nilai diperuntukkan bagi pegawai Kementerian Keuangan", "Kedua sistem nilai diterapkan bergantian sesuai proyek kerja"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Alasan Penyandingan Nilai ASN dan Nilai Kementerian Keuangan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pembahasan mengenai <strong>nilai-nilai ASN</strong> dan <strong>nilai-nilai Kementerian Keuangan</strong> dimuat secara bersamaan karena <strong>diperuntukkan bagi pegawai Kementerian Keuangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat dalam menjelaskan landasan penyandingan kedua tata nilai tersebut. Setiap insan Kementerian Keuangan berstatus sebagai Aparatur Sipil Negara sekaligus pegawai pengelola keuangan negara sehingga terikat pada kedua tata nilai yang saling selaras tersebut. Secara eksplisit, pemuatan hal-hal terkait nilai-nilai ASN dan nilai-nilai Kementerian Keuangan dilakukan karena diperuntukkan bagi pegawai Kementerian Keuangan. Oleh sebab itu, peruntukan subjek bagi pegawai Kementerian Keuangan menjadi alasan utamanya.</p><p>Jawaban tepat: <strong>D. Tata nilai diperuntukkan bagi pegawai Kementerian Keuangan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena Nilai-Nilai Kementerian Keuangan berlaku menguatkan budaya internal Kementerian Keuangan.</li><li><strong>B</strong>: Kurang tepat karena kedua tata nilai selaras berlaku di pusat maupun vertikal tanpa membagi wewenang.</li><li><strong>C</strong>: Kurang tepat karena Core Values ASN berlaku bagi seluruh pegawai pusat maupun daerah pada semua jabatan.</li><li><strong>E</strong>: Kurang tepat karena Nilai Dasar ASN dan Nilai-Nilai Kementerian Keuangan saling mendukung secara simultan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 6) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>nilai-nilai ASN dan nilai-nilai Kementerian Keuangan • diperuntukkan bagi pegawai Kementerian Keuangan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Ketentuan penerapan Nilai-Nilai Kementerian Keuangan dimaksudkan agar digunakan sebagai pedoman bagi...", "options": ["Seluruh mitra penyedia barang eksternal", "Seluruh pegawai Kementerian Keuangan", "Seluruh aparatur pemerintah daerah", "Seluruh masyarakat pengguna layanan", "Seluruh pejabat pemeriksa eksternal"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Subjek Sasaran Pedoman Nilai Kementerian Keuangan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Ketentuan tata nilai dimaksudkan sebagai <strong>pedoman bagi seluruh pegawai Kementerian Keuangan</strong> dalam bertindak dan berperilaku.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat mengenai subjek sasaran yang terikat pada pedoman Nilai-Nilai Kementerian Keuangan. Kesatuan budaya kerja di Kementerian Keuangan menuntut keterikatan utuh dari setiap insan internal di seluruh jenjang dan unit organisasi. Ketentuan tata nilai tersebut secara tegas dimaksudkan agar dapat digunakan sebagai pedoman bagi seluruh pegawai Kementerian Keuangan dalam bertindak dan berperilaku. Dengan demikian, seluruh pegawai Kementerian Keuangan menjadi subjek sasaran langsungnya.</p><p>Jawaban tepat: <strong>B. Seluruh pegawai Kementerian Keuangan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan pihak rekanan pengadaan di luar struktur kepegawaian internal Kementerian Keuangan.</li><li><strong>C</strong>: Merupakan aparatur instansi daerah yang berpedoman pada Core Values ASN dan budaya kerja daerahnya.</li><li><strong>D</strong>: Merupakan pemangku kepentingan penerima layanan publik, bukan pegawai internal kementerian.</li><li><strong>E</strong>: Merupakan auditor lembaga pengawas eksternal di luar jajaran pegawai Kementerian Keuangan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 6) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pedoman bagi seluruh pegawai Kementerian Keuangan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Seorang pemeriksa menyelesaikan verifikasi berkas secara cermat serta menjaga kesantunan layanan karena menjadikan Nilai-Nilai Kementerian Keuangan sebagai pedoman dalam...", "options": ["Bertindak dan berperilaku", "Merencanakan dan mengawasi", "Berdiskusi dan bersepakat", "Mengamati dan memetakan", "Menilai dan berargumentasi"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pedoman Pegawai dalam Bertindak dan Berperilaku</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Penerapan Nilai-Nilai Kementerian Keuangan digunakan sebagai pedoman bagi seluruh pegawai Kementerian Keuangan <strong>dalam bertindak dan berperilaku</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat sesuai wujud pengamalan nilai oleh pemeriksa tersebut. Penyelesaian verifikasi berkas mencerminkan ketepatan dalam bertindak, sedangkan penjagaan kesantunan layanan mencerminkan keluhuran dalam berperilaku. Sesuai peruntukannya, pedoman tata nilai digunakan bagi seluruh pegawai Kementerian Keuangan dalam bertindak dan berperilaku dengan menerapkan Nilai-Nilai Kementerian Keuangan. Oleh karena itu, dimensi bertindak dan berperilaku menjadi sasaran aktualisasi utamanya.</p><p>Jawaban tepat: <strong>A. Bertindak dan berperilaku</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan sebagian tahapan prinsip manajemen organisasi dari perencanaan hingga pengendalian.</li><li><strong>C</strong>: Merupakan aktivitas perumusan konsensus pada tahap pembentukan nilai menurut Susan M. Heathfield.</li><li><strong>D</strong>: Merupakan cara membaca situasi dan karakteristik pegawai melalui peran budaya organisasi.</li><li><strong>E</strong>: Merupakan sebagian rincian fungsi nilai organisasi sebagai standar kriteria penilaian kinerja.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 6) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>dalam bertindak dan berperilaku</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C4 - Analisis", "question": "Pernyataan berikut yang TIDAK TEPAT mengenai alur pengembangan dan penerapan nilai-nilai organisasi adalah...", "options": ["Diawali pemaparan pengertian dasar nilai organisasi", "Meliputi proses pembentukan dan fungsi nilai", "Internalisasi dilakukan sebelum proses pembentukan", "Menerapkan nilai dalam seluruh kegiatan organisasi", "Menginternalisasi nilai melalui budaya organisasi"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Analisis Alur Pengembangan dan Internalisasi Nilai</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pengembangan nilai-nilai organisasi bergerak urut mulai dari <strong>proses pembentukan</strong>, <strong>fungsi</strong>, hingga <strong>cara menerapkan (menginternalisasi) nilai</strong> dalam seluruh kegiatan organisasi <strong>melalui budaya organisasi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang benar karena memuat pernyataan yang tidak tepat terkait urutan pengembangan nilai organisasi. Secara logis, nilai belum dapat diinternalisasikan ke dalam kegiatan kerja apabila nilai tersebut belum dibentuk dan disepakati terlebih dahulu. Dalam alur pengembangan nilai-nilai organisasi, tahapan dimulai dari proses pembentukan, dilanjutkan fungsi, hingga cara menerapkan atau menginternalisasi nilai melalui budaya organisasi. Dengan demikian, menempatkan internalisasi mendahului proses pembentukan adalah keliru.</p><p>Jawaban tepat: <strong>C. Internalisasi dilakukan sebelum proses pembentukan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pernyataan tepat karena pembahasan tata nilai memaparkan pengertian dasar nilai dan nilai-nilai organisasi di awal.</li><li><strong>B</strong>: Pernyataan tepat karena pengembangan nilai dalam organisasi mencakup proses pembentukan serta fungsi nilai.</li><li><strong>D</strong>: Pernyataan tepat karena penerapan atau internalisasi nilai menjangkau seluruh kegiatan organisasi.</li><li><strong>E</strong>: Pernyataan tepat karena cara menginternalisasi nilai di seluruh kegiatan dilakukan melalui budaya organisasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 6) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>mulai dari proses pembentukan, fungsi • melalui budaya organisasi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Dalam bertindak dan berperilaku melaksanakan tugas sehari-hari, seluruh pegawai Kementerian Keuangan wajib berpedoman dengan menerapkan...", "options": ["Bagan Jabatan Kementerian Keuangan", "Target Kinerja Kementerian Keuangan", "Rencana Kerja Kementerian Keuangan", "Aturan Mutasi Kementerian Keuangan", "Nilai-Nilai Kementerian Keuangan"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penerapan Nilai-Nilai Kementerian Keuangan oleh Pegawai</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Seluruh pegawai Kementerian Keuangan berpedoman dalam bertindak dan berperilaku dengan menerapkan <strong>Nilai-Nilai Kementerian Keuangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat mengenai landasan yang wajib diterapkan pegawai saat bertindak dan berperilaku. Penguasaan konsep nilai dan budaya organisasi bermuara pada pengamalan nyata lima nilai utama institusi di tempat kerja. Secara tegas, pedoman perilaku aparatur digunakan bagi seluruh pegawai Kementerian Keuangan dalam bertindak dan berperilaku dengan menerapkan Nilai-Nilai Kementerian Keuangan. Oleh sebab itu, penerapan Nilai-Nilai Kementerian Keuangan menjadi muara akhir pembinaan tata nilai pegawai.</p><p>Jawaban tepat: <strong>E. Nilai-Nilai Kementerian Keuangan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan pemetaan kedudukan struktural jabatan, bukan pedoman nilai perilaku keseharian.</li><li><strong>B</strong>: Merupakan ukuran capaian hasil kerja tahunan, bukan tata nilai dasar dalam berperilaku.</li><li><strong>C</strong>: Merupakan dokumen perencanaan kegiatan operasional dan penganggaran satuan kerja.</li><li><strong>D</strong>: Merupakan ketentuan perpindahan penugasan wilayah kerja dalam manajemen kepegawaian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 6) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>dalam bertindak dan berperilaku • nilai-nilai Kementerian Keuangan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Menurut Gordon Allport, nilai merupakan suatu keyakinan yang dapat membuat seseorang...", "options": ["Melakukan suatu tindakan berdasarkan pilihannya", "Membedakan hal layak dan dikehendaki kelompok", "Menentukan cara sekaligus tujuan akhir tindakan", "Menakar kadar kepentingan serta ukuran benar-salah", "Mewujudkan karya budaya berharga di masyarakat"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengertian Nilai Menurut Gordon Allport</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sebagaimana dicatat Zakky (2020), menurut <strong>Gordon Allport</strong>, <strong>nilai</strong> adalah suatu <strong>keyakinan</strong> yang dapat membuat seseorang <strong>melakukan tindakan berdasarkan pilihannya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat untuk melengkapi pengertian nilai menurut Gordon Allport. Konsep ini menekankan bahwa keyakinan di dalam diri seseorang berfungsi langsung menggerakkan perbuatan nyata sesuai alternatif yang dipilihnya. Sebagaimana dirumuskan oleh Gordon Allport, nilai adalah suatu keyakinan yang dapat membuat seseorang melakukan tindakan berdasarkan pilihannya. Dengan demikian, keterkaitan antara keyakinan dan tindakan atas pilihan menjadi inti definisi Gordon Allport.</p><p>Jawaban tepat: <strong>A. Melakukan suatu tindakan berdasarkan pilihannya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan pengertian nilai menurut Antony Giddens (1995) mengenai gagasan kelayakan, kehendak, serta baik dan buruk.</li><li><strong>C</strong>: Merupakan pengertian nilai menurut Kluckhohn mengenai konsepsi keinginan penentu cara dan tujuan tindakan.</li><li><strong>D</strong>: Merupakan pengertian nilai menurut Danandjaja mengenai takaran penting-kurang penting serta benar-salah.</li><li><strong>E</strong>: Merupakan pengertian nilai menurut Schaefer dan Lamm (1998) yang mengaitkan gagasan nilai dengan karya budaya.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 7) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Gordon Allport • melakukan tindakan berdasarkan pilihannya</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Definisi nilai sebagai gagasan seseorang maupun kelompok mengenai apa yang layak, dikehendaki, serta baik dan buruk merupakan...", "options": ["Pandangan Gordon Allport", "Pandangan Kluckhohn", "Pandangan Antony Giddens", "Pandangan Danandjaja", "Pandangan Schaefer dan Lamm"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengertian Nilai Menurut Antony Giddens</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Menurut <strong>Antony Giddens (1995)</strong>, <strong>nilai</strong> adalah suatu <strong>gagasan</strong> yang dimiliki <strong>seseorang maupun kelompok</strong> mengenai <strong>apa yang layak, apa yang dikehendaki, serta apa yang baik dan buruk</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat karena merujuk pada tokoh pencetus definisi nilai sebagai gagasan individu maupun kelompok. Berbeda dari ahli lain yang menyoroti ranah individu semata, tokoh ini memperluas kepemilikan gagasan nilai hingga ke tingkat kelompok sosial. Menurut Antony Giddens (1995), nilai adalah gagasan milik seseorang maupun kelompok mengenai apa yang layak, dikehendaki, serta baik dan buruk. Oleh sebab itu, rumusan tersebut merupakan pandangan Antony Giddens.</p><p>Jawaban tepat: <strong>C. Pandangan Antony Giddens</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Gordon Allport mendefinisikan nilai sebagai keyakinan yang membuat seseorang bertindak berdasarkan pilihannya.</li><li><strong>B</strong>: Kluckhohn mendefinisikan nilai sebagai konsepsi apa yang diinginkan yang memengaruhi cara dan tujuan.</li><li><strong>D</strong>: Danandjaja mendefinisikan nilai sebagai pengertian mengenai lebih-kurang penting, lebih-kurang baik, dan benar-salah.</li><li><strong>E</strong>: Schaefer dan Lamm (1998) mengaitkan penilaian baik, layak, dan diinginkan dengan pembentukan karya budaya.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 7) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Antony Giddens (1995) • apa yang layak, apa yang dikehendaki</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Pegawai yang menyelaraskan prosedur kerja jujur dengan target layanan menerapkan konsep nilai menurut Kluckhohn, yakni penentuan tindakan terhadap...", "options": ["Hal lebih penting dan kurang penting", "Gagasan kelayakan individu dan kelompok", "Wujud karya budaya warga masyarakat", "Pilihan bebas berdasarkan keyakinan diri", "Cara dan tujuan yang ingin dicapai"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengertian Nilai Menurut Kluckhohn</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Menurut <strong>Kluckhohn</strong>, <strong>nilai</strong> adalah suatu <strong>konsepsi dari apa yang diinginkan</strong> dan memengaruhi seseorang dalam menentukan tindakan terhadap <strong>cara dan juga tujuan yang ingin dicapai</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat karena menggambarkan pengaruh nilai terhadap metode pelaksanaan sekaligus sasaran akhir pekerjaan. Dalam ilustrasi tersebut, prosedur kerja jujur mencerminkan pemilihan cara, sedangkan target layanan mencerminkan tujuan kerja pegawai. Sesuai pandangan Kluckhohn, nilai merupakan konsepsi dari apa yang diinginkan dan memengaruhi seseorang dalam menentukan tindakan terhadap cara serta tujuan yang ingin dicapai. Dengan demikian, praktik kerja tersebut mencerminkan rumusan nilai menurut Kluckhohn.</p><p>Jawaban tepat: <strong>E. Cara dan tujuan yang ingin dicapai</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan titik tekan pengertian nilai menurut Danandjaja mengenai gradasi kepentingan suatu hal.</li><li><strong>B</strong>: Merupakan inti pengertian nilai menurut Antony Giddens (1995) mengenai gagasan individu maupun kelompok.</li><li><strong>C</strong>: Merupakan unsur pengertian nilai menurut Schaefer dan Lamm (1998) mengenai pembentukan karya budaya.</li><li><strong>D</strong>: Merupakan fokus pengertian nilai menurut Gordon Allport mengenai tindakan berdasarkan pilihan keyakinan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 7) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Menurut Kluckhohn • cara dan juga tujuan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Menurut Danandjaja, nilai adalah pengertian seseorang akan sesuatu yang lebih atau kurang penting, lebih atau kurang baik, serta...", "options": ["Apa yang layak dan apa tidak layak", "Apa yang benar dan apa yang salah", "Apa yang dikehendaki oleh kelompok", "Cara dan tujuan yang ingin dicapai", "Hal berharga pada karya kebudayaan"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengertian Nilai Menurut Danandjaja</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Menurut <strong>Danandjaja</strong>, <strong>nilai</strong> adalah suatu <strong>pengertian</strong> yang dimiliki seseorang akan sesuatu yang <strong>lebih penting maupun kurang penting</strong>, <strong>lebih baik dan kurang baik</strong>, dan juga <strong>apa yang benar dan apa yang salah</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat untuk melengkapi unsur ketiga dalam pengertian nilai menurut Danandjaja. Tokoh ini memandang nilai sebagai pemahaman komparatif seseorang ketika menimbang bobot kepentingan, kebaikan, hingga kebenaran suatu perkara. Sebagaimana dikemukakan Danandjaja, nilai mencakup pengertian mengenai sesuatu yang lebih atau kurang penting, lebih atau kurang baik, dan juga apa yang benar dan apa yang salah. Oleh karena itu, dikotomi benar dan salah melengkapi definisi Danandjaja secara utuh.</p><p>Jawaban tepat: <strong>B. Apa yang benar dan apa yang salah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Istilah layak atau tidak layak digunakan dalam rumusan Antony Giddens serta Schaefer dan Lamm.</li><li><strong>C</strong>: Gagasan mengenai apa yang dikehendaki kelompok merupakan rumusan pengertian nilai menurut Antony Giddens (1995).</li><li><strong>D</strong>: Penentuan tindakan terhadap cara dan tujuan yang ingin dicapai merupakan rumusan nilai menurut Kluckhohn.</li><li><strong>E</strong>: Acuan hal berharga dan pembentukan karya budaya merupakan rumusan Schaefer dan Lamm (1998).</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 7) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Menurut Danandjaja • lebih penting maupun kurang penting • benar dan apa yang salah</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C4 - Analisis", "question": "Berikut ini merupakan unsur pengertian nilai menurut Richard T. Schaefer dan Robert P. Lamm (1998), KECUALI anggapan mengenai...", "options": ["Sesuatu yang baik atau tidak baik", "Sesuatu yang layak atau tidak layak", "Apa yang akan menjadi karya budaya", "Penentuan cara teknis meraih tujuan", "Hal berharga dan penting bagi manusia"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengertian Nilai Menurut Schaefer dan Lamm</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Menurut <strong>Richard T. Schaefer dan Robert P. Lamm (1998)</strong>, nilai mencakup sesuatu yang dianggap <strong>baik/tidak baik</strong>, <strong>layak/tidak layak</strong>, <strong>diinginkan/tidak diinginkan</strong>, apa yang menjadi <strong>karya budaya</strong>, serta hal <strong>berharga dan penting</strong> bagi individu maupun masyarakat.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat sebagai pengecualian karena unsur penentuan cara dan tujuan tindakan berasal dari definisi ahli lain. Pemilahan ini menuntut ketelitian membedakan cakupan kultural Schaefer dan Lamm dari konsepsi tindakan milik Kluckhohn. Menurut Schaefer dan Lamm (1998), nilai mencakup anggapan baik-tidak baik, layak-tidak layak, diinginkan-tidak diinginkan, karya budaya, serta hal berharga bagi manusia, sedangkan penentuan cara dan tujuan adalah definisi Kluckhohn. Dengan demikian, Opsi D bukan bagian rumusan Schaefer dan Lamm.</p><p>Jawaban tepat: <strong>D. Penentuan cara teknis meraih tujuan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Termasuk unsur eksplisit definisi nilai menurut Richard T. Schaefer dan Robert P. Lamm (1998).</li><li><strong>B</strong>: Termasuk unsur eksplisit mengenai kelayakan dalam rumusan Schaefer dan Lamm (1998).</li><li><strong>C</strong>: Termasuk ciri khas definisi Schaefer dan Lamm (1998) yang menghubungkan gagasan nilai dengan karya budaya.</li><li><strong>E</strong>: Termasuk penegasan Schaefer dan Lamm (1998) bahwa nilai mengacu pada hal berharga bagi individu maupun masyarakat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 7) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Richard T. Schaefer • karya budaya berdasar gagasan-gagasan tersebut • dianggap berharga dan penting</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C4 - Analisis", "question": "Dibandingkan rumusan Antony Giddens, definisi nilai menurut Schaefer dan Lamm (1998) menambahkan kaitan gagasan penilaian dengan pembentukan...", "options": ["Karya budaya yang berharga bagi manusia", "Pilihan tindakan atas keyakinan pribadi", "Cara kerja beserta tujuan akhir individu", "Ukuran benar dan salah suatu perbuatan", "Struktur pembagian wewenang unit kerja"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Perbandingan Definisi Nilai Schaefer-Lamm dan Giddens</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Selain memuat aspek baik, layak, dan diinginkan seperti <strong>Antony Giddens (1995)</strong>, <strong>Schaefer dan Lamm (1998)</strong> menambahkan aspek <strong>apa yang akan menjadi karya budaya</strong> serta hal <strong>berharga dan penting</strong> pada kehidupan manusia.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat karena menunjukkan perluasan konsep kebudayaan yang membedakan definisi Schaefer dan Lamm dari Giddens. Meskipun keduanya menyinggung kelayakan, keinginan, dan kebaikan, Giddens memfokuskan batasannya pada gagasan individu maupun kelompok. Sebaliknya, Schaefer dan Lamm (1998) mengaitkan gagasan tersebut dengan apa yang akan menjadi karya budaya serta hal berharga bagi individu ataupun anggota masyarakat. Oleh sebab itu, unsur karya budaya menjadi pembeda utama rumusan Schaefer dan Lamm.</p><p>Jawaban tepat: <strong>A. Karya budaya yang berharga bagi manusia</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan inti pengertian nilai menurut Gordon Allport mengenai tindakan berdasarkan pilihan.</li><li><strong>C</strong>: Merupakan inti pengertian nilai menurut Kluckhohn mengenai penentuan cara dan tujuan tindakan.</li><li><strong>D</strong>: Merupakan bagian pengertian nilai menurut Danandjaja mengenai apa yang benar dan salah.</li><li><strong>E</strong>: Berkaitan dengan penataan manajemen keorganisasian, bukan perluasan definisi nilai Schaefer dan Lamm.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 7) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>karya budaya berdasar gagasan-gagasan tersebut • sebagai individu ataupun sebagai anggota masyarakat</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Pembicaraan mengenai nilai menjadi hal yang menarik karena berkaitan erat dengan keyakinan serta cara manusia...", "options": ["Menyusun aturan tertulis di masyarakat", "Membagi wewenang kerja di organisasi", "Menciptakan benda fisik bernilai jual", "Memperoleh pengakuan kedudukan sosial", "Mengupayakan tercapainya tujuan hidup"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Keterkaitan Nilai dengan Tujuan Hidup Manusia</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pembicaraan mengenai <strong>nilai</strong> merupakan hal yang menarik karena berkaitan dengan <strong>keyakinan</strong> dan <strong>cara kita sebagai manusia untuk mencapai tujuan hidup</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat karena menjelaskan alasan mendasar mengapa topik nilai sangat relevan bagi kehidupan manusia. Nilai tidak berhenti sebagai wacana pemikiran, melainkan memandu langkah nyata seseorang dalam mengarahkan hidupnya. Secara konseptual ditegaskan bahwa pembicaraan mengenai nilai menarik karena berkaitan langsung dengan keyakinan dan cara kita sebagai manusia untuk mencapai tujuan hidup. Dengan demikian, pencapaian tujuan hidup merupakan muara keterkaitan keyakinan nilai pada diri manusia.</p><p>Jawaban tepat: <strong>E. Mengupayakan tercapainya tujuan hidup</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penyusunan aturan tertulis berkaitan dengan regulasi formal, bukan alasan utama ketertarikan kajian nilai.</li><li><strong>B</strong>: Pembagian wewenang kerja merupakan prinsip struktur manajemen organisasi, bukan tujuan hidup manusia secara umum.</li><li><strong>C</strong>: Penciptaan benda bernilai jual berfokus pada motif ekonomi materiil, bukan esensi dasar pembicaraan nilai.</li><li><strong>D</strong>: Pengakuan kedudukan sosial berkaitan dengan status kemasyarakatan, bukan rumusan keterkaitan keyakinan nilai.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 7) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pembicaraan mengenai nilai • keyakinan dan cara kita sebagai manusia • mencapai tujuan hidup</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Di dalam konteks sebuah organisasi, nilai akan menjadi keyakinan bersama yang dipegang oleh...", "options": ["Jajaran eksekutif puncak dan manajer menengah", "Pejabat pengawas dan petugas layanan teknis", "Setiap anggota dan para pemangku kepentingan", "Perumus strategi dan pengelola kepegawaian", "Pengguna layanan dan mitra kerja eksternal"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Subjek Pemilik Keyakinan Nilai dalam Organisasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam organisasi, <strong>nilai</strong> akan menjadi <strong>keyakinan</strong> bagi <strong>setiap anggota organisasi</strong> dan <strong>para pemangku kepentingan</strong> organisasi tersebut.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat karena mencakup seluruh subjek internal maupun pihak berkepentingan yang terikat pada nilai organisasi. Keberhasilan tata kelola nilai menuntut kesatuan keyakinan lintas jenjang dan lintas pemangku kepentingan agar arah gerak lembaga selaras. Dalam konsep dasar nilai keorganisasian, dinyatakan bahwa nilai akan menjadi keyakinan bagi setiap anggota organisasi dan para pemangku kepentingan organisasi tersebut. Oleh karena itu, cakupan subjek pada Opsi C adalah yang paling akurat.</p><p>Jawaban tepat: <strong>C. Setiap anggota dan para pemangku kepentingan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Belum mencakup staf pelaksana serta pemangku kepentingan organisasi di luar jajaran pimpinan dan manajer.</li><li><strong>B</strong>: Belum mencakup unsur pimpinan puncak maupun pemangku kepentingan lain yang terkait dengan organisasi.</li><li><strong>D</strong>: Terlalu sempit karena mengerucutkan kepemilikan nilai pada fungsi perencana strategi dan pengelola SDM.</li><li><strong>E</strong>: Belum mengikutsertakan setiap anggota internal organisasi sebagai subjek utama pengemban keyakinan nilai.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 7) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>keyakinan bagi setiap anggota organisasi • pemangku kepentingan organisasi tersebut</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Seluruh elemen kantor pelayanan mengacu pada nilai instansi saat bersikap dan bertugas karena nilai organisasi berfungsi sebagai...", "options": ["Standar pemetaan jenjang jabatan struktural", "Pedoman berperilaku, bekerja, dan beraktivitas", "Rujukan penyusunan laporan anggaran tahunan", "Instrumen pembagian wewenang administrasi kerja", "Dasar penentuan besaran tunjangan kinerja ASN"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Fungsi Nilai Organisasi sebagai Pedoman (Guidance)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Nilai organisasi</strong> menjadi <strong>pedoman (<em>guidance</em>)</strong> seluruh elemen organisasi dalam <strong>berperilaku, bekerja, dan melaksanakan aktivitas</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat karena menerjemahkan fungsi nilai organisasi sebagai penuntun sikap dan pelaksanaan tugas harian pegawai. Ketika seluruh elemen kantor merujuk pada nilai instansi, nilai tersebut bekerja aktif mengarahkan interaksi maupun penyelesaian pekerjaan. Secara konseptual ditegaskan bahwa nilai organisasi menjadi pedoman (guidance) seluruh elemen organisasi dalam berperilaku, bekerja, dan melaksanakan aktivitas. Dengan demikian, fungsi pedoman pada ketiga ranah tersebut tercermin tepat pada Opsi B.</p><p>Jawaban tepat: <strong>B. Pedoman berperilaku, bekerja, dan beraktivitas</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pemetaan jenjang jabatan merupakan fungsi penataan struktur organisasi dan manajemen kepegawaian.</li><li><strong>C</strong>: Penyusunan laporan anggaran tahunan merupakan fungsi teknis pengelolaan akuntansi dan keuangan.</li><li><strong>D</strong>: Pembagian wewenang administrasi berkaitan dengan pengorganisasian tugas struktural, bukan definisi guidance nilai.</li><li><strong>E</strong>: Penentuan besaran tunjangan kinerja berkaitan dengan sistem remunerasi dan kompensasi pegawai.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 7) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menjadi pedoman (guidance) • berperilaku, bekerja, dan melaksanakan aktivitas</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Pengamalan nilai sebagai pedoman kerja oleh seluruh elemen kantor perbendaharaan pada akhirnya diarahkan agar...", "options": ["Publikasi profil instansi tersebar luas ke daerah", "Tata naskah administrasi tersusun seragam di unit", "Agenda sosialisasi tata nilai terlaksana tiap periode", "Tujuan tercapai dan manfaatnya dinikmati semua pihak", "Riwayat pendirian lembaga tercatat rapi dalam buku"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Muara Penerapan Nilai Organisasi bagi Seluruh Pihak</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Nilai organisasi menjadi pedoman (<em>guidance</em>) seluruh elemen organisasi dalam berperilaku, bekerja, dan melaksanakan aktivitas <strong>sehingga tujuan organisasi tercapai dan dapat dinikmati manfaatnya oleh seluruh pihak</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat karena menunjukkan hasil akhir dari penerapan nilai organisasi sebagai pedoman bersama. Kepatuhan seluruh elemen kantor terhadap nilai instansi bukan bertujuan administratif belaka, melainkan berorientasi pada pencapaian kinerja dan kemanfaatan luas. Sesuai konsep nilai keorganisasian, fungsi nilai sebagai pedoman ditujukan agar tujuan organisasi tercapai dan dapat dinikmati manfaatnya oleh seluruh pihak. Oleh sebab itu, ketercapaian tujuan dan pemerataan manfaat pada Opsi D adalah muara yang tepat.</p><p>Jawaban tepat: <strong>D. Tujuan tercapai dan manfaatnya dinikmati semua pihak</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Publikasi profil instansi merupakan kegiatan kehumasan awal, bukan muara utama penerapan pedoman nilai.</li><li><strong>B</strong>: Keseragaman tata naskah administrasi merupakan ketertiban persuratan teknis di lingkungan internal.</li><li><strong>C</strong>: Pelaksanaan sosialisasi berkala merupakan sarana pembinaan, bukan tujuan akhir dari pengamalan nilai organisasi.</li><li><strong>E</strong>: Pencatatan riwayat pendirian lembaga merupakan dokumentasi historis, bukan dampak pencapaian tujuan bersama.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 7) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>sehingga tujuan organisasi tercapai • dinikmati manfaatnya oleh seluruh pihak</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Menurut Koentjaraningrat, nilai adalah bentuk budaya pedoman masyarakat yang bisa dikehendaki atau dibenci tergantung pada...", "options": ["Nilai jual hasil karya fisik masyarakat", "Ketegasan sanksi aturan tertulis pemerintah", "Anggapan baik dan buruk gagasan masyarakat", "Kesesuaian cara kerja dengan target individu", "Kekuatan keyakinan pribadi dalam memilih aksi"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengertian Nilai Menurut Koentjaraningrat</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Menurut <strong>Koentjaraningrat</strong>, <strong>nilai</strong> adalah suatu <strong>bentuk budaya</strong> yang berfungsi sebagai <strong>pedoman bagi setiap manusia dalam masyarakat</strong>, yang bisa dikehendaki atau dibenci tergantung pada <strong>anggapan baik dan buruk dalam gagasan yang berkembang di masyarakat</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat karena menjelaskan penentu diterima atau dibencinya suatu bentuk budaya menurut Koentjaraningrat. Sebagai pedoman hidup bermasyarakat, posisi suatu wujud budaya sangat dipengaruhi oleh cara pandang kolektif warga sekitarnya. Koentjaraningrat menegaskan bahwa bentuk budaya tersebut dapat dikehendaki maupun dibenci bergantung pada anggapan baik dan buruk dalam gagasan yang berkembang di masyarakat. Dengan demikian, faktor penentu utamanya terletak pada anggapan baik dan buruk masyarakat.</p><p>Jawaban tepat: <strong>C. Anggapan baik dan buruk gagasan masyarakat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penilaian bentuk budaya menurut Koentjaraningrat bertumpu pada gagasan baik-buruk, bukan kalkulasi nilai jual ekonomi.</li><li><strong>B</strong>: Sanksi aturan tertulis berkaitan dengan instrumen hukum formal negara, bukan gagasan kultural Koentjaraningrat.</li><li><strong>D</strong>: Kesesuaian cara dan tujuan tindakan merupakan fokus konsepsi nilai menurut Kluckhohn.</li><li><strong>E</strong>: Keyakinan pribadi dalam memilih tindakan merupakan fokus pengertian nilai menurut Gordon Allport.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 8) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Menurut Koentjaraningrat • bentuk budaya yang memiliki fungsi • anggapan baik dan buruk</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C4 - Analisis", "question": "Berdasarkan rangkuman pendapat para ahli manajemen, pengertian nilai mencakup berbagai unsur utama berikut ini, KECUALI...", "options": ["Keyakinan tentang layak, baik, dan penting", "Pengaruh terhadap tindakan serta tujuan hidup", "Kaitan kebudayaan berupa hasil karya masyarakat", "Pedoman bagi manusia di dalam masyarakat", "Garis silsilah kekerabatan warga di masyarakat"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Rangkuman Pengertian Nilai Menurut Para Ahli Manajemen</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Rangkuman pengertian <strong>nilai</strong> menurut para ahli manajemen adalah <strong>keyakinan seseorang</strong> tentang layak/tidak, baik/buruk, penting/tidak, yang memengaruhi <strong>tindakan dan tujuan</strong>, dalam hal <strong>kebudayaan (hasil karya)</strong> individu maupun masyarakat, sebagai <strong>pedoman bagi manusia dalam masyarakat</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat sebagai pengecualian karena garis silsilah kekerabatan tidak termasuk dalam sintesis pengertian nilai menurut para ahli manajemen. Rangkuman tersebut menyatukan unsur keyakinan, tindakan, tujuan, hasil karya kebudayaan, hingga fungsi pedoman sosial dari para tokoh sebelumnya. Secara eksplisit, sintesis pengertian nilai memuat aspek kelayakan-kebaikan-kepentingan, tindakan dan tujuan, hasil karya budaya, serta pedoman bermasyarakat tanpa menyinggung silsilah kekerabatan genealogis. Oleh karena itu, Opsi E berada di luar cakupan rangkuman nilai tersebut.</p><p>Jawaban tepat: <strong>E. Garis silsilah kekerabatan warga di masyarakat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Termasuk unsur pembuka rangkuman nilai mengenai apa yang dianggap layak, baik, dan penting.</li><li><strong>B</strong>: Termasuk unsur rangkuman nilai yang memengaruhi tindakan yang dilakukan serta tujuan yang ingin dicapai.</li><li><strong>C</strong>: Termasuk unsur rangkuman nilai dalam hal kebudayaan (hasil karya) individu maupun anggota masyarakat.</li><li><strong>D</strong>: Termasuk unsur penutup rangkuman nilai yang berfungsi menjadi pedoman bagi manusia dalam masyarakat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 8) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>ahli manajemen tentang nilai • kebudayaan (hasil karya) • pedoman bagi manusia dalam masyarakat</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Ketika sejumlah individu dengan nilai pribadi beragam berkumpul membentuk tim kerja baru, para stakeholders perlu...", "options": ["Memetakan latar belakang pendidikan tiap anggota", "Menyepakati nilai-nilai sebagai pedoman bersama", "Merancang penataan ruang kerja yang seragam", "Menyusun jadwal rotasi penugasan lintas bagian", "Mengadakan pelatihan aplikasi kerja bagi staf"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kesepakatan Nilai Bersama oleh Stakeholders Organisasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Karena nilai dasar semula masih berupa <strong>nilai pribadi masing-masing individu</strong>, dalam konteks organisasi para <strong><em>stakeholders</strong></em> yang berkumpul dalam kelompok kecil atau besar perlu <strong>menyepakati nilai-nilai yang menjadi pedoman bersama</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat karena menjembatani pergeseran dari nilai pribadi individu menuju kesatuan arah di dalam organisasi. Sekumpulan orang dengan latar belakang personal beragam membutuhkan konsensus acuan agar mampu bergerak selaras sebagai sebuah lembaga. Dalam konteks organisasi, para stakeholders yang berkumpul dalam kelompok kecil maupun besar perlu menyepakati nilai-nilai yang menjadi pedoman bersama. Dengan demikian, penyepakatan nilai bersama merupakan langkah utama pembentukan organisasi yang utuh.</p><p>Jawaban tepat: <strong>B. Menyepakati nilai-nilai sebagai pedoman bersama</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pemetaan latar belakang pendidikan berkaitan dengan penempatan kualifikasi teknis SDM, bukan penyatuan nilai pribadi.</li><li><strong>C</strong>: Penataan ruang kerja fisik berkaitan dengan sarana perkantoran, bukan kesepakatan normatif antar-stakeholders.</li><li><strong>D</strong>: Penyusunan jadwal rotasi tugas merupakan pengaturan operasional kepegawaian, bukan perumusan pedoman nilai.</li><li><strong>E</strong>: Pelatihan aplikasi kerja meningkatkan keterampilan teknis pegawai, belum menyatukan keragaman nilai individu.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 8) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>nilai pribadi masing-masing individu • stakeholders dalam organisasi • menyepakati nilai-nilai yang menjadi pedoman bersama</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Penyepakatan nilai bersama oleh para stakeholders bertujuan agar kelompok yang membentuk organisasi tersebut dapat...", "options": ["Tetap eksis, kokoh, dan berkembang terarah", "Memiliki struktur birokrasi besar di daerah", "Menyeragamkan jenjang pendidikan para staf", "Mempercepat proses pencatatan arsip harian", "Mengurangi tahapan verifikasi berkas kerja"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuan Penyepakatan Nilai Bersama bagi Organisasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Para <em>stakeholders</em> menyepakati nilai-nilai yang menjadi pedoman bersama agar kelompok yang membentuk organisasi <strong>tetap eksis, tetap kokoh dan terus berkembang terarah</strong> sesuai nilai yang disepakati.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat karena menyebutkan tiga tujuan utama mengapa kesepakatan nilai bersama diperlukan oleh sebuah organisasi. Tanpa pedoman bersama yang disepakati, kumpulan pemangku kepentingan akan kehilangan daya rekat serta arah pertumbuhan kelembagaannya. Secara eksplisit dijelaskan bahwa penyepakatan nilai bersama bertujuan agar organisasi tetap eksis, tetap kokoh, dan terus berkembang terarah sesuai nilai-nilai organisasi yang disepakati. Oleh sebab itu, Opsi A merangkum tujuan tersebut secara presisi.</p><p>Jawaban tepat: <strong>A. Tetap eksis, kokoh, dan berkembang terarah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Perluasan ukuran birokrasi di daerah berkaitan dengan ekspansi struktural, bukan tujuan hakiki kesepakatan nilai.</li><li><strong>C</strong>: Penyeragaman jenjang pendidikan berkaitan dengan kualifikasi formal kepegawaian, bukan ketahanan eksistensi nilai organisasi.</li><li><strong>D</strong>: Percepatan pencatatan arsip harian merupakan efisiensi ketatausahaan teknis di unit kerja.</li><li><strong>E</strong>: Pengurangan tahapan verifikasi berkas berkaitan dengan penyederhanaan prosedur operasional, bukan tujuan penyepakatan nilai.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 8) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>tetap eksis, tetap kokoh • terus berkembang terarah</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan kesepakatan nilai bersama, sebuah organisasi dapat memproklamasikan nilai-nilai organisasinya menjadi...", "options": ["Lampiran laporan keuangan tahunan satuan kerja", "Dokumen kajian akademik pimpinan tingkat pusat", "Catatan sejarah pendirian awal suatu instansi", "Pedoman bersama bagi seluruh anggota organisasi", "Rujukan penyusunan pagu anggaran belanja tahunan"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Proklamasi Nilai Organisasi sebagai Pedoman Bersama</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Setelah nilai disepakati, sebuah organisasi dapat <strong>memproklamasikan nilai-nilai organisasinya</strong> menjadi sebuah <strong>pedoman (<em>guidance</em>) bersama</strong> untuk <strong>semua anggota organisasi atau seluruh pemangku kepentingan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat karena menjelaskan kedudukan resmi nilai organisasi setelah disepakati oleh para pemangku kepentingan. Pengumuman atau proklamasi tata nilai dilakukan agar rumusan tersebut mengikat seluruh unsur lembaga secara terbuka dalam bekerja. Sesuai konsep pembentukan nilai kelembagaan, sebuah organisasi dapat memproklamasikan nilai-nilai organisasinya menjadi pedoman (guidance) bersama untuk semua anggota organisasi atau seluruh pemangku kepentingan. Dengan demikian, fungsi pedoman bersama pada Opsi D adalah jawaban yang akurat.</p><p>Jawaban tepat: <strong>D. Pedoman bersama bagi seluruh anggota organisasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Nilai organisasi diproklamasikan sebagai tuntunan perilaku bersama, bukan pelengkap laporan akuntansi keuangan.</li><li><strong>B</strong>: Nilai organisasi berlaku mengikat seluruh anggota dan pemangku kepentingan, bukan dokumen kajian pimpinan pusat.</li><li><strong>C</strong>: Nilai organisasi berperan aktif memandu aktivitas berjalan, bukan arsip sejarah masa lalu semata.</li><li><strong>E</strong>: Penyusunan pagu anggaran belanja merupakan proses perencanaan finansial, bukan hakikat proklamasi guidance nilai.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 8) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>memproklamasikan nilai-nilai organisasinya • pedoman (guidance) bersama</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Ditinjau dari karakteristik dasarnya, keberadaan nilai yang tidak berwujud fisik namun hadir nyata menunjukkan sifat nilai sebagai...", "options": ["Sifat normatif yang memuat suatu keharusan", "Daya dorong penggerak motivasi kerja insan", "Realitas abstrak dalam kehidupan manusia", "Wujud karya fisik peninggalan masyarakat", "Aturan tertulis pengikat struktur jabatan"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Karakteristik Nilai sebagai Realitas Abstrak</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dari segi karakteristiknya, sifat pertama nilai adalah <strong>nilai merupakan suatu realitas abstrak dan ada dalam kehidupan manusia</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat karena menggambarkan hakikat wujud nilai yang tidak dapat disentuh indra fisik namun nyata eksistensinya. Nilai hidup di dalam kesadaran batin dan dirasakan kehadirannya melalui pertimbangan serta tindakan manusia sehari-hari. Berdasarkan rincian karakteristik nilai, sifat pertama menegaskan bahwa nilai merupakan suatu realitas abstrak dan ada dalam kehidupan manusia. Oleh sebab itu, rumusan realitas abstrak dalam kehidupan manusia pada Opsi C sangat tepat.</p><p>Jawaban tepat: <strong>C. Realitas abstrak dalam kehidupan manusia</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan karakteristik kedua nilai mengenai kandungan harapan, cita-cita, dan keharusan ideal (das sollen).</li><li><strong>B</strong>: Merupakan karakteristik ketiga nilai mengenai fungsi nilai sebagai motivator dengan manusia selaku pendukungnya.</li><li><strong>D</strong>: Karya fisik merupakan hasil kebudayaan konkret, sedangkan nilai sendiri berkarakteristik sebagai realitas abstrak.</li><li><strong>E</strong>: Aturan tertulis struktur jabatan merupakan dokumen birokrasi formal, bukan sifat ontologis dasar dari nilai.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 8) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>merupakan suatu realitas abstrak • ada dalam kehidupan manusia</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Piagam layanan pajak memuat harapan, cita-cita, dan keharusan berintegritas yang mencerminkan karakteristik nilai bersifat ideal atau...", "options": ["Sifat normatif (das sollen)", "Realitas kebendaan kasatmata", "Daya dorong (motivator)", "Karya budaya masyarakat", "Kesepakatan struktur kerja"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Karakteristik Nilai yang Bersifat Normatif (Das Sollen)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Karakteristik kedua nilai adalah <strong>nilai memiliki sifat normatif</strong>, artinya nilai mengandung <strong>harapan, cita-cita, dan suatu keharusan</strong> sehingga nilai memiliki <strong>sifat ideal (<em>das sollen</em>)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat karena mengaitkan kandungan harapan, cita-cita, dan keharusan pada piagam layanan dengan istilah karakteristik normatif. Muatan tersebut bukan sebatas memotret kenyataan apa adanya, melainkan menetapkan standar ideal tentang apa yang semestinya diwujudkan pegawai. Sesuai karakteristik kedua nilai, nilai memiliki sifat normatif karena mengandung harapan, cita-cita, dan keharusan sehingga bersifat ideal (das sollen). Dengan demikian, piagam layanan tersebut mencerminkan sifat normatif (das sollen).</p><p>Jawaban tepat: <strong>A. Sifat normatif (das sollen)</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Berkebalikan dengan karakteristik pertama nilai yang justru merupakan suatu realitas abstrak tak kasatmata.</li><li><strong>C</strong>: Merupakan karakteristik ketiga nilai yang menyoroti fungsi penggerak tindakan dengan manusia sebagai pendukung nilai.</li><li><strong>D</strong>: Merupakan pengertian nilai sebagai bentuk budaya atau hasil karya dalam perspektif sosiologi-antropologi.</li><li><strong>E</strong>: Merupakan pengaturan pembagian tugas organisasi, bukan istilah untuk sifat ideal harapan dan keharusan nilai.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 8) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>memiliki sifat normatif • harapan, cita-cita, dan suatu keharusan • sifat ideal (das sollen)</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Semangat profesionalisme menggerakkan petugas perbendaharaan bekerja penuh dedikasi, yang menunjukkan fungsi nilai sebagai motivator dan manusia sebagai...", "options": ["Perancang aturan di kantor", "Pengawas kepatuhan pegawai", "Pencipta karya benda fisik", "Penilai jenjang jabatan staf", "Pendukung nilai dalam hidup"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Karakteristik Nilai sebagai Daya Dorong dan Manusia Pendukung Nilai</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Karakteristik ketiga dari sifat dasar nilai adalah <strong>nilai berfungsi sebagai daya dorong/motivator</strong> dan <strong>manusia adalah pendukung nilai</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat karena melengkapi kedudukan manusia terhadap nilai yang menggerakkan dedikasi kerjanya. Nilai profesionalisme memberi energi motivasi, sementara petugas perbendaharaan tampil sebagai aktor yang menghidupkan dan mengaktualisasikan nilai tersebut. Sebagaimana ditegaskan pada karakteristik ketiga sifat nilai, nilai berfungsi sebagai daya dorong atau motivator dan manusia adalah pendukung nilai. Oleh karena itu, kedudukan manusia pada situasi kerja tersebut adalah sebagai pendukung nilai.</p><p>Jawaban tepat: <strong>E. Pendukung nilai dalam hidup</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Perancang aturan kantor merujuk pada fungsi penyusunan regulasi internal, bukan pasangan konsep daya dorong nilai.</li><li><strong>B</strong>: Pengawas kepatuhan merujuk pada fungsi pengendalian internal birokrasi, bukan karakteristik ketiga sifat nilai.</li><li><strong>C</strong>: Pencipta karya fisik berkaitan dengan wujud kebudayaan materiil, bukan relasi motivator dan pendukung nilai.</li><li><strong>D</strong>: Penilai jenjang jabatan berkaitan dengan administrasi karier kepegawaian, bukan sifat dasar nilai pada manusia.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 8) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>daya dorong/motivator • manusia adalah pendukung nilai</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Peralihan layanan manual menuju layanan digital memperkaya pemaknaan nilai kecepatan kerja pegawai karena nilai memiliki sifat...", "options": ["Menetap permanen lintas generasi zaman", "Selalu berkembang seperti kehidupan manusia", "Berwujud fisik nyata di ruang pelayanan", "Mengatur rincian teknis anggaran kantor", "Memisahkan kepentingan individu dan kelompok"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Karakteristik Perkembangan Nilai Seiring Kehidupan Manusia</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Karakteristik keempat dari sifat nilai menyatakan bahwa <strong>nilai akan selalu berkembang sedemikian rupa, seperti halnya kehidupan manusia</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat karena menjelaskan mengapa pemaknaan nilai kecepatan layanan ikut tumbuh seiring modernisasi cara kerja manusia. Nilai tidak bersifat beku pada satu zaman, melainkan menyesuaikan pengejawantahannya dengan dinamika peradaban masyarakat. Sesuai karakteristik keempat sifat dasar nilai, nilai akan selalu berkembang sedemikian rupa seperti halnya kehidupan manusia. Dengan demikian, pengayaan makna nilai di era layanan digital membuktikan sifat perkembangan nilai tersebut.</p><p>Jawaban tepat: <strong>B. Selalu berkembang seperti kehidupan manusia</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena karakteristik nilai justru dinamis dan terus berkembang seiring perubahan kehidupan manusia.</li><li><strong>C</strong>: Kurang tepat karena nilai merupakan realitas abstrak, bukan benda yang berwujud fisik nyata.</li><li><strong>D</strong>: Kurang tepat karena pengaturan teknis anggaran merupakan prosedur manajerial, bukan sifat dasar perkembangan nilai.</li><li><strong>E</strong>: Kurang tepat karena dalam organisasi nilai justru menyatukan kepentingan pemangku kepentingan menjadi pedoman bersama.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 8) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>selalu berkembang sedemikian rupa • seperti halnya kehidupan manusia</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Organizational values merupakan keyakinan dan kepercayaan pemangku kepentingan dalam organisasi yang mengarahkan pilihan serta digunakan untuk...", "options": ["Merencanakan, membiayai, dan mengaudit kas", "Merekrut, melatih, dan mempromosikan staf", "Mencatat, mengarsipkan, dan melaporkan data", "Berpikir, bertindak, dan bertingkah laku", "Mengawasi, memeriksa, dan memberi penilaian"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengertian Dasar Nilai-Nilai Organisasi (Organizational Values)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong><em>Organizational values</strong></em> adalah sebuah <strong>keyakinan dan kepercayaan</strong> yang dimiliki oleh <strong>pemangku kepentingan</strong> dalam sebuah organisasi dan digunakan untuk <strong>berpikir, bertindak, dan bertingkah laku</strong>, serta dapat <strong>mengarahkan pilihan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat karena menyebutkan tiga ranah penggunaan nilai-nilai organisasi (organizational values) oleh para pemangku kepentingan. Keyakinan bersama di dalam lembaga tidak berhenti di alam pikiran, tetapi harus terwujud nyata dalam perbuatan dan sikap sehari-hari. Secara eksplisit didefinisikan bahwa organizational values dimiliki oleh pemangku kepentingan dalam organisasi dan digunakan untuk berpikir, bertindak, serta bertingkah laku. Oleh sebab itu, rangkaian kata kerja pada Opsi D adalah yang paling tepat.</p><p>Jawaban tepat: <strong>D. Berpikir, bertindak, dan bertingkah laku</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan rangkaian aktivitas pengelolaan keuangan dan pemeriksaan kas, bukan definisi dasar organizational values.</li><li><strong>B</strong>: Merupakan tahapan manajemen sumber daya manusia dalam pengadaan dan pengembangan karier pegawai.</li><li><strong>C</strong>: Merupakan rangkaian kegiatan ketatausahaan dan kearsipan data administratif di lingkungan kantor.</li><li><strong>E</strong>: Merupakan tahapan fungsi pengendalian dan evaluasi kinerja teknis, bukan rumusan tiga ranah organizational values.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 8) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Organizational values adalah • berpikir, bertindak, dan bertingkah laku • mengarahkan pilihan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Selain membedakan hal baik-buruk serta penting-tidak penting, organizational values mengarahkan pilihan mengenai apa yang...", "options": ["Menetapkan jenjang struktur jabatan di organisasi", "Membentuk karakter utama dari sebuah organisasi", "Mengatur pembagian anggaran operasional tahunan", "Menyusun prosedur administrasi persuratan dinas", "Menentukan besaran imbalan kerja bagi pegawai"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Peran Nilai Organisasi dalam Membentuk Karakter Organisasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Organizational values</strong> mengarahkan pilihan mengenai apa yang <strong>baik atau buruk</strong>, apa yang <strong>penting atau tidak penting</strong>, serta apa yang <strong>membentuk karakter sebuah organisasi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat dalam melengkapi peran pengarah pilihan dari nilai-nilai organisasi. Keyakinan bersama para pemangku kepentingan berfungsi menuntun penentuan jati diri kolektif dalam berpikir, bertindak, dan bertingkah laku. Secara konseptual, organizational values mengarahkan pilihan mengenai apa yang baik atau buruk, penting atau tidak penting, serta apa yang membentuk karakter sebuah organisasi. Dengan demikian, pembentukan karakter organisasi merupakan unsur ketiganya yang paling tepat.</p><p>Jawaban tepat: <strong>B. Membentuk karakter utama dari sebuah organisasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penetapan jenjang struktur jabatan merupakan bagian dari penataan kelembagaan dan pembagian wewenang.</li><li><strong>C</strong>: Pengaturan pembagian anggaran operasional termasuk dalam fungsi manajemen pengelolaan keuangan.</li><li><strong>D</strong>: Penyusunan prosedur persuratan dinas merupakan aspek teknis ketatalaksanaan administrasi kantor.</li><li><strong>E</strong>: Penentuan besaran imbalan kerja berkaitan dengan kebijakan manajemen kompensasi kepegawaian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 9) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>baik atau apa yang buruk • membentuk karakter sebuah organisasi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Nilai-nilai organisasi wajib muncul di dalam diri para pemangku kepentingan organisasi yang mencakup...", "options": ["Pimpinan, manajer, supervisor, hingga para pegawai", "Pejabat eksekutif, komisaris, dan konsultan luar", "Manajer puncak, auditor internal, serta pengawas", "Pimpinan pusat, dewan pengarah, beserta staf khusus", "Kepala unit, perumus kebijakan, dan analis senior"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Cakupan Pemangku Kepentingan Pendukung Nilai Organisasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Nilai-nilai organisasi harus muncul di dalam diri seluruh pemangku kepentingan internal, mulai dari <strong>pimpinan, manajer, supervisor, hingga para pegawai</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat mengenai cakupan subjek tempat tumbuhnya nilai-nilai organisasi. Agar nilai mampu menjadi pedoman bersama yang utuh, penghayatannya harus merata pada seluruh jenjang hierarki tanpa terputus di level tertentu. Dalam kaidah nilai organisasi, ditegaskan bahwa nilai-nilai organisasi harus muncul di dalam diri pemangku kepentingan mulai dari pimpinan, manajer, supervisor, hingga para pegawai. Oleh karena itu, rincian lintas jenjang pada Opsi A adalah yang paling akurat.</p><p>Jawaban tepat: <strong>A. Pimpinan, manajer, supervisor, hingga para pegawai</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Belum mencakup jenjang supervisor dan pegawai pelaksana yang menjadi unsur inti internal organisasi.</li><li><strong>C</strong>: Terfokus pada fungsi pengawasan dan manajemen puncak tanpa menyertakan seluruh staf pelaksana.</li><li><strong>D</strong>: Cakupannya terbatas pada lingkaran pimpinan pusat dan staf khusus, belum menyentuh lini operasional.</li><li><strong>E</strong>: Baru menyebutkan unsur pimpinan unit dan jabatan fungsional analis, belum merangkum seluruh pegawai.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 9) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pimpinan, manajer, supervisor • hingga para pegawai</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Sebuah instansi memperkokoh penghayatan nilai agar tingkah laku pegawainya tangguh menopang keutuhan lembaga. Upaya ini menempatkan nilai selayaknya...", "options": ["Atap pelindung dari persaingan pasar bebas", "Pagar pembatas kewenangan antarunit teknis", "Jendela keterbukaan informasi layanan umum", "Fondasi rumah penyangga berdirinya organisasi", "Serambi depan pembentuk citra layanan instansi"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Analogi Nilai Organisasi sebagai Fondasi Rumah</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Nilai organisasi akan <strong>membentuk tingkah laku</strong> yang berfungsi <strong>menyangga organisasi layaknya fondasi rumah</strong>; jika fondasi lemah, maka runtuhlah rumah itu.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat untuk menggambarkan peran nilai dalam menopang keutuhan institusi tersebut. Kekuatan suatu organisasi bergantung pada kualitas tingkah laku anggotanya yang dibentuk langsung oleh nilai-nilai yang dianut bersama. Secara konseptual, nilai akan membentuk tingkah laku yang menyangga organisasi layaknya fondasi rumah, di mana jika fondasi lemah maka rumah akan runtuh. Dengan demikian, perumpamaan fondasi rumah penyangga organisasi sangat tepat bagi ilustrasi ini.</p><p>Jawaban tepat: <strong>D. Fondasi rumah penyangga berdirinya organisasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Analogi nilai organisasi bukan diibaratkan sebagai atap pelindung persaingan, melainkan fondasi penyangga perilaku.</li><li><strong>B</strong>: Pembagian kewenangan antarunit merupakan ranah struktur organisasi, bukan perumpamaan fondasi rumah.</li><li><strong>C</strong>: Keterbukaan informasi layanan merupakan wujud transparansi publik, bukan analogi penopang berdirinya rumah organisasi.</li><li><strong>E</strong>: Serambi depan berkaitan dengan tampilan luar, sedangkan nilai organisasi menjadi fondasi dasar di bawah perilaku.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 9) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>membentuk tingkah laku • layaknya fondasi rumah</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Tiga komponen dalam organizational values yang berinteraksi secara kontinu pada setiap aksi anggota adalah...", "options": ["Visi, misi, dan tujuan strategis organisasi", "Aturan, wewenang, dan prosedur kerja tertulis", "Komponen kognitif, afektif, dan tingkah laku", "Kepercayaan, filosofi, dan prinsip kerja inti", "Perencanaan, pelaksanaan, dan evaluasi kerja"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tiga Komponen Utama Organizational Values</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Organizational values</strong> memiliki tiga komponen, yaitu <strong>kognitif, afektif, dan tingkah laku</strong>, yang <strong>berinteraksi secara kontinu</strong> serta muncul pada setiap aksi anggota organisasi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat karena memuat tiga komponen psikologis-perilaku pembentuk organizational values. Pemahaman akal (kognitif), penghayatan perasaan (afektif), dan perwujudan perbuatan nyata terus terhubung tanpa putus saat anggota bekerja. Konsep organizational values menjelaskan bahwa komponen kognitif, afektif, dan tingkah laku berinteraksi secara kontinu pada setiap aksi seluruh anggota organisasi. Oleh sebab itu, perpaduan kognitif, afektif, dan tingkah laku merupakan jawaban yang akurat.</p><p>Jawaban tepat: <strong>C. Komponen kognitif, afektif, dan tingkah laku</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Visi, misi, dan tujuan strategis merupakan elemen pembentuk identitas budaya organisasi secara makro.</li><li><strong>B</strong>: Aturan, wewenang, dan prosedur kerja merupakan perangkat tata kelola administratif di tempat kerja.</li><li><strong>D</strong>: Kepercayaan (beliefs), filosofi, dan prinsip-prinsip merupakan tiga unsur pendorong nilai menurut Roope Heinila.</li><li><strong>E</strong>: Perencanaan, pelaksanaan, dan evaluasi merupakan rangkaian fungsi manajemen dalam kegiatan organisasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 9) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>komponen kognitif, afektif • berinteraksi secara kontinyu</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Sikap jujur pegawai saat melayani publik membuat masyarakat memahami prinsip instansi tersebut. Hal ini menunjukkan organizational values berperan...", "options": ["Menetapkan pembagian wewenang pejabat struktural", "Menyeragamkan prosedur teknis administrasi kantor", "Mengukur efisiensi realisasi anggaran unit kerja", "Menyusun kurikulum pelatihan dasar pegawai baru", "Mengomunikasikan apa yang diyakini di organisasi"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Peran Komunikatif Organizational Values</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Organizational values</strong> yang hadir pada setiap aksi anggota berperan <strong>mengomunikasikan apa yang dipercayai atau diyakini</strong> di dalam organisasi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat untuk menjelaskan tersampaikannya prinsip instansi melalui perilaku pelayanan pegawainya. Tindakan nyata yang konsisten dari anggota organisasi menjadi media pesan paling jelas mengenai nilai yang dijunjung tinggi oleh lembaga. Secara konseptual, organizational values-lah yang mengomunikasikan apa yang dipercayai atau diyakini di dalam sebuah organisasi. Dengan demikian, tertangkapnya prinsip kejujuran instansi oleh publik mencerminkan peran komunikatif nilai organisasi.</p><p>Jawaban tepat: <strong>E. Mengomunikasikan apa yang diyakini di organisasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pembagian wewenang pejabat struktural merupakan bagian dari pengorganisasian struktur manajemen.</li><li><strong>B</strong>: Penyeragaman prosedur administrasi kantor berkaitan dengan standarisasi tata laksana teknis.</li><li><strong>C</strong>: Pengukuran efisiensi realisasi anggaran termasuk dalam evaluasi kinerja pengelolaan keuangan.</li><li><strong>D</strong>: Penyusunan kurikulum pelatihan dasar merupakan program pengembangan kompetensi sumber daya manusia.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 9) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>mengomunikasikan apa yang dipercayai • diyakini dalam organisasi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Seorang kepala seksi merujuk nilai organisasi sebelum menetapkan langkah kerja dan putusan rapat. Praktik ini menempatkan nilai organisasi sebagai...", "options": ["Catatan sejarah perkembangan kepemimpinan lembaga", "Pedoman, petunjuk, dan peraturan pembuat keputusan", "Instrumen penghitung besaran tunjangan kinerja staf", "Bagan pemisah fungsi perencanaan dan ketatausahaan", "Dokumen pelengkap pertanggungjawaban anggaran unit"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kedudukan Nilai Organisasi dalam Tindakan dan Keputusan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Organizational values</strong> berkedudukan sebagai <strong>pedoman, petunjuk, dan/atau peraturan</strong> dalam <strong>mengambil tindakan serta membuat keputusan</strong> di organisasi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat karena menggambarkan fungsi rujukan nilai organisasi saat kepala seksi menetapkan putusan. Dalam situasi kerja operasional, anggota organisasi memerlukan acuan normatif agar setiap tindakan dan keputusan selaras dengan arah lembaga. Konsep organizational values menegaskan kedudukannya sebagai pedoman, petunjuk, dan/atau peraturan dalam mengambil tindakan serta membuat keputusan dalam organisasi. Oleh karena itu, penggunaan nilai organisasi sebagai acuan rapat selaras dengan Opsi B.</p><p>Jawaban tepat: <strong>B. Pedoman, petunjuk, dan peraturan pembuat keputusan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Catatan sejarah kepemimpinan merupakan dokumentasi arsip masa lalu, bukan rujukan tindakan operasional.</li><li><strong>C</strong>: Penghitungan besaran tunjangan kinerja merupakan fungsi manajemen remunerasi kepegawaian.</li><li><strong>D</strong>: Pemisahan fungsi perencanaan dan ketatausahaan diatur melalui struktur organisasi dan tata kerja.</li><li><strong>E</strong>: Dokumen pertanggungjawaban anggaran merupakan instrumen pelaporan akuntabilitas keuangan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 9) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pedoman, petunjuk dan/atau peraturan • membuat keputusan dalam organisasi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Nilai-nilai organisasi yang diyakini dan menjadi petunjuk bersama akan meningkatkan kepercayaan diri kelompok sehingga memperkuat...", "options": ["Kemandirian kerja individu pada tiap bagian", "Persaingan prestasi internal antarstaf teknis", "Keleluasaan diskresi pejabat di kantor daerah", "Kohesi dan rasa kesatuan kelompok organisasi", "Percepatan rotasi jabatan struktural tahunan"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dampak Nilai Organisasi terhadap Kepercayaan Diri dan Kohesi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Nilai-nilai yang diyakini sebagai petunjuk akan <strong>meningkatkan kepercayaan diri</strong> kelompok dan organisasi, yang akhirnya meningkatkan <strong>kohesi dan rasa kesatuan</strong> kelompok serta organisasi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat mengenai muara dampak sosial-psikologis dari penghayatan nilai organisasi. Ketika seluruh anggota berpijak pada petunjuk nilai yang sama, tumbuh rasa percaya diri kolektif yang merekatkan keutuhan hubungan kerja antaranggota. Secara konseptual, peningkatan kepercayaan diri kelompok dan organisasi tersebut pada akhirnya akan meningkatkan kohesi dan rasa kesatuan kelompok serta organisasi. Dengan demikian, penguatan kohesi dan rasa kesatuan menjadi dampak akhir yang tepat.</p><p>Jawaban tepat: <strong>D. Kohesi dan rasa kesatuan kelompok organisasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kemandirian kerja individu tidak menggambarkan ikatan kebersamaan kelompok yang lahir dari kesamaan nilai.</li><li><strong>B</strong>: Persaingan internal antarstaf berbeda arah dengan semangat persatuan dan kekompakan kelompok kerja.</li><li><strong>C</strong>: Keleluasaan diskresi pejabat daerah berkaitan dengan pendelegasian wewenang administratif birokrasi.</li><li><strong>E</strong>: Percepatan rotasi jabatan struktural merupakan kebijakan manajemen karier sumber daya manusia.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 9) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>meningkatkan kepercayaan diri • kohesi dan rasa kesatuan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C4 - Analisis", "question": "Pernyataan berikut yang BUKAN merupakan karakteristik maupun peran organizational values bagi anggota organisasi adalah...", "options": ["Menyamakan profil organisasi dengan pesaing bisnis", "Muncul pada setiap aksi seluruh anggota organisasi", "Mengomunikasikan apa yang dipercayai di organisasi", "Menjadi petunjuk dalam membuat keputusan organisasi", "Meningkatkan kepercayaan diri kelompok organisasi"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Karakteristik dan Manifestasi Organizational Values</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Organizational values</strong> muncul pada setiap aksi seluruh anggota untuk mengomunikasikan keyakinan, memandu keputusan, memupuk kohesi, serta <strong>membedakan organisasi dari pesaing</strong> dalam kompetisi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan pernyataan yang keliru sehingga menjadi kunci jawaban untuk soal pengecualian ini. Nilai-nilai organisasi tidak bertujuan membuat profil suatu institusi menjadi seragam dengan organisasi lain, melainkan membentuk karakter khasnya sendiri. Sebagaimana ditegaskan oleh Roope Heinila (2024), nilai organisasi adalah DNA perusahaan yang justru membantu membedakan organisasi dari perusahaan lain dalam kompetisi. Dengan demikian, menyamakan profil organisasi dengan pesaing bukanlah peran organizational values.</p><p>Jawaban tepat: <strong>A. Menyamakan profil organisasi dengan pesaing bisnis</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan ciri tepat karena nilai organisasi muncul pada setiap aksi dan tingkah laku seluruh anggota.</li><li><strong>C</strong>: Merupakan peran tepat karena organizational values mengomunikasikan apa yang dipercayai dalam organisasi.</li><li><strong>D</strong>: Merupakan kedudukan tepat karena nilai organisasi menjadi petunjuk dalam membuat keputusan organisasi.</li><li><strong>E</strong>: Merupakan dampak tepat karena nilai yang diyakini mampu meningkatkan kepercayaan diri kelompok.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 9) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>muncul pada setiap aksi • seluruh anggota organisasi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Menurut Roope Heinila (2024), nilai-nilai organisasi yang membantu membedakan suatu organisasi dari pesaingnya diibaratkan sebagai...", "options": ["Struktur hierarki kepemimpinan perusahaan", "Neraca kekuatan modal finansial organisasi", "Standar operasional pelayanan administrasi", "Jejaring kemitraan strategis lintas sektor", "DNA perusahaan atau identitas organisasi"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Nilai Organisasi sebagai DNA Perusahaan Menurut Roope Heinila</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Menurut <strong>Roope Heinila (2024)</strong> dalam <em>Company Values: Definitions, Importance and Examples</em>, nilai-nilai organisasi adalah <strong>DNA perusahaan (organisasi)</strong> yang membedakannya dari organisasi lain dalam kompetisi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat sesuai perumpamaan nilai organisasi yang dikemukakan oleh Roope Heinila (2024). Sebagaimana kode genetik pada makhluk hidup, nilai inti melekat di dalam jati diri lembaga dan melahirkan keunikan karakter di tengah persaingan. Roope Heinila memaparkan bahwa nilai-nilai perusahaan adalah DNA perusahaan atau organisasi yang membantu membedakannya dari perusahaan lain dalam kompetisi. Oleh karena itu, istilah DNA perusahaan merupakan jawaban yang paling tepat.</p><p>Jawaban tepat: <strong>E. DNA perusahaan atau identitas organisasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Struktur hierarki kepemimpinan mengatur garis komando jabatan, bukan metafora nilai menurut Roope Heinila.</li><li><strong>B</strong>: Neraca kekuatan modal finansial mencerminkan kapasitas dana, bukan pembeda identitas nilai organisasi.</li><li><strong>C</strong>: Standar operasional pelayanan mengatur tata urutan kerja teknis harian pada unit layanan.</li><li><strong>D</strong>: Jejaring kemitraan lintas sektor berkaitan dengan hubungan kerja sama eksternal antarlembaga.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 9) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Roope Heinila (2024) • DNA perusahaan (organisasi)</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Seorang direktur hendak menetapkan kebijakan strategis lembaga. Menurut Roope Heinila (2024), keputusan penting tersebut membutuhkan landasan berupa...", "options": ["Proyeksi target penerimaan jangka pendek tahunan", "Perbandingan bagan struktur perusahaan pesaing", "Basis nilai-nilai yang diyakini dalam pikiran", "Rekomendasi teknis dari konsultan manajemen luar", "Penyederhanaan alur verifikasi persuratan dinas"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Basis Nilai dalam Keputusan Penting Menurut Roope Heinila</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Menurut <strong>Roope Heinila (2024)</strong>, seseorang tidak dapat <strong>membuat setiap keputusan penting</strong> tanpa mempunyai <strong>basis nilai-nilai yang diyakini dalam pikirannya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat sebagai prasyarat pengambilan keputusan strategis menurut Roope Heinila (2024). Mengingat nilai organisasi merupakan DNA penentu jati diri lembaga, setiap putusan krusial pimpinan harus berakar pada keyakinan nilai yang kokoh agar arah organisasi tetap terjaga. Roope Heinila menegaskan bahwa seseorang tidak bisa membuat setiap keputusan penting tanpa mempunyai basis nilai-nilai yang diyakini dalam pikirannya. Dengan demikian, basis nilai dalam pikiran pengambil keputusan adalah landasan utamanya.</p><p>Jawaban tepat: <strong>C. Basis nilai-nilai yang diyakini dalam pikiran</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Proyeksi target penerimaan jangka pendek merupakan pertimbangan teknis finansial, bukan basis nilai inti.</li><li><strong>B</strong>: Perbandingan struktur pesaing tidak menggantikan pentingnya basis nilai internal sebagai DNA organisasi.</li><li><strong>D</strong>: Rekomendasi konsultan luar bersifat masukan pendukung, sedangkan basis nilai harus hidup di pikiran pimpinan.</li><li><strong>E</strong>: Penyederhanaan alur persuratan dinas merupakan pembenahan administrasi rutin ketatausahaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 9) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>membuat setiap keputusan penting • basis nilai-nilai yang Anda yakini</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Seorang kepala kantor ingin membangun kepercayaan di lingkungan kerjanya. Menurut Roope Heinila, langkah utama yang wajib dilakukan adalah...", "options": ["Menyusun sistem pengawasan presensi berbasis aplikasi", "Mengadakan lokakarya teknis bersama pakar universitas", "Memperbarui tata naskah dinas koordinasi antarbagian", "Menerbitkan buletin capaian kinerja bulanan unit kerja", "Memegang teguh nilai organisasi dan memberi teladan"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Keteladanan Nilai untuk Membangun Kepercayaan Menurut Roope Heinila</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Menurut <strong>Roope Heinila</strong>, cara membangun <strong>kepercayaan dalam dunia kerja</strong> adalah dengan <strong>berpegang teguh pada nilai-nilai organisasi</strong> dalam setiap tindakan dan <strong>memberi contoh yang baik</strong> kepada pegawai.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan langkah kepemimpinan yang tepat bagi kepala kantor untuk menumbuhkan kepercayaan di tempat kerja. Kepercayaan pegawai lahir ketika mereka menyaksikan keselarasan nyata antara nilai yang ditetapkan lembaga dan keteladanan perilaku pimpinannya sehari-hari. Roope Heinila menjelaskan bahwa pimpinan perlu berpegang teguh pada nilai-nilai organisasi dalam setiap tindakan serta memberi contoh yang baik kepada pegawai untuk membangun kepercayaan dalam dunia kerja. Oleh sebab itu, konsistensi nilai dan keteladanan menjadi kunci utamanya.</p><p>Jawaban tepat: <strong>E. Memegang teguh nilai organisasi dan memberi teladan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Sistem pengawasan presensi aplikasi merupakan perangkat pemantauan disiplin kehadiran secara teknis.</li><li><strong>B</strong>: Lokakarya teknis bersama pakar bertujuan meningkatkan keterampilan kerja, bukan kunci pembangun kepercayaan menurut Heinila.</li><li><strong>C</strong>: Pembaruan tata naskah dinas berfokus pada kelancaran prosedur administrasi persuratan internal.</li><li><strong>D</strong>: Penerbitan buletin capaian kinerja merupakan sarana penyebaran informasi, belum menyentuh keteladanan tindakan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 10) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>memberi contoh yang baik • membangun kepercayaan dalam dunia kerja</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Menurut Roope Heinila, tiga unsur inti nilai organisasi yang menjadi pendorong (driver) organisasi adalah...", "options": ["Sasaran strategis (goals), ukuran (metrics), dan standar (standards)", "Kepercayaan (beliefs), filosofi (philosophies), dan prinsip (principles)", "Pengetahuan (knowledge), keahlian (skills), dan sikap kerja (attitude)", "Perencanaan (planning), penataan (organizing), dan evaluasi (evaluating)", "Kepatuhan (compliance), ketertiban arsip (order), dan wewenang (authority)"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tiga Unsur Pendorong (Driver) Organisasi Menurut Roope Heinila</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Menurut <strong>Roope Heinila</strong>, pada intinya nilai-nilai organisasi adalah <strong>kepercayaan (beliefs), filosofi (philosophies), dan prinsip-prinsip (principles)</strong> yang menjadi <strong>pendorong (driver)</strong> organisasi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat karena memuat tiga unsur esensial pembentuk nilai penggerak organisasi menurut Roope Heinila. Daya dorong kemajuan sebuah lembaga berakar dari keyakinan dasar yang dipercayai, pandangan filosofis yang dianut, serta prinsip kerja yang dipegang teguh anggotanya. Roope Heinila mengungkapkan bahwa inti nilai-nilai organisasi adalah kepercayaan (beliefs), filosofi (philosophies), dan prinsip-prinsip (principles) sebagai pendorong (driver) organisasi. Dengan demikian, ketiga unsur pada Opsi B sangat presisi.</p><p>Jawaban tepat: <strong>B. Kepercayaan (beliefs), filosofi (philosophies), dan prinsip (principles)</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Sasaran, ukuran, dan standar merupakan instrumen pengukuran target kinerja operasional.</li><li><strong>C</strong>: Pengetahuan, keahlian, dan sikap kerja merupakan ranah kompetensi sumber daya manusia individu.</li><li><strong>D</strong>: Perencanaan, penataan, dan evaluasi merupakan tahapan fungsi manajemen pengelolaan kegiatan.</li><li><strong>E</strong>: Kepatuhan, ketertiban arsip, dan wewenang merupakan unsur ketatalaksanaan administrasi birokrasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 10) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>kepercayaan (beliefs), filosofi (philosophies) • pendorong (driver) organisasi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan artikel Organizational values guide everything you at work, nilai-nilai organisasi menggambarkan etika inti tempat organisasi tinggal bersama...", "options": ["Dalam situasi apa pun yang dihadapi bersama", "Selama periode penyusunan anggaran tahunan", "Ketika kondisi operasional berjalan normal", "Pada masa orientasi penerimaan pegawai baru", "Sepanjang pelaksanaan proyek strategis pusat"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Nilai Organisasi sebagai Etika Inti Kebersamaan Lintas Situasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam artikel <em>Organizational values guide everything you at work</em>, nilai-nilai organisasi menggambarkan <strong>etika inti atau prinsip-prinsip</strong> tempat sebuah organisasi tinggal bersama <strong>dalam situasi apa pun</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat mengenai daya ikat nilai organisasi sebagai etika inti kebersamaan lembaga. Nilai organisasi sejatinya tidak berlaku musiman, melainkan menjadi pegangan hidup kolektif baik saat kondisi lancar maupun ketika menghadapi tantangan berat. Sebagaimana dipaparkan dalam artikel Organizational values guide everything you at work, nilai-nilai organisasi menggambarkan etika inti atau prinsip tempat organisasi tinggal bersama dalam situasi apa pun. Oleh karena itu, keberlakuan lintas situasi pada Opsi A adalah yang paling tepat.</p><p>Jawaban tepat: <strong>A. Dalam situasi apa pun yang dihadapi bersama</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Nilai organisasi berlaku terus-menerus sepanjang waktu, tidak terbatas pada periode penyusunan anggaran.</li><li><strong>C</strong>: Etika inti organisasi tetap mengikat saat krisis maupun perubahan, tidak terbatas pada kondisi normal.</li><li><strong>D</strong>: Penerapan nilai organisasi mengawal seluruh masa pengabdian pegawai, melampaui masa orientasi awal.</li><li><strong>E</strong>: Prinsip hidup bersama dalam organisasi berlaku permanen pada seluruh kegiatan, bukan proyek tertentu.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 10) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menggambarkan etika inti • dalam situasi apa pun</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C4 - Analisis", "question": "Dua peran serentak nilai-nilai organisasi dalam memandu tindakan para pegawai di tempat kerja adalah...", "options": ["Menetapkan target anggaran serta membagi beban kerja harian", "Menyusun jenjang karier sekaligus mengatur mutasi wilayah", "Menyeragamkan prosedur teknis serta menilai presensi staf", "Menginspirasi pencapaian terbaik serta batasan bertindak", "Memadukan inovasi layanan sekaligus menjaga arsip dokumen"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Peran Ganda Nilai Organisasi sebagai Inspirasi dan Batasan Bertindak</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Nilai-nilai organisasi berperan ganda <strong>menginspirasi pencapaian terbaik</strong> dari para pegawai sekaligus menjadi <strong>batasan-batasan mereka dalam bertindak</strong> mengenai apa yang boleh dan tidak boleh.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat karena menyatukan fungsi pendorong prestasi dan fungsi pengendali etis dari nilai organisasi. Di satu sisi pegawai terpacu memberikan kinerja puncak, sementara di sisi lain mereka memahami rambu kepatuhan mengenai perbuatan yang boleh dan tidak boleh dilakukan. Secara konseptual, nilai-nilai organisasi menginspirasi pencapaian terbaik pegawai sekaligus memberikan batasan-batasan mereka dalam bertindak. Dengan demikian, perpaduan inspirasi prestasi dan batasan bertindak ini menjawab soal secara akurat.</p><p>Jawaban tepat: <strong>D. Menginspirasi pencapaian terbaik serta batasan bertindak</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penetapan target anggaran dan pembagian beban kerja merupakan tugas perencanaan operasional unit.</li><li><strong>B</strong>: Penyusunan jenjang karier dan pengaturan mutasi wilayah termasuk dalam manajemen kepegawaian.</li><li><strong>C</strong>: Penyeragaman prosedur teknis dan penilaian presensi berkaitan dengan pengawasan ketatalaksanaan rutin.</li><li><strong>E</strong>: Inovasi layanan dan penjagaan arsip dokumen merupakan kegiatan teknis pelayanan serta kearsipan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 10) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menginspirasi pencapaian terbaik • batasan-batasan mereka dalam bertindak</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Nilai-nilai kuat yang diartikulasi secara jelas harus menjadi cerminan organisasi guna menghasilkan perilaku tepat serta membangun...", "options": ["Struktur birokrasi jabatan yang seragam", "Mekanisme pelaporan keuangan yang cepat", "Budaya kerja yang positif di organisasi", "Sentralisasi wewenang pada pimpinan atas", "Kemandirian pembiayaan operasional unit"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Artikulasi Nilai yang Jelas untuk Membangun Budaya Kerja Positif</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Nilai-nilai kuat yang diartikulasi secara jelas</strong> menjadi cerminan organisasi untuk menghasilkan <strong>perilaku yang tepat di tempat kerja</strong> dan <strong>membangun budaya kerja yang positif</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat mengenai peran strategis nilai organisasi yang dirumuskan dan disampaikan secara jernih. Kejelasan artikulasi nilai memudahkan setiap anggota menerjemahkan cerminan jati diri lembaga ke dalam kebiasaan kerja yang sehat sehari-hari. Secara konseptual, nilai-nilai kuat yang diartikulasi secara jelas menghasilkan perilaku yang tepat di tempat kerja serta berperan penting membangun budaya kerja yang positif dalam organisasi. Oleh karena itu, terbangunnya budaya kerja positif merupakan muara yang tepat.</p><p>Jawaban tepat: <strong>C. Budaya kerja yang positif di organisasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penyeragaman struktur birokrasi jabatan merupakan ranah penataan organisasi dan tata laksana.</li><li><strong>B</strong>: Kecepatan pelaporan keuangan berkaitan dengan peningkatan sistem akuntansi dan perbendaharaan.</li><li><strong>D</strong>: Sentralisasi wewenang pimpinan merupakan pola distribusi kekuasaan struktural, bukan tujuan artikulasi nilai.</li><li><strong>E</strong>: Kemandirian pembiayaan operasional berkaitan dengan pengelolaan sumber pendapatan layanan unit.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 10) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>diartikulasi secara jelas • membangun budaya kerja yang positif</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Tim telah menggali dan mendiskusikan nilai keseharian organisasi. Menurut Susan M. Heathfield (2021), langkah identifikasi nilai selanjutnya adalah...", "options": ["Melaksanakan asesmen tahunan atas standar layanan unit", "Menyelenggarakan pelatihan teknis inovasi layanan baru", "Membentuk komite evaluasi capaian kinerja kepala seksi", "Memublikasikan piagam nilai kepada mitra layanan luar", "Mencari konsensus komitmen para leader atau manager"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tahap Pencarian Konsensus Komitmen Pimpinan Menurut Susan M. Heathfield</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Menurut <strong>Susan M. Heathfield (2021)</strong>, setelah menggali, mempelajari, dan mendiskusikan nilai sehari-hari, langkah berikutnya adalah <strong>mencari kesepakatan atau konsensus</strong> di mana para <strong>leader atau manager mempunyai komitmen</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan langkah lanjutan yang tepat sesuai urutan proses identifikasi nilai organisasi menurut Susan M. Heathfield (2021). Nilai keseharian yang telah digali dan didiskusikan perlu disatukan terlebih dahulu melalui kesepakatan para pimpinan manajerial agar memiliki komitmen pengawalan yang kuat. Dalam tahapan Heathfield, penggalian nilai sehari-hari diikuti langkah mencari kesepakatan atau konsensus di mana para leader atau manager mempunyai komitmen atas rumusan yang dibuat. Dengan demikian, pencarian konsensus pimpinan pada Opsi E sangat tepat.</p><p>Jawaban tepat: <strong>E. Mencari konsensus komitmen para leader atau manager</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Asesmen standar layanan unit merupakan evaluasi mutu operasional pelayanan, bukan tahapan identifikasi nilai.</li><li><strong>B</strong>: Pelatihan teknis inovasi layanan berfokus pada kecakapan operasional, bukan pencarian konsensus rumusan nilai.</li><li><strong>C</strong>: Pembentukan komite evaluasi kinerja kepala seksi termasuk dalam mekanisme penilaian kinerja struktural.</li><li><strong>D</strong>: Publikasi kepada mitra luar belum tepat dilakukan sebelum adanya konsensus pimpinan dan rumusan tertulis.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 10) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Susan M. Heathfield (2021) • kesepakatan-kesepakatan atau konsensus</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Menurut Susan M. Heathfield (2021), peran para eksekutif ditetapkan untuk memimpin perumusan nilai organisasi karena mereka...", "options": ["Paling bertanggung jawab dalam penerapan nilai nanti", "Menguasai seluruh teknis administrasi harian pegawai", "Memegang wewenang penyusunan jadwal orientasi pegawai", "Bertugas mencatat notula diskusi kelompok lintas unit", "Mengelola pengadaan media publikasi nilai di kantor"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Alasan Peran Eksekutif Memimpin Perumusan Nilai Menurut Susan M. Heathfield</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Menurut <strong>Susan M. Heathfield (2021)</strong>, organisasi perlu menentukan peran <strong>para eksekutif dalam memimpin proses perumusan</strong> karena mereka menjadi pihak yang <strong>paling bertanggung jawab dalam penerapan nilai-nilai</strong> nantinya.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat mengenai dasar penetapan kepemimpinan eksekutif dalam merumuskan nilai organisasi. Keberhasilan implementasi nilai setelah disepakati sangat ditentukan oleh pengawalan jajaran eksekutif, sehingga keterlibatan memimpin sejak awal akan mengikat rasa tanggung jawab mereka. Susan M. Heathfield (2021) menegaskan bahwa eksekutif memimpin proses perumusan ini karena mereka yang nanti paling bertanggung jawab dalam penerapan nilai-nilai yang disepakati kemudian. Oleh karena itu, alasan tanggung jawab penerapan pada Opsi A sangat akurat.</p><p>Jawaban tepat: <strong>A. Paling bertanggung jawab dalam penerapan nilai nanti</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Penguasaan teknis administrasi harian merupakan ranah pelaksana operasional dan pengawas lini pertama.</li><li><strong>C</strong>: Penyusunan jadwal orientasi pegawai baru dikelola secara teknis oleh unit kepegawaian atau pelatihan.</li><li><strong>D</strong>: Pencatatan notula diskusi lintas unit merupakan tugas kesekretariatan tim perumus, bukan peran eksekutif.</li><li><strong>E</strong>: Pengadaan media publikasi nilai di kantor merupakan dukungan fungsi kehumasan dan kerumahtanggaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 10) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>memimpin proses perumusan ini • paling bertanggung jawab dalam penerapan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Menurut Susan M. Heathfield (2021), kesepakatan nilai organisasi oleh para eksekutif harus dirumuskan secara tertulis dengan memperoleh...", "options": ["Persetujuan lembaga konsultan manajemen", "Pengesahan auditor pengawas eksternal", "Dukungan penuh dari jajaran para staf", "Penilaian survei kepuasan masyarakat", "Rekomendasi asosiasi profesi sejenis"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Perumusan Tertulis Kesepakatan Nilai Menurut Susan M. Heathfield</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Tahapan keempat identifikasi nilai menurut <strong>Susan M. Heathfield (2021)</strong> adalah <strong>merumuskan secara tertulis</strong> apa yang telah disepakati oleh para eksekutif yang memperoleh <strong>dukungan penuh dari para staf</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat mengenai unsur pendukung dalam perumusan tertulis nilai organisasi menurut Susan M. Heathfield (2021). Dokumen nilai yang disepakati pimpinan puncak memerlukan penerimaan tulus dari jajaran pelaksana agar nilai tersebut benar-benar dihidupi bersama dalam praktik kerja. Sesuai tahapan Heathfield, organisasi perlu merumuskan secara tertulis apa yang telah disepakati oleh para eksekutif yang memperoleh dukungan penuh dari para staf. Dengan demikian, dukungan penuh jajaran staf merupakan kelengkapan yang tepat.</p><p>Jawaban tepat: <strong>C. Dukungan penuh dari jajaran para staf</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Konsultan manajemen bukan syarat pengesahan rumusan tertulis nilai dalam tahapan Susan M. Heathfield.</li><li><strong>B</strong>: Auditor pengawas eksternal berfokus pada pemeriksaan akuntabilitas, bukan perumusan nilai internal.</li><li><strong>D</strong>: Survei kepuasan masyarakat digunakan untuk mengevaluasi mutu layanan publik, bukan syarat dokumen nilai.</li><li><strong>E</strong>: Rekomendasi asosiasi profesi berkaitan dengan standar etika profesi lintas lembaga, bukan konsensus staf internal.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 10) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Rumuskan secara tertulis • dukungan penuh dari para staf</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Sebuah kementerian berskala besar hendak menginisiasi perumusan nilai organisasi. Sesuai pandangan Susan M. Heathfield (2021), para eksekutif perlu melibatkan...", "options": ["Tim perencana strategis pada biro kantor pusat", "Perwakilan satu unit percontohan layanan utama", "Kelompok akademisi perguruan tinggi mitra kerja", "Pegawai lintas sektoral pada seluruh jenjang", "Jajaran pejabat struktural tingkat menengah"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pelibatan Pegawai Lintas Sektoral pada Organisasi Sedang atau Besar</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Menurut <strong>Susan M. Heathfield (2021)</strong>, dalam <strong>organisasi berskala sedang atau besar</strong>, diperlukan <strong>keterlibatan pegawai lintas sektoral pada semua jenjang</strong> untuk bekerja sama dengan eksekutif merumuskan nilai.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat bagi pengelolaan partisipasi perumusan nilai pada kementerian berskala besar. Semakin luas cakupan organisasi, semakin dibutuhkan keterwakilan seluruh bidang dan tingkatan jabatan agar rumusan nilai yang diinisiasi mencerminkan aspirasi bersama. Susan M. Heathfield (2021) menjelaskan bahwa pada organisasi berskala sedang atau besar diperlukan keterlibatan pegawai lintas sektoral pada semua jenjang untuk bekerja sama dengan para eksekutif. Oleh sebab itu, pelibatan pegawai lintas sektoral seluruh jenjang sangat tepat.</p><p>Jawaban tepat: <strong>D. Pegawai lintas sektoral pada seluruh jenjang</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pelibatan satu biro perencana kantor pusat belum mewakili keragaman sektor dan jenjang organisasi besar.</li><li><strong>B</strong>: Perwakilan satu unit percontohan terlalu sempit untuk mencerminkan karakteristik seluruh bagian kementerian.</li><li><strong>C</strong>: Akademisi perguruan tinggi berasal dari luar instansi sehingga tidak menggantikan kolaborasi pegawai lintas jenjang.</li><li><strong>E</strong>: Pejabat struktural tingkat menengah baru mewakili satu lapisan jabatan, belum menyentuh seluruh jenjang.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 10) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>organisasi berskala sedang atau besar • keterlibatan pegawai lintas sektoral</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C4 - Analisis", "question": "Berikut merupakan langkah identifikasi dan pengembangan nilai organisasi menurut Susan M. Heathfield (2021), KECUALI...", "options": ["Menggali serta mendiskusikan nilai keseharian organisasi", "Menyerahkan perumusan nilai kepada konsultan independen", "Mencari konsensus komitmen dari para leader dan manager", "Merumuskan tertulis kesepakatan eksekutif didukung staf", "Brainstorming intensif saat pelatihan middle-top manager"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tahapan Identifikasi dan Pengembangan Nilai Menurut Susan M. Heathfield</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Enam langkah <strong>Susan M. Heathfield (2021)</strong> meliputi: menggali nilai sehari-hari, mencari konsensus <em>leader/manager</em>, kepemimpinan eksekutif, rumusan tertulis didukung staf, pelibatan lintas sektoral, serta <strong>brainstorming secara intensif</strong> saat pelatihan <strong>middle atau top manager</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan pernyataan yang tidak termasuk dalam enam tahapan Susan M. Heathfield (2021) sehingga menjadi kunci jawaban soal ini. Seluruh langkah Heathfield bertumpu pada partisipasi aktif elemen internal organisasi mulai dari penggalian keseharian, komitmen manajer, kepemimpinan eksekutif, dukungan staf, hingga curah gagasan pelatihan manajer. Sebaliknya, penyerahan perumusan nilai kepada konsultan independen eksternal tidak terdapat dalam rincian tahapan identifikasi nilai organisasi menurut Heathfield. Dengan demikian, Opsi B tepat dipilih sebagai pengecualian.</p><p>Jawaban tepat: <strong>B. Menyerahkan perumusan nilai kepada konsultan independen</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Termasuk langkah pertama Heathfield, yaitu menggali, mempelajari, dan mendiskusikan nilai sehari-hari di organisasi.</li><li><strong>C</strong>: Termasuk langkah kedua Heathfield, yaitu mencari konsensus komitmen para leader atau manager.</li><li><strong>D</strong>: Termasuk langkah keempat Heathfield, yaitu merumuskan secara tertulis kesepakatan eksekutif dengan dukungan staf.</li><li><strong>E</strong>: Termasuk langkah keenam Heathfield, yaitu pengembangan nilai bersamaan pelatihan middle atau top manager lewat brainstorming intensif.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 10) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>middle atau top manager • melakukan brainstorming secara intensif</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Dalam materi tertulis hasil diskusi awal lintas sektor sebelum pengembangan nilai organisasi, kegiatan pendahuluan para pemangku kepentingan memiliki karakteristik...", "options": ["Wajib disahkan melalui keputusan resmi pimpinan", "Dilakukan terstruktur dalam lokakarya direksi", "Berupa pelatihan terpadu bagi manajer menengah", "Tidak harus formal namun menunjukkan komitmen", "Berbentuk evaluasi kinerja tahunan tiap sektor"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Materi Tertulis Diskusi Awal pada Tahap Pra-Pengembangan Nilai</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sebelum mengembangkan nilai-nilai organisasi, para <strong>leader/manajer/eksekutif</strong> diharapkan <strong>membagikan materi tertulis</strong> berisi hasil diskusi awal lintas sektor yang menunjukkan semangat kegiatan pendahuluan — <strong>meski tidak harus formal</strong>, tetapi menunjukkan <strong>komitmen</strong> terus mengembangkan nilai organisasi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan karakteristik kegiatan pendahuluan yang tepat pada tahap pra-pengembangan nilai organisasi. Fokus utama langkah awal ini bukan pada kekakuan acara seremonial, melainkan pada bukti nyata adanya semangat awal dan keseriusan lintas sektor. Sebelum proses pengembangan nilai dilakukan, eksekutif membagikan materi tertulis hasil diskusi awal yang memperlihatkan bahwa pemangku kepentingan telah melakukan kegiatan pendahuluan, meski tidak harus formal, tetapi menunjukkan komitmen mengembangkan nilai organisasi. Dengan demikian, sifat nonformal yang disertai komitmen kuat menjadi ciri utamanya.</p><p>Jawaban tepat: <strong>D. Tidak harus formal namun menunjukkan komitmen</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pengesahan rumusan tertulis dilakukan setelah tercapai kesepakatan eksekutif dan dukungan staf pada tahap perumusan.</li><li><strong>B</strong>: Kegiatan pendahuluan tidak diwajibkan berformat lokakarya formal direksi karena melibatkan jajaran lintas sektor.</li><li><strong>C</strong>: Pelatihan manajer menengah dan atas dengan brainstorming intensif merupakan metode pada tahap identifikasi nilai.</li><li><strong>E</strong>: Evaluasi kinerja tahunan merupakan pemanfaatan nilai organisasi sebagai kriteria atau standar penilaian performance.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 11) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Membagikan materi tertulis • hasil diskusi awal para eksekutif • tidak harus formal</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Sebelum mengembangkan nilai-nilai organisasi, eksekutif perlu mempromosikan rasionalitas perlunya nilai beserta proses perumusannya dan dampaknya bagi...", "options": ["Pembagian wewenang teknis antarunit operasional", "Penertiban administrasi surat-menyurat kedinasan", "Kemajuan organisasi melalui pembentukan nilai", "Penyusunan kurikulum pelatihan manajer menengah", "Penggolongan jenjang jabatan struktural pegawai"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Promosi Rasionalitas Perlunya Nilai-Nilai Organisasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Langkah persiapan kedua sebelum pengembangan nilai organisasi adalah <strong>mempromosikan rasionalitas</strong> tentang perlunya nilai-nilai organisasi (termasuk <strong>proses perumusannya</strong>) serta dampaknya pada pembentukan nilai bagi <strong>kemajuan organisasi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat terkait arah promosi rasionalitas pada tahap pra-pengembangan nilai. Seluruh elemen kerja perlu memahami alasan logis mengapa nilai organisasi dirumuskan agar tumbuh keyakinan bersama atas manfaat strategisnya. Dalam langkah persiapan eksekutif sebelum mengembangkan nilai organisasi, ditekankan kewajiban mempromosikan rasionalitas perlunya nilai-nilai organisasi termasuk proses perumusannya serta dampaknya pada pembentukan nilai bagi kemajuan organisasi. Oleh karena itu, orientasi kemajuan organisasi menjadi muara utama promosi rasionalitas tersebut.</p><p>Jawaban tepat: <strong>C. Kemajuan organisasi melalui pembentukan nilai</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pembagian wewenang yang jelas antarpemangku kepentingan merupakan syarat manajemen dalam pelaksanaan kinerja.</li><li><strong>B</strong>: Penataan surat-menyurat kedinasan merupakan contoh penerapan budaya kerapian administrasi sehari-hari.</li><li><strong>D</strong>: Pelatihan manajer menengah melalui curah pendapat merupakan salah satu cara mengidentifikasi nilai organisasi.</li><li><strong>E</strong>: Penggolongan jenjang jabatan kepegawaian tidak termasuk fokus promosi rasionalitas pra-pengembangan nilai.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 11) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Mempromosikan rasionalitas • proses perumusannya • bagi kemajuan organisasi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Menjelang pengembangan nilai, pimpinan memastikan seluruh wakil unit konsisten datang dan aktif di tiap sesi pembahasan hingga perumusan usai. Tindakan ini menerapkan langkah...", "options": ["Membagikan materi tertulis diskusi pendahuluan", "Mempromosikan rasionalitas perlunya tata nilai", "Merumuskan konsensus tertulis bersama para staf", "Menetapkan peran eksekutif pemimpin perumusan", "Meyakinkan kehadiran aktif di seluruh diskusi"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Partisipasi Semua Unit dan Kehadiran dalam Diskusi Perumusan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pada tahap pra-pengembangan nilai, pimpinan perlu <strong>menunjukkan pentingnya partisipasi semua unit yang terlibat</strong> sekaligus <strong>meyakinkan bahwa semua pihak yang terlibat selalu hadir dan terlibat</strong> dalam semua <strong>proses diskusi dan perumusannya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan langkah persiapan pra-pengembangan yang tepat sesuai tindakan pimpinan tersebut. Keberhasilan perumusan tata nilai menuntut keterlibatan utuh tanpa terputus dari para wakil unit sejak pembahasan awal hingga penyusunan akhir. Dalam tahapan sebelum pengembangan nilai-nilai organisasi, para eksekutif diharapkan meyakinkan bahwa semua pihak yang terlibat selalu hadir dan terlibat dalam semua proses diskusi dan perumusannya. Dengan demikian, upaya menjaga konsistensi kehadiran aktif peserta di seluruh sesi pembahasan mencerminkan langkah keempat pra-pengembangan nilai.</p><p>Jawaban tepat: <strong>E. Meyakinkan kehadiran aktif di seluruh diskusi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan langkah pertama pra-pengembangan untuk memperlihatkan semangat awal dan komitmen lintas sektor.</li><li><strong>B</strong>: Merupakan langkah kedua pra-pengembangan untuk menjelaskan alasan logis dan dampak nilai bagi kemajuan.</li><li><strong>C</strong>: Merupakan tahapan identifikasi nilai menurut Susan M. Heathfield setelah kesepakatan eksekutif tercapai.</li><li><strong>D</strong>: Merupakan tahapan identifikasi nilai untuk menetapkan tanggung jawab eksekutif dalam penerapan nilai.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 11) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pentingnya partisipasi semua unit • selalu hadir dan terlibat • proses diskusi dan perumusannya</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Tim eksekutif menyusun daftar penjelasan guna merespons berbagai tanggapan kritis peserta selama pengembangan hingga perumusan nilai. Langkah pra-pengembangan ini berupa...", "options": ["Menyiapkan jawaban atas pertanyaan dan umpan balik", "Menggali nilai keseharian yang hidup di organisasi", "Menunjukkan pentingnya partisipasi seluruh unit kerja", "Mengadakan curah pendapat intensif manajer menengah", "Menyusun standar penilaian kinerja tahunan pegawai"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penyiapan Jawaban atas Pertanyaan dan Umpan Balik</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Langkah kelima yang diharapkan dari <strong>leader/manajer/eksekutif</strong> sebelum mengembangkan nilai organisasi adalah <strong>menyiapkan semua jawaban atas pertanyaan dan umpan balik</strong> yang muncul selama proses pengembangan sampai perumusan nilai-nilai organisasi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan langkah persiapan yang paling tepat menggambarkan tindakan antisipatif tim eksekutif tersebut. Proses dialog lintas sektor pasti memunculkan beragam keingintahuan dan masukan yang harus dijawab secara tuntas agar kepercayaan peserta terjaga. Sesuai rincian langkah pra-pengembangan nilai organisasi, jajaran eksekutif diharapkan menyiapkan semua jawaban atas pertanyaan dan umpan balik yang muncul selama proses pengembangan sampai perumusan nilai-nilai organisasi. Oleh sebab itu, penyusunan daftar penjelasan atas masukan peserta merupakan penerapan langsung langkah tersebut.</p><p>Jawaban tepat: <strong>A. Menyiapkan jawaban atas pertanyaan dan umpan balik</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan langkah awal tahapan identifikasi nilai organisasi menurut Susan M. Heathfield.</li><li><strong>C</strong>: Merupakan langkah ketiga pra-pengembangan yang menekankan urgensi kontribusi seluruh unit kerja.</li><li><strong>D</strong>: Merupakan variasi pengembangan nilai yang dipadukan dengan pelatihan middle atau top manager.</li><li><strong>E</strong>: Merupakan penerapan fungsi nilai organisasi sebagai kriteria atau standar evaluasi performance.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 11) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>jawaban atas pertanyaan • umpan balik yang muncul • sampai perumusan nilai-nilai organisasi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Sesuai artikel 'How to make Values Intrinsic to Your Organization Success', fungsi utama nilai-nilai organisasi adalah...", "options": ["Membedakan identitas perusahaan dari para pesaingnya", "Membimbing kegiatan pegawai, manajer, dan pimpinan", "Membagi wewenang struktural antarpejabat unit kerja", "Menertibkan alur persuratan dan keluar masuk barang", "Menarik minat talenta unggul bergabung ke instansi"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Fungsi Utama Nilai-Nilai Organisasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Berdasarkan artikel <em>\"How to make Values Intrinsic to Your Organization Success\"</em> (Susan M. Heathfield), <strong>fungsi utama nilai-nilai organisasi</strong> adalah <strong>membimbing apa pun kegiatan</strong> yang dilakukan oleh <strong>pegawai, manajer, dan pimpinan di tempat kerja</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B secara tepat menyatakan fungsi utama nilai-nilai organisasi di lingkungan kerja. Nilai organisasi hadir sebagai kompas pemandu yang mengarahkan aktivitas seluruh jenjang sumber daya manusia tanpa terkecuali. Sebagaimana ditegaskan dalam artikel 'How to make Values Intrinsic to Your Organization Success', fungsi utama nilai-nilai organisasi adalah membimbing apa pun kegiatan yang dilakukan oleh pegawai, manajer, dan pimpinan di tempat kerja. Dengan demikian, bimbingan menyeluruh atas aktivitas kerja lintas jenjang menjadi esensi fungsi utama nilai organisasi.</p><p>Jawaban tepat: <strong>B. Membimbing kegiatan pegawai, manajer, dan pimpinan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan pandangan Roope Heinila (2024) mengenai nilai perusahaan sebagai DNA pembeda dalam kompetisi.</li><li><strong>C</strong>: Merupakan syarat pembagian wewenang dalam ilmu manajemen untuk memperjelas batas tindakan kerja.</li><li><strong>D</strong>: Merupakan contoh penerapan budaya kerapian administrasi guna membantu pencapaian kinerja organisasi.</li><li><strong>E</strong>: Merupakan salah satu peran strategis budaya organisasi yang baik dalam memikat bibit unggul.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 11) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Fungsi utama nilai-nilai organisasi • membimbing apa pun kegiatan • di tempat kerja</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C4 - Analisis", "question": "Nilai-nilai organisasi merupakan tuntunan atau pedoman yang mendasari seseorang atau sebuah organisasi dalam hal-hal berikut, KECUALI...", "options": ["Berpikir menelaah persoalan pekerjaan", "Mengambil keputusan secara tepat terukur", "Bersikap menghadapi situasi kedinasan", "Menetapkan golongan kepangkatan pegawai", "Bertindak melaksanakan tugas organisasi"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Nilai Organisasi sebagai Tuntunan Berpikir, Keputusan, Sikap, dan Tindakan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Nilai-nilai organisasi</strong> berfungsi sebagai suatu <strong>tuntunan atau pedoman</strong> yang mendasari bagaimana seseorang atau sebuah organisasi <strong>berpikir, mengambil keputusan, bersikap, dan bertindak</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan pilihan yang tepat untuk dikecualikan karena bukan bagian dari empat aspek mendasar yang dituntun oleh fungsi nilai organisasi tersebut. Tuntunan nilai bekerja mengarahkan proses mental hingga perbuatan nyata insan maupun institusi, bukan mengatur administrasi kepangkatan formal. Dalam rincian fungsi nilai-nilai organisasi, disebutkan secara tegas bahwa nilai mendasari bagaimana seseorang atau organisasi berpikir, mengambil keputusan, bersikap, dan bertindak. Oleh karena opsi A, B, C, dan E memuat keempat unsur tersebut, maka opsi D tidak termasuk di dalamnya.</p><p>Jawaban tepat: <strong>D. Menetapkan golongan kepangkatan pegawai</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Termasuk unsur yang didasari nilai organisasi, yakni menuntun cara seseorang atau organisasi berpikir.</li><li><strong>B</strong>: Termasuk unsur yang didasari nilai organisasi, yakni memandu proses dalam mengambil keputusan.</li><li><strong>C</strong>: Termasuk unsur yang didasari nilai organisasi, yakni mendasari cara bersikap di tempat kerja.</li><li><strong>E</strong>: Termasuk unsur yang didasari nilai organisasi, yakni menjadi landasan saat bertindak nyata.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 11) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>tuntunan atau pedoman • berpikir, mengambil keputusan • bersikap, dan bertindak</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Instansi bea cukai beserta para pengguna jasanya sama-sama menjadikan integritas sebagai acuan interaksi layanan. Hal ini mencerminkan fungsi nilai sebagai...", "options": ["Risalah diskusi awal para eksekutif lintas sektor", "Rasionalitas dampak kemajuan pada tahap persiapan", "Sarana curah pendapat pelatihan manajer menengah", "Instrumen pembagian wewenang teknis antarunit seksi", "Pedoman organisasi dan setiap pemangku kepentingan"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pedoman Berperilaku bagi Organisasi dan Pemangku Kepentingan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Nilai-nilai organisasi</strong> berfungsi menjadi <strong>pedoman bagi organisasi dan setiap pemangku kepentingan</strong> untuk <strong>berperilaku dan bertindak</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan fungsi nilai organisasi yang tepat menggambarkan keselarasan acuan interaksi layanan tersebut. Tata nilai tidak berlaku bagi entitas kelembagaan secara terpisah, melainkan turut memandu seluruh pemangku kepentingan yang berhubungan dengan organisasi. Sesuai rincian fungsi nilai-nilai organisasi, tata nilai menjadi pedoman bagi organisasi dan setiap pemangku kepentingan untuk berperilaku dan bertindak. Dengan demikian, kepatuhan bersama antara instansi dan pengguna jasa terhadap nilai integritas mencerminkan fungsi pedoman perilaku bersama tersebut.</p><p>Jawaban tepat: <strong>E. Pedoman organisasi dan setiap pemangku kepentingan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan dokumen kegiatan pendahuluan yang dibagikan eksekutif sebelum proses pengembangan nilai dimulai.</li><li><strong>B</strong>: Merupakan fokus promosi rasionalitas perlunya nilai pada tahap persiapan pra-pengembangan.</li><li><strong>C</strong>: Merupakan metode brainstorming intensif dalam pelatihan manajer pada tahap identifikasi nilai.</li><li><strong>D</strong>: Merupakan syarat pengorganisasian dalam ilmu manajemen untuk memperjelas batas kewenangan kerja.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 11) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pedoman bagi organisasi • setiap pemangku kepentingan • berperilaku dan bertindak</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Bagi seluruh Sumber Daya Manusia (SDM) yang terlibat dalam kegiatan organisasi, nilai-nilai organisasi berfungsi menjadi batasan-batasan mengenai...", "options": ["Target hasil akhir serta teknik pengerjaannya", "Hal boleh atau tidak serta benar atau salah", "Wewenang struktural serta masa kerja jabatan", "Alur surat-menyurat serta mutasi barang unit", "Perencanaan program serta evaluasi anggaran"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Nilai Organisasi sebagai Batasan Boleh-Tidak dan Benar-Salah bagi SDM</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Nilai-nilai organisasi</strong> berfungsi menjadi <strong>batasan-batasan boleh atau tidak, serta benar atau salah</strong>, bagi seluruh <strong>Sumber Daya Manusia (SDM)</strong> yang terlibat dalam melakukan kegiatan dan tindakan organisasi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat mengenai peran nilai organisasi sebagai rambu normatif bagi seluruh SDM. Setiap aktivitas pegawai memerlukan koridor etika yang tegas agar pelaksanaan tugas senantiasa terjaga kepatutannya. Dalam uraian fungsi nilai-nilai organisasi, ditegaskan bahwa nilai menjadi batasan-batasan boleh atau tidak, serta benar atau salah, bagi seluruh Sumber Daya Manusia (SDM) yang terlibat dalam kegiatan dan tindakan organisasi. Oleh karena itu, kejelasan ukuran boleh-tidak serta benar-salah menjadi inti fungsi pembatas tersebut.</p><p>Jawaban tepat: <strong>B. Hal boleh atau tidak serta benar atau salah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Perbandingan hasil akhir dan teknik proses merupakan dimensi outcome orientation menurut Stephen P. Robbins.</li><li><strong>C</strong>: Wewenang struktural berkaitan dengan syarat pembagian wewenang manajemen, bukan definisi fungsi batasan etis SDM.</li><li><strong>D</strong>: Surat-menyurat dan mutasi barang merupakan cakupan contoh budaya kerapian administrasi di organisasi.</li><li><strong>E</strong>: Perencanaan dan pengendalian program merupakan bagian dari empat prinsip manajemen organisasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 11) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>batasan-batasan boleh atau tidak • benar atau salah • Sumber Daya Manusia</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Tata nilai instansi mengayomi pegawai, manajer, dan pimpinan sekaligus masyarakat luar yang merasakan dampak layanan. Hal ini mencerminkan nilai organisasi sebagai...", "options": ["Bangunan rumah yang ditinggali bersama semua pihak", "Materi tertulis hasil diskusi pendahuluan eksekutif", "Sistem penataan administrasi keuangan dan persuratan", "Standar pembagian wewenang teknis pejabat pelaksana", "Metode curah pendapat pelatihan pimpinan menengah"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Nilai Organisasi sebagai Bangunan Rumah Bersama</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Nilai-nilai organisasi</strong> diibaratkan menjadi <strong>bangunan rumah yang ditinggali bersama</strong> oleh seluruh <strong>pegawai, manajer, pimpinan</strong>, serta <strong>para pemangku kepentingan (di luar organisasi)</strong> yang terlibat atau menjadi <strong>akibat dari perilaku organisasi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A secara tepat melukiskan fungsi nilai organisasi yang menaungi unsur internal maupun pihak eksternal terdampak pada ilustrasi tersebut. Tindakan sebuah organisasi senantiasa berinteraksi dan berdampak pada lingkungan luarnya, sehingga nilai bersama harus menjadi ruang bernaung yang inklusif. Sebagaimana dirumuskan dalam fungsi nilai-nilai organisasi, tata nilai menjadi bangunan rumah yang ditinggali bersama oleh pegawai, manajer, pimpinan, dan pemangku kepentingan di luar organisasi yang terlibat atau menjadi akibat perilaku organisasi. Dengan demikian, metafora bangunan rumah bersama tepat menggambarkan pengayoman menyeluruh ini.</p><p>Jawaban tepat: <strong>A. Bangunan rumah yang ditinggali bersama semua pihak</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan dokumen awal pada tahap persiapan sebelum pengembangan nilai-nilai organisasi dimulai.</li><li><strong>C</strong>: Merupakan contoh penerapan nilai melalui budaya kerapian administrasi penunjang kinerja.</li><li><strong>D</strong>: Merupakan kaidah ilmu manajemen untuk memberi kejelasan bertindak antarpemangku kepentingan.</li><li><strong>E</strong>: Merupakan teknik brainstorming intensif dalam pengembangan nilai bagi manajer madya dan puncak.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 11) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>bangunan rumah yang ditinggali bersama • di luar organisasi • akibat dari perilaku organisasi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Dalam kaitannya dengan pembentukan budaya kerja di suatu institusi, nilai-nilai organisasi berkedudukan fungsional sebagai...", "options": ["Aturan keluar masuknya barang kantor", "Risalah diskusi awal para eksekutif", "Fondasi pembangunan budaya organisasi", "Instrumen pembagian wewenang struktural", "Pedoman penggolongan jenjang jabatan"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Nilai-Nilai Organisasi sebagai Fondasi Budaya Organisasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Salah satu fungsi pokok <strong>nilai-nilai organisasi</strong> adalah menjadi <strong>fondasi pembangunan budaya organisasi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat mengenai kedudukan fungsional nilai-nilai organisasi terhadap pembentukan budaya kerja. Budaya organisasi yang kokoh tidak mungkin terbangun tanpa adanya landasan keyakinan nilai yang disepakati bersama terlebih dahulu oleh anggotanya. Dalam rincian fungsi nilai-nilai organisasi, dinyatakan secara eksplisit bahwa nilai-nilai organisasi menjadi fondasi pembangunan budaya organisasi. Oleh sebab itu, keberadaan nilai organisasi berperan langsung sebagai landasan penopang utama tumbuhnya budaya organisasi.</p><p>Jawaban tepat: <strong>C. Fondasi pembangunan budaya organisasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Keluar masuknya barang merupakan salah satu proses ketatausahaan dalam budaya kerapian administrasi.</li><li><strong>B</strong>: Risalah diskusi awal merupakan materi tertulis yang dibagikan pada tahap pra-pengembangan nilai.</li><li><strong>D</strong>: Pembagian wewenang struktural merupakan syarat manajemen untuk memperjelas tugas pemangku kepentingan.</li><li><strong>E</strong>: Penggolongan jenjang jabatan merupakan aspek tata kelola kepegawaian, bukan fungsi dasar pembentuk budaya.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 11) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>fondasi pembangunan budaya organisasi • Nilai-nilai organisasi menjadi fondasi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Nilai-nilai organisasi harus mampu dipilih oleh seseorang atau sekelompok orang sebagai dasar berkegiatan maupun tujuan akhir tindakan yang bersifat...", "options": ["Keyakinan abadi (enduring belief)", "Orientasi hasil (outcome orientation)", "Rasa memiliki (sense of belonging)", "Orientasi tim (team orientation)", "Perhatian detail (attention to detail)"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Nilai Organisasi sebagai Keyakinan Abadi (Enduring Belief)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Nilai-nilai organisasi</strong> harus bisa menjadi <strong>keyakinan abadi (<em>enduring belief</em>)</strong> yang dipilih oleh <strong>seseorang atau sekelompok orang</strong> sebagai dasar melakukan kegiatan tertentu (<em>mode of conduct</em>) atau sebagai tujuan akhir tindakannya (<em>end state of existence</em>).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan istilah konsep yang tepat untuk melengkapi fungsi nilai-nilai organisasi tersebut. Agar tata nilai sanggup memandu perilaku jangka panjang maupun cita-cita organisasi secara konsisten, nilai harus tertanam kuat sebagai kepercayaan yang bertahan lama. Dalam uraian fungsi nilai-nilai organisasi, ditegaskan bahwa nilai organisasi harus bisa menjadi keyakinan abadi (enduring belief) yang dipilih seseorang atau sekelompok orang sebagai dasar kegiatan maupun tujuan akhir tindakannya. Dengan demikian, konsep keyakinan abadi (enduring belief) menjadi jawaban yang paling tepat.</p><p>Jawaban tepat: <strong>A. Keyakinan abadi (enduring belief)</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan karakteristik budaya organisasi menurut Stephen P. Robbins yang memusatkan perhatian manajemen pada hasil.</li><li><strong>C</strong>: Merupakan sikap keterikatan anggota yang tumbuh karena dilibatkan dalam pengambilan keputusan strategi organisasi.</li><li><strong>D</strong>: Merupakan karakteristik budaya organisasi menurut Robbins yang mengelola pekerjaan berorientasi pada tim kerja.</li><li><strong>E</strong>: Merupakan karakteristik budaya organisasi menurut Robbins yang menuntut kecermatan dan analisis pada hal rinci.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 12) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>keyakinan abadi (enduring belief) • seseorang atau sekelompok orang</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C4 - Analisis", "question": "Perbedaan peran nilai organisasi antara mode of conduct dan end state of existence dalam keyakinan abadi terletak pada kedudukannya sebagai...", "options": ["Ketentuan pimpinan pusat versus pedoman staf pelaksana", "Pedoman jajaran internal versus acuan mitra eksternal", "Rencana kegiatan awal versus pengendalian hasil akhir", "Standar disiplin waktu versus inovasi budaya organisasi", "Dasar kegiatan tertentu versus tujuan akhir tindakan"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Perbedaan Mode of Conduct dan End State of Existence</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam fungsinya sebagai <em>enduring belief</em>, <strong>nilai-nilai organisasi</strong> berperan sebagai <strong>dasar untuk melakukan suatu kegiatan tertentu (<em>mode of conduct</em>)</strong> atau sebagai <strong>tujuan akhir tindakannya (<em>end state of existence</em>)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E secara akurat membedakan makna konseptual antara mode of conduct dan end state of existence. Nilai organisasi dapat digunakan sebagai landasan cara menjalankan suatu aktivitas spesifik, ataupun dijadikan muara akhir yang hendak diwujudkan dari rangkaian perbuatan. Sesuai rumusan fungsi nilai-nilai organisasi, keyakinan abadi dipilih sebagai dasar untuk melakukan suatu kegiatan tertentu (mode of conduct) atau sebagai tujuan akhir tindakannya (end state of existence). Oleh karena itu, pembedaan antara dasar kegiatan tertentu dan tujuan akhir tindakan merupakan pemilahan yang tepat.</p><p>Jawaban tepat: <strong>E. Dasar kegiatan tertentu versus tujuan akhir tindakan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Nilai organisasi membimbing kegiatan seluruh jenjang baik pegawai, manajer, maupun pimpinan secara selaras.</li><li><strong>B</strong>: Cakupan pihak internal dan pemangku kepentingan eksternal dibahas dalam fungsi nilai sebagai bangunan rumah bersama.</li><li><strong>C</strong>: Perencanaan dan pengendalian merupakan bagian dari empat prinsip manajemen dalam implementasi kegiatan organisasi.</li><li><strong>D</strong>: Disiplin ketepatan waktu dan inovasi budaya merupakan dua contoh kebiasaan baik pada tahap implementasi nilai.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 12) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>melakukan suatu kegiatan tertentu • mode of conduct • end state of existence</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Saat rapat kebijakan, pegawai memakai tata nilai kantor sebagai ukuran menimbang alternatif pilihan dan menyampaikan pendapat. Nilai tersebut berfungsi sebagai...", "options": ["Risalah diskusi pendahuluan lintas sektor kerja", "Kriteria atau standar pilihan dan argumentasi", "Aturan ketertiban keluar masuknya barang unit", "Struktur pembagian wewenang pejabat pelaksana", "Materi pelatihan kepemimpinan manajer menengah"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Nilai Organisasi sebagai Kriteria atau Standar Penilaian dan Argumentasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Nilai-nilai organisasi</strong> berfungsi sebagai <strong>kriteria atau standar</strong> untuk <strong>melakukan penilaian, menentukan pilihan, bersikap, berargumentasi</strong>, maupun menilai kinerja (<em>performance</em>).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan fungsi nilai organisasi yang tepat tercermin dalam aktivitas rapat kebijakan tersebut. Ketika anggota organisasi dihadapkan pada beberapa opsi dan perlu mengadu gagasan secara sehat, tata nilai hadir sebagai tolok ukur objektif bersama. Dalam rincian fungsi nilai-nilai organisasi, dijelaskan bahwa nilai berfungsi sebagai kriteria atau standar untuk melakukan penilaian, menentukan pilihan, bersikap, serta berargumentasi. Dengan demikian, pemakaian tata nilai untuk menimbang pilihan dan berpendapat di rapat mencerminkan fungsi kriteria atau standar tersebut.</p><p>Jawaban tepat: <strong>B. Kriteria atau standar pilihan dan argumentasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan materi tertulis hasil diskusi awal pada tahap persiapan sebelum pengembangan nilai dimulai.</li><li><strong>C</strong>: Merupakan bagian dari proses ketatausahaan dalam contoh budaya kerapian administrasi organisasi.</li><li><strong>D</strong>: Merupakan syarat manajemen organisasi untuk memperjelas batas tindakan para pemangku kepentingan.</li><li><strong>E</strong>: Merupakan metode pengembangan nilai yang dilakukan bersamaan dengan pelatihan middle atau top manager.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 12) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>kriteria atau standar • menentukan pilihan, bersikap, berargumentasi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Dalam fungsinya sebagai kriteria atau standar penilaian kinerja (performance), nilai-nilai organisasi digunakan untuk menilai kinerja dari...", "options": ["Tim eksekutif perumus naskah nilai awal", "Petugas pengelola surat dan barang kantor", "Peserta pelatihan manajer tingkat menengah", "Orang-orang yang terlibat dan organisasi", "Pihak pesaing pada sektor industri serupa"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sasaran Penilaian Kinerja (Performance) Berbasis Nilai Organisasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Nilai-nilai organisasi</strong> berfungsi sebagai kriteria atau standar untuk <strong>menilai kinerja (<em>performance</em>)</strong> dari <strong>orang-orang yang terlibat dan organisasi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan cakupan sasaran yang tepat pada fungsi nilai organisasi sebagai standar evaluasi kinerja. Tolok ukur nilai tidak berhenti pada pengukuran prestasi sumber daya manusia secara perorangan, tetapi juga mengukur keberhasilan lembaga secara kolektif. Sebagaimana tertulis pada fungsi nilai-nilai organisasi, tata nilai berfungsi sebagai kriteria atau standar untuk menilai kinerja (performance) orang-orang yang terlibat dan organisasi. Oleh sebab itu, subjek penilaian kinerja berbasis nilai mencakup individu yang terlibat sekaligus entitas organisasinya.</p><p>Jawaban tepat: <strong>D. Orang-orang yang terlibat dan organisasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Terlalu sempit karena penilaian kinerja berbasis nilai berlaku bagi seluruh orang yang terlibat, bukan perumus awal.</li><li><strong>B</strong>: Pengelola surat dan barang berkaitan dengan contoh kerapian administrasi, bukan keseluruhan sasaran penilaian performance.</li><li><strong>C</strong>: Peserta pelatihan manajer menengah berkaitan dengan metode brainstorming pada proses penggalian nilai organisasi.</li><li><strong>E</strong>: Pesaing eksternal berkaitan dengan pembedaan identitas DNA perusahaan menurut Roope Heinila, bukan objek penilaian kinerja.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 12) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menilai kinerja (performance) • orang-orang yang terlibat dan organisasi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Nilai-nilai yang telah digali dan dirumuskan para pemangku kepentingan tidak akan bermakna sama sekali apabila tidak...", "options": ["Dibukukan menjadi naskah akademik pimpinan", "Diseminarkan bersama konsultan manajemen luar", "Diterapkan dalam kehidupan sehari-hari", "Dipadukan dengan atribut promosi eksternal", "Diperbarui pada setiap pergantian pimpinan"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pentingnya Implementasi Nilai Organisasi dalam Kehidupan Sehari-hari</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Nilai-nilai organisasi yang sudah digali dan dirumuskan menjadi pedoman berperilaku <strong>tidak akan bermakna sama sekali</strong> jika tidak <strong>diimplementasikan atau diterapkan dalam kehidupan sehari-hari</strong> oleh pemangku kepentingan di organisasi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan syarat utama agar rumusan nilai-nilai organisasi memiliki arti nyata bagi institusi. Dokumen pedoman perilaku yang disusun dengan baik akan kehilangan manfaatnya apabila berhenti sebagai rumusan tertulis tanpa diwujudkan ke dalam tindakan kerja nyata. Dalam prinsip implementasi nilai-nilai organisasi, ditekankan bahwa nilai yang telah digali dan dirumuskan tidak akan bermakna sama sekali jika tidak diimplementasikan atau diterapkan dalam kehidupan sehari-hari oleh pemangku kepentingan di organisasi. Dengan demikian, penerapan keseharian menjadi penentu kebermaknaan nilai.</p><p>Jawaban tepat: <strong>C. Diterapkan dalam kehidupan sehari-hari</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pembukuan naskah tertulis sudah dilakukan pada tahap perumusan, namun belum menjamin kebermaknaan praktik harian.</li><li><strong>B</strong>: Seminar bersama pihak luar bukan penentu utama bermaknanya nilai organisasi dalam aktivitas kerja rutin.</li><li><strong>D</strong>: Atribut promosi eksternal tidak menggantikan keharusan implementasi perilaku sehari-hari oleh pemangku kepentingan.</li><li><strong>E</strong>: Nilai organisasi justru harus menjadi keyakinan abadi (enduring belief), bukan diganti setiap pergantian pimpinan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 12) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>tidak akan bermakna sama sekali • diterapkan dalam kehidupan sehari-hari</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C4 - Analisis", "question": "Proses dalam budaya kerapian administrasi yang dihidupkan untuk membantu pencapaian kinerja organisasi meliputi kegiatan berikut, KECUALI...", "options": ["Proses promosi rasionalitas tata nilai", "Proses surat-menyurat kedinasan kantor", "Proses penatausahaan keuangan organisasi", "Proses memperoleh pegawai organisasi", "Proses keluar masuknya barang kantor"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Contoh Budaya Kerapian Administrasi Pendukung Kinerja</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Kerapian administrasi</strong> sebagai budaya organisasi yang membantu pencapaian kinerja mencakup proses administrasi mulai dari <strong>surat-menyurat, keuangan, proses memperoleh pegawai, hingga proses keluar masuknya barang</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan pilihan yang tepat untuk dikecualikan karena bukan bagian dari rincian proses kerapian administrasi sehari-hari. Promosi rasionalitas perlunya tata nilai merupakan langkah persiapan eksekutif sebelum memulai proses pengembangan nilai organisasi, bukan kegiatan ketatausahaan operasional. Dalam contoh menghidupkan kerapian administrasi sebagai budaya organisasi, dirinci bahwa proses administrasi mulai dari surat-menyurat, keuangan, proses memperoleh pegawai, hingga proses keluar masuknya barang akan membantu pencapaian kinerja organisasi. Oleh karena itu, opsi A berada di luar cakupan proses administrasi tersebut.</p><p>Jawaban tepat: <strong>A. Proses promosi rasionalitas tata nilai</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Termasuk cakupan kerapian administrasi karena surat-menyurat menjadi contoh pertama proses administrasi penunjang kinerja.</li><li><strong>C</strong>: Termasuk cakupan kerapian administrasi karena penatausahaan keuangan disebut langsung dalam rincian administrasi organisasi.</li><li><strong>D</strong>: Termasuk cakupan kerapian administrasi karena proses memperoleh pegawai termasuk administrasi pendukung capaian kinerja.</li><li><strong>E</strong>: Termasuk cakupan kerapian administrasi karena ketertiban keluar masuknya barang menjadi bagian rangkaian administrasi tersebut.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 12) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>kerapian administrasi sebagai budaya organisasi • proses memperoleh pegawai • keluar masuknya barang</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Kepala kantor merinci otoritas tiap seksi agar pegawai jelas dalam bertindak serta tahu hal yang boleh dan tidak boleh dilakukan. Kebijakan ini menerapkan syarat...", "options": ["Promosi rasionalitas perumusan nilai awal", "Pembagian wewenang yang jelas antar pihak", "Pelatihan kepemimpinan manajer tingkat madya", "Pencatatan keluar masuknya barang gudang", "Penyusunan risalah diskusi lintas sektor"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pembagian Wewenang yang Jelas sebagai Kunci Keberhasilan Kinerja</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam <strong>ilmu manajemen</strong>, organisasi mensyaratkan adanya <strong>pembagian wewenang yang jelas antar pemangku kepentingan</strong> sebagai <strong>kunci keberhasilan kinerja organisasi</strong>, sehingga pegawai memiliki kejelasan bertindak serta tahu mana yang <strong>boleh dan tidak boleh dilakukan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan prasyarat ilmu manajemen yang tepat diterapkan oleh kepala kantor tersebut. Penataan otoritas yang tegas menghindarkan tumpang-tindih pekerjaan sekaligus memberikan kepastian koridor kewenangan bagi setiap pelaksana. Sesuai kaidah manajemen dalam implementasi budaya kerja, pembagian wewenang yang jelas antarpemangku kepentingan menjadi kunci keberhasilan kinerja karena membuat pegawai mempunyai kejelasan bertindak dan berperilaku serta kejelasan mana yang boleh dan tidak boleh dilakukan. Dengan demikian, perincian otoritas seksi tersebut mencerminkan pembagian wewenang yang jelas.</p><p>Jawaban tepat: <strong>B. Pembagian wewenang yang jelas antar pihak</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan langkah kedua persiapan para eksekutif sebelum proses pengembangan nilai organisasi dilakukan.</li><li><strong>C</strong>: Merupakan kegiatan pelatihan yang dipadukan dengan brainstorming intensif pada tahap identifikasi nilai.</li><li><strong>D</strong>: Merupakan salah satu aktivitas ketatausahaan dalam contoh budaya kerapian administrasi organisasi.</li><li><strong>E</strong>: Merupakan langkah pertama pra-pengembangan nilai untuk menunjukkan komitmen awal lintas sektor.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 12) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pembagian wewenang yang jelas • kunci keberhasilan kinerja organisasi • boleh dan tidak boleh dilakukan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Budaya disiplin dalam ketepatan waktu dan menaati aturan semestinya melekat di mana pun berada karena disiplin merupakan...", "options": ["Identitas pembeda perusahaan dari kompetitor", "Tahap awal perumusan konsensus eksekutif", "Wujud orientasi kerja berbasis tim kolektif", "Tolok ukur pembagian wewenang struktural", "Karakter prasyarat untuk menjadi sukses"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Disiplin Waktu dan Aturan sebagai Karakter Prasyarat Sukses</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Disiplin dalam ketepatan waktu dan menaati aturan</strong> yang telah ditetapkan merupakan budaya organisasi yang semestinya melekat di mana pun berada, karena <strong>disiplin merupakan karakter prasyarat untuk menjadi sukses</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan alasan mendasar yang tepat mengapa budaya disiplin harus melekat pada setiap anggota organisasi di mana pun bertugas. Ketertiban menghargai waktu dan kepatuhan pada regulasi merupakan fondasi watak utama yang menopang pencapaian keberhasilan kerja. Sebagaimana ditegaskan dalam implementasi nilai dan budaya organisasi, disiplin dalam ketepatan waktu dan menaati aturan semestinya telah melekat di mana pun berada karena disiplin merupakan karakter prasyarat untuk menjadi sukses. Oleh sebab itu, kedudukan disiplin sebagai prasyarat kesuksesan menjadi jawaban yang tepat.</p><p>Jawaban tepat: <strong>E. Karakter prasyarat untuk menjadi sukses</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Identitas pembeda perusahaan dari pesaing merupakan pengertian nilai sebagai DNA perusahaan menurut Roope Heinila.</li><li><strong>B</strong>: Perumusan konsensus eksekutif merupakan tahapan dalam proses mengidentifikasi nilai-nilai organisasi.</li><li><strong>C</strong>: Orientasi kerja berbasis tim merupakan dimensi team orientation dalam karakteristik budaya menurut Stephen P. Robbins.</li><li><strong>D</strong>: Pembagian wewenang struktural merupakan syarat ilmu manajemen untuk memperjelas batas tindakan pegawai.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 12) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Disiplin dalam ketepatan waktu • menaati aturan yang telah ditetapkan • karakter prasyarat untuk menjadi sukses</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Manajemen memperbarui cara penguatan budaya kerja secara segar sehingga anggota tim terdorong melahirkan ide-ide kreatif baru. Praktik ini menerapkan kebiasaan baik berupa...", "options": ["Kerapian administrasi persuratan kedinasan", "Pembagian wewenang kinerja antarunit seksi", "Inovasi dalam mengelola budaya organisasi", "Disiplin ketaatan pada aturan jam layanan", "Promosi rasionalitas nilai lintas departemen"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kebiasaan Baik Inovasi dalam Mengelola Budaya Organisasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Salah satu kebiasaan baik yang perlu dibangun organisasi adalah adanya <strong>inovasi dalam mengelola budaya organisasi</strong>, yang mendorong anggota tim <strong>melahirkan ide-ide kreatif dan inovasi baru</strong> demi kemajuan organisasi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan kebiasaan baik organisasi yang tepat tercermin pada pembaruan pengelolaan budaya kerja tersebut. Pengelolaan budaya kerja yang dinamis dan terbuka terhadap pembaruan akan memicu keberanian anggota tim untuk berkreasi bagi kemajuan lembaga. Dalam uraian implementasi nilai organisasi, disebutkan bahwa salah satu kebiasaan baik bagi organisasi adalah adanya inovasi dalam mengelola budaya organisasi yang mendorong anggota tim melahirkan ide-ide kreatif dan inovasi baru demi kemajuan organisasi. Dengan demikian, praktik manajemen tersebut merupakan wujud inovasi pengelolaan budaya organisasi.</p><p>Jawaban tepat: <strong>C. Inovasi dalam mengelola budaya organisasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kerapian administrasi berfokus pada ketertiban surat-menyurat, keuangan, rekrutmen pegawai, dan mutasi barang.</li><li><strong>B</strong>: Pembagian wewenang kinerja berfokus pada kejelasan otoritas serta batas boleh-tidaknya tindakan pegawai.</li><li><strong>D</strong>: Disiplin waktu dan aturan berfokus pada kepatuhan ketepatan waktu dan regulasi sebagai prasyarat sukses.</li><li><strong>E</strong>: Promosi rasionalitas nilai merupakan agenda persiapan eksekutif sebelum proses pengembangan nilai dimulai.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 12) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>inovasi dalam mengelola budaya organisasi • melahirkan ide-ide kreatif</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Pada intinya, penguatan nilai dan budaya organisasi menuntut dilakukannya semua prinsip manajemen secara utuh, yaitu mulai dari...", "options": ["Penjajakan awal, penyusunan, penetapan, dan sosialisasi", "Sosialisasi nilai, pelatihan, pendampingan, dan penghargaan", "Identifikasi awal, penyusunan, pengesahan, dan penyebarluasan", "Perencanaan, pengorganisasian, pelaksanaan, dan pengendalian", "Penggalian isu, perumusan, komunikasi, dan pemutakhiran"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pelaksanaan Empat Prinsip Manajemen dalam Budaya Organisasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pada intinya, dalam menghidupkan nilai dan budaya organisasi, <strong>semua prinsip-prinsip manajemen</strong> perlu dilakukan mulai dari <strong>perencanaan, pengorganisasian, pelaksanaan kegiatan, hingga pengendalian</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D secara tepat menyebutkan rangkaian empat prinsip manajemen yang wajib dijalankan dalam mengelola implementasi nilai dan budaya organisasi. Keberhasilan internalisasi budaya kerja membutuhkan siklus manajerial yang utuh dari penyusunan rencana hingga pengawasan capaian agar berjalan terarah. Dalam penutup pembahasan implementasi nilai organisasi, ditegaskan bahwa semua prinsip-prinsip manajemen perlu dilakukan, mulai dari perencanaan, pengorganisasian, pelaksanaan kegiatan, hingga pengendalian. Oleh karena itu, keempat tahapan prinsip manajemen pada opsi D merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>D. Perencanaan, pengorganisasian, pelaksanaan, dan pengendalian</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan alur umum pembentukan kebijakan, bukan empat prinsip pokok manajemen yang ditetapkan.</li><li><strong>B</strong>: Merupakan ragam kegiatan pembinaan sumber daya manusia, bukan rangkaian empat fungsi dasar manajemen.</li><li><strong>C</strong>: Merupakan tahapan penyusunan dokumen perumusan nilai, tanpa memuat fungsi pengorganisasian dan pengendalian.</li><li><strong>E</strong>: Merupakan siklus komunikasi kebijakan publik, bukan rumusan prinsip perencanaan hingga pengendalian manajemen.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 12) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>semua prinsip-prinsip manajemen • perencanaan, pengorganisasian, pelaksanaan kegiatan • hingga pengendalian</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Agar pedoman berperilaku bagi seluruh anggota tidak berakhir sebagai pajangan belaka, langkah yang menjadi keharusan organisasi adalah...", "options": ["Merumuskan ulang teks kesepakatan para eksekutif", "Menyeragamkan atribut visual di ruang pelayanan", "Menjalankan aplikasi dan internalisasi nilai-nilai", "Menetapkan pembagian wewenang kerja antarjabatan", "Memperbarui tata kelola administrasi surat kantor"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Keharusan Aplikasi dan Internalisasi Nilai Organisasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Aplikasi atau internalisasi nilai-nilai</strong> dalam organisasi merupakan sebuah <strong>keharusan</strong> agar pedoman berperilaku bagi seluruh anggota tidak berakhir sebagai <strong>pajangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang paling tepat mengenai keharusan utama dalam mengimplementasikan nilai organisasi. Nilai yang telah dirumuskan baru memiliki makna nyata apabila diterapkan langsung dalam aktivitas keseharian seluruh anggota. Sesuai prinsip implementasi nilai keorganisasian, aplikasi atau internalisasi nilai-nilai wajib dijalankan agar pedoman berperilaku tersebut tidak dianggap sebagai pajangan belaka. Dengan demikian, internalisasi nilai menjadi kunci hidupnya pedoman perilaku di lingkungan kerja.</p><p>Jawaban tepat: <strong>C. Menjalankan aplikasi dan internalisasi nilai-nilai</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan tahapan perumusan tertulis kesepakatan eksekutif saat proses identifikasi nilai organisasi.</li><li><strong>B</strong>: Menyeragamkan atribut visual tanpa pengamalan perilaku justru berisiko menjadikan nilai sebagai simbol pajangan.</li><li><strong>D</strong>: Merupakan prinsip manajemen terkait kejelasan pembagian wewenang kinerja antar-pemangku kepentingan.</li><li><strong>E</strong>: Merupakan contoh kerapian proses administrasi ketatausahaan kantor, bukan konsep keharusan internalisasi nilai.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 13) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>internalisasi nilai-nilai dalam organisasi • dianggap sebagai pajangan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Selama sebuah organisasi masih hidup dan berkembang, pelaksanaan internalisasi nilai-nilai di dalamnya selayaknya dilakukan secara...", "options": ["Selektif, fleksibel, dan berkala", "Masif, terstruktur, dan kontinu", "Bertahap, adaptif, dan terjadwal", "Kolektif, terbuka, dan insidental", "Inklusif, terukur, dan situasional"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sifat Pelaksanaan Internalisasi Nilai-Nilai Organisasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Internalisasi nilai-nilai organisasi sudah selayaknya dilakukan secara <strong>masif, terstruktur, dan kontinu</strong> selama organisasi masih <strong>hidup dan berkembang</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B menyebutkan secara tepat tiga sifat pelaksanaan internalisasi nilai organisasi yang efektif. Penanaman nilai membutuhkan jangkauan luas ke seluruh pegawai, penataan program yang rapi, serta kesinambungan tanpa putus. Dalam kaidah internalisasi nilai organisasi, proses tersebut harus dilaksanakan secara masif, terstruktur, dan kontinu sepanjang organisasi masih hidup dan berkembang. Oleh sebab itu, ketiga karakter tersebut menjadi syarat terbentuknya komitmen perilaku yang kuat.</p><p>Jawaban tepat: <strong>B. Masif, terstruktur, dan kontinu</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena pelaksanaan selektif dan berkala belum menjangkau seluruh anggota secara masif dan kontinu.</li><li><strong>C</strong>: Kurang tepat karena penjadwalan terbatas belum mencerminkan sifat masif dan berkesinambungan sepanjang usia organisasi.</li><li><strong>D</strong>: Kurang tepat karena sifat insidental bertentangan dengan keharusan internalisasi yang terstruktur dan kontinu.</li><li><strong>E</strong>: Kurang tepat karena pelaksanaan situasional tidak menjamin konsistensi penanaman nilai selama organisasi berkembang.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 13) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>masif, terstruktur, dan kontinu • hidup dan berkembang</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Penerapan nilai-nilai organisasi yang telah menjadi komitmen bersama secara masif dan terus-menerus pada akhirnya akan membentuk sebuah...", "options": ["Struktur organisasi", "Pembagian wewenang", "Kerapian administrasi", "Budaya organisasi", "Konsensus eksekutif"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pembentukan Budaya Organisasi dari Komitmen Nilai</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Penerapan <strong>nilai-nilai organisasi</strong> yang telah menjadi <strong>komitmen bersama</strong> secara masif dan terus-menerus pada akhirnya akan membentuk sebuah <strong>budaya organisasi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan muara akhir yang tepat dari penerapan komitmen nilai organisasi secara berkelanjutan. Ketika keyakinan bersama diamalkan secara luas dan berulang oleh seluruh sumber daya manusia, kebiasaan positif tersebut akan mengakar di lingkungan kerja. Secara konseptual, penerapan nilai-nilai organisasi yang telah menjadi komitmen bersama secara masif dan terus-menerus akan membentuk budaya organisasi. Dengan demikian, budaya organisasi lahir langsung dari konsistensi pengamalan nilai bersama.</p><p>Jawaban tepat: <strong>D. Budaya organisasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan bagan susunan hierarki kelembagaan, bukan hasil pengamalan nilai komitmen bersama.</li><li><strong>B</strong>: Merupakan kejelasan batas tugas dan tanggung jawab kinerja antar-pemangku kepentingan manajemen.</li><li><strong>C</strong>: Merupakan salah satu contoh praktik tata kelola surat-menyurat, keuangan, dan barang di kantor.</li><li><strong>E</strong>: Merupakan tahapan pencarian kesepakatan para pimpinan saat merumuskan draf awal nilai organisasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 13) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menjadi komitmen bersama • membentuk sebuah budaya organisasi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan riset para peneliti, budaya organisasi terbukti berpengaruh positif dan sangat signifikan terhadap peningkatan kinerja organisasi serta...", "options": ["Kenaikan pangkat pegawai", "Kejelasan wewenang kerja", "Kerapian surat-menyurat", "Kesesuaian alokasi dana", "Kepuasan kerja pegawai"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengaruh Budaya Organisasi Menurut Riset Peneliti</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Riset para peneliti menunjukkan bahwa <strong>budaya organisasi</strong> berpengaruh positif dan sangat signifikan pada <strong>peningkatan kinerja organisasi</strong> serta <strong>kepuasan kerja pegawai</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E melengkapi secara akurat dua dampak signifikan budaya organisasi menurut hasil riset para peneliti. Lingkungan kerja yang berlandaskan budaya kuat mampu memacu produktivitas lembaga sekaligus menumbuhkan kenyamanan batin bagi para anggotanya. Berdasarkan temuan riset para peneliti, implementasi nilai yang membentuk budaya organisasi berpengaruh sangat signifikan terhadap kinerja organisasi dan kepuasan kerja pegawai. Oleh karena itu, kepuasan kerja pegawai merupakan pasangan dampak positif yang tepat.</p><p>Jawaban tepat: <strong>E. Kepuasan kerja pegawai</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan urusan karier administrasi kepegawaian, bukan variabel kedua dalam temuan riset budaya organisasi tersebut.</li><li><strong>B</strong>: Merupakan prasyarat pengorganisasian manajemen agar pelaku organisasi mengetahui batas tindakan yang diperbolehkan.</li><li><strong>C</strong>: Merupakan bagian dari proses tertib administrasi harian untuk membantu kelancaran operasional instansi.</li><li><strong>D</strong>: Merupakan aspek pengelolaan anggaran keuangan, bukan dampak psikologis pegawai yang disoroti riset peneliti.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 13) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>peningkatan kinerja organisasi • kepuasan kerja pegawai</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Suasana pelayanan yang tertib di suatu kantor langsung memberi petunjuk cara bertindak bagi pegawai baru saat bekerja. Hal ini menunjukkan budaya organisasi sebagai...", "options": ["Identitas sebuah organisasi", "Pembagian wewenang kinerja", "Tahapan identifikasi nilai", "Prinsip pengendalian kerja", "Kriteria evaluasi anggaran"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kedudukan Budaya Organisasi sebagai Identitas Organisasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Budaya organisasi (<em>organizational culture</em>)</strong> merupakan <strong>identitas sebuah organisasi</strong> karena mampu <strong>membentuk atmosfer</strong> organisasi dan menjadi <strong>petunjuk bagaimana pegawai harus berperilaku ketika bekerja</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan konsep yang paling tepat untuk menggambarkan kedudukan budaya organisasi pada situasi kantor tersebut. Suasana atau atmosfer kerja yang khas serta kejelasan tuntunan bersikap saat bertugas merupakan penciri utama jati diri suatu institusi. Secara konseptual, budaya organisasi disebut sebagai identitas sebuah organisasi karena membentuk atmosfer di dalamnya sekaligus memberi petunjuk cara pegawai berperilaku ketika bekerja. Dengan demikian, ilustrasi tersebut mencerminkan kedudukan budaya sebagai identitas organisasi.</p><p>Jawaban tepat: <strong>A. Identitas sebuah organisasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Berkaitan dengan pendelegasian otoritas jabatan antar-pemangku kepentingan, bukan pembentuk atmosfer identitas lembaga.</li><li><strong>C</strong>: Berkaitan dengan langkah awal menggali dan mendiskusikan nilai harian menurut kerangka Susan M. Heathfield.</li><li><strong>D</strong>: Merupakan bagian akhir dari siklus prinsip manajemen umum setelah perencanaan, pengorganisasian, dan pelaksanaan.</li><li><strong>E</strong>: Berkaitan dengan tolok ukur pemeriksaan keuangan, bukan kedudukan budaya organisasi sebagai pembentuk perilaku.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 13) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>identitas sebuah organisasi • membentuk atmosfer yang ada di organisasi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C4 - Analisis", "question": "Secara sederhana, budaya organisasi dapat dilihat sebagai gabungan dari berbagai elemen pengarah berikut di dalam organisasi, KECUALI...", "options": ["Nilai-nilai yang diyakini bersama", "Kode etik perilaku kepegawaian", "Daftar inventaris barang kantor", "Visi dan misi milik organisasi", "Tujuan (goals) capaian instansi"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Komponen Pembentuk Budaya Organisasi Secara Sederhana</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Secara sederhana, <strong>budaya organisasi</strong> dapat dilihat sebagai gabungan dari <strong>nilai</strong>, <strong>kode etik</strong>, <strong>visi dan misi</strong>, hingga <strong>tujuan (<em>goals</em>)</strong> yang ingin dicapai sebuah organisasi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat karena tidak termasuk dalam unsur gabungan pembentuk budaya organisasi secara sederhana. Daftar inventaris barang merupakan dokumen pencatatan logistik administratif, bukan elemen normatif-strategis pembentuk budaya kerja. Dalam perumusan sederhana mengenai budaya organisasi, unsur pembentuknya meliputi gabungan nilai, kode etik, visi dan misi, hingga tujuan (goals) yang ingin dicapai organisasi. Oleh karena itu, daftar inventaris barang kantor dikecualikan dari komponen tersebut.</p><p>Jawaban tepat: <strong>C. Daftar inventaris barang kantor</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Termasuk unsur pembentuk budaya organisasi karena nilai menjadi fondasi utama keyakinan bersama.</li><li><strong>B</strong>: Termasuk unsur pembentuk budaya organisasi karena kode etik mengatur standar kepatuhan perilaku anggota.</li><li><strong>D</strong>: Termasuk unsur pembentuk budaya organisasi karena visi dan misi mengarahkan cita-cita serta mandat lembaga.</li><li><strong>E</strong>: Termasuk unsur pembentuk budaya organisasi karena tujuan (goals) menjadi sasaran akhir yang ingin dicapai.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 13) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>gabungan nilai, kode etik • visi dan misi, hingga tujuan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Tokoh yang mendefinisikan budaya organisasi sebagai nilai-nilai dominan yang disebarluaskan dan dipakai sebagai filosofi kerja pegawai adalah...", "options": ["Gordon Allport", "Antony Giddens", "Roope Heinila", "Moeljono Djokosantoso", "Stephen P. Robbins"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Budaya Organisasi Menurut Moeljono Djokosantoso</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Moeljono Djokosantoso</strong> mendefinisikan <strong>budaya organisasi</strong> sebagai <strong>nilai-nilai dominan</strong> yang disebarluaskan di dalam organisasi dan dipakai sebagai <strong>acuan atau filosofi kerja pegawai</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan tokoh pencetus definisi budaya organisasi yang ditanyakan pada soal. Rumusan ini menitikberatkan pada penyebarluasan nilai-nilai utama di lingkungan internal agar menjadi falsafah kerja bagi seluruh aparatur. Sebagaimana dikemukakan Moeljono Djokosantoso, budaya organisasi adalah nilai-nilai dominan yang disebarluaskan di dalam organisasi serta digunakan sebagai acuan atau filosofi kerja pegawai. Dengan demikian, ciri khas istilah nilai dominan dan filosofi kerja merujuk tepat pada Moeljono Djokosantoso.</p><p>Jawaban tepat: <strong>D. Moeljono Djokosantoso</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Mendefinisikan nilai sebagai keyakinan yang membuat seseorang melakukan tindakan berdasarkan pilihannya.</li><li><strong>B</strong>: Mendefinisikan nilai sebagai gagasan mengenai apa yang layak, dikehendaki, serta baik dan buruk.</li><li><strong>C</strong>: Mengibaratkan nilai organisasi sebagai DNA perusahaan yang membedakan organisasi dalam kompetisi.</li><li><strong>E</strong>: Mendefinisikan budaya organisasi sebagai sistem makna bersama yang membedakan organisasi dengan organisasi lain.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 13) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Moeljono Djokosantoso • filosofi kerja pegawai</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Pegawai kantor pelayanan merujuk budaya kerja saat menyelesaikan kendala koordinasi staf maupun keluhan masyarakat. Hal ini menunjukkan fungsi budaya sebagai...", "options": ["Pedoman SDM menghadapi masalah internal dan eksternal", "Dasar pimpinan membagi wewenang struktural antarunit", "Acuan eksekutif merumuskan konsensus diskusi pendahuluan", "Standar bagian umum mengelola keluar masuknya barang", "Sarana manajer memfasilitasi pelatihan curah pendapat"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Peran Budaya Organisasi Menghadapi Permasalahan Internal dan Eksternal</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Budaya organisasi</strong> berperan sebagai <strong>pedoman bagi sumber daya manusia</strong> dalam organisasi untuk menghadapi <strong>permasalahan internal atau eksternal</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A menggambarkan secara tepat fungsi budaya organisasi yang diterapkan oleh jajaran pegawai kantor pelayanan tersebut. Kendala koordinasi antarstaf merupakan persoalan internal, sedangkan keluhan masyarakat pengguna layanan merupakan persoalan eksternal yang keduanya diatasi berlandaskan budaya kerja. Dalam konsep peran budaya organisasi, budaya berfungsi sebagai pedoman bagi sumber daya manusia untuk menghadapi permasalahan internal maupun eksternal. Oleh sebab itu, rujukan perilaku tersebut membuktikan fungsi budaya dalam menangani kedua lingkup masalah.</p><p>Jawaban tepat: <strong>A. Pedoman SDM menghadapi masalah internal dan eksternal</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan fungsi struktur pengorganisasian jabatan, bukan pedoman budaya menghadapi persoalan internal-eksternal.</li><li><strong>C</strong>: Merupakan langkah awal jajaran pimpinan sebelum memulai proses pengembangan nilai-nilai organisasi.</li><li><strong>D</strong>: Merupakan aktivitas ketatausahaan logistik kantor, bukan fungsi budaya organisasi sebagai rujukan pemecahan masalah.</li><li><strong>E</strong>: Merupakan metode brainstorming pada pelatihan manajer menengah saat mengidentifikasi nilai organisasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 13) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pedoman bagi sumber daya manusia • permasalahan internal atau eksternal</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Pegawai mutasi mempelajari budaya kerja di unit barunya agar dapat menyatu secara selaras dalam tim. Tindakan ini mencerminkan budaya organisasi sebagai...", "options": ["Tahap identifikasi nilai harian di organisasi", "Usaha penyesuaian integrasi ke dalam organisasi", "Promosi rasionalitas pembentukan nilai pimpinan", "Pembagian wewenang kinerja seluruh pelaku unit", "Perumusan tertulis kesepakatan jajaran eksekutif"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Hakikat Budaya Organisasi sebagai Usaha Penyesuaian Integrasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Budaya organisasi</strong> merupakan sebuah <strong>usaha penyesuaian integrasi ke dalam organisasi</strong> agar setiap anggota selaras dengan kesepakatan bersama dalam bertindak.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan rumusan yang tepat untuk menjelaskan tindakan adaptasi pegawai mutasi pada unit kerja barunya. Kesediaan mempelajari tatanan nilai setempat bertujuan agar pola sikap individu dapat menyatu secara harmonis ke dalam sistem kolektif lembaga. Secara konseptual, budaya organisasi pada hakikatnya merupakan sebuah usaha penyesuaian integrasi ke dalam organisasi bagi seluruh anggotanya. Dengan demikian, langkah penyelarasan diri pegawai tersebut mencerminkan proses penyesuaian integrasi ke dalam organisasi.</p><p>Jawaban tepat: <strong>B. Usaha penyesuaian integrasi ke dalam organisasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan langkah awal penggalian nilai yang berkembang sehari-hari saat menyusun rumusan nilai baru.</li><li><strong>C</strong>: Merupakan tugas pendahuluan pimpinan untuk menjelaskan urgensi pembentukan nilai bagi kemajuan lembaga.</li><li><strong>D</strong>: Merupakan pengaturan otoritas kerja formal antarjabatan, bukan proses penyatuan budaya anggota baru.</li><li><strong>E</strong>: Merupakan tahapan pendokumentasian nilai yang telah disepakati eksekutif dengan dukungan para staf.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 13) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>usaha penyesuaian integrasi • ke dalam organisasi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Agar nilai kesepakatan bersama mampu membimbing pegawai saat bertindak di kantor, setiap anggota organisasi perlu melakukan langkah utama berupa...", "options": ["Mendokumentasikan arsip diskusi awal eksekutif", "Menyeragamkan format surat-menyurat kedinasan", "Menyusun ulang struktur kewenangan subbagian", "Memperbanyak poster slogan di ruang pelayanan", "Memahami serta menghayati nilai-nilai bersama"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pemahaman dan Penghayatan Nilai Kesepakatan Bersama</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Setiap anggota organisasi perlu <strong>memahami dan menghayati nilai-nilai yang menjadi kesepakatan bersama</strong> untuk membimbing mereka dalam <strong>bertindak atau berperilaku</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E menunjukkan kewajiban utama setiap anggota agar kesepakatan nilai organisasi benar-benar menuntun perilaku kerja harian. Tanpa pemahaman kognitif dan peresapan batin oleh masing-masing individu, rumusan nilai bersama tidak akan terwujud menjadi tindakan yang selaras. Sesuai prinsip integrasi budaya organisasi, masing-masing anggota perlu memahami dan menghayati nilai-nilai kesepakatan bersama guna membimbing mereka dalam bertindak atau berperilaku. Oleh karena itu, memahami serta menghayati nilai bersama merupakan langkah yang paling tepat.</p><p>Jawaban tepat: <strong>E. Memahami serta menghayati nilai-nilai bersama</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan kegiatan pengarsipan bahan pendahuluan pimpinan sebelum proses perumusan nilai dijalankan.</li><li><strong>B</strong>: Merupakan bagian dari kerapian administrasi persuratan kantor, bukan proses penghayatan nilai perilaku.</li><li><strong>C</strong>: Merupakan penataan struktur organisasi struktural, bukan internalisasi kesepakatan bersama pada diri anggota.</li><li><strong>D</strong>: Pemasangan poster tanpa penghayatan batin berpotensi membuat nilai berhenti sebagai pajangan visual.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 13) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>yang menjadi kesepakatan bersama • dalam bertindak atau berperilaku</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Dalam buku Organizational Behavior, Stephen P. Robbins mendefinisikan budaya organisasi sebagai suatu sistem makna bersama yang berfungsi...", "options": ["Menyeragamkan alur surat-menyurat di dalam kantor", "Membedakan organisasi tersebut dengan organisasi lain", "Menetapkan batas pembagian wewenang pejabat struktural", "Mengatur mekanisme keluar masuknya barang operasional", "Merangkum hasil curah pendapat pelatihan para manajer"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Budaya Organisasi Menurut Stephen P. Robbins</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Stephen P. Robbins</strong> dalam buku <em>Organizational Behavior</em> menyatakan bahwa <strong>budaya organisasi</strong> adalah <strong>suatu sistem makna bersama</strong> yang dianut anggota yang <strong>membedakan organisasi tersebut dengan organisasi yang lain</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B melengkapi secara tepat definisi budaya organisasi menurut Stephen P. Robbins dalam buku <em>Organizational Behavior</em>. Kesamaan cara memaknai nilai dan kerja di antara para anggota membentuk jati diri khas yang tidak dimiliki oleh institusi lain. Robbins menegaskan bahwa budaya organisasi merupakan sistem makna bersama yang dianut anggota sekaligus menjadi pembeda organisasi kita dengan organisasi lainnya. Dengan demikian, fungsi pembeda antarorganisasi merupakan inti definisi budaya organisasi menurut Robbins.</p><p>Jawaban tepat: <strong>B. Membedakan organisasi tersebut dengan organisasi lain</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan fungsi tata naskah administrasi persuratan, bukan esensi sistem makna bersama menurut Robbins.</li><li><strong>C</strong>: Merupakan prinsip manajemen pembagian wewenang kinerja, bukan definisi budaya dalam buku Organizational Behavior.</li><li><strong>D</strong>: Merupakan tata kelola administrasi logistik barang, bukan sistem arti kesepakatan bersama anggota organisasi.</li><li><strong>E</strong>: Merupakan aktivitas pelatihan manajer menengah pada tahap pengembangan nilai menurut Susan M. Heathfield.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 14) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Stephen P. Robbins • suatu sistem makna bersama</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Menurut Stephen P. Robbins, sistem pemaknaan kesepakatan bersama yang membedakan suatu organisasi pada hakikatnya merupakan...", "options": ["Rincian uraian tugas jabatan bagi setiap aparatur teknis", "Kumpulan prosedur tertulis administrasi keuangan kantor", "Susunan struktur kewenangan formal antarpejabat eselon", "Karakter kunci yang disepakati para pemangku kepentingan", "Catatan sejarah pendirian lembaga oleh jajaran pendiri"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sistem Pemaknaan sebagai Seperangkat Karakter Kunci</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Menurut <strong>Stephen P. Robbins</strong>, <strong>sistem pemaknaan</strong> kesepakatan bersama merupakan <strong>seperangkat karakter kunci yang disepakati oleh seluruh pemangku kepentingan</strong> dalam organisasi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merangkum secara tepat wujud dari sistem pemaknaan bersama dalam pandangan Stephen P. Robbins. Arti dan makna kolektif yang dibangun anggota mengejawantah menjadi sifat-sifat utama institusi yang dijunjung bersama oleh seluruh pihak terkait. Secara eksplisit dijelaskan bahwa sistem pemaknaan tersebut merupakan seperangkat karakter kunci yang disepakati oleh seluruh pemangku kepentingan dalam organisasi. Oleh sebab itu, kesepakatan atas karakter kunci pemangku kepentingan menjadi hakikat sistem pemaknaan budaya.</p><p>Jawaban tepat: <strong>D. Karakter kunci yang disepakati para pemangku kepentingan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan dokumen kepegawaian mengenai rincian tugas individu, bukan seperangkat karakter kunci organisasi.</li><li><strong>B</strong>: Merupakan petunjuk teknis tata kelola administrasi keuangan, bukan sistem pemaknaan bersama menurut Robbins.</li><li><strong>C</strong>: Merupakan bagan hierarki pembagian wewenang manajemen, bukan karakter kunci kesepakatan pemangku kepentingan.</li><li><strong>E</strong>: Merupakan dokumentasi kronologis masa lalu lembaga, bukan sistem pemaknaan aktif yang dianut para anggota.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 14) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Sistem pemaknaan inilah • seperangkat karakter kunci</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C4 - Analisis", "question": "Fokus utama definisi budaya organisasi menurut Moeljono Djokosantoso dan Stephen P. Robbins secara berurutan adalah...", "options": ["Filosofi kerja nilai dominan dan sistem makna pembeda organisasi", "Sistem makna pembeda organisasi dan filosofi kerja nilai dominan", "Pembagian wewenang jabatan dan kerapian administrasi surat kantor", "Konsensus eksekutif puncak dan pelatihan curah pendapat manajer", "Penyusunan kode etik tertulis dan pencapaian target kinerja unit"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Perbandingan Definisi Moeljono Djokosantoso dan Stephen P. Robbins</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Moeljono Djokosantoso</strong> menekankan <strong>nilai-nilai dominan</strong> sebagai <strong>acuan atau filosofi kerja pegawai</strong>, sedangkan <strong>Stephen P. Robbins</strong> menekankan <strong>sistem makna bersama</strong> sebagai <strong>pembeda organisasi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A menyandingkan secara tepat perbedaan fokus definisi budaya organisasi dari kedua ahli tersebut secara berurutan. Moeljono melihat budaya dari penyebaran nilai utama yang menjadi pedoman falsafah kerja internal, sementara Robbins menyoroti kesatuan makna kolektif yang membedakan jati diri lembaga dari organisasi lain. Moeljono merumuskannya sebagai nilai dominan acuan filosofi kerja pegawai, sedangkan Robbins merumuskannya sebagai sistem makna bersama pembeda antarorganisasi. Dengan demikian, urutan perbandingan pada Opsi A sepenuhnya akurat.</p><p>Jawaban tepat: <strong>A. Filosofi kerja nilai dominan dan sistem makna pembeda organisasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Urutannya terbalik karena sistem makna pembeda milik Robbins diletakkan pada posisi Moeljono Djokosantoso.</li><li><strong>C</strong>: Merupakan prinsip manajemen umum terkait kejelasan wewenang dan administrasi, bukan definisi kedua tokoh tersebut.</li><li><strong>D</strong>: Merupakan tahapan identifikasi nilai organisasi menurut artikel Susan M. Heathfield (2021).</li><li><strong>E</strong>: Merupakan sebagian komponen tujuan dan aturan perilaku, bukan rumusan definisi khas Moeljono maupun Robbins.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 14) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>arti dan makna kesepakatan bersama • pembeda organisasi kita dengan organisasi lainnya</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Menurut Stephen P. Robbins, perhatian organisasi dalam menghargai pengambilan risiko serta membangkitkan ide pegawai termasuk karakteristik budaya...", "options": ["Attention to detail (perhatian detail)", "Outcome orientation (orientasi hasil)", "Innovation and risk taking (inovasi)", "People orientation (orientasi manusia)", "Team orientation (orientasi tim kerja)"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Karakteristik Budaya Organisasi: Innovation and Risk Taking</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Tingkat inovasi dan keberanian mengambil risiko (<em>innovation and risk taking</em>)</strong> mengukur dorongan bersikap inovatif, termasuk cara organisasi <strong>menilai/menghargai pengambilan risiko</strong> dan <strong>membangkitkan ide pegawai</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan karakteristik budaya organisasi menurut Stephen P. Robbins yang berfokus pada keberanian berinovasi. Pegawai akan terdorong melahirkan terobosan baru apabila institusi memberikan apresiasi positif terhadap keberanian mengambil langkah terukur. Dalam penjabaran Robbins, bagaimana organisasi menilai atau menghargai tindakan pengambilan risiko oleh pegawai serta membangkitkan ide pegawai merupakan perhatian utama (<em>concern</em>) pada dimensi <em>innovation and risk taking</em>. Oleh karena itu, Opsi C adalah jawaban yang tepat.</p><p>Jawaban tepat: <strong>C. Innovation and risk taking (inovasi)</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Mengukur tingkat ketelitian, kecermatan, analisis, serta perhatian pegawai terhadap hal-hal yang rinci.</li><li><strong>B</strong>: Mengukur pemusatan perhatian manajemen pada hasil akhir dibandingkan teknik dan proses pencapaiannya.</li><li><strong>D</strong>: Mengukur sejauh mana keputusan manajemen memperhitungkan efek hasil pada orang-orang yang terlibat.</li><li><strong>E</strong>: Mengukur pengelolaan pekerjaan yang berorientasi pada tim kerja guna mendukung kerja sama.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 14) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>innovation and risk taking • membangkitkan ide pegawai</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Seorang verifikator anggaran memeriksa keabsahan bukti transaksi secara teliti dan menguraikan rincian angka sebelum pencairan. Perilaku ini mencerminkan karakteristik...", "options": ["Outcome orientation", "People orientation", "Team orientation", "Innovation and risk taking", "Attention to detail"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Karakteristik Budaya Organisasi: Attention to Detail</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Tingkat perhatian terhadap aspek detail (<em>attention to detail</em>)</strong> mengukur sejauh mana organisasi mengharapkan pegawai memperlihatkan <strong>kecermatan, analisis, dan perhatian kepada hal-hal yang rinci</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan karakteristik budaya organisasi menurut Stephen P. Robbins yang tercermin pada ketelitian kerja verifikator tersebut. Pemeriksaan bukti transaksi hingga ke rincian angka sebelum pencairan menuntut akurasi serta daya urai yang tajam. Sesuai kerangka Robbins, dimensi <em>attention to detail</em> menakar sejauh mana organisasi memberikan perhatian pada ketelitian dan mengharapkan pegawai memperlihatkan kecermatan, analisis, serta perhatian pada hal rinci. Dengan demikian, perilaku verifikator tersebut merepresentasikan <em>attention to detail</em>.</p><p>Jawaban tepat: <strong>E. Attention to detail</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Berfokus pada pencapaian hasil akhir dibandingkan proses atau teknik pengerjaan yang digunakan pelaksana.</li><li><strong>B</strong>: Berfokus pada pertimbangan efek hasil keputusan manajemen terhadap orang-orang di dalam organisasi.</li><li><strong>C</strong>: Berfokus pada pengorganisasian tugas di seputar kelompok kerja bersama dibandingkan kerja individual.</li><li><strong>D</strong>: Berfokus pada dorongan kepada pegawai agar bersikap inovatif dan berani mengambil risiko kerja.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 14) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>attention to detail • kecermatan, analisis, dan perhatian</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Pada karakteristik budaya outcome orientation menurut Stephen P. Robbins, pihak manajemen organisasi lebih memusatkan perhatian pada...", "options": ["Ketelitian analisis dokumen dibandingkan kecepatan layanan", "Kenyamanan psikologis staf dibandingkan efisiensi anggaran", "Kolaborasi kelompok kerja dibandingkan penugasan individu", "Kemapanan rutinitas kantor dibandingkan ekspansi lembaga", "Capaian hasil akhir dibandingkan teknik dan proses kerja"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Karakteristik Budaya Organisasi: Outcome Orientation</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Karakteristik <strong>berorientasi kepada hasil (<em>outcome orientation</em>)</strong> adalah sejauh mana manajemen <strong>memusatkan perhatian pada hasil</strong> dibandingkan pada <strong>teknik dan proses</strong> untuk mencapai hasil tersebut.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E menjelaskan secara akurat inti karakteristik <em>outcome orientation</em> yang dikemukakan oleh Stephen P. Robbins. Pada organisasi berorientasi hasil, pimpinan menilai keberhasilan terutama dari tercapainya target keluaran akhir dibandingkan variasi cara kerja pelaksana di lapangan. Robbins mendefinisikan <em>outcome orientation</em> sebagai sejauh mana manajemen memusatkan perhatian pada hasil dibandingkan teknik dan proses yang digunakan pelaku tindakan. Oleh sebab itu, pengutamaan hasil di atas teknik dan proses adalah rumusan yang tepat.</p><p>Jawaban tepat: <strong>E. Capaian hasil akhir dibandingkan teknik dan proses kerja</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penekanan pada ketelitian analisis rincian dokumen merupakan ciri karakteristik attention to detail.</li><li><strong>B</strong>: Pertimbangan atas dampak kebijakan terhadap kondisi staf berkaitan dengan karakteristik people orientation.</li><li><strong>C</strong>: Pengutamaan kolaborasi kelompok kerja dibandingkan individu merupakan ciri karakteristik team orientation.</li><li><strong>D</strong>: Pengutamaan kemapanan rutinitas kegiatan organisasi merupakan salah satu kutub karakteristik stability.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 14) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>outcome orientation • memusatkan perhatian pada hasil</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Sebelum menetapkan aturan jam layanan baru, kepala kantor memperhitungkan dampak kebijakan tersebut bagi kondisi para petugas. Sikap ini mencerminkan...", "options": ["Outcome orientation", "People orientation", "Attention to detail", "Team orientation", "Innovation and risk taking"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Karakteristik Budaya Organisasi: People Orientation</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Tingkat orientasi kepada manusia (<em>people orientation</em>)</strong> mengukur sejauh mana setiap <strong>keputusan manajemen</strong> memperhitungkan <strong>efek hasil-hasil pada orang-orang yang terlibat</strong> di dalam organisasi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan karakteristik budaya organisasi menurut Stephen P. Robbins yang selaras dengan pertimbangan kepala kantor tersebut. Setiap ketetapan pimpinan memiliki konsekuensi langsung terhadap beban dan kesejahteraan sumber daya manusia yang menjalankannya. Menurut Robbins, <em>people orientation</em> menunjukkan sejauh mana keputusan manajemen organisasi telah memperhitungkan efek hasil-hasil pada orang-orang yang terlibat dalam organisasi. Dengan demikian, kepekaan menimbang dampak aturan jam layanan bagi petugas mencerminkan <em>people orientation</em>.</p><p>Jawaban tepat: <strong>B. People orientation</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Menitikberatkan perhatian manajemen pada perolehan hasil akhir dibandingkan teknik dan proses pencapaiannya.</li><li><strong>C</strong>: Menitikberatkan harapan organisasi pada ketelitian, kecermatan, dan analisis pegawai terhadap hal rinci.</li><li><strong>D</strong>: Menitikberatkan pengelolaan pekerjaan pada sinergi tim kerja dibandingkan penugasan perorangan.</li><li><strong>E</strong>: Menitikberatkan dorongan organisasi agar pegawai berani berinovasi serta mengambil risiko terobosan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 14) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>people orientation • efek hasil-hasil pada orang-orang</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Kepala bidang menata penyelesaian kajian fiskal melalui gugus tugas lintas seksi guna memperkuat kolaborasi. Pengelolaan pekerjaan tersebut menerapkan karakteristik...", "options": ["Tingkat perhatian aspek detail", "Tingkat orientasi pada hasil", "Tingkat orientasi pada manusia", "Tingkat orientasi tim kerja", "Tingkat stabilitas kegiatan"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Karakteristik Budaya Organisasi: Team Orientation</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Tingkat orientasi tim (<em>team orientation</em>)</strong> menunjukkan sejauh mana setiap <strong>pekerjaan dikelola dan berorientasi pada setiap tim kerja</strong> dibandingkan individu-individu untuk <strong>mendukung kerja sama</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan karakteristik budaya organisasi menurut Stephen P. Robbins yang tepat menggambarkan penataan gugus tugas tersebut. Pembentukan kelompok lintas seksi sengaja dirancang agar beban kajian diselesaikan secara sinergis ketimbang dikerjakan sendiri-sendiri oleh tiap staf. Dalam klasifikasi Robbins, tingkat orientasi tim (<em>team orientation</em>) mengukur sejauh mana pekerjaan dikelola berorientasi pada tim kerja demi mendukung kerja sama. Oleh karena itu, pengelolaan tugas berbasis gugus tugas mencerminkan tingkat orientasi tim.</p><p>Jawaban tepat: <strong>D. Tingkat orientasi tim kerja</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Padanan dari attention to detail yang menuntut kecermatan, ketelitian, serta analisis pada hal rinci.</li><li><strong>B</strong>: Padanan dari outcome orientation yang memfokuskan manajemen pada capaian akhir di atas teknik kerja.</li><li><strong>C</strong>: Padanan dari people orientation yang memperhitungkan efek keputusan manajemen bagi anggota organisasi.</li><li><strong>E</strong>: Padanan dari stability yang menakar pengutamaan kemapanan kondisi kegiatan versus pertumbuhan berisiko.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 14) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>team orientation • mendukung kerja sama</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Karakteristik tingkat stabilitas (stability) menurut Stephen P. Robbins mengukur sejauh mana kegiatan organisasi mementingkan kemapanan kondisi atau...", "options": ["Menampilkan kecermatan analisis pada hal rinci", "Memusatkan perhatian pimpinan pada hasil akhir", "Memilih untuk bertumbuh dengan mengambil risiko", "Memperhitungkan efek keputusan pada para anggota", "Mengelola aktivitas kerja berbasis kelompok tim"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Karakteristik Budaya Organisasi: Stability</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Tingkat stabilitas (<em>stability</em>)</strong> menurut <strong>Stephen P. Robbins</strong> mengukur sejauh mana kegiatan organisasi <strong>mementingkan stabilitas</strong> atau <strong>memilih untuk bertumbuh dengan mengambil risiko</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C melengkapi secara tepat definisi karakteristik <em>stability</em> dalam enam dimensi budaya organisasi Stephen P. Robbins. Dimensi ini memetakan kecenderungan lembaga antara mempertahankan keteraturan operasional yang telah ajek atau melakukan ekspansi yang penuh tantangan perubahan. Sesuai rumusan Robbins, tingkat stabilitas mengukur sejauh mana kegiatan organisasi mementingkan stabilitas atau memilih untuk bertumbuh dengan mengambil risiko. Dengan demikian, pilihan bertumbuh dengan mengambil risiko merupakan pasangan spektrum dimensi <em>stability</em>.</p><p>Jawaban tepat: <strong>C. Memilih untuk bertumbuh dengan mengambil risiko</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan esensi pengukuran dari karakteristik tingkat perhatian terhadap aspek detail (attention to detail).</li><li><strong>B</strong>: Merupakan esensi pengukuran dari karakteristik berorientasi kepada hasil (outcome orientation).</li><li><strong>D</strong>: Merupakan esensi pengukuran dari karakteristik tingkat orientasi kepada manusia (people orientation).</li><li><strong>E</strong>: Merupakan esensi pengukuran dari karakteristik tingkat orientasi tim (team orientation).</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 14) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Tingkat stabilitas (stability) • bertumbuh dengan mengambil risiko</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 01", "difficulty": "C4 - Analisis", "question": "Berikut ini yang BUKAN termasuk enam karakteristik budaya organisasi menurut Stephen P. Robbins adalah...", "options": ["Enduring belief", "Attention to detail", "Outcome orientation", "People orientation", "Team orientation"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Enam Karakteristik Budaya Organisasi Menurut Stephen P. Robbins</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Enam karakteristik budaya organisasi menurut <strong>Stephen P. Robbins</strong> meliputi <strong><em>innovation and risk taking</strong></em>, <strong><em>attention to detail</strong></em>, <strong><em>outcome orientation</strong></em>, <strong><em>people orientation</strong></em>, <strong><em>team orientation</strong></em>, dan <strong><em>stability</strong></em>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan pilihan yang tepat karena bukan bagian dari enam karakteristik budaya organisasi menurut Stephen P. Robbins. Istilah <em>enduring belief</em> (keyakinan abadi) merujuk pada salah satu fungsi nilai-nilai organisasi sebagai dasar bertindak, bukan dimensi karakteristik budaya Robbins. Berdasarkan buku <em>Organizational Behavior</em>, keenam karakteristik budaya Robbins terdiri atas <em>innovation and risk taking, attention to detail, outcome orientation, people orientation, team orientation,</em> serta <em>stability</em>. Oleh sebab itu, <em>enduring belief</em> dikecualikan dari daftar karakteristik tersebut.</p><p>Jawaban tepat: <strong>A. Enduring belief</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Termasuk karakteristik budaya Robbins yang mengukur ketelitian, kecermatan, dan analisis pada hal rinci.</li><li><strong>C</strong>: Termasuk karakteristik budaya Robbins yang mengukur fokus manajemen pada hasil dibandingkan proses kerja.</li><li><strong>D</strong>: Termasuk karakteristik budaya Robbins yang mengukur pertimbangan efek keputusan pada orang-orang di organisasi.</li><li><strong>E</strong>: Termasuk karakteristik budaya Robbins yang mengukur pengelolaan pekerjaan berbasis tim guna mendukung kerja sama.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 14) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Karakteristik budaya organisasi • dikemukakan oleh Robbins</strong></div></div>"}];
let current=0;const answers=new Array(questions.length).fill(null);const letters=["A","B","C","D","E"];let seconds=65*60;let submitted=false;


// Anotasi hanya dalam pembahasan. Satu set coretan untuk setiap nomor soal.
const ANNO_KEY='stangolden_kemenkeu_p01_review_annot_v1_user_<?= (int)$USER->id ?>';
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
 try{localStorage.setItem('stg_nk_p01_map_visible',show?'1':'0')}catch(e){}
}
try{if(localStorage.getItem('stg_nk_p01_map_visible')==='0')toggleQuestionMap(false)}catch(e){}
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

  if(modalTitle) modalTitle.textContent="🏆 Lihat Ranking Paket 01";
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
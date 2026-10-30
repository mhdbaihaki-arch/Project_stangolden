<?php
/**
 * STANGOLDEN.exam • Nilai-Nilai Kemenkeu Paket 02 (Halaman 15–24)
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
// $DB->get_record("stgnk_p02_rank") => tabel fisik "mdlhv_stgnk_p02_rank".
// Jangan menambahkan prefix secara manual di konstanta berikut.
// Penyimpanan bersama melalui database Moodle. Nama tabel pendek, kompatibel dengan batas Moodle.
const STG_LB_T = 'stgnk_p02_rank';
const STG_REVIEW_T = 'stgnk_p02_rev';
const STG_ANNO_T = 'stgnk_p02_anno';
const STG_KEY = [1, 3, 4, 0, 2, 1, 4, 3, 2, 0, 3, 0, 1, 4, 2, 3, 0, 1, 4, 2, 4, 2, 0, 3, 1, 4, 2, 0, 3, 1, 2, 0, 4, 1, 3, 2, 0, 4, 1, 3, 0, 1, 2, 3, 4, 0, 1, 2, 3, 4, 1, 3, 0, 2, 4, 1, 3, 0, 2, 4, 2, 0, 4, 1, 3, 2, 0, 4, 1, 3, 3, 1, 4, 0, 2, 3, 1, 4, 0, 2, 1, 3, 0, 4, 2, 1, 3, 0, 4, 2, 4, 2, 0, 3, 1, 4, 2, 0, 3, 1];
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
            error_log('STANGOLDEN NK P02 HLM15-24 SETUP: ' . $e->getMessage());
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
    }catch(Throwable $e){error_log('STANGOLDEN NK P02 HLM15-24: '.$e->getMessage());stgnk_json(['success'=>false,'message'=>($e instanceof RuntimeException ? $e->getMessage() : 'Penyimpanan belum tersedia. Hubungi administrator.')],500);}
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
<title>Latihan Nilai-Nilai Kemenkeu Paket 02 (Hlm. 15–24) • STANGOLDEN</title>
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
      <span class="series-tag">Paket 02 • Hlm. 15–24</span>
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
<h1>Latihan Nilai-Nilai Kemenkeu <span class="series-tag">Paket 02 • Hlm. 15–24</span></h1>
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
        <h2 class="leaderboard-title" id="rankingModalTitle">🏆 Lihat Ranking Paket 02</h2>
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
const STG_LOCAL_KEY="stg_nk_p02_review_user_<?= (int)$USER->id ?>";
const HAS_FULL_REVIEW=true;
const IS_SITE_ADMIN=<?= is_siteadmin($USER) ? "true" : "false" ?>;
const LEADERBOARD_ENDPOINT=window.location.href.split("?")[0].split("#")[0];
const questions=[{"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Dengan hadirnya budaya organisasi, para pegawai dan pelaku organisasi akan terbantu untuk...", "options": ["Menghitung rincian alokasi belanja operasional", "Mengetahui atau membaca situasi yang sedang terjadi", "Menyusun regulasi kenaikan pangkat secara mandiri", "Menetapkan standar kelulusan seleksi kepegawaian", "Mengawasi ketaatan pelaporan perpajakan masyarakat"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Peran Budaya Organisasi dalam Membaca Situasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dengan hadirnya <strong>budaya organisasi</strong>, para pegawai dan pelaku organisasi akan terbantu untuk <strong>mengetahui atau membaca situasi yang sedang terjadi</strong> di sebuah organisasi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat sesuai peran awal budaya organisasi di tempat kerja. Keberadaan budaya memberikan pedoman kontekstual bagi aparatur dalam mencermati dinamika kerja harian. Hadirnya budaya organisasi secara nyata membantu para pegawai dan pelaku organisasi mengetahui atau membaca situasi yang sedang terjadi. Oleh sebab itu, kemampuan membaca situasi kerja menjadi manfaat mendasar budaya organisasi.</p><p>Jawaban tepat: <strong>B. Mengetahui atau membaca situasi yang sedang terjadi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan tugas teknis pengelolaan anggaran dinas pada unit keuangan.</li><li><strong>C</strong>: Merupakan wewenang formasi kepegawaian pada regulasi aparatur negara.</li><li><strong>D</strong>: Merupakan kewenangan panitia pelaksana ujian dan rekrutmen pegawai.</li><li><strong>E</strong>: Merupakan fungsi pengawasan penerimaan negara pada unit perpajakan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 15) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Budaya organisasi memiliki peran yang besar • membaca situasi yang sedang terjadi</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Melalui kehadiran budaya organisasi yang hidup, organisasi dapat mengenali karakteristik pegawai mengenai...", "options": ["Jenjang pendidikan formal yang pernah ditempuh", "Besaran tunjangan kinerja bulanan yang diperoleh", "Urutan daftar riwayat kepangkatan kepegawaian", "Cara mereka menyelesaikan permasalahan yang ada", "Lama masa pengabdian dinas di kementerian"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengenalan Karakteristik Pegawai</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Melalui budaya organisasi, kita dapat mengetahui <strong>karakteristik pegawai</strong>, termasuk <strong>cara mereka menyelesaikan permasalahan yang ada</strong>, apa yang disukai, dan apa yang tidak disukai.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat mengenai karakteristik pegawai yang tecermin dari budaya organisasi. Budaya membentuk kebiasaan bertindak para anggota saat berhadapan dengan kendala pekerjaan. Secara konseptual ditegaskan bahwa dari budaya organisasi kita bisa mengetahui karakteristik pegawai, terutama cara mereka menyelesaikan permasalahan yang ada. Dengan demikian, pendekatan pemecahan masalah mencerminkan karakteristik pegawai tersebut.</p><p>Jawaban tepat: <strong>D. Cara mereka menyelesaikan permasalahan yang ada</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan data kualifikasi akademis pada berkas administrasi pegawai.</li><li><strong>B</strong>: Merupakan hak remunerasi aparatur berbasis capaian kinerja bulanan.</li><li><strong>C</strong>: Merupakan urutan kepangkatan formal dalam database kepegawaian instansi.</li><li><strong>E</strong>: Merupakan masa kerja kedinasan aparatur sipil negara sejak pelantikan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 15) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pegawai yang ada di sana • menyelesaikan permasalahan yang ada</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C4 - Analisis", "question": "Pemahaman karakteristik pegawai melalui budaya organisasi mencakup hal-hal berikut, KECUALI...", "options": ["Cara pegawai menyelesaikan masalah", "Hal-hal yang disukai oleh para pegawai", "Hal-hal yang tidak disukai para pegawai", "Membaca situasi yang sedang terjadi", "Catatan kepemilikan aset kekayaan pribadi"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Cakupan Karakteristik Pegawai dalam Budaya</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Karakteristik pegawai yang dikenali melalui budaya organisasi meliputi <strong>cara menyelesaikan masalah</strong>, <strong>apa yang disukai</strong>, serta <strong>apa yang tidak disukai</strong> dalam lingkungan kerja.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat karena catatan kepemilikan harta pribadi bukan merupakan karakteristik budaya kerja organisasi. Budaya organisasi berfokus pada pemahaman situasi serta kebiasaan perilaku kerja aparatur dalam bertugas. Pemahaman budaya meliputi kemampuan mencermati dinamika kerja, cara menyelesaikan masalah, serta nilai-nilai yang dianut pegawai. Oleh karena itu, catatan aset pribadi berada di luar dimensi budaya organisasi.</p><p>Jawaban tepat: <strong>E. Catatan kepemilikan aset kekayaan pribadi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan karakteristik budaya organisasi dalam memahami dinamika lingkungan kerja.</li><li><strong>B</strong>: Disebutkan secara eksplisit sebagai aspek pengenalan preferensi kerja pegawai.</li><li><strong>C</strong>: Disebutkan secara eksplisit sebagai aspek preferensi perilaku kerja pegawai.</li><li><strong>D</strong>: Disebutkan secara eksplisit sebagai manfaat kehadiran budaya organisasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 15) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>apa yang mereka • apa mereka tidak disukai</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Pemahaman yang baik mengenai situasi dan karakteristik pegawai sangat membantu pimpinan dalam...", "options": ["Membuat strategi di organisasi", "Menata alokasi anggaran program prioritas", "Menyusun standar prosedur operasional dinas", "Menyelaraskan sasaran kinerja unit kerja", "Mengembangkan sistem pelaporan terpadu"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Manfaat Budaya bagi Perumusan Strategi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pemahaman yang baik mengenai situasi dan karakteristik pegawai sangat membantu pimpinan dalam <strong>membuat strategi di organisasi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat karena pemahaman terhadap karakteristik pegawai dan cara mereka bersikap menjadi landasan utama bagi pimpinan dalam menentukan arah kebijakan. Pengetahuan atas dinamika kerja membantu perumusan langkah strategis organisasi secara efektif. Dengan demikian, pembuatan strategi menjadi manfaat langsung dari pemahaman situasi pegawai.</p><p>Jawaban tepat: <strong>A. Membuat strategi di organisasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kurang tepat karena penataan alokasi anggaran berfokus pada aspek perbendaharaan dan perencanaan fiskal.</li><li><strong>C</strong>: Kurang tepat karena penyusunan standar operasional menitikberatkan pada standardisasi alur birokrasi teknis.</li><li><strong>D</strong>: Kurang tepat karena penyelarasan sasaran kinerja merupakan instrumen manajemen kinerja individu dan unit.</li><li><strong>E</strong>: Kurang tepat karena pengembangan sistem pelaporan terpadu berkaitan dengan tata kelola akuntabilitas data.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 15) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>seperti ini • strategi di organisasi</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Pimpinan unit melibatkan berbagai elemen staf dalam perumusan strategi kantor agar mereka dapat...", "options": ["Menyusun rencana aksi operasional terpadu", "Meningkatkan efisiensi alur kerja birokrasi", "Melakukan kolaborasi dan berinovasi dengan baik", "Memperkuat komitmen akuntabilitas kedinasan", "Mengoptimalkan pemanfaatan teknologi digital"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pelibatan Banyak Pihak dalam Strategi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam membuat strategi, pelibatan banyak pihak bertujuan agar para pegawai dapat <strong>melakukan kolaborasi dan berinovasi dengan baik</strong> sehingga tujuan organisasi terealisasi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat karena pelibatan berbagai pihak dalam penyusunan strategi ditujukan secara spesifik untuk membangun wadah kolaborasi dan inovasi bersama. Ketika pegawai dilibatkan, mereka merasa memiliki andil dalam keputusan organisasi sehingga pencapaian tujuan bersama menjadi lebih terarah. Oleh sebab itu, ruang kolaborasi dan inovasi merupakan tujuan utama pelibatan staf.</p><p>Jawaban tepat: <strong>C. Melakukan kolaborasi dan berinovasi dengan baik</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena penyusunan rencana aksi operasional merupakan tahap teknis penjabaran rencana kerja.</li><li><strong>B</strong>: Kurang tepat karena efisiensi alur kerja birokrasi berfokus pada penyederhanaan tata kelola administrasi.</li><li><strong>D</strong>: Kurang tepat karena komitmen akuntabilitas kedinasan berkaitan dengan pertanggungjawaban hasil kerja.</li><li><strong>E</strong>: Kurang tepat karena pemanfaatan teknologi digital berorientasi pada modernisasi sarana prasarana dinas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 15) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>perlu melibatkan banyak pihak • kolaborasi dan berinovasi dengan</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Tujuan organisasi lebih mudah dicapai ketika pegawai memiliki rasa kepemilikan tinggi karena...", "options": ["Memperoleh target kinerja yang terukur dan realistis", "Merasa dilibatkan dalam pengambilan keputusan organisasi", "Mendapatkan kesempatan pengembangan kompetensi berkala", "Memiliki lingkungan kerja fisik yang kondusif dan aman", "Menerima umpan balik konstruktif atas capaian penugasan"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tumbuhnya Sense of Belonging Pegawai</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Tujuan organisasi dapat terealisasi karena tingginya <strong>rasa memiliki (sense of belonging)</strong> dari pegawai yang <strong>merasa dilibatkan dalam pengambilan keputusan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat karena rasa memiliki yang mendalam tumbuh secara alamiah saat pegawai diberikan ruang berkontribusi dalam proses pengambilan keputusan strategis. Keterlibatan tersebut menumbuhkan tanggung jawab moral untuk bersama-sama merealisasikan sasaran yang telah disepakati. Dengan demikian, pelibatan dalam keputusan menjadi sumber utama timbulnya rasa memiliki.</p><p>Jawaban tepat: <strong>B. Merasa dilibatkan dalam pengambilan keputusan organisasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena target kinerja yang realistis berfokus pada kejelasan kontrak sasaran kerja aparatur.</li><li><strong>C</strong>: Kurang tepat karena pengembangan kompetensi berkala merupakan pemenuhan hak peningkatan kapasitas SDM.</li><li><strong>D</strong>: Kurang tepat karena lingkungan kerja fisik kondusif berkaitan dengan kenyamanan sarana operasional.</li><li><strong>E</strong>: Kurang tepat karena umpan balik konstruktif merupakan bagian dari mekanisme evaluasi pembinaan pegawai.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 15) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>rasa memiliki (sense of belonging) • bagaimana cara pegawai dalam</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Pengamatan mendalam terhadap cara pegawai dalam organisasi bersikap membantu pimpinan untuk...", "options": ["Menata struktur pembagian kerja unit secara proporsional", "Menetapkan alokasi anggaran berbasis kinerja tahunan", "Menyusun pedoman teknis standar operasional prosedur", "Mengoptimalkan pemanfaatan sarana prasarana kedinasan", "Menentukan strategi paling tepat diterapkan bagi organisasi"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penentuan Strategi Berdasarkan Sikap Pegawai</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dengan mengetahui bagaimana cara pegawai dalam organisasi bersikap, pimpinan dapat <strong>menentukan strategi apa yang paling tepat diterapkan</strong> untuk mencapai tujuan organisasi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat karena pemahaman atas pola perilaku pegawai memungkinkan perumusan metode dan pendekatan implementasi yang paling selaras dengan kondisi riil organisasi. Ketepatan strategi organisasi sangat bergantung pada kecocokan antara rencana pimpinan dan kesiapan budaya kerja para pegawainya. Oleh karena itu, penentuan strategi yang tepat merupakan fungsi utama analisis sikap pegawai.</p><p>Jawaban tepat: <strong>E. Menentukan strategi paling tepat diterapkan bagi organisasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena penataan struktur pembagian kerja berkaitan dengan desain organisasi dan peta jabatan.</li><li><strong>B</strong>: Kurang tepat karena alokasi anggaran berbasis kinerja merupakan instrumen perencanaan keuangan negara.</li><li><strong>C</strong>: Kurang tepat karena penyusunan pedoman teknis standar operasional bertujuan menjaga konsistensi prosedur.</li><li><strong>D</strong>: Kurang tepat karena pengoptimalan sarana prasarana berkaitan dengan efisiensi pengelolaan aset dinas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 15) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>bagaimana cara pegawai dalam organisasi bersikap • menentukan strategi apa yang paling tepat</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Kelancaran kerja sama (teamwork) antaranggotanya dalam sebuah tim sangat ditentukan oleh...", "options": ["Banyaknya jumlah pegawai dalam satu kelompok kerja", "Besarnya anggaran operasional kegiatan yang dikelola", "Tingkat kesamaan asal perguruan tinggi para anggota", "Peran budaya organisasi yang ada di lingkungan kerja", "Ketersediaan fasilitas kendaraan dinas operasional kantor"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Peran Budaya bagi Kerja Sama Tim</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Kerja sama (<em>teamwork</em>)</strong> dalam sebuah tim sangat ditentukan oleh <strong>peran budaya organisasi</strong> tempat mereka bekerja.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat mengenai faktor penentu solidnya kolaborasi kelompok kerja. Budaya organisasi yang sehat menyediakan rasa saling percaya dan komitmen bersama yang mempermudah sinergi. Secara normatif, kerja sama (teamwork) dalam sebuah tim sangat ditentukan oleh peran budaya organisasi. Dengan demikian, budaya organisasi menjadi fondasi penggerak kerja sama tim.</p><p>Jawaban tepat: <strong>D. Peran budaya organisasi yang ada di lingkungan kerja</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Jumlah anggota yang terlalu besar tanpa tata nilai justru memicu hambatan komunikasi.</li><li><strong>B</strong>: Besaran anggaran tidak otomatis menjamin keharmonisan hubungan kerja tim.</li><li><strong>C</strong>: Kesamaan latar belakang almamater bukan tolok ukur profesionalisme kerja sama dinas.</li><li><strong>E</strong>: Fasilitas transportasi operasional merupakan sarana pendukung mobilitas fisik semata.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 15) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Kerja sama (teamwork) dalam sebuah tim • peran budaya organisasi</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Salah satu poin penting peran budaya organisasi bagi keberlanjutan sumber daya manusia adalah...", "options": ["Menyempurnakan sistem pelaporan akuntabilitas kinerja", "Mempercepat proses digitalisasi tata kelola kearsipan", "Menarik talent berbobot atau bibit unggul ke organisasi", "Memperluas cakupan layanan bimbingan teknis kepegawaian", "Menyelaraskan target penerimaan negara dengan potensi riil"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Budaya Organisasi dalam Menarik Talenta</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Poin penting peran budaya organisasi adalah kehadirannya untuk <strong>menarik talent berbobot atau bibit unggul</strong> agar bergabung dan mengabdi di organisasi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat karena lingkungan budaya kerja yang sehat dan positif menjadi daya tarik kuat bagi individu berprestasi tinggi untuk berkarier di instansi tersebut. Ketersediaan talenta unggul secara berkelanjutan merupakan faktor kunci keberhasilan jangka panjang organisasi. Dengan demikian, penarikan bibit unggul merupakan peran penting budaya organisasi bagi SDM.</p><p>Jawaban tepat: <strong>C. Menarik talent berbobot atau bibit unggul ke organisasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena penyempurnaan sistem akuntabilitas berfokus pada pelaporan hasil kerja institusi.</li><li><strong>B</strong>: Kurang tepat karena percepatan digitalisasi kearsipan merupakan aspek otomatisasi tata kelola naskah.</li><li><strong>D</strong>: Kurang tepat karena bimbingan teknis kepegawaian adalah program pembinaan dan transfer pengetahuan.</li><li><strong>E</strong>: Kurang tepat karena penyelarasan target penerimaan negara berkaitan dengan proyeksi fiskal perbendaharaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 15) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>berbobot atau bibit unggul • kehadirannya untuk menarik talent</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Dengan memiliki individu berkualitas sebagai SDM, instansi pemerintah akan lebih mudah dalam...", "options": ["Mencapai kesuksesan berkelanjutan yang dicita-citakan", "Mewujudkan target penyerapan anggaran secara proporsional", "Mengembangkan kemitraan strategis dengan pemangku kebijakan", "Menyederhanakan prosedur birokrasi perizinan terpadu", "Mempertahankan predikat kepatuhan standar pelayanan publik"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dukungan Talenta bagi Kesuksesan Berkelanjutan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dengan memiliki individu berkualitas sebagai SDM, organisasi akan lebih mudah <strong>mencapai kesuksesan berkelanjutan seperti yang dicita-citakan</strong> berkat dukungan para talenta.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat karena kualitas aparatur yang mumpuni menjamin konsistensi kinerja dan daya adaptasi institusi dalam menghadapi tantangan masa depan. Dukungan talenta terbaik menjadi fondasi utama kesinambungan capaian visi organisasi dari waktu ke waktu. Oleh sebab itu, pencapaian kesuksesan berkelanjutan adalah hasil dari pengelolaan SDM berkualitas.</p><p>Jawaban tepat: <strong>A. Mencapai kesuksesan berkelanjutan yang dicita-citakan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kurang tepat karena target penyerapan anggaran merupakan indikator kinerja keuangan operasional tahunan.</li><li><strong>C</strong>: Kurang tepat karena kemitraan strategis berfokus pada aspek koordinasi dan komunikasi kelembagaan.</li><li><strong>D</strong>: Kurang tepat karena penyederhanaan prosedur perizinan berfokus pada deregulasi pelayanan birokrasi.</li><li><strong>E</strong>: Kurang tepat karena predikat standar pelayanan publik berkaitan dengan kepatuhan tata kelola loket.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 15) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>individu berkualitas sebagai SDM • kesuksesan berkelanjutan</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Peluncuran Nilai-nilai Dasar dan Employer Branding baru bagi Aparatur Sipil Negara ditetapkan Kementerian PANRB melalui...", "options": ["Instruksi Presiden Nomor 10 Tahun 2020", "Keputusan Menteri Keuangan Nomor 312 Tahun 2011", "Peraturan Pemerintah Nomor 94 Tahun 2021", "SE Nomor 20 Tahun 2021", "Peraturan Kepala BKN Nomor 5 Tahun 2022"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dasar Surat Edaran Core Values ASN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kementerian PANRB meluncurkan Core Values dan Employer Branding ASN berdasarkan <strong>SE Nomor 20 Tahun 2021</strong> tentang Implementasi Core Values dan Employer Branding Aparatur Sipil Negara.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat mengenai dasar regulasi peluncuran BerAKHLAK oleh Kementerian PANRB. Surat Edaran tersebut diterbitkan untuk menyeragamkan budaya kerja aparatur di seluruh Indonesia. Secara resmi, Kementerian PANRB meluncurkan Nilai-nilai Dasar dan Employer Branding baru bagi ASN berdasarkan SE Nomor 20 Tahun 2021. Dengan demikian, SE Nomor 20 Tahun 2021 menjadi dasar penetapan resminya.</p><p>Jawaban tepat: <strong>D. SE Nomor 20 Tahun 2021</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Bukan merupakan dasar surat edaran peluncuran nilai BerAKHLAK Kementerian PANRB.</li><li><strong>B</strong>: Merupakan Keputusan Menteri Keuangan tentang Nilai-Nilai Kementerian Keuangan tahun 2011.</li><li><strong>C</strong>: Merupakan Peraturan Pemerintah mengenai ketentuan disiplin Pegawai Negeri Sipil.</li><li><strong>E</strong>: Merupakan peraturan teknis kepegawaian dari Badan Kepegawaian Negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 16) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Kementerian PANRB • SE Nomor 20 Tahun 2021</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Core Values BerAKHLAK dan Employer Branding ASN diresmikan langsung oleh Presiden Joko Widodo pada...", "options": ["Tanggal 27 Juli 2021", "Tanggal 17 Agustus 2021", "Tanggal 28 Oktober 2021", "Tanggal 10 November 2021", "Tanggal 31 Desember 2021"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tanggal Peresmian Core Values BerAKHLAK</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Core Values BerAKHLAK dan Employer Branding ASN <strong>diresmikan langsung oleh Presiden Joko Widodo pada tanggal 27 Juli 2021</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat mengenai waktu peresmian nilai dasar BerAKHLAK oleh kepala negara. Momentum ini menjadi tonggak sejarah dimulainya standardisasi budaya kerja aparatur sipil negara di seluruh nusantara. Presiden Joko Widodo meresmikan Core Values BerAKHLAK dan employer branding #BanggaMelayaniBangsa secara resmi pada tanggal 27 Juli 2021. Oleh karena itu, tanggal 27 Juli 2021 merupakan tanggal peresmian resminya.</p><p>Jawaban tepat: <strong>A. Tanggal 27 Juli 2021</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan hari peringatan Proklamasi Kemerdekaan Republik Indonesia.</li><li><strong>C</strong>: Merupakan hari peringatan nasional Hari Sumpah Pemuda.</li><li><strong>D</strong>: Merupakan hari peringatan nasional Hari Pahlawan di Indonesia.</li><li><strong>E</strong>: Merupakan hari penutupan kalender tahun anggaran pemerintah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 16) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Presiden Joko Widodo • tanggal 27 Juli 2021</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Dalam akronim Core Values ASN BerAKHLAK, huruf 'K' pertama dan 'K' kedua mewakili nilai...", "options": ["Komunikatif dan Konsisten", "Kompeten dan Kolaboratif", "Kredibel dan Komitmen", "Kreatif dan Konstruktif", "Kolektif dan Kualitatif"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Komponen Huruf K dalam Nilai BerAKHLAK</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>BerAKHLAK</strong> merupakan akronim resmi di mana huruf <strong>'K' pertama mewakili Kompeten</strong> dan <strong>'K' kedua mewakili Kolaboratif</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat mengenai representasi dua huruf K dalam akronim BerAKHLAK. Kompeten menekankan keahlian kerja, sedangkan Kolaboratif menekankan sinergi kemitraan bersama. Dalam prinsip dasar, BerAKHLAK adalah akronim dari Berorientasi Pelayanan, Akuntabel, Kompeten, Harmonis, Loyal, Adaptif, dan Kolaboratif. Dengan demikian, Kompeten dan Kolaboratif adalah kepanjangan huruf K yang tepat.</p><p>Jawaban tepat: <strong>B. Kompeten dan Kolaboratif</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Bukan merupakan nilai dasar yang tercakup dalam akronim resmi BerAKHLAK.</li><li><strong>C</strong>: Kata Kredibel dan Komitmen bukan bagian dari akronim BerAKHLAK.</li><li><strong>D</strong>: Kata Kreatif dan Konstruktif tidak terdapat dalam rumusan akronim BerAKHLAK.</li><li><strong>E</strong>: Kata Kolektif dan Kualitatif bukan nama nilai dasar dalam BerAKHLAK.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 16) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>BerAKHLAK merupakan akronim dari • Pelayanan, Akuntabel, Kompeten</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Surat Edaran Kementerian Pendayagunaan Aparatur Negara dan Reformasi Birokrasi yang menjadi landasan peluncuran Core Values BerAKHLAK adalah...", "options": ["Surat Edaran Nomor 10 Tahun 2021", "Surat Edaran Nomor 12 Tahun 2021", "Surat Edaran Nomor 15 Tahun 2021", "Surat Edaran Nomor 18 Tahun 2021", "Surat Edaran Nomor 20 Tahun 2021"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dasar Undang-Undang Nilai Dasar ASN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kementerian PANRB meluncurkan Core Values BerAKHLAK dan Employer Branding ASN berdasarkan <strong>SE Nomor 20 Tahun 2021 tentang Implementasi Core Values dan Employer Branding Aparatur Sipil Negara</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat. Kementerian PANRB secara resmi menerbitkan Surat Edaran Nomor 20 Tahun 2021 sebagai landasan hukum peluncuran nilai dasar BerAKHLAK dan employer branding bagi seluruh aparatur di Indonesia. Ketentuan ini menjadi pedoman bersama bagi seluruh instansi pemerintah untuk menyeragamkan nilai-nilai dasar ASN.</p><p>Jawaban tepat: <strong>E. Surat Edaran Nomor 20 Tahun 2021</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Bukan regulasi peluncuran nilai dasar BerAKHLAK aparatur.</li><li><strong>B</strong>: Bukan surat edaran yang menetapkan core values ASN.</li><li><strong>C</strong>: Tidak mengatur tentang implementasi nilai dasar BerAKHLAK.</li><li><strong>D</strong>: Bukan dasar hukum penetapan employer branding aparatur.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 16) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>SE Nomor 20 Tahun 2021 • Core Values dan Employer Branding</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Employer branding #banggamelayanibangsa merupakan pemutakhiran dari semboyan branding ASN sebelumnya, yaitu...", "options": ["Bekerja nyata untuk nusa dan bangsa", "Mengabdi tanpa pamrih demi negeri", "Melayani dengan sepenuh hati", "Membangun birokrasi berkelas dunia", "Aparatur profesional berdaya saing"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pemutakhiran Semboyan Employer Branding</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>#banggamelayanibangsa</strong> adalah pemutakhiran dari *employer branding* ASN sebelumnya, yaitu <strong>melayani dengan sepenuh hati</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat mengenai fungsi employer branding aparatur. Slogan #BanggaMelayaniBangsa dirancang untuk menumbuhkan rasa percaya diri dan martabat ASN dalam melayani publik. Employer branding #BanggaMelayaniBangsa berfungsi sebagai pendorong kebanggaan aparatur dalam memberikan pelayanan publik secara profesional. Dengan demikian, pemupuk rasa bangga melayani menjadi fungsi esensialnya.</p><p>Jawaban tepat: <strong>C. Melayani dengan sepenuh hati</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Bukan merupakan rumusan resmi employer branding aparatur sipil negara terdahulu.</li><li><strong>B</strong>: Merupakan frasa motivasi umum pengabdian abdi negara, bukan slogan branding.</li><li><strong>D</strong>: Merupakan visi reformasi birokrasi pemerintah menuju world class bureaucracy.</li><li><strong>E</strong>: Merupakan target kompetensi pengembangan sumber daya manusia aparatur.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 16) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pemutakhiran dari employer branding • melayani dengan sepenuh hati</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Presiden Joko Widodo menegaskan bahwa ASN yang bertugas sebagai pegawai pusat maupun daerah harus...", "options": ["Menerima standar besaran tunjangan daerah yang seragam", "Memiliki masa penugasan dinas luar kota yang setara", "Mengenakan seragam dinas harian bermotif identik", "Mempunyai core values yang sama", "Menjalani rotasi berkala lintas instansi pemerintah"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pesan Presiden tentang Kesamaan Nilai ASN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Presiden Joko Widodo berpesan bahwa <strong>ASN yang bertugas sebagai pegawai pusat maupun pegawai daerah harus mempunyai <em>core values</em> yang sama</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat mengenai arahan presiden tentang kesatuan budaya kerja birokrasi. Standardisasi nilai menghapus sekat ego kedaerahan dan mewujudkan pelayanan publik yang terpadu di seluruh tanah air. Secara tegas dinyatakan bahwa presiden: ASN yang bertugas sebagai pegawai pusat maupun pegawai daerah harus mempunyai core values yang sama. Oleh karena itu, kesamaan core values menjadi substansi pesan presiden.</p><p>Jawaban tepat: <strong>D. Mempunyai core values yang sama</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Besaran tunjangan kinerja daerah disesuaikan dengan kemampuan fiskal masing-masing pemda.</li><li><strong>B</strong>: Penugasan perjalanan dinas diatur menurut kebutuhan riil satuan kerja.</li><li><strong>C</strong>: Aturan seragam dinas diatur oleh masing-masing kementerian dan pemerintah daerah.</li><li><strong>E</strong>: Rotasi lintas instansi merupakan mekanisme mobilitas talenta, bukan arahan nilai inti.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 16) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pegawai pusat maupun pegawai daerah • core values yang sama</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Bagi seluruh aparatur sipil negara, nilai BerAKHLAK diposisikan sebagai panduan perilaku serta menjadi...", "options": ["Fondasi budaya kerja ASN yang profesional", "Pedoman penyusunan standar kompetensi teknis", "Kerangka acuan pengukuran efektivitas satker", "Tolok ukur perumusan kebijakan reformasi dinas", "Rujukan penyusunan rencana strategis instansi"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kedudukan Nilai BerAKHLAK bagi ASN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Nilai BerAKHLAK merupakan panduan perilaku bagi ASN yang harus dijalankan dengan tanggung jawab serta menjadi <strong>fondasi budaya kerja ASN yang profesional</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat karena Core Values BerAKHLAK dirancang sebagai pijakan dasar dalam membangun mentalitas dan etos kerja seluruh aparatur secara nasional. Nilai-nilai ini menjadi landasan moral dan operasional agar budaya kerja aparatur semakin adaptif dan profesional. Dengan demikian, BerAKHLAK menjadi fondasi budaya kerja ASN yang kokoh.</p><p>Jawaban tepat: <strong>A. Fondasi budaya kerja ASN yang profesional</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kurang tepat karena penyusunan standar kompetensi teknis merupakan pedoman pengelompokan jabatan.</li><li><strong>C</strong>: Kurang tepat karena pengukuran efektivitas satker menggunakan instrumen evaluasi kelembagaan.</li><li><strong>D</strong>: Kurang tepat karena perumusan kebijakan reformasi birokrasi mencakup area perubahan tata laksana.</li><li><strong>E</strong>: Kurang tepat karena rencana strategis instansi berfokus pada perencanaan jangka menengah kementerian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 16) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>BerAKHLAK merupakan panduan perilaku • budaya kerja ASN yang profesional</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Saat menyusun sistem antrean ramah disabilitas, pegawai menerapkan panduan perilaku Berorientasi Pelayanan dalam hal...", "options": ["Menjaga kerahasiaan informasi internal kantor", "Memahami dan memenuhi kebutuhan masyarakat", "Menghargai keberagaman suku antarsesama rekan", "Menyesuaikan diri dengan perubahan teknologi", "Menggerakkan sumber daya untuk sasaran seksi"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penerapan Kebutuhan Layanan Masyarakat</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Salah satu panduan perilaku nilai <strong>Berorientasi Pelayanan</strong> adalah <strong>memahami dan memenuhi kebutuhan masyarakat</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat sesuai ilustrasi penataan loket inklusif bagi warga tersebut. Mengidentifikasi kebutuhan khusus pengguna jasa merupakan wujud pemahaman konkret atas kepentingan publik. Dalam panduan perilaku BerAKHLAK, butir pertama panduan perilaku Berorientasi Pelayanan berbunyi memahami dan memenuhi kebutuhan masyarakat. Oleh karena itu, penyediaan sarana tersebut mewujudkan pemenuhan kebutuhan masyarakat.</p><p>Jawaban tepat: <strong>B. Memahami dan memenuhi kebutuhan masyarakat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan wujud panduan perilaku pada nilai Loyal terkait kerahasiaan jabatan.</li><li><strong>C</strong>: Merupakan butir panduan perilaku pada nilai Harmonis di tempat kerja.</li><li><strong>D</strong>: Merupakan butir panduan perilaku pada nilai Adaptif dalam menghadapi teknologi.</li><li><strong>E</strong>: Merupakan butir panduan perilaku pada nilai Kolaboratif dalam pemanfaatan daya.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 16) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Berorientasi Pelayanan, dengan panduan perilaku • memahami dan memenuhi kebutuhan masyarakat</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Petugas meja bantuan melayani wajib pajak dengan senyum, bertindak cepat, dan memberikan jalan keluar tepat, mencerminkan perilaku...", "options": ["Melakukan perbaikan sistem berkala tahunan", "Menjaga martabat dari segala hal tercela", "Membantu orang lain belajar secara aktif", "Membangun kemitraan strategis pemangku jabatan", "Ramah, cekatan, solutif, dan dapat diandalkan"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Perilaku Petugas Pelayanan Publik</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Panduan perilaku kedua nilai Berorientasi Pelayanan menegaskan kewajiban aparatur untuk bersikap <strong>ramah, cekatan, solutif, dan dapat diandalkan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat sesuai rangkaian sikap yang ditunjukkan oleh petugas meja bantuan. Keramahan, kegesitan tindakan, pemberian solusi, dan keandalan merupakan satu kesatuan perilaku pelayanan prima. Panduan perilaku secara tegas menetapkan bahwa Berorientasi Pelayanan mencakup ramah, cekatan, solutif, dan dapat diandalkan. Dengan demikian, seluruh sikap tersebut mencerminkan butir perilaku pada opsi E.</p><p>Jawaban tepat: <strong>E. Ramah, cekatan, solutif, dan dapat diandalkan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan butir perbaikan berkelanjutan pada nilai Berorientasi Pelayanan.</li><li><strong>B</strong>: Merupakan perilaku utama kedua pada nilai Integritas Kementerian Keuangan.</li><li><strong>C</strong>: Merupakan salah satu butir panduan perilaku pada nilai Kompeten.</li><li><strong>D</strong>: Merupakan fokus kerja sama strategis pada nilai Kolaboratif dan Sinergi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 16) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>ramah, cekatan, solutif • ramah, cekatan, solutif, dan dapat diandalkan</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C4 - Analisis", "question": "Berikut ini merupakan panduan perilaku (kode etik) nilai Berorientasi Pelayanan dalam prinsip pelayanan prima, KECUALI...", "options": ["Memahami dan memenuhi kebutuhan masyarakat", "Ramah, cekatan, solutif, dan dapat diandalkan", "Memberi kesempatan berbagai pihak untuk berkontribusi", "Melakukan perbaikan tiada henti", "Melayani masyarakat dengan kesungguhan hati"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Cakupan Panduan Nilai Berorientasi Pelayanan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Panduan perilaku <strong>Berorientasi Pelayanan</strong> mencakup: (1) <strong>memahami dan memenuhi kebutuhan masyarakat</strong>, (2) <strong>ramah, cekatan, solutif, dan dapat diandalkan</strong>, serta (3) <strong>melakukan perbaikan tiada henti</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat karena butir tersebut adalah panduan perilaku untuk nilai Kolaboratif, bukan Berorientasi Pelayanan. Ketiga panduan resmi Berorientasi Pelayanan berfokus langsung pada pemenuhan kebutuhan dan keandalan layanan bagi warga. Memberi kesempatan berkontribusi merupakan butir perilaku nilai Kolaboratif. Oleh sebab itu, opsi C menjadi pengecualian yang benar.</p><p>Jawaban tepat: <strong>C. Memberi kesempatan berbagai pihak untuk berkontribusi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan butir pertama panduan perilaku resmi nilai Berorientasi Pelayanan.</li><li><strong>B</strong>: Merupakan butir kedua panduan perilaku resmi nilai Berorientasi Pelayanan.</li><li><strong>D</strong>: Merupakan butir ketiga panduan perilaku resmi nilai Berorientasi Pelayanan.</li><li><strong>E</strong>: Merupakan penjelas umum makna orientasi pelayanan aparatur sipil negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 16) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>melakukan perbaikan tiada henti • Berorientasi Pelayanan, dengan panduan perilaku</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Menggunakan kekayaan dan barang milik negara secara bertanggung jawab, efektif, dan efisien merupakan panduan perilaku pada nilai...", "options": ["Harmonis", "Adaptif", "Kompeten", "Kolaboratif", "Akuntabel"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Akuntabilitas Penggunaan Barang Milik Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Menggunakan kekayaan dan barang milik negara secara bertanggung jawab, efektif, dan efisien merupakan panduan perilaku dari nilai <strong>Akuntabel</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat mengenai nilai yang menaungi pengelolaan sarana dan prasarana milik dinas. Setiap fasilitas yang dibiayai uang rakyat wajib dipertanggungjawabkan penggunaannya demi kelancaran tugas negara. Nilai Akuntabel secara normatif salah satunya mewajibkan ASN menggunakan kekayaan dan barang milik negara secara bertanggung jawab, efektif, dan efisien. Dengan demikian, nilai Akuntabel menjadi wadah panduan perilaku tersebut.</p><p>Jawaban tepat: <strong>E. Akuntabel</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Berfokus pada suasana saling menghargai dan lingkungan kerja yang kondusif.</li><li><strong>B</strong>: Berfokus pada kemampuan inovasi dan kecepatan menyesuaikan diri terhadap perubahan.</li><li><strong>C</strong>: Berfokus pada peningkatan keahlian diri dan bantuan belajar kepada orang lain.</li><li><strong>D</strong>: Berfokus pada keterbukaan kerja sama untuk menghasilkan nilai tambah bersama.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 17) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menggunakan kekayaan dan barang milik negara • secara bertanggung jawab,</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Seorang pejabat menolak memberikan rekomendasi izin usaha yang menyalahi prosedur, sebagai penerapan perilaku nilai Akuntabel dalam...", "options": ["Menjaga rahasia data pribadi wajib pajak", "Membangun lingkungan kerja yang kondusif", "Tidak menyalahgunakan kewenangan jabatan", "Menghargai keberagaman suku para pemohon", "Meningkatkan kompetensi teknis bawahan"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Integritas Kewenangan Jabatan Akuntabel</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Salah satu butir panduan perilaku nilai Akuntabel adalah <strong>tidak menyalahgunakan kewenangan jabatan</strong> dalam setiap pelaksanaan wewenang kedinasan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat sesuai penolakan tegas pejabat tersebut terhadap perbuatan melanggar tata tertib dinas. Wewenang yang diberikan negara adalah amanah publik yang tidak boleh disalahgunakan untuk kepentingan nonprosedural. Di dalam rincian panduan perilaku nilai Akuntabel tertulis jelas larangan menyalahgunakan kewenangan jabatan. Oleh karena itu, tindakan pejabat tersebut menjalankan butir tidak menyalahgunakan wewenang jabatan.</p><p>Jawaban tepat: <strong>C. Tidak menyalahgunakan kewenangan jabatan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan butir perlindungan rahasia kedinasan pada nilai Loyal.</li><li><strong>B</strong>: Merupakan butir penciptaan iklim kerja sehat pada nilai Harmonis.</li><li><strong>D</strong>: Merupakan butir penghargaan kesetaraan sosial pada nilai Harmonis.</li><li><strong>E</strong>: Merupakan butir pembinaan kecakapan kerja pada nilai Kompeten.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 17) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>tidak menyalahgunakan kewenangan jabatan • melaksanakan tugas dengan jujur</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Pegawai senior mendampingi staf baru dalam mengoperasikan sistem aplikasi pelaporan anggaran, mencerminkan panduan nilai Kompeten yaitu...", "options": ["Membantu orang lain belajar", "Menjaga nama baik aparatur negara", "Menyesuaikan diri menghadapi perubahan", "Memanfaatkan sumber daya bersama", "Melayani dengan ramah dan cekatan"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Berbagi Pengetahuan Sesuai Nilai Kompeten</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Panduan perilaku nilai <strong>Kompeten</strong> mencakup kewajiban aparatur untuk saling membina kecakapan kerja melalui perilaku <strong>membantu orang lain belajar</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat sesuai tindakan pendampingan teknis kerja kepada staf baru tersebut. Pembelajaran berkelanjutan dalam organisasi terjadi melalui kesediaan pegawai yang lebih berpengalaman untuk mentransfer pengetahuannya. Dalam pedoman etika, butir panduan perilaku nilai Kompeten secara tegas menyebutkan membantu orang lain belajar. Dengan demikian, kegiatan mendampingi rekan baru tersebut mewujudkan perilaku membantu orang lain belajar.</p><p>Jawaban tepat: <strong>A. Membantu orang lain belajar</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan butir menjaga martabat korps pada nilai Loyal.</li><li><strong>C</strong>: Merupakan butir kepekaan transformasi teknologi pada nilai Adaptif.</li><li><strong>D</strong>: Merupakan butir mobilisasi fasilitas dinas pada nilai Kolaboratif.</li><li><strong>E</strong>: Merupakan butir keramahan petugas layanan pada nilai Berorientasi Pelayanan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 17) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>membantu orang lain belajar • meningkatkan kompetensi diri</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Dalam panduan perilaku Kompeten, upaya aparatur untuk terus meningkatkan kompetensi diri bertujuan agar...", "options": ["Memenuhi ekspektasi layanan pemangku kepentingan", "Mewujudkan tata kelola sarana dinas secara efisien", "Membangun suasana rukun dalam keberagaman pegawai", "Menjawab tantangan lingkungan yang selalu berubah", "Menghasilkan nilai tambah melalui kemitraan kerja"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Peningkatan Kapasitas Nilai Kompeten</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Panduan perilaku nilai <strong>Kompeten</strong> mewajibkan setiap aparatur untuk <strong>meningkatkan kompetensi diri untuk menjawab tantangan yang selalu berubah</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat karena dinamika regulasi dan perkembangan teknologi menuntut aparatur untuk selalu memperbarui pengetahuannya. Peningkatan keahlian secara berkelanjutan menjadi kunci agar pelayanan publik tetap relevan dan berkualitas tinggi. Oleh sebab itu, menjawab tantangan yang selalu berubah adalah tujuan peningkatan kapasitas aparatur.</p><p>Jawaban tepat: <strong>D. Menjawab tantangan lingkungan yang selalu berubah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena memenuhi ekspektasi layanan masyarakat merupakan tujuan nilai Berorientasi Pelayanan.</li><li><strong>B</strong>: Kurang tepat karena penggunaan sarana dinas secara efisien merupakan panduan perilaku nilai Akuntabel.</li><li><strong>C</strong>: Kurang tepat karena membangun kerukunan keberagaman merupakan perwujudan dari nilai Harmonis.</li><li><strong>E</strong>: Kurang tepat karena menghasilkan nilai tambah melalui kerja sama merupakan panduan nilai Kolaboratif.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 17) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>meningkatkan kompetensi diri untuk menjawab • tantangan yang selalu berubah</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Petugas loket menyapa dan melayani setiap pemohon secara ramah tanpa membedakan suku maupun keyakinan, mencerminkan nilai Harmonis yaitu...", "options": ["Melakukan perbaikan layanan tiada henti", "Menghargai setiap orang tanpa membedakan latar belakang", "Menggerakkan sumber daya untuk tujuan bersama", "Memegang teguh ideologi dasar negara", "Melaksanakan tugas dengan cermat dan disiplin"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kesetaraan Perlakuan Nilai Harmonis</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Panduan perilaku nilai <strong>Harmonis</strong> salah satunya adalah <strong>menghargai setiap orang tanpa membedakan latar belakang</strong> suku, agama, ras, dan antargolongan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat sesuai sikap adil petugas loket terhadap seluruh pemohon jasa layanan. Penghormatan atas martabat kemanusiaan tanpa prasangka primordial menjadi fondasi kerukunan bermasyarakat. Prinsip budaya menegaskan bahwa nilai Harmonis dipandu oleh perilaku menghargai setiap orang tanpa membedakan latar belakang. Dengan demikian, pelayanan nondiskriminatif tersebut mencerminkan butir pada opsi B.</p><p>Jawaban tepat: <strong>B. Menghargai setiap orang tanpa membedakan latar belakang</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan butir penyempurnaan kualitas pada nilai Berorientasi Pelayanan.</li><li><strong>C</strong>: Merupakan butir pemanfaatan potensi pada nilai Kolaboratif.</li><li><strong>D</strong>: Merupakan butir kesetiaan konstitusional pada nilai Loyal.</li><li><strong>E</strong>: Merupakan butir ketelitian kerja dinas pada nilai Akuntabel.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 17) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menghargai setiap orang tanpa membedakan latar belakang • Harmonis, dengan panduan perilaku</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Sikap suka menolong dan saling mendukung antarrekan sekerja pada nilai Harmonis bertujuan untuk...", "options": ["Mempercepat penyelesaian target capaian kinerja unit", "Mengoptimalkan pemanfaatan sumber daya bersama satker", "Meningkatkan akurasi penyusunan laporan pertanggungjawaban", "Memperkuat komitmen kesetiaan terhadap tugas kedinasan", "Membangun lingkungan kerja kedinasan yang kondusif"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penciptaan Iklim Kerja Kondusif</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Panduan perilaku nilai <strong>Harmonis</strong> mencakup sikap menghargai setiap orang, suka menolong, serta <strong>membangun lingkungan kerja yang kondusif</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat karena suasana tolong-menolong memperkuat soliditas tim dan menghilangkan ketegangan dalam pelaksanaan tugas sehari-hari. Hubungan interpersonal yang positif secara langsung menciptakan atmosfer kerja yang menyenangkan dan produktif. Dengan demikian, terwujudnya iklim kerja kondusif menjadi tujuan dari perilaku suka menolong.</p><p>Jawaban tepat: <strong>E. Membangun lingkungan kerja kedinasan yang kondusif</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena kecepatan penyelesaian target kinerja berorientasi pada ketuntasan nilai Kompeten.</li><li><strong>B</strong>: Kurang tepat karena pemanfaatan sumber daya bersama merupakan panduan perilaku pada nilai Kolaboratif.</li><li><strong>C</strong>: Kurang tepat karena akurasi laporan pertanggungjawaban merupakan wujud kecermatan nilai Akuntabel.</li><li><strong>D</strong>: Kurang tepat karena komitmen kesetiaan kedinasan merupakan cerminan pengamalan dari nilai Loyal.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 17) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>membangun lingkungan kerja yang kondusif • suka menolong</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Seorang staf menolak memberikan salinan draf kebijakan perpajakan sebelum tanggal pengumuman resmi, sebagai wujud nilai Loyal dalam...", "options": ["Membantu orang lain belajar keterampilan baru", "Memberi kesempatan pihak luar berkontribusi", "Menjaga rahasia jabatan dan negara", "Menyesuaikan diri menghadapi disrupsi", "Menggunakan fasilitas dinas secara efisien"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penjagaan Kerahasiaan Data Kedinasan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Panduan perilaku nilai <strong>Loyal</strong> salah satunya mewajibkan seluruh aparatur negara untuk <strong>menjaga rahasia jabatan dan negara</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat sesuai penolakan staf terhadap pembocoran dokumen yang belum dibuka untuk umum. Menjaga dokumen rahasia kedinasan dari akses tidak sah merupakan wujud loyalitas mutlak abdi negara. Perilaku menjaga rahasia jabatan merupakan jabatan dan negara merupakan butir resmi nilai Loyal. Dengan demikian, tindakan pengamanan data dinas tersebut menjalankan nilai Loyal.</p><p>Jawaban tepat: <strong>C. Menjaga rahasia jabatan dan negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan butir pembelajaran pegawai pada nilai Kompeten.</li><li><strong>B</strong>: Merupakan butir keterbukaan peran serta pada nilai Kolaboratif.</li><li><strong>D</strong>: Merupakan butir kelincahan adaptasi pada nilai Adaptif.</li><li><strong>E</strong>: Merupakan butir pengelolaan fasilitas dinas pada nilai Akuntabel.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 17) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menjaga rahasia jabatan dan negara • Loyal, dengan panduan perilaku</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Panduan perilaku nilai Loyal yang paling mendasar bagi seluruh aparatur sipil negara di Indonesia adalah...", "options": ["Memegang teguh ideologi Pancasila dan UUD 1945 serta setia kepada NKRI", "Melakukan perbaikan berkelanjutan terhadap fasilitas loket masyarakat", "Menggunakan barang milik negara secara bertanggung jawab dan hemat", "Cepat beradaptasi terhadap perubahan aplikasi teknologi informasi", "Membangun kemitraan strategis dengan berbagai lembaga swasta"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Komitmen Kebangsaan Nilai Loyal</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Panduan perilaku pertama nilai Loyal adalah <strong>memegang teguh ideologi Pancasila dan Undang-Undang Dasar Negara Republik Indonesia Tahun 1945, setia kepada NKRI serta pemerintahan yang sah</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat mengenai komitmen dasar kebangsaan pada nilai Loyal. Kesetiaan terhadap konstitusi dan keutuhan bangsa merupakan landasan tertinggi integritas seorang aparatur negara. Secara psikologis, panduan perilaku pertama nilai Loyal berbunyi memegang teguh ideologi Pancasila dan UUD 1945, setia kepada NKRI serta pemerintahan yang sah. Oleh karena itu, rumusan pada opsi A adalah panduan perilaku yang dimaksud.</p><p>Jawaban tepat: <strong>A. Memegang teguh ideologi Pancasila dan UUD 1945 serta setia kepada NKRI</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan panduan perbaikan tanpa henti pada nilai Berorientasi Pelayanan.</li><li><strong>C</strong>: Merupakan panduan pemanfaatan aset pada nilai Akuntabel.</li><li><strong>D</strong>: Merupakan panduan kelincahan menghadapi perubahan pada nilai Adaptif.</li><li><strong>E</strong>: Merupakan panduan kemitraan lintas pihak pada nilai Kolaboratif.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 17) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>memegang teguh ideologi Pancasila • Undang-Undang Dasar Negara Republik</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Ketika instansi menerapkan sistem administrasi digital terintegrasi, aparatur segera menyesuaikan diri demi...", "options": ["Menjaga kerahasiaan data jabatan dan rahasia negara", "Membangun hubungan kemitraan strategis lintas instansi", "Memberikan kepuasan pelayanan bagi pemangku kepentingan", "Cepat beradaptasi dalam menghadapi perubahan lingkungan", "Mewujudkan efektivitas penggunaan kekayaan milik negara"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kelincahan Penyesuaian Nilai Adaptif</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pada nilai <strong>Adaptif</strong>, aparatur dituntut memiliki kelincahan untuk <strong>cepat menyesuaikan diri menghadapi perubahan</strong> alur kerja maupun teknologi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat karena kesiapan mengadopsi sistem digital menunjukkan sikap tanggap terhadap pembaruan metode kerja birokrasi. Aparatur yang adaptif tidak menunggu lama untuk mempelajari hal baru demi kelancaran operasional kantor. Oleh sebab itu, adaptasi terhadap perubahan merupakan perwujudan utama perilaku nilai Adaptif.</p><p>Jawaban tepat: <strong>D. Cepat beradaptasi dalam menghadapi perubahan lingkungan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena menjaga rahasia jabatan dan negara merupakan panduan perilaku dari nilai Loyal.</li><li><strong>B</strong>: Kurang tepat karena membangun kemitraan lintas instansi merupakan panduan perilaku nilai Kolaboratif.</li><li><strong>C</strong>: Kurang tepat karena kepuasan pemangku kepentingan merupakan orientasi nilai Berorientasi Pelayanan.</li><li><strong>E</strong>: Kurang tepat karena efektivitas penggunaan kekayaan negara merupakan panduan nilai Akuntabel.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 17) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>cepat menyesuaikan diri menghadapi perubahan • Adaptif, dengan panduan perilaku</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Sikap terbuka dalam bekerja sama pada nilai Kolaboratif diarahkan secara jelas guna...", "options": ["Menjaga keselarasan etika dan kode etik aparatur", "Menghasilkan nilai tambah bagi pencapaian bersama", "Memelihara reputasi positif korps kepegawaian dinas", "Mempercepat perbaikan layanan publik berkelanjutan", "Mewujudkan ketepatan waktu penyelesaian laporan dinas"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Keterbukaan Kerja Sama Nilai Kolaboratif</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Nilai <strong>Kolaboratif</strong> menuntut aparatur bersikap <strong>terbuka dalam bekerja sama untuk menghasilkan nilai tambah</strong> bagi organisasi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat karena kerja sama yang sinergis antarseksi mampu menggabungkan berbagai keahlian untuk melahirkan solusi terbaik. Kemitraan kerja tidak sekadar formalitas, melainkan sarana menciptakan efektivitas dan dampak positif yang lebih besar. Dengan demikian, penciptaan nilai tambah menjadi tujuan pokok dari keterbukaan kerja sama.</p><p>Jawaban tepat: <strong>B. Menghasilkan nilai tambah bagi pencapaian bersama</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena kepatuhan kode etik merupakan fokus utama pelaksanaan tugas pada nilai Integritas.</li><li><strong>C</strong>: Kurang tepat karena menjaga nama baik instansi dan negara merupakan panduan perilaku nilai Loyal.</li><li><strong>D</strong>: Kurang tepat karena perbaikan berkesinambungan merupakan panduan nilai Berorientasi Pelayanan.</li><li><strong>E</strong>: Kurang tepat karena ketepatan waktu penyelesaian laporan mencerminkan kecermatan nilai Akuntabel.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 17) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>terbuka dalam bekerja sama untuk menghasilkan nilai tambah • Kolaboratif, dengan panduan perilaku</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Pencetusan employer branding #BanggaMelayaniBangsa menegaskan kedudukan ASN sebagai...", "options": ["Insan birokrasi pengawal kebijakan fiskal nasional", "Penggerak utama transformasi digital instansi negara", "Profesi yang bangga dalam melayani kepentingan bangsa", "Aparatur perencana standar operasional layanan publik", "Agen pembaruan tata kelola akuntabilitas kedinasan"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Makna Employer Branding ASN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Employer branding <strong>#BanggaMelayaniBangsa</strong> menunjukkan bahwa ASN adalah <strong>profesi yang bangga dalam melayani bangsa</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat karena semboyan ini menanamkan kebanggaan luhur dan jiwa pengabdian bagi seluruh aparatur negara. ASN diposisikan bukan sebagai pejabat birokratis yang dilayani, melainkan pelayan masyarakat yang mengabdi demi kemakmuran negeri. Oleh karena itu, profesi yang bangga melayani bangsa adalah makna sejati dari branding tersebut.</p><p>Jawaban tepat: <strong>C. Profesi yang bangga dalam melayani kepentingan bangsa</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena pengawal kebijakan fiskal merupakan tugas spesifik pada rumpun perbendaharaan negara.</li><li><strong>B</strong>: Kurang tepat karena penggerak transformasi digital merupakan peran fungsional analis teknologi informasi.</li><li><strong>D</strong>: Kurang tepat karena perencana standar layanan merupakan fungsi tata laksana kelembagaan satker.</li><li><strong>E</strong>: Kurang tepat karena agen pembaruan akuntabilitas berfokus pada pengawasan kinerja internal instansi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 18) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>#BanggaMelayaniBangsa di dalam sanubari • profesi yang bangga dalam melayani bangsa</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Salah satu alasan mendasar mengapa profesi ASN patut dibanggakan oleh setiap aparatur adalah karena...", "options": ["Diberi pengakuan dan penghargaan yang adil", "Diberi wewenang menyusun regulasi kelembagaan", "Diberi keleluasaan menetapkan metode audit kerja", "Diberi penugasan dinas pada lembaga mitra strategis", "Diberi tanggung jawab memimpin program lintas seksi"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Apresiasi Adil bagi Profesi ASN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Profesi ASN patut dibanggakan karena ASN <strong>diberi pengakuan dan penghargaan yang adil</strong>, kesempatan meningkatkan kompetensi, serta kesempatan terbuka untuk berkarier.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat karena sistem merit aparatur sipil negara menjamin apresiasi yang setara atas setiap dedikasi dan kinerja pegawai. Keadilan perlakuan ini memberikan kepastian rasa aman dan penghargaan yang layak atas pengabdian kepada bangsa. Dengan demikian, pengakuan dan penghargaan yang adil menjadi pilar kebanggaan berprofesi.</p><p>Jawaban tepat: <strong>A. Diberi pengakuan dan penghargaan yang adil</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kurang tepat karena wewenang regulasi kelembagaan merupakan mandat struktural jabatan tertentu.</li><li><strong>C</strong>: Kurang tepat karena penetapan metode audit kerja merupakan ranah tugas auditor fungsional pemeriksa.</li><li><strong>D</strong>: Kurang tepat karena penugasan pada lembaga mitra merupakan penugasan kedinasan khusus pimpinan.</li><li><strong>E</strong>: Kurang tepat karena memimpin program lintas seksi adalah penugasan manajerial koordinatif.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 18) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Profesi ASN patut dibanggakan • pengakuan dan penghargaan yang</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C4 - Analisis", "question": "Berikut ini adalah landasan mengapa profesi ASN patut dibanggakan dalam tata kelola aparatur, KECUALI...", "options": ["Diberi pengakuan dan penghargaan yang adil", "Diberi kesempatan meningkatkan kompetensi seluasnya", "Diberi kesempatan terbuka untuk berkarier", "Diberi ruang untuk mengabdi kepada bangsa", "Diberi otonomi penuh menentukan standar belanja dinas"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Alasan Profesi ASN Patut Dibanggakan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Alasan profesi ASN patut dibanggakan meliputi: <strong>diberi pengakuan dan penghargaan yang adil</strong>, <strong>diberi kesempatan meningkatkan kompetensi seluas-luasnya</strong>, serta <strong>diberi kesempatan terbuka untuk berkarier</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat untuk soal pengecualian karena standar belanja dinas diatur ketat melalui standar biaya masukan perbendaharaan negara dan tidak ditentukan secara mandiri oleh aparatur. Adapun opsi A, B, C, dan D merupakan alasan nyata kebanggaan profesi yang menjadi pedoman kebanggaan profesi aparatur. Oleh sebab itu, opsi E adalah pilihan yang bukan merupakan alasan kebanggaan profesi ASN.</p><p>Jawaban tepat: <strong>E. Diberi otonomi penuh menentukan standar belanja dinas</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena pengakuan dan penghargaan yang adil adalah alasan resmi kebanggaan ASN.</li><li><strong>B</strong>: Kurang tepat karena kesempatan meningkatkan kapasitas seluas-luasnya adalah hak kebanggaan profesi ASN.</li><li><strong>C</strong>: Kurang tepat karena kesempatan terbuka dalam jenjang karier dijamin dalam manajemen ASN.</li><li><strong>D</strong>: Kurang tepat karena ruang pengabdian kepada bangsa merupakan hakikat utama pengabdian aparatur.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 18) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>kesempatan meningkatkan kompetensi seluas-luasnya • kesempatan terbuka untuk berkarier</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Sebagai wujud kebanggaan berprofesi, seorang aparatur menyeimbangkan ekspektasi organisasi melalui upaya...", "options": ["Menjaga kemitraan harmonis dengan seluruh pemangku tugas", "Terus meningkatkan kinerja kedinasan secara berkelanjutan", "Mengoptimalkan pemanfaatan sarana penunjang tugas satker", "Memperkuat ketaatan pada prosedur baku operasional dinas", "Memastikan akuntabilitas pelaporan pertanggungjawaban kas"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Keseimbangan Ekspektasi Kinerja ASN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dengan kebanggaan berprofesi, ASN diharapkan mampu menyeimbangkan ekspektasi organisasi terhadap dirinya dengan <strong>meningkatkan kinerja secara terus menerus</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat karena peningkatan capaian kinerja yang berkesinambungan mencerminkan dedikasi nyata pegawai dalam membalas kepercayaan institusi. Sikap proaktif ini memastikan bahwa output kerja pegawai selalu sepadan dengan harapan tinggi organisasi publik. Dengan demikian, peningkatan kinerja terus menerus merupakan wujud keseimbangan ekspektasi.</p><p>Jawaban tepat: <strong>B. Terus meningkatkan kinerja kedinasan secara berkelanjutan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena menjaga kemitraan harmonis merupakan pengamalan prinsip kolaborasi antarseksi.</li><li><strong>C</strong>: Kurang tepat karena pemanfaatan sarana penunjang tugas berkaitan dengan efisiensi pengelolaan aset dinas.</li><li><strong>D</strong>: Kurang tepat karena ketaatan prosedur operasional mencerminkan kepatuhan terhadap regulasi kedinasan.</li><li><strong>E</strong>: Kurang tepat karena akuntabilitas pelaporan kas merupakan cerminan ketelitian pengelolaan keuangan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 18) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menyeimbangkan harapan dan ekspektasi organisasi • meningkatkan kinerja secara terus menerus</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Nilai Dasar ASN dan Nilai Kementerian Keuangan saling mendukung karena berlandaskan konsep agar pegawai...", "options": ["Mewujudkan akuntabilitas pengelolaan anggaran negara", "Meningkatkan produktivitas kerja berbasis teknologi", "Membangun jejaring kerja sama lintas satuan kerja dinas", "Mengelola pribadi yang selaras dengan tujuan tugasnya", "Mengembangkan standar layanan publik yang solutif-aman"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kesamaan Konsep Nilai ASN dan Kemenkeu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Antara Nilai Dasar ASN dan Nilai Kementerian Keuangan saling mendukung karena memiliki konsep dasar yang sama, yaitu agar pegawai dapat <strong>mengelola pribadi yang selaras dengan tujuan dari pelaksanaan tugasnya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat karena kedua tatanan nilai tersebut berakar pada pembentukan karakter moral dan profesionalitas individu dalam birokrasi. Penyelarasan sikap mental aparatur dengan tujuan organisasi menjamin pelaksanaan kewajiban dinas secara berintegritas. Oleh karena itu, pengelolaan pribadi selaras tujuan tugas adalah konsep dasar perpaduan kedua nilai.</p><p>Jawaban tepat: <strong>D. Mengelola pribadi yang selaras dengan tujuan tugasnya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena akuntabilitas pengelolaan anggaran berfokus pada ranah teknis perbendaharaan.</li><li><strong>B</strong>: Kurang tepat karena produktivitas kerja berbasis teknologi merupakan sasaran modernisasi sistem kantor.</li><li><strong>C</strong>: Kurang tepat karena jejaring kerja sama lintas unit merupakan instrumen kelembagaan pada nilai Sinergi.</li><li><strong>E</strong>: Kurang tepat karena pengembangan standar layanan publik berorientasi pada kepuasan pengguna jasa loket.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 18) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>dua nilai yang dapat saling mendukung • pribadi yang selaras dengan tujuan</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Dalam peluncuran Core Values BerAKHLAK, Kementerian PANRB mengharapkan nilai-nilai yang telah ada di instansi...", "options": ["Dijadikan acuan tambahan khusus untuk unit pelayanan teknis", "Disesuaikan formulasi kalimatnya mengikuti urutan BerAKHLAK", "Tidak dihadapkan sebagai ketentuan yang saling bertentangan", "Diterapkan bertahap berdasarkan kesiapan masing-masing balai", "Diposisikan sebagai instrumen pelengkap regulasi kepegawaian"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Arahan Kementerian PANRB tentang Nilai Instansi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kementerian PANRB mengharapkan agar peluncuran Nilai Dasar BerAKHLAK <strong>tidak dihadapkan dengan nilai-nilai yang telah ada di instansinya sebagai sesuatu yang bertentangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat karena nilai-nilai instansi yang telah terbangun justru berfungsi memperkaya dan memperkuat nilai BerAKHLAK pada ranah operasional. Penyatuan semangat kedua nilai menciptakan harmoni budaya kerja tanpa harus merombak fondasi yang sudah berjalan baik. Dengan demikian, ketiadaan pertentangan nilai menjadi prinsip pokok yang diharapkan.</p><p>Jawaban tepat: <strong>C. Tidak dihadapkan sebagai ketentuan yang saling bertentangan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena nilai dasar berlaku mengikat seluruh struktur organisasi, bukan hanya unit teknis.</li><li><strong>B</strong>: Kurang tepat karena instansi tidak diwajibkan mengubah rumusan bahasa nilai yang sudah berlaku.</li><li><strong>D</strong>: Kurang tepat karena penerapan nilai dasar nasional diinstruksikan berlaku serentak di seluruh instansi.</li><li><strong>E</strong>: Kurang tepat karena nilai organisasi berkedudukan sebagai acuan budaya dan perilaku kerja utama.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 18) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Kementerian PAN RB • sesuatu yang bertentangan</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Meskipun BerAKHLAK menjadi acuan utama nasional, nilai-nilai yang sudah ada di kementerian/lembaga kedudukannya...", "options": ["Substansinya tetap hidup dan memperkuat nilai BerAKHLAK", "Kehilangan dasar kekuatan hukum dalam pelaksanaan dinas", "Beralih fungsi menjadi acuan pembagian fasilitas kerja", "Berlaku terbatas bagi pejabat tinggi madya dan pratama", "Disesuaikan kembali melalui voting terbuka seluruh pegawai"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kedudukan Nilai Instansi terhadap BerAKHLAK</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Nilai-nilai Dasar ASN BerAKHLAK tetap menjadi acuan, namun nilai-nilai yang sudah ada di kementerian/lembaga <strong>substansinya tetap hidup dan memperkuat nilai BerAKHLAK tersebut</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat mengenai hubungan komplementer antara nilai instansi dengan nilai BerAKHLAK. Budaya kerja yang telah matang justru menjadi katalisator bagi percepatan internalisasi nilai BerAKHLAK di lapangan. Dalam panduan perilaku dirumuskan bahwa nilai-nilai yang sudah ada di kementerian/lembaga substansinya tetap hidup dan memperkuat nilai BerAKHLAK. Dengan demikian, keberadaan nilai yang ada saling memperkokoh satu sama lain.</p><p>Jawaban tepat: <strong>A. Substansinya tetap hidup dan memperkuat nilai BerAKHLAK</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Keputusan menteri tentang nilai instansi tetap mempunyai kekuatan hukum sah.</li><li><strong>C</strong>: Nilai organisasi merupakan pedoman perilaku etis, bukan instrumen fasilitas.</li><li><strong>D</strong>: Nilai instansi mengikat seluruh pegawai tanpa membedakan tingkatan eselon.</li><li><strong>E</strong>: Tata nilai ditetapkan oleh pimpinan negara dan institusi melalui kajian formal.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 18) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>substansinya tetap • memperkuat nilai BerAKHLAK</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Budaya BerAKHLAK pada prinsipnya selaras dengan Nilai-Nilai Kementerian Keuangan sehingga...", "options": ["Memerlukan pembentukan kementerian koordinasi baru", "Meniadakan lima nilai kementerian yang telah ditetapkan", "Memisahkan tugas pegawai pusat dan aparatur daerah", "Mewajibkan penerbitan undang-undang kepegawaian baru", "Dapat masuk dan berpadu selaras di dalamnya"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Keselarasan Budaya BerAKHLAK dan Kemenkeu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Budaya BerAKHLAK pada prinsipnya <strong>sejalan dengan Nilai-nilai Kementerian Keuangan sehingga dapat masuk di dalamnya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat mengenai kemudahan integrasi kedua sistem nilai tersebut. Karena fondasi etik keduanya sama, BerAKHLAK dapat langsung menyatu dengan budaya kerja Kementerian Keuangan tanpa hambatan friksi konseptual. Prinsip organisasi menyatakan bahwa Budaya BerAKHLAK pada prinsipnya sejalan dengan Nilai-nilai Kementerian Keuangan sehingga dapat masuk di dalamnya. Oleh sebab itu, perpaduan yang selaras menjadi konsekuensi logisnya.</p><p>Jawaban tepat: <strong>E. Dapat masuk dan berpadu selaras di dalamnya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Integrasi nilai berjalan secara kultural tanpa perlu kelembagaan kementerian baru.</li><li><strong>B</strong>: Lima nilai Kementerian Keuangan tetap kokoh dan saling melengkapi dengan BerAKHLAK.</li><li><strong>C</strong>: Prinsip BerAKHLAK menyatukan pegawai pusat dan daerah dalam standar yang sama.</li><li><strong>D</strong>: Landasan hukum integrasi nilai telah dipayungi oleh peraturan perundangan yang ada.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 18) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Budaya BerAKHLAK pada prinsipnya sejalan • sehingga dapat masuk di dalamnya</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Dalam praktik kerja di lingkungan Kementerian Keuangan, hal utama yang wajib menjadi fokus perhatian pegawai adalah...", "options": ["Penyelarasan target penerimaan pajak antardaerah", "Keselarasan implementasi Nilai Dasar ASN dan Kemenkeu", "Perbedaan besaran insentif fiskal antarsektor industri", "Pembatasan pengadaan perangkat komputer kantor", "Penyeragaman motif batik dinas pada seluruh kantor"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Fokus Perhatian Implementasi Tata Nilai</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Hal yang perlu menjadi perhatian utama adalah <strong>bagaimana keselarasan implementasi Nilai Dasar ASN dengan Nilai-nilai Kementerian Keuangan</strong> dalam aktivitas kedinasan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat sesuai penekanan etika bagi seluruh jajaran pegawai. Memahami korelasi antara butir BerAKHLAK dan lima nilai kementerian menjamin sikap dan tindakan aparatur tetap berada di jalur yang benar. Secara jelas ditekankan bahwa dengan demikian, yang perlu menjadi perhatian adalah bagaimana keselarasan implementasi Nilai Dasar ASN dengan Nilai-nilai Kementerian Keuangan. Dengan demikian, keselarasan implementasi menjadi fokus utama.</p><p>Jawaban tepat: <strong>B. Keselarasan implementasi Nilai Dasar ASN dan Kemenkeu</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Target penerimaan pajak merupakan sasaran teknis kuantitatif pada rencana anggaran.</li><li><strong>C</strong>: Kebijakan insentif fiskal diatur melalui regulasi makroekonomi pemerintah.</li><li><strong>D</strong>: Pengadaan peralatan kantor merupakan ranah logistik dan rumah tangga kantor.</li><li><strong>E</strong>: Pakaian dinas merupakan tata tertib atribut fisik, bukan substansi keselarasan nilai.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 18) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>yang perlu menjadi • keselarasan implementasi Nilai Dasar ASN</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Penjelasan mengenai hubungan selaras antara komponen Nilai Dasar ASN dan Nilai Kementerian Keuangan dipaparkan melalui konsep...", "options": ["Sistem Informasi Tata Naskah Kepegawaian", "Mekanisme Pemantauan Kinerja Strategis Kantor", "Kerangka Regulasi Standar Operasional Pelayanan", "Ekosistem Budaya Kementerian Keuangan", "Struktur Hubungan Kelembagaan Vertikal Instansi"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Ekosistem Budaya Kementerian Keuangan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Hubungan yang selaras antara komponen Nilai Dasar ASN dan Nilai-Nilai Kementerian Keuangan dijelaskan melalui <strong>Ekosistem Budaya Kementerian Keuangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat mengenai wadah konseptual keselarasan tata nilai di kementerian. Pendekatan ekosistem memetakan interaksi nilai nasional dan nilai institusi ke dalam satu kesatuan budaya kerja yang harmonis. Dalam kerangka Ekosistem Budaya Kementerian Keuangan, terdapat hubungan yang selaras dan saling menguatkan antara kedua sistem nilai. Oleh sebab itu, konsep Ekosistem Budaya Kementerian Keuangan menjadi jawabannya.</p><p>Jawaban tepat: <strong>D. Ekosistem Budaya Kementerian Keuangan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan aplikasi administrasi surat-menyurat dinas dan arsip kepegawaian.</li><li><strong>B</strong>: Merupakan instrumen monitoring target sasaran kinerja pegawai pada organisasi.</li><li><strong>C</strong>: Merupakan panduan operasional prosedur teknis pelayanan pada meja bantuan.</li><li><strong>E</strong>: Merupakan tata susun pembagian garis hierarki pada bagan organisasi instansi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 18) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Budaya Kementerian Keuangan • hubungan yang selaras</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Perpaduan Core Values ASN BerAKHLAK Akuntabel dan Loyal selaras dengan nilai Kementerian Keuangan yaitu...", "options": ["Integritas", "Profesionalisme", "Sinergi", "Pelayanan", "Kesempurnaan"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Keterkaitan Akuntabel-Loyal dengan Integritas</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam keterkaitan Core Values ASN BerAKHLAK dan Nilai-nilai Kemenkeu, nilai <strong>Akuntabel dan Loyal</strong> berpadanan secara langsung dengan nilai <strong>Integritas</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat sesuai pemetaan resmi pada penyelarasan nilai BerAKHLAK dan Nilai Kemenkeu. Kejujuran bertindak (akuntabel) serta keteguhan memegang sumpah jabatan (loyal) merupakan intisari watak berintegritas. Dalam keterkaitan nilai, secara jelas dipadankan bahwa nilai Akuntabel dan Loyal berpasangan dengan Integritas. Dengan demikian, nilai Integritas merupakan padanan yang tepat bagi kedua nilai ASN tersebut.</p><p>Jawaban tepat: <strong>A. Integritas</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Berpasangan dengan Core Value Kompeten dalam pemetaan penyelarasan nilai.</li><li><strong>C</strong>: Berpasangan dengan gabungan Core Values Harmonis dan Kolaboratif.</li><li><strong>D</strong>: Berpasangan dengan Core Value Berorientasi Pelayanan.</li><li><strong>E</strong>: Berpasangan dengan Core Value Adaptif dalam pemetaan penyelarasan nilai.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 19) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Akuntabel dan Loyal • Integritas</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Dalam penyelarasan Core Values ASN BerAKHLAK dan Nilai-nilai Kemenkeu, nilai Profesionalisme berpadanan langsung dengan...", "options": ["Adaptif", "Kompeten", "Harmonis", "Akuntabel", "Kolaboratif"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Keterkaitan Kompeten dengan Profesionalisme</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Nilai Kementerian Keuangan <strong>Profesionalisme</strong> memiliki keterkaitan langsung dengan Core Value ASN <strong>Kompeten</strong> dalam penyelarasan nilai kementerian.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat sesuai matriks korelasi budaya kerja kementerian. Kemahiran bekerja secara tuntas dan andal (profesionalisme) berakar kuat pada penguasaan keahlian (kompeten). Dalam penyelarasan nilai ASN dan Kementerian Keuangan, komponen Kompeten berpadanan secara tepat dengan Profesionalisme. Oleh sebab itu, Core Value Kompeten merupakan pasangan yang benar.</p><p>Jawaban tepat: <strong>B. Kompeten</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Berpadanan dengan nilai Kesempurnaan dalam kerangka inovasi berkelanjutan.</li><li><strong>C</strong>: Bersama nilai Kolaboratif membentuk keselarasan dengan nilai Sinergi.</li><li><strong>D</strong>: Bersama nilai Loyal membentuk keselarasan dengan nilai Integritas.</li><li><strong>E</strong>: Bersama nilai Harmonis membentuk keselarasan dengan nilai Sinergi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 19) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Kompeten • Profesionalisme</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Nilai Kementerian Keuangan Sinergi memiliki keselarasan langsung dengan gabungan Core Values ASN yaitu...", "options": ["Akuntabel dan Adaptif", "Kompeten dan Loyal", "Harmonis dan Kolaboratif", "Berorientasi Pelayanan dan Adaptif", "Akuntabel dan Kolaboratif"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Keterkaitan Harmonis-Kolaboratif dengan Sinergi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Nilai Kementerian Keuangan <strong>Sinergi</strong> selaras dengan perpaduan Core Values ASN <strong>Harmonis dan Kolaboratif</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat mengenai kombinasi nilai ASN yang membentuk nilai Sinergi. Suasana saling menghargai (harmonis) yang dipadukan dengan keterbukaan bekerja sama (kolaboratif) merupakan prasyarat mutlak terciptanya sinergi organisasi. Dalam keselarasan nilai organisasi, Harmonis dan Kolaboratif dipetakan selaras dengan Sinergi. Dengan demikian, opsi C memuat pasangan yang tepat.</p><p>Jawaban tepat: <strong>C. Harmonis dan Kolaboratif</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Bukan merupakan pasangan terpadu pada matriks nilai penyelarasan nilai.</li><li><strong>B</strong>: Bukan merupakan gabungan nilai yang dipadankan dalam pedoman etika.</li><li><strong>D</strong>: Berorientasi Pelayanan berpasangan dengan Pelayanan, Adaptif dengan Kesempurnaan.</li><li><strong>E</strong>: Akuntabel berpasangan dengan Integritas bersama nilai Loyal.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 19) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Harmonis dan Kolaboratif • Sinergi</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Core Value ASN Berorientasi Pelayanan memiliki padanan langsung pada Nilai-Nilai Kementerian Keuangan yaitu...", "options": ["Integritas", "Profesionalisme", "Sinergi", "Pelayanan", "Kesempurnaan"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Keterkaitan Berorientasi Pelayanan dengan Pelayanan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Core Value ASN <strong>Berorientasi Pelayanan</strong> berpadanan langsung dengan nilai Kementerian Keuangan <strong>Pelayanan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat sesuai baris keempat pada penyelarasan nilai BerAKHLAK dan Nilai Kemenkeu. Komitmen memberikan pelayanan terbaik bagi kepuasan masyarakat menjadi fokus utama dari kedua konsep ini. Dalam penyelarasan nilai BerAKHLAK dan Kemenkeu, Berorientasi Pelayanan memiliki hubungan selaras dengan Pelayanan. Oleh karena itu, nilai Pelayanan merupakan pasangan yang benar.</p><p>Jawaban tepat: <strong>D. Pelayanan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan padanan bagi gabungan nilai Akuntabel dan Loyal.</li><li><strong>B</strong>: Merupakan padanan bagi Core Value ASN Kompeten.</li><li><strong>C</strong>: Merupakan padanan bagi perpaduan nilai Harmonis dan Kolaboratif.</li><li><strong>E</strong>: Merupakan padanan bagi Core Value ASN Adaptif.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 19) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Berorientasi Pelayanan • Pelayanan</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Nilai Kementerian Keuangan Kesempurnaan memiliki keselarasan langsung dengan Core Value ASN yaitu...", "options": ["Loyal", "Kompeten", "Harmonis", "Akuntabel", "Adaptif"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Keterkaitan Adaptif dengan Kesempurnaan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Nilai Kementerian Keuangan <strong>Kesempurnaan</strong> memiliki keselarasan langsung dengan Core Value ASN <strong>Adaptif</strong> dalam penyelarasan nilai kementerian.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat mengenai padanan nilai ASN bagi perbaikan tanpa henti dan inovasi kerja. Kemampuan menyesuaikan diri dan berinovasi (adaptif) merupakan wujud nyata ikhtiar memberikan hasil kerja terbaik (kesempurnaan). Dalam penyelarasan nilai tertulis tegas hubungan antara Adaptif dengan Kesempurnaan. Dengan demikian, Core Value Adaptif merupakan padanannya.</p><p>Jawaban tepat: <strong>E. Adaptif</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Bersama nilai Akuntabel berpadanan dengan nilai Integritas.</li><li><strong>B</strong>: Berpadanan dengan nilai Profesionalisme dalam penyelarasan nilai.</li><li><strong>C</strong>: Bersama nilai Kolaboratif berpadanan dengan nilai Sinergi.</li><li><strong>D</strong>: Bersama nilai Loyal berpadanan dengan nilai Integritas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 19) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Adaptif • Kesempurnaan</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Seorang bendahara mencatat pengeluaran secara jujur serta teguh menjaga rahasia dinas, membuktikan keselarasan nilai ASN dengan nilai Kemenkeu...", "options": ["Integritas", "Profesionalisme", "Sinergi", "Pelayanan", "Kesempurnaan"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penerapan Keselarasan Integritas</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Perilaku jujur bertanggung jawab (Akuntabel) dan menjaga rahasia dinas (Loyal) secara simultan mengaktualisasikan nilai Kementerian Keuangan <strong>Integritas</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat sesuai ilustrasi pengamalan tugas oleh bendahara tersebut. Nilai Akuntabel tecermin dari pencatatan jujur, sedangkan nilai Loyal tecermin dari penjagaan rahasia dinas. Dalam penyelarasan nilai ASN dan kementerian, kedua perilaku ASN tersebut bermuara pada nilai Integritas Kementerian Keuangan. Oleh sebab itu, nilai Integritas merupakan nilai yang terbukti dalam perilaku tersebut.</p><p>Jawaban tepat: <strong>A. Integritas</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Berfokus pada penyelesaian tugas berlandaskan kompetensi dan keahlian tinggi.</li><li><strong>C</strong>: Berfokus pada koordinasi kemitraan produktif dan hubungan kerja harmonis.</li><li><strong>D</strong>: Berfokus pada kecepatan, transparansi, dan keramahan pemenuhan layanan publik.</li><li><strong>E</strong>: Berfokus pada ikhtiar perbaikan berkelanjutan dan kreativitas inovasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 19) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Akuntabel dan Loyal • Tabel 1: Keterkaitan Core Values ASN dan Nilai-nilai Kemenkeu</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Pemeriksa pajak yang memperdalam ilmu akuntansi forensik demi menyelesaikan audit secara tuntas mencerminkan keselarasan nilai...", "options": ["Akuntabel dan Sinergi", "Kompeten dan Profesionalisme", "Harmonis dan Integritas", "Adaptif dan Pelayanan", "Loyal dan Kesempurnaan"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penerapan Keselarasan Profesionalisme</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Peningkatan keahlian secara tekun (Kompeten) guna menyelesaikan tugas tuntas dan akurat mencerminkan keselarasan antara nilai <strong>Kompeten dan Profesionalisme</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat sesuai hubungan langsung antara penguasaan ilmu dengan ketuntasan tugas. Tindakan memperdalam ilmu mewakili nilai Kompeten, sementara penyelesaian audit secara tuntas mewakili nilai Profesionalisme. Dalam pemetaan nilai kementerian, nilai Kompeten berpasangan langsung dengan Profesionalisme. Dengan demikian, kedua nilai tersebut selaras secara sempurna.</p><p>Jawaban tepat: <strong>B. Kompeten dan Profesionalisme</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Bukan merupakan pasangan keselarasan yang tercantum pada penyelarasan nilai pedoman kerja.</li><li><strong>C</strong>: Harmonis berpasangan dengan Sinergi, bukan Integritas.</li><li><strong>D</strong>: Adaptif berpasangan dengan Kesempurnaan, sedangkan Pelayanan dengan Berorientasi Pelayanan.</li><li><strong>E</strong>: Loyal berpasangan dengan Integritas bersama nilai Akuntabel.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 19) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Kompeten • Tabel 1: Keterkaitan Core Values ASN dan Nilai-nilai Kemenkeu</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Dua seksi pelayanan bekerja sama secara rukun dan terbuka demi mempercepat penyelesaian izin usaha, mencerminkan keselarasan nilai...", "options": ["Akuntabel, Loyal, dan Integritas", "Kompeten, Adaptif, dan Profesionalisme", "Harmonis, Kolaboratif, dan Sinergi", "Berorientasi Pelayanan, Loyal, dan Pelayanan", "Adaptif, Kompeten, dan Kesempurnaan"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penerapan Keselarasan Sinergi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kerja sama rukun (Harmonis) dan terbuka untuk nilai tambah bersama (Kolaboratif) merupakan wujud keselarasan dengan nilai Kementerian Keuangan <strong>Sinergi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat sesuai hubungan timbal balik antara suasana rukun dan kerja sama produktif. Kerukunan antarseksi mencerminkan nilai Harmonis, keterbukaan kerja sama mencerminkan nilai Kolaboratif, dan hasil sinergisnya mewujudkan nilai Sinergi. Dalam penyelarasan nilai, Harmonis dan Kolaboratif dipetakan selaras dengan nilai Sinergi. Oleh karena itu, ketiga nilai pada opsi C adalah wujud keselarasan yang tepat.</p><p>Jawaban tepat: <strong>C. Harmonis, Kolaboratif, dan Sinergi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan rumpun nilai kejujuran, kepatuhan norma, dan kesetiaan dinas.</li><li><strong>B</strong>: Merupakan rumpun nilai penguasaan kecakapan dan ketuntasan tugas teknis.</li><li><strong>D</strong>: Menggabungkan nilai Loyal yang tidak termasuk dalam rumpun pelayanan.</li><li><strong>E</strong>: Merupakan kombinasi nilai yang tidak selaras dengan pemetaan penyelarasan nilai.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 19) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Harmonis dan Kolaboratif • Nilai-nilai Kemenkeu</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Petugas loket menyapa pemohon dengan ramah serta memproses berkas secara cepat dan akurat, mencerminkan keselarasan nilai...", "options": ["Kompeten dan Sinergi", "Akuntabel dan Kesempurnaan", "Harmonis dan Profesionalisme", "Berorientasi Pelayanan dan Pelayanan", "Adaptif dan Integritas"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penerapan Keselarasan Pelayanan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sikap ramah dan cekatan memenuhi kebutuhan pemangku kepentingan secara transparan merupakan wujud keselarasan nilai <strong>Berorientasi Pelayanan dan Pelayanan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat sesuai perpaduan standar perilaku abdi negara dengan nilai kementerian. Menyapa ramah mencerminkan nilai Berorientasi Pelayanan ASN, sedangkan kecepatan dan keakuratan mencerminkan nilai Pelayanan Kementerian Keuangan. Penyelarasan nilai merumuskan bahwa Berorientasi Pelayanan berpasangan secara langsung dengan nilai Pelayanan. Dengan demikian, opsi D mencerminkan keselarasan tersebut.</p><p>Jawaban tepat: <strong>D. Berorientasi Pelayanan dan Pelayanan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kompeten berpasangan dengan Profesionalisme, sedangkan Sinergi dengan Harmonis-Kolaboratif.</li><li><strong>B</strong>: Akuntabel berpasangan dengan Integritas, sedangkan Kesempurnaan dengan Adaptif.</li><li><strong>C</strong>: Harmonis berpasangan dengan Sinergi, sedangkan Profesionalisme dengan Kompeten.</li><li><strong>E</strong>: Adaptif berpasangan dengan Kesempurnaan, sedangkan Integritas dengan Akuntabel-Loyal.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 19) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Berorientasi Pelayanan • Tabel 1: Keterkaitan Core Values ASN dan Nilai-nilai Kemenkeu</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C4 - Analisis", "question": "Pasangan penyelarasan nilai BerAKHLAK dan Nilai Kementerian Keuangan yang TIDAK TEPAT adalah...", "options": ["Akuntabel dan Loyal berpasangan dengan Integritas", "Kompeten berpasangan dengan Profesionalisme", "Harmonis dan Kolaboratif berpasangan dengan Sinergi", "Berorientasi Pelayanan berpasangan dengan Pelayanan", "Adaptif berpasangan dengan Profesionalisme"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Analisis Keterkaitan Tabel 1</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sesuai penyelarasan nilai pedoman, <strong>Adaptif</strong> berpasangan dengan nilai <strong>Kesempurnaan</strong>, bukan dengan Profesionalisme.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat karena memuat pasangan yang keliru dalam penyelarasan nilai. Dalam penyelarasan tersebut, Core Value Adaptif dipasangkan dengan nilai Kesempurnaan, sedangkan Profesionalisme berpasangan dengan Kompeten. Seluruh pasangan pada opsi A, B, C, dan D telah sesuai dengan rumusan resmi penyelarasan nilai. Oleh karena itu, opsi E merupakan pernyataan yang tidak tepat.</p><p>Jawaban tepat: <strong>E. Adaptif berpasangan dengan Profesionalisme</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan pasangan baris pertama yang tepat pada penyelarasan nilai pedoman kerja.</li><li><strong>B</strong>: Merupakan pasangan baris kedua yang tepat pada penyelarasan nilai pedoman kerja.</li><li><strong>C</strong>: Merupakan pasangan baris ketiga yang tepat pada penyelarasan nilai pedoman kerja.</li><li><strong>D</strong>: Merupakan pasangan baris keempat yang tepat pada penyelarasan nilai pedoman kerja.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 19) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Core Values ASN (BerAKHLAK) • Tabel 1: Keterkaitan Core Values ASN dan Nilai-nilai Kemenkeu</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Penetapan resmi mengenai Nilai-Nilai Kementerian Keuangan beserta rinciannya diatur dalam...", "options": ["Peraturan Pemerintah Nomor 42 Tahun 2004", "Keputusan Menteri Keuangan Nomor 312/KMK.01/2011", "Surat Edaran Bersama Menteri Keuangan dan Kepala BKN", "Peraturan Presiden Nomor 12 Tahun 2021", "Undang-Undang Nomor 17 Tahun 2003"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dasar Hukum Nilai-Nilai Kementerian Keuangan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Nilai-Nilai Kementerian Keuangan mencakup lima nilai yang ditetapkan resmi berdasarkan <strong>Keputusan Menteri Keuangan Nomor 312/KMK.01/2011</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat mengenai dasar hukum sah berlakunya Nilai-Nilai Kementerian Keuangan. Regulasi menteri ini menjadi pedoman perilaku baku bagi seluruh jajaran pegawai di pusat maupun daerah. Nilai-Nilai Kementerian Keuangan ditetapkan sesuai Keputusan Menteri Keuangan Nomor 312/KMK.01/2011. Dengan demikian, KMK Nomor 312/KMK.01/2011 adalah landasan hukum resminya.</p><p>Jawaban tepat: <strong>B. Keputusan Menteri Keuangan Nomor 312/KMK.01/2011</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan Peraturan Pemerintah mengenai pembinaan jiwa korps dan kode etik PNS.</li><li><strong>C</strong>: Bukan merupakan surat edaran penetapan resmi lima nilai Kementerian Keuangan.</li><li><strong>D</strong>: Merupakan Peraturan Presiden tentang pengadaan barang dan jasa pemerintah.</li><li><strong>E</strong>: Merupakan undang-undang induk tentang pengelolaan Keuangan Negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 20) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Keputusan Menteri Keuangan Nomor 312/KMK.01/2011 • Kementerian Keuangan mencakup 5 nilai</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Sebagai organisasi modern dan berkembang, Kementerian Keuangan menggerakkan organisasinya dengan mengadopsi...", "options": ["Pola evaluasi kinerja berbasis manajemen risiko terpadu", "Sistem perencanaan program berbasis penganggaran kinerja", "Standar pelayanan prima berorientasi kepuasan masyarakat", "Praktik-praktik manajemen yang sehat dalam organisasi dinas", "Kerangka pembinaan karier aparatur berbasis uji kompetensi"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penerapan Best Practices Manajemen Kemenkeu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sebagai organisasi modern, Kementerian Keuangan telah mengadopsi dan mengadaptasi <strong>praktik-praktik manajemen yang sehat (best practices)</strong> dalam menjalankan roda organisasinya.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat karena penerapan best practices memastikan bahwa seluruh tata kelola birokrasi berstandar internasional dan akuntabel. Adopsi praktik manajemen yang sehat mendorong terciptanya budaya kerja yang efektif, transparan, dan terpercaya. Oleh sebab itu, praktik manajemen yang sehat menjadi landasan operasional kementerian.</p><p>Jawaban tepat: <strong>D. Praktik-praktik manajemen yang sehat dalam organisasi dinas</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena manajemen risiko terpadu merupakan salah satu instrumen pengendalian internal kantor.</li><li><strong>B</strong>: Kurang tepat karena perencanaan berbasis anggaran kinerja merupakan metode penyusunan alokasi fiskal.</li><li><strong>C</strong>: Kurang tepat karena standar pelayanan prima merupakan wujud operasional pada unit pelayanan publik.</li><li><strong>E</strong>: Kurang tepat karena pembinaan karier berbasis uji kompetensi merupakan bagian dari manajemen talenta ASN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 20) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>organisasi modern, maju dan terus berkembang • praktik-praktik manajemen yang</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan Keputusan Menteri Keuangan Nomor 312/KMK.01/2011, Nilai-Nilai Kementerian Keuangan mencakup...", "options": ["5 nilai utama", "7 nilai utama", "4 nilai utama", "6 nilai utama", "3 nilai utama"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Jumlah Nilai Resmi Kementerian Keuangan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sesuai KMK Nomor 312/KMK.01/2011, Nilai-Nilai Kementerian Keuangan mencakup <strong>5 nilai yakni: Integritas, Profesionalisme, Sinergi, Pelayanan dan Kesempurnaan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat mengenai jumlah nilai resmi yang ditetapkan kementerian. Kelima pilar tersebut mengikat seluruh pegawai dalam berpikir, bertindak, dan berperilaku secara profesional. Secara resmi dinyatakan bahwa sesuai Keputusan Menteri Keuangan Nomor 312/KMK.01/2011, Nilai-Nilai Kementerian Keuangan mencakup 5 nilai yakni: Integritas, Profesionalisme, Sinergi, Pelayanan dan Kesempurnaan. Oleh karena itu, 5 nilai utama adalah jumlah yang tepat.</p><p>Jawaban tepat: <strong>A. 5 nilai utama</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan jumlah nilai dasar pada core values nasional ASN BerAKHLAK.</li><li><strong>C</strong>: Kurang tepat karena pedoman kerja menetapkan lima nilai secara utuh.</li><li><strong>D</strong>: Bukan merupakan jumlah pilar budaya kerja Kementerian Keuangan.</li><li><strong>E</strong>: Terlalu sedikit dan tidak mencakup seluruh nilai resmi kementerian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 20) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Kementerian Keuangan mencakup 5 nilai • Integritas, Profesionalisme, Sinergi,</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Unsur utama makna nilai Integritas menekankan sikap berpegang teguh pada...", "options": ["Kompetensi terbaik dan tanggung jawab tinggi", "Hubungan kerja sama produktif dan harmonis", "Kode etik dan prinsip-prinsip moral", "Kepuasan pemangku kepentingan secara aman", "Upaya perbaikan di segala bidang organisasi"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Unsur Moral Nilai Integritas Kemenkeu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Makna nilai <strong>Integritas</strong> adalah berpikir, berkata, berperilaku, dan bertindak dengan baik dan benar serta <strong>memegang teguh kode etik dan prinsip-prinsip moral</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat sesuai rumusan makna nilai Integritas pada perilaku utama. Integritas menyelaraskan seluruh dimensi lahiriah dan batiniah manusia dalam bingkai kebenaran serta kode etik. Dalam prinsip dasar, makna Integritas dirumuskan: berpikir, berkata, berperilaku, dan bertindak dengan baik dan benar serta memegang teguh kode etik dan prinsip-prinsip moral. Oleh sebab itu, opsi C merupakan unsur moral yang ditekankan.</p><p>Jawaban tepat: <strong>C. Kode etik dan prinsip-prinsip moral</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan penekanan pada makna nilai Profesionalisme pada panduan perilaku utama.</li><li><strong>B</strong>: Merupakan penekanan pada makna nilai Sinergi pada panduan perilaku utama.</li><li><strong>D</strong>: Merupakan penekanan pada makna nilai Pelayanan pada panduan perilaku utama.</li><li><strong>E</strong>: Merupakan penekanan pada makna nilai Kesempurnaan pada panduan perilaku utama.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 20) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Integritas • dan prinsip-prinsip moral</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Perilaku utama pertama dari nilai Integritas dalam panduan perilaku utama adalah...", "options": ["Mempunyai keahlian dan pengetahuan yang luas", "Memiliki sangka baik, saling percaya, dan saling menghormati", "Melakukan perbaikan secara terus menerus", "Melayani dengan berorientasi pada kepuasan", "Bersikap jujur, tulus, dan dapat dipercaya"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Perilaku Utama Pertama Nilai Integritas</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Perilaku utama pertama dari nilai Integritas adalah <strong>bersikap jujur, tulus, dan dapat dipercaya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat mengenai butir perilaku utama pertama pada nilai Integritas. Kejujuran yang disertai ketulusan hati menghasilkan kredibilitas diri aparatur yang diakui publik. Dalam panduan perilaku utama, butir nomor satu perilaku utama Integritas berbunyi bersikap jujur, tulus, dan dapat dipercaya. Dengan demikian, opsi E merupakan butir perilaku utama yang dimaksud.</p><p>Jawaban tepat: <strong>E. Bersikap jujur, tulus, dan dapat dipercaya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan perilaku utama pertama dari nilai Profesionalisme.</li><li><strong>B</strong>: Merupakan perilaku utama pertama dari nilai Sinergi.</li><li><strong>C</strong>: Merupakan perilaku utama pertama dari nilai Kesempurnaan.</li><li><strong>D</strong>: Merupakan perilaku utama pertama dari nilai Pelayanan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 20) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>bersikap jujur, tulus, dan • Integritas</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Perilaku utama kedua dari nilai Integritas dalam panduan perilaku utama kementerian adalah...", "options": ["Bekerja dengan hati dan panggilan jiwa", "Menjaga martabat dan tidak melakukan hal tercela", "Menemukan dan melaksanakan solusi terbaik", "Bersikap proaktif dan cepat tanggap dalam tugas", "Mengembangkan inovasi dan kreativitas baru"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Perilaku Utama Kedua Nilai Integritas</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Perilaku utama kedua dari nilai Integritas adalah <strong>menjaga martabat dan tidak melakukan hal tercela</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat mengenai butir perilaku kedua pada nilai Integritas. Menghindari perbuatan hina dan menjaga keluhuran budi merupakan benteng pertahanan moral abdi negara. Panduan perilaku utama mencatat butir nomor dua perilaku utama Integritas adalah menjaga martabat dan tidak melakukan hal tercela. Oleh karena itu, rumusan pada opsi B adalah perilaku utama yang tepat.</p><p>Jawaban tepat: <strong>B. Menjaga martabat dan tidak melakukan hal tercela</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan perilaku utama kedua pada nilai Profesionalisme (bekerja dengan hati).</li><li><strong>C</strong>: Merupakan perilaku utama kedua pada nilai Sinergi.</li><li><strong>D</strong>: Merupakan perilaku utama kedua pada nilai Pelayanan.</li><li><strong>E</strong>: Merupakan perilaku utama kedua pada nilai Kesempurnaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 20) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menjaga martabat dan • Integritas</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Makna nilai Profesionalisme dalam panduan perilaku utama menekankan pelaksanaan tugas atas dasar...", "options": ["Kepuasan pemangku kepentingan secara transparan", "Kemitraan harmonis dan kerja sama produktif", "Kode etik dan prinsip-prinsip moral kedinasan", "Kompetensi terbaik dengan komitmen yang tinggi", "Upaya perbaikan berkelanjutan di segala bidang"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Landasan Pelaksanaan Tugas Profesionalisme</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Makna nilai <strong>Profesionalisme</strong> menekankan bekerja tuntas dan akurat atas dasar <strong>kompetensi terbaik dengan penuh tanggung jawab dan komitmen yang tinggi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat sesuai definisi makna nilai Profesionalisme pada perilaku utama. Penguasaan keahlian yang disertai ketelitian dan dedikasi menghasilkan mutu output kerja yang andal. Dalam panduan perilaku dirumuskan bahwa Profesionalisme bermakna bekerja tuntas dan akurat atas dasar kompetensi terbaik dengan penuh tanggung jawab dan komitmen yang tinggi. Dengan demikian, opsi D merupakan landasan intinya.</p><p>Jawaban tepat: <strong>D. Kompetensi terbaik dengan komitmen yang tinggi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan penekanan pada makna nilai Pelayanan.</li><li><strong>B</strong>: Merupakan penekanan pada makna nilai Sinergi.</li><li><strong>C</strong>: Merupakan penekanan pada makna nilai Integritas.</li><li><strong>E</strong>: Merupakan penekanan pada makna nilai Kesempurnaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 20) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Profesionalisme • kompetensi terbaik dengan</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Perilaku utama pertama dari nilai Profesionalisme dalam panduan perilaku utama kementerian adalah...", "options": ["Mempunyai keahlian dan pengetahuan yang luas", "Bersikap jujur, tulus, dan dapat dipercaya", "Menemukan dan melaksanakan solusi terbaik", "Melayani dengan berorientasi pada kepuasan", "Melakukan perbaikan secara terus menerus"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Perilaku Utama Pertama Nilai Profesionalisme</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Perilaku utama pertama dari nilai Profesionalisme adalah <strong>mempunyai keahlian dan pengetahuan yang luas</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat mengenai butir perilaku utama pertama nilai Profesionalisme. Wawasan yang mendalam dan kecakapan teknis menjadi syarat mutlak bagi pegawai dalam memecahkan persoalan yang kompleks. Panduan perilaku utama menetapkan perilaku utama pertama nilai Profesionalisme berbunyi mempunyai keahlian dan pengetahuan yang luas. Oleh sebab itu, opsi A merupakan rumusan perilaku yang tepat.</p><p>Jawaban tepat: <strong>A. Mempunyai keahlian dan pengetahuan yang luas</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan perilaku utama pertama pada nilai Integritas.</li><li><strong>C</strong>: Merupakan perilaku utama kedua pada nilai Sinergi.</li><li><strong>D</strong>: Merupakan perilaku utama pertama pada nilai Pelayanan.</li><li><strong>E</strong>: Merupakan perilaku utama pertama pada nilai Kesempurnaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 20) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>dan pengetahuan yang • Profesionalisme</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Perilaku utama kedua dari nilai Profesionalisme dalam panduan perilaku utama kementerian adalah...", "options": ["Mengembangkan inovasi dan kreativitas kerja", "Menjaga martabat dan tidak berbuat tercela", "Bekerja dengan hati", "Bersikap proaktif dan cepat tanggap melayani", "Memiliki sangka baik dan saling menghormati"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Perilaku Utama Kedua Nilai Profesionalisme</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Perilaku utama kedua dari nilai Profesionalisme adalah <strong>bekerja dengan hati</strong> dengan penuh dedikasi dan cinta pada profesi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat mengenai perilaku utama kedua pada pilar Profesionalisme. Menjalankan tugas dengan ketulusan nurani dan keikhlasan mengubah rutinitas kerja menjadi pengabdian yang bermakna. Butir kedua perilaku utama Profesionalisme secara tegas mewajibkan bekerja dengan hati. Dengan demikian, bekerja dengan hati adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>C. Bekerja dengan hati</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan perilaku utama kedua dari nilai Kesempurnaan.</li><li><strong>B</strong>: Merupakan perilaku utama kedua dari nilai Integritas.</li><li><strong>D</strong>: Merupakan perilaku utama kedua dari nilai Pelayanan.</li><li><strong>E</strong>: Merupakan perilaku utama pertama dari nilai Sinergi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 20) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>bekerja dengan hati • Profesionalisme</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Seorang verifikator memeriksa dokumen pencairan anggaran secara teliti hingga tuntas tanpa salah hitung, mencerminkan makna...", "options": ["Sinergi", "Pelayanan", "Kesempurnaan", "Integritas", "Profesionalisme"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penerapan Ketuntasan Kerja Profesionalisme</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Bekerja tuntas dan akurat atas dasar kompetensi terbaik dengan penuh tanggung jawab dan komitmen tinggi merupakan perwujudan makna nilai <strong>Profesionalisme</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat sesuai pengamalan tugas oleh verifikator tersebut. Ketelitian dalam perhitungan dan ketuntasan proses berkas mencerminkan kompetensi teknis yang tinggi. Secara konseptual, esensi bekerja tuntas dan akurat merupakan rumusan langsung dari makna nilai Profesionalisme. Oleh karena itu, tindakan verifikator tersebut mencerminkan nilai Profesionalisme.</p><p>Jawaban tepat: <strong>E. Profesionalisme</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Berfokus pada koordinasi kemitraan harmonis dan kerja sama produktif.</li><li><strong>B</strong>: Berfokus pada kepuasan pemangku kepentingan dan kecepatan pelayanan.</li><li><strong>C</strong>: Berfokus pada perbaikan terus menerus dan penciptaan inovasi baru.</li><li><strong>D</strong>: Berfokus pada keselarasan perkataan dan perbuatan serta ketaatan kode etik.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 20) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Bekerja tuntas dan akurat • kompetensi terbaik dengan</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Fokus utama makna nilai Sinergi adalah membangun hubungan...", "options": ["Kompetensi terbaik dengan komitmen yang tinggi", "Pelayanan transparan demi kepuasan bersama", "Kerja sama produktif serta kemitraan harmonis", "Kepatuhan penuh pada kode etik kedinasan", "Perbaikan terus-menerus di segala bidang"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Fokus Utama Makna Nilai Sinergi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Makna nilai <strong>Sinergi</strong> berfokus pada <strong>membangun dan memastikan hubungan kerja sama internal yang produktif serta kemitraan yang harmonis dengan para pemangku kepentingan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat sesuai definisi makna nilai Sinergi pada perilaku utama. Sinergi menggabungkan kekuatan internal dan eksternal secara harmonis demi menghasilkan karya berkualitas tinggi. Nilai Sinergi bermakna membangun kerja sama internal produktif dan kemitraan harmonis para pemangku kepentingan. Oleh sebab itu, opsi C merupakan fokus utama yang tepat.</p><p>Jawaban tepat: <strong>C. Kerja sama produktif serta kemitraan harmonis</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan rumusan resmi makna nilai Profesionalisme pada panduan perilaku utama.</li><li><strong>B</strong>: Merupakan rumusan resmi makna nilai Pelayanan pada panduan perilaku utama.</li><li><strong>D</strong>: Merupakan rumusan resmi makna nilai Integritas pada panduan perilaku utama.</li><li><strong>E</strong>: Merupakan rumusan resmi makna nilai Kesempurnaan pada panduan perilaku utama.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 21) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>hubungan kerja sama internal • yang harmonis dengan para</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Perilaku utama pertama dari nilai Sinergi dalam panduan perilaku utama berbunyi...", "options": ["Memiliki sangka baik, saling percaya, dan saling menghormati", "Menemukan dan melaksanakan solusi terbaik", "Bersikap proaktif dan cepat tanggap dalam bertindak", "Mengembangkan inovasi dan kreativitas baru", "Mempunyai keahlian dan pengetahuan yang luas"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Perilaku Utama Pertama Nilai Sinergi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Perilaku utama pertama dari nilai Sinergi adalah <strong>memiliki sangka baik, saling percaya, dan saling menghormati</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat mengenai butir perilaku pertama pada pilar Sinergi. Prasangka baik dan rasa saling menghargai adalah fondasi utama yang memungkinkan terjalinnya kerja sama yang solid antarsesama pegawai. Dalam panduan perilaku utama tertulis tegas butir nomor satu perilaku utama Sinergi adalah memiliki sangka baik, saling percaya, dan saling menghormati. Dengan demikian, opsi A adalah rumusan perilaku yang benar.</p><p>Jawaban tepat: <strong>A. Memiliki sangka baik, saling percaya, dan saling menghormati</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan perilaku utama kedua dari nilai Sinergi.</li><li><strong>C</strong>: Merupakan perilaku utama kedua dari nilai Pelayanan.</li><li><strong>D</strong>: Merupakan perilaku utama kedua dari nilai Kesempurnaan.</li><li><strong>E</strong>: Merupakan perilaku utama pertama dari nilai Profesionalisme.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 21) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>memiliki sangka baik, • saling percaya, dan saling</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Perilaku utama kedua dari nilai Sinergi dalam panduan perilaku utama kementerian adalah...", "options": ["Menjaga martabat dan tidak melakukan hal tercela", "Bekerja dengan hati dan panggilan jiwa", "Melakukan perbaikan secara terus menerus", "Melayani dengan berorientasi pada kepuasan", "Menemukan dan melaksanakan solusi terbaik"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Perilaku Utama Kedua Nilai Sinergi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Perilaku utama kedua dari nilai Sinergi adalah <strong>menemukan dan melaksanakan solusi terbaik</strong> bersama seluruh pemangku kepentingan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat mengenai butir perilaku kedua pada pilar Sinergi. Kerja sama yang efektif tidak berhenti pada hubungan baik, melainkan bermuara pada penemuan dan pengeksekusian solusi terbaik demi kepentingan organisasi. Dalam panduan perilaku utama, butir nomor dua perilaku utama Sinergi berbunyi menemukan dan melaksanakan solusi terbaik. Oleh karena itu, opsi E adalah perilaku utama yang dimaksud.</p><p>Jawaban tepat: <strong>E. Menemukan dan melaksanakan solusi terbaik</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan perilaku utama kedua pada nilai Integritas.</li><li><strong>B</strong>: Merupakan perilaku utama kedua pada nilai Profesionalisme.</li><li><strong>C</strong>: Merupakan perilaku utama pertama pada nilai Kesempurnaan.</li><li><strong>D</strong>: Merupakan perilaku utama pertama pada nilai Pelayanan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 21) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menemukan dan • Sinergi</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Makna nilai Pelayanan menekankan pelaksanaan layanan pemenuh kepuasan secara...", "options": ["Mandiri, terstruktur, dan penuh tanggung jawab", "Sepenuh hati, transparan, cepat, akurat dan aman", "Inovatif, adaptif, dan berkelanjutan", "Komprehensif, terencana, dan hemat anggaran", "Kolektif, harmonis, dan berbasis kemitraan"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Karakter Pelaksanaan Nilai Pelayanan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Makna nilai <strong>Pelayanan</strong> adalah memberikan layanan yang memenuhi kepuasan pemangku kepentingan yang dilaksanakan secara <strong>sepenuh hati, transparan, cepat, akurat dan aman</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat sesuai rumusan sifat pelaksanaan layanan pada panduan perilaku utama. Pelayanan publik aparatur harus memenuhi lima kriteria mutu: ketulusan hati, keterbukaan informasi, kecepatan waktu, ketepatan hasil, dan keamanan proses. Dalam panduan perilaku dirumuskan bahwa Pelayanan dilaksanakan dengan sepenuh hati, transparan, cepat, akurat dan aman. Dengan demikian, opsi B adalah rincian pelaksanaan yang tepat.</p><p>Jawaban tepat: <strong>B. Sepenuh hati, transparan, cepat, akurat dan aman</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan gabungan kata sifat umum pada prinsip akuntabilitas kerja.</li><li><strong>C</strong>: Merupakan karakteristik tindakan pada nilai Adaptif dan Kesempurnaan.</li><li><strong>D</strong>: Merupakan karakteristik penyusunan rencana kerja operasional anggaran.</li><li><strong>E</strong>: Merupakan karakteristik hubungan koordinasi kerja pada nilai Sinergi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 21) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pelayanan • transparan, cepat, akurat dan</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Perilaku utama kedua dari nilai Pelayanan dalam panduan perilaku utama kementerian adalah...", "options": ["Melayani dengan berorientasi pada kepuasan pemangku kepentingan", "Memiliki sangka baik, saling percaya, dan saling menghormati", "Mempunyai keahlian dan pengetahuan yang luas", "Bersikap proaktif dan cepat tanggap", "Menjaga martabat dan tidak berbuat tercela"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Perilaku Utama Kedua Nilai Pelayanan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Perilaku utama kedua dari nilai Pelayanan adalah <strong>bersikap proaktif dan cepat tanggap</strong> dalam melayani pemangku kepentingan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat mengenai butir perilaku kedua pada nilai Pelayanan. Sikap tanggap dan inisiatif tinggi menjamin kebutuhan pengguna jasa segera teratasi tanpa hambatan birokrasi yang lambat. Dalam panduan perilaku utama, butir nomor dua perilaku utama Pelayanan berbunyi bersikap proaktif dan cepat tanggap. Oleh karena itu, opsi D merupakan butir perilaku yang dimaksud.</p><p>Jawaban tepat: <strong>D. Bersikap proaktif dan cepat tanggap</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan butir perilaku utama pertama dari nilai Pelayanan.</li><li><strong>B</strong>: Merupakan butir perilaku utama pertama dari nilai Sinergi.</li><li><strong>C</strong>: Merupakan perilaku utama pertama dari nilai Profesionalisme.</li><li><strong>E</strong>: Merupakan butir perilaku utama kedua dari nilai Integritas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 21) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>bersikap proaktif dan • Pelayanan</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Makna dari nilai Kesempurnaan dirumuskan sebagai upaya...", "options": ["Bekerja tuntas atas kompetensi terbaik", "Membangun kemitraan kerja sama harmonis", "Perbaikan di segala bidang untuk hasil terbaik", "Menjaga kode etik dan moralitas tindakan", "Memberikan layanan cepat dan akurat"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Rumusan Makna Nilai Kesempurnaan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Makna nilai <strong>Kesempurnaan</strong> adalah <strong>senantiasa melakukan upaya perbaikan di segala bidang untuk menjadi dan memberikan yang terbaik</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat sesuai rumusan makna nilai Kesempurnaan pada perilaku utama. Sikap pantang berpuas diri mendorong aparatur untuk terus mengevaluasi dan meningkatkan mutu kinerjanya. Secara normatif ditegaskan bahwa Kesempurnaan bermakna perbaikan di segala bidang untuk memberikan yang terbaik. Dengan demikian, opsi C merupakan makna yang benar.</p><p>Jawaban tepat: <strong>C. Perbaikan di segala bidang untuk hasil terbaik</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan rumusan resmi makna nilai Profesionalisme pada panduan perilaku utama.</li><li><strong>B</strong>: Merupakan rumusan resmi makna nilai Sinergi pada panduan perilaku utama.</li><li><strong>D</strong>: Merupakan rumusan resmi makna nilai Integritas pada panduan perilaku utama.</li><li><strong>E</strong>: Merupakan rumusan resmi makna nilai Pelayanan pada panduan perilaku utama.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 21) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>perbaikan di segala bidang • Kesempurnaan</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Dua perilaku utama dari nilai Kesempurnaan dalam panduan perilaku utama kementerian adalah...", "options": ["Melakukan perbaikan terus menerus serta mengembangkan inovasi dan kreativitas", "Mempunyai keahlian luas serta bekerja dengan keikhlasan hati nurani", "Memiliki sangka baik serta menemukan solusi terbaik pemangku kepentingan", "Bersikap jujur dan tulus serta menjaga martabat kehormatan dinas", "Melayani dengan berorientasi kepuasan serta bersikap proaktif dan tanggap"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dua Perilaku Utama Nilai Kesempurnaan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dua perilaku utama dari nilai <strong>Kesempurnaan</strong> adalah (1) <strong>melakukan perbaikan terus menerus</strong> dan (2) <strong>mengembangkan inovasi dan kreativitas</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat mengenai kedua butir perilaku utama nilai Kesempurnaan. Penyempurnaan yang berkelanjutan berpadu erat dengan kreativitas melahirkan terobosan baru bagi instansi. Dalam panduan perilaku utama, perilaku utama nilai Kesempurnaan terdiri atas: 1. melakukan perbaikan terus menerus; 2. mengembangkan inovasi dan kreativitas. Oleh sebab itu, opsi A merangkum kedua butir tersebut dengan benar.</p><p>Jawaban tepat: <strong>A. Melakukan perbaikan terus menerus serta mengembangkan inovasi dan kreativitas</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan pasangan perilaku utama dari nilai Profesionalisme.</li><li><strong>C</strong>: Merupakan pasangan perilaku utama dari nilai Sinergi.</li><li><strong>D</strong>: Merupakan pasangan perilaku utama dari nilai Integritas.</li><li><strong>E</strong>: Merupakan pasangan perilaku utama dari nilai Pelayanan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 21) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>melakukan perbaikan terus • mengembangkan inovasi</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Secara batiniah, integritas diartikan sebagai dorongan hati nurani untuk melaksanakan tanggung jawab dengan...", "options": ["Komitmen menjaga keharmonisan hubungan kerja sama internal", "Ketuntasan bekerja secara cermat atas kompetensi profesional", "Kesungguhan melayani demi pemenuhan kepuasan pemangku tugas", "Semangat melakukan perbaikan berkelanjutan di segala bidang", "Tekad yang mulia dalam melaksanakan setiap tugas kedinasan"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengertian Integritas sebagai Dorongan Batin</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Integritas dapat diartikan sebagai dorongan hati nurani untuk melaksanakan tugas dan tanggung jawab dengan <strong>tekad yang mulia</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat karena integritas berakar pada kemurnian niat dan komitmen luhur yang bersumber dari suara hati sanubari aparatur. Keikhlasan bertindak benar tanpa pamrih lahir dari tekad mulia dalam mengemban amanah pelayanan publik. Dengan demikian, pelaksanaan tugas berlandaskan tekad mulia menjadi hakikat batiniah integritas.</p><p>Jawaban tepat: <strong>E. Tekad yang mulia dalam melaksanakan setiap tugas kedinasan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena menjaga keharmonisan kerja sama internal mencerminkan fokus utama dari nilai Sinergi.</li><li><strong>B</strong>: Kurang tepat karena ketuntasan bekerja atas dasar kompetensi mencerminkan makna dari nilai Profesionalisme.</li><li><strong>C</strong>: Kurang tepat karena melayani demi kepuasan pemangku kepentingan merupakan makna pokok dari nilai Pelayanan.</li><li><strong>D</strong>: Kurang tepat karena semangat perbaikan berkelanjutan di segala bidang mencerminkan nilai Kesempurnaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 21) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>dorongan hati nurani untuk melaksanakan tugas • tekad yang mulia</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Menurut KBBI, integritas didefinisikan sebagai kesatuan utuh yang memancarkan...", "options": ["Kecerdasan berpikir dan inovasi kerja", "Kewibawaan dan kejujuran", "Kemahiran teknis dan keahlian profesi", "Keluasan jejaring dan hubungan sosial", "Ketangkasan adaptasi dan kedisiplinan"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pancaran Kewibawaan dan Kejujuran KBBI</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Menurut KBBI, integritas berarti mutu, sifat, atau keadaan yang menunjukkan kesatuan yang utuh sehingga <strong>memancarkan kewibawaan dan kejujuran</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat sesuai kutipan leksikal KBBI dalam etika kedinasan. Kesatuan yang utuh antara hati, ucapan, dan perbuatan memancarkan aura kewibawaan serta kejujuran sejati. Sebagaimana dikutip dalam pedoman etika: menurut KBBI, integritas berarti mutu, sifat, atau keadaan yang menunjukkan kesatuan yang utuh sehingga memiliki potensi dan kemampuan yang memancarkan kewibawaan dan kejujuran. Oleh karena itu, opsi B adalah inti pancarannya.</p><p>Jawaban tepat: <strong>B. Kewibawaan dan kejujuran</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan deskripsi keterampilan berpikir pada nilai Adaptif dan Kesempurnaan.</li><li><strong>C</strong>: Merupakan deskripsi kemahiran teknis pada nilai Profesionalisme.</li><li><strong>D</strong>: Merupakan deskripsi kecakapan relasi sosial pada nilai Sinergi.</li><li><strong>E</strong>: Merupakan gambaran kelincahan bertindak pada nilai Adaptif.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 21) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Menurut Kamus Besar Bahasa • kewibawaan dan kejujuran</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Ditinjau dari ranah etika, integritas secara lugas diartikan sebagai...", "options": ["Kecepatan dan ketepatan dalam melayani pemangku kepentingan", "Kesediaan menerima masukan dan kritik konstruktif masyarakat", "Kemampuan mengoordinasikan berbagai sumber daya organisasi", "Kejujuran dan kebenaran dari tindakan seseorang", "Kreativitas menciptakan metode kerja dinas yang baru"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengertian Integritas dalam Etika</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam ranah etika, <strong>integritas diartikan sebagai kejujuran dan kebenaran dari tindakan seseorang</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat mengenai batasan filosofis integritas dalam ilmu etika. Kebenaran suatu perbuatan diukur dari kesesuaian tindakan nyata seseorang dengan prinsip kejujuran moral. Secara konseptual ditegaskan bahwa dalam etika, integritas diartikan sebagai kejujuran dan kebenaran dari tindakan seseorang. Dengan demikian, kejujuran dan kebenaran tindakan merupakan arti integritas dalam etika.</p><p>Jawaban tepat: <strong>D. Kejujuran dan kebenaran dari tindakan seseorang</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan tolok ukur efektivitas pelayanan pada nilai Pelayanan.</li><li><strong>B</strong>: Merupakan sikap terbuka pada perbaikan nilai Berorientasi Pelayanan.</li><li><strong>C</strong>: Merupakan kecakapan manajerial koordinasi pada nilai Kolaboratif.</li><li><strong>E</strong>: Merupakan aspek inovasi dan pembaruan pada nilai Kesempurnaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 21) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Dalam etika, integritas diartikan sebagai • kebenaran dari tindakan seseorang</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Seseorang dikatakan mempunyai integritas apabila tindakannya selaras dengan...", "options": ["Target kuantitatif capaian sasaran kinerja tahunan", "Arahan kebijakan teknis operasional satuan kerja", "Standar baku prosedur pelayanan prima masyarakat", "Nilai, keyakinan, dan prinsip yang dipegangnya", "Kesepakatan hasil musyawarah kelompok kerja dinas"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengertian Integritas Menurut Ensiklopedia</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Seseorang dikatakan mempunyai integritas apabila tindakannya <strong>sesuai dengan nilai, keyakinan, dan prinsip yang dipegangnya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat karena integritas pribadi dicirikan oleh konsistensi teguh antara prinsip kebenaran yang dipercayai dengan perilaku lahiriah. Pegawai yang berintegritas tidak mudah goyah oleh desakan situasi karena memiliki pegangan nilai moral yang kokoh. Oleh karena itu, keselarasan dengan nilai dan prinsip yang dipegang adalah indikator seseorang berintegritas.</p><p>Jawaban tepat: <strong>D. Nilai, keyakinan, dan prinsip yang dipegangnya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena target kuantitatif sasaran kinerja merupakan tolok ukur capaian output aparatur.</li><li><strong>B</strong>: Kurang tepat karena arahan kebijakan teknis merupakan petunjuk pelaksanaan tugas pimpinan unit kerja.</li><li><strong>C</strong>: Kurang tepat karena standar operasional pelayanan prima merupakan panduan baku tata kelola loket.</li><li><strong>E</strong>: Kurang tepat karena kesepakatan hasil musyawarah kelompok kerja berorientasi pada mufakat tim kerja.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 22) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>mempunyai integritas • keyakinan, dan prinsip yang dipegangnya</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Salah satu ciri utama yang menandai bahwa seorang pegawai memiliki integritas tinggi adalah...", "options": ["Kemahiran memaparkan laporan kerja secara persuasif", "Bersatunya kata dan perbuatan dalam bertindak dinas", "Kecepatan memproses dokumen penugasan administrasi", "Keluasan hubungan kerja sama dengan mitra lembaga", "Ketelitian memeriksa kalkulasi data anggaran kantor"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Ciri Utama Bersatunya Kata dan Perbuatan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Salah satu ciri seseorang yang berintegritas adalah <strong>bersatunya kata dan perbuatan</strong> dalam setiap perkataan dan tindakannya.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat karena kejujuran sejati tercermin ketika ucapan yang disampaikan selaras sejalan dengan tindakan nyata di lapangan. Pegawai yang berintegritas menjunjung tinggi kesatuan moral ini sehingga tidak pernah menampilkan perilaku kepura-puraan. Dengan demikian, bersatunya kata dan perbuatan menjadi ciri mutlak integritas tinggi.</p><p>Jawaban tepat: <strong>B. Bersatunya kata dan perbuatan dalam bertindak dinas</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena kemahiran memaparkan laporan kerja mencerminkan keterampilan komunikasi presentasi.</li><li><strong>C</strong>: Kurang tepat karena kecepatan memproses dokumen administrasi mencerminkan ketangkasan nilai Pelayanan.</li><li><strong>D</strong>: Kurang tepat karena keluasan hubungan kerja sama dengan mitra mencerminkan jejaring nilai Sinergi.</li><li><strong>E</strong>: Kurang tepat karena ketelitian memeriksa kalkulasi anggaran mencerminkan kecermatan Profesionalisme.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 22) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Salah satu ciri seseorang yang • bersatunya kata dan perbuatan</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C4 - Analisis", "question": "Ditinjau dari karakter integritas, seorang aparatur sejati menjauhkan diri dari tipe figur yang...", "options": ["Bekerja dengan ketelitian tinggi dalam tata kelola", "Berinteraksi ramah serta solutif melayani masyarakat", "Berpegang teguh pada ideologi dan regulasi kedinasan", "Menjaga kerahasiaan berkas jabatan secara bertanggung jawab", "Memiliki banyak wajah dan penampilan sesuai motif pribadi"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penolakan Watak Oportunis Pribadi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Seorang yang mempunyai integritas <strong>bukan tipe manusia dengan banyak wajah dan penampilan yang disesuaikan dengan motif dan kepentingan pribadinya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat karena perilaku bermuka dua menunjukkan sikap hipokrit dan oportunistis yang bertolak belakang dengan kejujuran hati. Pegawai berintegritas selalu tulus dan konsisten dalam memegang prinsip tanpa memanipulasi citra diri demi keuntungan tertentu. Oleh sebab itu, tipe manusia dengan banyak wajah merupakan sifat yang dijauhi aparatur berintegritas.</p><p>Jawaban tepat: <strong>E. Memiliki banyak wajah dan penampilan sesuai motif pribadi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena bekerja dengan ketelitian tinggi merupakan karakter positif nilai Profesionalisme.</li><li><strong>B</strong>: Kurang tepat karena berinteraksi ramah dan solutif merupakan karakter positif Berorientasi Pelayanan.</li><li><strong>C</strong>: Kurang tepat karena berpegang teguh pada ideologi dan regulasi merupakan cerminan luhur nilai Loyal.</li><li><strong>D</strong>: Kurang tepat karena menjaga kerahasiaan berkas jabatan merupakan kewajiban etik aparatur negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 22) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>bukan tipe manusia dengan banyak wajah • motif dan kepentingan pribadinya</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Dalam konteks Nilai Kementerian Keuangan, integritas diwujudkan melalui keselarasan...", "options": ["Pikiran, ucapan, perilaku, dan tindakan benar", "Keahlian teknis dan target capaian kinerja", "Kepuasan publik dan efisiensi belanja dinas", "Hubungan kerja sama dan kemitraan harmonis", "Inovasi kerja dan kreativitas teknologi"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Keselarasan Dimensi Integritas Kemenkeu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam konteks Nilai-Nilai Kementerian Keuangan, <strong>integritas diartikan sebagai berpikir, berkata, berperilaku dan bertindak dengan baik dan benar serta memegang teguh kode etik dan prinsip-prinsip moral</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat sesuai rumusan konseptual integritas di lingkungan Kementerian Keuangan. Keempat dimensi manusia (pola pikir, ucapan, sikap, dan perbuatan) wajib diselaraskan dalam koridor moral dan kode etik. Sesuai panduan pedoman kerja, definisi resmi integritas Kementerian Keuangan memadukan berpikir, berkata, berperilaku, dan bertindak secara benar. Dengan demikian, opsi A adalah rumusan yang tepat.</p><p>Jawaban tepat: <strong>A. Pikiran, ucapan, perilaku, dan tindakan benar</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan dimensi keahlian teknis pada nilai Profesionalisme.</li><li><strong>C</strong>: Merupakan perpaduan aspek nilai Pelayanan dan manajemen anggaran.</li><li><strong>D</strong>: Merupakan dimensi hubungan kemitraan pada nilai Sinergi.</li><li><strong>E</strong>: Merupakan dimensi pembaruan pada nilai Kesempurnaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 22) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Dalam konteks Nilai-Nilai Kementerian Keuangan, integritas diartikan sebagai • kode etik dan prinsip-prinsip moral</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Seorang petugas penyusun laporan keuangan kantor menyajikan angka realisasi anggaran persis sesuai bukti transaksi, mewujudkan sikap jujur yaitu...", "options": ["Menjaga kerahasiaan seluruh dokumen dinas", "Membantu rekan kerja memahami alur jurnal", "Memberikan informasi berdasarkan fakta", "Menyesuaikan diri dengan aplikasi terbaru", "Membangun kemitraan harmonis lintas seksi"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penyampaian Fakta sebagai Sikap Jujur</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Salah satu sikap jujur ditunjukkan dengan <strong>memberikan informasi berdasarkan fakta</strong> yang sebenarnya terjadi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat sesuai pengamalan kejujuran dalam pelaporan angka keuangan tersebut. Transparansi dan keakuratan fakta menjamin laporan yang dihasilkan dapat dipercaya oleh semua pihak. Sesuai penegasan pedoman kerja, salah satu sikap jujur ditunjukkan dengan memberikan informasi berdasarkan fakta. Oleh sebab itu, penyajian data sesuai bukti transaksi tersebut mencerminkan pemberian informasi berdasar fakta.</p><p>Jawaban tepat: <strong>C. Memberikan informasi berdasarkan fakta</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan butir perlindungan informasi pada nilai Loyal dan Integritas.</li><li><strong>B</strong>: Merupakan butir transfer pengetahuan pada nilai Kompeten.</li><li><strong>D</strong>: Merupakan butir kelincahan adaptasi sistem pada nilai Adaptif.</li><li><strong>E</strong>: Merupakan butir penciptaan relasi kerja pada nilai Sinergi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 22) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>memberikan informasi • Salah satu sikap jujur ditunjukkan dengan</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Dalam kerangka nilai integritas, hakikat berbicara jujur pada pelaksanaan tugas kedinasan dimaknai sebagai...", "options": ["Menyampaikan laporan kerja secara ringkas kepada pimpinan", "Memberikan saran konstruktif demi kemajuan seksi pelayanan", "Mengomunikasikan kebijakan instansi secara ramah dan santun", "Berbicara sesuai kenyataan tanpa disembunyikan atau diubah", "Menjelaskan alur prosedur operasional sesuai ketentuan baku"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Hakikat Berbicara Jujur Tanpa Manipulasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Berbicara jujur</strong> adalah berbicara sesuai kenyataan, <strong>tidak ada yang disembunyikan atau dimanipulasi</strong> dalam penyampaian informasi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat karena kejujuran menuntut keterbukaan fakta apa adanya tanpa rekayasa data untuk kepentingan apa pun. Keakuratan informasi yang disampaikan kepada atasan maupun masyarakat menjadi landasan pengambilan keputusan yang tepat. Dengan demikian, berbicara sesuai kenyataan tanpa manipulasi adalah esensi berbicara jujur.</p><p>Jawaban tepat: <strong>D. Berbicara sesuai kenyataan tanpa disembunyikan atau diubah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena penyampaian laporan secara ringkas berkaitan dengan efektivitas tata naskah dinas.</li><li><strong>B</strong>: Kurang tepat karena pemberian saran konstruktif mencerminkan kontribusi positif dalam kerja sama tim.</li><li><strong>C</strong>: Kurang tepat karena komunikasi kebijakan secara ramah mencerminkan etika nilai Berorientasi Pelayanan.</li><li><strong>E</strong>: Kurang tepat karena penjelasan alur prosedur sesuai ketentuan baku berkaitan dengan transparansi loket.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 22) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Berbicara jujur adalah berbicara • tidak ada yang disembunyikan atau dimanipulasi</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Kejujuran harus senantiasa diiringi ketulusan, yang dalam penyampaian laporan kedinasan berarti...", "options": ["Memastikan data realisasi telah diverifikasi secara berjenjang", "Tidak ada maksud lain di balik informasi selain isi informasi itu", "Menjaga keselarasan data antara pembukuan bulanan dan tahunan", "Menyelaraskan informasi teknis dengan pedoman penyusunan laporan", "Menyajikan rincian fakta secara tepat waktu sebelum batas akhir"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Ketulusan yang Mengiringi Kejujuran</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Jujur diikuti oleh sikap ketulusan, yaitu <strong>tidak ada maksud lain di balik informasi yang diberikan kecuali sesuai dengan isi informasi tersebut</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat karena ketulusan menegaskan bahwa pelaporan data dilakukan semata-mata demi kebenaran kedinasan dan bukan sarana pamrih pribadi. Informasi yang bersih dari motif tersembunyi menjamin objektivitas tata kelola birokrasi kementerian. Oleh karena itu, ketiadaan maksud lain selain isi informasi adalah bukti ketulusan.</p><p>Jawaban tepat: <strong>B. Tidak ada maksud lain di balik informasi selain isi informasi itu</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena verifikasi berjenjang merupakan tahapan validasi administratif perbendaharaan.</li><li><strong>C</strong>: Kurang tepat karena keselarasan pembukuan bulanan dan tahunan berfokus pada rekonsiliasi akuntansi.</li><li><strong>D</strong>: Kurang tepat karena penyelarasan informasi dengan pedoman laporan berkaitan dengan ketaatan teknis.</li><li><strong>E</strong>: Kurang tepat karena penyajian rincian fakta secara tepat waktu merupakan wujud kedisiplinan kerja.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 22) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>tidak ada maksud lain di balik informasi • Jujur diikuti oleh</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Memberikan informasi kepada atasan dengan pamrih agar diberikan promosi jabatan dinilai bertentangan dengan...", "options": ["Standar keahlian ideal dalam menjaga citra profesi", "Kecepatan merespons kebutuhan masyarakat pengguna jasa", "Hubungan kemitraan produktif pada nilai kerja sama", "Perbaikan berkelanjutan dalam sistem tata kelola dinas", "Sikap tulus pada perilaku utama pertama nilai integritas"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pamrih Promosi Bertentangan dengan Integritas</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Memberikan informasi penting dengan harapan imbalan promosi <strong>bukanlah sikap berintegritas</strong> karena tidak mencerminkan <strong>sikap tulus</strong> pada perilaku utama pertama integritas.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat karena ketulusan menuntut kejujuran informasi yang murni tanpa pamrih atau kepentingan pamrih terselubung. Informasi kedinasan wajib disampaikan demi kemaslahatan organisasi, bukan sebagai alat tawar keuntungan pribadi pegawai. Dengan demikian, pamrih promosi mencederai sikap tulus pada nilai integritas.</p><p>Jawaban tepat: <strong>E. Sikap tulus pada perilaku utama pertama nilai integritas</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena standar keahlian ideal berfokus pada rujukan kompetensi pada nilai Profesionalisme.</li><li><strong>B</strong>: Kurang tepat karena kecepatan merespons kebutuhan masyarakat berfokus pada nilai Berorientasi Pelayanan.</li><li><strong>C</strong>: Kurang tepat karena hubungan kemitraan produktif merupakan fokus utama pada nilai Sinergi.</li><li><strong>D</strong>: Kurang tepat karena perbaikan berkelanjutan merupakan fokus implementasi dari nilai Kesempurnaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 22) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>bukanlah sikap berintegritas • atasannya akan memberikan imbalan promosi</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Buah karakter utama yang akan terbentuk secara alami dari perpaduan sikap jujur dan tulus adalah...", "options": ["Pribadi yang dapat dipercaya", "Aparatur yang kebal terhadap mutasi", "Pejabat yang disegani karena otoritas", "Pegawai dengan penghasilan tertinggi", "Staf yang paling sering dipuji pimpinan"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pembentukan Pribadi yang Dapat Dipercaya</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dari pembiasaan <strong>sikap jujur dan tulus</strong> dalam bertugas, <strong>akan terbentuk pribadi yang dapat dipercaya</strong> oleh organisasi maupun masyarakat.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat mengenai dampak karakter yang lahir dari kejujuran dan ketulusan. Kepercayaan pimpinan, rekan kerja, dan masyarakat diperoleh dari pembuktian konsistensi moral abdi negara. Prinsip moral menyimpulkan dengan tegas bahwa dari sikap jujur dan tulus itu, akan terbentuk pribadi yang dapat dipercaya. Oleh karena itu, terbentuknya pribadi yang dapat dipercaya adalah buah karakternya.</p><p>Jawaban tepat: <strong>A. Pribadi yang dapat dipercaya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Mutasi pegawai merupakan kebijakan pembinaan karier aparatur di kementerian.</li><li><strong>C</strong>: Rasa hormat yang sehat berakar dari keteladanan budi, bukan otoritas kekuasaan.</li><li><strong>D</strong>: Tingkat penghasilan aparatur negara diatur melalui skema gaji dan tunjangan sah.</li><li><strong>E</strong>: Pujian pimpinan bukan tujuan akhir dari pembentukan karakter integritas diri.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 22) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Dari sikap jujur dan tulus itu • akan terbentuk pribadi yang dapat dipercaya</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Pegawai yang menjaga kehormatan diri dengan mematuhi norma hukum, agama, dan sosial pada hakikatnya telah...", "options": ["Meningkatkan kepuasan pemangku kepentingan atas layanan kantor", "Mewujudkan efisiensi penggunaan sarana dan perlengkapan dinas", "Menjaga kehormatan organisasi tempatnya mengabdi sebagai aparatur", "Memperkuat sinergi kerja sama produktif dengan seksi kerja lain", "Memastikan ketuntasan penyelesaian berkas administrasi kedinasan"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Menjaga Kehormatan Diri dan Organisasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dengan <strong>menjaga kehormatan dirinya</strong> dari hal-hal tercela, seorang pegawai pada hakikatnya <strong>telah menjaga kehormatan organisasi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat karena reputasi kementerian tercermin langsung dari martabat moral yang dijaga oleh setiap individu pegawainya. Kepatuhan aparatur terhadap norma hukum, agama, dan sosial membentengi citra luhur instansi di mata publik. Oleh sebab itu, menjaga kehormatan diri identik dengan menjaga kehormatan institusi.</p><p>Jawaban tepat: <strong>C. Menjaga kehormatan organisasi tempatnya mengabdi sebagai aparatur</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena kepuasan pemangku kepentingan merupakan tolok ukur keberhasilan nilai Pelayanan.</li><li><strong>B</strong>: Kurang tepat karena efisiensi sarana perlengkapan dinas merupakan wujud pengelolaan nilai Akuntabel.</li><li><strong>D</strong>: Kurang tepat karena penguatan sinergi kerja sama produktif merupakan perwujudan dari nilai Sinergi.</li><li><strong>E</strong>: Kurang tepat karena ketuntasan berkas administrasi kedinasan mencerminkan penerapan nilai Profesionalisme.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 22) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>kehormatan dirinya dengan tidak melakukan • telah menjaga kehormatan organisasi</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Penerapan perilaku menjaga martabat pegawai dalam kehidupan kedinasan sehari-hari ditunjukkan melalui...", "options": ["Mengikuti pelatihan teknis kepemimpinan secara terjadwal", "Melaksanakan dan mematuhi sumpah jabatan dengan penuh kesadaran", "Mengembangkan sistem inovasi digital dalam tata kelola berkas", "Menata kearsipan dokumen kepegawaian secara rapi dan sistematis", "Membantu perumusan rencana operasional tahunan satuan kerja dinas"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kepatuhan terhadap Sumpah Jabatan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Aplikasi sehari-hari perilaku menjaga martabat ditunjukkan dalam bentuk <strong>melaksanakan dan mematuhi sumpah jabatan</strong> yang diucapkan saat dilantik dengan penuh kesadaran.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat karena sumpah jabatan memuat janji suci aparatur kepada Tuhan dan negara untuk selalu menjaga kehormatan diri dari hal tercela. Pelaksanaan sumpah secara sadar menjadi benteng moral terkuat dalam menunaikan setiap mandat kedinasan. Dengan demikian, ketaatan pada sumpah jabatan adalah wujud menjaga martabat pegawai.</p><p>Jawaban tepat: <strong>B. Melaksanakan dan mematuhi sumpah jabatan dengan penuh kesadaran</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena pelatihan kepemimpinan berorientasi pada pengembangan kompetensi manajerial SDM.</li><li><strong>C</strong>: Kurang tepat karena pengembangan inovasi digital tata kelola berkas mencerminkan nilai Kesempurnaan.</li><li><strong>D</strong>: Kurang tepat karena penataan kearsipan dokumen secara sistematis mencerminkan kecermatan administrasi.</li><li><strong>E</strong>: Kurang tepat karena perumusan rencana operasional tahunan satker berfokus pada perencanaan kinerja kantor.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 23) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>dan mematuhi sumpah jabatan • melaksanakannya dengan penuh kesadaran</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Salah satu poin penting dalam sumpah jabatan pegawai Kementerian Keuangan terkait larangan gratifikasi adalah...", "options": ["Melaksanakan tugas kedinasan dengan penuh ketelitian dan tanggung jawab", "Menjaga rahasia negara dan rahasia jabatan sebaik-baiknya kepada publik", "Mengutamakan kepentingan negara di atas kepentingan golongan atau kelompok", "Tidak menerima pemberian atau menjanjikan apa pun terkait jabatannya", "Menaati segala ketentuan regulasi kedinasan aparatur sipil negara yang sah"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Larangan Menerima Gratifikasi dalam Sumpah</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Salah satu isi sumpah jabatan pegawai adalah <strong>tidak menerima pemberian atau tidak menjanjikan apapun dari pihak lain yang terkait dengan jabatannya (gratifikasi)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat karena penolakan atas segala bentuk janji atau imbalan jabatan merupakan komitmen anti-gratifikasi yang diikrarkan secara resmi dalam sumpah. Sikap ini memastikan pelayanan birokrasi terbebas dari benturan kepentingan dan intervensi pihak luar. Oleh sebab itu, klausul penolakan pemberian jabatan adalah amanah sumpah pegawai.</p><p>Jawaban tepat: <strong>D. Tidak menerima pemberian atau menjanjikan apa pun terkait jabatannya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena klausul ketelitian dan tanggung jawab berkaitan dengan sumpah ketuntasan kerja.</li><li><strong>B</strong>: Kurang tepat karena penjagaan rahasia jabatan merupakan butir sumpah terkait perlindungan data dinas.</li><li><strong>C</strong>: Kurang tepat karena pengutamaan kepentingan negara merupakan klausul komitmen kesetiaan kebangsaan.</li><li><strong>E</strong>: Kurang tepat karena kepatuhan pada regulasi yang sah merupakan klausul ketaatan hukum umum aparatur.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 23) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pemberian atau tidak menjanjikan apapun • terkait dengan</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Dalam etika kedinasan, aparatur yang taat pada norma agama saat menjalankan tugas akan terdorong untuk...", "options": ["Membantu mengatasi kesulitan orang lain dalam lingkungan kerja", "Meningkatkan kemahiran teknis profesional secara berkesinambungan", "Menuntaskan berkas administrasi sebelum batas waktu yang ditetapkan", "Mengembangkan gagasan inovatif bagi penyempurnaan layanan publik", "Menjaga kerahasiaan data kedinasan sesuai standar operasional dinas"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Ketaatan Agama Membantu Kesulitan Sesama</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Menaati aturan agama termasuk bentuk menjaga kehormatan diri, di mana <strong>orang-orang yang taat pada agama akan membantu mengatasi kesulitan orang lain</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat karena keimanan dan ketaatan moral menggerakkan kepedulian sosial aparatur untuk saling meringankan beban kerja sesama rekan kerja. Nilai-nilai kebajikan agama mewujud dalam tindakan nyata tolong-menolong dalam mengatasi kendala operasional dinas. Dengan demikian, membantu kesulitan orang lain adalah buah dari ketaatan beragama.</p><p>Jawaban tepat: <strong>A. Membantu mengatasi kesulitan orang lain dalam lingkungan kerja</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kurang tepat karena peningkatan kemahiran teknis merupakan dorongan pengembangan nilai Profesionalisme.</li><li><strong>C</strong>: Kurang tepat karena penuntasan berkas administrasi tepat waktu berorientasi pada kedisiplinan kerja.</li><li><strong>D</strong>: Kurang tepat karena pengembangan gagasan inovatif layanan publik merupakan penerapan nilai Kesempurnaan.</li><li><strong>E</strong>: Kurang tepat karena penjagaan kerahasiaan data kedinasan merupakan penerapan kepatuhan kode etik Loyal.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 23) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Menaati aturan agama juga termasuk • mengatasi kesulitan orang lain</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Salah satu bentuk nyata pengamalan perilaku menjaga martabat pegawai di lingkungan kerja adalah...", "options": ["Menyusun standar prosedur layanan secara terkoordinasi", "Mengoptimalkan pemanfaatan sistem jaringan informasi dinas", "Membangun hubungan kemitraan strategis dengan instansi mitra", "Melakukan evaluasi berkala terhadap beban kerja seksi kantor", "Menaati kode etik dan perilaku yang sudah ditetapkan kementerian"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kepatuhan Kode Etik sebagai Panduan Bekerja</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Aplikasi lain dari perilaku utama kedua nilai integritas adalah <strong>menaati kode etik dan perilaku yang sudah ditetapkan</strong> oleh Kementerian Keuangan sebagai panduan bekerja.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat karena kode etik merumuskan batasan normatif mengenai kepatutan sikap, tutur kata, dan tindakan aparatur dalam bertugas. Kepatuhan terhadap pedoman ini menjaga keluhuran korps Kementerian Keuangan dari pelanggaran norma kerja. Oleh karena itu, ketaatan pada kode etik merupakan wujud menjaga martabat kedinasan.</p><p>Jawaban tepat: <strong>E. Menaati kode etik dan perilaku yang sudah ditetapkan kementerian</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena penyusunan standar prosedur layanan berfokus pada standardisasi operasional tugas.</li><li><strong>B</strong>: Kurang tepat karena pemanfaatan jaringan informasi dinas berkaitan dengan penguasaan teknologi kerja.</li><li><strong>C</strong>: Kurang tepat karena hubungan kemitraan strategis dengan mitra luar mencerminkan nilai Sinergi.</li><li><strong>D</strong>: Kurang tepat karena evaluasi beban kerja seksi kantor berkaitan dengan penataan efisiensi organisasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 23) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>kode etik dan perilaku yang sudah ditetapkan • sebagai panduan dalam bekerja</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Secara mendasar, profesionalisme didefinisikan sebagai sifat-sifat yang sewajarnya dimiliki seorang profesional, yaitu...", "options": ["Tingkat kepangkatan struktural tertinggi dalam formasi jabatan", "Kecepatan beradaptasi dengan dinamika perubahan regulasi dinas", "Kemampuan dan kemahiran dalam pelaksanaan kegiatan tertentu", "Keluasan wewenang dalam pengelolaan alokasi keuangan satker", "Kelengkapan sertifikasi keahlian dari institusi profesi mitra"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengertian Dasar Profesionalisme</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Profesionalisme</strong> adalah sifat-sifat yang sewajarnya dimiliki atau dilakukan oleh seorang profesional, berupa <strong>kemampuan dan kemahiran dalam pelaksanaan kegiatan tertentu</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat karena kepakaran teknis dan keterampilan mumpuni menjadi esensi pembeda dari sosok profesional dalam menjalankan profesinya. Penguasaan bidang kerja yang mendalam memastikan setiap penugasan dapat diselesaikan secara presisi dan tepat sasaran. Dengan demikian, kemampuan dan kemahiran teknis merupakan hakikat dasar profesionalisme.</p><p>Jawaban tepat: <strong>C. Kemampuan dan kemahiran dalam pelaksanaan kegiatan tertentu</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena kepangkatan struktural merupakan tingkatan hierarki dalam tata birokrasi kepegawaian.</li><li><strong>B</strong>: Kurang tepat karena adaptasi terhadap regulasi baru mencerminkan kelincahan dari nilai Adaptif.</li><li><strong>D</strong>: Kurang tepat karena keluasan wewenang alokasi keuangan merupakan mandat jabatan pada unit perbendaharaan.</li><li><strong>E</strong>: Kurang tepat karena kelengkapan dokumen sertifikasi merupakan bukti formal pengakuan kompetensi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 23) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Profesionalisme adalah sifat-sifat yang sewajarnya dimiliki • kemampuan dan kemahiran dalam pelaksanaan</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Menurut Kamus Besar Bahasa Indonesia (KBBI), makna profesionalisme berkaitan dengan profesi yang memerlukan...", "options": ["Pengalaman penugasan lapangan yang panjang dalam birokrasi", "Kepandaian khusus untuk menjalankan profesi bersangkutan", "Ketersediaan instrumen evaluasi kinerja pegawai yang terpadu", "Kemampuan koordinasi program lintas satuan kerja kementerian", "Keterampilan memimpin tim perumus kebijakan anggaran dinas"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Profesionalisme Menurut KBBI</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Menurut KBBI, makna profesionalisme berhubungan dengan profesi dan <strong>memerlukan kepandaian khusus untuk menjalankannya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat karena profesi menuntut standar ilmu pengetahuan dan kecakapan spesifik yang tidak dapat dilakukan secara sembarangan oleh orang awam. Penguasaan kepandaian khusus inilah yang menjamin integritas mutu dan keandalan layanan kedinasan. Oleh sebab itu, kebutuhan kepandaian khusus menjadi unsur definisi KBBI.</p><p>Jawaban tepat: <strong>B. Kepandaian khusus untuk menjalankan profesi bersangkutan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena lamanya pengalaman lapangan mencerminkan jam terbang pengabdian seorang pegawai.</li><li><strong>C</strong>: Kurang tepat karena instrumen evaluasi terpadu merupakan sarana pengukuran kinerja manajemen ASN.</li><li><strong>D</strong>: Kurang tepat karena koordinasi program lintas satuan kerja mencerminkan kepiawaian dalam nilai Sinergi.</li><li><strong>E</strong>: Kurang tepat karena kepemimpinan perumusan anggaran mencerminkan keahlian manajerial perbendaharaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 23) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Menurut KBBI, makna profesionalisme berhubungan dengan • memerlukan</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Dalam dimensi kualitas profesionalisme, konsep keahlian ideal dirumuskan sebagai...", "options": ["Standar kualifikasi teknis tingkat madya pada jabatan fungsional", "Target capaian output tertinggi dalam kontrak sasaran kinerja pegawai", "Penguasaan kompetensi manajerial berbasis kurikulum pendidikan dinas", "Suatu perangkat perilaku yang dipandang paling sempurna sebagai rujukan", "Wewenang otoritatif pejabat dalam menetapkan keputusan strategis unit"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Konsep Keahlian Ideal sebagai Rujukan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Yang dimaksud dengan <strong>keahlian ideal</strong> ialah <strong>suatu perangkat perilaku yang dipandang paling sempurna dan dijadikan sebagai rujukan</strong> dalam bekerja.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat karena keahlian ideal berfungsi sebagai tolok ukur keteladanan tertinggi yang senantiasa diupayakan pencapaiannya oleh aparatur profesional. Figur profesional akan terus mematutkan kompetensinya mendekati standar perilaku unggul tersebut dalam tugas hariannya. Dengan demikian, perangkat perilaku sempurna sebagai rujukan adalah makna keahlian ideal.</p><p>Jawaban tepat: <strong>D. Suatu perangkat perilaku yang dipandang paling sempurna sebagai rujukan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena standar kualifikasi fungsional merupakan persyaratan administratif jenjang jabatan.</li><li><strong>B</strong>: Kurang tepat karena target capaian output tertinggi merupakan target kinerja kuantitatif pegawai.</li><li><strong>C</strong>: Kurang tepat karena kompetensi manajerial berbasis kurikulum merupakan hasil diklat kepemimpinan.</li><li><strong>E</strong>: Kurang tepat karena wewenang penetapan keputusan strategis merupakan hak diskresi pimpinan satker.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 23) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>keahlian ideal • yang dipandang paling sempurna</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Aparatur yang menjaga citra luhur korps melalui kerapian busana, kesantunan bertutur, dan etika bergaul sedang...", "options": ["Meningkatkan dan memelihara citra positif profesi aparatur", "Mengoptimalkan efisiensi alur administrasi pelayanan berkas", "Membangun jejaring komunikasi koordinatif lintas seksi kerja", "Mempercepat penyelesaian target sasaran kinerja pegawai dinas", "Mengembangkan standar kompetensi teknis pelayanan prima kantor"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Perwujudan Memelihara Citra Profesi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Profesionalisme yang tinggi ditunjukkan oleh besarnya keinginan untuk selalu <strong>meningkatkan dan memelihara image profesi melalui perwujudan perilaku profesional</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat karena sikap hidup sehari-hari, kesantunan berbahasa, serta penampilan yang tertib mencerminkan kehormatan institusi di ruang publik. Keteladanan lahiriah ini memperkuat kepercayaan masyarakat terhadap kewibawaan birokrasi Kementerian Keuangan. Oleh sebab itu, pemeliharaan citra profesi terwujud melalui perilaku santun pegawai.</p><p>Jawaban tepat: <strong>A. Meningkatkan dan memelihara citra positif profesi aparatur</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kurang tepat karena efisiensi alur administrasi pelayanan berkas berfokus pada kecepatan teknis layanan.</li><li><strong>C</strong>: Kurang tepat karena komunikasi koordinatif lintas seksi berorientasi pada penguatan nilai Sinergi.</li><li><strong>D</strong>: Kurang tepat karena percepatan target sasaran kinerja berfokus pada ketuntasan kontrak kerja individu.</li><li><strong>E</strong>: Kurang tepat karena pengembangan standar kompetensi teknis berfokus pada perumusan silabus diklat SDM.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 23) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>memelihara image profesi melalui perwujudan • penampilan, cara percakapan, penggunaan bahasa</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Seorang pegawai yang secara proaktif mengikuti kursus keahlian mandiri demi meningkatkan kapasitas diri sedang...", "options": ["Memperluas jejaring kemitraan kerja dengan pemangku kebijakan", "Mempercepat penyelesaian penugasan rutin operasional kedinasan", "Memastikan ketaatan prosedur pada setiap tahapan layanan publik", "Mengoptimalkan koordinasi internal antar-subbagian di lingkungan satker", "Mengejar kesempatan pengembangan profesional demi perbaikan kualitas diri"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Mengejar Pengembangan Profesional Berkelanjutan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Seorang profesional ingin senantiasa <strong>mengejar kesempatan pengembangan profesional yang dapat meningkatkan dan memperbaiki kualitas pengetahuan dan keterampilannya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat karena semangat belajar mandiri membuktikan komitmen pegawai untuk senantiasa memperluas kompetensi demi memberikan kontribusi terbaik. Pengetahuan yang terus dimutakhirkan menghindarkan organisasi dari kejenuhan metode kerja konvensional. Dengan demikian, pengejaran kesempatan pengembangan profesional adalah ciri aparatur unggul.</p><p>Jawaban tepat: <strong>E. Mengejar kesempatan pengembangan profesional demi perbaikan kualitas diri</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena memperluas kemitraan kerja dengan pemangku kebijakan berorientasi pada nilai Sinergi.</li><li><strong>B</strong>: Kurang tepat karena penyelesaian penugasan rutin operasional mencerminkan efisiensi kerja harian.</li><li><strong>C</strong>: Kurang tepat karena kepatuhan prosedur pada tahapan layanan publik merupakan cerminan nilai Akuntabel.</li><li><strong>D</strong>: Kurang tepat karena koordinasi internal antar-subbagian mencerminkan keterpaduan hubungan kerja satker.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 23) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>profesional yang dapat • kualitas pengetahuan</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C4 - Analisis", "question": "Berikut ini merupakan ciri-ciri kualitas profesionalisme seseorang dalam standar keprofesian aparatur, KECUALI...", "options": ["Menampilkan perilaku yang mendekati keahlian ideal", "Meningkatkan dan memelihara citra luhur profesi", "Memprioritaskan kepentingan unit di atas tujuan bersama", "Mengejar kesempatan pengembangan kapasitas diri", "Mendorong diri mewujudkan kinerja yang profesional"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Ciri Kualitas Profesionalisme Seseorang</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Ciri kualitas profesionalisme seseorang meliputi: <strong>mendorong diri mewujudkan kerja profesional</strong>, <strong>menampilkan perilaku mendekati keahlian ideal</strong>, <strong>meningkatkan dan memelihara image profesi</strong>, serta <strong>mengejar kesempatan pengembangan profesional</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat untuk soal pengecualian karena memprioritaskan unit kerja sendiri merupakan bentuk eksklusivitas kelompok yang bertentangan dengan sinergi kementerian. Adapun opsi A, B, D, dan E merupakan ciri asli kualitas profesionalisme yang diuraikan secara eksplisit di modul. Oleh karena itu, opsi C adalah pernyataan yang bukan merupakan ciri profesionalisme.</p><p>Jawaban tepat: <strong>C. Memprioritaskan kepentingan unit di atas tujuan bersama</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena menampilkan perilaku mendekati keahlian ideal adalah ciri resmi profesionalisme.</li><li><strong>B</strong>: Kurang tepat karena memelihara citra profesi merupakan ciri penting etika seorang profesional.</li><li><strong>D</strong>: Kurang tepat karena mengejar pengembangan profesional merupakan ciri peningkatan mutu diri aparatur.</li><li><strong>E</strong>: Kurang tepat karena dorongan mewujudkan kerja profesional merupakan fondasi utama jiwa keprofesian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 23) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Kualitas profesionalisme memiliki ciri-ciri • mendekati keahlian</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Kualitas profesionalisme seorang pegawai terhadap penugasan kedinasan ditandai secara mendalam oleh...", "options": ["Keterampilan teknis dalam mengoperasikan aplikasi teknologi", "Kecepatan menyelesaikan dokumen transaksi anggaran kedinasan", "Keaktifan menyampaikan masukan solutif dalam forum rapat dinas", "Kemampuan memimpin tim kerja penyusun program strategis kantor", "Kualitas rasa bangga dan percaya diri akan profesi yang diemban"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kebanggaan dan Kepercayaan Diri Profesi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Profesionalisme ditandai dengan <strong>kualitas rasa bangga akan profesi yang dipegangnya</strong> sehingga memiliki rasa percaya diri yang tinggi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat karena rasa bangga atas profesi menumbuhkan harga diri positif dan dedikasi total dalam melayani negara. Pegawai yang percaya diri dan mencintai bidang tugasnya akan selalu terdorong memberikan hasil kerja dengan standar kualitas tertinggi. Dengan demikian, rasa bangga dan percaya diri profesi menjadi penanda utama profesionalisme.</p><p>Jawaban tepat: <strong>E. Kualitas rasa bangga dan percaya diri akan profesi yang diemban</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena keterampilan teknis aplikasi merupakan salah satu instrumen operasional kerja digital.</li><li><strong>B</strong>: Kurang tepat karena kecepatan dokumen anggaran mencerminkan ketangkasan administratif perbendaharaan.</li><li><strong>C</strong>: Kurang tepat karena keaktifan menyampaikan masukan dalam rapat mencerminkan kontribusi pada nilai Sinergi.</li><li><strong>D</strong>: Kurang tepat karena memimpin tim program strategis mencerminkan kapasitas kepemimpinan manajerial.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 24) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>kualitas rasa bangga akan profesi • memiliki rasa bangga dan</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Dalam Nilai Kementerian Keuangan, profesionalisme dinyatakan secara eksplisit dengan...", "options": ["Menjaga martabat dan keluhuran norma", "Membangun kemitraan kerja produktif", "Bekerja tuntas atas kompetensi terbaik", "Memberikan pelayanan prima transparan", "Melakukan perbaikan secara kontinu"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pernyataan Nilai Profesionalisme Kemenkeu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Profesionalisme dalam nilai Kementerian Keuangan dinyatakan dengan bekerja tuntas dan akurat atas dasar kompetensi terbaik dengan penuh tanggung jawab dan komitmen yang tinggi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat sesuai rumusan pernyataan nilai Profesionalisme dalam prinsip etika. Pernyataan ini menegaskan bahwa setiap output kerja insan Kementerian Keuangan harus akurat dan tuntas berbasis kompetensi unggul. Rumusan lengkap nilai Profesionalisme berintikan bekerja tuntas dan akurat atas dasar kompetensi terbaik. Dengan demikian, opsi C merupakan pernyataan nilai yang tepat.</p><p>Jawaban tepat: <strong>C. Bekerja tuntas atas kompetensi terbaik</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan butir perilaku utama kedua dari nilai Integritas.</li><li><strong>B</strong>: Merupakan rumusan pernyataan makna dari nilai Sinergi.</li><li><strong>D</strong>: Merupakan rumusan pernyataan makna dari nilai Pelayanan.</li><li><strong>E</strong>: Merupakan rumusan pernyataan makna dari nilai Kesempurnaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 24) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Profesionalisme dalam nilai Kementerian Keuangan dinyatakan dengan • dan akurat atas dasar kompetensi</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Pegawai Kementerian Keuangan dituntut mempunyai keahlian dan pengetahuan luas karena tugas organisasi...", "options": ["Sangat luas dan kompleks", "Mencakup penegakan hukum militer", "Mengatur tata kelola politik luar negeri", "Menaungi seluruh entitas perbankan dunia", "Menetapkan regulasi moneter perbankan"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Alasan Tuntutan Keahlian dan Pengetahuan Luas</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai Kementerian Keuangan dituntut memiliki keahlian dan pengetahuan luas karena <strong>tugas pokok dan fungsi organisasi Kementerian Keuangan sangat luas dan kompleks</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat mengenai alasan mendasar di balik tuntutan wawasan luas bagi pegawai. Mengelola kebijakan fiskal, perbendaharaan, bea cukai, pajak, dan kekayaan negara memerlukan pemahaman multi-disiplin yang mendalam. Pedoman kerja menegaskan bahwa tugas pokok dan fungsi organisasi Kementerian Keuangan sangat luas dan kompleks; oleh karenanya seluruh pegawai dituntut mempunyai keahlian dan pengetahuan yang luas. Oleh sebab itu, opsi A adalah alasannya.</p><p>Jawaban tepat: <strong>A. Sangat luas dan kompleks</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Penegakan hukum militer merupakan ranah pertahanan dan kepolisian negara.</li><li><strong>C</strong>: Pengaturan politik luar negeri dinaungi oleh Kementerian Luar Negeri.</li><li><strong>D</strong>: Kementerian Keuangan mengelola fiskal negara, bukan perbankan dunia.</li><li><strong>E</strong>: Regulasi moneter perbankan merupakan wewenang bank sentral.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 24) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Tugas pokok dan fungsi organisasi Kementerian Keuangan • sangat luas dan</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Dalam aplikasi sehari-hari, perwujudan dari perilaku utama mempunyai keahlian dan pengetahuan yang luas adalah...", "options": ["Bersikap jujur dan tulus dalam menyajikan data laporan", "Membangun hubungan kemitraan yang produktif dan harmonis", "Melayani secara transparan, tepat waktu, serta akuntabel", "Bekerja secara efisien dan menghasilkan output berkualitas", "Melakukan perbaikan tata kelola secara berkesinambungan"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Bekerja Efisien dan Berkualitas</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam aplikasi sehari-hari, perilaku utama pertama nilai profesionalisme adalah <strong>bekerja dengan efisien dan berkualitas</strong> melalui dukungan keahlian dan pengetahuan luas.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat karena efisiensi kerja dan keunggulan mutu output hanya dapat dicapai apabila pegawai memahami ilmunya secara mendalam. Tanpa pengetahuan luas, pekerjaan akan memakan waktu lama dan rentan terjadi kesalahan fatal dalam eksekusi. Oleh sebab itu, bekerja efisien dan berkualitas adalah wujud nyata perilaku utama tersebut.</p><p>Jawaban tepat: <strong>D. Bekerja secara efisien dan menghasilkan output berkualitas</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena bersikap jujur dan tulus dalam menyajikan data merupakan perilaku nilai Integritas.</li><li><strong>B</strong>: Kurang tepat karena membangun hubungan kemitraan produktif merupakan perilaku utama pada nilai Sinergi.</li><li><strong>C</strong>: Kurang tepat karena melayani secara transparan dan tepat waktu merupakan perilaku utama nilai Pelayanan.</li><li><strong>E</strong>: Kurang tepat karena melakukan perbaikan berkesinambungan merupakan perilaku utama nilai Kesempurnaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 24) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>aplikasi sehari-hari, perilaku • bekerja dengan efisien dan berkualitas</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Penggantian proses bisnis manual menjadi berbasis teknologi informasi di Kementerian Keuangan dicontohkan melalui penerapan...", "options": ["Pemberlakuan absensi kertas manual bertanda tangan basah", "E-registration, penyerahan softfile, dan administrasi data", "Pengiriman berkas fisik dokumen dinas melalui jasa pos darat", "Penyimpanan arsip formulir pajak pada gudang penyimpanan fisik", "Pembayaran setoran penerimaan negara secara tunai di kantor"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penerapan Teknologi Informasi dalam Proses Bisnis</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pengembangan teknologi informasi dicontohkan melalui pemanfaatan internet dan intranet untuk <strong><em>e-registration</em>, penyerahan dokumen kerja dalam bentuk <em>softfile</em>, serta administrasi data</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat sesuai contoh modernisasi teknologi informasi yang dipaparkan dalam ketentuan kerja. Digitalisasi proses bisnis memudahkan pengguna layanan menyelesaikan urusan perpajakan dan perbendaharaan secara cepat dan praktis. Dalam implementasi digital dicatat bahwa sebagai contoh, dengan jaringan internet atau intranet, dapat dilakukan e-registration, penyerahan dokumen kerja dalam bentuk softfile, serta administrasi data. Oleh karena itu, opsi B memuat contoh yang tepat.</p><p>Jawaban tepat: <strong>B. E-registration, penyerahan softfile, dan administrasi data</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Presensi aparatur kini telah menggunakan sistem digital elektronik terintegrasi.</li><li><strong>C</strong>: Pengiriman dokumen fisik antarunit kini digantikan oleh naskah dinas elektronik.</li><li><strong>D</strong>: Pengarsipan formulir perpajakan telah beralih ke basis data komputasi awan.</li><li><strong>E</strong>: Penerimaan kas negara dilakukan secara digital melalui sistem kas negara online.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 24) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>e-registration, penyerahan dokumen kerja • softfile, serta administrasi data</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Dalam prinsip bekerja dengan hati, seorang aparatur dituntut mampu mempertemukan dua simpul berlawanan yaitu...", "options": ["Rencana kerja tahunan dan dinamika penugasan insidental pimpinan", "Target penerimaan negara dan kemudahan fasilitas perizinan usaha", "Ketentuan disiplin aparatur dan pemenuhan hak pengembangan karier", "Standar kompetensi teknis dan kebutuhan penempatan pegawai di daerah", "Ketentuan standar operasional prosedur dengan kepekaan hati nurani"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Mempertemukan Simpul SOP dan Hati Nurani</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam bekerja terdapat dua hal penting yang harus diperhatikan yaitu <strong>SOP dan hati nurani atau perasaan</strong>. Seorang profesional mampu mempertemukan dua simpul berlawanan tersebut.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat karena kepatuhan kaku pada prosedur tanpa empati dapat mengurangi kualitas pelayanan kemanusiaan, sebaliknya mengabaikan prosedur demi rasa iba melanggar hukum. Profesionalisme sejati terletak pada kebijaksanaan mempertemukan ketertiban SOP dengan ketulusan nurani secara berimbang. Dengan demikian, perpaduan SOP dan hati nurani adalah dua simpul yang dipertemukan.</p><p>Jawaban tepat: <strong>E. Ketentuan standar operasional prosedur dengan kepekaan hati nurani</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena rencana kerja dan tugas insidental merupakan tantangan manajemen waktu pegawai.</li><li><strong>B</strong>: Kurang tepat karena target fiskal dan perizinan usaha merupakan aspek kebijakan makro ekonomi negara.</li><li><strong>C</strong>: Kurang tepat karena disiplin aparatur dan hak karier merupakan hak dan kewajiban dalam regulasi ASN.</li><li><strong>D</strong>: Kurang tepat karena standar kompetensi dan penempatan kerja berkaitan dengan pola mutasi SDM.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 24) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>mampu mempertemukan dua • SOP dan hati nurani atau</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Secara konseptual, ungkapan lain yang semakna dan menjadi hakikat dari bekerja dengan hati adalah...", "options": ["Menuntaskan pekerjaan tepat waktu sesuai target kinerja dinas", "Menjaga integritas moral dan kode etik aparatur sipil negara", "Mencintai pekerjaan yang menjadi amanah tanggung jawab kedinasan", "Mematuhi prosedur standar operasional secara cermat dan teliti", "Menguasai perkembangan teknologi informasi penunjang tugas kantor"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Makna Bekerja dengan Hati</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kata lain dari bekerja dengan hati adalah <strong>mencintai pekerjaan kita</strong> sehingga tidak ada tugas yang dikerjakan dengan keterpaksaan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat karena kecintaan tulus terhadap profesi melahirkan antusiasme, kebahagiaan batin, dan dedikasi maksimal dalam setiap penugasan. Ketika seseorang mencintai pekerjaannya, ia tidak merasa terbebani melainkan memandang tugas sebagai bentuk aktualisasi pengabdian. Oleh karena itu, mencintai pekerjaan adalah hakikat dari bekerja dengan hati.</p><p>Jawaban tepat: <strong>C. Mencintai pekerjaan yang menjadi amanah tanggung jawab kedinasan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena menuntaskan tugas tepat waktu merupakan wujud kedisiplinan pada target kinerja.</li><li><strong>B</strong>: Kurang tepat karena menjaga kode etik kedinasan merupakan penerapan dari pilar nilai Integritas.</li><li><strong>D</strong>: Kurang tepat karena kepatuhan cermat pada SOP merupakan cerminan ketelitian nilai Akuntabel.</li><li><strong>E</strong>: Kurang tepat karena penguasaan teknologi informasi merupakan wujud keahlian pada nilai Profesionalisme.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 24) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Kata lain bekerja dengan hati adalah • pekerjaan kita</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Seorang aparatur meyakini bahwa bekerja di lingkungan Kementerian Keuangan bukan sekadar mencari nafkah, melainkan...", "options": ["Panggilan jiwa dalam mengabdi", "Amanah konstitusi kedinasan", "Komitmen profesi aparatur", "Tanggung jawab institusi kerja", "Dedikasi pengabdian negara"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Bekerja karena Panggilan Jiwa</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Bekerja bukan hanya karena dibayar tinggi, <strong>tetapi karena panggilan jiwa</strong> yang mendorong aparatur bekerja melampaui standar minimal.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat karena makna luhur bekerja sebagai panggilan jiwa menempatkan profesi di atas orientasi materi semata. Semangat pengabdian ini mendorong pegawai memberikan karya terbaik melebihi apa yang diterima sebagai hak kedinasan. Dengan demikian, panggilan jiwa merupakan filosofi tertinggi dalam bekerja dengan hati.</p><p>Jawaban tepat: <strong>A. Panggilan jiwa dalam mengabdi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kurang tepat karena amanah konstitusi kedinasan berfokus pada mandat hukum penyelenggaraan negara.</li><li><strong>C</strong>: Kurang tepat karena komitmen profesi aparatur berkaitan dengan kode etik dan standar profesi kerja.</li><li><strong>D</strong>: Kurang tepat karena tanggung jawab institusi kerja mencerminkan kewajiban organisasi kepada publik.</li><li><strong>E</strong>: Kurang tepat karena dedikasi pengabdian negara merupakan prinsip kesetiaan umum pada nilai Loyal.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 24) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Bekerja bukan hanya karena dibayar tinggi • karena panggilan jiwa</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Ungkapan bijak yang mendasari filosofi bekerja dengan hati berbunyi: 'Bekerjalah Anda melebihi apa yang Anda terima, maka Anda akan...'", "options": ["Mendapat kemudahan promosi jabatan dinas", "Memperoleh apresiasi khusus dari pimpinan", "Diberikan layanan cepat oleh rekan kantor", "Menerima melebihi apa yang Anda kerjakan", "Mencapai kepuasan materiil yang diharapkan"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Filosofi Memberi Kontribusi Melampaui Imbalan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Filosofi bekerja dengan hati ditegaskan oleh ungkapan: <strong>'bekerjalah Anda melebihi apa yang Anda terima, maka Anda akan menerima melebihi apa yang Anda kerjakan'</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat sesuai ungkapan mutiara yang menutup pembahasan nilai Profesionalisme dalam etika kedinasan. Dedikasi yang melampaui perhitungan materiil akan membuahkan berkah, penghargaan, dan kepuasan batin yang jauh lebih besar di masa depan. Dalam etos kerja tertulis bahwa sebuah ungkapan mengatakan, 'bekerjalah Anda melebihi apa yang Anda terima, maka Anda akan menerima melebihi apa yang Anda kerjakan; hal ini tidak akan pernah terjadi kalau kita tidak bekerja dengan hati.' Oleh karena itu, opsi D merupakan kelanjutan ungkapan tersebut.</p><p>Jawaban tepat: <strong>D. Menerima melebihi apa yang Anda kerjakan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kemudahan promosi bukan esensi filosofis ungkapan bekerja dengan hati.</li><li><strong>B</strong>: Apresiasi pimpinan adalah dampak luar, bukan pesan moral ungkapan tersebut.</li><li><strong>C</strong>: Bukan merupakan rumusan prinsip dedikasi kerja yang dianut.</li><li><strong>E</strong>: Kepuasan batiniah melampaui pencapaian materiil semata dalam etika pengabdian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 24) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>bekerjalah Anda melebihi apa • maka Anda akan menerima melebihi apa yang Anda kerjakan</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C4 - Analisis", "question": "Perwujudan nyata dari prinsip bekerja dengan hati dalam pelaksanaan tugas operasional ditunjukkan oleh sikap...", "options": ["Memperluas keahlian teknis melalui penguasaan aplikasi digital", "Mampu mempertemukan simpul SOP kedinasan dengan nurani kemanusiaan", "Menyampaikan informasi realisasi belanja sesuai fakta transaksi", "Menjalin kerja sama produktif dan harmonis dengan rekan sejawat", "Memberikan layanan prima secara cepat, transparan, dan akuntabel"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sikap Menolak Bekerja Asal-Asalan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Salah satu ciri seorang profesional dalam <strong>bekerja dengan hati</strong> adalah <strong>mampu mempertemukan dua simpul yang berlawanan</strong> yaitu antara <strong>SOP dan hati nurani atau perasaan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat karena bekerja dengan hati menolak sikap kaku yang mengabaikan nurani, sekaligus menolak pengabaian aturan operasional yang dapat merugikan negara. Keseimbangan antara ketertiban regulasi dan empati nurani membuktikan kematangan profesionalitas aparatur dalam menjalankan tugas. Oleh karena itu, mempertemukan simpul SOP dan nurani adalah perwujudan bekerja dengan hati.</p><p>Jawaban tepat: <strong>B. Mampu mempertemukan simpul SOP kedinasan dengan nurani kemanusiaan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena memperluas keahlian teknis aplikasi merupakan perilaku mempunyai keahlian luas.</li><li><strong>C</strong>: Kurang tepat karena menyampaikan informasi sesuai fakta transaksi merupakan cerminan nilai Integritas.</li><li><strong>D</strong>: Kurang tepat karena menjalin kerja sama produktif dengan rekan kerja merupakan cerminan nilai Sinergi.</li><li><strong>E</strong>: Kurang tepat karena memberikan layanan cepat, transparan, dan akuntabel merupakan cerminan nilai Pelayanan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 24) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>sekenanya, yang penting selesai • bekerja dengan hati</strong></div></div>"}];
let current=0;const answers=new Array(questions.length).fill(null);const letters=["A","B","C","D","E"];let seconds=65*60;let submitted=false;


// Anotasi hanya dalam pembahasan. Satu set coretan untuk setiap nomor soal.
const ANNO_KEY='stangolden_kemenkeu_p02_review_annot_v1_user_<?= (int)$USER->id ?>';
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
 try{localStorage.setItem('stg_nk_p02_map_visible',show?'1':'0')}catch(e){}
}
try{if(localStorage.getItem('stg_nk_p02_map_visible')==='0')toggleQuestionMap(false)}catch(e){}
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

  if(modalTitle) modalTitle.textContent="🏆 Lihat Ranking Paket 02";
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
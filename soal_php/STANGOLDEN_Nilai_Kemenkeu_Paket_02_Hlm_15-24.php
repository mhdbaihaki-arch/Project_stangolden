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
const questions=[{"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Dengan hadirnya budaya organisasi, para pegawai dan pelaku organisasi akan terbantu untuk...", "options": ["Menghitung rincian alokasi belanja operasional", "Mengetahui atau membaca situasi yang sedang terjadi", "Menyusun regulasi kenaikan pangkat secara mandiri", "Menetapkan standar kelulusan seleksi kepegawaian", "Mengawasi ketaatan pelaporan perpajakan masyarakat"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Peran Budaya Organisasi dalam Membaca Situasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dengan hadirnya <strong>budaya organisasi</strong>, para pegawai dan pelaku organisasi akan terbantu untuk <strong>mengetahui atau membaca situasi yang sedang terjadi</strong> di sebuah organisasi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat sesuai peran awal budaya organisasi di tempat kerja. Keberadaan budaya memberikan pedoman kontekstual bagi aparatur dalam mencermati dinamika kerja harian. Hadirnya budaya organisasi secara nyata membantu para pegawai dan pelaku organisasi mengetahui atau membaca situasi yang sedang terjadi. Oleh sebab itu, kemampuan membaca situasi kerja menjadi manfaat mendasar budaya organisasi.</p><p>Jawaban tepat: <strong>B. Mengetahui atau membaca situasi yang sedang terjadi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan tugas teknis pengelolaan anggaran dinas pada unit keuangan.</li><li><strong>C</strong>: Merupakan wewenang formasi kepegawaian pada regulasi aparatur negara.</li><li><strong>D</strong>: Merupakan kewenangan panitia pelaksana ujian dan rekrutmen pegawai.</li><li><strong>E</strong>: Merupakan fungsi pengawasan penerimaan negara pada unit perpajakan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 15) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Budaya organisasi memiliki peran yang besar • membaca situasi yang sedang terjadi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Melalui kehadiran budaya organisasi yang hidup, organisasi dapat mengenali karakteristik pegawai mengenai...", "options": ["Jenjang pendidikan formal yang pernah ditempuh", "Besaran tunjangan kinerja bulanan yang diperoleh", "Urutan daftar riwayat kepangkatan kepegawaian", "Cara mereka menyelesaikan permasalahan yang ada", "Lama masa pengabdian dinas di kementerian"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengenalan Karakteristik Pegawai</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Melalui budaya organisasi, kita dapat mengetahui <strong>karakteristik pegawai</strong>, termasuk <strong>cara mereka menyelesaikan permasalahan yang ada</strong>, apa yang disukai, dan apa yang tidak disukai.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat mengenai karakteristik pegawai yang tecermin dari budaya organisasi. Budaya membentuk kebiasaan bertindak para anggota saat berhadapan dengan kendala pekerjaan. Secara konseptual ditegaskan bahwa dari budaya organisasi kita bisa mengetahui karakteristik pegawai, terutama cara mereka menyelesaikan permasalahan yang ada. Dengan demikian, pendekatan pemecahan masalah mencerminkan karakteristik pegawai tersebut.</p><p>Jawaban tepat: <strong>D. Cara mereka menyelesaikan permasalahan yang ada</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan data kualifikasi akademis pada berkas administrasi pegawai.</li><li><strong>B</strong>: Merupakan hak remunerasi aparatur berbasis capaian kinerja bulanan.</li><li><strong>C</strong>: Merupakan urutan kepangkatan formal dalam database kepegawaian instansi.</li><li><strong>E</strong>: Merupakan masa kerja kedinasan aparatur sipil negara sejak pelantikan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 15) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>karakteristik pegawai yang ada di sana • menyelesaikan permasalahan yang ada</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C4 - Analisis", "question": "Pemahaman karakteristik pegawai melalui budaya organisasi mencakup hal-hal berikut, KECUALI...", "options": ["Cara pegawai menyelesaikan masalah", "Hal-hal yang disukai oleh para pegawai", "Hal-hal yang tidak disukai para pegawai", "Membaca situasi yang sedang terjadi", "Catatan kepemilikan aset kekayaan pribadi"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Cakupan Karakteristik Pegawai dalam Budaya</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Karakteristik pegawai yang dikenali melalui budaya organisasi meliputi <strong>cara menyelesaikan masalah</strong>, <strong>apa yang disukai</strong>, serta <strong>apa yang tidak disukai</strong> dalam lingkungan kerja.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat karena catatan kepemilikan harta pribadi bukan merupakan karakteristik budaya kerja organisasi. Budaya organisasi berfokus pada pemahaman situasi serta kebiasaan perilaku kerja aparatur dalam bertugas. Pemahaman budaya meliputi kemampuan mencermati dinamika kerja, cara menyelesaikan masalah, serta nilai-nilai yang dianut pegawai. Oleh karena itu, catatan aset pribadi berada di luar dimensi budaya organisasi.</p><p>Jawaban tepat: <strong>E. Catatan kepemilikan aset kekayaan pribadi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan karakteristik budaya organisasi dalam memahami dinamika lingkungan kerja.</li><li><strong>B</strong>: Disebutkan secara eksplisit sebagai aspek pengenalan preferensi kerja pegawai.</li><li><strong>C</strong>: Disebutkan secara eksplisit sebagai aspek preferensi perilaku kerja pegawai.</li><li><strong>D</strong>: Disebutkan secara eksplisit sebagai manfaat kehadiran budaya organisasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 15) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>apa yang mereka sukai • apa mereka tidak disukai</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Pemahaman yang baik mengenai situasi dan karakteristik pegawai sangat membantu pimpinan dalam...", "options": ["Membuat strategi di organisasi", "Menghitung tarif pajak pertambahan nilai", "Mengurangi jadwal pelatihan teknis dinas", "Memperpanjang jam kerja harian pegawai", "Membatasi komunikasi lintas kementerian"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Manfaat Budaya bagi Perumusan Strategi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dengan <strong>pemahaman mengenai situasi dan karakteristik pegawai</strong>, organisasi akan lebih terbantu dalam <strong>membuat strategi di organisasi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat mengenai fungsi strategis pemahaman budaya. Mengetahui dinamika anggota memungkinkan perumusan rencana kerja yang realistis dan dapat diterima seluruh jajaran. Prinsip dasar menegaskan bahwa dengan pemahaman situasi dan perilaku kerja seperti ini, kita tentu akan lebih terbantu dalam membuat strategi di organisasi. Dengan demikian, perumusan strategi menjadi manfaat langsung dari pemahaman budaya tersebut.</p><p>Jawaban tepat: <strong>A. Membuat strategi di organisasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan penetapan tarif fiskal berdasarkan ketentuan perundang-undangan perpajakan.</li><li><strong>C</strong>: Bertentangan dengan tujuan pengembangan kompetensi pegawai aparatur sipil negara.</li><li><strong>D</strong>: Menyalahi regulasi jam kerja dinas yang telah ditetapkan pemerintah.</li><li><strong>E</strong>: Bertentangan dengan prinsip koordinasi dan sinergi antarkelembagaan negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 15) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pemahaman seperti ini • membuat strategi di organisasi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Pimpinan unit melibatkan berbagai elemen staf dalam rapat strategi kantor agar mereka dapat...", "options": ["Mengambil alih seluruh fungsi pemeriksaan pimpinan", "Menentukan standar besaran tunjangan kinerja seksi", "Melakukan kolaborasi dan berinovasi dengan baik", "Mengurangi beban jam pelayanan langsung masyarakat", "Menyusun peraturan kepegawaian nasional tersendiri"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pelibatan Banyak Pihak dalam Strategi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam membuat strategi perlu <strong>melibatkan banyak pihak</strong> agar mereka bisa <strong>melakukan kolaborasi dan berinovasi dengan baik</strong> sehingga tujuan organisasi terealisasi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat sesuai tujuan pelibatan staf dalam penyusunan strategi. Keterlibatan bersama memicu sinergi gagasan baru dan memperkuat komitmen kerja tim. Dalam tata kelola organisasi, perumusan strategi perlu melibatkan banyak pihak agar mereka bisa berkolaborasi dan berinovasi secara baik demi tujuan organisasi. Oleh karena itu, kolaborasi dan inovasi merupakan tujuan utama pelibatan tersebut.</p><p>Jawaban tepat: <strong>C. Melakukan kolaborasi dan berinovasi dengan baik</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Fungsi pengawasan struktural tetap melekat pada pejabat berwenang.</li><li><strong>B</strong>: Standar tunjangan diatur melalui kebijakan pemerintah pusat, bukan rapat seksi.</li><li><strong>D</strong>: Pelibatan strategi bertujuan meningkatkan kualitas layanan, bukan memotong jam kerja.</li><li><strong>E</strong>: Regulasi kepegawaian nasional merupakan wewenang kementerian teknis terkait.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 15) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>perlu melibatkan banyak pihak • kolaborasi dan berinovasi dengan baik</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Tujuan organisasi lebih mudah terealisasi ketika pegawai memiliki rasa kepemilikan tinggi karena...", "options": ["Diberikan hak kebebasan menentukan jam kerja", "Merasa dilibatkan dalam pengambilan keputusan", "Menerima fasilitas kedinasan setara pejabat pimpinan", "Mendapat keistimewaan tanpa evaluasi tahunan", "Dibebaskan dari kewajiban penyusunan laporan dinas"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tumbuhnya Sense of Belonging Pegawai</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Tujuan organisasi dapat terealisasi karena <strong>rasa memiliki</strong> (<strong><em>sense of belonging</em></strong>) yang tinggi dari mereka yang <strong>merasa dilibatkan dalam pengambilan keputusan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat mengenai sumber lahirnya rasa kepemilikan pegawai terhadap tujuan kantor. Rasa memiliki tumbuh ketika pegawai dipandang sebagai mitra penting dalam menentukan langkah organisasi. Prinsip dasar menegaskan bahwa tujuan organisasi terealisasi karena adanya rasa memiliki yang tinggi dari mereka yang merasa dilibatkan dalam pengambilan keputusan. Dengan demikian, pelibatan dalam keputusan memperkokoh rasa memiliki tersebut.</p><p>Jawaban tepat: <strong>B. Merasa dilibatkan dalam pengambilan keputusan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Jam kerja instansi pemerintah terikat pada peraturan disiplin jam dinas.</li><li><strong>C</strong>: Fasilitas dinas dialokasikan berdasarkan jenjang jabatan dan standar kebutuhan resmi.</li><li><strong>D</strong>: Evaluasi kinerja tahunan merupakan kewajiban bagi seluruh aparatur sipil negara.</li><li><strong>E</strong>: Penyusunan laporan kerja merupakan bentuk akuntabilitas mutlak aparatur sipil negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 15) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>rasa memiliki (sense of belonging) • dilibatkan dalam pengambilan keputusan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Pimpinan kantor menetapkan metode komunikasi baru setelah mengamati cara pegawai bersikap, guna...", "options": ["Menyeragamkan seluruh latar belakang kebudayaan pegawai", "Menggantikan seluruh sistem manajemen aparatur negara", "Meniadakan proses evaluasi sasaran kinerja berkala", "Membatasi inovasi staf pada ruang pelayanan umum", "Menentukan strategi paling tepat mencapai tujuan"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penentuan Strategi Berdasarkan Sikap Pegawai</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pada saat kita mengetahui <strong>bagaimana cara pegawai dalam organisasi bersikap</strong>, kita bisa <strong>menentukan strategi apa yang paling tepat diterapkan untuk mencapai tujuan organisasi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat sesuai penerapan pengamatan perilaku terhadap perumusan strategi. Strategi yang efektif adalah strategi yang selaras dengan kesiapan dan kultur bersikap para pegawainya. Prinsip budaya organisasi menetapkan bahwa dengan mengetahui cara pegawai bersikap, pimpinan dapat menentukan strategi apa yang paling tepat untuk mencapai tujuan organisasi. Oleh karena itu, penetapan strategi yang tepat berakar dari pengamatan sikap tersebut.</p><p>Jawaban tepat: <strong>E. Menentukan strategi paling tepat mencapai tujuan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Budaya kerja menghargai keanekaragaman latar belakang kultural setiap individu.</li><li><strong>B</strong>: Sistem manajemen nasional tetap menjadi rujukan baku tata kelola pemerintahan.</li><li><strong>C</strong>: Evaluasi sasaran kinerja pegawai berkala wajib dilakukan secara konsisten.</li><li><strong>D</strong>: Inovasi pelayanan masyarakat justru didorong untuk meningkatkan kepuasan publik.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 15) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>bagaimana cara pegawai dalam organisasi bersikap • menentukan strategi apa yang paling tepat</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Kelancaran kerja sama (teamwork) antaranggotanya dalam sebuah tim sangat ditentukan oleh...", "options": ["Banyaknya jumlah pegawai dalam satu kelompok kerja", "Besarnya anggaran operasional kegiatan yang dikelola", "Tingkat kesamaan asal perguruan tinggi para anggota", "Peran budaya organisasi yang ada di lingkungan kerja", "Ketersediaan fasilitas kendaraan dinas operasional kantor"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Peran Budaya bagi Kerja Sama Tim</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Kerja sama (<em>teamwork</em>)</strong> dalam sebuah tim sangat ditentukan oleh <strong>peran budaya organisasi</strong> tempat mereka bekerja.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat mengenai faktor penentu solidnya kolaborasi kelompok kerja. Budaya organisasi yang sehat menyediakan rasa saling percaya dan komitmen bersama yang mempermudah sinergi. Secara normatif, kerja sama (teamwork) dalam sebuah tim sangat ditentukan oleh peran budaya organisasi. Dengan demikian, budaya organisasi menjadi fondasi penggerak kerja sama tim.</p><p>Jawaban tepat: <strong>D. Peran budaya organisasi yang ada di lingkungan kerja</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Jumlah anggota yang terlalu besar tanpa tata nilai justru memicu hambatan komunikasi.</li><li><strong>B</strong>: Besaran anggaran tidak otomatis menjamin keharmonisan hubungan kerja tim.</li><li><strong>C</strong>: Kesamaan latar belakang almamater bukan tolok ukur profesionalisme kerja sama dinas.</li><li><strong>E</strong>: Fasilitas transportasi operasional merupakan sarana pendukung mobilitas fisik semata.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 15) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Kerja sama (teamwork) dalam sebuah tim • peran budaya organisasi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Poin penting peran budaya organisasi bagi keberlanjutan sumber daya manusia adalah kehadirannya untuk...", "options": ["Meniadakan tahapan seleksi kompetensi bidang aparatur", "Mengurangi kebutuhan anggaran belanja pegawai kementerian", "Menarik talent berbobot atau bibit unggul", "Menetapkan masa pensiun dini secara otomatis bagi staf", "Membatasi promosi terbuka khusus untuk staf kantor pusat"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Budaya Organisasi dalam Menarik Talenta</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Poin penting peran budaya organisasi adalah kehadirannya untuk <strong>menarik talent berbobot atau bibit unggul</strong> agar ingin bergabung dan bekerja di organisasi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat mengenai fungsi budaya dalam rekrutmen dan pemikat talenta unggul. Lingkungan kerja yang berbudaya positif menjadi magnet bagi para profesional terbaik untuk mendedikasikan keterampilannya. Prinsip dasar menegaskan bahwa peran budaya organisasi sangat penting untuk menarik talent berbobot atau bibit unggul. Oleh sebab itu, daya tarik terhadap talenta terbaik menjadi poin kunci budaya organisasi.</p><p>Jawaban tepat: <strong>C. Menarik talent berbobot atau bibit unggul</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Tahapan seleksi kompetensi tetap wajib sesuai regulasi sistem merit nasional.</li><li><strong>B</strong>: Belanja pegawai dialokasikan berdasarkan standar kebutuhan negara yang sah.</li><li><strong>D</strong>: Masa purnabakti aparatur telah diatur tersendiri dalam undang-undang kepegawaian.</li><li><strong>E</strong>: Promosi karier berbasis sistem merit terbuka adil bagi seluruh pegawai di daerah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 15) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menarik talent berbobot atau bibit unggul • kehadirannya untuk menarik talent</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Dengan memiliki talenta SDM yang berkualitas, organisasi instansi pemerintah akan lebih mudah...", "options": ["Mencapai kesuksesan berkelanjutan yang dicita-citakan", "Menaikkan penerimaan negara tanpa dukungan sistem tata kelola", "Menghapus ketentuan jam dinas pelayanan tatap muka masyarakat", "Memusatkan seluruh wewenang daerah ke unit kerja kantor pusat", "Meniadakan mekanisme pengawasan internal pada setiap transaksi dinas"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dukungan Talenta bagi Kesuksesan Berkelanjutan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dengan memiliki <strong>individu berkualitas sebagai SDM organisasi</strong>, organisasi tentu akan lebih mudah <strong>mencapai kesuksesan berkelanjutan</strong> seperti yang dicita-citakan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat mengenai dampak kepemilikan SDM unggul bagi kelangsungan organisasi. Talenta yang berintegritas dan terampil mampu menjaga ketahanan organisasi dalam menghadapi tantangan masa depan. Pedoman kerja menetapkan bahwa dukungan para talenta berkualitas mempermudah organisasi mencapai kesuksesan berkelanjutan yang dicita-citakan. Dengan demikian, kesuksesan jangka panjang berakar dari kualitas talenta tersebut.</p><p>Jawaban tepat: <strong>A. Mencapai kesuksesan berkelanjutan yang dicita-citakan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Peningkatan pendapatan negara tetap menuntut keandalan sistem tata kelola fiskal.</li><li><strong>C</strong>: Jam layanan publik diatur ketat untuk memastikan kepastian pelayanan masyarakat.</li><li><strong>D</strong>: Sentralisasi total bertentangan dengan prinsip pembagian wewenang instansi vertikal.</li><li><strong>E</strong>: Pengawasan internal merupakan instrumen wajib penjaga akuntabilitas keuangan negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 15) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>individu berkualitas sebagai SDM organisasi • kesuksesan berkelanjutan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Peluncuran Nilai-nilai Dasar dan Employer Branding baru bagi Aparatur Sipil Negara ditetapkan Kementerian PANRB melalui...", "options": ["Instruksi Presiden Nomor 10 Tahun 2020", "Keputusan Menteri Keuangan Nomor 312 Tahun 2011", "Peraturan Pemerintah Nomor 94 Tahun 2021", "SE Nomor 20 Tahun 2021", "Peraturan Kepala BKN Nomor 5 Tahun 2022"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dasar Surat Edaran Core Values ASN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kementerian PANRB meluncurkan Core Values dan Employer Branding ASN berdasarkan <strong>SE Nomor 20 Tahun 2021</strong> tentang Implementasi Core Values dan Employer Branding Aparatur Sipil Negara.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat mengenai dasar regulasi peluncuran BerAKHLAK oleh Kementerian PANRB. Surat Edaran tersebut diterbitkan untuk menyeragamkan budaya kerja aparatur di seluruh Indonesia. Secara resmi, Kementerian PANRB meluncurkan Nilai-nilai Dasar dan Employer Branding baru bagi ASN berdasarkan SE Nomor 20 Tahun 2021. Dengan demikian, SE Nomor 20 Tahun 2021 menjadi dasar penetapan resminya.</p><p>Jawaban tepat: <strong>D. SE Nomor 20 Tahun 2021</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Bukan merupakan dasar surat edaran peluncuran nilai BerAKHLAK Kementerian PANRB.</li><li><strong>B</strong>: Merupakan Keputusan Menteri Keuangan tentang Nilai-Nilai Kementerian Keuangan tahun 2011.</li><li><strong>C</strong>: Merupakan Peraturan Pemerintah mengenai ketentuan disiplin Pegawai Negeri Sipil.</li><li><strong>E</strong>: Merupakan peraturan teknis kepegawaian dari Badan Kepegawaian Negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 16) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Kementerian PANRB • SE Nomor 20 Tahun 2021</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Core Values BerAKHLAK dan Employer Branding ASN diresmikan langsung oleh Presiden Joko Widodo pada...", "options": ["Tanggal 27 Juli 2021", "Tanggal 17 Agustus 2021", "Tanggal 28 Oktober 2021", "Tanggal 10 November 2021", "Tanggal 31 Desember 2021"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tanggal Peresmian Core Values BerAKHLAK</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Core Values BerAKHLAK dan Employer Branding ASN <strong>diresmikan langsung oleh Presiden Joko Widodo pada tanggal 27 Juli 2021</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat mengenai waktu peresmian nilai dasar BerAKHLAK oleh kepala negara. Momentum ini menjadi tonggak sejarah dimulainya standardisasi budaya kerja aparatur sipil negara di seluruh nusantara. Presiden Joko Widodo meresmikan Core Values BerAKHLAK dan employer branding #BanggaMelayaniBangsa secara resmi pada tanggal 27 Juli 2021. Oleh karena itu, tanggal 27 Juli 2021 merupakan tanggal peresmian resminya.</p><p>Jawaban tepat: <strong>A. Tanggal 27 Juli 2021</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan hari peringatan Proklamasi Kemerdekaan Republik Indonesia.</li><li><strong>C</strong>: Merupakan hari peringatan nasional Hari Sumpah Pemuda.</li><li><strong>D</strong>: Merupakan hari peringatan nasional Hari Pahlawan di Indonesia.</li><li><strong>E</strong>: Merupakan hari penutupan kalender tahun anggaran pemerintah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 16) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Presiden Joko Widodo • tanggal 27 Juli 2021</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Dalam akronim Core Values ASN BerAKHLAK, huruf 'K' pertama dan 'K' kedua mewakili nilai...", "options": ["Komunikatif dan Konsisten", "Kompeten dan Kolaboratif", "Kredibel dan Komitmen", "Kreatif dan Konstruktif", "Kolektif dan Kualitatif"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Komponen Huruf K dalam Nilai BerAKHLAK</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>BerAKHLAK</strong> merupakan akronim resmi di mana huruf <strong>'K' pertama mewakili Kompeten</strong> dan <strong>'K' kedua mewakili Kolaboratif</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat mengenai representasi dua huruf K dalam akronim BerAKHLAK. Kompeten menekankan keahlian kerja, sedangkan Kolaboratif menekankan sinergi kemitraan bersama. Dalam prinsip dasar, BerAKHLAK adalah akronim dari Berorientasi Pelayanan, Akuntabel, Kompeten, Harmonis, Loyal, Adaptif, dan Kolaboratif. Dengan demikian, Kompeten dan Kolaboratif adalah kepanjangan huruf K yang tepat.</p><p>Jawaban tepat: <strong>B. Kompeten dan Kolaboratif</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Bukan merupakan nilai dasar yang tercakup dalam akronim resmi BerAKHLAK.</li><li><strong>C</strong>: Kata Kredibel dan Komitmen bukan bagian dari akronim BerAKHLAK.</li><li><strong>D</strong>: Kata Kreatif dan Konstruktif tidak terdapat dalam rumusan akronim BerAKHLAK.</li><li><strong>E</strong>: Kata Kolektif dan Kualitatif bukan nama nilai dasar dalam BerAKHLAK.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 16) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>BerAKHLAK merupakan akronim dari • Berorientasi Pelayanan, Akuntabel, Kompeten</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Core Values ASN BerAKHLAK menjadi inti dari nilai-nilai dasar ASN sebagaimana diatur dalam...", "options": ["Undang-Undang Nomor 5 Tahun 2014", "Undang-Undang Nomor 17 Tahun 2003", "Undang-Undang Nomor 1 Tahun 2004", "Undang-Undang Nomor 15 Tahun 2004", "Undang-Undang No. 20 Tahun 2023"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dasar Undang-Undang Nilai Dasar ASN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Core Values ASN BerAKHLAK adalah inti dari nilai-nilai dasar ASN sesuai dengan <strong>Undang-Undang No. 20 Tahun 2023 tentang Aparatur Sipil Negara</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat mengenai payung undang-undang yang mengatur nilai dasar ASN terkini. Regulasi ini memperkuat status hukum nilai BerAKHLAK sebagai standar perilaku aparatur di Indonesia. Secara normatif, Core Values ASN ini adalah inti dari nilai-nilai dasar ASN sesuai dengan Undang-Undang No. 20 Tahun 2023 tentang Aparatur Sipil Negara. Oleh karena itu, UU No. 20 Tahun 2023 merupakan rujukan undang-undangnya.</p><p>Jawaban tepat: <strong>E. Undang-Undang No. 20 Tahun 2023</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan undang-undang aparatur sipil negara yang telah digantikan oleh regulasi baru.</li><li><strong>B</strong>: Merupakan undang-undang yang mengatur tentang Keuangan Negara.</li><li><strong>C</strong>: Merupakan undang-undang yang mengatur tentang Perbendaharaan Negara.</li><li><strong>D</strong>: Merupakan undang-undang tentang Pemeriksaan Pengelolaan Keuangan Negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 16) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>UndangUndang No. 20 Tahun 2023 • tentang Aparatur Sipil Negara</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Employer branding #banggamelayanibangsa merupakan pemutakhiran dari semboyan branding ASN sebelumnya, yaitu...", "options": ["Bekerja nyata untuk nusa dan bangsa", "Mengabdi tanpa pamrih demi negeri", "Melayani dengan sepenuh hati", "Membangun birokrasi berkelas dunia", "Aparatur profesional berdaya saing"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pemutakhiran Semboyan Employer Branding</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>#banggamelayanibangsa</strong> adalah pemutakhiran dari *employer branding* ASN sebelumnya, yaitu <strong>melayani dengan sepenuh hati</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat mengenai fungsi employer branding aparatur. Slogan #BanggaMelayaniBangsa dirancang untuk menumbuhkan rasa percaya diri dan martabat ASN dalam melayani publik. Employer branding #BanggaMelayaniBangsa berfungsi sebagai pendorong kebanggaan aparatur dalam memberikan pelayanan publik secara profesional. Dengan demikian, pemupuk rasa bangga melayani menjadi fungsi esensialnya.</p><p>Jawaban tepat: <strong>C. Melayani dengan sepenuh hati</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Bukan merupakan rumusan resmi employer branding aparatur sipil negara terdahulu.</li><li><strong>B</strong>: Merupakan frasa motivasi umum pengabdian abdi negara, bukan slogan branding.</li><li><strong>D</strong>: Merupakan visi reformasi birokrasi pemerintah menuju world class bureaucracy.</li><li><strong>E</strong>: Merupakan target kompetensi pengembangan sumber daya manusia aparatur.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 16) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pemutakhiran dari employer branding • melayani dengan sepenuh hati</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Presiden Joko Widodo menegaskan bahwa ASN yang bertugas sebagai pegawai pusat maupun daerah harus...", "options": ["Menerima standar besaran tunjangan daerah yang seragam", "Memiliki masa penugasan dinas luar kota yang setara", "Mengenakan seragam dinas harian bermotif identik", "Mempunyai core values yang sama", "Menjalani rotasi berkala lintas instansi pemerintah"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pesan Presiden tentang Kesamaan Nilai ASN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Presiden Joko Widodo berpesan bahwa <strong>ASN yang bertugas sebagai pegawai pusat maupun pegawai daerah harus mempunyai <em>core values</em> yang sama</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat mengenai arahan presiden tentang kesatuan budaya kerja birokrasi. Standardisasi nilai menghapus sekat ego kedaerahan dan mewujudkan pelayanan publik yang terpadu di seluruh tanah air. Secara tegas dinyatakan bahwa presiden: ASN yang bertugas sebagai pegawai pusat maupun pegawai daerah harus mempunyai core values yang sama. Oleh karena itu, kesamaan core values menjadi substansi pesan presiden.</p><p>Jawaban tepat: <strong>D. Mempunyai core values yang sama</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Besaran tunjangan kinerja daerah disesuaikan dengan kemampuan fiskal masing-masing pemda.</li><li><strong>B</strong>: Penugasan perjalanan dinas diatur menurut kebutuhan riil satuan kerja.</li><li><strong>C</strong>: Aturan seragam dinas diatur oleh masing-masing kementerian dan pemerintah daerah.</li><li><strong>E</strong>: Rotasi lintas instansi merupakan mekanisme mobilitas talenta, bukan arahan nilai inti.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 16) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pegawai pusat maupun pegawai daerah • core values yang sama</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Bagi seluruh aparatur sipil negara, nilai BerAKHLAK diposisikan sebagai panduan perilaku serta menjadi...", "options": ["Fondasi budaya kerja ASN yang profesional", "Satu-satunya syarat kenaikan jabatan fungsional", "Pedoman pembagian pagu anggaran satuan kerja", "Aturan teknis pengadaan barang milik negara", "Instrumen pembebasan kewajiban audit dinas"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kedudukan Nilai BerAKHLAK bagi ASN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>BerAKHLAK</strong> merupakan panduan perilaku bagi ASN, sebagai nilai dasar yang harus dijalankan dengan penuh tanggung jawab, dan menjadi <strong>fondasi budaya kerja ASN yang profesional</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat mengenai kedudukan BerAKHLAK dalam manajemen kepegawaian nasional. Nilai BerAKHLAK berperan sebagai fondasi etika dan moral utama yang melandasi profesionalitas aparatur. Prinsip etika merumuskan bahwa BerAKHLAK adalah nilai dasar yang harus dijalankan penuh tanggung jawab dan menjadi fondasi budaya kerja ASN yang profesional. Dengan demikian, fondasi budaya kerja profesional menjadi kedudukan intinya.</p><p>Jawaban tepat: <strong>A. Fondasi budaya kerja ASN yang profesional</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kenaikan jabatan menuntut pemenuhan angka kredit dan uji kompetensi jabatan.</li><li><strong>C</strong>: Pagu anggaran diatur dalam dokumen rencana kerja dan anggaran kementerian.</li><li><strong>D</strong>: Pengadaan barang dan jasa pemerintah diatur tersendiri dalam peraturan presiden.</li><li><strong>E</strong>: Audit berkala tetap wajib dijalankan oleh aparat pengawas internal pemerintah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 16) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>BerAKHLAK merupakan panduan perilaku • fondasi budaya kerja ASN yang profesional</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Saat menyusun sistem antrean ramah disabilitas, pegawai menerapkan panduan perilaku Berorientasi Pelayanan dalam hal...", "options": ["Menjaga kerahasiaan informasi internal kantor", "Memahami dan memenuhi kebutuhan masyarakat", "Menghargai keberagaman suku antarsesama rekan", "Menyesuaikan diri dengan perubahan teknologi", "Menggerakkan sumber daya untuk sasaran seksi"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penerapan Kebutuhan Layanan Masyarakat</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Salah satu panduan perilaku nilai <strong>Berorientasi Pelayanan</strong> adalah <strong>memahami dan memenuhi kebutuhan masyarakat</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat sesuai ilustrasi penataan loket inklusif bagi warga tersebut. Mengidentifikasi kebutuhan khusus pengguna jasa merupakan wujud pemahaman konkret atas kepentingan publik. Dalam panduan perilaku BerAKHLAK, butir pertama panduan perilaku Berorientasi Pelayanan berbunyi memahami dan memenuhi kebutuhan masyarakat. Oleh karena itu, penyediaan sarana tersebut mewujudkan pemenuhan kebutuhan masyarakat.</p><p>Jawaban tepat: <strong>B. Memahami dan memenuhi kebutuhan masyarakat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan wujud panduan perilaku pada nilai Loyal terkait kerahasiaan jabatan.</li><li><strong>C</strong>: Merupakan butir panduan perilaku pada nilai Harmonis di tempat kerja.</li><li><strong>D</strong>: Merupakan butir panduan perilaku pada nilai Adaptif dalam menghadapi teknologi.</li><li><strong>E</strong>: Merupakan butir panduan perilaku pada nilai Kolaboratif dalam pemanfaatan daya.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 16) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Berorientasi Pelayanan, dengan panduan perilaku • memahami dan memenuhi kebutuhan masyarakat</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Petugas meja bantuan melayani wajib pajak dengan senyum, bertindak cepat, dan memberikan jalan keluar tepat, mencerminkan perilaku...", "options": ["Melakukan perbaikan sistem berkala tahunan", "Menjaga martabat dari segala hal tercela", "Membantu orang lain belajar secara aktif", "Membangun kemitraan strategis pemangku jabatan", "Ramah, cekatan, solutif, dan dapat diandalkan"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Perilaku Petugas Pelayanan Publik</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Panduan perilaku kedua nilai Berorientasi Pelayanan menegaskan kewajiban aparatur untuk bersikap <strong>ramah, cekatan, solutif, dan dapat diandalkan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat sesuai rangkaian sikap yang ditunjukkan oleh petugas meja bantuan. Keramahan, kegesitan tindakan, pemberian solusi, dan keandalan merupakan satu kesatuan perilaku pelayanan prima. Panduan perilaku secara tegas menetapkan bahwa Berorientasi Pelayanan mencakup ramah, cekatan, solutif, dan dapat diandalkan. Dengan demikian, seluruh sikap tersebut mencerminkan butir perilaku pada opsi E.</p><p>Jawaban tepat: <strong>E. Ramah, cekatan, solutif, dan dapat diandalkan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan butir perbaikan berkelanjutan pada nilai Berorientasi Pelayanan.</li><li><strong>B</strong>: Merupakan perilaku utama kedua pada nilai Integritas Kementerian Keuangan.</li><li><strong>C</strong>: Merupakan salah satu butir panduan perilaku pada nilai Kompeten.</li><li><strong>D</strong>: Merupakan fokus kerja sama strategis pada nilai Kolaboratif dan Sinergi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 16) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>ramah, cekatan, solutif • ramah, cekatan, solutif, dan dapat diandalkan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C4 - Analisis", "question": "Berikut ini merupakan panduan perilaku (kode etik) nilai Berorientasi Pelayanan dalam prinsip pelayanan prima, KECUALI...", "options": ["Memahami dan memenuhi kebutuhan masyarakat", "Ramah, cekatan, solutif, dan dapat diandalkan", "Memberi kesempatan berbagai pihak untuk berkontribusi", "Melakukan perbaikan tiada henti", "Melayani masyarakat dengan kesungguhan hati"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Cakupan Panduan Nilai Berorientasi Pelayanan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Panduan perilaku <strong>Berorientasi Pelayanan</strong> mencakup: (1) <strong>memahami dan memenuhi kebutuhan masyarakat</strong>, (2) <strong>ramah, cekatan, solutif, dan dapat diandalkan</strong>, serta (3) <strong>melakukan perbaikan tiada henti</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat karena butir tersebut adalah panduan perilaku untuk nilai Kolaboratif, bukan Berorientasi Pelayanan. Ketiga panduan resmi Berorientasi Pelayanan berfokus langsung pada pemenuhan kebutuhan dan keandalan layanan bagi warga. Memberi kesempatan berkontribusi merupakan butir perilaku nilai Kolaboratif. Oleh sebab itu, opsi C menjadi pengecualian yang benar.</p><p>Jawaban tepat: <strong>C. Memberi kesempatan berbagai pihak untuk berkontribusi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan butir pertama panduan perilaku resmi nilai Berorientasi Pelayanan.</li><li><strong>B</strong>: Merupakan butir kedua panduan perilaku resmi nilai Berorientasi Pelayanan.</li><li><strong>D</strong>: Merupakan butir ketiga panduan perilaku resmi nilai Berorientasi Pelayanan.</li><li><strong>E</strong>: Merupakan penjelas umum makna orientasi pelayanan aparatur sipil negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 16) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>melakukan perbaikan tiada henti • Berorientasi Pelayanan, dengan panduan perilaku</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Menggunakan kekayaan dan barang milik negara secara bertanggung jawab, efektif, dan efisien merupakan panduan perilaku pada nilai...", "options": ["Harmonis", "Adaptif", "Kompeten", "Kolaboratif", "Akuntabel"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Akuntabilitas Penggunaan Barang Milik Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Menggunakan kekayaan dan barang milik negara secara bertanggung jawab, efektif, dan efisien merupakan panduan perilaku dari nilai <strong>Akuntabel</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat mengenai nilai yang menaungi pengelolaan sarana dan prasarana milik dinas. Setiap fasilitas yang dibiayai uang rakyat wajib dipertanggungjawabkan penggunaannya demi kelancaran tugas negara. Nilai Akuntabel secara normatif salah satunya mewajibkan ASN menggunakan kekayaan dan barang milik negara secara bertanggung jawab, efektif, dan efisien. Dengan demikian, nilai Akuntabel menjadi wadah panduan perilaku tersebut.</p><p>Jawaban tepat: <strong>E. Akuntabel</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Berfokus pada suasana saling menghargai dan lingkungan kerja yang kondusif.</li><li><strong>B</strong>: Berfokus pada kemampuan inovasi dan kecepatan menyesuaikan diri terhadap perubahan.</li><li><strong>C</strong>: Berfokus pada peningkatan keahlian diri dan bantuan belajar kepada orang lain.</li><li><strong>D</strong>: Berfokus pada keterbukaan kerja sama untuk menghasilkan nilai tambah bersama.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 17) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menggunakan kekayaan dan barang milik negara • secara bertanggung jawab, efektif, dan efisien</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Seorang pejabat menolak memberikan rekomendasi izin usaha yang menyalahi prosedur, sebagai penerapan perilaku nilai Akuntabel dalam...", "options": ["Menjaga rahasia data pribadi wajib pajak", "Membangun lingkungan kerja yang kondusif", "Tidak menyalahgunakan kewenangan jabatan", "Menghargai keberagaman suku para pemohon", "Meningkatkan kompetensi teknis bawahan"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Integritas Kewenangan Jabatan Akuntabel</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Salah satu butir panduan perilaku nilai Akuntabel adalah <strong>tidak menyalahgunakan kewenangan jabatan</strong> dalam setiap pelaksanaan wewenang kedinasan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat sesuai penolakan tegas pejabat tersebut terhadap perbuatan melanggar tata tertib dinas. Wewenang yang diberikan negara adalah amanah publik yang tidak boleh disalahgunakan untuk kepentingan nonprosedural. Di dalam rincian panduan perilaku nilai Akuntabel tertulis jelas larangan menyalahgunakan kewenangan jabatan. Oleh karena itu, tindakan pejabat tersebut menjalankan butir tidak menyalahgunakan wewenang jabatan.</p><p>Jawaban tepat: <strong>C. Tidak menyalahgunakan kewenangan jabatan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan butir perlindungan rahasia kedinasan pada nilai Loyal.</li><li><strong>B</strong>: Merupakan butir penciptaan iklim kerja sehat pada nilai Harmonis.</li><li><strong>D</strong>: Merupakan butir penghargaan kesetaraan sosial pada nilai Harmonis.</li><li><strong>E</strong>: Merupakan butir pembinaan kecakapan kerja pada nilai Kompeten.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 17) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>tidak menyalahgunakan kewenangan jabatan • melaksanakan tugas dengan jujur</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Pegawai senior mendampingi staf baru dalam mengoperasikan sistem aplikasi pelaporan anggaran, mencerminkan panduan nilai Kompeten yaitu...", "options": ["Membantu orang lain belajar", "Menjaga nama baik aparatur negara", "Menyesuaikan diri menghadapi perubahan", "Memanfaatkan sumber daya bersama", "Melayani dengan ramah dan cekatan"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Berbagi Pengetahuan Sesuai Nilai Kompeten</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Panduan perilaku nilai <strong>Kompeten</strong> mencakup kewajiban aparatur untuk saling membina kecakapan kerja melalui perilaku <strong>membantu orang lain belajar</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat sesuai tindakan pendampingan teknis kerja kepada staf baru tersebut. Pembelajaran berkelanjutan dalam organisasi terjadi melalui kesediaan pegawai yang lebih berpengalaman untuk mentransfer pengetahuannya. Dalam pedoman etika, butir panduan perilaku nilai Kompeten secara tegas menyebutkan membantu orang lain belajar. Dengan demikian, kegiatan mendampingi rekan baru tersebut mewujudkan perilaku membantu orang lain belajar.</p><p>Jawaban tepat: <strong>A. Membantu orang lain belajar</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan butir menjaga martabat korps pada nilai Loyal.</li><li><strong>C</strong>: Merupakan butir kepekaan transformasi teknologi pada nilai Adaptif.</li><li><strong>D</strong>: Merupakan butir mobilisasi fasilitas dinas pada nilai Kolaboratif.</li><li><strong>E</strong>: Merupakan butir keramahan petugas layanan pada nilai Berorientasi Pelayanan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 17) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>membantu orang lain belajar • meningkatkan kompetensi diri</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Upaya aparatur sipil negara untuk terus meningkatkan kompetensi diri bertujuan agar dapat...", "options": ["Mendapatkan fasilitas kendaraan dinas operasional khusus", "Menentukan besaran tarif sewa gedung pertemuan dinas", "Mempercepat peralihan masa pensiun dari jadwal resmi", "Menjawab tantangan yang selalu berubah", "Meniadakan tahapan uji berkas administrasi kepegawaian"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Peningkatan Kapasitas Nilai Kompeten</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Aparatur sipil negara dituntut <strong>meningkatkan kompetensi diri untuk menjawab tantangan yang selalu berubah</strong> dan dinamis.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat mengenai tujuan utama pengembangan kapasitas pegawai secara berkelanjutan. Perubahan regulasi dan teknologi menuntut pemutakhiran pengetahuan secara kontinu agar kinerja pelayanan tetap optimal. Butir pertama nilai Kompeten secara tegas menekankan kewajiban untuk meningkatkan kompetensi diri untuk menjawab tantangan yang selalu berubah. Oleh sebab itu, menjawab tantangan yang berubah menjadi tujuan intinya.</p><p>Jawaban tepat: <strong>D. Menjawab tantangan yang selalu berubah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Fasilitas dinas diberikan berdasarkan regulasi standar sarana prasarana kementerian.</li><li><strong>B</strong>: Tarif pemanfaatan barang milik negara diatur melalui regulasi tarif resmi pemerintah.</li><li><strong>C</strong>: Jadwal purnabakti aparatur telah ditentukan berdasarkan batas usia pensiun undang-undang.</li><li><strong>E</strong>: Verifikasi administrasi berkas merupakan prosedur operasional baku tata kelola kepegawaian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 17) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>meningkatkan kompetensi diri untuk menjawab • tantangan yang selalu berubah</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Petugas loket menyapa dan melayani setiap pemohon secara ramah tanpa membedakan suku maupun keyakinan, mencerminkan nilai Harmonis yaitu...", "options": ["Melakukan perbaikan layanan tiada henti", "Menghargai setiap orang tanpa membedakan latar belakang", "Menggerakkan sumber daya untuk tujuan bersama", "Memegang teguh ideologi dasar negara", "Melaksanakan tugas dengan cermat dan disiplin"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kesetaraan Perlakuan Nilai Harmonis</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Panduan perilaku nilai <strong>Harmonis</strong> salah satunya adalah <strong>menghargai setiap orang tanpa membedakan latar belakang</strong> suku, agama, ras, dan antargolongan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat sesuai sikap adil petugas loket terhadap seluruh pemohon jasa layanan. Penghormatan atas martabat kemanusiaan tanpa prasangka primordial menjadi fondasi kerukunan bermasyarakat. Prinsip budaya menegaskan bahwa nilai Harmonis dipandu oleh perilaku menghargai setiap orang tanpa membedakan latar belakang. Dengan demikian, pelayanan nondiskriminatif tersebut mencerminkan butir pada opsi B.</p><p>Jawaban tepat: <strong>B. Menghargai setiap orang tanpa membedakan latar belakang</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan butir penyempurnaan kualitas pada nilai Berorientasi Pelayanan.</li><li><strong>C</strong>: Merupakan butir pemanfaatan potensi pada nilai Kolaboratif.</li><li><strong>D</strong>: Merupakan butir kesetiaan konstitusional pada nilai Loyal.</li><li><strong>E</strong>: Merupakan butir ketelitian kerja dinas pada nilai Akuntabel.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 17) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menghargai setiap orang tanpa membedakan latar belakang • Harmonis, dengan panduan perilaku</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Saling tolong-menolong antarrekan sekerja dalam mengatasi kesulitan operasional kantor bertujuan untuk...", "options": ["Meniadakan fungsi pengawasan fungsional inspektorat", "Menghapus ketentuan batas waktu penyelesaian berkas", "Mengurangi jadwal absensi kehadiran harian kantor", "Memperoleh keistimewaan kenaikan pangkat reguler", "Membangun lingkungan kerja yang kondusif"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penciptaan Iklim Kerja Kondusif</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Di samping suka menolong, panduan perilaku nilai <strong>Harmonis</strong> menekankan pentingnya <strong>membangun lingkungan kerja yang kondusif</strong> dan nyaman.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat mengenai dampak positif dari budaya saling tolong-menolong di lingkungan kerja. Suasana kerja yang suportif dan bersahabat akan mendorong produktivitas serta ketenangan seluruh staf dalam bertugas. Pedoman kerja menetapkan bahwa panduan perilaku nilai Harmonis meliputi suka menolong serta membangun lingkungan kerja yang kondusif. Oleh karena itu, terbangunnya lingkungan kerja yang kondusif menjadi tujuan esensialnya.</p><p>Jawaban tepat: <strong>E. Membangun lingkungan kerja yang kondusif</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pengawasan aparat pengawas internal pemerintah tetap berjalan independen dan objektif.</li><li><strong>B</strong>: Batas waktu pemrosesan dokumen diatur dalam standar pelayanan operasional kantor.</li><li><strong>C</strong>: Ketentuan presensi elektronik wajib ditaati sesuai aturan disiplin jam dinas.</li><li><strong>D</strong>: Kenaikan pangkat aparatur diatur berdasarkan kualifikasi, kinerja, dan sistem merit.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 17) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>membangun lingkungan kerja yang kondusif • suka menolong</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Seorang staf menolak memberikan salinan draf kebijakan perpajakan sebelum tanggal pengumuman resmi, sebagai wujud nilai Loyal dalam...", "options": ["Membantu orang lain belajar keterampilan baru", "Memberi kesempatan pihak luar berkontribusi", "Menjaga rahasia jabatan dan negara", "Menyesuaikan diri menghadapi disrupsi", "Menggunakan fasilitas dinas secara efisien"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penjagaan Kerahasiaan Data Kedinasan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Panduan perilaku nilai <strong>Loyal</strong> salah satunya mewajibkan seluruh aparatur negara untuk <strong>menjaga rahasia jabatan dan negara</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat sesuai penolakan staf terhadap pembocoran dokumen yang belum dibuka untuk umum. Menjaga dokumen rahasia kedinasan dari akses tidak sah merupakan wujud loyalitas mutlak abdi negara. Perilaku menjaga rahasia jabatan merupakan jabatan dan negara merupakan butir resmi nilai Loyal. Dengan demikian, tindakan pengamanan data dinas tersebut menjalankan nilai Loyal.</p><p>Jawaban tepat: <strong>C. Menjaga rahasia jabatan dan negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan butir pembelajaran pegawai pada nilai Kompeten.</li><li><strong>B</strong>: Merupakan butir keterbukaan peran serta pada nilai Kolaboratif.</li><li><strong>D</strong>: Merupakan butir kelincahan adaptasi pada nilai Adaptif.</li><li><strong>E</strong>: Merupakan butir pengelolaan fasilitas dinas pada nilai Akuntabel.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 17) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menjaga rahasia jabatan dan negara • Loyal, dengan panduan perilaku</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Panduan perilaku nilai Loyal yang paling mendasar bagi seluruh aparatur sipil negara di Indonesia adalah...", "options": ["Memegang teguh ideologi Pancasila dan UUD 1945 serta setia kepada NKRI", "Melakukan perbaikan berkelanjutan terhadap fasilitas loket masyarakat", "Menggunakan barang milik negara secara bertanggung jawab dan hemat", "Cepat beradaptasi terhadap perubahan aplikasi teknologi informasi", "Membangun kemitraan strategis dengan berbagai lembaga swasta"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Komitmen Kebangsaan Nilai Loyal</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Panduan perilaku pertama nilai Loyal adalah <strong>memegang teguh ideologi Pancasila dan Undang-Undang Dasar Negara Republik Indonesia Tahun 1945, setia kepada NKRI serta pemerintahan yang sah</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat mengenai komitmen dasar kebangsaan pada nilai Loyal. Kesetiaan terhadap konstitusi dan keutuhan bangsa merupakan landasan tertinggi integritas seorang aparatur negara. Secara psikologis, panduan perilaku pertama nilai Loyal berbunyi memegang teguh ideologi Pancasila dan UUD 1945, setia kepada NKRI serta pemerintahan yang sah. Oleh karena itu, rumusan pada opsi A adalah panduan perilaku yang dimaksud.</p><p>Jawaban tepat: <strong>A. Memegang teguh ideologi Pancasila dan UUD 1945 serta setia kepada NKRI</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan panduan perbaikan tanpa henti pada nilai Berorientasi Pelayanan.</li><li><strong>C</strong>: Merupakan panduan pemanfaatan aset pada nilai Akuntabel.</li><li><strong>D</strong>: Merupakan panduan kelincahan menghadapi perubahan pada nilai Adaptif.</li><li><strong>E</strong>: Merupakan panduan kemitraan lintas pihak pada nilai Kolaboratif.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 17) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>memegang teguh ideologi Pancasila • Undang-Undang Dasar Negara Republik Indonesia Tahun 1945</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Ketika kementerian menerapkan sistem surat dinas digital sepenuhnya, para staf segera menguasai alur kerja baru tersebut demi...", "options": ["Mendapatkan tambahan cuti tahunan di luar ketentuan", "Mengalihkan tanggung jawab pengelolaan aset dinas", "Membatasi konsultasi layanan publik dengan masyarakat", "Cepat menyesuaikan diri menghadapi perubahan", "Mengurangi alokasi anggaran operasional seksi kantor"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kelincahan Penyesuaian Nilai Adaptif</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Panduan perilaku nilai <strong>Adaptif</strong> menuntut aparatur agar <strong>cepat menyesuaikan diri menghadapi perubahan</strong> zaman dan teknologi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat sesuai kesiapan staf dalam menyerap transformasi sistem kerja digital kantor. Kesiapan merespons perkembangan teknologi memungkinkan tugas kedinasan tetap berjalan lancar dan akurat. Prinsip budaya menegaskan bahwa butir pertama nilai Adaptif adalah cepat menyesuaikan diri menghadapi perubahan. Dengan demikian, penguasaan alur kerja baru tersebut mencerminkan perilaku penyesuaian diri.</p><p>Jawaban tepat: <strong>D. Cepat menyesuaikan diri menghadapi perubahan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Hak cuti tahunan aparatur diatur secara ketat dalam undang-undang manajemen kepegawaian.</li><li><strong>B</strong>: Pengelolaan barang dinas tetap wajib dipertanggungjawabkan sesuai nilai Akuntabel.</li><li><strong>C</strong>: Konsultasi pelayanan publik wajib diselenggarakan secara terbuka dan profesional.</li><li><strong>E</strong>: Penyesuaian teknologi bertujuan meningkatkan produktivitas, bukan memotong belanja kerja.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 17) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>cepat menyesuaikan diri menghadapi perubahan • Adaptif, dengan panduan perilaku</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Keterbukaan dalam menjalin kerja sama lintas seksi pada nilai Kolaboratif ditujukan secara terarah untuk...", "options": ["Menyeragamkan seluruh latar belakang keahlian staf kantor", "Menghasilkan nilai tambah bagi pencapaian organisasi", "Meniadakan pembagian uraian tugas masing-masing jabatan", "Mengalihkan tanggung jawab pekerjaan seksi kepada pihak luar", "Menambah beban jam kerja lembur harian para pelaksana"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Keterbukaan Kerja Sama Nilai Kolaboratif</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Panduan perilaku kedua nilai <strong>Kolaboratif</strong> adalah <strong>terbuka dalam bekerja sama untuk menghasilkan nilai tambah</strong> bagi organisasi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat mengenai tujuan akhir dari jalinan kerja sama lintas unit kerja. Kolaborasi yang efektif bukan sekadar berkumpul, melainkan menciptakan sinergi yang meningkatkan mutu capaian bersama. Dalam panduan perilaku, nilai Kolaboratif dipandu oleh perilaku terbuka dalam bekerja sama untuk menghasilkan nilai tambah. Oleh sebab itu, perolehan nilai tambah menjadi sasaran utama keterbukaan kerja sama tersebut.</p><p>Jawaban tepat: <strong>B. Menghasilkan nilai tambah bagi pencapaian organisasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kolaborasi justru memanfaatkan keragaman latar belakang keahlian untuk saling melengkapi.</li><li><strong>C</strong>: Uraian tugas pokok masing-masing jabatan tetap menjadi acuan kerja yang baku.</li><li><strong>D</strong>: Tanggung jawab kedinasan tetap melekat pada pejabat berwenang secara akuntabel.</li><li><strong>E</strong>: Kerja sama bertujuan menciptakan efisiensi kerja, bukan menambah beban waktu kerja.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 17) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>terbuka dalam bekerja sama untuk menghasilkan nilai tambah • Kolaboratif, dengan panduan perilaku</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Pencetusan employer branding #BanggaMelayaniBangsa oleh Presiden Joko Widodo menunjukkan bahwa ASN adalah...", "options": ["Kelompok aparatur yang kebal dari evaluasi publik", "Pengelola tunggal seluruh pendapatan dan belanja negara", "Profesi yang bangga dalam melayani bangsa", "Pelaksana administrasi tanpa kewajiban inovasi kerja", "Pekerja yang dibebaskan dari aturan disiplin pegawai"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Makna Employer Branding ASN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Employer branding <strong>#BanggaMelayaniBangsa</strong> menunjukkan bahwa <strong>ASN adalah profesi yang bangga dalam melayani bangsa</strong> di seluruh penjuru Indonesia.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat mengenai makna luhur di balik semboyan pengabdian aparatur tersebut. Kebanggaan melayani masyarakat menumbuhkan dedikasi dan profesionalisme sejati dalam setiap tindakan birokrasi. Employer branding #BanggaMelayaniBangsa menegaskan bahwa menunjukkan bahwa ASN adalah profesi yang bangga dalam melayani bangsa. Dengan demikian, kebanggaan dalam melayani bangsa menjadi pesan utamanya.</p><p>Jawaban tepat: <strong>C. Profesi yang bangga dalam melayani bangsa</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Aparatur sipil negara selalu terbuka dan wajib dievaluasi kinerjanya oleh publik.</li><li><strong>B</strong>: Pengelolaan keuangan negara melibatkan banyak instansi dan lembaga tinggi negara.</li><li><strong>D</strong>: Seluruh aparatur sipil negara didorong untuk terus berinovasi dalam pelayanan.</li><li><strong>E</strong>: Setiap pegawai pemerintah terikat secara mutlak pada aturan disiplin dinas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 18) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>employer branding #BanggaMelayaniBangsa • profesi yang bangga dalam melayani bangsa</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Salah satu alasan mendasar mengapa profesi ASN patut dibanggakan oleh setiap pegawai adalah karena...", "options": ["Diberi pengakuan dan penghargaan yang adil", "Diberikan wewenang mutlak tanpa mekanisme kontrol", "Mendapat kebebasan menentukan jam kerja kantor sendiri", "Dibebaskan dari kewajiban mengikuti pelatihan dinas", "Memperoleh fasilitas setara pimpinan unit sejak awal"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Apresiasi Adil bagi Profesi ASN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Profesi ASN patut dibanggakan antara lain karena para aparatur <strong>diberi pengakuan dan penghargaan yang adil</strong> atas pengabdian dan kinerjanya.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat mengenai hakikat perlakuan meritokrasi bagi aparatur sipil negara. Pengakuan atas kontribusi kerja memotivasi pegawai untuk memberikan pelayanan terbaik bagi kemajuan bangsa. Ketentuan resmi menjelaskan secara eksplisit bahwa profesi ASN patut dibanggakan karena ASN diberi pengakuan dan penghargaan yang adil. Oleh karena itu, pengakuan dan penghargaan yang adil menjadi salah satu pilar kebanggaan tersebut.</p><p>Jawaban tepat: <strong>A. Diberi pengakuan dan penghargaan yang adil</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kewenangan jabatan senantiasa dibatasi oleh sistem checks and balances.</li><li><strong>C</strong>: Jam dinas aparatur sipil negara diatur seragam oleh regulasi pemerintah.</li><li><strong>D</strong>: Setiap pegawai aparatur berhak sekaligus wajib mengembangkan kompetensinya.</li><li><strong>E</strong>: Fasilitas dinas dialokasikan secara proporsional sesuai tingkat jabatan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 18) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Profesi ASN patut dibanggakan • pengakuan dan penghargaan yang adil</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C4 - Analisis", "question": "Berikut ini adalah alasan mengapa profesi ASN patut dibanggakan dalam tata kelola aparatur negara, KECUALI...", "options": ["Diberi pengakuan dan penghargaan yang adil", "Diberi kesempatan meningkatkan kompetensi seluasnya", "Diberi kesempatan terbuka untuk berkarier", "Diberi ruang untuk mengabdi kepada bangsa", "Diberi kebebasan bekerja tanpa penilaian kinerja"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Alasan Profesi ASN Patut Dibanggakan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Tiga alasan profesi ASN patut dibanggakan bagi insan aparatur adalah: (1) <strong>diberi pengakuan dan penghargaan yang adil</strong>, (2) <strong>diberi kesempatan meningkatkan kompetensi seluas-luasnya</strong>, dan (3) <strong>diberi kesempatan terbuka untuk berkarier</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat karena kebebasan tanpa penilaian kinerja bertentangan dengan prinsip meritokrasi aparatur sipil negara. Seluruh aparatur sipil negara justru wajib dinilai kinerjanya secara terukur dan transparan. Pedoman etika hanya menyebutkan tiga poin pengakuan adil, kompetensi luas, dan karier terbuka. Dengan demikian, opsi E merupakan pernyataan yang bukan bagian dari alasan resmi kebanggaan profesi ASN.</p><p>Jawaban tepat: <strong>E. Diberi kebebasan bekerja tanpa penilaian kinerja</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Disebutkan secara eksplisit secara eksplisit sebagai alasan pertama kebanggaan ASN.</li><li><strong>B</strong>: Disebutkan secara eksplisit secara eksplisit sebagai alasan kedua pengembangan diri.</li><li><strong>C</strong>: Disebutkan secara eksplisit secara eksplisit sebagai alasan ketiga kepastian karier.</li><li><strong>D</strong>: Merupakan intisari dari semangat employer branding melayani bangsa.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 18) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>kesempatan meningkatkan kompetensi seluas-luasnya • kesempatan terbuka untuk berkarier</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Sebagai wujud kebanggaan berprofesi, seorang pegawai menyeimbangkan harapan organisasi terhadap dirinya melalui upaya...", "options": ["Mengurangi komunikasi koordinatif dengan rekan sekerja", "Terus meningkatkan kinerja secara terus menerus", "Menyederhanakan kriteria kelulusan layanan masyarakat", "Membatasi pemanfaatan sarana teknologi informasi dinas", "Menunda tindak lanjut perbaikan sistem pelayanan loket"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Keseimbangan Ekspektasi Kinerja ASN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dengan kebanggaan berprofesi, ASN diharapkan mampu menyeimbangkan ekspektasi organisasi dengan <strong>terus meningkatkan kinerja secara terus menerus</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat sesuai tanggung jawab profesional abdi negara dalam merespons kepercayaan instansi. Peningkatan mutu kerja yang konsisten memastikan organisasi mampu mencapai target pelayanan publik secara optimal. Keseimbangan ekspektasi organisasi diwujudkan secara nyata melalui peningkatan kinerja aparatur yang berkesinambungan. Oleh karena itu, peningkatan kinerja terus-menerus adalah wujud nyatanya.</p><p>Jawaban tepat: <strong>B. Terus meningkatkan kinerja secara terus menerus</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Koordinasi kerja antarsesama rekan harus terus diperkuat melalui nilai Sinergi.</li><li><strong>C</strong>: Kriteria pelayanan masyarakat wajib mengacu pada standar mutu yang telah disahkan.</li><li><strong>D</strong>: Pemanfaatan teknologi harus dioptimalkan untuk efisiensi birokrasi pemerintahan.</li><li><strong>E</strong>: Tindak lanjut perbaikan harus dilakukan dengan cepat sesuai nilai Pelayanan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 18) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menyeimbangkan harapan dan ekspektasi organisasi • meningkatkan kinerja secara terus menerus</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Nilai Dasar ASN dan Nilai-Nilai Kementerian Keuangan saling mendukung karena memiliki konsep dasar yang sama, yaitu agar pegawai...", "options": ["Menerima kompensasi finansial tertinggi di sektor publik", "Mendapat kebebasan penuh merumuskan program dinas unit", "Memiliki wewenang ganda dalam pemeriksaan keuangan negara", "Mengelola pribadi yang selaras dengan tujuan tugasnya", "Mewakili organisasi dalam seluruh perundingan diplomatik"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kesamaan Konsep Nilai ASN dan Kemenkeu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Nilai Dasar ASN dan Nilai Kemenkeu saling mendukung karena memiliki konsep dasar yang sama, yaitu agar pegawai dapat <strong>mengelola pribadi yang selaras dengan tujuan dari pelaksanaan tugasnya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat mengenai benang merah yang menyatukan kedua tata nilai tersebut. Keselarasan watak dan komitmen pribadi pegawai dengan arah tujuan institusi merupakan kunci keberhasilan pelayanan negara. Dalam keselarasan nilai, kedua nilai ini saling mendukung karena bertujuan agar ASN atau pegawai dapat mengelola pribadi yang selaras dengan tujuan pelaksanaan tugasnya. Dengan demikian, pengelolaan pribadi selaras tujuan menjadi konsep dasarnya.</p><p>Jawaban tepat: <strong>D. Mengelola pribadi yang selaras dengan tujuan tugasnya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Tingkat remunerasi aparatur ditetapkan menurut kemampuan keuangan negara.</li><li><strong>B</strong>: Penyusunan rencana kerja wajib selaras dengan rencana strategis kementerian.</li><li><strong>C</strong>: Pemeriksaan keuangan negara merupakan wewenang aparat pengawas yang berwenang.</li><li><strong>E</strong>: Perundingan luar negeri merupakan mandat diplomatik dari kementerian terkait.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 18) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>dua nilai yang dapat saling mendukung • mengelola pribadi yang selaras dengan tujuan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Kementerian PANRB mengharapkan agar dalam peluncuran Nilai Dasar BerAKHLAK, nilai-nilai yang telah ada di instansi...", "options": ["Dihapus seluruhnya dan digantikan regulasi baru", "Diubah total mengikuti susunan abjad kata BerAKHLAK", "Tidak dihadapkan sebagai sesuatu yang bertentangan", "Dibatasi penerapannya khusus pada kantor perwakilan", "Diberhentikan masa berlakunya melalui peraturan menteri"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Arahan Kementerian PANRB tentang Nilai Instansi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kementerian PANRB mengharapkan agar nilai-nilai yang telah ada di kementerian/lembaga <strong>tidak dihadapkan dengan Nilai-nilai Dasar ASN BerAKHLAK sebagai sesuatu yang bertentangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat mengenai arahan harmonisasi budaya kerja nasional dengan kementerian/lembaga. Kehadiran nilai BerAKHLAK dimaksudkan untuk menguatkan nilai-nilai luhur yang sudah bertumbuh di masing-masing instansi. Secara jelas ditegaskan harapan Kementerian PANRB agar dalam peluncuran BerAKHLAK tidak dihadapkan dengan nilai yang telah ada sebagai sesuatu yang bertentangan. Oleh sebab itu, tiadanya pertentangan nilai menjadi sikap resminya.</p><p>Jawaban tepat: <strong>C. Tidak dihadapkan sebagai sesuatu yang bertentangan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Nilai-nilai luhur instansi yang relevan tetap dipertahankan dan dihidupkan.</li><li><strong>B</strong>: Tidak ada instruksi perombakan paksa terhadap istilah budaya kerja yang selaras.</li><li><strong>D</strong>: Tata nilai berlaku mengikat di seluruh tingkatan unit organisasi kementerian.</li><li><strong>E</strong>: Nilai yang sudah eksis tetap memiliki legitimasi penguat budaya kerja organisasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 18) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Kementerian PAN RB sendiri mengharapkan • sesuatu yang bertentangan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Meskipun BerAKHLAK menjadi acuan utama nasional, nilai-nilai yang sudah ada di kementerian/lembaga kedudukannya...", "options": ["Substansinya tetap hidup dan memperkuat nilai BerAKHLAK", "Kehilangan dasar kekuatan hukum dalam pelaksanaan dinas", "Beralih fungsi menjadi acuan pembagian fasilitas kerja", "Berlaku terbatas bagi pejabat tinggi madya dan pratama", "Disesuaikan kembali melalui voting terbuka seluruh pegawai"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kedudukan Nilai Instansi terhadap BerAKHLAK</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Nilai-nilai Dasar ASN BerAKHLAK tetap menjadi acuan, namun nilai-nilai yang sudah ada di kementerian/lembaga <strong>substansinya tetap hidup dan memperkuat nilai BerAKHLAK tersebut</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat mengenai hubungan komplementer antara nilai instansi dengan nilai BerAKHLAK. Budaya kerja yang telah matang justru menjadi katalisator bagi percepatan internalisasi nilai BerAKHLAK di lapangan. Dalam panduan perilaku dirumuskan bahwa nilai-nilai yang sudah ada di kementerian/lembaga substansinya tetap hidup dan memperkuat nilai BerAKHLAK. Dengan demikian, keberadaan nilai yang ada saling memperkokoh satu sama lain.</p><p>Jawaban tepat: <strong>A. Substansinya tetap hidup dan memperkuat nilai BerAKHLAK</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Keputusan menteri tentang nilai instansi tetap mempunyai kekuatan hukum sah.</li><li><strong>C</strong>: Nilai organisasi merupakan pedoman perilaku etis, bukan instrumen fasilitas.</li><li><strong>D</strong>: Nilai instansi mengikat seluruh pegawai tanpa membedakan tingkatan eselon.</li><li><strong>E</strong>: Tata nilai ditetapkan oleh pimpinan negara dan institusi melalui kajian formal.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 18) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>substansinya tetap hidup • memperkuat nilai BerAKHLAK</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Budaya BerAKHLAK pada prinsipnya selaras dengan Nilai-Nilai Kementerian Keuangan sehingga...", "options": ["Memerlukan pembentukan kementerian koordinasi baru", "Meniadakan lima nilai kementerian yang telah ditetapkan", "Memisahkan tugas pegawai pusat dan aparatur daerah", "Mewajibkan penerbitan undang-undang kepegawaian baru", "Dapat masuk dan berpadu selaras di dalamnya"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Keselarasan Budaya BerAKHLAK dan Kemenkeu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Budaya BerAKHLAK pada prinsipnya <strong>sejalan dengan Nilai-nilai Kementerian Keuangan sehingga dapat masuk di dalamnya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat mengenai kemudahan integrasi kedua sistem nilai tersebut. Karena fondasi etik keduanya sama, BerAKHLAK dapat langsung menyatu dengan budaya kerja Kementerian Keuangan tanpa hambatan friksi konseptual. Prinsip organisasi menyatakan bahwa Budaya BerAKHLAK pada prinsipnya sejalan dengan Nilai-nilai Kementerian Keuangan sehingga dapat masuk di dalamnya. Oleh sebab itu, perpaduan yang selaras menjadi konsekuensi logisnya.</p><p>Jawaban tepat: <strong>E. Dapat masuk dan berpadu selaras di dalamnya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Integrasi nilai berjalan secara kultural tanpa perlu kelembagaan kementerian baru.</li><li><strong>B</strong>: Lima nilai Kementerian Keuangan tetap kokoh dan saling melengkapi dengan BerAKHLAK.</li><li><strong>C</strong>: Prinsip BerAKHLAK menyatukan pegawai pusat dan daerah dalam standar yang sama.</li><li><strong>D</strong>: Landasan hukum integrasi nilai telah dipayungi oleh peraturan perundangan yang ada.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 18) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Budaya BerAKHLAK pada prinsipnya sejalan • sehingga dapat masuk di dalamnya</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Dalam praktik kerja di lingkungan Kementerian Keuangan, hal utama yang wajib menjadi fokus perhatian pegawai adalah...", "options": ["Penyelarasan target penerimaan pajak antardaerah", "Keselarasan implementasi Nilai Dasar ASN dan Kemenkeu", "Perbedaan besaran insentif fiskal antarsektor industri", "Pembatasan pengadaan perangkat komputer kantor", "Penyeragaman motif batik dinas pada seluruh kantor"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Fokus Perhatian Implementasi Tata Nilai</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Hal yang perlu menjadi perhatian utama adalah <strong>bagaimana keselarasan implementasi Nilai Dasar ASN dengan Nilai-nilai Kementerian Keuangan</strong> dalam aktivitas kedinasan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat sesuai penekanan etika bagi seluruh jajaran pegawai. Memahami korelasi antara butir BerAKHLAK dan lima nilai kementerian menjamin sikap dan tindakan aparatur tetap berada di jalur yang benar. Secara jelas ditekankan bahwa dengan demikian, yang perlu menjadi perhatian adalah bagaimana keselarasan implementasi Nilai Dasar ASN dengan Nilai-nilai Kementerian Keuangan. Dengan demikian, keselarasan implementasi menjadi fokus utama.</p><p>Jawaban tepat: <strong>B. Keselarasan implementasi Nilai Dasar ASN dan Kemenkeu</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Target penerimaan pajak merupakan sasaran teknis kuantitatif pada rencana anggaran.</li><li><strong>C</strong>: Kebijakan insentif fiskal diatur melalui regulasi makroekonomi pemerintah.</li><li><strong>D</strong>: Pengadaan peralatan kantor merupakan ranah logistik dan rumah tangga kantor.</li><li><strong>E</strong>: Pakaian dinas merupakan tata tertib atribut fisik, bukan substansi keselarasan nilai.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 18) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>yang perlu menjadi perhatian adalah • keselarasan implementasi Nilai Dasar ASN</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Penjelasan mengenai hubungan selaras antara komponen Nilai Dasar ASN dan Nilai Kementerian Keuangan dipaparkan melalui konsep...", "options": ["Sistem Informasi Tata Naskah Kepegawaian", "Mekanisme Pemantauan Kinerja Strategis Kantor", "Kerangka Regulasi Standar Operasional Pelayanan", "Ekosistem Budaya Kementerian Keuangan", "Struktur Hubungan Kelembagaan Vertikal Instansi"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Ekosistem Budaya Kementerian Keuangan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Hubungan yang selaras antara komponen Nilai Dasar ASN dan Nilai-Nilai Kementerian Keuangan dijelaskan melalui <strong>Ekosistem Budaya Kementerian Keuangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat mengenai wadah konseptual keselarasan tata nilai di kementerian. Pendekatan ekosistem memetakan interaksi nilai nasional dan nilai institusi ke dalam satu kesatuan budaya kerja yang harmonis. Dalam kerangka Ekosistem Budaya Kementerian Keuangan, terdapat hubungan yang selaras dan saling menguatkan antara kedua sistem nilai. Oleh sebab itu, konsep Ekosistem Budaya Kementerian Keuangan menjadi jawabannya.</p><p>Jawaban tepat: <strong>D. Ekosistem Budaya Kementerian Keuangan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan aplikasi administrasi surat-menyurat dinas dan arsip kepegawaian.</li><li><strong>B</strong>: Merupakan instrumen monitoring target sasaran kinerja pegawai pada organisasi.</li><li><strong>C</strong>: Merupakan panduan operasional prosedur teknis pelayanan pada meja bantuan.</li><li><strong>E</strong>: Merupakan tata susun pembagian garis hierarki pada bagan organisasi instansi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 18) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Ekosistem Budaya Kementerian Keuangan • hubungan yang selaras</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Perpaduan Core Values ASN BerAKHLAK Akuntabel dan Loyal selaras dengan nilai Kementerian Keuangan yaitu...", "options": ["Integritas", "Profesionalisme", "Sinergi", "Pelayanan", "Kesempurnaan"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Keterkaitan Akuntabel-Loyal dengan Integritas</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam keterkaitan Core Values ASN BerAKHLAK dan Nilai-nilai Kemenkeu, nilai <strong>Akuntabel dan Loyal</strong> berpadanan secara langsung dengan nilai <strong>Integritas</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat sesuai pemetaan resmi pada penyelarasan nilai BerAKHLAK dan Nilai Kemenkeu. Kejujuran bertindak (akuntabel) serta keteguhan memegang sumpah jabatan (loyal) merupakan intisari watak berintegritas. Dalam keterkaitan nilai, secara jelas dipadankan bahwa nilai Akuntabel dan Loyal berpasangan dengan Integritas. Dengan demikian, nilai Integritas merupakan padanan yang tepat bagi kedua nilai ASN tersebut.</p><p>Jawaban tepat: <strong>A. Integritas</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Berpasangan dengan Core Value Kompeten dalam pemetaan penyelarasan nilai.</li><li><strong>C</strong>: Berpasangan dengan gabungan Core Values Harmonis dan Kolaboratif.</li><li><strong>D</strong>: Berpasangan dengan Core Value Berorientasi Pelayanan.</li><li><strong>E</strong>: Berpasangan dengan Core Value Adaptif dalam pemetaan penyelarasan nilai.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 19) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Akuntabel dan Loyal • Integritas</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Dalam penyelarasan Core Values ASN BerAKHLAK dan Nilai-nilai Kemenkeu, nilai Profesionalisme berpadanan langsung dengan...", "options": ["Adaptif", "Kompeten", "Harmonis", "Akuntabel", "Kolaboratif"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Keterkaitan Kompeten dengan Profesionalisme</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Nilai Kementerian Keuangan <strong>Profesionalisme</strong> memiliki keterkaitan langsung dengan Core Value ASN <strong>Kompeten</strong> dalam penyelarasan nilai kementerian.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat sesuai matriks korelasi budaya kerja kementerian. Kemahiran bekerja secara tuntas dan andal (profesionalisme) berakar kuat pada penguasaan keahlian (kompeten). Dalam penyelarasan nilai ASN dan Kementerian Keuangan, komponen Kompeten berpadanan secara tepat dengan Profesionalisme. Oleh sebab itu, Core Value Kompeten merupakan pasangan yang benar.</p><p>Jawaban tepat: <strong>B. Kompeten</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Berpadanan dengan nilai Kesempurnaan dalam kerangka inovasi berkelanjutan.</li><li><strong>C</strong>: Bersama nilai Kolaboratif membentuk keselarasan dengan nilai Sinergi.</li><li><strong>D</strong>: Bersama nilai Loyal membentuk keselarasan dengan nilai Integritas.</li><li><strong>E</strong>: Bersama nilai Harmonis membentuk keselarasan dengan nilai Sinergi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 19) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Kompeten • Profesionalisme</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Nilai Kementerian Keuangan Sinergi memiliki keselarasan langsung dengan gabungan Core Values ASN yaitu...", "options": ["Akuntabel dan Adaptif", "Kompeten dan Loyal", "Harmonis dan Kolaboratif", "Berorientasi Pelayanan dan Adaptif", "Akuntabel dan Kolaboratif"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Keterkaitan Harmonis-Kolaboratif dengan Sinergi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Nilai Kementerian Keuangan <strong>Sinergi</strong> selaras dengan perpaduan Core Values ASN <strong>Harmonis dan Kolaboratif</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat mengenai kombinasi nilai ASN yang membentuk nilai Sinergi. Suasana saling menghargai (harmonis) yang dipadukan dengan keterbukaan bekerja sama (kolaboratif) merupakan prasyarat mutlak terciptanya sinergi organisasi. Dalam keselarasan nilai organisasi, Harmonis dan Kolaboratif dipetakan selaras dengan Sinergi. Dengan demikian, opsi C memuat pasangan yang tepat.</p><p>Jawaban tepat: <strong>C. Harmonis dan Kolaboratif</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Bukan merupakan pasangan terpadu pada matriks nilai penyelarasan nilai.</li><li><strong>B</strong>: Bukan merupakan gabungan nilai yang dipadankan dalam pedoman etika.</li><li><strong>D</strong>: Berorientasi Pelayanan berpasangan dengan Pelayanan, Adaptif dengan Kesempurnaan.</li><li><strong>E</strong>: Akuntabel berpasangan dengan Integritas bersama nilai Loyal.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 19) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Harmonis dan Kolaboratif • Sinergi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Core Value ASN Berorientasi Pelayanan memiliki padanan langsung pada Nilai-Nilai Kementerian Keuangan yaitu...", "options": ["Integritas", "Profesionalisme", "Sinergi", "Pelayanan", "Kesempurnaan"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Keterkaitan Berorientasi Pelayanan dengan Pelayanan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Core Value ASN <strong>Berorientasi Pelayanan</strong> berpadanan langsung dengan nilai Kementerian Keuangan <strong>Pelayanan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat sesuai baris keempat pada penyelarasan nilai BerAKHLAK dan Nilai Kemenkeu. Komitmen memberikan pelayanan terbaik bagi kepuasan masyarakat menjadi fokus utama dari kedua konsep ini. Dalam penyelarasan nilai BerAKHLAK dan Kemenkeu, Berorientasi Pelayanan memiliki hubungan selaras dengan Pelayanan. Oleh karena itu, nilai Pelayanan merupakan pasangan yang benar.</p><p>Jawaban tepat: <strong>D. Pelayanan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan padanan bagi gabungan nilai Akuntabel dan Loyal.</li><li><strong>B</strong>: Merupakan padanan bagi Core Value ASN Kompeten.</li><li><strong>C</strong>: Merupakan padanan bagi perpaduan nilai Harmonis dan Kolaboratif.</li><li><strong>E</strong>: Merupakan padanan bagi Core Value ASN Adaptif.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 19) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Berorientasi Pelayanan • Pelayanan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Nilai Kementerian Keuangan Kesempurnaan memiliki keselarasan langsung dengan Core Value ASN yaitu...", "options": ["Loyal", "Kompeten", "Harmonis", "Akuntabel", "Adaptif"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Keterkaitan Adaptif dengan Kesempurnaan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Nilai Kementerian Keuangan <strong>Kesempurnaan</strong> memiliki keselarasan langsung dengan Core Value ASN <strong>Adaptif</strong> dalam penyelarasan nilai kementerian.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat mengenai padanan nilai ASN bagi perbaikan tanpa henti dan inovasi kerja. Kemampuan menyesuaikan diri dan berinovasi (adaptif) merupakan wujud nyata ikhtiar memberikan hasil kerja terbaik (kesempurnaan). Dalam penyelarasan nilai tertulis tegas hubungan antara Adaptif dengan Kesempurnaan. Dengan demikian, Core Value Adaptif merupakan padanannya.</p><p>Jawaban tepat: <strong>E. Adaptif</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Bersama nilai Akuntabel berpadanan dengan nilai Integritas.</li><li><strong>B</strong>: Berpadanan dengan nilai Profesionalisme dalam penyelarasan nilai.</li><li><strong>C</strong>: Bersama nilai Kolaboratif berpadanan dengan nilai Sinergi.</li><li><strong>D</strong>: Bersama nilai Loyal berpadanan dengan nilai Integritas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 19) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Adaptif • Kesempurnaan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Seorang bendahara mencatat pengeluaran secara jujur serta teguh menjaga rahasia dinas, membuktikan keselarasan nilai ASN dengan nilai Kemenkeu...", "options": ["Integritas", "Profesionalisme", "Sinergi", "Pelayanan", "Kesempurnaan"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penerapan Keselarasan Integritas</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Perilaku jujur bertanggung jawab (Akuntabel) dan menjaga rahasia dinas (Loyal) secara simultan mengaktualisasikan nilai Kementerian Keuangan <strong>Integritas</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat sesuai ilustrasi pengamalan tugas oleh bendahara tersebut. Nilai Akuntabel tecermin dari pencatatan jujur, sedangkan nilai Loyal tecermin dari penjagaan rahasia dinas. Dalam penyelarasan nilai ASN dan kementerian, kedua perilaku ASN tersebut bermuara pada nilai Integritas Kementerian Keuangan. Oleh sebab itu, nilai Integritas merupakan nilai yang terbukti dalam perilaku tersebut.</p><p>Jawaban tepat: <strong>A. Integritas</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Berfokus pada penyelesaian tugas berlandaskan kompetensi dan keahlian tinggi.</li><li><strong>C</strong>: Berfokus pada koordinasi kemitraan produktif dan hubungan kerja harmonis.</li><li><strong>D</strong>: Berfokus pada kecepatan, transparansi, dan keramahan pemenuhan layanan publik.</li><li><strong>E</strong>: Berfokus pada ikhtiar perbaikan berkelanjutan dan kreativitas inovasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 19) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Akuntabel dan Loyal • Tabel 1: Keterkaitan Core Values ASN dan Nilai-nilai Kemenkeu</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Pemeriksa pajak yang memperdalam ilmu akuntansi forensik demi menyelesaikan audit secara tuntas mencerminkan keselarasan nilai...", "options": ["Akuntabel dan Sinergi", "Kompeten dan Profesionalisme", "Harmonis dan Integritas", "Adaptif dan Pelayanan", "Loyal dan Kesempurnaan"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penerapan Keselarasan Profesionalisme</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Peningkatan keahlian secara tekun (Kompeten) guna menyelesaikan tugas tuntas dan akurat mencerminkan keselarasan antara nilai <strong>Kompeten dan Profesionalisme</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat sesuai hubungan langsung antara penguasaan ilmu dengan ketuntasan tugas. Tindakan memperdalam ilmu mewakili nilai Kompeten, sementara penyelesaian audit secara tuntas mewakili nilai Profesionalisme. Dalam pemetaan nilai kementerian, nilai Kompeten berpasangan langsung dengan Profesionalisme. Dengan demikian, kedua nilai tersebut selaras secara sempurna.</p><p>Jawaban tepat: <strong>B. Kompeten dan Profesionalisme</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Bukan merupakan pasangan keselarasan yang tercantum pada penyelarasan nilai pedoman kerja.</li><li><strong>C</strong>: Harmonis berpasangan dengan Sinergi, bukan Integritas.</li><li><strong>D</strong>: Adaptif berpasangan dengan Kesempurnaan, sedangkan Pelayanan dengan Berorientasi Pelayanan.</li><li><strong>E</strong>: Loyal berpasangan dengan Integritas bersama nilai Akuntabel.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 19) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Kompeten • Tabel 1: Keterkaitan Core Values ASN dan Nilai-nilai Kemenkeu</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Dua seksi pelayanan bekerja sama secara rukun dan terbuka demi mempercepat penyelesaian izin usaha, mencerminkan keselarasan nilai...", "options": ["Akuntabel, Loyal, dan Integritas", "Kompeten, Adaptif, dan Profesionalisme", "Harmonis, Kolaboratif, dan Sinergi", "Berorientasi Pelayanan, Loyal, dan Pelayanan", "Adaptif, Kompeten, dan Kesempurnaan"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penerapan Keselarasan Sinergi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kerja sama rukun (Harmonis) dan terbuka untuk nilai tambah bersama (Kolaboratif) merupakan wujud keselarasan dengan nilai Kementerian Keuangan <strong>Sinergi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat sesuai hubungan timbal balik antara suasana rukun dan kerja sama produktif. Kerukunan antarseksi mencerminkan nilai Harmonis, keterbukaan kerja sama mencerminkan nilai Kolaboratif, dan hasil sinergisnya mewujudkan nilai Sinergi. Dalam penyelarasan nilai, Harmonis dan Kolaboratif dipetakan selaras dengan nilai Sinergi. Oleh karena itu, ketiga nilai pada opsi C adalah wujud keselarasan yang tepat.</p><p>Jawaban tepat: <strong>C. Harmonis, Kolaboratif, dan Sinergi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan rumpun nilai kejujuran, kepatuhan norma, dan kesetiaan dinas.</li><li><strong>B</strong>: Merupakan rumpun nilai penguasaan kecakapan dan ketuntasan tugas teknis.</li><li><strong>D</strong>: Menggabungkan nilai Loyal yang tidak termasuk dalam rumpun pelayanan.</li><li><strong>E</strong>: Merupakan kombinasi nilai yang tidak selaras dengan pemetaan penyelarasan nilai.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 19) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Harmonis dan Kolaboratif • Nilai-nilai Kemenkeu</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Petugas loket menyapa pemohon dengan ramah serta memproses berkas secara cepat dan akurat, mencerminkan keselarasan nilai...", "options": ["Kompeten dan Sinergi", "Akuntabel dan Kesempurnaan", "Harmonis dan Profesionalisme", "Berorientasi Pelayanan dan Pelayanan", "Adaptif dan Integritas"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penerapan Keselarasan Pelayanan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sikap ramah dan cekatan memenuhi kebutuhan pemangku kepentingan secara transparan merupakan wujud keselarasan nilai <strong>Berorientasi Pelayanan dan Pelayanan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat sesuai perpaduan standar perilaku abdi negara dengan nilai kementerian. Menyapa ramah mencerminkan nilai Berorientasi Pelayanan ASN, sedangkan kecepatan dan keakuratan mencerminkan nilai Pelayanan Kementerian Keuangan. Penyelarasan nilai merumuskan bahwa Berorientasi Pelayanan berpasangan secara langsung dengan nilai Pelayanan. Dengan demikian, opsi D mencerminkan keselarasan tersebut.</p><p>Jawaban tepat: <strong>D. Berorientasi Pelayanan dan Pelayanan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kompeten berpasangan dengan Profesionalisme, sedangkan Sinergi dengan Harmonis-Kolaboratif.</li><li><strong>B</strong>: Akuntabel berpasangan dengan Integritas, sedangkan Kesempurnaan dengan Adaptif.</li><li><strong>C</strong>: Harmonis berpasangan dengan Sinergi, sedangkan Profesionalisme dengan Kompeten.</li><li><strong>E</strong>: Adaptif berpasangan dengan Kesempurnaan, sedangkan Integritas dengan Akuntabel-Loyal.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 19) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Berorientasi Pelayanan • Tabel 1: Keterkaitan Core Values ASN dan Nilai-nilai Kemenkeu</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C4 - Analisis", "question": "Pasangan penyelarasan nilai BerAKHLAK dan Nilai Kementerian Keuangan yang TIDAK TEPAT adalah...", "options": ["Akuntabel dan Loyal berpasangan dengan Integritas", "Kompeten berpasangan dengan Profesionalisme", "Harmonis dan Kolaboratif berpasangan dengan Sinergi", "Berorientasi Pelayanan berpasangan dengan Pelayanan", "Adaptif berpasangan dengan Profesionalisme"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Analisis Keterkaitan Tabel 1</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sesuai penyelarasan nilai pedoman, <strong>Adaptif</strong> berpasangan dengan nilai <strong>Kesempurnaan</strong>, bukan dengan Profesionalisme.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat karena memuat pasangan yang keliru dalam penyelarasan nilai. Dalam penyelarasan tersebut, Core Value Adaptif dipasangkan dengan nilai Kesempurnaan, sedangkan Profesionalisme berpasangan dengan Kompeten. Seluruh pasangan pada opsi A, B, C, dan D telah sesuai dengan rumusan resmi penyelarasan nilai. Oleh karena itu, opsi E merupakan pernyataan yang tidak tepat.</p><p>Jawaban tepat: <strong>E. Adaptif berpasangan dengan Profesionalisme</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan pasangan baris pertama yang tepat pada penyelarasan nilai pedoman kerja.</li><li><strong>B</strong>: Merupakan pasangan baris kedua yang tepat pada penyelarasan nilai pedoman kerja.</li><li><strong>C</strong>: Merupakan pasangan baris ketiga yang tepat pada penyelarasan nilai pedoman kerja.</li><li><strong>D</strong>: Merupakan pasangan baris keempat yang tepat pada penyelarasan nilai pedoman kerja.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 19) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Core Values ASN (BerAKHLAK) • Tabel 1: Keterkaitan Core Values ASN dan Nilai-nilai Kemenkeu</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Penetapan resmi mengenai Nilai-Nilai Kementerian Keuangan beserta rinciannya diatur dalam...", "options": ["Peraturan Pemerintah Nomor 42 Tahun 2004", "Keputusan Menteri Keuangan Nomor 312/KMK.01/2011", "Surat Edaran Bersama Menteri Keuangan dan Kepala BKN", "Peraturan Presiden Nomor 12 Tahun 2021", "Undang-Undang Nomor 17 Tahun 2003"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dasar Hukum Nilai-Nilai Kementerian Keuangan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Nilai-Nilai Kementerian Keuangan mencakup lima nilai yang ditetapkan resmi berdasarkan <strong>Keputusan Menteri Keuangan Nomor 312/KMK.01/2011</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat mengenai dasar hukum sah berlakunya Nilai-Nilai Kementerian Keuangan. Regulasi menteri ini menjadi pedoman perilaku baku bagi seluruh jajaran pegawai di pusat maupun daerah. Nilai-Nilai Kementerian Keuangan ditetapkan sesuai Keputusan Menteri Keuangan Nomor 312/KMK.01/2011. Dengan demikian, KMK Nomor 312/KMK.01/2011 adalah landasan hukum resminya.</p><p>Jawaban tepat: <strong>B. Keputusan Menteri Keuangan Nomor 312/KMK.01/2011</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan Peraturan Pemerintah mengenai pembinaan jiwa korps dan kode etik PNS.</li><li><strong>C</strong>: Bukan merupakan surat edaran penetapan resmi lima nilai Kementerian Keuangan.</li><li><strong>D</strong>: Merupakan Peraturan Presiden tentang pengadaan barang dan jasa pemerintah.</li><li><strong>E</strong>: Merupakan undang-undang induk tentang pengelolaan Keuangan Negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 20) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Keputusan Menteri Keuangan Nomor 312/KMK.01/2011 • Nilai-Nilai Kementerian Keuangan mencakup 5 nilai</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Sebagai organisasi modern yang terus berkembang, Kementerian Keuangan menggerakkan organisasinya dengan mengadopsi...", "options": ["Standar kompensasi perusahaan multinasional swasta", "Sistem komando tunggal kemiliteran yang tertutup", "Metode pembagian kerja berdasarkan senioritas pegawai", "Praktik-praktik manajemen yang sehat (best practices)", "Kebijakan promosi instan tanpa uji kompetensi dinas"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penerapan Best Practices Manajemen Kemenkeu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kementerian Keuangan sebagai organisasi modern, maju, dan terus berkembang mengadopsi serta mengadaptasi <strong>praktik-praktik manajemen yang sehat (<em>best practices</em>)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat mengenai metode pengelolaan kelembagaan yang dijalankan oleh Kementerian Keuangan. Penerapan praktik tata kelola unggul memastikan pelayanan publik dan pengelolaan fiskal berlangsung efektif dan transparan. Pedoman etika merumuskan bahwa Kementerian Keuangan telah mengadopsi dan mengadaptasi praktik-praktik manajemen yang sehat (best practices). Oleh karena itu, praktik manajemen sehat menjadi pendekatan utamanya.</p><p>Jawaban tepat: <strong>D. Praktik-praktik manajemen yang sehat (best practices)</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kompensasi aparatur negara diatur secara resmi melalui regulasi keuangan negara.</li><li><strong>B</strong>: Tata kelola birokrasi sipil bertumpu pada profesionalitas dan sistem merit dinas.</li><li><strong>C</strong>: Penempatan pegawai mengacu pada analisis jabatan dan kompetensi keahlian.</li><li><strong>E</strong>: Promosi aparatur sipil negara wajib melewati penilaian kinerja dan rekam jejak integritas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 20) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>organisasi modern, maju dan terus berkembang • praktik-praktik manajemen yang sehat (best practices)</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan Keputusan Menteri Keuangan Nomor 312/KMK.01/2011, Nilai-Nilai Kementerian Keuangan mencakup...", "options": ["5 nilai utama", "7 nilai utama", "4 nilai utama", "6 nilai utama", "3 nilai utama"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Jumlah Nilai Resmi Kementerian Keuangan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sesuai KMK Nomor 312/KMK.01/2011, Nilai-Nilai Kementerian Keuangan mencakup <strong>5 nilai yakni: Integritas, Profesionalisme, Sinergi, Pelayanan dan Kesempurnaan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat mengenai jumlah nilai resmi yang ditetapkan kementerian. Kelima pilar tersebut mengikat seluruh pegawai dalam berpikir, bertindak, dan berperilaku secara profesional. Secara resmi dinyatakan bahwa sesuai Keputusan Menteri Keuangan Nomor 312/KMK.01/2011, Nilai-Nilai Kementerian Keuangan mencakup 5 nilai yakni: Integritas, Profesionalisme, Sinergi, Pelayanan dan Kesempurnaan. Oleh karena itu, 5 nilai utama adalah jumlah yang tepat.</p><p>Jawaban tepat: <strong>A. 5 nilai utama</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan jumlah nilai dasar pada core values nasional ASN BerAKHLAK.</li><li><strong>C</strong>: Kurang tepat karena pedoman kerja menetapkan lima nilai secara utuh.</li><li><strong>D</strong>: Bukan merupakan jumlah pilar budaya kerja Kementerian Keuangan.</li><li><strong>E</strong>: Terlalu sedikit dan tidak mencakup seluruh nilai resmi kementerian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 20) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Nilai-Nilai Kementerian Keuangan mencakup 5 nilai • Integritas, Profesionalisme, Sinergi, Pelayanan dan Kesempurnaan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Unsur utama makna nilai Integritas menekankan sikap berpegang teguh pada...", "options": ["Kompetensi terbaik dan tanggung jawab tinggi", "Hubungan kerja sama produktif dan harmonis", "Kode etik dan prinsip-prinsip moral", "Kepuasan pemangku kepentingan secara aman", "Upaya perbaikan di segala bidang organisasi"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Unsur Moral Nilai Integritas Kemenkeu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Makna nilai <strong>Integritas</strong> adalah berpikir, berkata, berperilaku, dan bertindak dengan baik dan benar serta <strong>memegang teguh kode etik dan prinsip-prinsip moral</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat sesuai rumusan makna nilai Integritas pada perilaku utama. Integritas menyelaraskan seluruh dimensi lahiriah dan batiniah manusia dalam bingkai kebenaran serta kode etik. Dalam prinsip dasar, makna Integritas dirumuskan: berpikir, berkata, berperilaku, dan bertindak dengan baik dan benar serta memegang teguh kode etik dan prinsip-prinsip moral. Oleh sebab itu, opsi C merupakan unsur moral yang ditekankan.</p><p>Jawaban tepat: <strong>C. Kode etik dan prinsip-prinsip moral</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan penekanan pada makna nilai Profesionalisme pada panduan perilaku utama.</li><li><strong>B</strong>: Merupakan penekanan pada makna nilai Sinergi pada panduan perilaku utama.</li><li><strong>D</strong>: Merupakan penekanan pada makna nilai Pelayanan pada panduan perilaku utama.</li><li><strong>E</strong>: Merupakan penekanan pada makna nilai Kesempurnaan pada panduan perilaku utama.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 20) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Integritas • kode etik dan prinsip-prinsip moral</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Perilaku utama pertama dari nilai Integritas dalam panduan perilaku utama adalah...", "options": ["Mempunyai keahlian dan pengetahuan yang luas", "Memiliki sangka baik, saling percaya, dan saling menghormati", "Melakukan perbaikan secara terus menerus", "Melayani dengan berorientasi pada kepuasan", "Bersikap jujur, tulus, dan dapat dipercaya"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Perilaku Utama Pertama Nilai Integritas</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Perilaku utama pertama dari nilai Integritas adalah <strong>bersikap jujur, tulus, dan dapat dipercaya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat mengenai butir perilaku utama pertama pada nilai Integritas. Kejujuran yang disertai ketulusan hati menghasilkan kredibilitas diri aparatur yang diakui publik. Dalam panduan perilaku utama, butir nomor satu perilaku utama Integritas berbunyi bersikap jujur, tulus, dan dapat dipercaya. Dengan demikian, opsi E merupakan butir perilaku utama yang dimaksud.</p><p>Jawaban tepat: <strong>E. Bersikap jujur, tulus, dan dapat dipercaya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan perilaku utama pertama dari nilai Profesionalisme.</li><li><strong>B</strong>: Merupakan perilaku utama pertama dari nilai Sinergi.</li><li><strong>C</strong>: Merupakan perilaku utama pertama dari nilai Kesempurnaan.</li><li><strong>D</strong>: Merupakan perilaku utama pertama dari nilai Pelayanan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 20) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>bersikap jujur, tulus, dan dapat dipercaya • Integritas</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Perilaku utama kedua dari nilai Integritas dalam panduan perilaku utama kementerian adalah...", "options": ["Bekerja dengan hati dan panggilan jiwa", "Menjaga martabat dan tidak melakukan hal tercela", "Menemukan dan melaksanakan solusi terbaik", "Bersikap proaktif dan cepat tanggap dalam tugas", "Mengembangkan inovasi dan kreativitas baru"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Perilaku Utama Kedua Nilai Integritas</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Perilaku utama kedua dari nilai Integritas adalah <strong>menjaga martabat dan tidak melakukan hal tercela</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat mengenai butir perilaku kedua pada nilai Integritas. Menghindari perbuatan hina dan menjaga keluhuran budi merupakan benteng pertahanan moral abdi negara. Panduan perilaku utama mencatat butir nomor dua perilaku utama Integritas adalah menjaga martabat dan tidak melakukan hal tercela. Oleh karena itu, rumusan pada opsi B adalah perilaku utama yang tepat.</p><p>Jawaban tepat: <strong>B. Menjaga martabat dan tidak melakukan hal tercela</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan perilaku utama kedua pada nilai Profesionalisme (bekerja dengan hati).</li><li><strong>C</strong>: Merupakan perilaku utama kedua pada nilai Sinergi.</li><li><strong>D</strong>: Merupakan perilaku utama kedua pada nilai Pelayanan.</li><li><strong>E</strong>: Merupakan perilaku utama kedua pada nilai Kesempurnaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 20) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menjaga martabat dan tidak melakukan hal tercela • Integritas</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Makna nilai Profesionalisme dalam panduan perilaku utama menekankan pelaksanaan tugas atas dasar...", "options": ["Kepuasan pemangku kepentingan secara transparan", "Kemitraan harmonis dan kerja sama produktif", "Kode etik dan prinsip-prinsip moral kedinasan", "Kompetensi terbaik dengan komitmen yang tinggi", "Upaya perbaikan berkelanjutan di segala bidang"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Landasan Pelaksanaan Tugas Profesionalisme</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Makna nilai <strong>Profesionalisme</strong> menekankan bekerja tuntas dan akurat atas dasar <strong>kompetensi terbaik dengan penuh tanggung jawab dan komitmen yang tinggi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat sesuai definisi makna nilai Profesionalisme pada perilaku utama. Penguasaan keahlian yang disertai ketelitian dan dedikasi menghasilkan mutu output kerja yang andal. Dalam panduan perilaku dirumuskan bahwa Profesionalisme bermakna bekerja tuntas dan akurat atas dasar kompetensi terbaik dengan penuh tanggung jawab dan komitmen yang tinggi. Dengan demikian, opsi D merupakan landasan intinya.</p><p>Jawaban tepat: <strong>D. Kompetensi terbaik dengan komitmen yang tinggi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan penekanan pada makna nilai Pelayanan.</li><li><strong>B</strong>: Merupakan penekanan pada makna nilai Sinergi.</li><li><strong>C</strong>: Merupakan penekanan pada makna nilai Integritas.</li><li><strong>E</strong>: Merupakan penekanan pada makna nilai Kesempurnaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 20) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Profesionalisme • kompetensi terbaik dengan penuh tanggung jawab</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Perilaku utama pertama dari nilai Profesionalisme dalam panduan perilaku utama kementerian adalah...", "options": ["Mempunyai keahlian dan pengetahuan yang luas", "Bersikap jujur, tulus, dan dapat dipercaya", "Menemukan dan melaksanakan solusi terbaik", "Melayani dengan berorientasi pada kepuasan", "Melakukan perbaikan secara terus menerus"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Perilaku Utama Pertama Nilai Profesionalisme</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Perilaku utama pertama dari nilai Profesionalisme adalah <strong>mempunyai keahlian dan pengetahuan yang luas</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat mengenai butir perilaku utama pertama nilai Profesionalisme. Wawasan yang mendalam dan kecakapan teknis menjadi syarat mutlak bagi pegawai dalam memecahkan persoalan yang kompleks. Panduan perilaku utama menetapkan perilaku utama pertama nilai Profesionalisme berbunyi mempunyai keahlian dan pengetahuan yang luas. Oleh sebab itu, opsi A merupakan rumusan perilaku yang tepat.</p><p>Jawaban tepat: <strong>A. Mempunyai keahlian dan pengetahuan yang luas</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan perilaku utama pertama pada nilai Integritas.</li><li><strong>C</strong>: Merupakan perilaku utama kedua pada nilai Sinergi.</li><li><strong>D</strong>: Merupakan perilaku utama pertama pada nilai Pelayanan.</li><li><strong>E</strong>: Merupakan perilaku utama pertama pada nilai Kesempurnaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 20) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>mempunyai keahlian dan pengetahuan yang luas • Profesionalisme</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Perilaku utama kedua dari nilai Profesionalisme dalam panduan perilaku utama kementerian adalah...", "options": ["Mengembangkan inovasi dan kreativitas kerja", "Menjaga martabat dan tidak berbuat tercela", "Bekerja dengan hati", "Bersikap proaktif dan cepat tanggap melayani", "Memiliki sangka baik dan saling menghormati"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Perilaku Utama Kedua Nilai Profesionalisme</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Perilaku utama kedua dari nilai Profesionalisme adalah <strong>bekerja dengan hati</strong> dengan penuh dedikasi dan cinta pada profesi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat mengenai perilaku utama kedua pada pilar Profesionalisme. Menjalankan tugas dengan ketulusan nurani dan keikhlasan mengubah rutinitas kerja menjadi pengabdian yang bermakna. Butir kedua perilaku utama Profesionalisme secara tegas mewajibkan bekerja dengan hati. Dengan demikian, bekerja dengan hati adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>C. Bekerja dengan hati</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan perilaku utama kedua dari nilai Kesempurnaan.</li><li><strong>B</strong>: Merupakan perilaku utama kedua dari nilai Integritas.</li><li><strong>D</strong>: Merupakan perilaku utama kedua dari nilai Pelayanan.</li><li><strong>E</strong>: Merupakan perilaku utama pertama dari nilai Sinergi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 20) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>bekerja dengan hati • Profesionalisme</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Seorang verifikator memeriksa dokumen pencairan anggaran secara teliti hingga tuntas tanpa salah hitung, mencerminkan makna...", "options": ["Sinergi", "Pelayanan", "Kesempurnaan", "Integritas", "Profesionalisme"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penerapan Ketuntasan Kerja Profesionalisme</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Bekerja tuntas dan akurat atas dasar kompetensi terbaik dengan penuh tanggung jawab dan komitmen tinggi merupakan perwujudan makna nilai <strong>Profesionalisme</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat sesuai pengamalan tugas oleh verifikator tersebut. Ketelitian dalam perhitungan dan ketuntasan proses berkas mencerminkan kompetensi teknis yang tinggi. Secara konseptual, esensi bekerja tuntas dan akurat merupakan rumusan langsung dari makna nilai Profesionalisme. Oleh karena itu, tindakan verifikator tersebut mencerminkan nilai Profesionalisme.</p><p>Jawaban tepat: <strong>E. Profesionalisme</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Berfokus pada koordinasi kemitraan harmonis dan kerja sama produktif.</li><li><strong>B</strong>: Berfokus pada kepuasan pemangku kepentingan dan kecepatan pelayanan.</li><li><strong>C</strong>: Berfokus pada perbaikan terus menerus dan penciptaan inovasi baru.</li><li><strong>D</strong>: Berfokus pada keselarasan perkataan dan perbuatan serta ketaatan kode etik.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 20) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Bekerja tuntas dan akurat • kompetensi terbaik dengan penuh tanggung jawab</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Fokus utama makna nilai Sinergi adalah membangun hubungan...", "options": ["Kompetensi terbaik dengan komitmen yang tinggi", "Pelayanan transparan demi kepuasan bersama", "Kerja sama produktif serta kemitraan harmonis", "Kepatuhan penuh pada kode etik kedinasan", "Perbaikan terus-menerus di segala bidang"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Fokus Utama Makna Nilai Sinergi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Makna nilai <strong>Sinergi</strong> berfokus pada <strong>membangun dan memastikan hubungan kerja sama internal yang produktif serta kemitraan yang harmonis dengan para pemangku kepentingan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat sesuai definisi makna nilai Sinergi pada perilaku utama. Sinergi menggabungkan kekuatan internal dan eksternal secara harmonis demi menghasilkan karya berkualitas tinggi. Nilai Sinergi bermakna membangun kerja sama internal produktif dan kemitraan harmonis para pemangku kepentingan. Oleh sebab itu, opsi C merupakan fokus utama yang tepat.</p><p>Jawaban tepat: <strong>C. Kerja sama produktif serta kemitraan harmonis</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan rumusan resmi makna nilai Profesionalisme pada panduan perilaku utama.</li><li><strong>B</strong>: Merupakan rumusan resmi makna nilai Pelayanan pada panduan perilaku utama.</li><li><strong>D</strong>: Merupakan rumusan resmi makna nilai Integritas pada panduan perilaku utama.</li><li><strong>E</strong>: Merupakan rumusan resmi makna nilai Kesempurnaan pada panduan perilaku utama.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 21) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>hubungan kerja sama internal yang produktif • kemitraan yang harmonis dengan para pemangku kepentingan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Perilaku utama pertama dari nilai Sinergi dalam panduan perilaku utama berbunyi...", "options": ["Memiliki sangka baik, saling percaya, dan saling menghormati", "Menemukan dan melaksanakan solusi terbaik", "Bersikap proaktif dan cepat tanggap dalam bertindak", "Mengembangkan inovasi dan kreativitas baru", "Mempunyai keahlian dan pengetahuan yang luas"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Perilaku Utama Pertama Nilai Sinergi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Perilaku utama pertama dari nilai Sinergi adalah <strong>memiliki sangka baik, saling percaya, dan saling menghormati</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat mengenai butir perilaku pertama pada pilar Sinergi. Prasangka baik dan rasa saling menghargai adalah fondasi utama yang memungkinkan terjalinnya kerja sama yang solid antarsesama pegawai. Dalam panduan perilaku utama tertulis tegas butir nomor satu perilaku utama Sinergi adalah memiliki sangka baik, saling percaya, dan saling menghormati. Dengan demikian, opsi A adalah rumusan perilaku yang benar.</p><p>Jawaban tepat: <strong>A. Memiliki sangka baik, saling percaya, dan saling menghormati</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan perilaku utama kedua dari nilai Sinergi.</li><li><strong>C</strong>: Merupakan perilaku utama kedua dari nilai Pelayanan.</li><li><strong>D</strong>: Merupakan perilaku utama kedua dari nilai Kesempurnaan.</li><li><strong>E</strong>: Merupakan perilaku utama pertama dari nilai Profesionalisme.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 21) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>memiliki sangka baik, saling percaya • saling menghormati</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Perilaku utama kedua dari nilai Sinergi dalam panduan perilaku utama kementerian adalah...", "options": ["Menjaga martabat dan tidak melakukan hal tercela", "Bekerja dengan hati dan panggilan jiwa", "Melakukan perbaikan secara terus menerus", "Melayani dengan berorientasi pada kepuasan", "Menemukan dan melaksanakan solusi terbaik"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Perilaku Utama Kedua Nilai Sinergi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Perilaku utama kedua dari nilai Sinergi adalah <strong>menemukan dan melaksanakan solusi terbaik</strong> bersama seluruh pemangku kepentingan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat mengenai butir perilaku kedua pada pilar Sinergi. Kerja sama yang efektif tidak berhenti pada hubungan baik, melainkan bermuara pada penemuan dan pengeksekusian solusi terbaik demi kepentingan organisasi. Dalam panduan perilaku utama, butir nomor dua perilaku utama Sinergi berbunyi menemukan dan melaksanakan solusi terbaik. Oleh karena itu, opsi E adalah perilaku utama yang dimaksud.</p><p>Jawaban tepat: <strong>E. Menemukan dan melaksanakan solusi terbaik</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan perilaku utama kedua pada nilai Integritas.</li><li><strong>B</strong>: Merupakan perilaku utama kedua pada nilai Profesionalisme.</li><li><strong>C</strong>: Merupakan perilaku utama pertama pada nilai Kesempurnaan.</li><li><strong>D</strong>: Merupakan perilaku utama pertama pada nilai Pelayanan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 21) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menemukan dan melaksanakan solusi terbaik • Sinergi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Makna nilai Pelayanan menekankan pelaksanaan layanan pemenuh kepuasan secara...", "options": ["Mandiri, terstruktur, dan penuh tanggung jawab", "Sepenuh hati, transparan, cepat, akurat dan aman", "Inovatif, adaptif, dan berkelanjutan", "Komprehensif, terencana, dan hemat anggaran", "Kolektif, harmonis, dan berbasis kemitraan"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Karakter Pelaksanaan Nilai Pelayanan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Makna nilai <strong>Pelayanan</strong> adalah memberikan layanan yang memenuhi kepuasan pemangku kepentingan yang dilaksanakan secara <strong>sepenuh hati, transparan, cepat, akurat dan aman</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat sesuai rumusan sifat pelaksanaan layanan pada panduan perilaku utama. Pelayanan publik aparatur harus memenuhi lima kriteria mutu: ketulusan hati, keterbukaan informasi, kecepatan waktu, ketepatan hasil, dan keamanan proses. Dalam panduan perilaku dirumuskan bahwa Pelayanan dilaksanakan dengan sepenuh hati, transparan, cepat, akurat dan aman. Dengan demikian, opsi B adalah rincian pelaksanaan yang tepat.</p><p>Jawaban tepat: <strong>B. Sepenuh hati, transparan, cepat, akurat dan aman</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan gabungan kata sifat umum pada prinsip akuntabilitas kerja.</li><li><strong>C</strong>: Merupakan karakteristik tindakan pada nilai Adaptif dan Kesempurnaan.</li><li><strong>D</strong>: Merupakan karakteristik penyusunan rencana kerja operasional anggaran.</li><li><strong>E</strong>: Merupakan karakteristik hubungan koordinasi kerja pada nilai Sinergi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 21) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pelayanan • sepenuh hati, transparan, cepat, akurat dan aman</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Perilaku utama kedua dari nilai Pelayanan dalam panduan perilaku utama kementerian adalah...", "options": ["Melayani dengan berorientasi pada kepuasan pemangku kepentingan", "Memiliki sangka baik, saling percaya, dan saling menghormati", "Mempunyai keahlian dan pengetahuan yang luas", "Bersikap proaktif dan cepat tanggap", "Menjaga martabat dan tidak berbuat tercela"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Perilaku Utama Kedua Nilai Pelayanan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Perilaku utama kedua dari nilai Pelayanan adalah <strong>bersikap proaktif dan cepat tanggap</strong> dalam melayani pemangku kepentingan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat mengenai butir perilaku kedua pada nilai Pelayanan. Sikap tanggap dan inisiatif tinggi menjamin kebutuhan pengguna jasa segera teratasi tanpa hambatan birokrasi yang lambat. Dalam panduan perilaku utama, butir nomor dua perilaku utama Pelayanan berbunyi bersikap proaktif dan cepat tanggap. Oleh karena itu, opsi D merupakan butir perilaku yang dimaksud.</p><p>Jawaban tepat: <strong>D. Bersikap proaktif dan cepat tanggap</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan butir perilaku utama pertama dari nilai Pelayanan.</li><li><strong>B</strong>: Merupakan butir perilaku utama pertama dari nilai Sinergi.</li><li><strong>C</strong>: Merupakan perilaku utama pertama dari nilai Profesionalisme.</li><li><strong>E</strong>: Merupakan butir perilaku utama kedua dari nilai Integritas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 21) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>bersikap proaktif dan cepat tanggap • Pelayanan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Makna dari nilai Kesempurnaan dirumuskan sebagai upaya...", "options": ["Bekerja tuntas atas kompetensi terbaik", "Membangun kemitraan kerja sama harmonis", "Perbaikan di segala bidang untuk hasil terbaik", "Menjaga kode etik dan moralitas tindakan", "Memberikan layanan cepat dan akurat"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Rumusan Makna Nilai Kesempurnaan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Makna nilai <strong>Kesempurnaan</strong> adalah <strong>senantiasa melakukan upaya perbaikan di segala bidang untuk menjadi dan memberikan yang terbaik</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat sesuai rumusan makna nilai Kesempurnaan pada perilaku utama. Sikap pantang berpuas diri mendorong aparatur untuk terus mengevaluasi dan meningkatkan mutu kinerjanya. Secara normatif ditegaskan bahwa Kesempurnaan bermakna perbaikan di segala bidang untuk memberikan yang terbaik. Dengan demikian, opsi C merupakan makna yang benar.</p><p>Jawaban tepat: <strong>C. Perbaikan di segala bidang untuk hasil terbaik</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan rumusan resmi makna nilai Profesionalisme pada panduan perilaku utama.</li><li><strong>B</strong>: Merupakan rumusan resmi makna nilai Sinergi pada panduan perilaku utama.</li><li><strong>D</strong>: Merupakan rumusan resmi makna nilai Integritas pada panduan perilaku utama.</li><li><strong>E</strong>: Merupakan rumusan resmi makna nilai Pelayanan pada panduan perilaku utama.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 21) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Senantiasa melakukan upaya perbaikan di segala bidang • Kesempurnaan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Dua perilaku utama dari nilai Kesempurnaan dalam panduan perilaku utama kementerian adalah...", "options": ["Melakukan perbaikan terus menerus serta mengembangkan inovasi dan kreativitas", "Mempunyai keahlian luas serta bekerja dengan keikhlasan hati nurani", "Memiliki sangka baik serta menemukan solusi terbaik pemangku kepentingan", "Bersikap jujur dan tulus serta menjaga martabat kehormatan dinas", "Melayani dengan berorientasi kepuasan serta bersikap proaktif dan tanggap"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dua Perilaku Utama Nilai Kesempurnaan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dua perilaku utama dari nilai <strong>Kesempurnaan</strong> adalah (1) <strong>melakukan perbaikan terus menerus</strong> dan (2) <strong>mengembangkan inovasi dan kreativitas</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat mengenai kedua butir perilaku utama nilai Kesempurnaan. Penyempurnaan yang berkelanjutan berpadu erat dengan kreativitas melahirkan terobosan baru bagi instansi. Dalam panduan perilaku utama, perilaku utama nilai Kesempurnaan terdiri atas: 1. melakukan perbaikan terus menerus; 2. mengembangkan inovasi dan kreativitas. Oleh sebab itu, opsi A merangkum kedua butir tersebut dengan benar.</p><p>Jawaban tepat: <strong>A. Melakukan perbaikan terus menerus serta mengembangkan inovasi dan kreativitas</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan pasangan perilaku utama dari nilai Profesionalisme.</li><li><strong>C</strong>: Merupakan pasangan perilaku utama dari nilai Sinergi.</li><li><strong>D</strong>: Merupakan pasangan perilaku utama dari nilai Integritas.</li><li><strong>E</strong>: Merupakan pasangan perilaku utama dari nilai Pelayanan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 21) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>melakukan perbaikan terus menerus • mengembangkan inovasi dan kreativitas</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Secara batiniah, integritas dapat diartikan sebagai dorongan hati nurani untuk melaksanakan tugas dan tanggung jawab dengan...", "options": ["Harapan memperoleh kenaikan pangkat istimewa", "Kepatuhan semu di hadapan pengawasan pimpinan", "Tujuan meraih insentif remunerasi tambahan", "Keinginan mengungguli pencapaian unit kerja lain", "Tekad yang mulia"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengertian Integritas sebagai Dorongan Batin</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Integritas dapat diartikan sebagai <strong>dorongan hati nurani untuk melaksanakan tugas dan tanggung jawab dengan tekad yang mulia</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat mengenai dimensi spiritual dari pengertian nilai Integritas. Ketulusan hati nurani memotivasi seorang pegawai untuk bekerja sebaik-baiknya tanpa pamrih keduniawian yang sempit. Secara mendasar, integritas diartikan sebagai dorongan hati nurani untuk melaksanakan tugas dan tanggung jawab dengan tekad yang mulia. Dengan demikian, tekad yang mulia menjadi substansi intinya.</p><p>Jawaban tepat: <strong>E. Tekad yang mulia</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kenaikan pangkat merupakan penghargaan hasil kinerja, bukan motif dasar integritas nurani.</li><li><strong>B</strong>: Integritas menuntut ketulusan sejati, bukan kepatuhan palsu saat diawasi pimpinan.</li><li><strong>C</strong>: Bekerja demi integritas berakar dari keluhuran tekad, bukan dorongan materi.</li><li><strong>D</strong>: Kompetisi antarseksi harus diarahkan pada sinergi positif, bukan motif kebanggaan semu.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 21) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>dorongan hati nurani untuk melaksanakan tugas • tekad yang mulia</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Menurut KBBI, integritas didefinisikan sebagai kesatuan utuh yang memancarkan...", "options": ["Kecerdasan berpikir dan inovasi kerja", "Kewibawaan dan kejujuran", "Kemahiran teknis dan keahlian profesi", "Keluasan jejaring dan hubungan sosial", "Ketangkasan adaptasi dan kedisiplinan"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pancaran Kewibawaan dan Kejujuran KBBI</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Menurut KBBI, integritas berarti mutu, sifat, atau keadaan yang menunjukkan kesatuan yang utuh sehingga <strong>memancarkan kewibawaan dan kejujuran</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat sesuai kutipan leksikal KBBI dalam etika kedinasan. Kesatuan yang utuh antara hati, ucapan, dan perbuatan memancarkan aura kewibawaan serta kejujuran sejati. Sebagaimana dikutip dalam pedoman etika: menurut KBBI, integritas berarti mutu, sifat, atau keadaan yang menunjukkan kesatuan yang utuh sehingga memiliki potensi dan kemampuan yang memancarkan kewibawaan dan kejujuran. Oleh karena itu, opsi B adalah inti pancarannya.</p><p>Jawaban tepat: <strong>B. Kewibawaan dan kejujuran</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan deskripsi keterampilan berpikir pada nilai Adaptif dan Kesempurnaan.</li><li><strong>C</strong>: Merupakan deskripsi kemahiran teknis pada nilai Profesionalisme.</li><li><strong>D</strong>: Merupakan deskripsi kecakapan relasi sosial pada nilai Sinergi.</li><li><strong>E</strong>: Merupakan gambaran kelincahan bertindak pada nilai Adaptif.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 21) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Menurut Kamus Besar Bahasa Indonesia (KBBI) • memancarkan kewibawaan dan kejujuran</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Ditinjau dari ranah etika, integritas secara lugas diartikan sebagai...", "options": ["Kecepatan dan ketepatan dalam melayani pemangku kepentingan", "Kesediaan menerima masukan dan kritik konstruktif masyarakat", "Kemampuan mengoordinasikan berbagai sumber daya organisasi", "Kejujuran dan kebenaran dari tindakan seseorang", "Kreativitas menciptakan metode kerja dinas yang baru"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengertian Integritas dalam Etika</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam ranah etika, <strong>integritas diartikan sebagai kejujuran dan kebenaran dari tindakan seseorang</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat mengenai batasan filosofis integritas dalam ilmu etika. Kebenaran suatu perbuatan diukur dari kesesuaian tindakan nyata seseorang dengan prinsip kejujuran moral. Secara konseptual ditegaskan bahwa dalam etika, integritas diartikan sebagai kejujuran dan kebenaran dari tindakan seseorang. Dengan demikian, kejujuran dan kebenaran tindakan merupakan arti integritas dalam etika.</p><p>Jawaban tepat: <strong>D. Kejujuran dan kebenaran dari tindakan seseorang</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan tolok ukur efektivitas pelayanan pada nilai Pelayanan.</li><li><strong>B</strong>: Merupakan sikap terbuka pada perbaikan nilai Berorientasi Pelayanan.</li><li><strong>C</strong>: Merupakan kecakapan manajerial koordinasi pada nilai Kolaboratif.</li><li><strong>E</strong>: Merupakan aspek inovasi dan pembaruan pada nilai Kesempurnaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 21) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Dalam etika, integritas diartikan sebagai • kejujuran dan kebenaran dari tindakan seseorang</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Seseorang dikatakan mempunyai integritas apabila tindakannya selaras dengan...", "options": ["Keinginan dan instruksi informal rekan kerja seangkatan", "Arah pergerakan keuntungan finansial pasar investasi", "Opini mayoritas publik yang sedang berkembang di media", "Nilai, keyakinan, dan prinsip yang dipegangnya", "Kepentingan politik praktis kelompok pemangku jabatan"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengertian Integritas Menurut Ensiklopedia</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Seseorang dikatakan <strong>mempunyai integritas</strong> apabila tindakannya <strong>sesuai dengan nilai, keyakinan, dan prinsip yang dipegangnya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat mengenai tolok ukur integritas personal menurut kutipan ensiklopedia dalam etika kedinasan. Integritas sejati tampak ketika perilaku nyata seseorang berpijak kokoh pada keyakinan moral yang dianutnya secara konsisten. Seseorang dikatakan memiliki integritas apabila tindakannya sesuai dengan nilai, keyakinan, dan prinsip yang dipegangnya. Oleh karena itu, kesesuaian dengan prinsip yang dipegang menjadi parameternya.</p><p>Jawaban tepat: <strong>D. Nilai, keyakinan, dan prinsip yang dipegangnya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Instruksi informal tidak dapat menjadi dasar bertindak jika bertentangan dengan norma.</li><li><strong>B</strong>: Keuntungan pasar finansial adalah urusan bisnis, bukan standar integritas moral pribadi.</li><li><strong>C</strong>: Opini media sosial sering kali berubah-ubah dan tidak dapat menjadi kompas etika mutlak.</li><li><strong>E</strong>: Aparatur sipil negara wajib netral dan bebas dari kepentingan politik praktis.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 22) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>mempunyai integritas • sesuai dengan nilai, keyakinan, dan prinsip yang dipegangnya</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Salah satu ciri utama yang menandai seseorang mempunyai integritas tinggi adalah...", "options": ["Kemampuan berpidato memikat di depan publik luas", "Bersatunya kata dan perbuatan", "Kecepatan menyelesaikan berkas di luar prosedur", "Keluasan jejaring relasi bisnis di luar instansi", "Tingginya frekuensi penugasan perjalanan dinas"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Ciri Utama Bersatunya Kata dan Perbuatan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Salah satu ciri seseorang yang berintegritas</strong> adalah <strong>bersatunya kata dan perbuatan</strong> dalam kehidupan sehari-hari.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat mengenai ciri khas perilaku insan yang berintegritas. Kejujuran batin seseorang tecermin dari keselarasan mutlak antara apa yang diucapkan dengan apa yang diperbuatnya di lapangan. Sebagaimana ditegaskan dalam pedoman etika, salah satu ciri seseorang yang berintegritas adalah bersatunya kata dan perbuatan. Dengan demikian, persatuan kata dan perbuatan merupakan ciri utamanya.</p><p>Jawaban tepat: <strong>B. Bersatunya kata dan perbuatan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kemampuan retorika berbicara belum tentu mencerminkan kejujuran tindakan nyata.</li><li><strong>C</strong>: Penyelesaian berkas kedinasan wajib mematuhi prosedur operasional yang sah.</li><li><strong>D</strong>: Relasi eksternal aparatur harus dijaga agar tidak memicu konflik kepentingan.</li><li><strong>E</strong>: Frekuensi dinas luar adalah penugasan teknis dinas semata, bukan ukuran integritas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 22) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Salah satu ciri seseorang yang berintegritas • bersatunya kata dan perbuatan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C4 - Analisis", "question": "Seorang aparatur yang berintegritas dicirikan sebagai sosok yang BUKAN bertipe...", "options": ["Pribadi yang tekun dan teliti dalam bekerja", "Pegawai yang ramah dan solutif kepada pemohon", "Aparatur yang loyal kepada konstitusi negara", "Abdi negara yang menjaga rahasia kedinasan", "Manusia dengan banyak wajah sesuai motif pribadi"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penolakan Watak Oportunis Pribadi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Seorang yang mempunyai integritas <strong>bukan tipe manusia dengan banyak wajah dan penampilan yang disesuaikan dengan motif dan kepentingan pribadinya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat karena memuat tipe watak yang secara tegas ditolak dalam profil manusia berintegritas. Karakter bunglon atau bermuka dua mencerminkan kepalsuan dan ketidakjujuran moral yang merusak kepercayaan publik. Secara resmi dinyatakan bahwa seorang yang mempunyai integritas bukan tipe manusia dengan banyak wajah dan penampilan yang disesuaikan dengan motif dan kepentingan pribadinya. Oleh karena itu, opsi E adalah jawaban yang tepat.</p><p>Jawaban tepat: <strong>E. Manusia dengan banyak wajah sesuai motif pribadi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan ciri positif pegawai yang berintegritas dan profesional.</li><li><strong>B</strong>: Merupakan wujud sikap positif aparatur pada nilai Berorientasi Pelayanan.</li><li><strong>C</strong>: Merupakan wujud kesetiaan positif aparatur pada nilai Loyal.</li><li><strong>D</strong>: Merupakan komitmen positif aparatur dalam mematuhi kode etik dinas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 22) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>bukan tipe manusia dengan banyak wajah • motif dan kepentingan pribadinya</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Dalam konteks Nilai Kementerian Keuangan, integritas diwujudkan melalui keselarasan...", "options": ["Pikiran, ucapan, perilaku, dan tindakan benar", "Keahlian teknis dan target capaian kinerja", "Kepuasan publik dan efisiensi belanja dinas", "Hubungan kerja sama dan kemitraan harmonis", "Inovasi kerja dan kreativitas teknologi"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Keselarasan Dimensi Integritas Kemenkeu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam konteks Nilai-Nilai Kementerian Keuangan, <strong>integritas diartikan sebagai berpikir, berkata, berperilaku dan bertindak dengan baik dan benar serta memegang teguh kode etik dan prinsip-prinsip moral</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat sesuai rumusan konseptual integritas di lingkungan Kementerian Keuangan. Keempat dimensi manusia (pola pikir, ucapan, sikap, dan perbuatan) wajib diselaraskan dalam koridor moral dan kode etik. Sesuai panduan pedoman kerja, definisi resmi integritas Kementerian Keuangan memadukan berpikir, berkata, berperilaku, dan bertindak secara benar. Dengan demikian, opsi A adalah rumusan yang tepat.</p><p>Jawaban tepat: <strong>A. Pikiran, ucapan, perilaku, dan tindakan benar</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan dimensi keahlian teknis pada nilai Profesionalisme.</li><li><strong>C</strong>: Merupakan perpaduan aspek nilai Pelayanan dan manajemen anggaran.</li><li><strong>D</strong>: Merupakan dimensi hubungan kemitraan pada nilai Sinergi.</li><li><strong>E</strong>: Merupakan dimensi pembaruan pada nilai Kesempurnaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 22) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Dalam konteks Nilai-Nilai Kementerian Keuangan, integritas diartikan sebagai • memegang teguh kode etik dan prinsip-prinsip moral</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Seorang petugas penyusun laporan keuangan kantor menyajikan angka realisasi anggaran persis sesuai bukti transaksi, mewujudkan sikap jujur yaitu...", "options": ["Menjaga kerahasiaan seluruh dokumen dinas", "Membantu rekan kerja memahami alur jurnal", "Memberikan informasi berdasarkan fakta", "Menyesuaikan diri dengan aplikasi terbaru", "Membangun kemitraan harmonis lintas seksi"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penyampaian Fakta sebagai Sikap Jujur</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Salah satu sikap jujur ditunjukkan dengan <strong>memberikan informasi berdasarkan fakta</strong> yang sebenarnya terjadi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat sesuai pengamalan kejujuran dalam pelaporan angka keuangan tersebut. Transparansi dan keakuratan fakta menjamin laporan yang dihasilkan dapat dipercaya oleh semua pihak. Sesuai penegasan pedoman kerja, salah satu sikap jujur ditunjukkan dengan memberikan informasi berdasarkan fakta. Oleh sebab itu, penyajian data sesuai bukti transaksi tersebut mencerminkan pemberian informasi berdasar fakta.</p><p>Jawaban tepat: <strong>C. Memberikan informasi berdasarkan fakta</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan butir perlindungan informasi pada nilai Loyal dan Integritas.</li><li><strong>B</strong>: Merupakan butir transfer pengetahuan pada nilai Kompeten.</li><li><strong>D</strong>: Merupakan butir kelincahan adaptasi sistem pada nilai Adaptif.</li><li><strong>E</strong>: Merupakan butir penciptaan relasi kerja pada nilai Sinergi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 22) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>memberikan informasi berdasarkan fakta • Salah satu sikap jujur ditunjukkan dengan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Secara konseptual, hakikat berbicara jujur dalam pelaksanaan tugas kedinasan diartikan sebagai...", "options": ["Menyampaikan pendapat pribadi tanpa menghiraukan perasaan orang lain", "Menjawab pertanyaan atasan dengan bahasa akademis yang rumit", "Mengumumkan seluruh berkas rahasia jabatan kepada masyarakat umum", "Berbicara sesuai kenyataan tanpa disembunyikan atau dimanipulasi", "Menyepakati seluruh saran rekan kerja tanpa melalui telaah kritis"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Hakikat Berbicara Jujur Tanpa Manipulasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Berbicara jujur adalah berbicara sesuai kenyataan, tidak ada yang disembunyikan atau dimanipulasi</strong> dalam setiap pelaporan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat mengenai batasan berbicara jujur menurut prinsip etika kedinasan. Komunikasi kedinasan menuntut keterbukaan informasi yang murni tanpa distorsi demi kepentingan tertentu. Secara normatif ditegaskan bahwa berbicara jujur adalah berbicara sesuai kenyataan, tidak ada yang disembunyikan atau dimanipulasi. Dengan demikian, opsi D merupakan makna berbicara jujur yang dimaksud.</p><p>Jawaban tepat: <strong>D. Berbicara sesuai kenyataan tanpa disembunyikan atau dimanipulasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kejujuran tetap harus disampaikan dengan tutur kata yang santun dan beretika.</li><li><strong>B</strong>: Bahasa laporan kedinasan harus lugas, jelas, dan mudah dipahami pimpinan.</li><li><strong>C</strong>: Dokumen rahasia negara wajib dilindungi sesuai ketentuan peraturan perundangan.</li><li><strong>E</strong>: Penelaahan kritis tetap diperlukan demi akuntabilitas pengambilan keputusan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 22) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Berbicara jujur adalah berbicara sesuai kenyataan • tidak ada yang disembunyikan atau dimanipulasi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Sikap jujur harus senantiasa didampingi oleh ketulusan, yang dalam penyampaian laporan kedinasan berarti...", "options": ["Mengharapkan imbalan kenaikan golongan jabatan", "Tidak ada maksud lain di balik informasi selain isi informasi itu", "Menyampaikan laporan saat diminta langsung pimpinan", "Menambahkan pujian berlebihan kepada atasan langsung", "Menyimpan data cadangan untuk kepentingan pribadi"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Ketulusan yang Mengiringi Kejujuran</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Jujur diikuti oleh sikap ketulusan, yaitu <strong>tidak ada maksud lain di balik informasi yang diberikan kecuali sesuai dengan isi informasi tersebut</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat sesuai komitmen aparatur mengenai perpaduan jujur dan tulus. Ketulusan memastikan informasi kedinasan disampaikan secara murni untuk kepentingan organisasi tanpa motif terselubung. Dalam pedoman etika, jujur diikuti oleh sikap ketulusan, tidak ada maksud lain di balik informasi yang diberikan kecuali sesuai dengan isi informasi tersebut. Oleh karena itu, opsi B merumuskan hakikat ketulusan tersebut.</p><p>Jawaban tepat: <strong>B. Tidak ada maksud lain di balik informasi selain isi informasi itu</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Mengharapkan imbalan promosi bertentangan dengan ketulusan integritas.</li><li><strong>C</strong>: Pelaporan kedinasan harus dilakukan secara proaktif dan bertanggung jawab.</li><li><strong>D</strong>: Pujian palsu kepada atasan mencerminkan sikap oportunis yang tidak tulus.</li><li><strong>E</strong>: Menyimpan data dinas untuk kepentingan sendiri melanggar kode etik kerahasiaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 22) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>tidak ada maksud lain di balik informasi • Jujur diikuti oleh sikap ketulusan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Memberikan informasi penting kepada atasan semata-mata dengan pamrih agar diberikan promosi jabatan bertentangan dengan prinsip...", "options": ["Keahlian ideal dalam menjaga citra profesi dinas", "Kecepatan merespons kebutuhan masyarakat publik", "Kerja sama kemitraan yang produktif dan harmonis", "Perbaikan tanpa henti dalam sistem administrasi", "Sikap tulus pada perilaku utama nilai integritas"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pamrih Promosi Bertentangan dengan Integritas</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Menyampaikan informasi dengan pamrih imbalan promosi <strong>bukanlah sikap berintegritas karena tidak mencerminkan sikap tulus sebagaimana perilaku utama pertama dari nilai integritas</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat sesuai contoh kasus nyata yang dipaparkan dalam ketentuan kerja. Pelaporan data dinas merupakan kewajiban moral dan jabatan yang tidak boleh diperdagangkan demi keuntungan karier personal. Pedoman resmi menegaskan bahwa sebagai contoh, bukanlah sikap berintegritas jika seseorang memberikan informasi kepada atasan dengan harapan imbalan promosi, karena tidak mencerminkan sikap tulus. Dengan demikian, tindakan tersebut melanggar sikap tulus pada nilai integritas.</p><p>Jawaban tepat: <strong>E. Sikap tulus pada perilaku utama nilai integritas</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan konsep etika penampilan dan kecakapan pada nilai Profesionalisme.</li><li><strong>B</strong>: Merupakan tolok ukur ketanggapan pelayanan pada nilai Pelayanan.</li><li><strong>C</strong>: Merupakan pilar hubungan kerja antarrekan pada nilai Sinergi.</li><li><strong>D</strong>: Merupakan pilar inovasi berkelanjutan pada nilai Kesempurnaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 22) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>bukanlah sikap berintegritas • harapan atasannya akan memberikan imbalan promosi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Buah karakter utama yang akan terbentuk secara alami dari perpaduan sikap jujur dan tulus adalah...", "options": ["Pribadi yang dapat dipercaya", "Aparatur yang kebal terhadap mutasi", "Pejabat yang disegani karena otoritas", "Pegawai dengan penghasilan tertinggi", "Staf yang paling sering dipuji pimpinan"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pembentukan Pribadi yang Dapat Dipercaya</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dari pembiasaan <strong>sikap jujur dan tulus</strong> dalam bertugas, <strong>akan terbentuk pribadi yang dapat dipercaya</strong> oleh organisasi maupun masyarakat.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat mengenai dampak karakter yang lahir dari kejujuran dan ketulusan. Kepercayaan pimpinan, rekan kerja, dan masyarakat diperoleh dari pembuktian konsistensi moral abdi negara. Prinsip moral menyimpulkan dengan tegas bahwa dari sikap jujur dan tulus itu, akan terbentuk pribadi yang dapat dipercaya. Oleh karena itu, terbentuknya pribadi yang dapat dipercaya adalah buah karakternya.</p><p>Jawaban tepat: <strong>A. Pribadi yang dapat dipercaya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Mutasi pegawai merupakan kebijakan pembinaan karier aparatur di kementerian.</li><li><strong>C</strong>: Rasa hormat yang sehat berakar dari keteladanan budi, bukan otoritas kekuasaan.</li><li><strong>D</strong>: Tingkat penghasilan aparatur negara diatur melalui skema gaji dan tunjangan sah.</li><li><strong>E</strong>: Pujian pimpinan bukan tujuan akhir dari pembentukan karakter integritas diri.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 22) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Dari sikap jujur dan tulus itu • akan terbentuk pribadi yang dapat dipercaya</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Pegawai Kementerian Keuangan yang menjaga martabat dengan mematuhi norma hukum, agama, dan sosial pada hakikatnya telah...", "options": ["Meniadakan kewajiban penyusunan sasaran kinerja tahunan", "Memperoleh jaminan promosi jabatan otomatis setiap tahun", "Menjaga kehormatan organisasi tempatnya mengabdi", "Menghapus wewenang audit pengawasan aparat fungsional", "Membatasi tanggung jawab pelaksanaan tugas operasional"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Menjaga Kehormatan Diri dan Organisasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dengan <strong>menjaga kehormatan diri</strong> melalui kepatuhan pada norma hukum, agama, dan sosial, berarti pegawai <strong>telah menjaga kehormatan organisasi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat mengenai hubungan erat antara moralitas pribadi pegawai dengan reputasi institusi. Citra dan keluhuran nama Kementerian Keuangan di mata publik sangat bergantung pada perilaku etis setiap pegawainya. Pedoman etika merumuskan bahwa dengan menjaga kehormatan diri, berarti dia telah menjaga kehormatan organisasi. Dengan demikian, menjaga kehormatan organisasi adalah hakikat dari perbuatan tersebut.</p><p>Jawaban tepat: <strong>C. Menjaga kehormatan organisasi tempatnya mengabdi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penyusunan sasaran kinerja pegawai tetap wajib dilaksanakan sesuai siklus tahunan.</li><li><strong>B</strong>: Promosi aparatur sipil negara diatur berdasarkan sistem merit dan ketersediaan formasi.</li><li><strong>D</strong>: Audit fungsional merupakan instrumen pengawasan mutlak dalam tata kelola negara.</li><li><strong>E</strong>: Kepatuhan etika justru memperkuat tanggung jawab dalam menjalankan tugas dinas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 22) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menjaga kehormatan dirinya dengan tidak melakukan • telah menjaga kehormatan organisasi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Penerapan perilaku menjaga martabat pegawai dalam kehidupan sehari-hari ditunjukkan secara nyata dalam bentuk...", "options": ["Menghadiri acara seremonial kementerian di luar jam dinas", "Melaksanakan dan mematuhi sumpah jabatan dengan penuh kesadaran", "Menyusun usulan penambahan formasi pegawai di unit kerjanya", "Mengikuti kegiatan studi banding ke luar negeri setiap tahun", "Membatasi komunikasi dengan masyarakat penerima layanan"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kepatuhan terhadap Sumpah Jabatan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Aplikasi perilaku menjaga martabat ditunjukkan dalam bentuk <strong>melaksanakan dan mematuhi sumpah jabatan yang telah diucapkan ketika dilantik menjadi pegawai dan melaksanakannya dengan penuh kesadaran</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat mengenai wujud konkret pengamalan martabat diri aparatur. Sumpah jabatan yang diucapkan di hadapan Tuhan dan negara merupakan ikatan janji suci yang wajib dipegang teguh sepanjang masa dinas. Dalam praktik kerja dijelaskan bahwa dalam aplikasi sehari-hari, perilaku ini dapat ditunjukkan dalam bentuk melaksanakan dan mematuhi sumpah jabatan dengan penuh kesadaran. Oleh sebab itu, opsi B adalah wujud nyatanya.</p><p>Jawaban tepat: <strong>B. Melaksanakan dan mematuhi sumpah jabatan dengan penuh kesadaran</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kehadiran acara seremonial merupakan kegiatan protokoler biasa, bukan wujud esensial sumpah.</li><li><strong>C</strong>: Penyusunan usulan formasi kepegawaian merupakan tugas administratif kepegawaian.</li><li><strong>D</strong>: Studi banding luar negeri dilakukan selektif sesuai kebutuhan strategis dinas.</li><li><strong>E</strong>: Aparatur pelayanan publik wajib membuka komunikasi yang ramah dan solutif.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 23) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>melaksanakan dan mematuhi sumpah jabatan • melaksanakannya dengan penuh kesadaran</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Salah satu poin penting dalam sumpah jabatan pegawai Kementerian Keuangan terkait larangan gratifikasi adalah...", "options": ["Meminta penundaan mutasi kerja ke wilayah terpencil", "Menerima bingkisan hadiah hari raya dari rekanan swasta", "Menjanjikan kemudahan izin usaha kepada keluarga dekat", "Tidak menerima pemberian atau menjanjikan apa pun terkait jabatannya", "Meminta kompensasi tambahan atas bantuan konsultasi dinas"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Larangan Menerima Gratifikasi dalam Sumpah</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Semua pegawai Kementerian Keuangan telah disumpah, yang salah satu isi sumpahnya adalah <strong>tidak menerima pemberian atau tidak menjanjikan apapun dari pihak lain yang terkait dengan jabatannya (gratifikasi)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat mengenai komitmen antisuap dalam sumpah aparatur Kementerian Keuangan. Menjauhkan diri dari segala bentuk gratifikasi menjamin objektivitas dan independensi pelayanan publik. Sebagaimana ketentuan dasarnya: semua pegawai Kementerian Keuangan sudah disumpah, yang salah satu isinya adalah tidak menerima pemberian atau menjanjikan apa pun terkait jabatan. Dengan demikian, opsi D adalah bunyi komitmen sumpah tersebut.</p><p>Jawaban tepat: <strong>D. Tidak menerima pemberian atau menjanjikan apa pun terkait jabatannya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penugasan mutasi kerja diatur oleh instansi sesuai kebutuhan organisasi.</li><li><strong>B</strong>: Menerima bingkisan dari rekanan dinas termasuk tindak gratifikasi yang dilarang keras.</li><li><strong>C</strong>: Menjanjikan kemudahan nonprosedural merupakan pelanggaran berat kode etik jabatan.</li><li><strong>E</strong>: Konsultasi kedinasan merupakan layanan gratis negara tanpa pungutan biaya apa pun.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 23) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>tidak menerima pemberian atau tidak menjanjikan apapun • terkait dengan jabatannya (gratifikasi)</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Dalam etika kerja kedinasan, ketaatan aparatur dalam melaksanakan perintah agama dan menjauhi larangan akan mendorongnya untuk...", "options": ["Membantu mengatasi kesulitan orang lain", "Menuntut kenaikan tarif tunjangan kinerja dinas", "Mengurangi koordinasi kerja dengan unit seksi lain", "Memperoleh keistimewaan dispensasi jam dinas", "Membatasi pergaulan sosial di lingkungan kantor"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Ketaatan Agama Membantu Kesulitan Sesama</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Menaati aturan agama merupakan bentuk menjaga kehormatan diri; di mana <strong>orang-orang yang taat pada agama akan membantu mengatasi kesulitan orang lain</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat sesuai dampak positif ketaatan beragama terhadap empati pelayanan di tempat kerja. Nilai-nilai religius mengajarkan kepedulian sosial untuk meringankan beban sesama rekan kerja maupun masyarakat. Pedoman resmi menegaskan bahwa melaksanakan perintah agama dan menjauhi larangan merupakan bagian penting dalam bekerja; orang-orang yang taat pada agama akan membantu mengatasi kesulitan orang lain. Oleh karena itu, opsi A adalah wujud nyatanya.</p><p>Jawaban tepat: <strong>A. Membantu mengatasi kesulitan orang lain</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Tunjangan kinerja diatur oleh sistem merit negara, bukan tuntutan pribadi.</li><li><strong>C</strong>: Koordinasi antarseksi wajib dipelihara melalui semangat nilai Sinergi.</li><li><strong>D</strong>: Ketaatan beragama justru melahirkan kedisiplinan tinggi dalam mematuhi jam kerja.</li><li><strong>E</strong>: Aparatur negara dituntut membangun hubungan sosial yang harmonis dan inklusif.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 23) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Menaati aturan agama juga termasuk bentuk menjaga kehormatan diri • akan membantu mengatasi kesulitan orang lain</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Aplikasi lain dari perilaku utama kedua nilai integritas di lingkungan Kementerian Keuangan adalah...", "options": ["Menyusun analisis jabatan baru secara independen di daerah", "Mengatur jadwal piket pengamanan kantor tanpa koordinasi", "Menetapkan besaran retribusi daerah secara mandiri", "Mengganti seragam kedinasan dengan pakaian kasual", "Menaati kode etik dan perilaku yang sudah ditetapkan"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kepatuhan Kode Etik sebagai Panduan Bekerja</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Aplikasi lain dari perilaku utama kedua nilai integritas adalah <strong>menaati kode etik dan perilaku yang sudah ditetapkan oleh Kementerian Keuangan sebagai panduan dalam bekerja</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat mengenai wujud penerapan perilaku kedua nilai integritas. Kode etik dan kode perilaku institusi merupakan pedoman praktis yang memandu aparatur dalam bertindak profesional setiap hari. Sebagaimana pedoman resminya: aplikasi lain dari perilaku utama kedua nilai integritas adalah menaati kode etik dan perilaku yang sudah ditetapkan oleh Kementerian Keuangan sebagai panduan dalam bekerja. Dengan demikian, opsi E adalah jawabannya.</p><p>Jawaban tepat: <strong>E. Menaati kode etik dan perilaku yang sudah ditetapkan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Analisis jabatan disusun secara terpusat oleh biro organisasi dan tata laksana.</li><li><strong>B</strong>: Pengamanan kantor diatur oleh unit kepatuhan internal dan rumah tangga kantor.</li><li><strong>C</strong>: Tarif retribusi daerah diatur melalui peraturan daerah berdasarkan undang-undang.</li><li><strong>D</strong>: Ketentuan pakaian seragam dinas wajib ditaati sesuai surat edaran kementerian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 23) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>kode etik dan perilaku yang sudah ditetapkan • sebagai panduan dalam bekerja</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Secara mendasar, profesionalisme diartikan sebagai sifat-sifat seorang profesional berupa...", "options": ["Keluasan hak kekebalan hukum dalam menjalankan tugas dinas", "Kedudukan struktural tertinggi dalam tata kepangkatan jabatan", "Kemampuan dan kemahiran dalam pelaksanaan kegiatan tertentu", "Kewenangan penuh mengalokasikan anggaran keuangan negara", "Kepemilikan gelar akademis kehormatan dari lembaga swasta"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengertian Dasar Profesionalisme</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Profesionalisme adalah sifat-sifat yang sewajarnya dimiliki atau dilakukan oleh seorang profesional, berupa kemampuan dan kemahiran dalam pelaksanaan kegiatan tertentu</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat mengenai definisi dasar etos kerja profesional. Keahlian teknis dan kemahiran kerja merupakan ciri pokok yang membedakan seorang profesional dengan pekerja awam. Dalam prinsip profesionalitas dijelaskan bahwa profesionalisme adalah sifat-sifat yang sewajarnya dimiliki atau dilakukan oleh seorang profesional, berupa kemampuan dan kemahiran dalam pelaksanaan kegiatan tertentu. Oleh sebab itu, opsi C merumuskan definisi tersebut secara tepat.</p><p>Jawaban tepat: <strong>C. Kemampuan dan kemahiran dalam pelaksanaan kegiatan tertentu</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Aparatur sipil negara tunduk pada asas kepatuhan hukum yang berlaku bagi seluruh warga.</li><li><strong>B</strong>: Profesionalisme melekat pada seluruh jenjang jabatan, bukan hanya pejabat struktural.</li><li><strong>D</strong>: Pengalokasian anggaran dibatasi oleh undang-undang APBN dan rencana kerja resmi.</li><li><strong>E</strong>: Kompetensi nyata di lapangan lebih utama daripada kepemilikan gelar formal semata.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 23) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Profesionalisme adalah sifat-sifat yang sewajarnya dimiliki • kemampuan dan kemahiran dalam pelaksanaan kegiatan tertentu</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Menurut Kamus Besar Bahasa Indonesia (KBBI), makna profesionalisme berkaitan dengan profesi yang memerlukan...", "options": ["Masa pengabdian dinas puluhan tahun tanpa cela", "Kepandaian khusus untuk menjalankannya", "Ketersediaan fasilitas sarana prasarana mewah", "Garis keturunan dari keluarga pejabat dinas", "Dukungan rekomendasi politik dari tokoh daerah"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Profesionalisme Menurut KBBI</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Menurut KBBI, makna profesionalisme <strong>berhubungan dengan profesi dan memerlukan kepandaian khusus untuk menjalankannya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat sesuai kutipan kamus leksikal bahasa Indonesia dalam etika kedinasan. Sebuah profesi selalu menuntut keahlian spesifik yang diperoleh melalui pendidikan dan pelatihan terstruktur. Secara leksikal tercantum bahwa menurut KBBI, makna profesionalisme berhubungan dengan profesi dan memerlukan kepandaian khusus untuk menjalankannya. Dengan demikian, kepandaian khusus untuk menjalankannya adalah makna menurut KBBI.</p><p>Jawaban tepat: <strong>B. Kepandaian khusus untuk menjalankannya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Masa kerja lama tidak otomatis mencerminkan kepandaian khusus profesional.</li><li><strong>C</strong>: Profesionalisme bertumpu pada kompetensi intelektual, bukan fasilitas fisik.</li><li><strong>D</strong>: Sistem kepegawaian modern berlandaskan meritokrasi murni tanpa nepotisme.</li><li><strong>E</strong>: Birokrasi aparatur sipil negara steril dari intervensi dan rekomendasi politik.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 23) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Menurut KBBI, makna profesionalisme berhubungan dengan • memerlukan kepandaian khusus untuk menjalankannya</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Dalam ciri kualitas profesionalisme, konsep 'keahlian ideal' didefinisikan sebagai...", "options": ["Sertifikat kelulusan uji kompetensi tingkat internasional", "Target kuantitatif tertinggi dalam sasaran kinerja pegawai", "Tingkat pendidikan pascasarjana bidang manajemen keuangan", "Suatu perangkat perilaku yang dipandang paling sempurna dan dijadikan rujukan", "Wewenang mutlak pejabat dalam mengambil keputusan dinas"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Konsep Keahlian Ideal sebagai Rujukan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Yang dimaksud dengan <strong>keahlian ideal</strong> ialah <strong>suatu perangkat perilaku yang dipandang paling sempurna dan dijadikan sebagai rujukan</strong> oleh seorang profesional.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat mengenai batasan konseptual keahlian ideal seorang profesional. Standar perilaku unggul menjadi teladan dan tolok ukur bagi seorang profesional untuk terus menyempurnakan kinerjanya. Secara konseptual ditegaskan bahwa yang dimaksud dengan keahlian ideal ialah suatu perangkat perilaku yang dipandang paling sempurna dan dijadikan sebagai rujukan. Oleh sebab itu, opsi D merupakan jawaban yang benar.</p><p>Jawaban tepat: <strong>D. Suatu perangkat perilaku yang dipandang paling sempurna dan dijadikan rujukan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Sertifikasi kompetensi merupakan dokumen pengakuan bukti formal keahlian.</li><li><strong>B</strong>: Target kinerja kuantitatif merupakan tolok ukur output capaian pekerjaan.</li><li><strong>C</strong>: Gelar akademis magister merupakan jenjang kualifikasi pendidikan formal.</li><li><strong>E</strong>: Kewenangan pejabat merupakan aspek yuridis legalitas hukum jabatan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 23) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>keahlian ideal • suatu perangkat perilaku yang dipandang paling sempurna</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Aparatur yang menjaga citra luhur korps melalui kerapian berbusana, kesantunan bertutur kata, dan etika bergaul sedang mewujudkan...", "options": ["Peningkatan dan pemeliharaan image profesi", "Pengurangan beban target kerja harian unit", "Pencegahan evaluasi kinerja tahunan kantor", "Penyeragaman seluruh pendapat dalam forum rapat", "Pemberian fasilitas khusus bagi pegawai senior"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Perwujudan Memelihara Citra Profesi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Profesionalisme yang tinggi ditunjukkan oleh besarnya keinginan untuk selalu <strong>meningkatkan dan memelihara image profesi melalui perwujudan perilaku profesional</strong> seperti penampilan, cara percakapan, bahasa, dan cara berhubungan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat sesuai ciri kedua kualitas profesionalisme. Penampilan yang rapi serta kesantunan berkomunikasi merefleksikan kehormatan profesi yang diembannya di hadapan publik. Pedoman etika mencatat bahwa perwujudannya dilakukan melalui berbagai cara misalnya penampilan, cara percakapan, penggunaan bahasa, sikap hidup harian, cara berhubungan dengan individu lainnya untuk memelihara image profesi. Dengan demikian, opsi A adalah wujud perbuatannya.</p><p>Jawaban tepat: <strong>A. Peningkatan dan pemeliharaan image profesi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Etika pergaulan bertujuan memperkuat citra profesi, bukan mengurangi beban kerja.</li><li><strong>C</strong>: Evaluasi kinerja tetap wajib dijalankan sesuai indikator sasaran kinerja pegawai.</li><li><strong>D</strong>: Perbedaan pandangan profesional dalam rapat dihargai untuk melahirkan solusi terbaik.</li><li><strong>E</strong>: Fasilitas dinas dialokasikan berdasarkan standar kebutuhan tugas resmi yang adil.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 23) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>memelihara image profesi melalui perwujudan • penampilan, cara percakapan, penggunaan bahasa</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Seorang analis kebijakan rutin mengikuti seminar dan lokakarya mandiri, mencerminkan salah satu ciri profesionalisme yaitu...", "options": ["Membatasi konsultasi teknis dengan pengguna jasa", "Mencari pembebasan dari tugas pengawasan pimpinan", "Menyerahkan penyusunan laporan dinas kepada rekan", "Menuntut alokasi kenaikan pagu belanja satuan kerja", "Mengejar kesempatan pengembangan profesional"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Mengejar Pengembangan Profesional Berkelanjutan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Seorang profesional memiliki ciri <strong>senantiasa mengejar kesempatan pengembangan profesional yang dapat meningkatkan dan memperbaiki kualitas pengetahuan dan keterampilannya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat sesuai kemauan belajar mandiri dari analis tersebut. Keinginan memperbarui wawasan dan keterampilan teknis merupakan motor penggerak keunggulan seorang profesional. Prinsip profesi merumuskan ciri ketiga profesionalisme: seorang profesional ingin senantiasa mengejar kesempatan pengembangan profesional yang dapat meningkatkan dan memperbaiki kualitas pengetahuan dan keterampilannya. Oleh karena itu, opsi E adalah ciri yang tepat.</p><p>Jawaban tepat: <strong>E. Mengejar kesempatan pengembangan profesional</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Konsultasi teknis dengan pemangku kepentingan wajib dilayani secara terbuka.</li><li><strong>B</strong>: Pengawasan melekat pimpinan merupakan bagian integral dari kendali mutu birokrasi.</li><li><strong>C</strong>: Pelaporan tugas kedinasan wajib diselesaikan secara mandiri dan bertanggung jawab.</li><li><strong>D</strong>: Pagu belanja satuan kerja disusun berdasarkan rencana strategis kementerian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 23) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>kesempatan pengembangan profesional yang dapat • kualitas pengetahuan dan keterampilannya</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C4 - Analisis", "question": "Berikut ini merupakan ciri-ciri kualitas profesionalisme seseorang dalam standar keprofesian aparatur, KECUALI...", "options": ["Menampilkan perilaku yang mendekati keahlian ideal", "Meningkatkan dan memelihara image profesi", "Menuntut pemberian fasilitas mewah sebelum bekerja", "Mengejar kesempatan pengembangan profesional", "Mendorong dirinya mewujudkan kerja-kerja profesional"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Ciri Kualitas Profesionalisme Seseorang</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Ciri kualitas profesionalisme meliputi: <strong>mendekati keahlian ideal</strong>, <strong>memelihara image profesi</strong>, dan <strong>mengejar pengembangan profesional</strong>, bukan menuntut fasilitas materiil.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat karena menuntut fasilitas mewah sebelum bekerja bertentangan dengan semangat pengabdian dan kode perilaku. Profesionalisme sejati berfokus pada kualitas kontribusi dan kompetensi diri abdi negara. Ketiga ciri resmi keprofesian adalah mendekati keahlian ideal, memelihara image profesi, dan mengejar kesempatan pengembangan profesional. Dengan demikian, opsi C merupakan pengecualian yang benar.</p><p>Jawaban tepat: <strong>C. Menuntut pemberian fasilitas mewah sebelum bekerja</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan butir ciri pertama kualitas profesionalisme dalam prinsip dasar.</li><li><strong>B</strong>: Merupakan butir ciri kedua kualitas profesionalisme dalam prinsip dasar.</li><li><strong>D</strong>: Merupakan butir ciri ketiga kualitas profesionalisme dalam prinsip dasar.</li><li><strong>E</strong>: Merupakan dorongan jiwa profesionalisme yang dijelaskan dalam pedoman etika.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 23) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Kualitas profesionalisme memiliki ciri-ciri sebagai berikut • mendekati keahlian ideal</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Kualitas profesionalisme seseorang terhadap pekerjaannya ditandai dengan hadirnya...", "options": ["Keinginan menguasai seluruh sarana fasilitas kantor", "Harapan memperoleh tunjangan tertinggi di kementerian", "Kecenderungan membatasi komunikasi dengan unit lain", "Kepatuhan yang didorong oleh kehadiran pengawas dinas", "Kualitas rasa bangga dan percaya diri akan profesinya"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kebanggaan dan Kepercayaan Diri Profesi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Profesionalisme ditandai dengan <strong>kualitas rasa bangga akan profesi yang dipegangnya</strong> sehingga seorang profesional diharapkan <strong>memiliki rasa bangga dan percaya diri akan profesinya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat mengenai sikap batin yang menjiwai seorang profesional sejati. Rasa bangga terhadap profesi melahirkan komitmen mendalam untuk memberikan kontribusi terbaik bagi bangsa. Dalam prinsip profesionalitas dijelaskan bahwa profesionalisme ditandai dengan kualitas rasa bangga akan profesi yang dipegangnya; diharapkan seorang profesional memiliki rasa bangga dan percaya diri akan profesinya. Oleh sebab itu, opsi E adalah jawabannya.</p><p>Jawaban tepat: <strong>E. Kualitas rasa bangga dan percaya diri akan profesinya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pemanfaatan sarana dinas ditujukan bagi kelancaran tugas bersama organisasi.</li><li><strong>B</strong>: Penghasilan aparatur negara diatur melalui regulasi penggajian resmi negara.</li><li><strong>C</strong>: Profesionalisme justru menuntut keterbukaan koordinasi dan sinergi aktif.</li><li><strong>D</strong>: Integritas dan profesionalisme sejati bersumber dari kesadaran nurani mandiri.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 24) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>kualitas rasa bangga akan profesi yang dipegangnya • memiliki rasa bangga dan percaya diri</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Dalam Nilai Kementerian Keuangan, profesionalisme dinyatakan secara eksplisit dengan...", "options": ["Menjaga martabat dan keluhuran norma", "Membangun kemitraan kerja produktif", "Bekerja tuntas atas kompetensi terbaik", "Memberikan pelayanan prima transparan", "Melakukan perbaikan secara kontinu"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pernyataan Nilai Profesionalisme Kemenkeu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Profesionalisme dalam nilai Kementerian Keuangan dinyatakan dengan bekerja tuntas dan akurat atas dasar kompetensi terbaik dengan penuh tanggung jawab dan komitmen yang tinggi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat sesuai rumusan pernyataan nilai Profesionalisme dalam prinsip etika. Pernyataan ini menegaskan bahwa setiap output kerja insan Kementerian Keuangan harus akurat dan tuntas berbasis kompetensi unggul. Rumusan lengkap nilai Profesionalisme berintikan bekerja tuntas dan akurat atas dasar kompetensi terbaik. Dengan demikian, opsi C merupakan pernyataan nilai yang tepat.</p><p>Jawaban tepat: <strong>C. Bekerja tuntas atas kompetensi terbaik</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan butir perilaku utama kedua dari nilai Integritas.</li><li><strong>B</strong>: Merupakan rumusan pernyataan makna dari nilai Sinergi.</li><li><strong>D</strong>: Merupakan rumusan pernyataan makna dari nilai Pelayanan.</li><li><strong>E</strong>: Merupakan rumusan pernyataan makna dari nilai Kesempurnaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 24) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Profesionalisme dalam nilai Kementerian Keuangan dinyatakan dengan • bekerja tuntas dan akurat atas dasar kompetensi terbaik</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Pegawai Kementerian Keuangan dituntut mempunyai keahlian dan pengetahuan luas karena tugas organisasi...", "options": ["Sangat luas dan kompleks", "Mencakup penegakan hukum militer", "Mengatur tata kelola politik luar negeri", "Menaungi seluruh entitas perbankan dunia", "Menetapkan regulasi moneter perbankan"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Alasan Tuntutan Keahlian dan Pengetahuan Luas</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai Kementerian Keuangan dituntut memiliki keahlian dan pengetahuan luas karena <strong>tugas pokok dan fungsi organisasi Kementerian Keuangan sangat luas dan kompleks</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat mengenai alasan mendasar di balik tuntutan wawasan luas bagi pegawai. Mengelola kebijakan fiskal, perbendaharaan, bea cukai, pajak, dan kekayaan negara memerlukan pemahaman multi-disiplin yang mendalam. Pedoman kerja menegaskan bahwa tugas pokok dan fungsi organisasi Kementerian Keuangan sangat luas dan kompleks; oleh karenanya seluruh pegawai dituntut mempunyai keahlian dan pengetahuan yang luas. Oleh sebab itu, opsi A adalah alasannya.</p><p>Jawaban tepat: <strong>A. Sangat luas dan kompleks</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Penegakan hukum militer merupakan ranah pertahanan dan kepolisian negara.</li><li><strong>C</strong>: Pengaturan politik luar negeri dinaungi oleh Kementerian Luar Negeri.</li><li><strong>D</strong>: Kementerian Keuangan mengelola fiskal negara, bukan perbankan dunia.</li><li><strong>E</strong>: Regulasi moneter perbankan merupakan wewenang bank sentral.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 24) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Tugas pokok dan fungsi organisasi Kementerian Keuangan • sangat luas dan kompleks</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Dalam aplikasi sehari-hari, perwujudan dari perilaku utama mempunyai keahlian dan pengetahuan yang luas adalah...", "options": ["Mengajukan penambahan alokasi belanja operasional kantor", "Menghadiri seluruh kegiatan seminar tanpa kaitan tugas", "Menyusun regulasi kenaikan pangkat pegawai secara mandiri", "Bekerja dengan efisien dan berkualitas", "Membatasi waktu pelayanan langsung kepada masyarakat"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Bekerja Efisien dan Berkualitas</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam aplikasi sehari-hari, perilaku utama pertama nilai profesional ini adalah <strong>bekerja dengan efisien dan berkualitas</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat mengenai wujud praktis dari kepemilikan pengetahuan dan keahlian luas. Wawasan luas mencegah pemborosan waktu kerja (efisien), sedangkan keahlian menjamin hasil kerja yang bermutu tinggi (berkualitas). Dalam praktik sehari-hari, dalam aplikasi sehari-hari, perilaku utama pertama nilai profesional ini adalah bekerja dengan efisien dan berkualitas. Dengan demikian, opsi D adalah wujud perilakunya.</p><p>Jawaban tepat: <strong>D. Bekerja dengan efisien dan berkualitas</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pengajuan belanja kantor merupakan urusan administratif perencanaan anggaran.</li><li><strong>B</strong>: Pelatihan dan seminar harus relevan dengan kebutuhan kompetensi tugas dinas.</li><li><strong>C</strong>: Regulasi kepegawaian nasional merupakan ranah kewenangan kementerian terkait.</li><li><strong>E</strong>: Waktu pelayanan publik diatur secara penuh untuk memenuhi hak masyarakat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 24) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>aplikasi sehari-hari, perilaku utama pertama • bekerja dengan efisien dan berkualitas</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Penggantian proses bisnis manual menjadi berbasis teknologi informasi di Kementerian Keuangan dicontohkan melalui penerapan...", "options": ["Pemberlakuan absensi kertas manual bertanda tangan basah", "E-registration, penyerahan softfile, dan administrasi data", "Pengiriman berkas fisik dokumen dinas melalui jasa pos darat", "Penyimpanan arsip formulir pajak pada gudang penyimpanan fisik", "Pembayaran setoran penerimaan negara secara tunai di kantor"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penerapan Teknologi Informasi dalam Proses Bisnis</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pengembangan teknologi informasi dicontohkan melalui pemanfaatan internet dan intranet untuk <strong><em>e-registration</em>, penyerahan dokumen kerja dalam bentuk <em>softfile</em>, serta administrasi data</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat sesuai contoh modernisasi teknologi informasi yang dipaparkan dalam ketentuan kerja. Digitalisasi proses bisnis memudahkan pengguna layanan menyelesaikan urusan perpajakan dan perbendaharaan secara cepat dan praktis. Dalam implementasi digital dicatat bahwa sebagai contoh, dengan jaringan internet atau intranet, dapat dilakukan e-registration, penyerahan dokumen kerja dalam bentuk softfile, serta administrasi data. Oleh karena itu, opsi B memuat contoh yang tepat.</p><p>Jawaban tepat: <strong>B. E-registration, penyerahan softfile, dan administrasi data</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Presensi aparatur kini telah menggunakan sistem digital elektronik terintegrasi.</li><li><strong>C</strong>: Pengiriman dokumen fisik antarunit kini digantikan oleh naskah dinas elektronik.</li><li><strong>D</strong>: Pengarsipan formulir perpajakan telah beralih ke basis data komputasi awan.</li><li><strong>E</strong>: Penerimaan kas negara dilakukan secara digital melalui sistem kas negara online.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 24) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>e-registration, penyerahan dokumen kerja • dalam bentuk softfile, serta administrasi data</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Salah satu ciri seorang profesional dalam bekerja dengan hati adalah kemampuan mempertemukan dua simpul yang berlawanan, yaitu...", "options": ["Jam kerja dinas dan waktu istirahat pribadi pegawai", "Target penerimaan pajak dan tarif insentif fiskal industri", "Peraturan disiplin pegawai dan hak kenaikan pangkat", "Kewenangan kantor pusat dan wewenang kantor perwakilan", "SOP dan hati nurani atau perasaan"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Mempertemukan Simpul SOP dan Hati Nurani</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Salah satu ciri seorang profesional adalah <strong>mampu mempertemukan dua simpul yang berlawanan tersebut, yaitu SOP dan hati nurani atau perasaan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat mengenai keahlian memadukan kepatuhan prosedur dengan kehangatan nurani. Prosedur baku (SOP) memberikan kepastian tata kelola, sementara hati nurani memberikan empati kemanusiaan dalam melayani masyarakat. Pedoman pelayanan menerangkan secara eksplisit bahwa dalam bekerja terdapat dua hal penting yang harus diperhatikan yaitu SOP dan hati nurani atau perasaan; salah satu ciri profesional adalah mampu mempertemukan dua simpul yang berlawanan tersebut. Dengan demikian, opsi E adalah jawabannya.</p><p>Jawaban tepat: <strong>E. SOP dan hati nurani atau perasaan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Bukan merupakan dua simpul berlawanan yang dijelaskan dalam etika pelayanan.</li><li><strong>B</strong>: Merupakan instrumen kebijakan ekonomi makro pada manajemen fiskal negara.</li><li><strong>C</strong>: Merupakan hak dan kewajiban aparatur yang saling melengkapi dalam kepegawaian.</li><li><strong>D</strong>: Merupakan pembagian wewenang kelembagaan vertikal dalam struktur organisasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 24) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>mampu mempertemukan dua simpul yang berlawanan • SOP dan hati nurani atau perasaan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Secara konseptual, ungkapan kata lain yang semakna dengan bekerja dengan hati adalah...", "options": ["Mengharapkan pujian dari masyarakat pengguna jasa", "Bekerja dengan tujuan utama mengejar imbalan materi", "Mencintai pekerjaan kita", "Memilih pekerjaan yang bebas dari risiko operasional", "Menyerahkan keputusan rumit kepada pimpinan"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Makna Bekerja dengan Hati</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Kata lain bekerja dengan hati adalah mencintai pekerjaan kita</strong> dengan sepenuh dedikasi dan ketulusan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat mengenai persamaan makna dari konsep bekerja dengan hati. Mencintai pekerjaan mengubah kewajiban menjadi sebuah kegembiraan dalam berkontribusi bagi kemaslahatan publik. Secara lugas ditegaskan bahwa kata lain bekerja dengan hati adalah mencintai pekerjaan kita. Oleh sebab itu, opsi C merupakan makna yang dimaksud.</p><p>Jawaban tepat: <strong>C. Mencintai pekerjaan kita</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pujian masyarakat merupakan dampak layanan, bukan motivasi batin mencintai tugas.</li><li><strong>B</strong>: Bekerja karena imbalan materi bertentangan dengan filosofi bekerja dengan hati.</li><li><strong>D</strong>: Profesionalisme menuntut keberanian menghadapi tantangan dan risiko pekerjaan.</li><li><strong>E</strong>: Tanggung jawab teknis jabatan wajib diselesaikan secara mandiri dan akuntabel.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 24) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Kata lain bekerja dengan hati adalah • mencintai pekerjaan kita</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Seorang petugas perbendaharaan menolak bekerja sekenanya atau sekadar asal selesai, karena meyakini bahwa bekerja adalah...", "options": ["Panggilan jiwa", "Beban keterpaksaan", "Kewajiban semu", "Rutinitas mekanis", "Formalitas birokrasi"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Bekerja karena Panggilan Jiwa</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Seorang profesional menyadari bahwa <strong>bekerja bukan hanya karena dibayar tinggi, tetapi karena panggilan jiwa</strong> untuk mengabdi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat sesuai motivasi tertinggi dari insan profesional yang bekerja dengan hati. Menganggap pekerjaan sebagai panggilan jiwa melahirkan kesungguhan dalam menyempurnakan setiap rincian tugas negara. Dedikasi kerja bermakna bekerja bukan semata-mata karena dibayar tinggi, tetapi karena panggilan jiwa. Dengan demikian, panggilan jiwa adalah keyakinan yang mendasarinya.</p><p>Jawaban tepat: <strong>A. Panggilan jiwa</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Bekerja dengan keterpaksaan ditolak keras dalam budaya profesionalisme.</li><li><strong>C</strong>: Kewajiban kedinasan harus dijalankan dengan kesadaran moral yang sungguh-sungguh.</li><li><strong>D</strong>: Rutinitas mekanis menumpulkan kreativitas dan kepekaan nurani dalam melayani.</li><li><strong>E</strong>: Formalitas birokrasi mengaburkan esensi pemberian pelayanan publik yang bermutu.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 24) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Bekerja bukan hanya karena dibayar tinggi • tetapi karena panggilan jiwa</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Ungkapan bijak yang mendasari filosofi bekerja dengan hati berbunyi: 'Bekerjalah Anda melebihi apa yang Anda terima, maka Anda akan...'", "options": ["Mendapat kemudahan promosi jabatan dinas", "Memperoleh apresiasi khusus dari pimpinan", "Diberikan layanan cepat oleh rekan kantor", "Menerima melebihi apa yang Anda kerjakan", "Mencapai kepuasan materiil yang diharapkan"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Filosofi Memberi Kontribusi Melampaui Imbalan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Filosofi bekerja dengan hati ditegaskan oleh ungkapan: <strong>'bekerjalah Anda melebihi apa yang Anda terima, maka Anda akan menerima melebihi apa yang Anda kerjakan'</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat sesuai ungkapan mutiara yang menutup pembahasan nilai Profesionalisme dalam etika kedinasan. Dedikasi yang melampaui perhitungan materiil akan membuahkan berkah, penghargaan, dan kepuasan batin yang jauh lebih besar di masa depan. Dalam etos kerja tertulis bahwa sebuah ungkapan mengatakan, 'bekerjalah Anda melebihi apa yang Anda terima, maka Anda akan menerima melebihi apa yang Anda kerjakan; hal ini tidak akan pernah terjadi kalau kita tidak bekerja dengan hati.' Oleh karena itu, opsi D merupakan kelanjutan ungkapan tersebut.</p><p>Jawaban tepat: <strong>D. Menerima melebihi apa yang Anda kerjakan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kemudahan promosi bukan esensi filosofis ungkapan bekerja dengan hati.</li><li><strong>B</strong>: Apresiasi pimpinan adalah dampak luar, bukan pesan moral ungkapan tersebut.</li><li><strong>C</strong>: Bukan merupakan rumusan prinsip dedikasi kerja yang dianut.</li><li><strong>E</strong>: Kepuasan batiniah melampaui pencapaian materiil semata dalam etika pengabdian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 24) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>bekerjalah Anda melebihi apa yang Anda terima • maka Anda akan menerima melebihi apa yang Anda kerjakan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 02", "difficulty": "C4 - Analisis", "question": "Bekerja dengan hati menolak segala bentuk sikap kerja berikut ini, KECUALI...", "options": ["Meninggalkan tugas kedinasan tanpa kepastian", "Mempertemukan prosedur dengan kepekaan nurani", "Mengerjakan tugas secara asal-asalan dan sekenanya", "Bekerja sekadar yang penting selesai demi formalitas", "Melaksanakan pekerjaan dengan rasa keterpaksaan"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sikap Menolak Bekerja Asal-Asalan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Bekerja dengan hati menolak tugas ditinggalkan, dikerjakan asal-asalan, sekenanya, atau karena keterpaksaan; serta <strong>mampu mempertemukan SOP dengan hati nurani</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat karena mempertemukan kepatuhan prosedur dengan kepekaan nurani adalah sikap positif yang justru dianjurkan dalam bekerja dengan hati. Sementara opsi A, C, D, dan E merupakan sikap-sikap negatif yang secara tegas ditolak oleh filosofi bekerja dengan hati. Dalam dedikasi kerja ditekankan bahwa tidak ada tugas yang ditinggalkan atau dikerjakan asal-asalan, sekenanya, yang penting selesai, atau bermakna keterpaksaan; profesional mampu mempertemukan SOP dan hati nurani. Dengan demikian, opsi B adalah pengecualian yang benar.</p><p>Jawaban tepat: <strong>B. Mempertemukan prosedur dengan kepekaan nurani</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan perilaku negatif meninggalkan tugas yang secara tegas ditolak pedoman kerja.</li><li><strong>C</strong>: Merupakan perilaku negatif asal-asalan yang bertentangan dengan profesionalisme.</li><li><strong>D</strong>: Merupakan perilaku negatif formalitas semata yang ditolak dalam pedoman kerja.</li><li><strong>E</strong>: Merupakan perilaku negatif bekerja dengan keterpaksaan yang ditolak pedoman kerja.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 24) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>dikerjakan asal-asalan, sekenanya, yang penting selesai • bekerja dengan hati</strong></div></div>"}];
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
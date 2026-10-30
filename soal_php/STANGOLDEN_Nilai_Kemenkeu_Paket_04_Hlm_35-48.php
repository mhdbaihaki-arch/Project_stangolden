<?php
/**
 * STANGOLDEN.exam • Nilai-Nilai Kemenkeu Paket 04 (Halaman 35–48)
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
// $DB->get_record("stgnk_p04_rank") => tabel fisik "mdlhv_stgnk_p04_rank".
// Jangan menambahkan prefix secara manual di konstanta berikut.
// Penyimpanan bersama melalui database Moodle. Nama tabel pendek, kompatibel dengan batas Moodle.
const STG_LB_T = 'stgnk_p04_rank';
const STG_REVIEW_T = 'stgnk_p04_rev';
const STG_ANNO_T = 'stgnk_p04_anno';
const STG_KEY = [0, 1, 2, 3, 4, 0, 1, 2, 3, 4, 0, 1, 2, 3, 4, 0, 1, 2, 3, 4, 0, 1, 2, 3, 4, 0, 1, 2, 3, 4, 0, 1, 2, 3, 4, 0, 1, 2, 3, 4, 0, 1, 2, 3, 4, 0, 1, 2, 3, 4, 0, 1, 2, 3, 4, 0, 1, 2, 3, 4, 0, 1, 2, 3, 4, 0, 1, 2, 3, 4, 0, 1, 2, 3, 4, 0, 1, 2, 3, 4, 0, 1, 2, 3, 4, 0, 1, 2, 3, 4, 0, 1, 2, 3, 4, 0, 1, 2, 3, 4];
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
            error_log('STANGOLDEN NK P04 HLM35-48 SETUP: ' . $e->getMessage());
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
    }catch(Throwable $e){error_log('STANGOLDEN NK P04 HLM35-48: '.$e->getMessage());stgnk_json(['success'=>false,'message'=>($e instanceof RuntimeException ? $e->getMessage() : 'Penyimpanan belum tersedia. Hubungi administrator.')],500);}
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
<title>Latihan Nilai-Nilai Kemenkeu Paket 04 (Hlm. 35–48) • STANGOLDEN</title>
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
      <span class="series-tag">Paket 04 • Hlm. 35–48</span>
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
<h1>Latihan Nilai-Nilai Kemenkeu <span class="series-tag">Paket 04 • Hlm. 35–48</span></h1>
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
        <h2 class="leaderboard-title" id="rankingModalTitle">🏆 Lihat Ranking Paket 04</h2>
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
const STG_LOCAL_KEY="stg_nk_p04_review_user_<?= (int)$USER->id ?>";
const HAS_FULL_REVIEW=true;
const IS_SITE_ADMIN=<?= is_siteadmin($USER) ? "true" : "false" ?>;
const LEADERBOARD_ENDPOINT=window.location.href.split("?")[0].split("#")[0];
const questions=[{"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Peran penting pimpinan unit dalam pengawasan intern diwujudkan melalui pemberdayaan UKI untuk...", "options": ["Meningkatkan efektivitas tata kelola, manajemen risiko, dan pengendalian", "Menyusun pembukuan keuangan serta penatausahaan aset secara mandiri", "Menetapkan standar kompetensi teknis bagi seluruh pejabat fungsional", "Mengelola administrasi persuratan dan kearsipan pada sekretariat unit", "Melakukan evaluasi berkala terhadap serapan anggaran belanja kantor"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pemberdayaan Unit Kepatuhan Internal</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pimpinan unit wajib <strong>memberdayakan Unit Kepatuhan Internal</strong> guna <strong>meningkatkan efektivitas tata kelola, manajemen risiko, dan pengendalian intern</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat mengenai sasaran pemberdayaan UKI di lingkungan kementerian. Keberadaan UKI sebagai lini pertahanan kedua bertujuan memastikan sistem pengendalian internal dan manajemen risiko berjalan optimal demi tercapainya tata kelola yang baik. Ketentuan pengawasan intern secara eksplisit mengamanatkan peningkatan tata kelola, manajemen risiko, dan pengendalian intern. Dengan demikian, opsi A adalah rumusan fungsi yang benar.</p><p>Jawaban tepat: <strong>A. Meningkatkan efektivitas tata kelola, manajemen risiko, dan pengendalian</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan tugas teknis fungsi perbendaharaan dan pengelola barang milik negara.</li><li><strong>C</strong>: Merupakan wewenang unit pengelola kepegawaian dan pengembangan sumber daya manusia.</li><li><strong>D</strong>: Merupakan fungsi bagian umum atau tata usaha perkantoran pada sekretariat.</li><li><strong>E</strong>: Merupakan tugas bagian perencanaan dan evaluasi anggaran satuan kerja.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 35) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Memberdayakan Unit Kepatuhan Internal • pengendalian intern</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Dalam rangka pelaksanaan pengawasan intern, pimpinan unit kerja diinstruksikan untuk membangun...", "options": ["Sistem pelaporan kinerja berbasis aplikasi mandiri tingkat satker", "Hubungan kemitraan yang konstruktif dengan Inspektorat Jenderal", "Forum koordinasi berkala dengan aparat penegak hukum setempat", "Mekanisme pengaduan masyarakat yang terpisah dari sistem kementerian", "Kerangka kerja sama pengawasan dengan lembaga swadaya masyarakat"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kemitraan Konstruktif dengan Inspektorat Jenderal</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pimpinan unit kerja wajib <strong>membangun hubungan kemitraan yang konstruktif dengan Inspektorat Jenderal</strong> dalam pelaksanaan pengawasan intern.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat mengenai sinergi pengawasan di lingkungan kementerian. Hubungan yang harmonis dan terbuka antara unit kerja dengan aparat pengawasan intern pemerintah memperkuat deteksi dini penyimpangan serta mendorong perbaikan tata kelola. Himbauan kedinasan menetapkan keharusan membangun kemitraan konstruktif dengan Inspektorat Jenderal. Oleh sebab itu, opsi B adalah langkah yang tepat.</p><p>Jawaban tepat: <strong>B. Hubungan kemitraan yang konstruktif dengan Inspektorat Jenderal</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan inisiatif sistem teknologi informasi lokal yang bukan fokus pengawasan intern.</li><li><strong>C</strong>: Merupakan hubungan koordinasi eksternal penegakan hukum di luar fungsi Itjen.</li><li><strong>D</strong>: Merupakan saluran aduan tersendiri yang bertentangan dengan sentralisasi saluran pengaduan kementerian.</li><li><strong>E</strong>: Merupakan pelibatan pihak luar yang tidak termasuk dalam skema pengawasan intern kedinasan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 35) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Membangun hubungan kemitraan yang konstruktif • Inspektorat Jenderal</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Langkah preventif yang wajib dilaksanakan pimpinan unit guna menekan potensi pelanggaran pegawai adalah...", "options": ["Mengadakan rotasi berkala seluruh staf pelaksana antar-seksi kantor", "Menyelenggarakan bimbingan teknis penyusunan lembar sasaran kinerja", "Menginternalisasi nilai kementerian serta penegakan kode etik disiplin", "Menerapkan jam kerja tambahan secara terjadwal pada triwulan akhir", "Memperketat verifikasi administrasi pengajuan lembur dan uang makan"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Internalisasi Nilai dan Penegakan Disiplin</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pimpinan unit wajib <strong>menginternalisasi Nilai-Nilai Kementerian Keuangan dan ketentuan-ketentuan yang berhubungan dengan penegakan kode etik dan disiplin</strong> kepada seluruh pegawai.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat mengenai strategi pencegahan pelanggaran moral aparatur. Penanaman nilai dan pemahaman kode etik secara berkelanjutan membentuk benteng moral pegawai sehingga terhindar dari perilaku koruptif maupun pelanggaran disiplin. Panduan kedinasan menginstruksikan internalisasi nilai dan ketentuan kode etik disiplin kepada seluruh jajaran. Maka dari itu, opsi C adalah tindakan pencegahan yang benar.</p><p>Jawaban tepat: <strong>C. Menginternalisasi nilai kementerian serta penegakan kode etik disiplin</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan kebijakan manajemen pola mutasi internal untuk penyegaran tugas kedinasan.</li><li><strong>B</strong>: Merupakan pelatihan manajerial untuk peningkatan pemahaman indikator kinerja pegawai.</li><li><strong>D</strong>: Merupakan penyesuaian beban operasional kantor saat batas waktu anggaran mendekat.</li><li><strong>E</strong>: Merupakan fungsi pengendalian administratif keuangan satuan kerja terkait belanja pegawai.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 35) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Menginternalisasi Nilai-Nilai Kementerian Keuangan • penegakan kode etik dan disiplin</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Himbauan peningkatan penerapan nilai kementerian menjadi sangat relevan terutama untuk meningkatkan disiplin serta...", "options": ["Menstandarisasi alur disposisi naskah dinas elektronik kementerian", "Mengukur capaian target penerimaan kas negara pada triwulan berjalan", "Memperluas jejaring kemitraan fiskal dengan instansi pemerintah daerah", "Mencegah terjadinya potensi perbuatan pelanggaran disiplin dan hukum", "Mengoptimalkan pemanfaatan fasilitas sarana dan prasarana perkantoran"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Relevansi Peningkatan Disiplin Pegawai</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Penggiatan penerapan nilai dan etika ditujukan untuk <strong>meningkatkan disiplin dan mencegah terjadinya potensi-potensi perbuatan yang dapat mengarah pada pelanggaran disiplin dan pelanggaran hukum</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat mengenai latar belakang penggiatan disiplin kementerian. Nilai luhur organisasi berfungsi sebagai rambu-rambu pencegahan dini dari segala bentuk perbuatan tercela yang berpotensi merusak reputasi institusi dan melanggar hukum. Dalam arahan kedinasan ditegaskan pentingnya pencegahan potensi pelanggaran disiplin dan hukum. Oleh karena itu, opsi D adalah jawaban yang tepat.</p><p>Jawaban tepat: <strong>D. Mencegah terjadinya potensi perbuatan pelanggaran disiplin dan hukum</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan tata kelola administrasi surat-menyurat kedinasan berbasis sistem otomasi.</li><li><strong>B</strong>: Merupakan indikator target fiskal kuantitatif pada direktorat teknis penerimaan.</li><li><strong>C</strong>: Merupakan fungsi koordinasi hubungan kelembagaan antar-tingkat pemerintahan.</li><li><strong>E</strong>: Merupakan pemeliharaan aset fisik kantor yang diatur dalam manajemen sarana prasarana.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 35) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>meningkatkan disiplin dan mencegah terjadinya potensi-potensi perbuatan yang • pelanggaran disiplin dan pelanggaran hukum</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Penggiatan penerapan Nilai Kementerian Keuangan ditujukan untuk menjamin terpeliharanya tata tertib dalam...", "options": ["Penyelenggaraan audit operasional perbendaharaan daerah", "Penyusunan standar biaya masukan kementerian lembaga", "Penetapan rencana penarikan dana harian kantor satker", "Pengelolaan data base kepegawaian aparatur sipil negara", "Kelancaran pelaksanaan tugas dan fungsi seluruh pegawai"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuan Penerapan Nilai dan Kode Etik</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Penerapan nilai dan kode etik ditujukan untuk <strong>menjamin terpeliharanya tata tertib dalam kelancaran pelaksanaan tugas dan fungsi seluruh pegawai Kementerian Keuangan tanpa terkecuali</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat tentang muara dari ketertiban perilaku kerja aparatur. Ketertiban internal yang terpelihara secara konsisten menjamin iklim kerja yang kondusif sehingga seluruh tugas dan fungsi kementerian dapat terselenggara secara lancar dan bermutu. Penjelasan resmi modul menyatakan tujuannya adalah kelancaran pelaksanaan tugas dan fungsi seluruh pegawai. Dengan demikian, opsi E adalah rumusan yang benar.</p><p>Jawaban tepat: <strong>E. Kelancaran pelaksanaan tugas dan fungsi seluruh pegawai</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan tugas teknis pemeriksaan keuangan pada kantor perwakilan perbendaharaan.</li><li><strong>B</strong>: Merupakan ranah penyusunan regulasi anggaran belanja negara di tingkat kementerian.</li><li><strong>C</strong>: Merupakan perencanaan kas jangka pendek yang disusun oleh pejabat pembuat komitmen.</li><li><strong>D</strong>: Merupakan pengelolaan data kepegawaian pada Biro Sumber Daya Manusia aparatur.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 35) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menjamin terpeliharanya tata tertib dalam kelancaran • pelaksanaan tugas dan fungsi seluruh pegawai</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Terbitnya PMK Nomor 190/PMK.01/2018 tentang Kode Etik dilatarbelakangi secara sosiologis oleh adanya...", "options": ["Perubahan teknologi, nilai etika, budaya, dan perilaku publik", "Penurunan penerimaan negara dari sektor bea kepabeanan", "Pergantian seluruh perangkat lunak aplikasi kantor dinas", "Desakan dari organisasi perburuhan luar kementerian", "Pengurangan alokasi anggaran operasional pengawasan"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Latar Belakang Sosiologis PMK 190/2018</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Terbitnya PMK Nomor 190/PMK.01/2018 dilatarbelakangi oleh <strong>adanya perubahan teknologi, nilai etika, budaya, dan perilaku yang terjadi di masyarakat</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat mengenai latar belakang sosiologis penerbitan kode etik. Perkembangan zaman, disrupsi teknologi, dan pergeseran norma sosial di masyarakat menuntut pembaruan standar perilaku aparatur agar tetap relevan, adaptif, dan berintegritas tinggi. Penjelasan resmi menyebutkan perubahan teknologi, nilai etika, budaya, dan perilaku di masyarakat. Dengan demikian, opsi A adalah latar belakang yang tepat.</p><p>Jawaban tepat: <strong>A. Perubahan teknologi, nilai etika, budaya, dan perilaku publik</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Penurunan penerimaan kas negara merupakan fluktuasi ekonomi fiskal.</li><li><strong>C</strong>: Pergantian sistem peranti lunak merupakan agenda teknis digitalisasi kantor.</li><li><strong>D</strong>: Tuntutan serikat buruh eksternal bukan pemicu formal regulasi kode etik aparatur.</li><li><strong>E</strong>: Penyesuaian anggaran operasional merupakan kebijakan efisiensi belanja dinas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 35) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>perilaku yang terjadi di masyarakat • Nomor 190/PMK.01/2018 tentang Kode Etik</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Selain mencegah pelanggaran disiplin, tujuan utama penerbitan PMK Nomor 190/PMK.01/2018 adalah...", "options": ["Mengatur tata cara pemberian sanksi administratif kepegawaian", "Menjaga martabat dan kehormatan Pegawai Negeri Sipil kementerian", "Menetapkan standar minimum fasilitas ruang konsultasi aparatur", "Memperluas jejaring kemitraan kerja sama internasional fiskal", "Merumuskan indikator keberhasilan audit kepatuhan perbendaharaan"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuan Penerbitan PMK 190/PMK.01/2018</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>PMK Nomor 190/PMK.01/2018 ditujukan untuk mencegah pelanggaran disiplin serta <strong>menjaga martabat dan kehormatan Pegawai Negeri Sipil di lingkungan Kementerian Keuangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat mengenai tujuan pokok regulasi kode etik kementerian. Kehormatan dan martabat korps PNS merupakan aset integritas yang sangat berharga dalam mempertahankan kepercayaan publik terhadap birokrasi keuangan negara. Regulasi terkait menegaskan tujuan menjaga martabat dan kehormatan Pegawai Negeri Sipil. Oleh karena itu, opsi B adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>B. Menjaga martabat dan kehormatan Pegawai Negeri Sipil kementerian</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan materi regulasi hukum disiplin PNS umum pada Peraturan Pemerintah terkait.</li><li><strong>C</strong>: Merupakan standarisasi sarana prasarana fisik kantor pada unit pendukung.</li><li><strong>D</strong>: Merupakan kerangka kerja sama luar negeri pada bidang diplomasi ekonomi fiskal.</li><li><strong>E</strong>: Merupakan instrumen pengawasan internal pada unit inspektorat bidang perbendaharaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 35) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menjaga martabat dan • kehormatan Pegawai Negeri Sipil di lingkungan Kementerian Keuangan</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Makna nilai Integritas yang ditegaskan kembali dalam PMK Nomor 190/PMK.01/2018 adalah...", "options": ["Bekerja tuntas dan akurat atas dasar kompetensi terbaik dan tanggung jawab", "Membangun hubungan kemitraan internal yang produktif dan harmonis", "Berpikir, berkata, bertindak terpuji berpegang kode etik dan moral", "Memberikan pelayanan yang berorientasi pada kepuasan pemangku tugas", "Melakukan upaya perbaikan tanpa henti untuk menjadi yang terbaik"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Makna Nilai Integritas PMK 190/2018</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Integritas</strong> berarti seluruh pegawai harus <strong>berpikir, berkata, berperilaku, dan bertindak dengan baik dan benar serta selalu memegang teguh Kode Etik dan prinsip-prinsip moral</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat mengenai definisi nilai Integritas dalam PMK Nomor 190/PMK.01/2018. Integritas menuntut keselarasan utuh antara pikiran, perkataan, dan perbuatan aparatur sipil negara yang bersandar pada landasan moral dan kode etik kedinasan. Ketentuan PMK secara eksplisit menetapkan berpikir, berkata, berperilaku, dan bertindak dengan baik dan benar. Dengan demikian, opsi C adalah rumusan yang tepat.</p><p>Jawaban tepat: <strong>C. Berpikir, berkata, bertindak terpuji berpegang kode etik dan moral</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan definisi nilai Profesionalisme dalam PMK Nomor 190/PMK.01/2018.</li><li><strong>B</strong>: Merupakan definisi nilai Sinergi dalam PMK Nomor 190/PMK.01/2018.</li><li><strong>D</strong>: Merupakan definisi nilai Pelayanan dalam PMK Nomor 190/PMK.01/2018.</li><li><strong>E</strong>: Merupakan definisi nilai Kesempurnaan dalam PMK Nomor 190/PMK.01/2018.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 36) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>berpikir, berkata, berperilaku, dan • prinsip-prinsip moral</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Nilai Profesionalisme dalam PMK Nomor 190/PMK.01/2018 diartikan sebagai keharusan pegawai untuk...", "options": ["Berpikir, bertindak, dan berperilaku jujur serta memegang prinsip moral", "Membangun dan memastikan kerja sama internal yang harmonis produktif", "Memberikan layanan sepenuh hati demi kepuasan pemangku kepentingan", "Bekerja tuntas, akurat, berlandaskan kompetensi dan komitmen tinggi", "Melakukan upaya perbaikan di segala bidang untuk memberi yang terbaik"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Makna Nilai Profesionalisme PMK 190/2018</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Profesionalisme</strong> berarti seluruh pegawai harus <strong>bekerja dengan tuntas dan akurat berdasarkan kompetensi terbaik dan penuh tanggung jawab serta komitmen yang tinggi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat mengenai hakikat nilai Profesionalisme aparatur. Bekerja profesional menuntut ketuntasan eksekusi tugas, ketepatan hasil kerja, keahlian teknis yang mumpuni, serta rasa tanggung jawab penuh terhadap amanah jabatan. Regulasi etika kementerian merumuskan bekerja tuntas dan akurat berlandaskan kompetensi terbaik. Oleh karena itu, opsi D adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>D. Bekerja tuntas, akurat, berlandaskan kompetensi dan komitmen tinggi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan makna esensial nilai Integritas dalam PMK Nomor 190/PMK.01/2018.</li><li><strong>B</strong>: Merupakan makna esensial nilai Sinergi dalam PMK Nomor 190/PMK.01/2018.</li><li><strong>C</strong>: Merupakan makna esensial nilai Pelayanan dalam PMK Nomor 190/PMK.01/2018.</li><li><strong>E</strong>: Merupakan makna esensial nilai Kesempurnaan dalam PMK Nomor 190/PMK.01/2018.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 36) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>bekerja dengan tuntas dan akurat • kompetensi terbaik dan penuh tanggung jawab</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Definisi nilai Sinergi dalam PMK Nomor 190/PMK.01/2018 menekankan komitmen seluruh pegawai untuk...", "options": ["Berpikir, berkata, dan bertindak jujur serta menjunjung kode etik", "Bekerja dengan tuntas dan akurat berdasarkan kompetensi terbaik", "Memberikan pelayanan yang memenuhi kepuasan pemangku kepentingan", "Senantiasa melakukan upaya perbaikan di segala bidang tugas dinas", "Membangun kerja sama internal produktif serta kemitraan harmonis"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Makna Nilai Sinergi PMK 190/2018</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Sinergi</strong> berarti seluruh pegawai berkomitmen untuk <strong>membangun dan memastikan hubungan kerja sama internal yang produktif serta kemitraan yang harmonis dengan para pemangku kepentingan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat sesuai rumusan nilai Sinergi dalam regulasi kode etik. Kolaborasi lintas unit dan kemitraan harmonis antarpemangku kepentingan menghilangkan sekat birokrasi sehingga melahirkan karya kerja yang berkualitas tinggi. Bunyi ketentuan PMK menegaskan pembangunan kerja sama internal produktif dan kemitraan harmonis. Dengan demikian, opsi E adalah rumusan yang tepat.</p><p>Jawaban tepat: <strong>E. Membangun kerja sama internal produktif serta kemitraan harmonis</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan definisi nilai Integritas dalam tata kelola nilai kementerian.</li><li><strong>B</strong>: Merupakan definisi nilai Profesionalisme dalam tata kelola nilai kementerian.</li><li><strong>C</strong>: Merupakan definisi nilai Pelayanan dalam tata kelola nilai kementerian.</li><li><strong>D</strong>: Merupakan definisi nilai Kesempurnaan dalam tata kelola nilai kementerian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 36) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>membangun dan • memastikan hubungan kerja sama internal yang produktif</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Kewajiban kode perilaku nilai integritas terkait reputasi lembaga mengamanatkan pegawai untuk...", "options": ["Menjaga citra, harkat, dan martabat Kementerian Keuangan", "Menjaga kerahasiaan data dan informasi jabatan serta negara", "Menyelesaikan tugas kedinasan secara tuntas dan tepat waktu", "Mengembangkan kompetensi diri secara berkelanjutan dan mandiri", "Terbuka terhadap usulan perbaikan dan pengetahuan baru"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kode Etik Integritas: Citra Lembaga</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kode etik integritas mewajibkan: <strong>Menjaga citra, harkat, dan martabat Kementerian Keuangan di berbagai forum, baik formal maupun informal di dalam maupun di luar negeri</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat mengenai kewajiban etis menjaga marwah organisasi. Setiap pegawai berperan sebagai duta representasi institusi di ruang publik, sehingga perilakunya wajib senantiasa mencerminkan keluhuran harkat dan martabat korps Kementerian Keuangan. Rincian butir integritas menetapkan keharusan menjaga citra, harkat, dan martabat kementerian. Oleh sebab itu, opsi A adalah kewajiban yang dimaksud.</p><p>Jawaban tepat: <strong>A. Menjaga citra, harkat, dan martabat Kementerian Keuangan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan kewajiban etika pada butir nilai Profesionalisme.</li><li><strong>C</strong>: Merupakan kewajiban etika ketuntasan tugas pada nilai Profesionalisme.</li><li><strong>D</strong>: Merupakan kewajiban etika optimalisasi keahlian pada nilai Profesionalisme.</li><li><strong>E</strong>: Merupakan kewajiban etika keterbukaan inovasi pada nilai Kesempurnaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 36) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Menjaga citra, harkat, dan martabat Kementerian Keuangan • baik formal maupun informal</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Dalam perhelatan pesta demokrasi pemilihan umum, kode etik nilai integritas mewajibkan aparatur untuk...", "options": ["Membantu sosialisasi visi misi pasangan calon kepala daerah", "Bersikap netral dalam pemilihan presiden, pilkada, dan legislatif", "Menghadiri deklarasi dukungan partai politik di luar jam dinas", "Memasang atribut kampanye di kendaraan operasional kementerian", "Mengarahkan pilihan politik warga binaan saat proses pelayanan"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kode Etik Integritas: Netralitas Pemilu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai wajib: <strong>Bersikap netral dalam Pemilihan Calon Presiden dan Wakil Presiden, Kepala Daerah dan Wakil Kepala Daerah, serta Anggota Legislatif Pusat dan Daerah</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat tentang asas netralitas aparatur sipil negara. Netralitas politik menjamin pelayanan publik tetap profesional, adil, dan tidak terdistorsi oleh kepentingan partisan demi persatuan bangsa. Pedoman etika integritas secara tegas mewajibkan bersikap netral dalam pilpres, pilkada, dan pemilu legislatif. Dengan demikian, opsi B adalah kewajiban yang benar.</p><p>Jawaban tepat: <strong>B. Bersikap netral dalam pemilihan presiden, pilkada, dan legislatif</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan keterlibatan kampanye politik praktis yang dilarang keras bagi seluruh ASN.</li><li><strong>C</strong>: Merupakan pelanggaran netralitas meskipun dilakukan di luar jam dinas kantor.</li><li><strong>D</strong>: Merupakan penyalahgunaan fasilitas negara untuk kepentingan partisan partai politik.</li><li><strong>E</strong>: Merupakan bentuk mobilisasi dukungan politik yang mencemari netralitas layanan publik.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 36) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Bersikap netral dalam Pemilihan Calon Presiden • Daerah dan Wakil Kepala Daerah</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Terkait aktivitas di ranah digital internet, kode etik nilai integritas menginstruksikan pegawai untuk...", "options": ["Mengalihkan seluruh komunikasi kedinasan ke sistem persuratan konvensional", "Menyerahkan pengelolaan akun pribadi kepada admin unit kehumasan", "Bijak dan mematuhi tata krama dalam penggunaan media sosial", "Mempublikasikan draf kebijakan fiskal sebelum disahkan pimpinan", "Mewajibkan seluruh komunikasi kedinasan dialihkan ke media daring"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kode Etik Integritas: Penggunaan Media Sosial</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kode etik nilai Integritas mewajibkan pegawai: <strong>Menggunakan media sosial dengan bijak</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat mengenai etika bermedia digital bagi aparatur. Penggunaan media sosial secara bijak mencegah penyebaran disinformasi, menjaga kerahasiaan jabatan, serta melindungi reputasi institusi dari unggahan yang mencederai kepatutan etis. Pedoman kedinasan secara eksplisit menginstruksikan penggunaan media sosial dengan bijak. Oleh sebab itu, opsi C adalah sikap yang benar.</p><p>Jawaban tepat: <strong>C. Bijak dan mematuhi tata krama dalam penggunaan media sosial</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan peralihan ke sistem manual yang bertentangan dengan efisiensi administrasi digital.</li><li><strong>B</strong>: Merupakan ranah privasi pegawai yang tidak memerlukan pengambilalihan oleh humas.</li><li><strong>D</strong>: Merupakan pembocoran rahasia dokumen kebijakan sebelum penetapan resmi negara.</li><li><strong>E</strong>: Merupakan pemindahan saluran komunikasi yang tidak sesuai dengan tata naskah dinas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 36) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Menggunakan media sosial • dengan bijak</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Bagi pegawai yang berencana melakukan perjalanan ke luar negeri untuk kepentingan pribadi, ketentuan yang wajib ditaati adalah...", "options": ["Menyampaikan pemberitahuan lisan kepada rekan kerja satu ruangan", "Melampirkan surat jaminan sponsor dari organisasi kemasyarakatan", "Mendaftarkan rencana keberangkatan pada perwakilan kedutaan besar", "Mengajukan izin tertulis kepada pimpinan unit yang berwenang", "Menyerahkan paspor pribadi kepada bagian kepegawaian kementerian"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kode Etik Integritas: Izin Luar Negeri</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai wajib: <strong>Mengajukan permohonan izin setiap akan melakukan perjalanan ke luar negeri untuk kepentingan pribadi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat mengenai mekanisme perizinan bepergian ke luar negeri. Prosedur perizinan resmi bertujuan memastikan akuntabilitas keberadaan pegawai, menjaga keamanan dinas, serta mengantisipasi potensi benturan kepentingan selama di luar negeri. Butir kode etik menetapkan keharusan mengajukan izin tertulis bagi perjalanan pribadi ke luar negeri. Oleh karena itu, opsi D adalah prosedur yang sah.</p><p>Jawaban tepat: <strong>D. Mengajukan izin tertulis kepada pimpinan unit yang berwenang</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pemberitahuan lisan kepada rekan tidak memiliki kekuatan hukum perizinan administratif.</li><li><strong>B</strong>: Surat jaminan sponsor swasta bukan syarat perizinan kepegawaian bagi perjalanan pribadi.</li><li><strong>C</strong>: Pendaftaran perwakilan diplomatik merupakan urusan kekonsuleran umum dan bukan izin kedinasan.</li><li><strong>E</strong>: Penyerahan paspor pribadi tidak diwajibkan dalam tata tertib perjalanan ke luar negeri.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 36) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Mengajukan permohonan izin • melakukan perjalanan ke luar negeri • untuk kepentingan pribadi</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Pegawai dilarang menemui pihak yang berpotensi menimbulkan benturan kepentingan, KECUALI...", "options": ["Pertemuan tersebut dilaksanakan di aula gedung perwakilan daerah", "Dihadiri oleh seluruh anggota tim kerja pada jam istirahat kantor", "Mengagendakan pembahasan umum terkait perkembangan ekonomi mikro", "Memperoleh izin tidak tertulis dari perwakilan serikat pegawai", "Dilaksanakan dalam rangka penugasan resmi kedinasan yang sah"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kode Etik Integritas: Larangan Pertemuan Konflik Kepentingan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai dilarang: <strong>Tidak menemui pihak yang berpotensi menimbulkan konflik kepentingan, kecuali karena penugasan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat untuk pengecualian pertemuan pihak berkepentingan. Pembatasan pertemuan di luar kedinasan mencegah kompromi objektivitas, korupsi, atau gratifikasi, sehingga interaksi hanya diperkenankan apabila didasari surat tugas kedinasan yang sah. Ketentuan resmi mengecualikan larangan tersebut semata-mata karena adanya penugasan resmi. Maka dari itu, opsi E adalah pengecualian yang sah.</p><p>Jawaban tepat: <strong>E. Dilaksanakan dalam rangka penugasan resmi kedinasan yang sah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Lokasi gedung perwakilan tidak melegitimasi pertemuan pribadi tanpa surat penugasan.</li><li><strong>B</strong>: Kehadiran tim pada jam istirahat tetap berisiko memicu benturan kepentingan terlarang.</li><li><strong>C</strong>: Topik bahasan ekonomi umum tidak menggugurkan kewajiban kepemilikan surat tugas sah.</li><li><strong>D</strong>: Izin serikat pegawai tidak memiliki wewenang yuridis memberikan dispensasi penugasan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 36) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Tidak menemui pihak yang berpotensi • menimbulkan konflik kepentingan, kecuali • karena penugasan</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Larangan etika integritas terkait perlindungan martabat rekan kerja melarang perbuatan...", "options": ["Bertindak sewenang-wenang, perundungan, dan pelecehan", "Memiliki rajah tato pada bagian tubuh yang terbuka terlihat", "Menyebarkan informasi yang memicu rasa permusuhan", "Menerima tamu non-pekerjaan tanpa izin pimpinan kantor", "Menghalangi kreativitas rekan yang memberi nilai tambah"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kode Etik Integritas: Larangan Perundungan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kode etik integritas melarang: <strong>Tidak bertindak sewenang-wenang, melakukan perundungan (bullying) dan/ atau pelecehan terhadap pegawai atau pihak lain baik di dalam maupun di luar lingkungan kerja</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat tentang larangan perilaku mencederai kemanusiaan pada nilai integritas. Lingkungan kerja aparatur harus bebas dari intimidasi psikis, fisik, maupun verbal agar martabat setiap pegawai terlindungi dan produktivitas kerja terjaga. Rincian larangan integritas secara eksplisit menyebut tindakan sewenang-wenang, perundungan, dan pelecehan. Oleh karena itu, opsi A adalah larangan yang dimaksud.</p><p>Jawaban tepat: <strong>A. Bertindak sewenang-wenang, perundungan, dan pelecehan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan larangan standar penampilan fisik pada nilai Profesionalisme.</li><li><strong>C</strong>: Merupakan larangan peredaran informasi hoaks pada nilai Sinergi.</li><li><strong>D</strong>: Merupakan larangan penerimaan tamu pribadi pada nilai Pelayanan.</li><li><strong>E</strong>: Merupakan larangan penghambatan ide inovasi pada nilai Kesempurnaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 37) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Tidak bertindak sewenang-wenang • melakukan perundungan (bullying) dan/ atau • pelecehan</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Pegawai dilarang memasuki tempat yang dinilai tidak pantas secara etika seperti prostitusi dan perjudian, KECUALI...", "options": ["Menghadiri jamuan ramah tamah atas undangan mitra swasta", "Sedang melaksanakan tugas resmi kedinasan yang sah", "Memperoleh izin lisan dari koordinator tim kerja lapangan", "Mengisi waktu luang saat pelaksanaan perjalanan dinas luar", "Memanfaatkan masa libur cuti bersama tahunan kementerian"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kode Etik Integritas: Tempat Tidak Pantas</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai dilarang: <strong>Tidak memasuki tempat yang dipandang tidak pantas secara etika dan moral yang berlaku di masyarakat, seperti tempat prostitusi dan perjudian, kecuali karena penugasan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat mengenai batasan ketat tempat-tempat yang merusak moral aparatur. Keberadaan di lokasi prostitusi atau perjudian hanya dapat dibenarkan apabila pegawai sedang melaksanakan operasi penegakan hukum kepabeanan, cukai, atau penyelidikan pajak berdasarkan surat tugas sah. Ketentuan kedinasan memberikan pengecualian mutlak karena penugasan resmi. Oleh sebab itu, opsi B adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>B. Sedang melaksanakan tugas resmi kedinasan yang sah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Undangan mitra swasta ke lokasi tidak pantas merupakan pelanggaran berat kode etik.</li><li><strong>C</strong>: Izin lisan koordinator tim tidak sah dan tidak dapat menggantikan surat perintah tugas.</li><li><strong>D</strong>: Waktu luang saat perjalanan dinas tetap mengikat integritas moral seorang aparatur sipil.</li><li><strong>E</strong>: Status cuti dinas tidak membebaskan pegawai dari kewajiban menjaga kehormatan korps.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 37) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Tidak memasuki tempat yang dipandang tidak pantas • seperti tempat prostitusi dan perjudian • kecuali • karena penugasan</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Larangan menunjukkan gaya hidup hedonisme bagi seluruh pegawai Kementerian Keuangan berakar dari prinsip...", "options": ["Penegakan efisiensi pemanfaatan fasilitas sarana dinas", "Pemenuhan target kinerja penerimaan negara sektor pajak", "Penjagaan rasa empati dan kepatutan di tengah masyarakat", "Standardisasi tata naskah dinas kearsipan kementerian", "Pengawasan ketat atas kepatuhan jam kerja operasional"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kode Etik Integritas: Larangan Hedonisme</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai dilarang: <strong>Tidak menunjukkan gaya hidup hedonisme sebagai bentuk empati kepada masyarakat terutama kepada sesama pegawai</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat tentang kepekaan sosial aparatur pengelola keuangan negara. Pamer kemewahan atau gaya hidup konsumtif berlebihan melukai hati masyarakat yang membayar pajak serta mencederai kepercayaan publik terhadap integritas kementerian. Pedoman etika integritas menegaskan larangan hedonisme sebagai bentuk empati kepada masyarakat dan sesama pegawai. Dengan demikian, opsi C adalah landasan yang benar.</p><p>Jawaban tepat: <strong>C. Penjagaan rasa empati dan kepatutan di tengah masyarakat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan pengelolaan sarana inventaris kantor pada unit perlengkapan.</li><li><strong>B</strong>: Merupakan fungsi operasional pemungutan pendapatan kas negara.</li><li><strong>D</strong>: Merupakan ketertiban administrasi korespondensi dokumen perkantoran.</li><li><strong>E</strong>: Merupakan penegakan disiplin waktu jam kehadiran kerja aparatur.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 37) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Tidak menunjukkan gaya hidup hedonisme sebagai bentuk empati kepada • terutama kepada sesama pegawai</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Butir pertama dalam Kode Etik dan Kode Perilaku Nilai Profesionalisme mewajibkan pegawai untuk...", "options": ["Menjaga citra, harkat, dan martabat kementerian di forum formal", "Mengakui persamaan derajat, hak, dan kewajiban setiap manusia", "Menunjukkan kepedulian, sikap ramah, dan santun saat melayani", "Mengutamakan kepentingan bangsa dan organisasi di atas pribadi", "Terbuka terhadap usulan perbaikan dan pengetahuan teknologi baru"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kode Etik Profesionalisme: Kepentingan Bangsa</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kode etik profesionalisme mewajibkan: <strong>Mengutamakan kepentingan bangsa dan organisasi di atas kepentingan pribadi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat untuk butir pembuka kode etik profesionalisme. Jiwa korsa pengabdian aparatur menuntut subordinasi kepentingan individual di bawah cita-cita luhur kemakmuran bangsa dan mandat institusi kementerian. Rumusan pertama nilai Profesionalisme menegaskan pengutamaan kepentingan bangsa dan organisasi di atas pribadi. Maka dari itu, opsi D adalah butir yang benar.</p><p>Jawaban tepat: <strong>D. Mengutamakan kepentingan bangsa dan organisasi di atas pribadi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan butir pertama dalam kode etik dan kode perilaku nilai Integritas.</li><li><strong>B</strong>: Merupakan butir pertama dalam kode etik dan kode perilaku nilai Sinergi.</li><li><strong>C</strong>: Merupakan butir pertama dalam kode etik dan kode perilaku nilai Pelayanan.</li><li><strong>E</strong>: Merupakan butir pertama dalam kode etik dan kode perilaku nilai Kesempurnaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 37) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Mengutamakan kepentingan bangsa dan organisasi • kepentingan pribadi</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Dalam pelaksanaan tugas sehari-hari, profesionalitas aparatur diwujudkan melalui keharusan bekerja sesuai...", "options": ["Kerangka manajemen risiko operasional satuan kerja kementerian", "Rencana strategis jangka menengah direktorat jenderal teknis", "Rekomendasi hasil pengawasan berkala unit kepatuhan internal", "Pedoman teknis tata kelola anggaran berbasis kinerja instansi", "Standar operasional prosedur dan batas kewenangan jabatan"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kode Etik Profesionalisme: Kepatuhan SOP</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai wajib: <strong>Bekerja sesuai standar operasional prosedur dan kewenangan jabatan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat tentang kepatuhan yuridis operasional aparatur. Bekerja sesuai SOP menjamin kepastian hukum, mencegah mal-administrasi, dan memastikan keluaran layanan memiliki mutu yang seragam dan akuntabel. Dalam pedoman etika, pegawai wajib bekerja berlandaskan SOP dan batas kewenangan jabatannya. Dengan demikian, opsi E adalah pedoman kerja yang benar.</p><p>Jawaban tepat: <strong>E. Standar operasional prosedur dan batas kewenangan jabatan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan kerangka mitigasi ketidakpastian dalam pencapaian target organisasi.</li><li><strong>B</strong>: Merupakan dokumen perencanaan makro jangka lima tahun kementerian.</li><li><strong>C</strong>: Merupakan saran tindak lanjut perbaikan tata kelola hasil pemantauan UKI.</li><li><strong>D</strong>: Merupakan pedoman teknis perumusan pagu belanja pada unit perencana.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 37) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Bekerja sesuai standar operasional prosedur • kewenangan jabatan</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Saat terjadi kekeliruan dalam pengetikan surat ketetapan dinas, sikap profesional yang harus ditunjukkan pegawai adalah...", "options": ["Berani mengakui kesalahan dan bertanggung jawab memperbaikinya", "Menunggu arahan resmi dari tim pemeriksa kearsipan kementerian", "Mengalihkan penerbitan keputusan baru pada triwulan anggaran depan", "Menyimpan naskah tersebut sebagai dokumen arsip inaktif kantor", "Menyerahkan kewenangan perbaikan sepenuhnya kepada unit kehumasan"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kode Etik Profesionalisme: Pengakuan Kesalahan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai wajib: <strong>Berani mengakui kesalahan dan bertanggung jawab atas pelaksanaan tugasnya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat tentang kematangan moral profesionalitas. Mengakui kekeliruan dengan kesatria dan segera melakukan perbaikan membuktikan tingginya rasa tanggung jawab serta integritas seorang aparatur sipil negara. Pedoman kedinasan secara eksplisit mewajibkan sikap berani mengakui kesalahan dan bertanggung jawab. Oleh sebab itu, opsi A adalah tindakan yang benar.</p><p>Jawaban tepat: <strong>A. Berani mengakui kesalahan dan bertanggung jawab memperbaikinya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Menunggu arahan pemeriksa arsip memperlambat perbaikan administratif yang mendesak.</li><li><strong>C</strong>: Menunda hingga triwulan depan mencederai kepastian hukum bagi pemohon keputusan.</li><li><strong>D</strong>: Mengarsipkan naskah cacat merupakan pembiaran kesalahan dokumentasi kedinasan.</li><li><strong>E</strong>: Unit kehumasan tidak memiliki kewenangan substansi perbaikan naskah ketetapan dinas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 37) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Berani mengakui kesalahan dan • bertanggung jawab atas pelaksanaan tugasnya</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Terkait kenyamanan lingkungan kerja, ketentuan kode etik profesionalisme melarang pegawai untuk...", "options": ["Menata kembali dokumen kerja setelah selesai jam operasional dinas", "Merokok di luar area merokok yang telah ditentukan kantor", "Mematikan pendingin ruangan sebelum meninggalkan ruang rapat kantor", "Menggunakan sarana kerja kantor sesuai dengan peruntukannya", "Menjaga kerapian meja kerja dari tumpukan berkas yang tidak terpakai"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kode Etik Profesionalisme: Kebersihan dan Merokok</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai wajib menjaga kebersihan, keamanan, dan kenyamanan ruang kerja, termasuk <strong>tidak merokok di luar area merokok yang telah disediakan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat tentang ketertiban ruang bersama di lingkungan kantor. Asap rokok mengganggu kesehatan, mencemari udara ruangan, serta menciptakan ketidaknyamanan bagi rekan kerja dan pemohon layanan publik. Ketentuan tata tertib melarang keras merokok di luar zona khusus merokok yang telah ditetapkan. Maka dari itu, opsi B adalah larangan yang dimaksud.</p><p>Jawaban tepat: <strong>B. Merokok di luar area merokok yang telah ditentukan kantor</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan ketertiban kearsipan meja kerja yang justru dianjurkan dalam standar 5R.</li><li><strong>C</strong>: Merupakan langkah penghematan energi listrik yang mendukung efisiensi kantor.</li><li><strong>D</strong>: Merupakan kewajiban pemanfaatan aset negara secara bertanggung jawab.</li><li><strong>E</strong>: Merupakan budaya kerapian meja kerja yang mendukung produktivitas aparatur.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 37) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Menjaga kebersihan, keamanan, dan kenyamanan ruang kerja, termasuk tidak • merokok di luar area merokok yang telah disediakan</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Penggunaan tindik (piercing) bagi pegawai dilarang dalam kode etik profesionalisme, KECUALI...", "options": ["Penggunaan tindik pada daun telinga untuk seluruh staf pria", "Penggunaan anting hidung saat menghadiri acara protokoler", "Di daun telinga khusus pegawai perempuan atau alasan agama", "Penggunaan aksesoris tindik alis pada kegiatan operasional", "Penggunaan perhiasan tindik bibir bagi aparatur pelayanan"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kode Etik Profesionalisme: Pengecualian Tindik</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai dilarang: <strong>Tidak memakai tindik (piercing), kecuali penggunaan di daun telinga khusus untuk pegawai perempuan atau karena alasan keagamaan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat untuk pengecualian tata kerapian berbusana dan berpenampilan. Standar kepantasan PNS Kemenkeu menuntut kerapian yang mencerminkan wibawa birokrasi, dengan toleransi khusus bagi tradisi wanita di daun telinga serta alasan keyakinan keagamaan yang sah. Aturan kedinasan merumuskan pengecualian tersebut secara tegas. Oleh sebab itu, opsi C adalah ketentuan sah yang dibenarkan.</p><p>Jawaban tepat: <strong>C. Di daun telinga khusus pegawai perempuan atau alasan agama</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Tindik telinga bagi staf pria tidak diperkenankan dalam kode kerapian dinas.</li><li><strong>B</strong>: Tindik hidung tidak termasuk dalam pengecualian resmi etika berpenampilan.</li><li><strong>D</strong>: Tindik alis dilarang keras dalam standar kepantasan fisik aparatur negara.</li><li><strong>E</strong>: Tindik bibir menyalahi kesopanan dan tidak dibenarkan dalam aturan kedinasan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 37) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Tidak memakai tindik (piercing), kecuali • pegawai perempuan atau karena alasan keagamaan</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Standar kerapian berpenampilan pada nilai profesionalisme melarang pegawai untuk...", "options": ["Mengenakan busana dinas harian sesuai ketentuan hari kerja", "Memakai sepatu kerja formal tertutup saat berada di kantor", "Menyematkan lencana korps pegawai aparatur sipil negara", "Memiliki tato pada bagian tubuh yang terbuka terlihat", "Menggunakan kartu tanda pengenal pegawai selama jam kerja"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kode Etik Profesionalisme: Larangan Tato</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kode etik profesionalisme melarang: <strong>Tidak bertato di bagian tubuh yang terbuka</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat tentang ketentuan penampilan fisik aparatur Kementerian Keuangan. Kerapian yang terjaga bebas dari rajah tato di area tubuh yang terbuka menjaga citra kesopanan, netralitas, dan kepantasan seorang abdi negara di hadapan publik. Pedoman kerja melarang tegas tato di bagian tubuh yang terbuka. Oleh sebab itu, opsi D adalah larangan yang dimaksud.</p><p>Jawaban tepat: <strong>D. Memiliki tato pada bagian tubuh yang terbuka terlihat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan busana dinas harian yang diwajibkan sesuai jadwal seragam kementerian.</li><li><strong>B</strong>: Merupakan sepatu dinas formal standar yang wajib dikenakan di lingkungan kantor.</li><li><strong>C</strong>: Merupakan atribut resmi korps pegawai Republik Indonesia yang harus disematkan.</li><li><strong>E</strong>: Merupakan kartu identitas resmi yang wajib dikenakan selama menjalankan kedinasan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 38) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Tidak bertato • di bagian tubuh yang terbuka</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Kode etik nilai Sinergi mewajibkan seluruh pegawai untuk menghormati dan menghargai perbedaan...", "options": ["Jenjang kepangkatan dan masa pengabdian aparatur di instansi", "Tingkat penyelesaian sasaran kinerja tahunan antar-unit kerja", "Preferensi metode kerja administratif yang diterapkan rekan kerja", "Karakteristik tugas fungsional pada masing-masing direktorat", "Ras, warna kulit, agama, jenis kelamin, dan disabilitas"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kode Etik Sinergi: Penghormatan Keragaman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai wajib: <strong>Menghormati dan menghargai perbedaan latar belakang, ras, warna kulit, agama, asal-usul, jenis kelamin, status pernikahan, umur, atau kondisi kecacatan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat mengenai nilai inklusivitas dan antidiskriminasi aparatur. Lingkungan kerja Kemenkeu yang majemuk menuntut penghargaan tulus atas perbedaan suku, keyakinan, gender, dan kondisi disabilitas demi terciptanya sinergi yang kokoh. Pedoman toleransi merinci aspek keberagaman tersebut secara komprehensif. Dengan demikian, opsi E adalah rincian yang benar.</p><p>Jawaban tepat: <strong>E. Ras, warna kulit, agama, jenis kelamin, dan disabilitas</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan jenjang hierarki karier kepegawaian umum dalam birokrasi pemerintahan.</li><li><strong>B</strong>: Merupakan capaian kinerja administratif tahunan satuan kerja kementerian.</li><li><strong>C</strong>: Merupakan variasi gaya kerja teknis yang berada di luar ranah hak asasi toleransi.</li><li><strong>D</strong>: Merupakan pembagian tugas pokok dan fungsi operasional kedinasan antar-unit.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 38) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Menghormati dan menghargai perbedaan latar belakang, ras, warna kulit, agama • kondisi kecacatan</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Bentuk toleransi religius dalam kode etik nilai Sinergi saat rapat kerja sedang berlangsung adalah...", "options": ["Memberikan kesempatan ibadah dan tidak mengadakan rapat di waktu ibadah", "Menyusun lembar presensi kehadiran peserta rapat secara digital terpusat", "Menetapkan durasi penyampaian tanggapan maksimal lima belas menit per seksi", "Mengalihkan pembahasan materi non-esensial ke sesi koordinasi terpisah", "Membatasi jumlah perwakilan peserta rapat kerja maksimal tiga pejabat"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kode Etik Sinergi: Kesempatan Ibadah</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kode etik sinergi mewajibkan: <strong>Memberikan kesempatan untuk menunaikan ibadah ketika rapat kerja atau tugas kedinasan sedang berlangsung</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat tentang pemenuhan hak beragama dalam dinamika penugasan dinas. Penjadwalan rapat tidak boleh berbenturan dengan waktu salat atau ibadah keagamaan, serta pimpinan rapat wajib memberikan jeda istirahat bagi peserta untuk menunaikan kewajiban agamanya. Pedoman kerja mengatur pemberian kesempatan ibadah saat rapat berlangsung. Oleh sebab itu, opsi A adalah wujud toleransi yang benar.</p><p>Jawaban tepat: <strong>A. Memberikan kesempatan ibadah dan tidak mengadakan rapat di waktu ibadah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan administrasi kehadiran rapat yang tidak berkaitan dengan toleransi ibadah.</li><li><strong>C</strong>: Merupakan manajemen waktu tata tertib sidang pembahasan teknis kedinasan.</li><li><strong>D</strong>: Merupakan efisiensi agenda bahasan materi rapat kerja dalam pengelolaan waktu.</li><li><strong>E</strong>: Merupakan kuota delegasi satuan kerja dalam forum koordinasi antarseksi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 38) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Memberikan kesempatan untuk menunaikan ibadah • rapat kerja atau tugas</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Terkait arus peredaran informasi, kode etik sinergi melarang keras pegawai untuk menyebarkan...", "options": ["Ringkasan hasil kajian akademis perumusan kebijakan fiskal negara", "Informasi tidak terbukti yang memicu kebencian dan permusuhan", "Risalah rapat pembahasan tindak lanjut rekomendasi audit pengawasan", "Salinan keputusan mutasi dan rotasi pejabat perbendaharaan kantor", "Draf pedoman teknis tata kelola barang milik negara kementerian"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kode Etik Sinergi: Larangan Informasi Hoaks</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kode etik sinergi melarang: <strong>Tidak menyebarkan informasi yang tidak dapat dibuktikan kebenarannya, menimbulkan rasa kebencian dan/ atau permusuhan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat mengenai larangan penyebaran kabar bohong dan ujaran kebencian. Informasi yang tidak dapat diverifikasi kebenarannya berpotensi memecah belah solidaritas internal, memicu fitnah, dan merusak hubungan kerja harmonis antarpegawai. Regulasi etika melarang tegas penyebaran informasi yang memicu kebencian dan permusuhan. Maka dari itu, opsi B adalah larangan yang dimaksud.</p><p>Jawaban tepat: <strong>B. Informasi tidak terbukti yang memicu kebencian dan permusuhan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan naskah akademis perumusan kebijakan fiskal yang sah untuk didistribusikan.</li><li><strong>C</strong>: Merupakan dokumen notula resmi tindak lanjut pengawasan yang wajib ditindaklanjuti.</li><li><strong>D</strong>: Merupakan petikan keputusan kepegawaian resmi yang disampaikan secara sah.</li><li><strong>E</strong>: Merupakan draf regulasi teknis yang dibahas secara terbuka dalam penyusunan aturan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 38) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Tidak menyebarkan informasi yang tidak dapat dibuktikan kebenarannya • menimbulkan rasa kebencian</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Dalam kode etik nilai Pelayanan, pelaksanaan tugas kedinasan seluruh pegawai wajib berorientasi pada...", "options": ["Keteguhan menjaga kejujuran dan martabat kehormatan institusi", "Ketepatan penyelesaian tugas berbasis kompetensi dan kepatuhan SOP", "Peningkatan kesejahteraan masyarakat luas dalam bertugas", "Kerapian koordinasi internal dan keharmonisan kemitraan kerja", "Keberlanjutan inovasi perbaikan tata kelola birokrasi instansi"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kode Etik Pelayanan: Kesejahteraan Masyarakat</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kode etik nilai Pelayanan mewajibkan: <strong>Berorientasi pada peningkatan kesejahteraan masyarakat dalam melaksanakan tugas</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat sesuai hakikat orientasi nilai Pelayanan. Pelayanan publik aparatur kementerian tidak berhenti pada formalitas kepatuhan administratif, melainkan harus memberikan dampak nyata bagi peningkatan taraf hidup dan kesejahteraan seluruh rakyat Indonesia. Ketentuan etika menetapkan orientasi peningkatan kesejahteraan masyarakat. Oleh sebab itu, opsi C adalah rumusan yang benar.</p><p>Jawaban tepat: <strong>C. Peningkatan kesejahteraan masyarakat luas dalam bertugas</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan fokus pengabdian etis pada nilai dasar Integritas.</li><li><strong>B</strong>: Merupakan fokus pelaksanaan tugas teknis pada nilai dasar Profesionalisme.</li><li><strong>D</strong>: Merupakan fokus penguatan hubungan kerja pada nilai dasar Sinergi.</li><li><strong>E</strong>: Merupakan fokus transformasi sistem kerja pada nilai dasar Kesempurnaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 38) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Berorientasi pada peningkatan kesejahteraan masyarakat • dalam melaksanakan • tugas</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Penerimaan tamu non-pekerjaan pada jam kerja diperbolehkan dalam kode etik pelayanan dengan syarat mutlak...", "options": ["Pertemuan didampingi oleh pejabat unit kepatuhan internal kantor", "Dilaksanakan pada ruang konsultasi terpadu gedung kementerian", "Durasi kunjungan dicatatkan dalam sistem presensi kehadiran harian", "Seizin atasan dan tidak mengganggu pekerjaan atau layanan", "Dituangkan dalam lembar registrasi buku tamu keprotokolan dinas"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kode Etik Pelayanan: Tamu Non-Pekerjaan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai diperkenankan: <strong>Menerima pihak lain yang tidak terkait dengan pekerjaan di luar jam kerja atau pada jam kerja dengan seizin atasan dan/atau sepanjang tidak mengganggu pekerjaan atau layanan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat tentang pengaturan tamu pribadi di kantor. Pelayanan kepada masyarakat dan penyelesaian tugas utama kantor tidak boleh terabaikan akibat urusan personal, sehingga kunjungan pribadi harus mendapatkan persetujuan atasan dan dijamin tidak mengganggu jalannya layanan. Pedoman etika secara tegas mensyaratkan izin atasan dan tidak mengganggu pekerjaan. Dengan demikian, opsi D adalah syarat yang tepat.</p><p>Jawaban tepat: <strong>D. Seizin atasan dan tidak mengganggu pekerjaan atau layanan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pendampingan UKI tidak dipersyaratkan untuk penerimaan tamu urusan pribadi.</li><li><strong>B</strong>: Ruang konsultasi terpadu diperuntukkan bagi pemohon layanan dinas resmi.</li><li><strong>C</strong>: Pencatatan presensi kehadiran hanya merekam jam kerja masuk dan pulang pegawai.</li><li><strong>E</strong>: Buku keprotokolan dinas diperuntukkan bagi tamu dinas kenegaraan atau pimpinan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 38) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pada jam kerja dengan seizin atasan • pekerjaan atau layanan</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Karakter keterbukaan yang wajib ditunjukkan pegawai dalam kode etik nilai Kesempurnaan meliputi keterbukaan atas...", "options": ["Perubahan mekanisme pelaporan administrasi keuangan berbasis aplikasi", "Ketentuan teknis penyesuaian target penerimaan pada triwulan berjalan", "Standar baku penilaian kompetensi manajerial dalam promosi jabatan", "Format dokumen perencanaan kebutuhan barang milik negara satuan kerja", "Usulan perbaikan serta informasi dan pengetahuan baru"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kode Etik Kesempurnaan: Keterbukaan Perbaikan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kode etik kesempurnaan mewajibkan: <strong>Terbuka terhadap usulan perbaikan</strong> serta <strong>Terbuka terhadap informasi atau pengetahuan baru</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat mengenai pondasi inovasi aparatur. Budaya kesempurnaan menuntut kerendahan hati untuk menerima saran perbaikan serta semangat menyerap ilmu dan wawasan baru demi penyempurnaan sistem kerja yang berkelanjutan. Rincian butir kesempurnaan secara eksplisit menyebut terbuka terhadap usulan perbaikan dan pengetahuan baru. Oleh karena itu, opsi E adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>E. Usulan perbaikan serta informasi dan pengetahuan baru</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan adaptasi teknis operasional sistem perbendaharaan negara.</li><li><strong>B</strong>: Merupakan penyesuaian target fiskal pada direktorat jenderal teknis.</li><li><strong>C</strong>: Merupakan kriteria penilaian pengembangan sumber daya manusia aparatur.</li><li><strong>D</strong>: Merupakan format penatausahaan aset negara pada unit perlengkapan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 38) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Terbuka terhadap usulan perbaikan • Terbuka terhadap informasi atau pengetahuan baru</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Kode etik nilai kesempurnaan melarang aparatur pimpinan maupun rekan kerja untuk...", "options": ["Menghalangi kreativitas bernilai tambah bagi kemajuan instansi", "Membuka forum diskusi evaluasi berkala alur proses kerja kantor", "Mengusulkan standardisasi sarana pelayanan publik berbasis teknologi", "Melakukan uji petik terhadap kecepatan verifikasi berkas permohonan", "Mengikuti pelatihan peningkatan kompetensi manajerial bagi aparatur"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kode Etik Kesempurnaan: Larangan Menghalangi Kreativitas</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kode etik kesempurnaan melarang: <strong>Tidak menghalangi kreativitas/gagasan/pendapat yang bernilai tambah bagi kemajuan organisasi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat tentang iklim inovasi birokrasi. Kemajuan kementerian sangat bergantung pada keberanian pegawai menelurkan ide-ide segar, sehingga tindakan mematikan kreativitas atau menolak gagasan konstruktif dilarang keras dalam kode etik. Ketentuan etika menetapkan larangan menghalangi kreativitas yang bernilai tambah. Oleh sebab itu, opsi A adalah larangan yang dimaksud.</p><p>Jawaban tepat: <strong>A. Menghalangi kreativitas bernilai tambah bagi kemajuan instansi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan kegiatan evaluasi positif yang mendukung perbaikan tata kelola.</li><li><strong>C</strong>: Merupakan usulan inovasi sarana yang mendukung peningkatan mutu pelayanan.</li><li><strong>D</strong>: Merupakan metode pengendalian mutu layanan yang dianjurkan dalam manajemen.</li><li><strong>E</strong>: Merupakan upaya pengembangan diri yang selaras dengan nilai profesionalisme.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 39) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Tidak menghalangi kreativitas/gagasan/pendapat yang bernilai tambah • kemajuan organisasi</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Penguatan budaya Kementerian Keuangan dalam KMK Nomor 429/KMK.01/2022 didasarkan pada perpaduan...", "options": ["Peraturan perundang-undangan administrasi pemerintahan dan perdata", "Core Values ASN BerAKHLAK dan Nilai-Nilai Kementerian Keuangan", "Standar tata kelola keuangan perbankan sentral dan pasar modal", "Pedoman audit kinerja badan pemeriksa keuangan dan pengawasan", "Kerangka kerja sama pembangunan lembaga keuangan internasional"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Landasan Budaya KMK 429/KMK.01/2022</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Penguatan budaya di Kementerian Keuangan didasarkan pada core values ASN BerAKHLAK dan Nilai-Nilai Kementerian Keuangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat mengenai pondasi ganda transformasi budaya Kemenkeu. Integrasi harmonis antara nilai dasar nasional seluruh ASN (BerAKHLAK) dengan lima nilai khas Kemenkeu memperkokoh karakter aparatur dalam mengawal keuangan negara. Bunyi resmi KMK 429/KMK.01/2022 menetapkan dasar tersebut. Dengan demikian, opsi B adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>B. Core Values ASN BerAKHLAK dan Nilai-Nilai Kementerian Keuangan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan kerangka hukum positif umum tata usaha negara dan perdata.</li><li><strong>C</strong>: Merupakan regulasi otoritas sektor moneter dan pasar modal terpisah.</li><li><strong>D</strong>: Merupakan standar operasional pengawasan eksternal pemeriksa keuangan.</li><li><strong>E</strong>: Merupakan kesepakatan internasional institusi donor pembiayaan dunia.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 39) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>core values ASN BerAKHLAK dan Nilai-Nilai Kementerian Keuangan • Penguatan budaya di Kementerian Keuangan didasarkan</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Pola pikir, sikap, dan kebiasaan pegawai dalam KMK Nomor 429/KMK.01/2022 diarahkan agar terwujud dalam budaya...", "options": ["Tata Kelola Terpadu Berkelanjutan", "Birokrasi Terbuka dan Akuntabel", "Kementerian Keuangan Satu atau Kemenkeu Satu", "Ekosistem Pelayanan Publik Mandiri", "Profesionalisme Terintegrasi Nasional"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Arah Budaya Kemenkeu Satu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pola pikir, sikap, perilaku, dan kebiasaan pegawai diarahkan agar <strong>terwujud dalam budaya Kementerian Keuangan Satu (Kemenkeu Satu) dan sejalan dengan Core Values ASN BerAKHLAK dan Nilai-Nilai Kementerian Keuangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat tentang visi integrasi budaya kerja kementerian. Budaya Kemenkeu Satu menyatukan persepsi, mengikis ego sektoral antar-eselon satu, serta menyelaraskan gerak langkah seluruh aparatur di bawah satu panji institusi. Ketentuan KMK 429 menetapkan terwujudnya budaya Kementerian Keuangan Satu. Oleh sebab itu, opsi C adalah arah budaya yang dimaksud.</p><p>Jawaban tepat: <strong>C. Kementerian Keuangan Satu atau Kemenkeu Satu</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan istilah prinsip tata kelola umum birokrasi pemerintahan.</li><li><strong>B</strong>: Merupakan karakteristik sistem akuntabilitas kinerja kementerian.</li><li><strong>D</strong>: Merupakan konsep pelayanan mandiri terpadu pada unit operasional.</li><li><strong>E</strong>: Merupakan cita-cita pembinaan karier aparatur sipil negara nasional.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 39) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>budaya Kementerian • Keuangan Satu (Kemenkeu Satu)</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "KMK Nomor 429/KMK.01/2022 ditujukan mendorong terciptanya ekosistem dan lingkungan kerja yang...", "options": ["Terfokus pada pengawasan kepatuhan administratif secara berjenjang", "Mengedepankan efisiensi alokasi pembiayaan belanja operasional", "Memprioritaskan standarisasi sarana teknologi informasi terpusat", "Kolaboratif, produktif, inovatif, beretika, dan berintegritas", "Menitikberatkan pada pencapaian target penerimaan negara triwulan"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Karakteristik Ekosistem Kerja KMK 429</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Regulasi budaya ditujukan mendorong terciptanya <strong>ekosistem dan lingkungan kerja yang kolaboratif, produktif, dan inovatif dengan tetap menjunjung tinggi etika dan integritas</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat mengenai ekosistem kerja ideal aparatur kementerian. Lingkungan kerja yang mendorong sinergi, produktivitas tinggi, serta terobosan inovasi tanpa pernah mengorbankan standar moral dan kejujuran adalah sasaran utama transformasi kelembagaan. Penjelasan regulasi menegaskan ekosistem kolaboratif, produktif, inovatif, beretika, dan berintegritas. Oleh karena itu, opsi D adalah rumusan yang benar.</p><p>Jawaban tepat: <strong>D. Kolaboratif, produktif, inovatif, beretika, dan berintegritas</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan fungsi pengawasan internal pada unit kepatuhan birokrasi.</li><li><strong>B</strong>: Merupakan sasaran pengetatan anggaran operasional pada unit keuangan.</li><li><strong>C</strong>: Merupakan sasaran modernisasi infrastruktur peranti lunak kementerian.</li><li><strong>E</strong>: Merupakan target fiskal jangka pendek pada unit pemungut kas negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 39) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>kolaboratif, • produktif, dan inovatif dengan tetap menjunjung tinggi etika dan integritas</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Penguatan Budaya Kementerian Keuangan dalam pelaksanaannya dilakukan melalui pembudayaan...", "options": ["Penyusunan indikator kinerja individu dan penetapan kontrak kerja", "Penyelenggaraan uji kompetensi teknis dan sertifikasi profesi", "Pemantauan kehadiran apel pagi dan kepatuhan jam kerja operasional", "Penerapan manajemen risiko satuan kerja dan pelaporan berkala", "Sikap Dasar dan Perilaku Efektif pada masing-masing nilai"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Metode Penguatan Budaya Kemenkeu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Penguatan Budaya Kemenkeu dilakukan dengan pembudayaan Sikap Dasar dan Perilaku Efektif yang diturunkan dari Core Values ASN BerAKHLAK dan Nilai-Nilai Kementerian Keuangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat mengenai instrumen operasional pembudayaan nilai. Pembudayaan nilai tidak cukup dengan retorika, melainkan harus diturunkan secara konkret menjadi sikap batin (Sikap Dasar) dan tindakan nyata sehari-hari (Perilaku Efektif) yang terukur. Bunyi aturan KMK 429 menegaskan pembudayaan Sikap Dasar dan Perilaku Efektif. Maka dari itu, opsi E adalah jawaban yang tepat.</p><p>Jawaban tepat: <strong>E. Sikap Dasar dan Perilaku Efektif pada masing-masing nilai</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan instrumen pengelolaan kinerja pegawai pada sistem merit.</li><li><strong>B</strong>: Merupakan pembinaan kapabilitas teknis fungsional aparatur kementerian.</li><li><strong>C</strong>: Merupakan penegakan tata tertib jam kerja harian pegawai kantor.</li><li><strong>D</strong>: Merupakan kerangka mitigasi ketidakpastian tata kelola pada satuan kerja.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 39) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Penguatan Budaya Kemenkeu dilakukan dengan pembudayaan • Sikap Dasar dan • Perilaku Efektif</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Yang dimaksud dengan Sikap Dasar pegawai dalam regulasi penguatan budaya adalah sekumpulan pikiran dan perasaan yang...", "options": ["Secara tidak langsung terlihat namun harus dimiliki pegawai", "Dapat diukur secara kuantitatif dalam sistem penilaian kinerja", "Diwajibkan khusus bagi pejabat pengelola anggaran satuan kerja", "Dirumuskan secara baku dalam naskah perjanjian kerja tahunan", "Tercantum eksplisit dalam dokumen uraian tugas jabatan fungsional"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Sikap Dasar Pegawai</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Sikap Dasar pegawai</strong> adalah sekumpulan pikiran, perasaan, dan/atau kecenderungan seseorang yang <strong>secara tidak langsung terlihat namun harus dimiliki oleh pegawai Kementerian Keuangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat mengenai hakikat konsep Sikap Dasar. Sikap dasar bersemayam dalam pola pikir dan batin pegawai sebagai fondasi nilai yang tidak tampak secara fisik, namun menjadi motor penggerak seluruh keputusan dan tindakan. Ketentuan KMK 429 merumuskannya sebagai hal yang secara tidak langsung terlihat namun harus dimiliki. Dengan demikian, opsi A adalah definisi yang benar.</p><p>Jawaban tepat: <strong>A. Secara tidak langsung terlihat namun harus dimiliki pegawai</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan capaian kinerja keluaran yang dinilai melalui angka capaian IKU.</li><li><strong>C</strong>: Sikap dasar berlaku bagi seluruh jajaran pegawai tanpa terkecuali.</li><li><strong>D</strong>: Merupakan kontrak kinerja formal tahunan pegawai aparatur sipil negara.</li><li><strong>E</strong>: Merupakan rincian butir kegiatan jabatan fungsional pada dokumen formasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 39) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Sikap Dasar pegawai Kementerian Keuangan yang dimaksud • secara tidak • langsung terlihat namun harus dimiliki</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Karakteristik utama dari Perilaku Efektif pegawai Kementerian Keuangan dalam tata kelola aparatur adalah...", "options": ["Menjadi keyakinan batin yang melandasi komitmen moral kerja", "Berasal dari Sikap Dasar dan bersifat secara langsung terlihat", "Menjadi instrumen penilaian kepatuhan hukum oleh auditor negara", "Disusun sebagai dokumen panduan standar operasional prosedur", "Dituangkan dalam pakta integritas bersama mitra kerja dinas"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Perilaku Efektif Pegawai</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Perilaku Efektif</strong> adalah sekumpulan perilaku atau gaya kerja yang <strong>berasal dari Sikap Dasar, dan bersifat secara langsung terlihat</strong>, serta perlu ditunjukkan setiap pegawai.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat mengenai karakteristik Perilaku Efektif. Jika sikap dasar berada di alam pikiran, maka perilaku efektif adalah manifestasi nyata gaya kerja yang dapat diamati, dievaluasi, dan dirasakan langsung oleh rekan kerja dan penerima layanan. Regulasi budaya mendefinisikannya sebagai hal yang berasal dari sikap dasar dan bersifat langsung terlihat. Oleh karena itu, opsi B adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>B. Berasal dari Sikap Dasar dan bersifat secara langsung terlihat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan definisi hakiki dari konsep Sikap Dasar pegawai.</li><li><strong>C</strong>: Merupakan instrumen kepatuhan hukum pada fungsi audit pengawasan.</li><li><strong>D</strong>: Merupakan pedoman kerja tertulis pada tata laksana operasional dinas.</li><li><strong>E</strong>: Merupakan komitmen formal antikorupsi bersama pihak eksternal.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 39) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Perilaku Efektif pegawai Kementerian Keuangan yang dimaksud • bersifat secara langsung terlihat</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Pelaksanaan pembudayaan Sikap Dasar dan Perilaku Efektif dilakukan melalui tiga strategi utama, yaitu...", "options": ["Formulasi, Implementasi, dan Evaluasi kinerja aparatur", "Rasionalisasi, Efisiensi, dan Sentralisasi tata kelola", "Internalisasi, Institusionalisasi, dan Eksternalisasi", "Sosialisasi, Standardisasi, dan Sertifikasi kepegawaian", "Perencanaan, Penganggaran, dan Pertanggungjawaban kas"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tiga Strategi Pembudayaan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pelaksanaan pembudayaan dilakukan dengan strategi: <strong>Internalisasi, Institusionalisasi, dan Eksternalisasi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat mengenai trilogi strategi pembudayaan nilai di Kementerian Keuangan. Ketiga strategi tersebut bekerja sinergis mulai dari penanaman keyakinan diri (internalisasi), pelembagaan dalam sistem hukum dan penghargaan (institusionalisasi), hingga keteladanan interaksi dengan pihak luar (eksternalisasi). Regulasi resmi merumuskan ketiganya secara tegas. Oleh karena itu, opsi C adalah rumusan yang benar.</p><p>Jawaban tepat: <strong>C. Internalisasi, Institusionalisasi, dan Eksternalisasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan tahapan umum manajemen strategis kebijakan publik.</li><li><strong>B</strong>: Merupakan konsep efisiensi struktur organisasi birokrasi pemerintahan.</li><li><strong>D</strong>: Merupakan tahapan penataan administrasi sumber daya manusia aparatur.</li><li><strong>E</strong>: Merupakan siklus pengelolaan perbendaharaan dan anggaran keuangan negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 40) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>dilakukan • dengan strategi: • Internalisasi • Institusionalisasi • Eksternalisasi</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Strategi pembudayaan yang bertujuan membentuk keyakinan atau mindset diri agar terwujud perilaku positif disebut...", "options": ["Eksternalisasi", "Institusionalisasi", "Restrukturisasi", "Internalisasi", "Standardisasi"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Strategi Internalisasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Internalisasi</strong> merupakan kegiatan atau upaya yang bertujuan <strong>membentuk keyakinan/mindset diri agar terwujud dalam sikap/perilaku yang positif dalam organisasi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat sesuai definisi strategi internalisasi. Proses internalisasi menitikberatkan pada transformasi cara berpikir dan kesadaran batin pegawai melalui edukasi, pembelajaran mandiri, dan diskusi terarah. Penjelasan resmi modul menyatakan tujuannya adalah membentuk keyakinan dan pola pikir positif. Dengan demikian, Internalisasi adalah strategi yang tepat.</p><p>Jawaban tepat: <strong>D. Internalisasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan strategi penanaman pemahaman nilai ke pihak luar instansi.</li><li><strong>B</strong>: Merupakan strategi pelembagaan nilai melalui regulasi dan sistem penghargaan.</li><li><strong>C</strong>: Merupakan penataan kembali struktur organisasi dan tata laksana kedinasan.</li><li><strong>E</strong>: Merupakan penyusunan standar operasional baku dalam pelayanan publik.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 40) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Internalisasi, merupakan kegiatan atau upaya yang bertujuan membentuk • keyakinan/mindset diri</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Upaya menjadikan sekumpulan nilai melekat dan melembaga melalui penguatan regulasi serta reward and punishment disebut...", "options": ["Internalisasi", "Eksternalisasi", "Sentralisasi", "Rasionalisasi", "Institusionalisasi"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Strategi Institusionalisasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Institusionalisasi</strong> merupakan kegiatan atau upaya yang bertujuan <strong>menjadikan sekumpulan nilai, sikap, dan perilaku tertentu sebagai bagian yang melekat dan melembaga dalam organisasi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat mengenai strategi pelembagaan budaya. Institusionalisasi memastikan nilai-nilai organisasi didukung oleh kerangka regulasi yang kuat serta penegakan disiplin dan pemberian apresiasi yang nyata bagi pegawai. Ketentuan modul merumuskan pelembagaan melalui regulasi dan sistem penghargaan sebagai institusionalisasi. Oleh sebab itu, opsi E adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>E. Institusionalisasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan strategi pembentukan pola pikir dan kesadaran diri individu pegawai.</li><li><strong>B</strong>: Merupakan strategi keteladanan pelayanan bagi pihak di luar kementerian.</li><li><strong>C</strong>: Merupakan pemusatan wewenang pengambilan keputusan pada pimpinan tertinggi.</li><li><strong>D</strong>: Merupakan penyesuaian jumlah atau struktur kelembagaan secara efisien.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 40) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>bagian yang melekat dan • melembaga</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Strategi pembudayaan yang bertujuan mendorong dan memastikan pemahaman budaya kementerian oleh pihak luar adalah...", "options": ["Eksternalisasi", "Internalisasi", "Institusionalisasi", "Restrukturisasi", "Reorganisasi"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Strategi Eksternalisasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Eksternalisasi</strong> merupakan kegiatan atau upaya yang bertujuan <strong>mendorong dan memastikan pemahaman Budaya Kemenkeu oleh pihak di luar Kementerian Keuangan</strong> melalui keteladanan (role models).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat tentang strategi penularan nilai ke masyarakat dan mitra kerja. Eksternalisasi memproyeksikan citra positif integritas dan profesionalitas kementerian melalui keteladanan aparatur saat berinteraksi dan melayani pemangku kepentingan. Ketentuan etika menegaskan tujuannya adalah pemahaman budaya oleh pihak luar kementerian. Maka dari itu, opsi A adalah strategi yang dimaksud.</p><p>Jawaban tepat: <strong>A. Eksternalisasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan strategi pembentukan keyakinan batin di kalangan internal pegawai.</li><li><strong>C</strong>: Merupakan strategi penguatan regulasi serta instrumen penghargaan disiplin.</li><li><strong>D</strong>: Merupakan perombakan struktur kelembagaan atau perbaikan model bisnis.</li><li><strong>E</strong>: Merupakan penataan ulang susunan unit kerja dan pembagian kewenangan dinas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 40) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Eksternalisasi, merupakan kegiatan atau upaya yang bertujuan mendorong dan • pemahaman Budaya Kemenkeu oleh pihak di luar Kementerian</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Pimpinan Unit Jabatan Pimpinan Tinggi Madya wajib menyampaikan laporan pelaksanaan penguatan budaya kepada...", "options": ["Inspektorat Jenderal Kementerian Keuangan", "Biro SDM Sekretariat Jenderal Kementerian Keuangan", "Badan Pendidikan dan Pelatihan Keuangan Negara", "Biro Organisasi dan Tata Laksana Sekretariat Jenderal", "Komite Pengawas Pengelolaan Keuangan Negara"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pelaporan Penguatan Budaya oleh Pimpinan Unit</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pimpinan Unit Jabatan Pimpinan Tinggi Madya melakukan pembudayaan dan <strong>penyampaian laporan pelaksanaannya kepada Biro SDM Sekretariat Jenderal Kementerian Keuangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat mengenai alur pelaporan tata kelola budaya kementerian. Biro SDM bertindak sebagai pengelola utama fungsi pembinaan kepegawaian dan penguatan budaya kementerian yang menghimpun seluruh laporan dari pimpinan unit eselon satu. Regulasi KMK 429 menetapkan penyampaian laporan ditujukan kepada Biro SDM Sekretariat Jenderal. Dengan demikian, opsi B adalah unit penerima laporan yang benar.</p><p>Jawaban tepat: <strong>B. Biro SDM Sekretariat Jenderal Kementerian Keuangan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Inspektorat Jenderal bertindak sebagai pengawas kepatuhan dan bukan sekretariat pengelola laporan budaya.</li><li><strong>C</strong>: BPPK berfokus pada penyelenggaraan pelatihan dan pendidikan aparatur sipil.</li><li><strong>D</strong>: Biro Organta berfokus pada analisis beban kerja dan perancangan struktur organisasi.</li><li><strong>E</strong>: Komite Pengawas merupakan lembaga pemantau eksternal kebijakan perpajakan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 40) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Unit Jabatan Pimpinan Tinggi Madya/Unit Organisasi Non Eselon di • Biro SDM Sekretariat • Jenderal Kementerian Keuangan</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Tugas utama Biro SDM Sekretariat Jenderal dalam pelaksanaan penguatan budaya kementerian adalah melakukan...", "options": ["Penetapan alokasi anggaran belanja pegawai seluruh direktorat", "Audit kepatuhan operasional perbendaharaan pada kantor dinas", "Koordinasi internalisasi dan pengukuran tingkat kematangan budaya", "Pengesahan perjanjian kerja sama teknologi informasi kementerian", "Penerbitan surat keputusan penjatuhan hukuman disiplin berat"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Peran Biro SDM Sekretariat Jenderal</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Biro SDM Sekretariat Jenderal Kementerian Keuangan melakukan koordinasi pelaksanaan internalisasi Sikap Dasar dan Perilaku Efektif pegawai, pemantauan, evaluasi, dan pengukuran tingkat kematangan Budaya Kemenkeu</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat mengenai peran strategis Biro SDM. Sebagai koordinator pembudayaan, Biro SDM mengawal implementasi nilai, memantau kemajuan penumbuhan perilaku efektif, serta mengukur maturitas budaya di seluruh satker. Penjelasan regulasi menetapkan peran koordinasi dan pengukuran kematangan budaya bagi Biro SDM. Oleh sebab itu, opsi C adalah tugas yang tepat.</p><p>Jawaban tepat: <strong>C. Koordinasi internalisasi dan pengukuran tingkat kematangan budaya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan wewenang Direktorat Jenderal Anggaran dalam perencanaan belanja negara.</li><li><strong>B</strong>: Merupakan tugas pengawasan operasional oleh Inspektorat Jenderal.</li><li><strong>D</strong>: Merupakan ranah Pusat Sistem Informasi dan Teknologi Keuangan kementerian.</li><li><strong>E</strong>: Merupakan wewenang pejabat pembina kepegawaian sesuai peraturan disiplin.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 40) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Biro SDM Sekretariat Jenderal • koordinasi pelaksanaan internalisasi Sikap Dasar • pengukuran tingkat • kematangan Budaya Kemenkeu</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Hasil evaluasi dan pengukuran tingkat kematangan Budaya Kementerian Keuangan oleh Biro SDM dilaporkan kepada...", "options": ["Ketua Komisi Aparatur Sipil Negara Republik Indonesia", "Kepala Badan Pengawasan Keuangan dan Pembangunan", "Ketua Badan Pemeriksa Keuangan Republik Indonesia", "Menteri Keuangan Republik Indonesia", "Kepala Badan Kepegawaian Negara Republik Indonesia"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pelaporan Akhir Tingkat Kematangan Budaya</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Biro SDM Sekretariat Jenderal melakukan pemantauan, evaluasi, pengukuran tingkat kematangan budaya, serta <strong>penyampaian laporan pelaksanaannya kepada Menteri Keuangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat mengenai muara tertinggi pelaporan budaya kementerian. Menteri Keuangan selaku pimpinan kementerian memegang kendali tertinggi dalam pengambilan keputusan strategis reformasi birokrasi dan transformasi budaya organisasi. Regulasi KMK 429 menegaskan penyampaian laporan akhir ditujukan kepada Menteri Keuangan. Oleh karena itu, opsi D adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>D. Menteri Keuangan Republik Indonesia</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: KASN merupakan komite pengawas sistem merit nasional di luar kementerian.</li><li><strong>B</strong>: BPKP merupakan aparat pengawasan intern pemerintah yang bertanggung jawab ke Presiden.</li><li><strong>C</strong>: BPK merupakan lembaga eksternal pemeriksa keuangan negara sesuai amanat konstitusi.</li><li><strong>E</strong>: BKN merupakan instansi pembina manajemen kepegawaian aparatur sipil nasional.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 40) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>penyampaian laporan pelaksanaannya kepada • Menteri Keuangan</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Nilai dasar ASN 'Akuntabel dan Loyal' dalam sistem penguatan budaya kementerian diselaraskan dengan nilai...", "options": ["Profesionalisme", "Sinergi", "Pelayanan", "Kesempurnaan", "Integritas"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penyelarasan Nilai Integritas</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Akuntabel dan Loyal (Core Values ASN)</strong> selaras dengan <strong>Integritas (nilai-nilai Kemenkeu)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat mengenai pemetaan Core Values BerAKHLAK dengan Nilai Kemenkeu. Sikap bertanggung jawab, kejujuran (akuntabel), serta kesetiaan teguh kepada bangsa dan negara (loyal) merupakan intisari utama dari pilar Integritas. Regulasi KMK 429 menetapkan pasangan penyelarasan tersebut. Maka dari itu, opsi E adalah nilai kementerian yang tepat.</p><p>Jawaban tepat: <strong>E. Integritas</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Nilai Profesionalisme diselaraskan dengan Core Values ASN Kompeten.</li><li><strong>B</strong>: Nilai Sinergi diselaraskan dengan Core Values ASN Harmonis dan Kolaboratif.</li><li><strong>C</strong>: Nilai Pelayanan diselaraskan dengan Core Values ASN Berorientasi Pelayanan.</li><li><strong>D</strong>: Nilai Kesempurnaan diselaraskan dengan Core Values ASN Adaptif.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 41) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Akuntabel dan Loyal (Core Values ASN) selaras dengan • Integritas</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Rumusan Sikap Dasar pada nilai Integritas yang selaras dengan Akuntabel dan Loyal adalah senantiasa...", "options": ["Amanah, jujur, disiplin, konsisten, terpercaya, dan militan", "Antusias belajar, berbagi ilmu, cepat akurat, dan terandalkan", "Solid, proaktif, berkolaborasi, dan berjiwa humanis bertugas", "Komunikatif, respektif, responsif, cekatan, dan solutif", "Efektif manfaatkan perubahan, inovatif, terbuka, dan korektif"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sikap Dasar Nilai Integritas</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sikap Dasar pada nilai Integritas adalah: <strong>senantiasa amanah, jujur, disiplin, konsisten, dapat dipercaya, militan dalam bekerja</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat sesuai rumusan Sikap Dasar nilai Integritas dalam KMK 429. Karakter amanah, kejujuran tak tergoyahkan, kedisiplinan, serta militansi kerja mencerminkan integritas sejati aparatur pengawal keuangan negara. Rincian sikap dasar resmi merumuskan amanah, jujur, disiplin, konsisten, dapat dipercaya, dan militan. Oleh sebab itu, opsi A adalah rumusan yang benar.</p><p>Jawaban tepat: <strong>A. Amanah, jujur, disiplin, konsisten, terpercaya, dan militan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan rumusan Sikap Dasar pada nilai dasar Profesionalisme.</li><li><strong>C</strong>: Merupakan rumusan Sikap Dasar pada nilai dasar Sinergi.</li><li><strong>D</strong>: Merupakan rumusan Sikap Dasar pada nilai dasar Pelayanan.</li><li><strong>E</strong>: Merupakan rumusan Sikap Dasar pada nilai dasar Kesempurnaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 41) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Sikap Dasar: senantiasa amanah, jujur, disiplin, konsisten, dapat dipercaya, militan • dalam bekerja</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Salah satu butir Perilaku Efektif nilai Integritas terkait kepatuhan aturan mengamanatkan pegawai untuk...", "options": ["Menunjukkan kegigihan belajar dan mempraktikkan manajemen pengetahuan", "Taat aturan meski tanpa pengawasan dengan penuh kesadaran diri", "Membangun koordinasi proaktif dan berbagi data akurat demi solusi", "Memberikan pelayanan prima yang adil, memudahkan, dan tanggap", "Mengapresiasi kreativitas rekan dalam menyempurnakan alur kerja"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Perilaku Efektif Integritas: Taat Aturan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Perilaku Efektif integritas: <strong>Menunjukkan perilaku disiplin diri, memegang teguh tanggungjawab, dan taat aturan meski tanpa pengawasan dengan penuh kesadaran, serta rela berkorban</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat tentang kematangan moral aparatur. Integritas tertinggi dibuktikan saat seseorang tetap teguh mematuhi hukum dan kode etik meskipun tidak ada kamera pemantau atau atasan yang mengawasi. Pedoman perilaku efektif merinci ketaatan aturan tanpa pengawasan dengan penuh kesadaran. Dengan demikian, opsi B adalah butir perilaku yang tepat.</p><p>Jawaban tepat: <strong>B. Taat aturan meski tanpa pengawasan dengan penuh kesadaran diri</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan butir Perilaku Efektif pada nilai dasar Profesionalisme.</li><li><strong>C</strong>: Merupakan butir Perilaku Efektif pada nilai dasar Sinergi.</li><li><strong>D</strong>: Merupakan butir Perilaku Efektif pada nilai dasar Pelayanan.</li><li><strong>E</strong>: Merupakan butir Perilaku Efektif pada nilai dasar Kesempurnaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 41) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>taat • aturan meski tanpa pengawasan dengan penuh kesadaran • rela berkorban</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Nilai dasar ASN 'Kompeten' dalam sistem penguatan budaya Kementerian Keuangan diselaraskan dengan nilai...", "options": ["Integritas", "Sinergi", "Profesionalisme", "Pelayanan", "Kesempurnaan"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penyelarasan Nilai Profesionalisme</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Kompeten (Core Values ASN)</strong> selaras dengan <strong>Profesionalisme (nilai-nilai Kemenkeu)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat mengenai penyelarasan nilai keahlian kerja. Kapabilitas keahlian, kemauan belajar tiada henti, dan ketepatan eksekusi tugas dalam nilai Kompeten berpadu utuh dengan prinsip bekerja tuntas dan akurat pada nilai Profesionalisme Kemenkeu. Regulasi KMK 429 menetapkan pasangan nilai Kompeten selaras dengan Profesionalisme. Maka dari itu, opsi C adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>C. Profesionalisme</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Core Values ASN Akuntabel dan Loyal diselaraskan dengan nilai Integritas.</li><li><strong>B</strong>: Core Values ASN Harmonis dan Kolaboratif diselaraskan dengan nilai Sinergi.</li><li><strong>D</strong>: Core Values ASN Berorientasi Pelayanan diselaraskan dengan nilai Pelayanan.</li><li><strong>E</strong>: Core Values ASN Adaptif diselaraskan dengan nilai Kesempurnaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 41) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Kompeten (Core Values ASN) selaras dengan • Profesionalisme</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Sikap Dasar pada nilai Profesionalisme yang selaras dengan nilai Kompeten menuntut pegawai senantiasa...", "options": ["Amanah, jujur, disiplin, konsisten, terpercaya, dan militan", "Solid, proaktif, berkolaborasi, dan berjiwa humanis bertugas", "Komunikatif, respektif, responsif, cekatan, dan solutif", "Antusias belajar, berbagi ilmu, cepat akurat, dan terandalkan", "Efektif manfaatkan perubahan, inovatif, terbuka, dan korektif"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sikap Dasar Nilai Profesionalisme</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sikap Dasar pada nilai Profesionalisme adalah: <strong>senantiasa antusias belajar, berbagi ilmu, bertanggung jawab, berinisiatif, cepat dan akurat dalam bekerja serta dapat diandalkan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat sesuai rumusan sikap dasar profesionalisme. Aparatur profesional tidak berpuas diri dengan pengetahuan lama, melainkan terus belajar, menularkan ilmu kepada rekan kerja, dan mengeksekusi tugas dengan cepat serta akurat. Penjelasan resmi modul menetapkan rincian antusias belajar, berbagi ilmu, cepat akurat, dan dapat diandalkan. Oleh sebab itu, opsi D adalah rumusan yang tepat.</p><p>Jawaban tepat: <strong>D. Antusias belajar, berbagi ilmu, cepat akurat, dan terandalkan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan rumusan Sikap Dasar pada nilai dasar Integritas.</li><li><strong>B</strong>: Merupakan rumusan Sikap Dasar pada nilai dasar Sinergi.</li><li><strong>C</strong>: Merupakan rumusan Sikap Dasar pada nilai dasar Pelayanan.</li><li><strong>E</strong>: Merupakan rumusan Sikap Dasar pada nilai dasar Kesempurnaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 41) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Sikap Dasar: senantiasa antusias belajar, berbagi ilmu, bertanggung jawab • cepat dan akurat dalam bekerja serta dapat diandalkan</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Perilaku Efektif profesionalisme mengarahkan pegawai untuk menunjukkan prakarsa pencapaian sasaran kerja dengan...", "options": ["Menjaga kerahasiaan data jabatan serta menjauhi benturan kepentingan", "Membangun komunikasi harmonis dan mengelola perbedaan sebagai solusi", "Memberikan pelayanan yang adil serta responsif menindaklanjuti masukan", "Mendorong partisipasi tim dalam mencari peluang perbaikan sistem kerja", "Memperhitungkan risiko kerja berdasarkan dukungan data dan fakta"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Perilaku Efektif Profesionalisme: Manajemen Risiko</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Perilaku Efektif profesionalisme: <strong>Menunjukkan prakarsa dalam merencanakan dan mencapai rencana serta sasaran kerja dengan memperhitungkan risiko berdasarkan data dan fakta</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat tentang integrasi manajemen risiko dalam eksekusi tugas. Aparatur yang profesional bertindak terukur berdasarkan data dan fakta yang sahih guna mengantisipasi hambatan kinerja dan menjamin akuntabilitas keluaran. Panduan perilaku efektif menetapkan perhitungan risiko berbasis data dan fakta. Oleh karena itu, opsi E adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>E. Memperhitungkan risiko kerja berdasarkan dukungan data dan fakta</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan butir Perilaku Efektif pada nilai dasar Integritas.</li><li><strong>B</strong>: Merupakan butir Perilaku Efektif pada nilai dasar Sinergi.</li><li><strong>C</strong>: Merupakan butir Perilaku Efektif pada nilai dasar Pelayanan.</li><li><strong>D</strong>: Merupakan butir Perilaku Efektif pada nilai dasar Kesempurnaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 41) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Menunjukkan prakarsa dalam merencanakan dan mencapai rencana • memperhitungkan risiko berdasarkan data dan fakta</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Selain memperhitungkan risiko, Perilaku Efektif profesionalisme juga mengarahkan pegawai untuk memiliki...", "options": ["Komunikasi efektif, perhatikan kecepatan serta akurasi hasil", "Sikap kerja transparan sesuai perkataan dan perbuatan kedinasan", "Kemampuan mengelola perbedaan sebagai peluang solusi bersama", "Ketanggapan dalam merespons kebutuhan layanan pemangku tugas", "Kejelian dalam membaca dan memanfaatkan analisis data informasi"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Perilaku Efektif Profesionalisme: Komunikasi dan Kecepatan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Perilaku Efektif profesionalisme mencakup: <strong>Memperhatikan kecepatan dan akurasi untuk memperoleh hasil kinerja dengan kualitas terbaik</strong> serta <strong>Mengembangkan cara berkomunikasi efektif</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat tentang kompetensi eksekusi dan komunikasi profesional. Kecepatan tanpa akurasi membahayakan keuangan negara, sedangkan akurasi tanpa komunikasi efektif menimbulkan kebuntuan koordinasi, sehingga keduanya harus berpadu seimbang. Pedoman kerja merumuskan komunikasi efektif serta perhatian pada kecepatan dan akurasi. Dengan demikian, opsi A adalah perilaku yang tepat.</p><p>Jawaban tepat: <strong>A. Komunikasi efektif, perhatikan kecepatan serta akurasi hasil</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan butir Perilaku Efektif pada nilai dasar Integritas.</li><li><strong>C</strong>: Merupakan butir Perilaku Efektif pada nilai dasar Sinergi.</li><li><strong>D</strong>: Merupakan butir Perilaku Efektif pada nilai dasar Pelayanan.</li><li><strong>E</strong>: Merupakan butir Perilaku Efektif pada nilai dasar Kesempurnaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 41) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Memperhatikan kecepatan dan akurasi • kualitas terbaik • Mengembangkan cara berkomunikasi efektif</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Nilai dasar ASN 'Harmonis dan Kolaboratif' dalam sistem budaya Kementerian Keuangan diselaraskan dengan nilai...", "options": ["Integritas", "Sinergi", "Profesionalisme", "Pelayanan", "Kesempurnaan"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penyelarasan Nilai Sinergi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Harmonis dan Kolaboratif (Core Values ASN)</strong> selaras dengan <strong>Sinergi (nilai-nilai Kemenkeu)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat mengenai penyelarasan nilai kerja sama. Nilai Harmonis yang menekankan kepedulian antarsesama dan Kolaboratif yang membangun kerja sama strategis berpadu secara utuh dalam pilar Sinergi Kementerian Keuangan. Regulasi KMK 429 menetapkan pasangan penyelarasan tersebut. Maka dari itu, opsi B adalah nilai yang tepat.</p><p>Jawaban tepat: <strong>B. Sinergi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Nilai Integritas diselaraskan dengan Core Values Akuntabel dan Loyal.</li><li><strong>C</strong>: Nilai Profesionalisme diselaraskan dengan Core Values Kompeten.</li><li><strong>D</strong>: Nilai Pelayanan diselaraskan dengan Core Values Berorientasi Pelayanan.</li><li><strong>E</strong>: Nilai Kesempurnaan diselaraskan dengan Core Values Adaptif.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 42) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Harmonis dan Kolaboratif (Core Values ASN) selaras dengan • Sinergi</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Rumusan Sikap Dasar pada nilai Sinergi yang selaras dengan Harmonis dan Kolaboratif adalah senantiasa...", "options": ["Amanah, jujur, disiplin, konsisten, terpercaya, dan militan", "Antusias belajar, berbagi ilmu, cepat akurat, dan terandalkan", "Solid, proaktif, berkolaborasi, dan humanis dalam bekerja", "Komunikatif, respektif, responsif, cekatan, dan solutif", "Efektif manfaatkan perubahan, inovatif, terbuka, dan korektif"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sikap Dasar Nilai Sinergi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sikap Dasar pada nilai Sinergi adalah: <strong>senantiasa solid, proaktif, berkolaborasi, dan humanis</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat sesuai rumusan Sikap Dasar nilai Sinergi. Kesolidan tim, inisiatif proaktif dalam koordinasi, semangat bermitra, serta sentuhan humanis dalam memanusiakan rekan kerja membentuk lingkungan kerja kolaboratif yang sehat. Rincian sikap dasar resmi menetapkan solid, proaktif, berkolaborasi, dan humanis. Oleh sebab itu, opsi C adalah rumusan yang benar.</p><p>Jawaban tepat: <strong>C. Solid, proaktif, berkolaborasi, dan humanis dalam bekerja</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan rumusan Sikap Dasar pada nilai dasar Integritas.</li><li><strong>B</strong>: Merupakan rumusan Sikap Dasar pada nilai dasar Profesionalisme.</li><li><strong>D</strong>: Merupakan rumusan Sikap Dasar pada nilai dasar Pelayanan.</li><li><strong>E</strong>: Merupakan rumusan Sikap Dasar pada nilai dasar Kesempurnaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 42) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Sikap Dasar: senantiasa solid, proaktif, berkolaborasi, dan humanis</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Dalam menghadapi keanekaragaman gagasan dan latar belakang, Perilaku Efektif sinergi menuntut pegawai untuk...", "options": ["Berani mengemukakan fakta dengan pantas pada kondisi yang tepat", "Menjaga disiplin waktu kerja dan melaksanakan tugas secara efisien", "Menerima masukan dan melakukan evaluasi perbaikan layanan berkala", "Mengelola perbedaan pandangan sebagai peluang memberi solusi terbaik", "Mendorong partisipasi aktif untuk mencari terobosan cara-cara baru"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Perilaku Efektif Sinergi: Pengelolaan Perbedaan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Perilaku Efektif sinergi: <strong>Mampu mengelola perbedaan sebagai peluang untuk memberikan solusi dan hasil terbaik</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat tentang kepemimpinan kolaboratif aparatur. Perbedaan latar belakang dan sudut pandang tidak dipandang sebagai ancaman perpecahan, melainkan dikelola secara bijak sebagai sumber kekayaan gagasan untuk menghasilkan solusi terbaik. Pedoman perilaku menegaskan pengelolaan perbedaan sebagai peluang solusi terbaik. Dengan demikian, opsi D adalah perilaku yang tepat.</p><p>Jawaban tepat: <strong>D. Mengelola perbedaan pandangan sebagai peluang memberi solusi terbaik</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan butir Perilaku Efektif pada nilai dasar Integritas.</li><li><strong>B</strong>: Merupakan butir Perilaku Efektif pada nilai dasar Profesionalisme.</li><li><strong>C</strong>: Merupakan butir Perilaku Efektif pada nilai dasar Pelayanan.</li><li><strong>E</strong>: Merupakan butir Perilaku Efektif pada nilai dasar Kesempurnaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 42) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Mampu mengelola perbedaan sebagai peluang untuk • solusi dan • hasil terbaik</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Nilai dasar ASN 'Berorientasi Pelayanan' dalam penguatan budaya kementerian diselaraskan dengan nilai...", "options": ["Integritas", "Profesionalisme", "Sinergi", "Kesempurnaan", "Pelayanan"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penyelarasan Nilai Pelayanan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Berorientasi Pelayanan (Core Values ASN)</strong> selaras dengan <strong>Pelayanan (nilai-nilai Kemenkeu)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat mengenai penyelarasan pilar pelayanan aparatur. Semangat melayani masyarakat secara ramah, solutif, dan berorientasi pada kepuasan pemangku kepentingan dalam Core Value Berorientasi Pelayanan selaras sempurna dengan nilai Pelayanan Kemenkeu. Regulasi KMK 429 menetapkan pasangan penyelarasan ini secara tegas. Maka dari itu, opsi E adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>E. Pelayanan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Core Values ASN Akuntabel dan Loyal dipadankan dengan nilai Integritas.</li><li><strong>B</strong>: Core Values ASN Kompeten dipadankan dengan nilai Profesionalisme.</li><li><strong>C</strong>: Core Values ASN Harmonis dan Kolaboratif dipadankan dengan nilai Sinergi.</li><li><strong>D</strong>: Core Values ASN Adaptif dipadankan dengan nilai Kesempurnaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 42) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Berorientasi Pelayanan (Core Values ASN) selaras dengan • Pelayanan</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Sikap Dasar pada nilai Pelayanan yang selaras dengan Berorientasi Pelayanan mewajibkan pegawai senantiasa...", "options": ["Komunikatif, respektif, responsif, cekatan, dan solutif", "Amanah, jujur, disiplin, konsisten, terpercaya, dan militan", "Antusias belajar, berbagi ilmu, cepat akurat, dan terandalkan", "Solid, proaktif, berkolaborasi, dan humanis dalam bekerja", "Efektif manfaatkan perubahan, inovatif, terbuka, dan korektif"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sikap Dasar Nilai Pelayanan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sikap Dasar pada nilai Pelayanan adalah: <strong>senantiasa komunikatif, respektif, responsif, cekatan, dan solutif untuk memberikan layanan terbaik</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat sesuai rumusan Sikap Dasar nilai Pelayanan dalam KMK 429. Aparatur yang melayani dituntut membuka komunikasi yang hangat, menghormati hak masyarakat, tanggap terhadap keluhan, bergerak cepat, serta selalu menghadirkan solusi nyata. Pedoman modul merumuskan komunikatif, respektif, responsif, cekatan, dan solutif. Oleh sebab itu, opsi A adalah rumusan yang benar.</p><p>Jawaban tepat: <strong>A. Komunikatif, respektif, responsif, cekatan, dan solutif</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan rumusan Sikap Dasar pada nilai dasar Integritas.</li><li><strong>C</strong>: Merupakan rumusan Sikap Dasar pada nilai dasar Profesionalisme.</li><li><strong>D</strong>: Merupakan rumusan Sikap Dasar pada nilai dasar Sinergi.</li><li><strong>E</strong>: Merupakan rumusan Sikap Dasar pada nilai dasar Kesempurnaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 42) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Sikap Dasar: senantiasa komunikatif, respektif, responsif, cekatan • solutif untuk • memberikan layanan terbaik</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Nilai dasar ASN 'Adaptif' dalam sistem penguatan budaya Kementerian Keuangan diselaraskan dengan nilai...", "options": ["Integritas", "Kesempurnaan", "Profesionalisme", "Sinergi", "Pelayanan"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penyelarasan Nilai Kesempurnaan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Adaptif (Core Values ASN)</strong> selaras dengan <strong>Kesempurnaan (nilai-nilai Kemenkeu)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat mengenai penyelarasan nilai perbaikan berkelanjutan. Kemampuan beradaptasi dengan disrupsi zaman, berinovasi secara fleksibel, dan antusias menyongsong perubahan dalam nilai Adaptif menopang terwujudnya nilai Kesempurnaan di Kemenkeu. Regulasi KMK 429 menetapkan pasangan nilai Adaptif selaras dengan Kesempurnaan. Oleh karena itu, opsi B adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>B. Kesempurnaan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Core Values ASN Akuntabel dan Loyal dipadankan dengan nilai Integritas.</li><li><strong>C</strong>: Core Values ASN Kompeten dipadankan dengan nilai Profesionalisme.</li><li><strong>D</strong>: Core Values ASN Harmonis dan Kolaboratif dipadankan dengan nilai Sinergi.</li><li><strong>E</strong>: Core Values ASN Berorientasi Pelayanan dipadankan dengan nilai Pelayanan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 42) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Adaptif (Core Values ASN) selaras dengan • Kesempurnaan</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Sikap Dasar pada nilai Kesempurnaan menuntut pegawai di lingkungan kementerian senantiasa...", "options": ["Amanah, jujur, disiplin, konsisten, terpercaya, dan militan", "Antusias belajar, berbagi ilmu, cepat akurat, dan terandalkan", "Efektif manfaatkan perubahan, inovatif, terbuka, dan korektif", "Solid, proaktif, berkolaborasi, dan humanis dalam bekerja", "Komunikatif, respektif, responsif, cekatan, dan solutif"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sikap Dasar Nilai Kesempurnaan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sikap Dasar pada nilai Kesempurnaan adalah: <strong>senantiasa efektif memanfaatkan perubahan, inovatif, transparan, dan korektif, menuju kesempurnaan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat sesuai rumusan Sikap Dasar nilai Kesempurnaan dalam KMK 429. Keberhasilan menuju kesempurnaan menuntut pegawai tanggap menangkap peluang dari perubahan lingkungan, melahirkan ide pembaruan, menjaga transparansi, serta berani mengoreksi kelemahan sistem secara objektif. Pedoman etika merinci efektif memanfaatkan perubahan, inovatif, transparan, dan korektif. Dengan demikian, opsi C adalah rumusan yang tepat.</p><p>Jawaban tepat: <strong>C. Efektif manfaatkan perubahan, inovatif, terbuka, dan korektif</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan rumusan Sikap Dasar pada nilai dasar Integritas.</li><li><strong>B</strong>: Merupakan rumusan Sikap Dasar pada nilai dasar Profesionalisme.</li><li><strong>D</strong>: Merupakan rumusan Sikap Dasar pada nilai dasar Sinergi.</li><li><strong>E</strong>: Merupakan rumusan Sikap Dasar pada nilai dasar Pelayanan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 42) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Sikap Dasar: senantiasa efektif memanfaatkan perubahan, inovatif, transparan • menuju kesempurnaan</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Dalam rangka perbaikan sistem yang komprehensif, Perilaku Efektif kesempurnaan mengamanatkan pegawai untuk...", "options": ["Menjaga kerahasiaan data organisasi dan bertanggung jawab atas BMN", "Mengembangkan pola pikir dengan memanfaatkan manajemen pengetahuan", "Bersikap empati, suka menolong, dan kooperatif demi tujuan bersama", "Mendorong partisipasi aktif perbaikan sistem kerja komprehensif", "Memberikan pelayanan yang adil dan tanggap menindaklanjuti kebutuhan"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Perilaku Efektif Kesempurnaan: Perbaikan Sistem</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Perilaku Efektif kesempurnaan: <strong>Mendorong partisipasi aktif untuk mencari peluang perbaikan, mengembangkan dan mengimplementasikan cara-cara baru/inovatif dalam rangka perbaikan sistem secara lebih komprehensif</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat tentang partisipasi inovasi aparatur. Perbaikan sistem yang berdaya tahan lama tidak bisa dilakukan sepihak oleh pimpinan, melainkan membutuhkan pelibatan seluruh anggota tim dalam mengidentifikasi titik lemah dan menciptakan terobosan tata kelola. Panduan perilaku menetapkan keharusan mendorong partisipasi aktif untuk perbaikan komprehensif. Oleh sebab itu, opsi D adalah perilaku yang tepat.</p><p>Jawaban tepat: <strong>D. Mendorong partisipasi aktif perbaikan sistem kerja komprehensif</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan butir Perilaku Efektif pada nilai dasar Integritas.</li><li><strong>B</strong>: Merupakan butir Perilaku Efektif pada nilai dasar Profesionalisme.</li><li><strong>C</strong>: Merupakan butir Perilaku Efektif pada nilai dasar Sinergi.</li><li><strong>E</strong>: Merupakan butir Perilaku Efektif pada nilai dasar Pelayanan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 43) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Mendorong partisipasi aktif untuk mencari peluang perbaikan • sistem secara lebih komprehensif</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Perilaku Efektif kesempurnaan menuntut pegawai tidak sekadar berwacana, melainkan mampu...", "options": ["Menunjukkan perilaku jujur dan berani mengemukakan fakta secara pantas", "Melaksanakan tugas dengan memperhatikan kecepatan dan akurasi terbaik", "Melakukan kolaborasi yang harmonis dengan mengedepankan keterbukaan", "Fokus pada penyelesaian masalah dan memberikan solusi layanan terbaik", "Mengapresiasi kreativitas untuk mengembangkan proses kerja bermutu"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Perilaku Efektif Kesempurnaan: Apresiasi Kreativitas</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Perilaku Efektif kesempurnaan: <strong>Memanfaatkan situasi tantangan dan mengapresiasi kreativitas untuk mengembangkan proses kerja menuju kesempurnaan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat tentang penghargaan terhadap inisiatif inovasi. Budaya kesempurnaan tumbuh subur apabila setiap ide kreatif rekan kerja dihargai dan diuji coba untuk memangkas birokrasi serta meningkatkan mutu keluaran layanan publik. Panduan perilaku efektif menetapkan pengapresiasian kreativitas guna mengembangkan proses kerja. Oleh karena itu, opsi E adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>E. Mengapresiasi kreativitas untuk mengembangkan proses kerja bermutu</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan butir Perilaku Efektif pada nilai dasar Integritas.</li><li><strong>B</strong>: Merupakan butir Perilaku Efektif pada nilai dasar Profesionalisme.</li><li><strong>C</strong>: Merupakan butir Perilaku Efektif pada nilai dasar Sinergi.</li><li><strong>D</strong>: Merupakan butir Perilaku Efektif pada nilai dasar Pelayanan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 43) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Mendorong partisipasi aktif untuk mencari peluang perbaikan • dan mengimplementasikan cara-cara baru/inovatif</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Dalam melakukan evaluasi perbaikan berkelanjutan, langkah objektif yang wajib ditempuh aparatur adalah...", "options": ["Membaca, menganalisis, dan memanfaatkan data informasi secara rutin", "Menyusun ringkasan analisis berdasarkan estimasi kebutuhan anggaran", "Menunggu instruksi resmi penetapan indikator kinerja baru kementerian", "Mengacu pada publikasi tren makroekonomi lembaga riset independen", "Mengadopsi format lembar verifikasi administrasi kantor mitra dinas"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Perilaku Efektif Kesempurnaan: Analisis Data</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Perilaku Efektif kesempurnaan: <strong>Membaca, menganalisis, dan memanfaatkan data dan informasi guna perbaikan pekerjaan secara terus menerus</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat mengenai pengambilan keputusan berbasis data (evidence-based decision making). Perbaikan berkelanjutan tidak boleh didasarkan pada intuisi subjektif, melainkan harus ditopang pembacaan dan analisis cermat atas data kinerja guna menemukan celah perbaikan yang tepat sasaran. Rincian perilaku modul merumuskan membaca, menganalisis, dan memanfaatkan data informasi. Dengan demikian, opsi A adalah langkah yang tepat.</p><p>Jawaban tepat: <strong>A. Membaca, menganalisis, dan memanfaatkan data informasi secara rutin</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Estimasi sepihak tanpa data historis valid berisiko menghasilkan perencanaan bias.</li><li><strong>C</strong>: Menunggu instruksi pasif bertentangan dengan etos proaktif nilai kesempurnaan.</li><li><strong>D</strong>: Data riset makroekonomi umum tidak menggantikan analisis data mikro operasional satuan kerja.</li><li><strong>E</strong>: Meniru format kantor mitra tanpa analisis internal tidak menjamin kesesuaian kebutuhan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 43) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Membaca, menganalisis, dan memanfaatkan data dan informasi • perbaikan • pekerjaan secara terus menerus</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Perilaku Efektif pada nilai kesempurnaan menuntut pegawai memiliki visi yang maju agar mampu...", "options": ["Mengidentifikasi potensi risiko fiskal pada tahun anggaran berjalan", "Menyusun rencana yang relevan dan matang serta memeriksa hasil kerja", "Menetapkan standar kompetensi teknis fungsional pranata keuangan", "Memetakan sebaran alokasi pagu belanja modal satuan kerja vertikal", "Mengembangkan sistem pelaporan capaian output strategis kementerian"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Perilaku Efektif Kesempurnaan: Visi Maju</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Perilaku Efektif kesempurnaan: <strong>Memiliki visi yang maju agar mampu menyusun rencana yang relevan dan matang, serta memeriksa hasil pekerjaan yang dilakukan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat mengenai wawasan masa depan dalam perencanaan tugas. Visi maju mengarahkan aparatur untuk melihat tren jangka panjang, merumuskan rencana operasional yang matang dan kontekstual, serta senantiasa menguji kualitas keluaran kerja sebelum diserahkan kepada pengguna. Penjelasan resmi modul menetapkan rencana relevan matang serta pemeriksaan hasil kerja. Oleh sebab itu, opsi B adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>B. Menyusun rencana yang relevan dan matang serta memeriksa hasil kerja</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan tugas khusus analisis risiko pada bagian perbendaharaan dan fiskal.</li><li><strong>C</strong>: Merupakan wewenang instansi pembina jabatan fungsional pengelola keuangan.</li><li><strong>D</strong>: Merupakan ranah pembagian anggaran belanja modal pada unit perencana.</li><li><strong>E</strong>: Merupakan fungsi evaluasi akuntabilitas kinerja sistemik pada tingkat kementerian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 43) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Memiliki visi yang maju agar mampu menyusun rencana yang relevan dan • matang, serta memeriksa hasil pekerjaan</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Selain menyusun rencana kerja matang, kebiasaan profesional yang wajib dipraktikkan pegawai adalah...", "options": ["Mengarsipkan dokumen dinas dalam format digital pada basis data kantor", "Mengomunikasikan capaian kinerja harian pada forum evaluasi antarseksi", "Memeriksa secara teliti hasil pekerjaan yang telah diselesaikan", "Memperbarui data inventarisasi sarana perkantoran secara periodik", "Mencatat setiap masukan teknis dari rapat koordinasi satuan kerja"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Perilaku Efektif Kesempurnaan: Pemeriksaan Hasil Kerja</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Perilaku Efektif kesempurnaan menuntut pegawai untuk <strong>menyusun rencana yang relevan dan matang, serta memeriksa hasil pekerjaan yang dilakukan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat tentang ketelitian dan pengendalian mutu pribadi (self-quality control). Memeriksa kembali hasil kerja sebelum dikirimkan mencegah kekeliruan administrasi, menjamin kepatuhan hukum, dan menegaskan komitmen memberikan layanan terbaik. Pedoman perilaku efektif merinci keharusan memeriksa hasil pekerjaan yang dilakukan. Maka dari itu, opsi C adalah tindakan yang benar.</p><p>Jawaban tepat: <strong>C. Memeriksa secara teliti hasil pekerjaan yang telah diselesaikan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan prosedur tata kelola kearsipan digital pada unit kesekretariatan.</li><li><strong>B</strong>: Merupakan komunikasi koordinasi internal dalam pembagian beban tugas.</li><li><strong>D</strong>: Merupakan pengelolaan fisik aset barang milik negara pada seksi umum.</li><li><strong>E</strong>: Merupakan dokumentasi notula rapat dinas pada fungsi keprotokolan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 43) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>memeriksa hasil pekerjaan yang dilakukan • menyusun rencana yang relevan dan • matang</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Tujuan akhir dari kebiasaan membaca dan menganalisis data informasi di lingkungan kerja adalah...", "options": ["Pengurangan durasi siklus penyusunan laporan keuangan kementerian", "Peningkatan akurasi proyeksi penerimaan kas negara setiap triwulan", "Penyeragaman format dokumen administrasi tata kelola perkantoran", "Perbaikan pekerjaan secara terus menerus dan berkesinambungan", "Optimalisasi penatausahaan barang milik negara berbasis aplikasi"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuan Analisis Data Nilai Kesempurnaan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Membaca, menganalisis, dan memanfaatkan data dan informasi ditujukan <strong>guna perbaikan pekerjaan secara terus menerus</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat sesuai kaidah nilai Kesempurnaan. Data dan informasi bukan sekadar arsip statistik pasif, melainkan amunisi evaluasi kritis untuk menemukan kelemahan proses bisnis dan menyempurnakannya tanpa henti (continuous improvement). Bunyi resmi modul menegaskan tujuannya adalah perbaikan pekerjaan secara terus menerus. Oleh karena itu, opsi D adalah tujuan yang tepat.</p><p>Jawaban tepat: <strong>D. Perbaikan pekerjaan secara terus menerus dan berkesinambungan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan sasaran efisiensi waktu pada direktorat akuntansi pelaporan keuangan.</li><li><strong>B</strong>: Merupakan target akurasi proyeksi pada direktorat jenderal perbendaharaan.</li><li><strong>C</strong>: Merupakan sasaran standarisasi tata naskah dinas pada biro umum.</li><li><strong>E</strong>: Merupakan sasaran penertiban barang milik negara pada direktorat jenderal kekayaan negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 43) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Membaca, menganalisis, dan memanfaatkan data dan informasi • guna perbaikan • pekerjaan secara terus menerus</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Karakteristik rencana kerja yang bermutu dalam perencanaan dan pelaksanaan kerja harus bersifat...", "options": ["Berorientasi pada pemenuhan target administratif formalitas semata", "Disesuaikan dengan batas waktu siklus penyerapan anggaran tahunan", "Mengacu pada tolok ukur standar pelayanan minimum lembaga publik", "Memperhatikan ketersediaan sarana prasarana penunjang perkantoran", "Relevan dengan kebutuhan organisasi serta disusun secara matang"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Karakteristik Rencana Kerja Nilai Kesempurnaan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Aparatur yang memiliki visi maju dituntut mampu <strong>menyusun rencana yang relevan dan matang, serta memeriksa hasil pekerjaan yang dilakukan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat mengenai standar mutu perencanaan kerja. Rencana kerja yang efektif harus menjawab tantangan nyata organisasi (relevan) dan dikaji secara mendalam dari segi sumber daya serta mitigasi risiko (matang) agar dapat dieksekusi dengan sukses. Pedoman kerja menetapkan karakteristik rencana yang relevan dan matang. Dengan demikian, opsi E adalah rumusan yang benar.</p><p>Jawaban tepat: <strong>E. Relevan dengan kebutuhan organisasi serta disusun secara matang</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Perencanaan formalitas semata mengabaikan substansi mutu keluaran layanan publik.</li><li><strong>B</strong>: Batas waktu anggaran merupakan kendala teknis dan bukan indikator mutu rencana.</li><li><strong>C</strong>: Standar pelayanan minimum merupakan batas dasar kepatuhan pelayanan publik.</li><li><strong>D</strong>: Ketersediaan fasilitas merupakan faktor pendukung logistik perkantoran.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 43) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menyusun rencana yang relevan dan • matang</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Panduan Pelaksanaan Penguatan Budaya di lingkungan Kementerian Keuangan diatur melalui...", "options": ["Surat Edaran Nomor SE-15/MK.1/2022", "Instruksi Menteri Keuangan Nomor 12/IMK.01/2021", "Peraturan Pemerintah Nomor 94 Tahun 2021", "Keputusan Bersama Menteri Nomor 05/KBM/2020", "Peraturan Presiden Republik Indonesia Nomor 17 Tahun 2022"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Regulasi SE-15/MK.1/2022</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Menteri Keuangan menerbitkan <strong>Surat Edaran Nomor SE-15/MK.1/2022 tentang Panduan Pelaksanaan Penguatan Budaya di lingkungan Kementerian Keuangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat mengenai nomor dan nomenklatur regulasi panduan budaya kerja kementerian. SE-15/MK.1/2022 memuat petunjuk teknis implementasi penumbuhan sikap dasar dan perilaku efektif bagi pimpinan unit dan pengelola SDM. Ketentuan resmi kementerian menetapkan Surat Edaran Nomor SE-15/MK.1/2022. Dengan demikian, opsi A adalah nama aturan yang tepat.</p><p>Jawaban tepat: <strong>A. Surat Edaran Nomor SE-15/MK.1/2022</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Bukan nomor surat edaran panduan penguatan budaya yang berlaku resmi.</li><li><strong>C</strong>: Merupakan Peraturan Pemerintah tentang Disiplin Pegawai Negeri Sipil nasional.</li><li><strong>D</strong>: Merupakan regulasi bersama antarkementerian yang bukan produk hukum khusus budaya Kemenkeu.</li><li><strong>E</strong>: Merupakan peraturan presiden umum di luar panduan operasional budaya kementerian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 44) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Surat Edaran Nomor SE-15/MK.1/2022 tentang Panduan • Pelaksanaan Penguatan Budaya</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Surat Edaran Nomor SE-15/MK.1/2022 diterbitkan secara khusus sebagai panduan bagi...", "options": ["Auditor Badan Pemeriksa Keuangan dan pengawas eksternal", "Pimpinan unit dan unit pengelola kepegawaian di kementerian", "Konsultan manajemen sumber daya manusia sektor perbankan", "Seluruh rekanan penyedia pengadaan barang dan jasa kantor", "Aparat penegak hukum pada bidang penanganan sengketa perdata"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sasaran Pembaca SE-15/MK.1/2022</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Surat Edaran tersebut <strong>ditujukan sebagai panduan pimpinan unit dan unit pengelola kepegawaian untuk melakukan upaya menumbuhkan dan membudayakan Sikap Dasar dan Perilaku Efektif</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat mengenai sasaran subjek penerima panduan budaya. Pimpinan unit dan pengelola kepegawaian memegang peranan kunci sebagai arsitek dan penggerak penanaman nilai bagi seluruh pegawai di bawah naungannya. Rincian tujuan surat edaran menyebutkan panduan bagi pimpinan unit dan unit pengelola kepegawaian. Oleh sebab itu, opsi B adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>B. Pimpinan unit dan unit pengelola kepegawaian di kementerian</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: BPK merupakan auditor eksternal negara yang bekerja berdasarkan standar audit sendiri.</li><li><strong>C</strong>: Konsultan swasta bukan pihak internal pengelola budaya kerja kementerian.</li><li><strong>D</strong>: Penyedia barang jasa merupakan mitra pengadaan dan bukan subjek pembudayaan internal.</li><li><strong>E</strong>: Aparat penegak hukum perdata menangani sengketa kebendaan hukum luar instansi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 44) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Surat Edaran • dimaksud ditujukan sebagai panduan pimpinan unit dan unit pengelola kepegawaian</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Tujuan penerbitan SE-15/MK.1/2022 selain memperkuat budaya di lingkungan instansi adalah mendorong pegawai menerapkan nilai dalam...", "options": ["Penyusunan berkas pertanggungjawaban hibah luar negeri", "Kegiatan penelitian akademis pada jenjang pascasarjana", "Bekerja melaksanakan tugas dinas dan kehidupan sehari-hari", "Penyelenggaraan audiensi bersama organisasi masyarakat", "Pengelolaan portofolio investasi aset keuangan daerah"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuan Penerapan Nilai Sehari-hari</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Panduan budaya bertujuan untuk <strong>mendorong pegawai dalam penerapan Sikap Dasar dan Perilaku Efektif dalam bekerja dan melaksanakan tugas di lingkungan Kementerian Keuangan dan kehidupan sehari-hari</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat tentang ruang lingkup pengamalan nilai. Internalisasi nilai aparatur tidak boleh terhenti saat keluar dari pintu kantor, melainkan harus menyatu dalam karakter pribadi dan dipraktikkan secara konsisten dalam kehidupan bermasyarakat. Bunyi resmi SE-15/MK.1/2022 menegaskan penerapannya dalam bertugas dan kehidupan sehari-hari. Maka dari itu, opsi C adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>C. Bekerja melaksanakan tugas dinas dan kehidupan sehari-hari</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan administrasi pengelolaan hibah pada direktorat jenderal perbendaharaan.</li><li><strong>B</strong>: Merupakan kegiatan riset ilmiah pada bidang pendidikan kepegawaian.</li><li><strong>D</strong>: Merupakan kegiatan protokoler hubungan masyarakat pada bagian kehumasan.</li><li><strong>E</strong>: Merupakan pengelolaan pembiayaan daerah pada direktorat dana perimbangan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 44) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>mendorong pegawai dalam penerapan Sikap Dasar dan • Perilaku Efektif dalam bekerja dan melaksanakan tugas di lingkungan Kementerian • Keuangan dan kehidupan sehari-hari</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Target tingkat kematangan minimal bagi mahasiswa PKN STAN dan CPNS atau pelaksana kurang dari satu tahun adalah...", "options": ["Penerapan seluruh sikap dasar atau implementing the values", "Memiliki dan mengembangkan nilai atau owning the values", "Paham pentingnya dan penerapan sebagian nilai organisasi", "Sadar pentingnya sikap dasar dan perilaku efektif atau aware the values", "Menjadi role model pembudayaan nilai bagi instansi kementerian lain"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Target Kematangan Mahasiswa STAN dan CPNS</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Target tingkat kematangan minimal bagi Mahasiswa PKN STAN dan CPNS/Pelaksana kurang dari 1 tahun adalah: <strong>Sadar pentingnya Sikap Dasar dan Perilaku Efektif (aware the values)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat mengenai target kematangan fase awal kepegawaian. Pada jenjang mahasiswa dan calon pegawai baru, fokus utama pembudayaan adalah membangun kesadaran (*awareness*) akan arti penting integritas dan nilai-nilai kementerian dalam kehidupan berbirokrasi. Matriks strategi pembudayaan menetapkan target *aware the values*. Oleh karena itu, opsi D adalah target kematangan yang benar.</p><p>Jawaban tepat: <strong>D. Sadar pentingnya sikap dasar dan perilaku efektif atau aware the values</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan target tingkat kematangan bagi Pejabat Pengawas dan Administrator.</li><li><strong>B</strong>: Merupakan target tingkat kematangan bagi Pejabat Pimpinan Tinggi.</li><li><strong>C</strong>: Merupakan target tingkat kematangan bagi Pelaksana dengan masa kerja 1 tahun atau lebih.</li><li><strong>E</strong>: Merupakan peran eksternalisasi bagi Pejabat Pimpinan Tinggi instansi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 44) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Sadar pentingnya • Sikap Dasar dan • Perilaku Efektif • (aware the values)</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Salah satu bentuk strategi internalisasi nilai yang dirancang bagi mahasiswa PKN STAN dan CPNS kementerian adalah...", "options": ["Ujian kompetensi jabatan administrator secara tertulis", "Penyusunan naskah pakta integritas bersama mitra kerja", "Pelaksanaan audit operasional pengelolaan anggaran kas", "Rotasi kepangkatan berkala setiap periode semesteran", "Penguatan budaya dalam kurikulum dan pelatihan dasar"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Strategi Internalisasi Mahasiswa STAN dan CPNS</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Strategi internalisasi bagi mahasiswa STAN dan CPNS mencakup: <strong>Penguatan budaya dalam kurikulum PKN STAN, Program Habituasi Budaya, serta Penguatan budaya melalui pelatihan dasar CPNS/pegawai baru Kemenkeu</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat mengenai kanal penanaman nilai bagi pemula. Memasukkan materi budaya ke dalam kurikulum perkuliahan di STAN dan kurikulum Latsar CPNS memastikan setiap calon aparatur dibekali pondasi etika sejak awal pembentukan karakternya. Tabel strategi modul merinci penguatan budaya dalam kurikulum dan latsar. Dengan demikian, opsi E adalah bentuk strategi yang benar.</p><p>Jawaban tepat: <strong>E. Penguatan budaya dalam kurikulum dan pelatihan dasar</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan uji kompetensi manajerial bagi jenjang eselon administrator.</li><li><strong>B</strong>: Merupakan strategi institusionalisasi bagi jenjang pegawai berpengalaman.</li><li><strong>C</strong>: Merupakan tugas pemeriksaan pengawasan keuangan oleh aparat pengawas.</li><li><strong>D</strong>: Merupakan kebijakan mutasi penempatan tugas reguler kepegawaian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 44) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Penguatan • budaya dalam • kurikulum PKN • STAN • pelatihan dasar • CPNS/ pegawai • baru Kemenkeu</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Program pembiasaan nilai yang menjadi salah satu strategi internalisasi budaya bagi CPNS dan mahasiswa PKN STAN adalah...", "options": ["Program Habituasi Budaya", "Program Magang Struktural", "Program Audit Kepegawaian", "Program Konseling Jabatan", "Program Rotasi Kedinasan"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Program Habituasi Budaya STAN dan CPNS</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Salah satu bentuk strategi internalisasi bagi mahasiswa STAN dan CPNS adalah pelaksanaan <strong>Program Habituasi Budaya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat mengenai program pembiasaan nilai. Program habituasi dirancang untuk membiasakan peserta didik dan pegawai baru mengamalkan sikap dasar dan perilaku efektif dalam interaksi sehari-hari dan penugasan awal. Penjelasan matriks strategi modul menetapkan nama Program Habituasi Budaya. Oleh sebab itu, opsi A adalah nama program yang tepat.</p><p>Jawaban tepat: <strong>A. Program Habituasi Budaya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Bukan nama program pembudayaan nilai yang tercantum dalam panduan resmi.</li><li><strong>C</strong>: Merupakan kegiatan verifikasi kepatuhan administrasi regulasi kepegawaian.</li><li><strong>D</strong>: Merupakan layanan pendampingan psikologis atau karier bagi pegawai.</li><li><strong>E</strong>: Merupakan kebijakan mutasi penugasan berkala aparatur kementerian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 44) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Program • Habituasi • Budaya</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Strategi eksternalisasi budaya yang dirancang bagi mahasiswa PKN STAN dan pegawai baru dilaksanakan dalam bentuk...", "options": ["Pembentukan pos pengaduan layanan terpadu kementerian", "Tugas desain grafis atau video sosialisasi eksternal", "Penyelenggaraan forum dengar pendapat bersama publik", "Publikasi laporan berkala kepatuhan etika aparatur", "Penandatanganan nota kesepahaman pengawasan bersama"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Eksternalisasi Budaya Mahasiswa STAN dan CPNS</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Strategi eksternalisasi bagi mahasiswa STAN dan CPNS adalah kompetisi atau <strong>tugas desain grafis atau video terkait budaya sebagai bahan sosialisasi kepada pihakpihak eksternal</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat mengenai wujud eksternalisasi kreatif bagi pegawai pemula. Mahasiswa dan CPNS diberdayakan keterampilannya untuk memproduksi konten kreatif seperti video dan grafis budaya guna menyebarkan nilai-nilai luhur Kemenkeu ke masyarakat luas. Tabel modul merinci tugas desain grafis atau video sosialisasi eksternal. Oleh karena itu, opsi B adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>B. Tugas desain grafis atau video sosialisasi eksternal</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pos pengaduan terpadu merupakan sarana operasional unit pelayanan publik.</li><li><strong>C</strong>: Forum dengar pendapat diselenggarakan oleh pejabat pimpinan instansi.</li><li><strong>D</strong>: Publikasi laporan kepatuhan merupakan kewenangan unit pengawasan intern.</li><li><strong>E</strong>: Penandatanganan nota kesepahaman merupakan wewenang pimpinan struktural.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 44) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>tugas desain • grafis atau video • sosialisasi • kepada pihakpihak eksternal</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Target tingkat kematangan minimal bagi pegawai pelaksana dengan masa kerja satu tahun atau lebih adalah...", "options": ["Sadar akan arti penting nilai atau aware the values", "Penerapan seluruh sikap dasar atau implementing values", "Paham pentingnya dan penerapan sebagian sikap dasar", "Memiliki dan mengembangkan nilai atau owning the values", "Menjadi perumus kerangka pengawasan kepatuhan etika"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Target Kematangan Pelaksana Masa Kerja >= 1 Tahun</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Target tingkat kematangan minimal bagi Pelaksana masa kerja 1 tahun atau lebih adalah: <strong>Paham pentingnya dan Penerapan sebagian Sikap Dasar dan Perilaku Efektif (understand the values)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat mengenai target maturitas pelaksana senior. Pegawai yang telah berdinas lebih dari satu tahun diharapkan telah melampaui fase sekadar tahu (sadar) dan mulai memahami urgensi nilai serta mengamalkannya dalam porsi nyata tugas pekerjaan harian (*understand the values*). Rincian tabel modul menetapkan paham pentingnya dan penerapan sebagian sikap dasar. Dengan demikian, opsi C adalah target yang tepat.</p><p>Jawaban tepat: <strong>C. Paham pentingnya dan penerapan sebagian sikap dasar</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan target minimal bagi mahasiswa STAN dan CPNS kurang dari 1 tahun.</li><li><strong>B</strong>: Merupakan target minimal bagi Pejabat Pengawas dan Administrator.</li><li><strong>D</strong>: Merupakan target minimal bagi Pejabat Pimpinan Tinggi.</li><li><strong>E</strong>: Merupakan tugas pimpinan unit pembina kepegawaian kementerian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 45) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Paham pentingnya • dan Penerapan • sebagian Sikap • Dasar dan Perilaku • Efektif (understand • the values)</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Penggerak internalisasi budaya bagi pelaksana yang bertindak sebagai agen perubahan di unit kerja adalah...", "options": ["Petugas pengelola aset inventaris kantor", "Pejabat pemeriksa kas bendahara penerimaan", "Pengawas urusan kebersihan rumah tangga", "Duta Transformasi pada satuan unit kerja", "Petugas satuan pengamanan gedung kantor"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Internalisasi oleh Duta Transformasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Salah satu bentuk strategi internalisasi bagi pelaksana adalah pelaksanaan <strong>Internalisasi oleh Duta Transformasi</strong> di lingkungan kerja.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat mengenai figur agen penggerak budaya kerja. Duta Transformasi dipilih dan dibekali secara khusus untuk menjadi katalisator perubahan, panutan, dan penyambung pesan budaya kepada rekan sejawat di lingkungan kerjanya. Panduan strategi pembudayaan menetapkan internalisasi oleh Duta Transformasi. Oleh sebab itu, opsi D adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>D. Duta Transformasi pada satuan unit kerja</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Petugas pengelola aset mengurusi penatausahaan barang milik negara.</li><li><strong>B</strong>: Pemeriksa kas bertugas melakukan rekonsiliasi pembukuan perbendaharaan.</li><li><strong>C</strong>: Pengawas rumah tangga bertugas mengelola fasilitas fisik perkantoran.</li><li><strong>E</strong>: Petugas keamanan bertugas menjaga ketertiban fisik lingkungan gedung dinas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 45) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Internalisasi • oleh Duta • Transformasi</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Unit kerja yang diperkuat perannya dalam strategi institusionalisasi budaya kerja kementerian adalah...", "options": ["Biro Organisasi dan Tata Laksana Sekretariat Jenderal", "Pusat Pendidikan dan Pelatihan Keuangan Terpadu", "Badan Standardisasi dan Kebijakan Fiskal Negara", "Komite Pengawas Perpajakan dan Bea Cukai Publik", "Inspektorat Jenderal dan Unit Kepatuhan Internal"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Unit Penguatan Institusionalisasi Budaya</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Strategi institusionalisasi budaya mencakup <strong>Penguatan peran Inspektorat Jenderal, Unit Kepatuhan Internal (UKI), dan Kehumasan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat mengenai benteng pengawasan institusional budaya. Itjen dan UKI bertindak sebagai garda terdepan penegakan kepatuhan etika dan disiplin, sedangkan Kehumasan memperkuat amplifikasi narasi positif budaya ke seluruh kanal komunikasi. Rincian tabel modul menegaskan penguatan peran Inspektorat Jenderal, UKI, dan Kehumasan. Oleh karena itu, opsi E adalah unit kerja yang tepat.</p><p>Jawaban tepat: <strong>E. Inspektorat Jenderal dan Unit Kepatuhan Internal</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Biro Organta mengurusi tata laksana dan struktur organisasi umum.</li><li><strong>B</strong>: Pusdiklat bertugas menyelenggarakan pelatihan kedinasan teknis.</li><li><strong>C</strong>: BKF bertugas merumuskan kebijakan fiskal dan makroekonomi negara.</li><li><strong>D</strong>: Komite Pengawas Perpajakan mengawasi kebijakan perpajakan secara makro.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 45) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Penguatan • peran • Inspektorat • Jenderal, Unit • Kepatuhan • Internal (UKI), • dan Kehumasan</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Dokumen formal perwujudan komitmen moral pegawai yang dibuat dalam strategi institusionalisasi adalah...", "options": ["Pakta Integritas aparatur sipil kementerian", "Berita Acara Rekonsiliasi Kas Perbendaharaan", "Surat Pernyataan Tanggung Jawab Belanja Modal", "Lembar Konfirmasi Capaian Kinerja Pegawai", "Catatan Hasil Pengawasan Rutin Operasional"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dokumen Pakta Integritas Institusionalisasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Strategi institusionalisasi budaya bagi pelaksana mencakup kegiatan <strong>Membuat Pakta Integritas</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat mengenai ikrar tertulis aparatur. Penandatanganan Pakta Integritas mengikat komitmen moral setiap pegawai untuk menjauhi korupsi, menolak gratifikasi, serta setia menjalankan nilai dan kode etik kementerian dalam pelaksanaan tugasnya. Panduan modul mencantumkan pembuatan Pakta Integritas sebagai butir institusionalisasi. Dengan demikian, opsi A adalah dokumen yang benar.</p><p>Jawaban tepat: <strong>A. Pakta Integritas aparatur sipil kementerian</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan dokumen pembukuan akuntansi kas pada pejabat perbendaharaan.</li><li><strong>C</strong>: Merupakan dokumen pertanggungjawaban yuridis pelaksanaan belanja anggaran.</li><li><strong>D</strong>: Merupakan formulir penilaian kinerja berkala pada sistem kepegawaian.</li><li><strong>E</strong>: Merupakan laporan hasil pemeriksaan kepatuhan administratif satuan kerja.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 45) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Membuat Pakta • Integritas</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Target tingkat kematangan minimal bagi Pejabat Pengawas dan Administrator di Kementerian Keuangan adalah...", "options": ["Sadar akan arti penting nilai atau aware the values", "Penerapan seluruh sikap dasar atau implementing values", "Paham pentingnya nilai dan penerapan sebagian sikap", "Memiliki dan mengembangkan nilai atau owning the values", "Menjadi penasihat utama perumusan tata kelola nilai"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Target Kematangan Pengawas dan Administrator</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Target tingkat kematangan minimal bagi Pejabat Pengawas/Administrator adalah: <strong>Penerapan seluruh Sikap Dasar dan Perilaku Efektif (implementing the values)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat mengenai target kematangan level manajemen operasional. Pejabat Pengawas dan Administrator memimpin langsung tim kerja lapangan, sehingga dituntut telah mengimplementasikan seluruh sikap dasar dan perilaku efektif secara utuh (*implementing the values*) agar menjadi teladan bagi bawahannya. Tabel modul menetapkan penerapan seluruh sikap dasar dan perilaku efektif. Oleh sebab itu, opsi B adalah target yang benar.</p><p>Jawaban tepat: <strong>B. Penerapan seluruh sikap dasar atau implementing values</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan target minimal bagi mahasiswa STAN dan CPNS baru.</li><li><strong>C</strong>: Merupakan target minimal bagi staf pelaksana masa kerja 1 tahun atau lebih.</li><li><strong>D</strong>: Merupakan target minimal bagi Pejabat Pimpinan Tinggi instansi.</li><li><strong>E</strong>: Merupakan peran strategis penasihat kepegawaian tingkat pimpinan tinggi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 45) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pejabat Pengawas/ • Administrator • Penerapan seluruh • Sikap Dasar dan • Perilaku Efektif • (implementing the • values)</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Salah satu strategi eksternalisasi bagi Pejabat Pengawas dan Administrator di lingkungan kerjanya adalah...", "options": ["Menjadi narasumber sosialisasi nilai ke kementerian lain", "Memasukkan klausul nilai budaya dalam naskah kerja sama dinas", "Menjadi role model budaya bagi unit kerja terkecilnya", "Mengikuti kompetisi karya desain grafis sosialisasi publik", "Menyusun modul pelatihan penguatan budaya kerja aparatur"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Eksternalisasi Pengawas dan Administrator</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Strategi eksternalisasi bagi Pejabat Pengawas/Administrator meliputi: <strong>Implementasi budaya dan menjadi role model bagi unit terkecilnya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat mengenai peran keteladanan pimpinan tingkat pengawas dan administrator. Keteladanan nyata seorang atasan langsung dalam melayani, bersikap adil, dan berintegritas memiliki daya tular terkuat dalam membentuk budaya di seksi atau subbagian terkecil yang dipimpinnya. Penjelasan tabel modul menetapkan peran menjadi role model bagi unit terkecilnya. Maka dari itu, opsi C adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>C. Menjadi role model budaya bagi unit kerja terkecilnya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan strategi eksternalisasi bagi Pejabat Pimpinan Tinggi.</li><li><strong>B</strong>: Merupakan strategi eksternalisasi klausul kerja sama bagi Pimpinan Tinggi.</li><li><strong>D</strong>: Merupakan tugas penugasan kreatif bagi mahasiswa STAN dan pelaksana pemula.</li><li><strong>E</strong>: Merupakan tugas unit perancang pelatihan pada Badan Pendidikan dan Pelatihan Keuangan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 45) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Implementasi • budaya dan • menjadi role • model bagi unit • terkecilnya</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Bentuk eksternalisasi budaya yang dapat diwujudkan oleh pegawai pelaksana di lingkungan masyarakat adalah...", "options": ["Menandatangani komitmen integritas bersama aparat penegak hukum", "Merumuskan regulasi pedoman teknis pengelolaan anggaran dinas", "Mengesahkan jadwal piket pelayanan publik terpadu kantor cabang", "Menerapkan budaya kerja yang memberi manfaat bagi masyarakat", "Menyelenggarakan forum dengar pendapat umum bersama asosiasi"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Eksternalisasi Pegawai Pelaksana</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Strategi eksternalisasi bagi pelaksana mencakup: <strong>Mendukung pelaksanaan penerapan budaya Kemenkeu yang memberi manfaat bagi masyarakat/instansi lain</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat tentang dampak positif budaya kerja terhadap publik. Nilai integritas, pelayanan prima, dan profesionalitas yang dipraktikkan oleh para pelaksana di loket layanan dan interaksi sosial memberikan manfaat nyata yang dapat dirasakan langsung oleh masyarakat luas. Tabel strategi modul merinci penerapan budaya yang memberi manfaat bagi masyarakat. Oleh karena itu, opsi D adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>D. Menerapkan budaya kerja yang memberi manfaat bagi masyarakat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan komitmen formal tingkat pimpinan satker dengan instansi penegak hukum.</li><li><strong>B</strong>: Merupakan perumusan regulasi teknis yang menjadi wewenang direktorat jenderal perencana.</li><li><strong>C</strong>: Merupakan penetapan jadwal operasional oleh pejabat pengawas seksi pelayanan.</li><li><strong>E</strong>: Merupakan forum konsultasi publik yang difasilitasi oleh pimpinan tinggi kementerian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 45) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>budaya • Kemenkeu yang • memberi • manfaat bagi • masyarakat/</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Materi budaya kementerian diinstitusikan bagi Pejabat Pengawas dan Administrator melalui...", "options": ["Ujian kompetensi kenaikan jenjang pangkat berkala", "Forum konsultasi publik rancangan undang-undang", "Program pertukaran pegawai antarlembaga swasta", "Penyusunan laporan pertanggungjawaban hibah luar", "Memasukkan ke dalam kurikulum diklat kepemimpinan atau soft skill pegawai"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Institusionalisasi Pengawas Melalui Diklat</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Salah satu bentuk institusionalisasi bagi Pengawas dan Administrator adalah: <strong>Memasukkan ke dalam kurikulum diklat kepemimpinan atau soft skill pegawai</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat mengenai pelembagaan budaya lewat kurikulum kepemimpinan. Pendidikan dan pelatihan kepemimpinan pengawas dan administrator menjadi wahana strategis untuk menanamkan kompetensi manajerial berbasis nilai budaya kementerian. Kurikulum kepemimpinan mengintegrasikan penguatan budaya dan soft skill aparatur. Dengan demikian, opsi E adalah jalur institusionalisasi yang tepat.</p><p>Jawaban tepat: <strong>E. Memasukkan ke dalam kurikulum diklat kepemimpinan atau soft skill pegawai</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Uji kompetensi pangkat mengukur kemampuan teknis jabatan fungsional.</li><li><strong>B</strong>: Konsultasi publik merupakan mekanisme penyusunan peraturan perundang-undangan.</li><li><strong>C</strong>: Pertukaran pegawai merupakan program pengayaan wawasan industri.</li><li><strong>D</strong>: Laporan hibah luar negeri merupakan akuntabilitas pembiayaan internasional.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 46) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Memasukkan dalam • materi diklat • kepemimpinan atau • soft skill pegawai</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Target tingkat kematangan minimal bagi Pejabat Pimpinan Tinggi di Kementerian Keuangan adalah...", "options": ["Memiliki dan mengembangkan nilai atau owning the values", "Penerapan seluruh sikap dasar atau implementing the values", "Paham pentingnya dan penerapan sebagian sikap dasar nilai", "Mengetahui dan sadar pentingnya nilai atau aware the values", "Menjadi tim penyusun instrumen evaluasi kepatuhan tata kelola"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Target Kematangan Pejabat Pimpinan Tinggi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Target tingkat kematangan minimal bagi Pejabat Pimpinan Tinggi adalah: <strong>Memiliki, mengajak orang lain untuk menerapkan, serta mengembangkan Sikap Dasar dan Perilaku Efektif (owning the values)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat mengenai puncak kematangan kepemimpinan nilai (*owning the values*). Pimpinan tinggi tidak hanya mengamalkan nilai secara pribadi, melainkan menginternalisasikannya hingga memiliki rasa kepemilikan penuh (*owning*), aktif mengajak seluruh pegawai, serta memperkaya sistem pengembangan budaya organisasi. Ketentuan modul merinci *owning the values*. Oleh sebab itu, opsi A adalah target yang tepat.</p><p>Jawaban tepat: <strong>A. Memiliki dan mengembangkan nilai atau owning the values</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan target minimal bagi Pejabat Pengawas dan Administrator (*implementing*).</li><li><strong>C</strong>: Merupakan target minimal bagi Pelaksana dengan masa kerja 1 tahun atau lebih (*understand*).</li><li><strong>D</strong>: Merupakan target minimal bagi Mahasiswa STAN dan CPNS pemula (*aware*).</li><li><strong>E</strong>: Merupakan fungsi operasional tim teknis pengembang instrumen kepatuhan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 46) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pejabat Pimpinan • Tinggi/ Pejabat • Memiliki, mengajak • orang lain untuk • menerapkan, serta • mengembangkan • Sikap Dasar dan • Perilaku Efektif • (owning the values)</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Salah satu strategi eksternalisasi bagi Pejabat Pimpinan Tinggi dalam budaya kementerian adalah...", "options": ["Menjadi role model bagi unit kerja terkecil yang dipimpinnya", "Menjadi role model bagi unit kerjanya dan instansi lain", "Mengikuti tugas penugasan kreatif video sosialisasi budaya", "Menyusun laporan presensi kehadiran kerja pegawai operasional", "Melakukan verifikasi berkas permohonan belanja satuan kerja"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Eksternalisasi Pimpinan Tinggi: Role Model</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Strategi eksternalisasi Pejabat Pimpinan Tinggi meliputi: <strong>Menjadi role model bagi unitnya dan instansi lain</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat tentang keteladanan pimpinan tinggi di panggung nasional. Integritas dan kepemimpinan beretika dari pimpinan tinggi Kemenkeu diharapkan menjadi mercusuar percontohan bukan hanya bagi unit internal kementerian, melainkan juga menginspirasi kementerian dan lembaga negara lainnya. Matriks strategi modul merumuskan role model bagi unitnya dan instansi lain. Maka dari itu, opsi B adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>B. Menjadi role model bagi unit kerjanya dan instansi lain</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan strategi eksternalisasi bagi Pejabat Pengawas dan Administrator.</li><li><strong>C</strong>: Merupakan penugasan eksternalisasi bagi mahasiswa STAN dan CPNS baru.</li><li><strong>D</strong>: Merupakan tugas administrasi presensi pada subbagian kepegawaian kantor.</li><li><strong>E</strong>: Merupakan tugas teknis verifikasi keuangan pada pejabat penguji tagihan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 46) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Menjadi role model • bagi unitnya dan • instansi lain</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Wujud eksternalisasi Pejabat Pimpinan Tinggi melalui pembuatan kebijakan di unit kerjanya adalah...", "options": ["Menetapkan standar pedoman operasional pengadaan barang dinas", "Merumuskan indikator keberhasilan audit kepatuhan internal", "Membuat keputusan sebagai wujud nyata budaya kementerian", "Mengesahkan perjanjian kinerja tahunan satuan kerja vertikal", "Menyetujui peta jalan transformasi digital kearsipan kementerian"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Eksternalisasi Pimpinan Tinggi Melalui Keputusan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Strategi eksternalisasi Pimpinan Tinggi mencakup: <strong>Membuat keputusan/kebijakan yang merupakan perwujudan dari budaya Kemenkeu di unitnya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat mengenai keberpihakan kebijakan terhadap nilai budaya. Kebijakan publik dan regulasi internal yang ditandatangani oleh pimpinan tinggi harus memancarkan roh keadilan, integritas, dan pelayanan publik yang mencerminkan budaya luhur Kemenkeu. Rincian tabel modul menegaskan pembuatan keputusan/kebijakan sebagai perwujudan budaya kementerian. Oleh sebab itu, opsi C adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>C. Membuat keputusan sebagai wujud nyata budaya kementerian</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan penetapan pedoman teknis operasional pengadaan sarana kantor.</li><li><strong>B</strong>: Merupakan penyusunan instrumen pengawasan oleh pejabat fungsional auditor.</li><li><strong>D</strong>: Merupakan penetapan target perjanjian kinerja manajerial tahunan.</li><li><strong>E</strong>: Merupakan pengesahan cetak biru sistem informasi kearsipan instansi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 46) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Membuat • keputusan/kebijak • an yang • merupakan • perwujudan dari • budaya Kemenkeu</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Peran pimpinan tinggi dalam memperluas sosialisasi budaya kementerian tercermin melalui...", "options": ["Memimpin sidang pleno pembahasan rencana strategis kementerian", "Mengoordinasikan komite manajemen risiko fiskal tingkat nasional", "Mengarahkan tim penyusunan dokumen evaluasi reformasi birokrasi", "Menjadi narasumber yang memasukkan budaya ke dalam substansi sosialisasi", "Mengawasi pemeliharaan sarana prasarana gedung perkantoran dinas"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Peran Narasumber Pimpinan Tinggi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Peran eksternalisasi Pimpinan Tinggi meliputi: <strong>Menjadi narasumber yang memasukkan budaya ke dalam substansi sosialisasi baik ke dalam maupun ke luar</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat mengenai diseminasi nilai oleh pucuk pimpinan. Ketika pimpinan tinggi tampil sebagai pembicara dalam berbagai seminar, lokakarya, atau sosialisasi publik, mereka menyelipkan pesan-pesan penguatan budaya sehingga pemangku kepentingan memahami integritas kementerian. Peran pimpinan tinggi mencakup diseminasi nilai budaya secara internal maupun eksternal. Oleh karena itu, opsi D adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>D. Menjadi narasumber yang memasukkan budaya ke dalam substansi sosialisasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan fungsi kepemimpinan dalam perumusan arah rencana strategis.</li><li><strong>B</strong>: Merupakan peran manajerial pengendalian risiko makroekonomi fiskal.</li><li><strong>C</strong>: Merupakan fungsi pengarahan reformasi tata laksana kelembagaan.</li><li><strong>E</strong>: Merupakan tugas pengelolaan fasilitas fisik perkantoran pada bagian umum.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 46) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Menjadi • narasumber yang • memasukkan • budaya dalam • materi sosialisasi • baik ke dalam • maupun ke luar</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Strategi eksternalisasi Pejabat Pimpinan Tinggi yang mengikat mitra kerja dilakukan dengan...", "options": ["Menyelenggarakan forum temu pemangku kepentingan tingkat nasional", "Menerbitkan piagam apresiasi kepatuhan kemitraan bagi pihak swasta", "Menetapkan standar minimum kualifikasi perbankan mitra pembayaran", "Melakukan audit forensik berkala terhadap rekanan pengadaan barang", "Memasukkan klausul nilai budaya dalam naskah kerja sama pihak luar"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Klausul Nilai Budaya dengan Mitra Kerja</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Strategi eksternalisasi Pimpinan Tinggi mencakup: <strong>Memasukkan ketentuan yang dilandasi core values ASN dan nilai-nilai Kementerian Keuangan ke dalam klausul kerja sama dengan pihak eksternal</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat mengenai legalitas pengikatan mitra terhadap standar etika kementerian. Mencantumkan klausul budaya dalam kontrak atau perjanjian kerja sama memaksa vendor, perbankan, dan mitra internasional mematuhi asas transparansi, antisuap, dan integritas saat bertransaksi dengan Kemenkeu. Tabel modul menetapkan pencantuman klausul nilai ke dalam naskah kerja sama pihak eksternal. Dengan demikian, opsi E adalah langkah yang benar.</p><p>Jawaban tepat: <strong>E. Memasukkan klausul nilai budaya dalam naskah kerja sama pihak luar</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Forum temu pemangku kepentingan merupakan kegiatan kehumasan umum.</li><li><strong>B</strong>: Piagam kepatuhan merupakan bentuk apresiasi non-hukum bagi mitra.</li><li><strong>C</strong>: Standar perbankan merupakan kriteria seleksi teknis rekening pemerintah.</li><li><strong>D</strong>: Audit forensik merupakan langkah penegakan hukum terhadap indikasi fraud.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 46) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Memasukkan • ketentuan yang • dilandasi core • values ASN dan • nilai-nilai • Kementerian • Keuangan ke • dalam klausul kerja • sama dengan • pihak eksternal</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Program pelatihan yang disiapkan untuk membekali agen perubahan budaya di kementerian adalah...", "options": ["Training of Trainers bagi Duta Transformasi", "Kursus Singkat Akuntansi Keuangan Pemerintah", "Pelatihan Teknis Tata Naskah Dinas Kedinasan", "Lokakarya Penanganan Perkara Administrasi", "Bimbingan Teknis Pengadaan Barang dan Jasa"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pelatihan Agen Perubahan Budaya</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Internalisasi bagi Duta Transformasi dilakukan melalui kegiatan <strong>Training of Trainers terkait Sikap Dasar/Perilaku Efektif bagi Duta Transformasi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat mengenai program pembekalan agen perubahan. Pelatihan *Training of Trainers* (ToT) memberikan bekal metodologi komunikasi, fasilitasi, dan pemahaman mendalam tentang nilai agar para Duta Transformasi mampu menginternalisasikan budaya kepada rekan di unitnya. Tabel strategi resmi merinci ToT bagi Duta Transformasi. Oleh sebab itu, opsi A adalah nama pelatihan yang tepat.</p><p>Jawaban tepat: <strong>A. Training of Trainers bagi Duta Transformasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kursus akuntansi berfokus pada penyusunan neraca dan laporan keuangan.</li><li><strong>C</strong>: Pelatihan tata naskah berfokus pada standarisasi penulisan surat dinas.</li><li><strong>D</strong>: Lokakarya penanganan perkara berfokus pada advokasi sengketa hukum dinas.</li><li><strong>E</strong>: Bimtek pengadaan berfokus pada sertifikasi kompetensi pengadaan barang jasa.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 46) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Training of Trainers • Duta Transformasi</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Secara konseptual, nilai dirumuskan sebagai keyakinan mengenai hal yang dipandang baik atau buruk yang...", "options": ["Menjadi pedoman teknis penyusunan tata naskah dinas kedinasan", "Memengaruhi perilaku individu dalam melakukan setiap tindakan", "Mengatur tata cara teknis penyusunan laporan keuangan kementerian", "Menentukan besaran alokasi pagu belanja operasional satuan kerja", "Menstandarkan format pelaporan kinerja perbendaharaan negara"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengertian Nilai Bab Penutup</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Nilai</strong> adalah sebuah <strong>keyakinan tentang apa yang dianggap baik atau buruk yang memengaruhi perilaku orang dalam melakukan tindakan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat mengenai esensi definisi nilai dalam penutup modul. Nilai berkedudukan sebagai kompas moral internal yang menyaring pilihan dan mengarahkan bagaimana seseorang bersikap, bertutur kata, dan mengambil keputusan dalam kehidupannya. Rumusan penutup modul menegaskan nilai memengaruhi perilaku orang dalam melakukan tindakan. Oleh karena itu, opsi B adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>B. Memengaruhi perilaku individu dalam melakukan setiap tindakan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan fungsi pedoman tata persuratan administrasi perkantoran.</li><li><strong>C</strong>: Merupakan standar akuntansi keuangan pemerintah dalam pelaporan buku.</li><li><strong>D</strong>: Merupakan mekanisme penganggaran pagu belanja pada unit perencana.</li><li><strong>E</strong>: Merupakan format baku sistem pelaporan perbendaharaan dan kas negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 47) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>keyakinan tentang apa yang dianggap baik atau buruk yang • memengaruhi perilaku orang dalam melakukan tindakan</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Nilai-nilai organisasi merupakan keyakinan yang dianut seluruh pemangku kepentingan untuk...", "options": ["Mempercepat penyelesaian urusan administrasi kepegawaian kantor", "Menjaga kesinambungan realisasi belanja operasional tahun berjalan", "Berpikir, bertindak, dan berperilaku menuju tujuan organisasi", "Menghasilkan rumusan kebijakan fiskal jangka menengah instansi", "Menstandarkan mekanisme pelaporan akuntabilitas kinerja satker"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengertian Nilai-Nilai Organisasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Nilai-Nilai Organisasi</strong> merupakan keyakinan dan kepercayaan yang diyakini untuk <strong>berpikir, bertindak, dan berperilaku untuk menuju sebuah tujuan organisasi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat tentang fungsi pemersatu nilai organisasi. Nilai organisasi menyatukan energi dan pola kerja seluruh pegawai dari berbagai latar belakang agar bergerak selaras mencapai visi bersama instansi. Penjelasan penutup modul merinci berpikir, bertindak, dan berperilaku menuju tujuan organisasi. Dengan demikian, opsi C adalah rumusan yang benar.</p><p>Jawaban tepat: <strong>C. Berpikir, bertindak, dan berperilaku menuju tujuan organisasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan efisiensi layanan administratif pada subbagian kepegawaian.</li><li><strong>B</strong>: Merupakan penyerapan anggaran belanja rutin pada bagian keuangan.</li><li><strong>D</strong>: Merupakan produk kajian fiskal makro pada badan perumus kebijakan.</li><li><strong>E</strong>: Merupakan standarisasi format laporan kinerja instansi pemerintah (LAKIP).</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 47) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Nilai-Nilai • Organisasi merupakan keyakinan dan kepercayaan yang diyakini • untuk berpikir, • bertindak, dan berperilaku untuk menuju sebuah tujuan organisasi</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Faktor yang menjadi prasyarat bagi organisasi dalam meningkatkan kinerjanya melalui budaya organisasi adalah...", "options": ["Optimalisasi pemanfaatan alokasi anggaran belanja operasional", "Modernisasi sarana dan prasarana teknologi informasi perkantoran", "Perluasan cakupan unit pelayanan perwakilan di daerah kepulauan", "Kohesi antarindividu, kesatuan kelompok, dan kesatuan organisasi", "Standardisasi seluruh alur korespondensi naskah dinas kedinasan"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Prasyarat Peningkatan Kinerja Organisasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Kohesi antar individu, kesatuan kelompok dan kesatuan organisasi merupakan prasyarat sebuah organisasi untuk meningkatkan kinerjanya, melalui budaya organisasi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat sesuai kaidah penguatan kinerja kelembagaan. Soliditas hubungan antarpegawai dan kekompakan antartim menciptakan sinergi tanpa gesekan yang memacu produktivitas organisasi ke tingkat optimal. Penutup modul menegaskan kohesi antarindividu dan kesatuan organisasi sebagai prasyarat peningkatan kinerja. Oleh sebab itu, opsi D adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>D. Kohesi antarindividu, kesatuan kelompok, dan kesatuan organisasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan faktor pendukung pembiayaan anggaran dalam eksekusi kegiatan.</li><li><strong>B</strong>: Merupakan sarana penunjang peranti lunak dan keras otomasi kantor.</li><li><strong>C</strong>: Merupakan perluasan jaringan fisik kantor pelayanan di daerah.</li><li><strong>E</strong>: Merupakan penataan tertib administrasi surat-menyurat kedinasan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 47) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>kohesi antar individu, kesatuan kelompok dan kesatuan organisasi merupakan • prasyarat sebuah organisasi untuk meningkatkan kinerjanya</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Penetapan Nilai-nilai Dasar ASN BerAKHLAK diposisikan sebagai tonggak baru bagi aparatur agar...", "options": ["Memiliki pedoman teknis pengelolaan keuangan instansi pemerintah", "Mengikuti pola penjenjangan kepangkatan aparatur secara seragam", "Memperoleh standar fasilitas sarana kerja kedinasan yang memadai", "Mempercepat harmonisasi regulasi kelembagaan aparatur nasional", "Memiliki langkah yang sama membangun budaya aparatur negara"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Arti Penetapan Core Values BerAKHLAK</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Penetapan Nilai-nilai Dasar ASN BerAKHLAK merupakan tonggak baru bagi seluruh ASN agar memiliki langkah yang sama dalam membangun budaya ASN</strong> sesuai harapan masyarakat.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat tentang signifikansi historis peluncuran Core Values BerAKHLAK. Penyeragaman nilai dasar menghapus fragmentasi budaya antar-instansi pemerintah, menyatukan orientasi pelayanan, serta menyelaraskan etos kerja seluruh ASN di seluruh pelosok Indonesia. Penutup modul menegaskan tujuannya agar memiliki langkah yang sama membangun budaya ASN. Maka dari itu, opsi E adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>E. Memiliki langkah yang sama membangun budaya aparatur negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan materi regulasi hukum perbendaharaan pada UU Keuangan Negara.</li><li><strong>B</strong>: Merupakan pola karier kepangkatan pada manajemen PNS Badan Kepegawaian Negara.</li><li><strong>C</strong>: Merupakan standarisasi pengadaan sarana kerja fisik kantor pemerintah.</li><li><strong>D</strong>: Merupakan penataan kelembagaan birokrasi pada kementerian pendayagunaan aparatur.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 47) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Penetapan Nilai-nilai Dasar ASN BerAKHLAK merupakan tonggak baru bagi seluruh • ASN agar memiliki langkah yang sama dalam membangun budaya ASN</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Konsep dasar yang mempertemukan Nilai Dasar ASN dengan Nilai Kementerian Keuangan adalah agar pegawai mampu...", "options": ["Mengelola pribadi selaras tujuan pelaksanaan tugasnya", "Mengembangkan jejaring kemitraan dengan sektor industri", "Mengoptimalkan pemanfaatan teknologi informasi perbankan", "Mempercepat penyelesaian proses audit kinerja tahunan", "Menyusun standarisasi tata kelola anggaran berbasis risiko"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Titik Temu Nilai Dasar ASN dan Nilai Kemenkeu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Nilai Dasar ASN dan Nilai Kementerian Keuangan saling mendukung karena <strong>memiliki konsep dasar yang sama yaitu agar ASN atau pegawai dapat mengelola pribadi yang selaras dengan tujuan dari pelaksanaan tugasnya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat tentang titik temu filosofis kedua rumpun nilai. Baik BerAKHLAK maupun Nilai Kemenkeu bertumpu pada kemampuan aparatur mengendalikan diri, menjaga integritas, dan menyelaraskan motif pribadi dengan keluhuran tujuan tugas kedinasan negara. Rincian teks penutup modul secara eksplisit menyatakan mengelola pribadi yang selaras dengan tujuan tugas. Oleh sebab itu, opsi A adalah rumusan yang benar.</p><p>Jawaban tepat: <strong>A. Mengelola pribadi selaras tujuan pelaksanaan tugasnya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan inisiatif kemitraan eksternal pada sektor komersial keuangan.</li><li><strong>C</strong>: Merupakan pemanfaatan teknologi digital pada proses transaksi perbankan.</li><li><strong>D</strong>: Merupakan percepatan siklus evaluasi pengawasan internal kementerian.</li><li><strong>E</strong>: Merupakan penyusunan manajemen risiko fiskal pada penganggaran belanja.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 47) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>memiliki konsep dasar yang sama yaitu agar ASN atau pegawai dapat • mengelola • pribadi yang selaras dengan tujuan dari pelaksanaan tugasnya</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Makna nilai Integritas menuntut komitmen etis bagi seluruh pegawai untuk...", "options": ["Bekerja dengan tuntas dan akurat berdasarkan kompetensi terbaik", "Berpikir, berkata, bertindak terpuji dan memegang teguh kode etik", "Membangun hubungan kerja sama internal dan kemitraan yang produktif", "Memberikan pelayanan yang memenuhi kepuasan pemangku kepentingan", "Melakukan upaya perbaikan di segala bidang untuk memberi terbaik"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Ringkasan Nilai Integritas Bab Penutup</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Integritas</strong> berarti seluruh pegawai harus <strong>berpikir, berkata, berperilaku, dan bertindak dengan baik dan benar serta selalu memegang teguh Kode Etik dan prinsip-prinsip moral</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat mengenai ringkasan makna Integritas di bab penutup. Integritas mencakup kejujuran paripurna dalam pikiran, perkataan, dan tindakan serta ketaatan mutlak terhadap kode etik kedinasan dan prinsip moral. Rumusan penutup modul menegaskan berpikir, berkata, berperilaku, dan bertindak baik dan benar serta memegang kode etik. Dengan demikian, opsi B adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>B. Berpikir, berkata, bertindak terpuji dan memegang teguh kode etik</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan ringkasan makna nilai dasar Profesionalisme di bab penutup.</li><li><strong>C</strong>: Merupakan ringkasan makna nilai dasar Sinergi di bab penutup.</li><li><strong>D</strong>: Merupakan ringkasan makna nilai dasar Pelayanan di bab penutup.</li><li><strong>E</strong>: Merupakan ringkasan makna nilai dasar Kesempurnaan di bab penutup.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 47) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Integritas, yang berarti seluruh pegawai harus berpikir, berkata, berperilaku, dan • bertindak dengan baik dan benar serta selalu memegang teguh Kode Etik</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Makna nilai Sinergi dalam prinsip kerja sama Kementerian Keuangan menekankan komitmen untuk...", "options": ["Berpikir, berkata, bertindak jujur serta menjunjung tinggi kode etik", "Bekerja dengan tuntas dan akurat atas dasar komitmen tanggung jawab", "Membangun kerja sama internal produktif serta kemitraan harmonis", "Memberikan pelayanan prima yang memenuhi kepuasan pemangku tugas", "Melakukan upaya perbaikan tanpa henti untuk menjadi yang terbaik"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Ringkasan Nilai Sinergi Bab Penutup</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Sinergi</strong> berarti seluruh pegawai berkomitmen untuk <strong>membangun dan memastikan hubungan kerja sama internal yang produktif serta kemitraan yang harmonis dengan para pemangku kepentingan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat mengenai ringkasan pilar Sinergi. Kerja sama produktif antarseksi internal dan hubungan harmonis dengan mitra pemangku kepentingan menjadi kunci utama terciptanya hasil kerja yang bernilai tambah tinggi bagi bangsa. Penutup modul merumuskan pembangunan kerja sama internal produktif dan kemitraan harmonis. Oleh karena itu, opsi C adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>C. Membangun kerja sama internal produktif serta kemitraan harmonis</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan ringkasan makna nilai Integritas dalam bab penutup modul.</li><li><strong>B</strong>: Merupakan ringkasan makna nilai Profesionalisme dalam bab penutup modul.</li><li><strong>D</strong>: Merupakan ringkasan makna nilai Pelayanan dalam bab penutup modul.</li><li><strong>E</strong>: Merupakan ringkasan makna nilai Kesempurnaan dalam bab penutup modul.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 47) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Sinergi, yang berarti seluruh pegawai harus berkomitmen untuk membangun dan • memastikan hubungan kerja sama internal yang produktif serta kemitraan yang • harmonis dengan para pemangku kepentingan</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Makna nilai Pelayanan di lingkungan kementerian diwujudkan dengan memberikan layanan yang...", "options": ["Mengutamakan kejujuran dan ketaatan penuh pada kode etik kedinasan", "Berorientasi pada ketuntasan kerja dan keandalan kompetensi teknis", "Mengedepankan koordinasi harmonis serta kemitraan produktif", "Memenuhi kepuasan pemangku kepentingan sepenuh hati", "Mendorong terciptanya inovasi dan perbaikan tiada henti di semua lini"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Ringkasan Nilai Pelayanan Bab Penutup</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Pelayanan</strong> berarti seluruh pegawai harus <strong>memberikan pelayanan untuk memenuhi kepuasan para pemangku kepentingan dan dilaksanakan dengan sepenuh hati, transparan, cepat, akurat, dan aman</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat mengenai hakikat nilai Pelayanan di bab penutup modul. Pelayanan publik diselenggarakan dengan meletakkan kepuasan pemangku kepentingan sebagai tolok ukur utama, yang dieksekusi dengan ketulusan hati, keterbukaan, dan akurasi tinggi. Teks resmi modul menyatakan memberikan pelayanan untuk memenuhi kepuasan para pemangku kepentingan. Oleh sebab itu, opsi D adalah rumusan yang benar.</p><p>Jawaban tepat: <strong>D. Memenuhi kepuasan pemangku kepentingan sepenuh hati</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan ringkasan makna nilai Integritas dalam bab penutup modul.</li><li><strong>B</strong>: Merupakan ringkasan makna nilai Profesionalisme dalam bab penutup modul.</li><li><strong>C</strong>: Merupakan ringkasan makna nilai Sinergi dalam bab penutup modul.</li><li><strong>E</strong>: Merupakan ringkasan makna nilai Kesempurnaan dalam bab penutup modul.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 48) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pelayanan, yang berarti seluruh pegawai harus memberikan pelayanan untuk • memenuhi kepuasan para pemangku kepentingan</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Pelaksanaan nilai Pelayanan di lingkungan Kementerian Keuangan wajib diselenggarakan secara...", "options": ["Terpadu, berkesinambungan, responsif, terstandar, dan berintegritas", "Konsisten, adaptif, profesional, akuntabel, dan proporsional", "Kolaboratif, sistematis, inklusif, cermat, dan berorientasi mutu", "Humanis, solutif, produktif, terbuka, dan komprehensif", "Sepenuh hati, transparan, cepat, akurat, dan aman"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Prinsip Pelaksanaan Pelayanan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pelayanan di Kementerian Keuangan dilaksanakan dengan prinsip: <strong>sepenuh hati, transparan, cepat, akurat, dan aman</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat sesuai rumusan lima prinsip pelayanan di halaman 48 modul. Kelima prinsip ini menjamin layanan publik diberikan dengan ketulusan (sepenuh hati), kejelasan alur (transparan), efisiensi waktu (cepat), ketepatan hasil (akurat), dan kepastian hukum serta data (aman). Penjelasan penutup merinci kelima prinsip tersebut secara utuh. Dengan demikian, opsi E adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>E. Sepenuh hati, transparan, cepat, akurat, dan aman</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan kombinasi karakteristik tata kelola administrasi pelayanan umum.</li><li><strong>B</strong>: Merupakan prinsip-prinsip umum akuntabilitas kinerja birokrasi pemerintahan.</li><li><strong>C</strong>: Merupakan deskriptor mutu manajemen mutu terpadu pada sektor publik.</li><li><strong>D</strong>: Merupakan kata sifat normatif pelayanan yang bukan rumusan resmi modul.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 48) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>sepenuh hati, transparan, cepat, akurat, dan aman;</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Makna nilai Kesempurnaan menuntut komitmen aparatur kementerian untuk senantiasa...", "options": ["Melakukan upaya perbaikan menjadi dan memberi terbaik", "Selalu berpegang teguh pada kejujuran dan norma kode etik dinas", "Bekerja dengan tuntas dan akurat berlandaskan keahlian yang teruji", "Membangun hubungan kemitraan yang produktif dan harmonis bersama", "Memberikan kepuasan pelayanan publik dengan ketulusan hati penuh"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Ringkasan Nilai Kesempurnaan Bab Penutup</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Kesempurnaan</strong> berarti seluruh pegawai harus senantiasa <strong>melakukan upaya perbaikan di segala bidang untuk menjadi dan memberikan yang terbaik</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat mengenai ringkasan nilai Kesempurnaan dalam bab penutup. Nilai Kesempurnaan melarang aparatur merasa cepat puas dengan pencapaian yang ada, melainkan terus-menerus mencari celah penyempurnaan proses kerja agar kementerian selalu berada pada standar kinerja kelas dunia. Teks modul merinci perbaikan di segala bidang untuk menjadi dan memberikan yang terbaik. Oleh sebab itu, opsi A adalah rumusan yang benar.</p><p>Jawaban tepat: <strong>A. Melakukan upaya perbaikan menjadi dan memberi terbaik</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan ringkasan makna nilai Integritas dalam bab penutup modul.</li><li><strong>C</strong>: Merupakan ringkasan makna nilai Profesionalisme dalam bab penutup modul.</li><li><strong>D</strong>: Merupakan ringkasan makna nilai Sinergi dalam bab penutup modul.</li><li><strong>E</strong>: Merupakan ringkasan makna nilai Pelayanan dalam bab penutup modul.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 48) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Kesempurnaan, yang berarti seluruh pegawai harus senantiasa melakukan upaya • perbaikan di segala bidang untuk menjadi dan memberikan yang terbaik.</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Tujuan utama dari dilakukannya upaya perbaikan di segala bidang pada nilai Kesempurnaan adalah untuk...", "options": ["Menjaga keluhuran citra martabat serta kehormatan korps aparatur", "Menjadi dan memberikan yang terbaik bagi organisasi", "Menghasilkan keluaran kerja yang akurat serta dapat diandalkan", "Mewujudkan keselarasan hubungan kerja lintas satuan organisasi", "Memenuhi harapan dan kepuasan masyarakat penerima layanan publik"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuan Upaya Perbaikan Nilai Kesempurnaan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Upaya perbaikan di segala bidang dalam nilai Kesempurnaan ditujukan secara mendasar untuk <strong>menjadi dan memberikan yang terbaik</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat mengenai cita-cita luhur nilai Kesempurnaan. Seluruh inovasi, pembaruan aplikasi, penyempurnaan SOP, dan peningkatan kompetensi bermuara pada satu tujuan mulia, yaitu agar institusi Kementerian Keuangan mampu mempersembahkan pengabdian terbaiknya bagi bangsa dan negara. Rumusan resmi modul menetapkan tujuan menjadi dan memberikan yang terbaik. Maka dari itu, opsi B adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>B. Menjadi dan memberikan yang terbaik bagi organisasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan tujuan utama pengamalan nilai dasar Integritas.</li><li><strong>C</strong>: Merupakan tujuan utama pengamalan nilai dasar Profesionalisme.</li><li><strong>D</strong>: Merupakan tujuan utama pengamalan nilai dasar Sinergi.</li><li><strong>E</strong>: Merupakan tujuan utama pengamalan nilai dasar Pelayanan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 48) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>perbaikan di segala bidang untuk menjadi dan memberikan yang terbaik.</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Orientasi utama dari pelaksanaan nilai Pelayanan oleh aparatur Kementerian Keuangan adalah...", "options": ["Menegakkan standar moral dan kejujuran dalam setiap pelaksanaan tugas", "Mengoptimalkan kompetensi keahlian demi ketuntasan pekerjaan dinas", "Memenuhi kepuasan pemangku kepentingan secara transparan", "Mempererat jalinan kerja sama dan sinergi internal yang saling menguatkan", "Mendorong terwujudnya inovasi perbaikan tata kelola secara berkelanjutan"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Fokus Kepuasan Pemangku Kepentingan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Fokus utama pemberian layanan pada nilai Pelayanan adalah <strong>memenuhi kepuasan para pemangku kepentingan</strong> secara <strong>transparan, cepat, akurat, dan aman</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat mengenai fokus nilai Pelayanan di bab penutup. Keberhasilan pelayanan aparatur tidak diukur dari persepsi internal birokrasi, melainkan dari tingkat kepuasan, kepercayaan, dan kemudahan yang dirasakan secara transparan oleh para pemangku kepentingan. Ketentuan etika menegaskan pemenuhan kepuasan pemangku kepentingan secara transparan. Oleh karena itu, opsi C adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>C. Memenuhi kepuasan pemangku kepentingan secara transparan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan orientasi utama pengamalan nilai Integritas aparatur.</li><li><strong>B</strong>: Merupakan orientasi utama pengamalan nilai Profesionalisme aparatur.</li><li><strong>D</strong>: Merupakan orientasi utama pengamalan nilai Sinergi aparatur.</li><li><strong>E</strong>: Merupakan orientasi utama pengamalan nilai Kesempurnaan aparatur.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 48) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>memenuhi kepuasan para pemangku kepentingan • transparan, cepat, akurat, dan aman;</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Sikap keterbukaan proses dan kejelasan informasi dalam nilai Pelayanan dicerminkan oleh prinsip...", "options": ["Akuntabel dan proporsional", "Responsif dan berkesinambungan", "Solutif dan berkomitmen", "Transparan dan akurat", "Kolaboratif dan terpercaya"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Prinsip Keterbukaan dan Keakuratan Pelayanan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pelayanan di Kementerian Keuangan dilaksanakan dengan prinsip <strong>transparan, cepat, akurat, dan aman</strong> guna memberikan kepuasan pemangku kepentingan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat mengenai pasangan prinsip keterbukaan dan ketelitian layanan. Prinsip transparan menjamin seluruh prosedur, persyaratan, dan jangka waktu layanan dapat diakses secara terbuka tanpa ada yang disembunyikan, sedangkan akurat memastikan keluaran layanan bebas dari kesalahan hitung atau kekeliruan data. Rumusan modul menetapkan prinsip transparan dan akurat. Dengan demikian, opsi D adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>D. Transparan dan akurat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan prinsip tata kelola umum anggaran dan bukan rincian eksplisit modul.</li><li><strong>B</strong>: Merupakan karakteristik pelayanan adaptif yang bukan frasa baku penutup.</li><li><strong>C</strong>: Merupakan sikap dasar pelayanan umum dan bukan pasangan prinsip mutu layanan.</li><li><strong>E</strong>: Merupakan karakteristik nilai sinergi dalam hubungan kerja sama antarsesama.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 48) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>transparan, cepat, akurat, dan aman;</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Pengamalan lima nilai utama Kementerian Keuangan bermuara pada komitmen untuk senantiasa...", "options": ["Memperketat pengawasan formalitas dokumen belanja operasional", "Meningkatkan efisiensi alokasi pembiayaan sarana prasarana dinas", "Memperluas cakupan regulasi teknis penatausahaan aset kementerian", "Menstandardisasi mekanisme pelaporan realisasi kinerja satker", "Memberikan pelayanan prima dan perbaikan berkelanjutan di segala bidang"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Muara Pengamalan Nilai Kementerian Keuangan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kelima <strong>Nilai-Nilai Kementerian Keuangan</strong> (Integritas, Profesionalisme, Sinergi, Pelayanan, Kesempurnaan) bermuara pada pemberian pelayanan terbaik serta upaya perbaikan di segala bidang secara berkelanjutan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat sebagai rangkuman filosofis penutup modul. Keluhuran integritas, profesionalitas, dan sinergi antarpegawai bermuara pada dua output utama: menghadirkan pelayanan prima bagi masyarakat serta tekad melakukan perbaikan tiada henti di seluruh bidang tugas demi kemajuan bangsa. Penutup modul merangkum komitmen pelayanan prima dan perbaikan berkelanjutan. Oleh sebab itu, opsi E adalah jawaban yang tepat.</p><p>Jawaban tepat: <strong>E. Memberikan pelayanan prima dan perbaikan berkelanjutan di segala bidang</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pengawasan formalitas merupakan fungsi kepatuhan administratif semata.</li><li><strong>B</strong>: Efisiensi sarana merupakan manajemen anggaran fisik operasional kantor.</li><li><strong>C</strong>: Regulasi penatausahaan aset merupakan fungsi teknis pengelolaan barang negara.</li><li><strong>D</strong>: Standarisasi pelaporan kinerja merupakan tertib administrasi akuntabilitas instansi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 48) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Nilai-Nilai Kementerian • Keuangan • perbaikan di segala bidang untuk menjadi dan memberikan yang terbaik.</strong></div></div>"}];
let current=0;const answers=new Array(questions.length).fill(null);const letters=["A","B","C","D","E"];let seconds=65*60;let submitted=false;


// Anotasi hanya dalam pembahasan. Satu set coretan untuk setiap nomor soal.
const ANNO_KEY='stangolden_kemenkeu_p04_review_annot_v1_user_<?= (int)$USER->id ?>';
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
 try{localStorage.setItem('stg_nk_p04_map_visible',show?'1':'0')}catch(e){}
}
try{if(localStorage.getItem('stg_nk_p04_map_visible')==='0')toggleQuestionMap(false)}catch(e){}
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

  if(modalTitle) modalTitle.textContent="🏆 Lihat Ranking Paket 04";
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
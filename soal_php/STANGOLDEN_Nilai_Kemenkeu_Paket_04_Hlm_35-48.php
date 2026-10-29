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
const questions=[{"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Dalam rangka memperkuat tata kelola instansi, pimpinan unit dihimbau memberdayakan UKI untuk...", "options": ["Meningkatkan efektivitas tata kelola dan manajemen risiko", "Menyusun pembukuan keuangan pribadi seluruh staf kantor", "Menghadiri acara seremonial di luar lingkungan kedinasan", "Mengurangi alokasi anggaran perjalanan dinas pengawasan", "Mengambil alih fungsi penuntutan pidana di pengadilan"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pemberdayaan Unit Kepatuhan Internal (UKI)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pimpinan unit dihimbau untuk: <strong>Memberdayakan Unit Kepatuhan Internal untuk meningkatkan efektivitas tata kelola, manajemen risiko, dan pengendalian intern</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat tentang fungsi strategis UKI sebagai lini pertahanan kedua (*second line of defense*). UKI bertugas memastikan kepatuhan prosedur, memantau pengendalian risiko, dan menjaga tata kelola unit tetap bersih. Pemberdayaan UKI ditujukan untuk ditujukan untuk efektivitas tata kelola, manajemen risiko, dan pengendalian intern. Oleh sebab itu, opsi A adalah tujuan yang benar.</p><p>Jawaban tepat: <strong>A. Meningkatkan efektivitas tata kelola dan manajemen risiko</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan urusan pribadi yang berada di luar ranah pengawasan kepatuhan dinas.</li><li><strong>C</strong>: Merupakan kegiatan seremonial luar yang bukan fokus pembinaan sistem kendali.</li><li><strong>D</strong>: Merupakan urusan anggaran perjalanan dinas yang bukan tugas inti pemberdayaan UKI.</li><li><strong>E</strong>: Merupakan kewenangan aparat penegak hukum kejaksaan di luar yurisdiksi UKI.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 35) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Memberdayakan Unit Kepatuhan Internal untuk • meningkatkan efektivitas tata kelola, manajemen risiko, dan pengendalian intern</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Sikap yang wajib dibangun oleh pimpinan unit kerja terhadap Inspektorat Jenderal dalam pengawasan intern adalah...", "options": ["Membatasi akses data dan informasi pengawasan kantor", "Membangun hubungan kemitraan yang konstruktif dan sinergis", "Menunda rekomendasi perbaikan hasil audit pengawasan", "Meminta perlindungan hukum khusus bagi aparatur terperiksa", "Menyimpan catatan hasil pemeriksaan secara rahasia pribadi"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kemitraan Konstruktif dengan Inspektorat Jenderal</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pimpinan unit dihimbau untuk: <strong>Membangun hubungan kemitraan yang konstruktif dengan Inspektorat Jenderal dalam pelaksanaan pengawasan intern</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat mengenai kolaborasi sehat antara auditee dengan auditor internal. Pengawasan tidak boleh dipandang sebagai ajang mencari kesalahan, melainkan sarana konsultatif kemitraan untuk memperbaiki kelemahan tata kelola unit. Pedoman etika menegaskan pentingnya kemitraan konstruktif bersama Itjen. Dengan demikian, opsi B adalah sikap yang diharapkan.</p><p>Jawaban tepat: <strong>B. Membangun hubungan kemitraan yang konstruktif dan sinergis</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan tindakan menghalangi audit yang melanggar standar pengawasan intern.</li><li><strong>C</strong>: Merupakan sikap pembangkangan yang merusak komitmen perbaikan tata kelola.</li><li><strong>D</strong>: Merupakan permintaan intervensi yang bertentangan dengan objektivitas hukum.</li><li><strong>E</strong>: Merupakan pengendapan berkas yang melanggar transparansi tindak lanjut hasil audit.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 35) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Membangun hubungan kemitraan yang konstruktif • dengan Inspektorat Jenderal dalam pelaksanaan pengawasan intern</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Kewajiban pimpinan unit kerja terkait penegakan aturan perilaku kepada seluruh jajaran stafnya adalah...", "options": ["Memberikan keringanan sanksi bagi staf yang berprestasi", "Menyerahkan urusan kedisiplinan kepada bagian keamanan luar", "Menginternalisasi nilai serta penegakan kode etik dan disiplin", "Menunda peneguran langsung demi menjaga keakraban pribadi", "Menghapus ketentuan jam kehadiran kerja saat akhir tahun"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Internalisasi Kode Etik dan Disiplin</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pimpinan unit dihimbau untuk: <strong>Menginternalisasi Nilai-Nilai Kementerian Keuangan dan ketentuan-ketentuan yang berhubungan dengan penegakan kode etik dan disiplin kepada seluruh pegawai di lingkungan kerjanya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat mengenai mandat pembinaan moral pimpinan satuan kerja. Sebagai teladan, pimpinan berkewajiban aktif menanamkan nilai luhur serta menegakkan disiplin kerja secara konsisten kepada seluruh bawahannya. Pimpinan unit mengemban kewajiban internalisasi nilai dan kode etik tersebut secara lugas. Oleh sebab itu, opsi C adalah jawaban yang tepat.</p><p>Jawaban tepat: <strong>C. Menginternalisasi nilai serta penegakan kode etik dan disiplin</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan tebang pilih hukum yang mencederai prinsip keadilan penegakan aturan.</li><li><strong>B</strong>: Merupakan pelepasan wewenang pembinaan yang merusak kepemimpinan dinas.</li><li><strong>D</strong>: Merupakan pembiaran kesalahan yang berpotensi memicu pelanggaran lebih berat.</li><li><strong>E</strong>: Merupakan pelanggaran tata tertib kehadiran kerja yang merusak integritas kantor.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 35) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Menginternalisasi Nilai-Nilai Kementerian Keuangan dan ketentuan-ketentuan yang berhubungan dengan • penegakan kode etik dan disiplin kepada seluruh pegawai di lingkungan kerjanya</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C4 - Analisis", "question": "Himbauan penerapan nilai dalam Surat Edaran menjadi sangat relevan terutama ditujukan untuk...", "options": ["Mempercepat proses mutasi pegawai ke kota-kota besar", "Menaikkan alokasi anggaran belanja modal satuan kerja", "Menghapus kewajiban pelaporan harta kekayaan aparatur", "Meningkatkan disiplin dan mencegah potensi pelanggaran hukum", "Mengurangi interaksi kerja dengan kementerian lembaga lain"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuan Relevansi Himbauan Surat Edaran</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Surat Edaran tersebut sangat relevan karena untuk <strong>meningkatkan disiplin dan mencegah terjadinya potensi-potensi perbuatan yang dapat mengarah pada pelanggaran disiplin dan pelanggaran hukum</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat tentang urgensi diterbitkannya surat edaran pencegahan dini. Nilai dan kode etik berfungsi memitigasi potensi penyimpangan sejak hulu sebelum berubah menjadi pelanggaran disiplin maupun tindak pidana korupsi. Sebagaimana dijelaskan di pedoman etika, tujuannya adalah menegakkan disiplin dan mencegah potensi pelanggaran. Dengan demikian, opsi D adalah tujuan yang tepat.</p><p>Jawaban tepat: <strong>D. Meningkatkan disiplin dan mencegah potensi pelanggaran hukum</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan dinamika pola karier mutasi yang diatur ketentuan manajemen talenta.</li><li><strong>B</strong>: Merupakan kebijakan anggaran belanja yang berada di luar substansi surat edaran etika.</li><li><strong>C</strong>: Merupakan kewajiban LHKPN/LHKASN yang mutlak wajib bagi seluruh aparatur negara.</li><li><strong>E</strong>: Merupakan isolasi antarlembaga yang bertentangan dengan prinsip nilai sinergi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 35) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>meningkatkan disiplin dan mencegah terjadinya potensi-potensi perbuatan yang dapat mengarah pada • pelanggaran disiplin dan pelanggaran hukum</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Penggiatan penerapan Nilai Kementerian Keuangan ditujukan untuk menjamin terpeliharanya tata tertib bagi...", "options": ["Aparatur sipil negara yang bertugas di kantor pusat saja", "Pejabat eselon yang mengelola anggaran bernilai miliaran", "Pegawai negeri sipil yang sedang diusulkan kenaikan pangkat", "Tenaga honorer dan pegawai paruh waktu di daerah", "Seluruh pegawai Kementerian Keuangan tanpa terkecuali"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sasaran Terpeliharanya Tata Tertib</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Penerapan nilai dan kode etik bertujuan <strong>menjamin terpeliharanya tata tertib dalam kelancaran pelaksanaan tugas dan fungsi seluruh pegawai Kementerian Keuangan tanpa terkecuali</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat mengenai sifat universalitas keberlakuan aturan tata tertib instansi. Tidak ada pejabat atau pegawai yang kebal dari pengawasan etika dan disiplin di lingkungan Kementerian Keuangan. Aturan disiplin menegaskan bahwa hal ini berlaku bagi seluruh pegawai tanpa ada pengecualian apapun. Oleh karena itu, opsi E adalah subjek sasaran yang benar.</p><p>Jawaban tepat: <strong>E. Seluruh pegawai Kementerian Keuangan tanpa terkecuali</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan pembatasan lokasi kantor pusat yang menafikan ribuan aparatur daerah.</li><li><strong>B</strong>: Merupakan pengkhususan pejabat anggaran yang mengabaikan pegawai non-pengelola kas.</li><li><strong>C</strong>: Merupakan pembatasan momentum promosi yang tidak mencakup aparatur lainnya.</li><li><strong>D</strong>: Merupakan tenaga pendukung yang mempersempit kewajiban umum korps kementerian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 35) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menjamin terpeliharanya tata tertib dalam kelancaran pelaksanaan tugas dan fungsi • seluruh pegawai Kementerian Keuangan tanpa terkecuali</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Terbitnya PMK Nomor 190/PMK.01/2018 tentang Kode Etik dilatarbelakangi secara sosiologis oleh adanya...", "options": ["Perubahan teknologi, nilai etika, budaya, dan perilaku publik", "Penurunan penerimaan negara dari sektor bea kepabeanan", "Pergantian seluruh perangkat lunak aplikasi kantor dinas", "Desakan dari organisasi perburuhan luar kementerian", "Pengurangan alokasi anggaran operasional pengawasan"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Latar Belakang Penerbitan PMK 190/2018</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Terbitnya PMK 190/PMK.01/2018 <strong>dilatarbelakangi oleh adanya perubahan teknologi, nilai etika, budaya, dan perilaku yang terjadi di masyarakat</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat untuk latar belakang sosiologis pembaruan regulasi kode etik. Perkembangan era digital dan keterbukaan media sosial membawa tantangan etika baru yang membutuhkan panduan perilaku yang lebih adaptif bagi aparatur. Pedoman kerja menyebutkan pergeseran teknologi, etika, budaya, dan perilaku publik sebagai pemicunya. Maka dari itu, opsi A adalah latar belakang yang benar.</p><p>Jawaban tepat: <strong>A. Perubahan teknologi, nilai etika, budaya, dan perilaku publik</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan realisasi fiskal kepabeanan yang bukan pertimbangan pembentukan kode etik.</li><li><strong>C</strong>: Merupakan pembaruan aplikasi teknis yang sekadar bagian kecil perkembangan sarana.</li><li><strong>D</strong>: Merupakan tekanan organisasi eksternal yang tidak melatarbelakangi PMK internal ini.</li><li><strong>E</strong>: Merupakan penyesuaian belanja dinas yang berada di luar ranah pertimbangan etika.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 35) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>dilatarbelakangi oleh adanya perubahan teknologi, nilai etika, budaya • dan perilaku yang terjadi di masyarakat</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Selain mencegah pelanggaran disiplin, tujuan utama penerbitan PMK Nomor 190/PMK.01/2018 adalah...", "options": ["Menjamin kenaikan pangkat otomatis seluruh pegawai berkala", "Menjaga martabat dan kehormatan PNS Kementerian Keuangan", "Membatasi kebebasan berpendapat dalam musyawarah kedinasan", "Menambah beban jam kerja lembur pegawai di kantor vertikal", "Menyederhanakan struktur organisasi eselon satu kementerian"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuan Utama Penerbitan PMK 190/2018</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Peraturan ini ditujukan untuk mencegah pelanggaran disiplin, serta <strong>menjaga martabat dan kehormatan Pegawai Negeri Sipil di lingkungan Kementerian Keuangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat tentang marwah kelembagaan yang dilindungi kode etik. Kehormatan korps aparatur pengelola keuangan negara bertumpu pada martabat moral setiap pegawainya dalam bertindak dan bergaul di masyarakat. Dalam pedoman etika, menjaga martabat dan kehormatan PNS adalah tujuan fundamental aturan ini. Dengan demikian, opsi B adalah tujuan yang tepat.</p><p>Jawaban tepat: <strong>B. Menjaga martabat dan kehormatan PNS Kementerian Keuangan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan mekanisme kenaikan pangkat reguler yang diatur regulasi merit kepegawaian.</li><li><strong>C</strong>: Merupakan pembungkaman aspirasi yang bertentangan dengan iklim musyawarah sehat.</li><li><strong>D</strong>: Merupakan penambahan jam dinas yang tidak berkaitan dengan substansi keluhuran etika.</li><li><strong>E</strong>: Merupakan restrukturisasi kelembagaan yang diatur dalam peraturan organisasi tata kerja.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 35) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>ditujukan untuk mencegah pelanggaran disiplin pegawai Kementerian Keuangan, serta • menjaga martabat dan kehormatan Pegawai Negeri Sipil di lingkungan Kementerian Keuangan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Makna nilai Integritas yang ditegaskan kembali dalam PMK Nomor 190/PMK.01/2018 adalah...", "options": ["Kepatuhan mutlak pada perintah atasan tanpa pertimbangan", "Pencapaian target penerimaan kas negara dengan segala cara", "Berpikir, berkata, berperilaku, dan bertindak baik serta benar", "Penyelesaian tugas kedinasan kantor sebelum batas jadwal", "Pemberian bantuan finansial kepada rekan kerja seprofesi"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Nilai Integritas dalam PMK 190/2018</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Integritas</strong> berarti seluruh pegawai harus <strong>berpikir, berkata, berperilaku, dan bertindak dengan baik dan benar serta selalu memegang teguh Kode Etik dan prinsip-prinsip moral</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat untuk penegasan definisi integritas pada regulasi kode etik. Integritas menyatukan kejujuran batin, ucapan lisan, dan perbuatan nyata aparatur dalam koridor moralitas yang kokoh. Pedoman etika menegaskan bahwa integritas menuntut keselarasan berpikir, berkata, berperilaku, dan bertindak baik dan benar. Oleh sebab itu, opsi C adalah definisi resmi yang tepat.</p><p>Jawaban tepat: <strong>C. Berpikir, berkata, berperilaku, dan bertindak baik serta benar</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan kepatuhan buta yang bertentangan dengan prinsip memegang teguh kebenaran.</li><li><strong>B</strong>: Merupakan penghalalan segala cara yang mencederai prinsip moralitas aparatur.</li><li><strong>D</strong>: Merupakan ketepatan waktu kerja yang masuk dalam ranah profesionalisme.</li><li><strong>E</strong>: Merupakan kepedulian sosial materiil yang berada di luar hakikat definisi integritas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 36) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Integritas, berarti seluruh pegawai harus • berpikir, berkata, berperilaku, dan bertindak dengan baik dan benar serta selalu memegang teguh Kode Etik dan prinsip-prinsip moral</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Nilai Profesionalisme dalam PMK Nomor 190/PMK.01/2018 diartikan sebagai keharusan pegawai untuk...", "options": ["Menghafal seluruh nama pejabat eselon di kantor pusat dinas", "Membatasi waktu penyelesaian tugas rutin harian kantor", "Menerima penugasan dinas ke luar kota secara terus menerus", "Bekerja tuntas dan akurat berdasar kompetensi terbaik", "Memiliki gelar akademik tertinggi dari perguruan tinggi ternama"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Nilai Profesionalisme dalam PMK 190/2018</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Profesionalisme</strong> berarti seluruh pegawai harus <strong>bekerja dengan tuntas dan akurat berdasarkan kompetensi terbaik dan penuh tanggung jawab serta komitmen yang tinggi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat untuk rumusan nilai profesionalisme dalam aturan kode etik. Profesionalitas aparatur menuntut keahlian mumpuni, ketelitian data, dan ketuntasan penyelesaian tugas hingga tuntas tanpa meninggalkan beban tertunda. Profesionalisme bermakna bekerja tuntas dan akurat berlandaskan kompetensi terbaik. Dengan demikian, opsi D adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>D. Bekerja tuntas dan akurat berdasar kompetensi terbaik</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan pengenalan struktur birokrasi yang bukan hakikat kompetensi profesional.</li><li><strong>B</strong>: Merupakan pembatasan waktu kerja yang tidak menjamin ketuntasan output tugas.</li><li><strong>C</strong>: Merupakan frekuensi dinas luar yang bukan tolok ukur keahlian profesionalisme.</li><li><strong>E</strong>: Merupakan jenjang titel ijazah yang tidak otomatis menjamin keandalan kinerja nyata.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 36) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Profesionalisme, berarti seluruh pegawai harus • bekerja dengan tuntas dan akurat berdasarkan kompetensi terbaik dan penuh tanggung jawab</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Definisi nilai Sinergi dalam PMK Nomor 190/PMK.01/2018 menekankan komitmen seluruh pegawai untuk...", "options": ["Menyeragamkan pandangan pribadi seluruh anggota organisasi", "Mengutamakan kepentingan unit kerja sendiri di atas yang lain", "Melimpahkan pekerjaan sulit kepada unit eselon satu lainnya", "Menyalurkan komunikasi kedinasan via nota dinas tertulis", "Membangun kerja sama internal produktif dan kemitraan harmonis"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Nilai Sinergi dalam PMK 190/2018</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Sinergi</strong> berarti seluruh pegawai harus berkomitmen untuk <strong>membangun dan memastikan hubungan kerja sama internal yang produktif serta kemitraan yang harmonis dengan para pemangku kepentingan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat mengenai batasan resmi nilai sinergi pada PMK kode etik. Keberhasilan kementerian menuntut keterpaduan antar-unit internal dan kemitraan erat bersama para stakeholders eksternal demi menghasilkan karya berkualitas. Pedoman etika menyatakan sinergi bertujuan membangun kerja sama internal produktif dan kemitraan harmonis. Maka dari itu, opsi E adalah rumusan yang tepat.</p><p>Jawaban tepat: <strong>E. Membangun kerja sama internal produktif dan kemitraan harmonis</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan penyeragaman paksa yang mematikan keanekaragaman gagasan positif.</li><li><strong>B</strong>: Merupakan sikap mementingkan unit sendiri yang bertentangan dengan sinergi bersama.</li><li><strong>C</strong>: Merupakan pelimpahan tanggung jawab yang merusak soliditas tim kerja kementerian.</li><li><strong>D</strong>: Merupakan pembatasan kaku saluran komunikasi yang menghambat kelancaran koordinasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 36) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Sinergi, berarti seluruh pegawai harus berkomitmen untuk • membangun dan memastikan hubungan kerja sama internal yang produktif serta kemitraan yang harmonis</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Kewajiban kode perilaku nilai integritas terkait reputasi lembaga mengamanatkan pegawai untuk...", "options": ["Menjaga citra, harkat, dan martabat di forum dalam dan luar negeri", "Mempromosikan pencapaian pribadi pegawai di media massa lokal", "Menyamarkan identitas instansi tempat bekerja kepada publik", "Membela rekan kerja seunit yang terbukti berbuat kekeliruan", "Meminta penundaan tugas kedinasan ke luar wilayah kota dinas"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kewajiban Menjaga Citra Kementerian</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kode etik integritas mencakup: <strong>Menjaga citra, harkat, dan martabat Kementerian Keuangan di berbagai forum, baik formal maupun informal di dalam maupun di luar negeri</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat mengenai tanggung jawab menjaga kehormatan institusi. Sikap dan tutur kata aparatur di manapun berada senantiasa merefleksikan wibawa kementerian di mata publik, baik di dalam negeri maupun kancah internasional. Dalam pedoman etika, menjaga citra, harkat, dan martabat di forum formal/informal adalah butir pertama kode etik integritas. Oleh sebab itu, opsi A adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>A. Menjaga citra, harkat, dan martabat di forum dalam dan luar negeri</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan publikasi diri yang bukan perwujudan menjaga marwah kehormatan instansi.</li><li><strong>C</strong>: Merupakan penyembunyian identitas yang mencerminkan hilangnya rasa bangga korps.</li><li><strong>D</strong>: Merupakan solidaritas keliru yang mencederai penegakan hukum dan integritas.</li><li><strong>E</strong>: Merupakan penolakan penugasan resmi yang melanggar disiplin kewajiban dinas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 36) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Menjaga citra, harkat, dan martabat Kementerian Keuangan • baik formal maupun informal di dalam maupun di luar negeri</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Dalam perhelatan pesta demokrasi pemilihan umum, kode etik nilai integritas mewajibkan aparatur untuk...", "options": ["Menjadi tim sukses kampanye calon kepala daerah setempat", "Bersikap netral dalam pemilihan presiden, pilkada, dan pemilu legislatif", "Memfasilitasi kegiatan partai politik tertentu di aula kantor", "Mengunggah simbol dukungan calon legislatif di akun media sosial", "Mengarahkan pilihan politik warga binaan saat proses pelayanan"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kewajiban Netralitas Politik Pegawai</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai wajib: <strong>Bersikap netral dalam Pemilihan Calon Presiden dan Wakil Presiden, Kepala Daerah dan Wakil Kepala Daerah, serta Anggota Legislatif Pusat dan Daerah</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat tentang asas netralitas aparatur sipil negara. ASN adalah abdi negara yang melayani seluruh rakyat tanpa memihak kekuatan politik manapun demi menjaga objektivitas dan keadilan birokrasi. Pedoman etika menegaskan kewajiban bersikap netral dalam pemilu presiden, kepala daerah, maupun legislatif. Dengan demikian, opsi B adalah kewajiban yang digariskan.</p><p>Jawaban tepat: <strong>B. Bersikap netral dalam pemilihan presiden, pilkada, dan pemilu legislatif</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan keterlibatan tim kampanye politik yang dilarang keras undang-undang ASN.</li><li><strong>C</strong>: Merupakan pemanfaatan sarana milik negara untuk kepentingan politik partisan.</li><li><strong>D</strong>: Merupakan pelanggaran disiplin netralitas digital yang dilarang bagi aparatur.</li><li><strong>E</strong>: Merupakan penyalahgunaan wewenang pelayanan publik untuk tujuan pemenangan politik.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 36) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Bersikap netral dalam Pemilihan Calon Presiden dan Wakil Presiden • Kepala Daerah dan Wakil Kepala Daerah, serta Anggota Legislatif Pusat dan Daerah</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Terkait aktivitas di ranah digital internet, kode etik nilai integritas menginstruksikan pegawai untuk...", "options": ["Menyebarluaskan dokumen rancangan anggaran sebelum rilis", "Membatasi kepemilikan akun pada seluruh jaringan media maya", "Menggunakan media sosial dengan bijak dan bertanggung jawab", "Mendebat opini warganet terkait kebijakan tarif cukai dinas", "Mengunggah foto fasilitas ruang kerja rahasia kantor dinas"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Etika Pemanfaatan Media Sosial</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kode etik integritas mengatur bahwa pegawai wajib: <strong>Menggunakan media sosial dengan bijak</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat tentang etika bermedia sosial insan perbendaharaan dan keuangan negara. Jejak digital aparatur senantiasa dipantau publik sehingga setiap unggahan wajib menjunjung kesantunan, menjaga kerahasiaan data negara, dan bebas provokasi. Pedoman kerja menyebutkan butir singkat menggunakan media sosial dengan bijak. Oleh sebab itu, opsi C adalah rumusan yang benar.</p><p>Jawaban tepat: <strong>C. Menggunakan media sosial dengan bijak dan bertanggung jawab</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan pembocoran rahasia dokumen negara yang tergolong pelanggaran berat.</li><li><strong>B</strong>: Merupakan larangan ekstrem yang tidak disyaratkan dalam aturan media sosial.</li><li><strong>D</strong>: Merupakan polemik debat kusir di ruang siber yang dapat memperkeruh persepsi publik.</li><li><strong>E</strong>: Merupakan pelanggaran keamanan data fisik kantor yang membahayakan kerahasiaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 36) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Menggunakan media sosial • dengan bijak</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Bagi pegawai yang berencana melakukan perjalanan ke luar negeri untuk kepentingan pribadi, ketentuan yang wajib ditaati adalah...", "options": ["Menitipkan tugas kantor kepada tenaga keamanan gedung", "Cukup memberitahukan kepada rekan sejawat lewat pesan singkat", "Melakukan perjalanan langsung tanpa perlu izin pimpinan unit", "Mengajukan permohonan izin resmi setiap akan bepergian", "Menggunakan paspor dinas dinas diplomatik kementerian"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Ketentuan Perjalanan Pribadi ke Luar Negeri</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai wajib: <strong>Mengajukan permohonan izin setiap akan melakukan perjalanan ke luar negeri untuk kepentingan pribadi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat mengenai prosedur administratif bepergian ke luar negeri. Pengajuan izin resmi bertujuan memantau keberadaan dan keamanan pegawai serta memastikan tidak ada pelanggaran kepentingan tugas kedinasan. Berdasarkan pedoman kerja, pegawai wajib mengajukan permohonan izin setiap akan bepergian ke luar negeri untuk keperluan pribadi. Maka dari itu, opsi D adalah prosedur resmi yang wajib dipatuhi.</p><p>Jawaban tepat: <strong>D. Mengajukan permohonan izin resmi setiap akan bepergian</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan pelimpahan tugas kepada petugas yang tidak berwenang mengurusi berkas.</li><li><strong>B</strong>: Merupakan pemberitahuan informal yang tidak sah secara tertib administrasi dinas.</li><li><strong>C</strong>: Merupakan pelanggaran disiplin kepegawaian mengenai izin ke luar negeri.</li><li><strong>E</strong>: Merupakan penyalahgunaan paspor dinas untuk urusan pribadi yang dilarang undang-undang.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 36) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Mengajukan permohonan izin setiap akan melakukan • perjalanan ke luar negeri untuk kepentingan pribadi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C4 - Analisis", "question": "Larangan integritas mengenai pertemuan dengan pihak yang berpotensi menimbulkan konflik kepentingan dikecualikan apabila...", "options": ["Pertemuan tersebut diadakan di restoran mewah luar kota", "Pihak yang ditemui berjanji memberikan bingkisan bantuan", "Pertemuan dilakukan atas dasar hubungan keluarga dekat", "Pegawai membutuhkan pinjaman dana keuangan mendesak", "Pertemuan dilaksanakan resmi karena adanya surat penugasan"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Larangan Pertemuan Berpotensi Konflik Kepentingan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai dilarang: <strong>Tidak menemui pihak yang berpotensi menimbulkan konflik kepentingan, kecuali karena penugasan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat untuk pengecualian resmi larangan pertemuan dengan pihak berkepentingan. Pertemuan dinas dengan wajib pajak, rekanan pengadaan, atau debitur hanya sah dan terlindungi apabila berlandaskan surat perintah penugasan dinas yang transparan. Pedoman kerja menegaskan frasa pengecualian \"kecuali karena penugasan\". Oleh sebab itu, opsi E adalah kondisi sah yang dibenarkan.</p><p>Jawaban tepat: <strong>E. Pertemuan dilaksanakan resmi karena adanya surat penugasan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan lokasi pertemuan informal yang justru memperbesar risiko gratifikasi.</li><li><strong>B</strong>: Merupakan penerimaan suap/gratifikasi yang tergolong tindak pidana korupsi.</li><li><strong>C</strong>: Merupakan konflik kepentingan kekeluargaan yang wajib dihindari dalam tugas dinas.</li><li><strong>D</strong>: Merupakan motif peminjaman dana pribadi yang mencoreng independensi pemeriksa.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 36) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Tidak menemui pihak yang berpotensi menimbulkan konflik kepentingan • kecuali karena penugasan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Larangan etika integritas terkait perlindungan martabat rekan kerja melarang perbuatan...", "options": ["Bertindak sewenang-wenang, perundungan, dan pelecehan", "Memberikan penilaian kinerja objektif pada akhir tahun", "Menyampaikan koreksi teknis penulisan dokumen dinas", "Mengadakan rapat koordinasi percepatan proyek satker", "Mengingatkan batas waktu penyelesaian disposisi surat"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Larangan Perundungan dan Pelecehan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai dilarang: <strong>Tidak bertindak sewenang-wenang, melakukan perundungan (bullying) dan/atau pelecehan terhadap pegawai atau pihak lain baik di dalam maupun di luar lingkungan kerja</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat tentang jaminan lingkungan kerja yang aman dan bermartabat. Tindakan perundungan (*bullying*), kesewenang-wenangan atasan, maupun pelecehan verbal/fisik mencederai kemanusiaan dan merusak keharmonisan instansi. Kode etik melarang tegas perbuatan tersebut baik di dalam maupun luar kantor. Oleh sebab itu, opsi A adalah larangan yang dimaksud.</p><p>Jawaban tepat: <strong>A. Bertindak sewenang-wenang, perundungan, dan pelecehan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan tugas manajerial evaluasi kinerja yang sah dan wajib dilaksanakan.</li><li><strong>C</strong>: Merupakan bimbingan teknis profesional demi menjaga akurasi produk dinas.</li><li><strong>D</strong>: Merupakan manajemen rapat kerja dinas yang lazim dalam pelaksanaan tugas.</li><li><strong>E</strong>: Merupakan pengingat jadwal kerja yang mendukung ketertiban waktu penyelesaian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 37) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Tidak bertindak sewenang-wenang, melakukan perundungan (bullying) dan/ atau pelecehan • terhadap pegawai atau pihak lain baik di dalam maupun di luar lingkungan kerja</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Pegawai dilarang memasuki tempat yang dinilai tidak pantas secara etika seperti prostitusi dan perjudian, KECUALI...", "options": ["Memperoleh undangan pesta ulang tahun dari kerabat swasta", "Sedang melaksanakan tugas resmi kedinasan yang sah", "Ditemani oleh rekan seprofesi dalam rombongan dinas", "Memanfaatkan waktu libur cuti bersama akhir pekan", "Menyertai kunjungan pejabat pemerintah luar instansi"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Larangan Memasuki Tempat Tidak Pantas</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai dilarang: <strong>Tidak memasuki tempat yang dipandang tidak pantas secara etika dan moral yang berlaku di masyarakat, seperti tempat prostitusi dan perjudian, kecuali karena penugasan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat untuk pengecualian larangan memasuki lokasi tidak pantas. Petugas pemeriksa pajak atau intelijen bea cukai adakalanya harus melakukan operasi penertiban di kawasan hiburan tertentu atas dasar surat tugas resmi. Regulasi kedinasan menetapkan klausul pengecualian \"kecuali karena penugasan\". Dengan demikian, opsi B adalah kondisi yang dibenarkan aturan.</p><p>Jawaban tepat: <strong>B. Sedang melaksanakan tugas resmi kedinasan yang sah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan kegiatan privat yang tetap terikat larangan menjaga moralitas aparatur.</li><li><strong>C</strong>: Merupakan tindakan bersama rekan yang tetap terlarang jika tanpa surat tugas dinas.</li><li><strong>D</strong>: Merupakan waktu luang pribadi yang tidak menggugurkan kode kehormatan PNS.</li><li><strong>E</strong>: Merupakan pengiringan informal tanpa surat penugasan sah yang melanggar kode etik.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 37) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Tidak memasuki tempat yang dipandang tidak pantas secara etika dan moral • seperti tempat prostitusi dan perjudian, kecuali karena penugasan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Larangan menunjukkan gaya hidup hedonisme bagi seluruh pegawai Kementerian Keuangan berakar dari prinsip...", "options": ["Penyeragaman jumlah saldo tabungan pribadi seluruh staf", "Kewajiban membeli barang dari koperasi kantor semata", "Empati kepada masyarakat dan terhadap sesama pegawai", "Pembatasan hak kepemilikan kendaraan bermotor keluarga", "Penghitungan pajak penghasilan dengan tarif progresif"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Larangan Menunjukkan Gaya Hidup Hedonisme</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai dilarang: <strong>Tidak menunjukkan gaya hidup hedonisme sebagai bentuk empati kepada masyarakat terutama kepada sesama pegawai</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat mengenai etika kesederhanaan aparatur sipil negara. Perilaku pamer kemewahan mencederai rasa keadilan publik dan merusak ikatan persaudaraan di lingkungan kerja. Larangan hidup berlebihan didasari pertimbangan kepantasan moral abdi negara demi menjaga martabat korps. Dengan demikian, opsi C merupakan alasan yang benar.</p><p>Jawaban tepat: <strong>C. Empati kepada masyarakat dan terhadap sesama pegawai</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan intervensi rekening pribadi yang tidak diatur dalam kode perilaku.</li><li><strong>B</strong>: Merupakan pemaksaan transaksi belanja koperasi yang keliru dalam aturan etika.</li><li><strong>D</strong>: Merupakan pembatasan harta keluarga yang bukan maksud larangan pamer hedonisme.</li><li><strong>E</strong>: Merupakan aturan perpajakan normatif yang terpisah dari kode etik gaya hidup.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 37) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Tidak menunjukkan gaya hidup hedonisme sebagai bentuk empati • kepada masyarakat terutama kepada sesama pegawai</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Butir pertama dalam Kode Etik dan Kode Perilaku Nilai Profesionalisme mewajibkan pegawai untuk...", "options": ["Menyelesaikan kursus bahasa asing dalam waktu singkat", "Memiliki ruang kerja khusus terpisah dari rekan lainnya", "Menyimpan seluruh tanda bukti belanja kegiatan kantor", "Mengutamakan kepentingan bangsa dan organisasi di atas pribadi", "Menghadiri seminar internasional yang diadakan di luar kota"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Butir Pertama Kode Perilaku Profesionalisme</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kode etik profesionalisme butir pertama: <strong>Mengutamakan kepentingan bangsa dan organisasi di atas kepentingan pribadi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat untuk butir pembuka kode perilaku profesionalisme. Aparatur profesional menempatkan kelangsungan kedaulatan negara dan misi kementerian sebagai prioritas tertinggi di atas ambisi maupun kenyamanan individual. Kode perilaku mewajibkan mengutamakan kepentingan bangsa dan organisasi sebagai butir pertama. Oleh sebab itu, opsi D adalah rumusan yang tepat.</p><p>Jawaban tepat: <strong>D. Mengutamakan kepentingan bangsa dan organisasi di atas pribadi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan keahlian kompetensi bahasa yang sekadar keterampilan pendukung tugas.</li><li><strong>B</strong>: Merupakan fasilitas fisik kantor yang tidak menggambarkan dedikasi kebangsaan.</li><li><strong>C</strong>: Merupakan penatausahaan bukti kuitansi yang sekadar prosedur teknis bendahara.</li><li><strong>E</strong>: Merupakan keikutsertaan acara seminar ilmiah yang berada di luar hakikat dedikasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 37) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>2. Kode Etik dan Kode Perilaku Nilai Profesionalisme meliputi • Mengutamakan kepentingan bangsa dan organisasi di atas kepentingan pribadi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Dalam pelaksanaan tugas sehari-hari, profesionalitas aparatur diwujudkan melalui keharusan bekerja sesuai...", "options": ["Kebiasaan tidak tertulis yang disepakati para pegawai senior", "Instruksi lisan rekan kerja yang memiliki golongan lebih tinggi", "Hasil jajak pendapat terbuka di grup percakapan pegawai", "Seleranya masing-masing demi efisiensi pengerjaan tugas", "Standar operasional prosedur dan batas kewenangan jabatan"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kepatuhan SOP dan Kewenangan Jabatan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kode etik profesionalisme mengamanatkan: <strong>Bekerja sesuai standar operasional prosedur dan kewenangan jabatan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat tentang kepatuhan yuridis operasional aparatur. Bekerja sesuai SOP menjamin kepastian hukum, mencegah mal-administrasi, dan memastikan keluaran layanan memiliki mutu yang seragam dan akuntabel. Dalam pedoman etika, pegawai wajib bekerja berlandaskan SOP dan batas kewenangan jabatannya. Dengan demikian, opsi E adalah pedoman kerja yang benar.</p><p>Jawaban tepat: <strong>E. Standar operasional prosedur dan batas kewenangan jabatan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan kebiasaan informal yang berisiko menyimpang dari tata aturan baku.</li><li><strong>B</strong>: Merupakan komando lisan rekan tanpa dasar wewenang sah yang berpotensi keliru.</li><li><strong>C</strong>: Merupakan konsensus grup obrolan informal yang tidak memiliki kekuatan hukum.</li><li><strong>D</strong>: Merupakan tindakan semaunya sendiri yang merusak standardisasi layanan dinas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 37) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Bekerja sesuai standar operasional prosedur • dan kewenangan jabatan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Saat terjadi kekeliruan dalam pengetikan surat ketetapan dinas, sikap profesional yang harus ditunjukkan pegawai adalah...", "options": ["Berani mengakui kesalahan dan bertanggung jawab memperbaikinya", "Menyalahkan staf magang yang membantu proses pencetakan berkas", "Meneruskan dokumen tersebut selama belum ada komplain dinas", "Menghapus riwayat pembuatan dokumen dari sistem komputer kantor", "Meminta rekan sejawat mengaku sebagai pembuat berkas keliru"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sikap Berani Mengakui Kesalahan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai wajib: <strong>Berani mengakui kesalahan dan bertanggung jawab atas pelaksanaan tugasnya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat tentang kematangan moral profesionalitas. Mengakui kekeliruan dengan kesatria dan segera melakukan perbaikan membuktikan tingginya rasa tanggung jawab serta integritas seorang aparatur sipil negara. Pedoman kedinasan secara eksplisit mewajibkan sikap berani mengakui kesalahan dan bertanggung jawab. Oleh sebab itu, opsi A adalah tindakan yang benar.</p><p>Jawaban tepat: <strong>A. Berani mengakui kesalahan dan bertanggung jawab memperbaikinya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan tindakan melempar kesalahan kepada pihak lemah yang melanggar etika.</li><li><strong>C</strong>: Merupakan pembiaran cacat administrasi yang membahayakan kepastian hukum.</li><li><strong>D</strong>: Merupakan penghilangan bukti jejak kerja yang tergolong pelanggaran berat.</li><li><strong>E</strong>: Merupakan pengorbanan rekan kerja yang bertentangan dengan kejujuran profesional.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 37) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Berani mengakui kesalahan dan • bertanggung jawab atas pelaksanaan tugasnya</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Terkait kenyamanan lingkungan kerja, ketentuan kode etik profesionalisme melarang pegawai untuk...", "options": ["Menempatkan tempat sampah tertutup di sudut ruangan kantor", "Merokok di luar area merokok yang telah ditentukan kantor", "Membawa tanaman hias berukuran kecil ke meja kerja masing-masing", "Membersihkan layar komputer kerja sebelum memulai operasional", "Menggunakan lampu penerangan meja saat membaca berkas naskah"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kerapian Ruang Kerja dan Etika Merokok</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai wajib: Menjaga kebersihan, keamanan, dan kenyamanan ruang kerja, termasuk <strong>tidak merokok di luar area merokok yang telah disediakan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat tentang ketertiban ruang bersama di lingkungan kantor. Asap rokok mengganggu kesehatan, mencemari udara ruangan, serta menciptakan ketidaknyamanan bagi rekan kerja dan pemohon layanan publik. Ketentuan tata tertib melarang keras merokok di luar zona khusus merokok yang telah ditetapkan. Maka dari itu, opsi B adalah larangan yang dimaksud.</p><p>Jawaban tepat: <strong>B. Merokok di luar area merokok yang telah ditentukan kantor</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan sarana kebersihan yang justru dianjurkan dalam tata kelola 5R.</li><li><strong>C</strong>: Merupakan penghias ruangan yang diperbolehkan sepanjang tidak mengganggu.</li><li><strong>D</strong>: Merupakan perawatan sarana kerja elektronik yang mendukung kebersihan.</li><li><strong>E</strong>: Merupakan pemanfaatan sarana penerangan untuk ketelitian membaca berkas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 37) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Menjaga kebersihan, keamanan, dan kenyamanan ruang kerja, termasuk • tidak merokok di luar area merokok yang telah disediakan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C4 - Analisis", "question": "Penggunaan tindik (piercing) bagi pegawai dilarang dalam kode etik profesionalisme, KECUALI...", "options": ["Penggunaan tindik pada alis mata untuk seluruh pegawai dinas", "Penggunaan tindik pada bibir bawah saat menghadiri rapat", "Di daun telinga khusus pegawai perempuan atau alasan agama", "Penggunaan perhiasan tindik di hidung bagi seluruh aparatur", "Penggunaan anting ganda bertingkat pada daun telinga pria"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Ketentuan Penggunaan Tindik (Piercing)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai dilarang: <strong>Tidak memakai tindik (piercing), kecuali penggunaan di daun telinga khusus untuk pegawai perempuan atau karena alasan keagamaan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat untuk pengecualian tata kerapian berbusana dan berpenampilan. Standar kepantasan PNS Kemenkeu menuntut kerapian yang mencerminkan wibawa birokrasi, dengan toleransi khusus bagi tradisi wanita di daun telinga serta alasan keyakinan keagamaan yang sah. Aturan kedinasan merumuskan pengecualian tersebut secara tegas. Oleh sebab itu, opsi C adalah ketentuan sah yang dibenarkan.</p><p>Jawaban tepat: <strong>C. Di daun telinga khusus pegawai perempuan atau alasan agama</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan tindik alis yang dilarang keras dalam standar penampilan kerja dinas.</li><li><strong>B</strong>: Merupakan tindik bibir yang tidak pantas dan menyalahi kode etik kerapian.</li><li><strong>D</strong>: Merupakan tindik hidung yang berada di luar pengecualian resmi pedoman kerja.</li><li><strong>E</strong>: Merupakan anting pada pria yang dilarang dalam standar kesopanan aparatur.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 37) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Tidak memakai tindik (piercing), kecuali • penggunaan di daun telinga khusus untuk pegawai perempuan atau karena alasan keagamaan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Standar kerapian berpenampilan pada nilai profesionalisme melarang pegawai untuk...", "options": ["Mengenakan seragam dinas batik khas daerah pada hari jumat", "Memakai sepatu pantofel berwarna hitam di ruang kerja kantor", "Menyematkan lencana korps pegawai di dada sebelah kiri", "Memiliki tato pada bagian tubuh yang terbuka terlihat", "Merapikan rambut sesuai ketentuan potongan dinas aparatur"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Larangan Bertato pada Tubuh Terbuka</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kode etik profesionalisme melarang: <strong>Tidak bertato di bagian tubuh yang terbuka</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat tentang ketentuan penampilan fisik aparatur Kementerian Keuangan. Kerapian yang terjaga bebas dari rajah tato di area tubuh yang terbuka menjaga citra kesopanan, netralitas, dan kepantasan seorang abdi negara di hadapan publik. Pedoman kerja melarang tegas tato di bagian tubuh yang terbuka. Oleh sebab itu, opsi D adalah larangan yang dimaksud.</p><p>Jawaban tepat: <strong>D. Memiliki tato pada bagian tubuh yang terbuka terlihat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan busana dinas batik yang dianjurkan pada hari kerja tertentu.</li><li><strong>B</strong>: Merupakan sepatu dinas formal standar yang wajib dikenakan pegawai.</li><li><strong>C</strong>: Merupakan atribut resmi korps pegawai Republik Indonesia (KORPRI).</li><li><strong>E</strong>: Merupakan kewajiban kerapian rambut yang justru diharuskan bagi seluruh ASN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 38) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Tidak bertato • di bagian tubuh yang terbuka</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Kode etik nilai Sinergi mewajibkan seluruh pegawai untuk menghormati dan menghargai perbedaan...", "options": ["Gaya berbusana mewah antar-keluarga pejabat eselon dinas", "Jumlah saldo tabungan pribadi antar-rekan di satu ruangan", "Pilihan merek kendaraan roda empat yang terparkir di kantor", "Besaran alokasi pagu anggaran belanja barang antar-satker", "Ras, warna kulit, agama, jenis kelamin, dan disabilitas"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penghormatan atas Keragaman dalam Nilai Sinergi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai wajib: <strong>Menghormati dan menghargai perbedaan latar belakang, ras, warna kulit, agama, asal-usul, jenis kelamin, status pernikahan, umur, atau kondisi kecacatan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat mengenai nilai inklusivitas dan antidiskriminasi aparatur. Lingkungan kerja Kemenkeu yang majemuk menuntut penghargaan tulus atas perbedaan suku, keyakinan, gender, dan kondisi disabilitas demi terciptanya sinergi yang kokoh. Pedoman toleransi merinci aspek keberagaman tersebut secara komprehensif. Dengan demikian, opsi E adalah rincian yang benar.</p><p>Jawaban tepat: <strong>E. Ras, warna kulit, agama, jenis kelamin, dan disabilitas</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan pembedaan berbasis materiil yang bertentangan dengan kesederhanaan.</li><li><strong>B</strong>: Merupakan urusan finansial privat yang tidak ada kaitannya dengan nilai sinergi.</li><li><strong>C</strong>: Merupakan kepemilikan aset pribadi yang berada di luar substansi etika toleransi.</li><li><strong>D</strong>: Merupakan porsi anggaran dinas yang bukan ranah keragaman hak asasi pegawai.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 38) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Menghormati dan menghargai perbedaan latar belakang, ras, warna kulit, agama • asal-usul, jenis kelamin, status pernikahan, umur, atau kondisi kecacatan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Bentuk toleransi religius dalam kode etik nilai Sinergi saat rapat kerja sedang berlangsung adalah...", "options": ["Memberikan kesempatan menunaikan ibadah tepat waktu", "Menunda waktu ibadah hingga seluruh agenda rapat selesai", "Melarang pegawai meninggalkan ruangan rapat dinas kantor", "Menggabungkan seluruh waktu ibadah di akhir jam dinas", "Membatalkan seluruh hasil kesepakatan rapat kerja unit"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Toleransi Waktu Ibadah dalam Bertugas</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kode etik sinergi mengamanatkan: <strong>Memberikan kesempatan untuk menunaikan ibadah ketika rapat kerja atau tugas kedinasan sedang berlangsung</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat tentang jaminan kemerdekaan beribadah bagi insan kementerian. Kesibukan menuntaskan agenda dinas tidak boleh merampas hak spiritual pegawai untuk menjalankan kewajiban agamanya secara khusyuk. Nilai kebersamaan mewajibkan memberi jeda waktu ibadah saat rapat kerja berlangsung. Maka dari itu, opsi A adalah tindakan yang benar.</p><p>Jawaban tepat: <strong>A. Memberikan kesempatan menunaikan ibadah tepat waktu</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan penundaan sepihak yang mencederai penghormatan atas hak beribadah.</li><li><strong>C</strong>: Merupakan larangan sewenang-wenang yang melanggar kode etik nilai sinergi.</li><li><strong>D</strong>: Merupakan pemaksaan jadwal ibadah yang tidak menghargai ketentuan syariat agama.</li><li><strong>E</strong>: Merupakan pembatalan keputusan yang merusak kesinambungan rencana kerja kantor.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 38) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Memberikan kesempatan untuk menunaikan ibadah • ketika rapat kerja atau tugas kedinasan sedang berlangsung</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Terkait arus peredaran informasi, kode etik sinergi melarang keras pegawai untuk menyebarkan...", "options": ["Peraturan menteri keuangan terbaru yang telah diundangkan", "Informasi tak terbukti yang picu kebencian dan permusuhan", "Pengumuman jadwal seleksi pelatihan kompetensi pegawai", "Pedoman teknis penyusunan laporan keuangan kementerian", "Daftar pemenang lomba karya tulis ilmiah antar-kantor"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Larangan Penyebaran Informasi Hoaks dan Permusuhan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai dilarang: <strong>Tidak menyebarkan informasi yang tidak dapat dibuktikan kebenarannya, menimbulkan rasa kebencian dan/ atau permusuhan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat tentang integritas peredaran kabar di era digital. Penyebaran berita bohong (*hoaks*) dan ujaran permusuhan berpotensi membelah persatuan korps serta merusak tatanan stabilitas nasional. Pedoman kerja melarang penyebaran kabar tidak terbukti yang menyulut kebencian. Oleh sebab itu, opsi B adalah larangan yang dimaksud.</p><p>Jawaban tepat: <strong>B. Informasi tak terbukti yang picu kebencian dan permusuhan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan produk hukum positif negara yang justru wajib disosialisasikan.</li><li><strong>C</strong>: Merupakan informasi kedinasan resmi yang bermanfaat bagi pengembangan diri.</li><li><strong>D</strong>: Merupakan petunjuk teknis dinas yang mendukung akurasi pembukuan fiskal.</li><li><strong>E</strong>: Merupakan berita prestasi kepegawaian yang menyemangati budaya inovasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 38) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Tidak menyebarkan informasi yang tidak dapat dibuktikan kebenarannya • menimbulkan rasa kebencian dan/ atau permusuhan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Dalam kode etik nilai Pelayanan, pelaksanaan tugas kedinasan seluruh pegawai wajib berorientasi pada...", "options": ["Kecepatan menghabiskan sisa pagu belanja modal kantor", "Kemudahan bagi kerabat dekat untuk memperoleh izin usaha", "Peningkatan kesejahteraan masyarakat luas dalam bertugas", "Pemberian penghargaan sertifikat bagi unit kerja sendiri", "Pengurangan frekuensi layanan tatap muka secara drastis"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Orientasi Kesejahteraan Masyarakat dalam Nilai Pelayanan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kode etik nilai pelayanan mencakup: <strong>Berorientasi pada peningkatan kesejahteraan masyarakat dalam melaksanakan tugas</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat untuk tujuan hakiki pelayanan fiskal negara. Seluruh kebijakan pemungutan pendapatan, pengelolaan kekayaan negara, dan alokasi anggaran bermuara pada peningkatan taraf hidup dan kemakmuran rakyat. Peningkatan taraf kesejahteraan masyarakat adalah orientasi utama nilai pelayanan. Dengan demikian, opsi C adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>C. Peningkatan kesejahteraan masyarakat luas dalam bertugas</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan penyerapan anggaran sepihak yang mengabaikan efisiensi mutu manfaat.</li><li><strong>B</strong>: Merupakan praktik nepotisme izin yang melanggar asas keadilan hukum.</li><li><strong>D</strong>: Merupakan kebanggaan sertifikat formalitas yang bukan tujuan pokok layanan.</li><li><strong>E</strong>: Merupakan pembatasan akses warga yang mencederai asas kemudahan pelayanan publik.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 38) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>4. Kode Etik dan Kode Perilaku Nilai Pelayanan meliputi • Berorientasi pada peningkatan kesejahteraan masyarakat dalam melaksanakan tugas</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Penerimaan tamu non-pekerjaan pada jam kerja diperbolehkan dalam kode etik pelayanan dengan syarat mutlak...", "options": ["Tamu tersebut merupakan pejabat dari kementerian lain", "Pertemuan dilakukan di ruang rapat utama kepala kantor", "Tamu membawa bingkisan makanan untuk seluruh anggota tim", "Seizin atasan dan tidak mengganggu pekerjaan atau layanan", "Pegawai mencatat pertemuan dalam buku kas keuangan dinas"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Etika Menerima Tamu Non-Pekerjaan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai diperbolehkan: Menerima pihak lain yang tidak terkait dengan pekerjaan pada jam kerja <strong>dengan seizin atasan dan/atau sepanjang tidak mengganggu pekerjaan atau layanan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat tentang ketertiban jam kerja operasional kantor. Urusan pribadi di ruang kerja berisiko menyita waktu dinas dan menelantarkan masyarakat yang sedang menanti pelayanan publik. Tata tertib kantor mengatur bahwa penerimaan tamu pribadi wajib mengantongi izin atasan dan sama sekali tidak mengganggu layanan dinas. Oleh sebab itu, opsi D adalah syarat yang benar.</p><p>Jawaban tepat: <strong>D. Seizin atasan dan tidak mengganggu pekerjaan atau layanan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan status tamu pejabat yang tetap wajib tertib aturan penerimaan tamu.</li><li><strong>B</strong>: Merupakan penggunaan fasilitas rapat yang tidak diperkenankan untuk urusan privat.</li><li><strong>C</strong>: Merupakan pemberian makanan yang berisiko mengarah pada gratifikasi informal.</li><li><strong>E</strong>: Merupakan pembukuan kas yang tidak relevan dengan izin kunjungan pribadi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 38) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Menerima pihak lain yang tidak terkait dengan pekerjaan di luar jam kerja atau pada jam kerja • dengan seizin atasan dan/atau sepanjang tidak mengganggu pekerjaan atau layanan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C4 - Analisis", "question": "Karakter keterbukaan yang wajib ditunjukkan pegawai dalam kode etik nilai Kesempurnaan meliputi keterbukaan atas...", "options": ["Bocoran naskah soal ujian seleksi promosi jabatan", "Kritik tanpa dasar yang bertujuan menjatuhkan rekan", "Permintaan penghapusan denda sanksi administratif pajak", "Pembukaan rahasia data keuangan negara kepada media", "Usulan perbaikan serta informasi dan pengetahuan baru"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Keterbukaan dalam Kode Etik Kesempurnaan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kode etik nilai kesempurnaan meliputi: (1) <strong>Terbuka terhadap usulan perbaikan</strong> dan (2) <strong>Terbuka terhadap informasi atau pengetahuan baru</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat tentang sikap adaptif menuju keunggulan berkelanjutan. Sikap tertutup dan merasa paling pandai akan menutup pintu kemajuan serta membiarkan sistem kerja tertinggal dari tuntutan zaman. Pedoman kerja menyebutkan keterbukaan atas usulan perbaikan dan wawasan baru sebagai butir utama nilai kesempurnaan. Maka dari itu, opsi E adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>E. Usulan perbaikan serta informasi dan pengetahuan baru</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan pembocoran rahasia ujian kedinasan yang tergolong tindak pidana.</li><li><strong>B</strong>: Merupakan fitnah perundungan yang bertentangan dengan etika berorganisasi.</li><li><strong>C</strong>: Merupakan penghapusan denda sewenang-wenang yang melanggar aturan perpajakan.</li><li><strong>D</strong>: Merupakan pembocoran rahasia negara yang mencederai integritas sumpah jabatan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 38) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>5. Kode Etik dan Kode Perilaku Nilai Kesempurnaan meliputi • Terbuka terhadap usulan perbaikan • Terbuka terhadap informasi atau pengetahuan baru</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Kode etik nilai kesempurnaan melarang aparatur pimpinan maupun rekan kerja untuk...", "options": ["Menghalangi kreativitas bernilai tambah bagi kemajuan instansi", "Melakukan evaluasi berkala atas mutu penyelesaian tugas dinas", "Memberikan masukan kritis dalam perbaikan sistem aplikasi kantor", "Menghadiri lokakarya pembahasan terobosan proses bisnis baru", "Mendorong rekan kerja menguasai peranti lunak teknologi baru"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Larangan Menghalangi Kreativitas Organisasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai dilarang: <strong>Tidak menghalangi kreativitas/gagasan/pendapat yang bernilai tambah bagi kemajuan organisasi</strong>, serta tidak menghalangi upaya inovasi yang tidak bertentangan dengan peraturan perundang-undangan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat tentang iklim perlindungan inovasi kerja. Sikap konservatif yang mematikan ide-ide kreatif bawahan atau rekan kerja akan membelenggu instansi dalam stagnasi birokrasi. Kode perilaku melarang secara tegas tindakan menghalangi kreativitas atau gagasan yang bernilai tambah bagi organisasi. Oleh sebab itu, opsi A adalah larangan yang dimaksud.</p><p>Jawaban tepat: <strong>A. Menghalangi kreativitas bernilai tambah bagi kemajuan instansi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan pengendalian mutu yang justru diharuskan dalam perbaikan berkelanjutan.</li><li><strong>C</strong>: Merupakan sumbangsih pemikiran konstruktif yang mendukung pembaruan sistem.</li><li><strong>D</strong>: Merupakan partisipasi aktif yang selaras dengan semangat belajar inovatif.</li><li><strong>E</strong>: Merupakan dorongan positif peningkatan kecakapan teknologi informasi rekan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 39) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Tidak menghalangi kreativitas/gagasan/pendapat yang bernilai tambah • bagi kemajuan organisasi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Penguatan budaya Kementerian Keuangan dalam KMK Nomor 429/KMK.01/2022 didasarkan pada perpaduan...", "options": ["Peraturan daerah dan adat istiadat suku setempat", "Core Values ASN BerAKHLAK dan Nilai-Nilai Kemenkeu", "Instruksi kerja pimpinan perbankan swasta nasional", "Pedoman audit eksternal badan pemeriksa keuangan", "Ketentuan tata tertib kepegawaian negara sahabat"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dua Fondasi Utama Penguatan Budaya 2022</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Penguatan budaya di Kementerian Keuangan didasarkan pada <strong>core values ASN BerAKHLAK dan Nilai-Nilai Kementerian Keuangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat mengenai dua pilar penguatan budaya kerja modern. Integrasi nilai BerAKHLAK (tingkat nasional) dan 5 Nilai Kemenkeu (tingkat kementerian) menciptakan panduan terpadu bagi seluruh pegawai dalam menjalankan amanah kebangsaan dan fiskal negara. Sebagaimana dicatat di pedoman etika, kedua fondasi tersebut menjadi dasar KMK 429/2022. Dengan demikian, opsi B adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>B. Core Values ASN BerAKHLAK dan Nilai-Nilai Kemenkeu</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan kearifan lokal daerah yang bukan dasar yuridis penetapan keputusan menteri.</li><li><strong>C</strong>: Merupakan operasional korporasi swasta yang berada di luar ranah regulasi ASN.</li><li><strong>D</strong>: Merupakan standar pengawasan BPK yang bukan instrumen internal pembinaan budaya.</li><li><strong>E</strong>: Merupakan aturan birokrasi asing yang tidak berlaku bagi aparatur sipil negara kita.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 39) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Penguatan budaya di Kementerian Keuangan didasarkan pada • core values ASN BerAKHLAK dan Nilai-Nilai Kementerian Keuangan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Pola pikir, sikap, dan kebiasaan pegawai dalam KMK Nomor 429/KMK.01/2022 diarahkan agar terwujud dalam budaya...", "options": ["Kompetisi Antar-Eselon yang Ketat", "Birokrasi Tradisional Berjenjang", "Kementerian Keuangan Satu (Kemenkeu Satu)", "Kemandirian Unit Fiskal Terpisah", "Kepatuhan Seremonial Berkelanjutan"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Wujud Budaya Kementerian Keuangan Satu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Penguatan budaya ditujukan untuk memberikan pedoman dan mendorong terciptanya pola pikir, sikap, perilaku, dan kebiasaan yang <strong>terwujud dalam budaya Kementerian Keuangan Satu (Kemenkeu Satu)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat untuk identitas terpadu budaya organisasi Kemenkeu. Konsep \"Kemenkeu Satu\" meruntuhkan sekat ego sektoral antardirektorat jenderal dan menyatukan tekad seluruh insan perbendaharaan sebagai satu keluarga besar pengelola keuangan negara. Wujud nyata budaya kerja budaya tersebut adalah Kemenkeu Satu. Maka dari itu, opsi C adalah istilah resmi yang benar.</p><p>Jawaban tepat: <strong>C. Kementerian Keuangan Satu (Kemenkeu Satu)</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan persaingan internal yang justru merusak semangat kolaborasi bersama.</li><li><strong>B</strong>: Merupakan pola lama yang menghambat kecepatan respon pelayanan aparatur modern.</li><li><strong>D</strong>: Merupakan fragmentasi unit kerja yang bertolak belakang dengan persatuan organisasi.</li><li><strong>E</strong>: Merupakan pendekatan seremonial luar yang mengabaikan transformasi perilaku batin.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 39) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>terwujud dalam budaya Kementerian Keuangan Satu • (Kemenkeu Satu) dan sejalan dengan Core Values ASN BerAKHLAK</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "KMK Nomor 429/KMK.01/2022 ditujukan mendorong terciptanya ekosistem dan lingkungan kerja yang...", "options": ["Santai tanpa target capaian keluaran yang jelas", "Tertutup dari setiap akses pengawasan masyarakat", "Bergantung penuh pada arahan pimpinan di pusat", "Kolaboratif, produktif, inovatif, beretika, dan berintegritas", "Tertutup dan membatasi pemanfaatan sarana teknologi"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Karakteristik Ekosistem Kerja yang Diharapkan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Penguatan budaya ditujukan untuk: <strong>mendorong terciptanya ekosistem dan lingkungan kerja yang kolaboratif, produktif, dan inovatif dengan tetap menjunjung tinggi etika dan integritas</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat mengenai profil iklim kerja institusi masa depan. Ekosistem yang ideal memadukan kerja sama tim yang lincah (*kolaboratif*), hasil kerja nyata bernilai tinggi (*produktif*), keberanian terobosan baru (*inovatif*), dan kepatuhan moral (*integritas*). Dalam pedoman etika, kelima nilai luhur inilah yang membentuk ekosistem Kemenkeu. Oleh sebab itu, opsi D adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>D. Kolaboratif, produktif, inovatif, beretika, dan berintegritas</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan ketiadaan target yang merusak profesionalisme dan akuntabilitas dinas.</li><li><strong>B</strong>: Merupakan ketertutupan birokrasi yang melanggar asas keterbukaan informasi publik.</li><li><strong>C</strong>: Merupakan kepasifan struktural yang mematikan inisiatif pemecahan masalah di daerah.</li><li><strong>E</strong>: Merupakan kekakuan kerja yang bertolak belakang dengan dinamika era digital.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 39) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>mendorong terciptanya ekosistem dan lingkungan kerja yang • kolaboratif, produktif, dan inovatif dengan tetap menjunjung tinggi etika dan integritas</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Penguatan Budaya Kementerian Keuangan dalam pelaksanaannya dilakukan melalui pembudayaan...", "options": ["Instruksi tertulis bertingkat dan sanksi pemotongan gaji", "Ujian kompetensi tertulis dan pembuatan karya ilmiah", "Pemasangan spanduk seremonial dan pembacaan ikrar apel", "Kegiatan olahraga mingguan dan senam kesegaran jasmani", "Sikap Dasar dan Perilaku Efektif pada setiap nilai"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dua Instrumen Penguatan Budaya Kemenkeu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Penguatan Budaya Kemenkeu dilakukan dengan <strong>pembudayaan Sikap Dasar dan Perilaku Efektif</strong> yang diturunkan dari Core Values ASN BerAKHLAK dan Nilai-Nilai Kementerian Keuangan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat untuk dua pilar operasionalisasi budaya organisasi. Sikap Dasar menyentuh ranah kesadaran batiniah, sedangkan Perilaku Efektif mewujudkannya dalam tindakan nyata sehari-hari yang dapat diamati dan diteladani. Secara operasional ditegaskan bahwa pembudayaan dilakukan lewat Sikap Dasar dan Perilaku Efektif. Maka dari itu, opsi E adalah instrumen yang tepat.</p><p>Jawaban tepat: <strong>E. Sikap Dasar dan Perilaku Efektif pada setiap nilai</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan pendekatan penalti formal yang tidak membangun kesadaran intrinsik.</li><li><strong>B</strong>: Merupakan instrumen evaluasi akademik yang berada di luar ranah pembiasaan budaya.</li><li><strong>C</strong>: Merupakan kegiatan publisitas lahiriah yang tidak menjamin perubahan perilaku nyata.</li><li><strong>D</strong>: Merupakan sarana kebugaran jasmani yang sekadar kegiatan penunjang kesehatan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 39) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Penguatan Budaya Kemenkeu dilakukan dengan pembudayaan • Sikap Dasar dan Perilaku Efektif</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C4 - Analisis", "question": "Yang dimaksud dengan Sikap Dasar pegawai dalam regulasi penguatan budaya adalah sekumpulan pikiran dan perasaan yang...", "options": ["Secara tidak langsung terlihat namun harus dimiliki pegawai", "Dapat dicatat secara angka dalam daftar penilaian kehadiran", "Diwajibkan khusus bagi pejabat pengelola anggaran satker", "Dituliskan secara rinci dalam naskah sumpah janji aparatur", "Menghilang secara otomatis saat pegawai menyelesaikan masa tugas"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengertian dan Karakteristik Sikap Dasar</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Sikap Dasar</strong> adalah sekumpulan pikiran, perasaan, dan/atau kecenderungan seseorang untuk menyukai atau tidak menyukai sesuatu, yang <strong>secara tidak langsung terlihat namun harus dimiliki oleh pegawai Kementerian Keuangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat mengenai hakikat psikologis sikap dasar. Sikap dasar bersemayam di dalam alam kesadaran mental dan emosional seseorang sehingga tidak tampak kasat mata, namun menjadi kompas penentu seluruh tindakan lahiriah. Sifat batiniah sikap dasar mencirikan tidak langsung terlihat namun wajib dimiliki. Oleh sebab itu, opsi A menyajikan pengertian yang benar.</p><p>Jawaban tepat: <strong>A. Secara tidak langsung terlihat namun harus dimiliki pegawai</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan data absensi kehadiran yang merupakan catatan fisik perilaku luar.</li><li><strong>C</strong>: Merupakan pembatasan jabatan yang keliru karena sikap dasar mengikat seluruh ASN.</li><li><strong>D</strong>: Merupakan butir naskah ikrar sumpah yang berada dalam dokumen hukum terpisah.</li><li><strong>E</strong>: Merupakan anggapan keliru karena nilai moral melekat seumur hidup pada jati diri abdi negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 39) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Sikap Dasar pegawai Kementerian Keuangan yang dimaksud dalam peraturan ini adalah • sekumpulan pikiran, perasaan, dan/atau kecenderungan seseorang untuk menyukai atau tidak menyukai sesuatu • secara tidak langsung terlihat namun harus dimiliki</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Karakteristik utama dari Perilaku Efektif pegawai Kementerian Keuangan dalam tata kelola aparatur adalah...", "options": ["Disimpan sebagai keyakinan batin yang dirahasiakan", "Berasal dari sikap dasar dan bersifat langsung terlihat", "Membutuhkan alokasi anggaran khusus untuk memunculkannya", "Dipraktikkan sewaktu ada kegiatan inspeksi mendadak kantor", "Berlaku khusus pada saat perhelatan upacara bendera dinas"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengertian dan Karakteristik Perilaku Efektif</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Perilaku Efektif</strong> adalah sekumpulan perilaku atau gaya kerja yang berasal dari Sikap Dasar, dan <strong>bersifat secara langsung terlihat, serta perlu ditunjukkan oleh setiap pegawai guna mendukung efektivitas dan produktivitas dalam sistem kerja baru</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat untuk ciri pembeda perilaku efektif dari sikap dasar. Perilaku efektif adalah wujud konkrit perbuatan, tutur kata, dan gaya kerja yang tampak nyata sehingga dapat dilihat, dievaluasi, dan dirasakan langsung oleh rekan kerja maupun masyarakat. Pedoman etika menegaskan sifatnya yang berasal dari sikap dasar dan langsung terlihat. Dengan demikian, opsi B adalah jawaban yang tepat.</p><p>Jawaban tepat: <strong>B. Berasal dari sikap dasar dan bersifat langsung terlihat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan karakteristik sikap dasar batiniah, bukan perilaku efektif yang tampak.</li><li><strong>C</strong>: Merupakan asumsi biaya yang keliru karena perilaku baik lahir dari kesadaran moral.</li><li><strong>D</strong>: Merupakan kepura-puraan saat sidak yang bertentangan dengan konsistensi integritas.</li><li><strong>E</strong>: Merupakan formalisme upacara yang mempersempit praktik kerja sehari-hari di kantor.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 39) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Perilaku Efektif pegawai Kementerian Keuangan yang dimaksud adalah • sekumpulan perilaku atau gaya kerja yang berasal dari Sikap Dasar, dan bersifat secara langsung terlihat</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Pelaksanaan pembudayaan Sikap Dasar dan Perilaku Efektif dilakukan melalui tiga strategi utama, yaitu...", "options": ["Rasionalisasi, Efisiensi, dan Sentralisasi tugas dinas", "Sosialisasi, Sertifikasi, dan Terminasi kepegawaian", "Internalisasi, Institusionalisasi, dan Eksternalisasi", "Formulasi, Implementasi, dan Evaluasi kinerja anggaran", "Perencanaan, Penganggaran, dan Pertanggungjawaban kas"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tiga Strategi Pembudayaan Budaya Kemenkeu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pelaksanaan pembudayaan Sikap Dasar dan Perilaku Efektif dilakukan dengan strategi: (a) <strong>Internalisasi</strong>, (b) <strong>Institusionalisasi</strong>, dan (c) <strong>Eksternalisasi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat untuk tiga strategi pembudayaan resmi pada KMK 429/2022. Ketiga pilar ini merangkum penanaman keyakinan batin (*internalisasi*), pengikatan sistemik kelembagaan (*institusionalisasi*), serta keteladanan kepada publik luar (*eksternalisasi*). Dalam panduan perilaku, ketiga pilar strategi tersebut adalah Internalisasi, Institusionalisasi, dan Eksternalisasi. Dengan demikian, opsi C adalah jawaban yang tepat.</p><p>Jawaban tepat: <strong>C. Internalisasi, Institusionalisasi, dan Eksternalisasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan konsep manajemen perampingan birokrasi, bukan tiga pilar pembudayaan.</li><li><strong>B</strong>: Merupakan tahapan sertifikasi profesi aparatur di luar kerangka strategi budaya.</li><li><strong>D</strong>: Merupakan siklus kebijakan publik umum yang bukan trilogi strategi budaya kementerian.</li><li><strong>E</strong>: Merupakan siklus pengelolaan perbendaharaan negara, bukan strategi budaya organisasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 40) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>dilakukan dengan strategi • Internalisasi • Institusionalisasi • Eksternalisasi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Strategi pembudayaan yang bertujuan membentuk keyakinan atau mindset diri agar terwujud perilaku positif disebut...", "options": ["Institusionalisasi", "Eksternalisasi", "Restrukturisasi", "Internalisasi", "Standardisasi"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Strategi Internalisasi Budaya</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Internalisasi</strong> merupakan kegiatan atau upaya yang bertujuan <strong>membentuk keyakinan/mindset diri agar terwujud dalam sikap/perilaku yang positif dalam organisasi</strong>, melalui sosialisasi, proses pembelajaran, FGD, dan upaya lainnya.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat mengenai strategi penanaman nilai ke dalam alam pikir individu. Melalui pembelajaran berkesinambungan dan diskusi kelompok terarah, aparatur menyerap nilai hingga menjadi kesadaran batin yang mengarahkan perbuatan sehari-hari. Secara konseptual didefinisikan bahwa upaya pembentukan mindset diri ini sebagai Internalisasi. Maka dari itu, opsi D adalah istilah yang benar.</p><p>Jawaban tepat: <strong>D. Internalisasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan pelembagaan nilai ke dalam sistem aturan hukum dan penghargaan instansi.</li><li><strong>B</strong>: Merupakan penyebaran pemahaman nilai kepada pihak luar melalui keteladanan pelayanan.</li><li><strong>C</strong>: Merupakan penataan ulang susunan kelembagaan birokrasi kementerian.</li><li><strong>E</strong>: Merupakan penetapan tolok ukur mutu teknis baku bagi seluruh proses dinas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 40) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Internalisasi, merupakan kegiatan atau upaya yang bertujuan • membentuk keyakinan/mindset diri agar terwujud dalam sikap/perilaku yang positif dalam organisasi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Upaya menjadikan sekumpulan nilai melekat dan melembaga melalui penguatan regulasi serta reward and punishment disebut...", "options": ["Internalisasi", "Eksternalisasi", "Sentralisasi", "Rasionalisasi", "Institusionalisasi"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Strategi Institusionalisasi Budaya</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Institusionalisasi</strong> merupakan kegiatan atau upaya yang bertujuan <strong>menjadikan sekumpulan nilai, sikap, dan perilaku tertentu sebagai bagian yang melekat dan melembaga dalam organisasi</strong>, melalui penguatan regulasi/kebijakan serta integrasi reward/punishment.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat untuk pengikatan nilai ke dalam sistem kelembagaan. Agar nilai tidak bergantung pada kerelaan pribadi semata, organisasi memperkuatnya dengan payung hukum formal, indikator kinerja, dan sistem konsekuensi apresiasi/sanksi. Secara psikologis didefinisikan bahwa strategi ini sebagai Institusionalisasi. Oleh sebab itu, opsi E adalah jawaban yang tepat.</p><p>Jawaban tepat: <strong>E. Institusionalisasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan penanaman keyakinan pola pikir individu, bukan pelembagaan sistemik formal.</li><li><strong>B</strong>: Merupakan penularan budaya kepada pemangku kepentingan di luar instansi.</li><li><strong>C</strong>: Merupakan pemusatan wewenang birokrasi yang bukan konsep strategi budaya.</li><li><strong>D</strong>: Merupakan efisiensi logis susunan pegawai yang terpisah dari pelembagaan nilai.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 40) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Institusionalisasi, merupakan kegiatan atau upaya yang bertujuan • menjadikan sekumpulan nilai, sikap, dan perilaku tertentu sebagai bagian yang melekat dan melembaga dalam organisasi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Strategi eksternalisasi budaya kementerian kepada para pemangku kepentingan di luar instansi diwujudkan melalui...", "options": ["Keteladanan perilaku pegawai sebagai role models melayani", "Pemberian hibah dana bantuan sosial kepada masyarakat luas", "Pemasangan iklan niaga komersial di stasiun televisi swasta", "Pembatasan kunjungan tamu dinas ke kantor pelayanan daerah", "Penarikan biaya tarif layanan tambahan bagi pemohon eksternal"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Strategi Eksternalisasi Budaya</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Eksternalisasi</strong> bertujuan memastikan pemahaman Budaya Kemenkeu oleh pihak luar <strong>melalui keteladanan perilaku pegawai dalam memberikan pelayanan kepada pemangku kepentingan (role models)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat mengenai metode penularan budaya kepada masyarakat publik. Publik eksternal akan memahami keluhuran nilai Kemenkeu bukan dari ceramah teori, melainkan dari pembuktian integritas, keramahan, dan kecepatan pegawai saat melayani sebagai teladan nyata (*role models*). Keteladanan nyata para pimpinan role models sebagai sarana eksternalisasi. Dengan demikian, opsi A adalah wujud nyata strategi tersebut.</p><p>Jawaban tepat: <strong>A. Keteladanan perilaku pegawai sebagai role models melayani</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan pos belanja bantuan sosial yang bukan strategi penanaman budaya organisasi.</li><li><strong>C</strong>: Merupakan kampanye iklan berbayar yang tidak mencerminkan keteladanan etika kerja.</li><li><strong>D</strong>: Merupakan isolasi layanan yang bertentangan dengan asas keterbukaan melayani publik.</li><li><strong>E</strong>: Merupakan pungutan liar terlarang yang melanggar hukum pemberantasan korupsi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 40) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Eksternalisasi, merupakan kegiatan atau upaya yang bertujuan • mendorong dan memastikan pemahaman Budaya Kemenkeu oleh pihak di luar Kementerian Keuangan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Pimpinan Unit Jabatan Pimpinan Tinggi Madya wajib menyampaikan laporan pelaksanaan penguatan budaya kepada...", "options": ["Komisi Pemilihan Umum Republik Indonesia", "Biro SDM Sekretariat Jenderal Kementerian Keuangan", "Badan Kepegawaian Negara Kantor Regional", "Lembaga Administrasi Negara Republik Indonesia", "Kementerian Koordinator Bidang Perekonomian"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tanggung Jawab Pimpinan Unit Eselon I</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pimpinan Unit Jabatan Pimpinan Tinggi Madya melakukan penumbuhan Sikap Dasar dan Perilaku Efektif, serta <strong>penyampaian laporan pelaksanaannya kepada Biro SDM Sekretariat Jenderal Kementerian Keuangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat untuk unit penerima laporan pelaksanaan budaya dari pimpinan eselon I. Biro Sumber Daya Manusia (SDM) bertindak sebagai pusat kompilasi dan pengendali data budaya pegawai sebelum diolah menjadi laporan komprehensif tingkat kementerian. Dalam pedoman etika, laporan ditujukan kepada Biro SDM Sekretariat Jenderal. Maka dari itu, opsi B adalah unit tujuan pelaporan yang benar.</p><p>Jawaban tepat: <strong>B. Biro SDM Sekretariat Jenderal Kementerian Keuangan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan lembaga penyelenggara pemilu yang tidak memiliki relasi laporan budaya dinas.</li><li><strong>C</strong>: Merupakan instansi kepegawaian luar yang bukan penampung laporan budaya internal.</li><li><strong>D</strong>: Merupakan lembaga kediklatan aparatur nasional di luar hierarki internal Kemenkeu.</li><li><strong>E</strong>: Merupakan kementerian koordinator yang berada di luar mekanisme teknis laporan SDM.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 40) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Setiap pimpinan Unit Jabatan Pimpinan Tinggi Madya/Unit Organisasi Non Eselon di bawah Menteri Keuangan • penyampaian laporan pelaksanaannya kepada Biro SDM Sekretariat Jenderal</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Unit yang bertugas melaksanakan koordinasi, evaluasi, dan pengukuran tingkat kematangan Budaya Kemenkeu adalah...", "options": ["Pusat Pengelolaan Barang Milik Negara", "Direktorat Surat Berharga Syariah Negara", "Biro SDM Sekretariat Jenderal Kemenkeu", "Badan Pengawas Perdagangan Komoditas Berjangka", "Lembaga Pengelola Dana Pendidikan Nasional"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Peran Biro SDM dalam Pengukuran Kematangan Budaya</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Biro SDM Sekretariat Jenderal Kementerian Keuangan melakukan koordinasi pelaksanaan internalisasi Sikap Dasar dan Perilaku Efektif pegawai, pemantauan, evaluasi, dan pengukuran tingkat kematangan Budaya Kemenkeu</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat untuk unit pengukur maturitas budaya di Kementerian Keuangan. Biro SDM menyusun instrumen, memantau kemajuan penerapan sikap dasar, dan mengukur tingkat kematangan budaya di seluruh unit eselon kementerian. Tugas koordinasi tersebut diemban oleh Biro SDM Sekretariat Jenderal. Oleh sebab itu, opsi C adalah unit yang tepat.</p><p>Jawaban tepat: <strong>C. Biro SDM Sekretariat Jenderal Kemenkeu</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan unit pengelola aset fisik gedung dan tanah milik negara di kementerian.</li><li><strong>B</strong>: Merupakan direktorat penerbitan instrumen sukuk negara di Ditjen Pengelolaan Pembiayaan.</li><li><strong>D</strong>: Merupakan otoritas bursa komoditas di bawah Kementerian Perdagangan.</li><li><strong>E</strong>: Merupakan badan layanan umum pengelola beasiswa pendidikan di lingkungan kementerian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 40) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Biro SDM Sekretariat Jenderal Kementerian Keuangan melakukan koordinasi pelaksanaan internalisasi • pemantauan, evaluasi, dan pengukuran tingkat kematangan Budaya Kemenkeu</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C4 - Analisis", "question": "Hasil evaluasi dan pengukuran tingkat kematangan Budaya Kemenkeu pada tahap akhir dilaporkan kepada...", "options": ["Ketua Badan Pemeriksa Keuangan", "Gubernur Bank Sentral Indonesia", "Ketua Majelis Permusyawaratan Rakyat", "Menteri Keuangan Republik Indonesia", "Kepala Kepolisian Negara Republik Indonesia"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Muara Pelaporan Tingkat Kematangan Budaya</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Biro SDM Sekretariat Jenderal menyampaikan <strong>laporan pelaksanaannya kepada Menteri Keuangan</strong> sebagai pemegang kewenangan tertinggi institusi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat untuk muara pelaporan akuntabilitas budaya organisasi. Menteri Keuangan selaku pimpinan kementerian memerlukan data kematangan budaya sebagai bahan pertimbangan strategis penyempurnaan kebijakan kepegawaian dan kepemimpinan birokrasi. Secara fungsional, laporan akhir disampaikan kepada Menteri Keuangan. Dengan demikian, opsi D adalah pihak penerima yang benar.</p><p>Jawaban tepat: <strong>D. Menteri Keuangan Republik Indonesia</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan pimpinan lembaga audit keuangan negara eksternal di luar kementerian.</li><li><strong>B</strong>: Merupakan pimpinan otoritas moneter independen yang bukan atasan langsung kementerian.</li><li><strong>C</strong>: Merupakan lembaga legislatif parlemen tinggi yang tidak menerima laporan teknis SDM.</li><li><strong>E</strong>: Merupakan pimpinan aparat penegak hukum yang berada di luar pelaporan budaya ASN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 40) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pengukuran tingkat kematangan Budaya Kemenkeu, serta • penyampaian laporan pelaksanaannya kepada Menteri Keuangan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Dalam KMK Nomor 429/KMK.01/2022, Core Values ASN Akuntabel dan Loyal diselaraskan dengan nilai Kemenkeu...", "options": ["Sinergi", "Pelayanan", "Profesionalisme", "Kesempurnaan", "Integritas"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penyelarasan Akuntabel dan Loyal dengan Integritas</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Akuntabel dan Loyal (Core Values ASN)</strong> selaras dengan <strong>Integritas (nilai-nilai Kemenkeu)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat mengenai pemetaan keselarasan nilai nasional dan kementerian. Tanggung jawab memegang amanah keuangan (akuntabel) serta kesetiaan pada bangsa dan konstitusi (loyal) merupakan fondasi utama dari tegaknya nilai Integritas di lingkungan Kemenkeu. Sebagaimana dianjurkan dalam etika kerja, Core Values Akuntabel dan Loyal selaras dengan Integritas. Oleh sebab itu, opsi E adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>E. Integritas</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan nilai Kemenkeu yang diselaraskan dengan Core Values Harmonis dan Kolaboratif.</li><li><strong>B</strong>: Merupakan nilai Kemenkeu yang diselaraskan dengan Core Values Berorientasi Pelayanan.</li><li><strong>C</strong>: Merupakan nilai Kemenkeu yang diselaraskan dengan Core Values Kompeten.</li><li><strong>D</strong>: Merupakan nilai Kemenkeu yang diselaraskan dengan Core Values Adaptif.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 41) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Akuntabel dan Loyal (Core Values ASN) selaras dengan • Integritas (nilai-nilai Kemenkeu)</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Rumusan Sikap Dasar pada nilai Integritas yang selaras dengan Akuntabel dan Loyal adalah senantiasa...", "options": ["Amanah, jujur, disiplin, konsisten, terpercaya, dan militan", "Santai, fleksibel, kompromistis, serta menyukai ketenangan", "Menunggu instruksi tertulis pimpinan dalam setiap tindakan", "Mengutamakan keuntungan finansial pribadi di atas tugas", "Pasrah menerima segala kekurangan sarana kerja yang ada"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Rumusan Sikap Dasar Nilai Integritas</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sikap Dasar pada nilai Integritas adalah: <strong>senantiasa amanah, jujur, disiplin, konsisten, dapat dipercaya, militan dalam bekerja</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat untuk bunyi rumusan resmi sikap dasar nilai integritas. Militansi dalam bekerja mencerminkan kegigihan pantang menyerah menjaga amanah dan kejujuran di tengah berbagai tekanan tugas kedinasan. Secara representatif dihadirkan sikap dasar ini meliputi amanah, jujur, disiplin, konsisten, dapat dipercaya, dan militan bekerja. Dengan demikian, opsi A adalah rumusan yang tepat.</p><p>Jawaban tepat: <strong>A. Amanah, jujur, disiplin, konsisten, terpercaya, dan militan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan kompromi integritas yang berisiko menoleransi penyimpangan aturan.</li><li><strong>C</strong>: Merupakan ketiadaan inisiatif mandiri yang bertolak belakang dengan etos militansi.</li><li><strong>D</strong>: Merupakan sikap materialistis yang bertentangan dengan sumpah jabatan aparatur.</li><li><strong>E</strong>: Merupakan kepasrahan apatis yang menghambat upaya perbaikan kinerja dinas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 41) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Sikap Dasar: senantiasa amanah, jujur, disiplin, konsisten • dapat dipercaya, militan dalam bekerja</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Salah satu butir Perilaku Efektif nilai Integritas terkait kepatuhan aturan mengamanatkan pegawai untuk...", "options": ["Bekerja tertib sewaktu berada di hadapan pimpinan kantor", "Taat aturan meski tanpa pengawasan dengan penuh kesadaran", "Menyederhanakan prosedur baku saat melayani kawan seunit", "Menyerahkan dokumen verifikasi anggaran tanpa ditelaah teliti", "Meminta kelonggaran batas waktu penyelesaian laporan tahunan"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Perilaku Efektif Ketaatan Tanpa Pengawasan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Perilaku Efektif integritas: <strong>Menunjukkan perilaku disiplin diri, memegang teguh tanggungjawab, dan taat aturan meski tanpa pengawasan dengan penuh kesadaran, serta rela berkorban</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat tentang kematangan integritas sejati aparatur sipil negara. Integritas tertinggi dibuktikan saat seseorang tetap konsisten mematuhi hukum dan standar etika meskipun tidak ada kamera pemantau atau atasan yang melihat. Dalam pedoman etika, ketaatan tanpa pengawasan berlandaskan kesadaran batin adalah butir perilaku efektif integritas. Oleh sebab itu, opsi B adalah tindakan yang benar.</p><p>Jawaban tepat: <strong>B. Taat aturan meski tanpa pengawasan dengan penuh kesadaran</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan kepatuhan semu di depan pimpinan yang mencerminkan hipokrisi moral.</li><li><strong>C</strong>: Merupakan dispensasi nepotisme yang melanggar standardisasi operasional kerja.</li><li><strong>D</strong>: Merupakan kecerobohan verifikasi yang membahayakan akuntabilitas keuangan negara.</li><li><strong>E</strong>: Merupakan kebiasaan menunda tugas yang menurunkan disiplin ketuntasan waktu.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 41) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Menunjukkan perilaku disiplin diri, memegang teguh tanggungjawab • dan taat aturan meski tanpa pengawasan dengan penuh kesadaran</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Nilai dasar ASN 'Kompeten' dalam sistem penguatan budaya Kementerian Keuangan diselaraskan dengan nilai...", "options": ["Sinergi", "Pelayanan", "Profesionalisme", "Integritas", "Kesempurnaan"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penyelarasan Core Values Kompeten</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Kompeten (Core Values ASN)</strong> selaras dengan <strong>Profesionalisme (nilai-nilai Kemenkeu)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat untuk pasangan penyelarasan nilai profesionalitas kerja. Kapabilitas keahlian, kemauan belajar tiada henti, dan ketepatan eksekusi tugas dalam nilai Kompeten berpadu utuh dengan prinsip bekerja tuntas dan akurat pada nilai Profesionalisme Kemenkeu. Dalam prinsip dasar, Core Value Kompeten selaras dengan Profesionalisme. Maka dari itu, opsi C adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>C. Profesionalisme</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan nilai Kemenkeu yang menjadi pasangan nilai Harmonis dan Kolaboratif.</li><li><strong>B</strong>: Merupakan nilai Kemenkeu yang dipadankan dengan Core Values Berorientasi Pelayanan.</li><li><strong>D</strong>: Merupakan nilai Kemenkeu yang dipadankan dengan Core Values Akuntabel dan Loyal.</li><li><strong>E</strong>: Merupakan nilai Kemenkeu yang diselaraskan dengan Core Values Adaptif.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 41) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Kompeten (Core Values ASN) selaras dengan • Profesionalisme (nilai-nilai Kemenkeu)</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Sikap Dasar pada nilai Profesionalisme yang selaras dengan nilai Kompeten menuntut pegawai senantiasa...", "options": ["Menunda pembaruan aplikasi sistem informasi perbendaharaan", "Mengerjakan tugas seadanya asal selesai sebelum jam kantor", "Menyimpan pengetahuan baru sebagai rahasia keunggulan diri", "Antusias belajar, berbagi ilmu, cepat akurat, dan terandalkan", "Menyerahkan pembuatan konsep surat dinas kepada staf magang"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Rumusan Sikap Dasar Nilai Profesionalisme</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sikap Dasar pada nilai Profesionalisme adalah: <strong>senantiasa antusias belajar, berbagi ilmu, bertanggung jawab, berinisiatif, cepat dan akurat dalam bekerja serta dapat diandalkan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat untuk rumusan sikap dasar profesionalisme. Semangat mengembangkan diri tidak boleh dinikmati sendiri, melainkan harus ditularkan kepada sesama rekan kerja dengan ketelitian dan keandalan tinggi dalam penyelesaian tugas. Pedoman kerja merinci sikap dasar ini meliputi antusias belajar, berbagi ilmu, berinisiatif, cepat akurat, dan dapat diandalkan. Oleh sebab itu, opsi D adalah rumusan yang benar.</p><p>Jawaban tepat: <strong>D. Antusias belajar, berbagi ilmu, cepat akurat, dan terandalkan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan sikap resisten teknologi yang menghambat efisiensi operasional kantor.</li><li><strong>B</strong>: Merupakan etos kerja asal-asalan yang bertolak belakang dengan profesionalitas.</li><li><strong>C</strong>: Merupakan penimbunan ilmu egoistis yang merusak pembelajaran organisasi bersama.</li><li><strong>E</strong>: Merupakan pelimpahan tanggung jawab yang menunjukkan keengganan berkinerja prima.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 41) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Sikap Dasar: senantiasa antusias belajar, berbagi ilmu, bertanggung jawab • berinisiatif, cepat dan akurat dalam bekerja serta dapat diandalkan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C4 - Analisis", "question": "Perilaku Efektif profesionalisme mengarahkan pegawai untuk menunjukkan prakarsa pencapaian sasaran kerja dengan...", "options": ["Mengesampingkan potensi risiko demi mengejar batas waktu", "Mengandalkan firasat pribadi tanpa memerlukan bukti angka", "Meniru sepenuhnya strategi kerja instansi swasta komersial", "Membatasi koordinasi risiko dengan unit kepatuhan internal", "Memperhitungkan risiko berdasarkan dukungan data dan fakta"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Prakarsa Pencapaian Sasaran Berbasis Risiko</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Perilaku Efektif profesionalisme: <strong>Menunjukkan prakarsa dalam merencanakan dan mencapai rencana serta sasaran kerja dengan memperhitungkan risiko berdasarkan data dan fakta</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat tentang integrasi manajemen risiko dalam eksekusi tugas. Aparatur yang profesional tidak bertindak gegabah atau spekulatif, melainkan selalu mengkaji mitigasi risiko berlandaskan basis data dan fakta yang sahih. Nilai kebersamaan mewajibkan memperhitungkan risiko berbasis data dan fakta dalam perencanaan sasaran. Dengan demikian, opsi E adalah jawaban yang tepat.</p><p>Jawaban tepat: <strong>E. Memperhitungkan risiko berdasarkan dukungan data dan fakta</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan tindakan ceroboh mengejar target yang mengorbankan keselamatan tata kelola.</li><li><strong>B</strong>: Merupakan intuisi subjektif tanpa pembuktian data yang berisiko memicu kegagalan.</li><li><strong>C</strong>: Merupakan adopsi mentah tanpa penyesuaian aturan dan karakteristik sektor publik.</li><li><strong>D</strong>: Merupakan pengabaian sistem kendali yang melanggar prinsip *three lines of defense*.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 41) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Menunjukkan prakarsa dalam merencanakan dan mencapai rencana • serta sasaran kerja dengan memperhitungkan risiko berdasarkan data dan fakta</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Dalam meningkatkan kompetensi profesional, butir kelima Perilaku Efektif mewajibkan aparatur mengembangkan...", "options": ["Cara berkomunikasi efektif baik secara verbal dan nonverbal", "Kemampuan berdebat secara terbuka di berbagai media sosial", "Keahlian menyusun alibi pembelaan diri atas keterlambatan tugas", "Gaya komunikasi yang menonjolkan superioritas jenjang pangkat", "Penggunaan istilah bahasa asing yang sulit dipahami masyarakat"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengembangan Komunikasi Efektif Profesional</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Perilaku Efektif profesionalisme mencakup: <strong>Mengembangkan cara berkomunikasi efektif, baik verbal maupun nonverbal serta kompetensi diri lainnya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat untuk penguasaan keterampilan lunak (*soft skills*) aparatur. Keahlian teknis yang hebat tidak akan optimal jika tidak dibarengi kemampuan menyampaikan gagasan, mendengar masukan, dan berkoordinasi secara santun dan jelas (verbal maupun nonverbal). Dalam panduan perilaku, mengembangkan komunikasi efektif verbal dan nonverbal adalah butir kelima. Maka dari itu, opsi A adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>A. Cara berkomunikasi efektif baik secara verbal dan nonverbal</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan polemik siber yang merusak wibawa dan kesantunan aparatur negara.</li><li><strong>C</strong>: Merupakan tindakan manipulatif yang bertentangan dengan tanggung jawab kejujuran.</li><li><strong>D</strong>: Merupakan gaya feodal hierarkis yang merusak kehangatan relasi kerja modern.</li><li><strong>E</strong>: Merupakan penggunaan jargon rumit yang menghambat pemahaman layanan bagi warga.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 41) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Mengembangkan cara berkomunikasi efektif • baik verbal maupun nonverbal serta kompetensi diri lainnya</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Core Values ASN 'Harmonis dan Kolaboratif' dalam sistem penguatan budaya diselaraskan dengan nilai Kemenkeu...", "options": ["Pelayanan", "Sinergi", "Integritas", "Kesempurnaan", "Profesionalisme"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penyelarasan Harmonis dan Kolaboratif dengan Sinergi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Harmonis dan Kolaboratif (Core Values ASN)</strong> selaras dengan <strong>Sinergi (nilai-nilai Kemenkeu)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat mengenai pemetaan nilai kerja sama. Suasana rukun tanpa diskriminasi (*harmonis*) dan kemitraan aktif lintas fungsi (*kolaboratif*) berpadu sempurna dalam mewujudkan nilai Sinergi Kementerian Keuangan. Sebagaimana dianjurkan dalam etika kerja, Core Values Harmonis dan Kolaboratif selaras dengan Sinergi. Oleh sebab itu, opsi B adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>B. Sinergi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan nilai Kemenkeu yang dipadankan dengan Core Values Berorientasi Pelayanan.</li><li><strong>C</strong>: Merupakan nilai Kemenkeu yang diselaraskan dengan Akuntabel dan Loyal.</li><li><strong>D</strong>: Merupakan nilai Kemenkeu yang diselaraskan dengan Core Values Adaptif.</li><li><strong>E</strong>: Merupakan nilai Kemenkeu yang diselaraskan dengan Core Values Kompeten.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 42) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Harmonis dan Kolaboratif (Core Values ASN) selaras dengan • Sinergi (nilai-nilai Kemenkeu)</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Rumusan Sikap Dasar pada nilai Sinergi yang selaras dengan Harmonis dan Kolaboratif adalah senantiasa...", "options": ["Menunggu instruksi tertulis atasan dalam koordinasi tugas", "Mengerjakan beban tugas mandiri tanpa bantuan pihak lain", "Solid, proaktif, berkolaborasi, dan humanis dalam bertugas", "Mengutamakan kepentingan unit kerja sendiri di atas yang lain", "Menyeragamkan seluruh latar belakang suku anggota tim dinas"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Rumusan Sikap Dasar Nilai Sinergi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sikap Dasar pada nilai Sinergi adalah: <strong>senantiasa solid, proaktif, berkolaborasi, dan humanis</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat untuk rumusan resmi sikap dasar nilai sinergi. Kekompakan tim (*solid*), inisiatif koordinasi (*proaktif*), keterpaduan kerja (*berkolaborasi*), serta perlakuan memanusiakan manusia (*humanis*) merupakan ruh kemitraan di Kemenkeu. Pedoman kerja merinci sikap dasar ini secara tegas. Dengan demikian, opsi C adalah rumusan yang benar.</p><p>Jawaban tepat: <strong>C. Solid, proaktif, berkolaborasi, dan humanis dalam bertugas</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan kepasifan birokrasi yang memperlambat kelincahan koordinasi tim.</li><li><strong>B</strong>: Merupakan individualisme tugas yang bertentangan dengan semangat kemitraan.</li><li><strong>D</strong>: Merupakan ego sektoral unit yang merusak keutuhan sinergi kelembagaan.</li><li><strong>E</strong>: Merupakan penyeragaman latar belakang yang mustahil dalam keberagaman bangsa.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 42) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Sikap Dasar: senantiasa solid, proaktif • berkolaborasi, dan humanis</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C4 - Analisis", "question": "Dalam menghadapi keanekaragaman gagasan dan latar belakang, Perilaku Efektif sinergi menuntut pegawai untuk...", "options": ["Membatasi pertukaran pendapat dengan rekan kerja yang berbeda", "Memaksakan pandangan pribadi agar disepakati seluruh tim rapat", "Menyerahkan penentuan keputusan kepada anggota tim yang senior", "Mengelola perbedaan sebagai peluang solusi dan hasil terbaik", "Membatalkan rencana kerja sama bila terjadi silang pendapat tim"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengelolaan Perbedaan dalam Perilaku Efektif Sinergi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Perilaku Efektif sinergi: <strong>Mampu mengelola perbedaan sebagai peluang untuk memberikan solusi dan hasil terbaik</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat mengenai manajemen konflik konstruktif aparatur. Perbedaan sudut pandang bukanlah ancaman perpecahan, melainkan kekayaan intelektual yang bila diramu dengan bijak akan melahirkan terobosan solusi yang jauh lebih komprehensif. Nilai kebersamaan mewajibkan memandang perbedaan sebagai peluang menghasilkan solusi terbaik. Oleh sebab itu, opsi D adalah sikap yang tepat.</p><p>Jawaban tepat: <strong>D. Mengelola perbedaan sebagai peluang solusi dan hasil terbaik</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan sikap eksklusif yang membatasi kekayaan ide dalam tim kerja.</li><li><strong>B</strong>: Merupakan pemaksaan kehendak otoriter yang merusak iklim musyawarah.</li><li><strong>C</strong>: Merupakan kepasifan nalar yang mengabaikan kontribusi pemikiran kritis.</li><li><strong>E</strong>: Merupakan keputusasaan kerja sama yang menggagalkan pencapaian target dinas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 42) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Mampu mengelola perbedaan sebagai • peluang untuk memberikan solusi dan hasil terbaik</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Core Values ASN 'Berorientasi Pelayanan' dalam pembudayaan Kemenkeu Satu diselaraskan dengan nilai Kemenkeu...", "options": ["Integritas", "Sinergi", "Profesionalisme", "Kesempurnaan", "Pelayanan"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penyelarasan Nilai Pelayanan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Berorientasi Pelayanan (Core Values ASN)</strong> selaras dengan <strong>Pelayanan (nilai-nilai Kemenkeu)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat untuk keselarasan nilai pengabdian kepada masyarakat. Komitmen memberikan kepuasan bagi pemangku kepentingan secara transparan, cepat, akurat, dan aman merupakan inti dari nilai Berorientasi Pelayanan ASN dan nilai Pelayanan Kemenkeu. Sebagaimana dianjurkan dalam etika kerja, kedua nilai ini saling berpadu selaras. Maka dari itu, opsi E adalah jawaban yang tepat.</p><p>Jawaban tepat: <strong>E. Pelayanan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan nilai Kemenkeu yang diselaraskan dengan Akuntabel dan Loyal.</li><li><strong>B</strong>: Merupakan nilai Kemenkeu yang dipadankan dengan Harmonis dan Kolaboratif.</li><li><strong>C</strong>: Merupakan nilai Kemenkeu yang dipadankan dengan Core Values Kompeten.</li><li><strong>D</strong>: Merupakan nilai Kemenkeu yang diselaraskan dengan Core Values Adaptif.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 42) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Berorientasi Pelayanan (Core Values ASN) selaras dengan • Pelayanan (nilai-nilai Kemenkeu)</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Sikap Dasar pada nilai Pelayanan yang selaras dengan Berorientasi Pelayanan mewajibkan pegawai senantiasa...", "options": ["Komunikatif, respektif, responsif, cekatan, dan solutif prima", "Menunggu instruksi tertulis pimpinan sebelum menjawab keluhan", "Membatasi waktu pelayanan pemohon maksimal sepuluh menit saja", "Meminta tanda bukti terima kasih materiil setelah melayani", "Melayani secara selektif pemohon yang memiliki kedudukan tinggi"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Rumusan Sikap Dasar Nilai Pelayanan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sikap Dasar pada nilai Pelayanan adalah: <strong>senantiasa komunikatif, respektif, responsif, cekatan, dan solutif untuk memberikan layanan terbaik</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat untuk bunyi rumusan resmi sikap dasar nilai pelayanan. Keramahan komunikasi, rasa hormat (*respektif*), kesigapan tanggap (*responsif*), kecepatan bertindak (*cekatan*), dan fokus pada pemecahan masalah (*solutif*) membentuk standar prima pelayanan Kemenkeu. Pedoman kerja merinci kelima unsur ini secara utuh. Dengan demikian, opsi A adalah rumusan yang benar.</p><p>Jawaban tepat: <strong>A. Komunikatif, respektif, responsif, cekatan, dan solutif prima</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan kelambanan birokrasi yang memperpanjang ketidakpastian pemohon.</li><li><strong>C</strong>: Merupakan pembatasan kaku durasi yang mengorbankan kualitas ketuntasan solusi.</li><li><strong>D</strong>: Merupakan praktik gratifikasi terlarang yang mencederai etika integritas.</li><li><strong>E</strong>: Merupakan diskriminasi layanan yang bertentangan dengan prinsip keadilan publik.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 42) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Sikap Dasar: senantiasa komunikatif, respektif • responsif, cekatan, dan solutif untuk memberikan layanan terbaik</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Core Values ASN 'Adaptif' dalam KMK Nomor 429/KMK.01/2022 diselaraskan secara konsep dengan nilai Kemenkeu...", "options": ["Integritas", "Kesempurnaan", "Sinergi", "Profesionalisme", "Pelayanan"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penyelarasan Core Values Adaptif</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Adaptif (Core Values ASN)</strong> selaras dengan <strong>Kesempurnaan (nilai-nilai Kemenkeu)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat mengenai pasangan penyelarasan nilai pembaruan berkelanjutan. Kelincahan menyesuaikan diri dengan perubahan zaman, inovasi teknologi, serta perbaikan tiada henti dalam nilai Adaptif menyatu utuh dengan esensi nilai Kesempurnaan Kementerian Keuangan. Pedoman etika menegaskan Core Value Adaptif selaras dengan Kesempurnaan. Oleh sebab itu, opsi B adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>B. Kesempurnaan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan nilai Kemenkeu yang dipadankan dengan Akuntabel dan Loyal.</li><li><strong>C</strong>: Merupakan nilai Kemenkeu yang dipadankan dengan Harmonis dan Kolaboratif.</li><li><strong>D</strong>: Merupakan nilai Kemenkeu yang dipadankan dengan Core Values Kompeten.</li><li><strong>E</strong>: Merupakan nilai Kemenkeu yang dipadankan dengan Core Values Berorientasi Pelayanan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 42) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Adaptif (Core Values ASN) selaras dengan • Kesempurnaan (nilai-nilai Kemenkeu)</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Sikap Dasar pada nilai Kesempurnaan menuntut pegawai di lingkungan kementerian senantiasa...", "options": ["Mempertahankan prosedur kerja lama yang telah berjalan puluhan tahun", "Membatasi pemanfaatan aplikasi kecerdasan buatan di pekerjaan", "Efektif memanfaatkan perubahan, inovatif, transparan, dan korektif", "Menunggu instruksi tertulis menteri sebelum melakukan evaluasi", "Merasa berpuas diri terhadap capaian prestasi kinerja saat ini"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Rumusan Sikap Dasar Nilai Kesempurnaan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sikap Dasar pada nilai Kesempurnaan adalah: <strong>senantiasa efektif memanfaatkan perubahan, inovatif, transparan, dan korektif, menuju kesempurnaan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat untuk rumusan resmi sikap dasar nilai kesempurnaan. Perubahan tidak boleh ditolak, melainkan dimanfaatkan secara cerdas (*efektif*), melahirkan pembaruan (*inovatif*), terbuka (*transparan*), serta berani berbenah diri (*korektif*). Karakteristik adaptif mencakup sikap dasar ini menuju kesempurnaan. Maka dari itu, opsi C adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>C. Efektif memanfaatkan perubahan, inovatif, transparan, dan korektif</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan sikap resisten perubahan yang memicu ketertinggalan organisasi.</li><li><strong>B</strong>: Merupakan penolakan kemajuan teknologi yang bertolak belakang dengan inovasi.</li><li><strong>D</strong>: Merupakan ketergantungan birokratis pasif yang menghambat inisiatif perbaikan.</li><li><strong>E</strong>: Merupakan kepuasan semu yang mematikan semangat perbaikan tiada henti.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 42) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Sikap Dasar: senantiasa efektif memanfaatkan perubahan • inovatif, transparan, dan korektif, menuju kesempurnaan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Dalam rangka perbaikan sistem yang komprehensif, Perilaku Efektif kesempurnaan mengamanatkan pegawai untuk...", "options": ["Menunggu instruksi konsultan luar sebelum melakukan perubahan", "Menyimpan kelemahan sistem agar tidak diketahui unit lain", "Membatasi keterlibatan staf bawahan dalam pembahasan perbaikan", "Mendorong partisipasi aktif mencari peluang perbaikan sistem", "Membatalkan seluruh prosedur kerja lama yang terbukti andal"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Partisipasi Aktif Mencari Peluang Perbaikan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Perilaku Efektif kesempurnaan: <strong>Mendorong partisipasi aktif untuk mencari peluang perbaikan, mengembangkan dan mengimplementasikan cara-cara baru/inovatif dalam rangka perbaikan sistem secara lebih komprehensif</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat tentang pelibatan aktif seluruh insan organisasi dalam penyempurnaan sistem. Peluang perbaikan tidak hanya datang dari pimpinan, melainkan lahir dari kepekaan para staf di lini operasional yang berhadapan langsung dengan kendala harian. Pedoman kerja menegaskan pentingnya partisipasi aktif mencari peluang perbaikan. Oleh sebab itu, opsi D adalah tindakan yang tepat.</p><p>Jawaban tepat: <strong>D. Mendorong partisipasi aktif mencari peluang perbaikan sistem</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan ketergantungan pihak eksternal yang melemahkan kemandirian instansi.</li><li><strong>B</strong>: Merupakan penutupan masalah yang berisiko memperparah inefisiensi sistemik.</li><li><strong>C</strong>: Merupakan eksklusivisme manajerial yang membatasi kekayaan masukan lapangan.</li><li><strong>E</strong>: Merupakan perombakan gegabah yang merusak stabilitas operasional yang sudah baik.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 43) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Mendorong partisipasi aktif untuk mencari peluang perbaikan • dalam rangka perbaikan sistem secara lebih komprehensif</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Perilaku Efektif kesempurnaan menuntut pegawai tidak sekadar berwacana, melainkan mampu...", "options": ["Menunda penyesuaian regulasi baru dari kementerian teknis", "Membuat laporan fiktif penyerapan anggaran inovasi kantor", "Menghabiskan anggaran riset tanpa menghasilkan dokumen output", "Menyerahkan pembuatan aplikasi kantor ke pihak swasta tanpa uji", "Mengembangkan dan mengimplementasikan cara-cara inovatif"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Implementasi Cara Baru dan Inovatif</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai wajib: <strong>mengembangkan dan mengimplementasikan cara-cara baru/inovatif dalam rangka perbaikan sistem secara lebih komprehensif</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat mengenai eksekusi nyata inovasi birokrasi. Gagasan cemerlang hanya akan memberi dampak apabila diuji coba, dikembangkan, dan diterapkan secara konsisten dalam alur kerja nyata organisasi. Dalam prinsip dasar, inisiatif inovasi diwujudkan dengan cara-cara baru yang inovatif adalah intinya. Dengan demikian, opsi E adalah wujud nyata yang diharapkan.</p><p>Jawaban tepat: <strong>E. Mengembangkan dan mengimplementasikan cara-cara inovatif</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan pembangkangan regulasi yang bertentangan dengan kepatuhan hukum.</li><li><strong>B</strong>: Merupakan tindak pidana korupsi pemalsuan laporan yang melanggar integritas.</li><li><strong>C</strong>: Merupakan pemborosan kas negara yang tidak mendatangkan nilai tambah publik.</li><li><strong>D</strong>: Merupakan pelepasan kendali sistem informasi yang berisiko membahayakan keamanan data.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 43) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>mengembangkan dan mengimplementasikan cara-cara baru/inovatif • dalam rangka perbaikan sistem secara lebih komprehensif</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Dalam melakukan evaluasi perbaikan berkelanjutan, langkah objektif yang wajib ditempuh aparatur adalah...", "options": ["Membaca, menganalisis, dan memanfaatkan data informasi", "Mengandalkan perkiraan subjektif tanpa memeriksa dokumen", "Meminta bantuan peramal nasib untuk memprediksi anggaran", "Meniru data laporan keuangan kantor lain di daerah berbeda", "Menghapus data anomali yang menunjukkan adanya kendala"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pemanfaatan Data bagi Perbaikan Berkelanjutan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Perilaku Efektif kesempurnaan mencakup: <strong>Membaca, menganalisis, dan memanfaatkan data dan informasi guna perbaikan pekerjaan secara terus menerus</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat tentang budaya pengambilan keputusan berbasis data (*data-driven decision making*). Perbaikan kualitas kerja harus didasarkan pada telaah angka, tren statistik, dan rekaman informasi yang akurat agar solusi yang dirumuskan tepat sasaran. Dalam pedoman etika, peningkatan mutu dicapai dengan membaca, menganalisis, dan data adalah tindakan yang diwajibkan. Oleh sebab itu, opsi A adalah langkah yang benar.</p><p>Jawaban tepat: <strong>A. Membaca, menganalisis, dan memanfaatkan data informasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan analisis spekulatif tanpa fakta yang berisiko menghasilkan kebijakan keliru.</li><li><strong>C</strong>: Merupakan tindakan irasional yang bertentangan dengan kaidah keilmuan profesional.</li><li><strong>D</strong>: Merupakan manipulasi duplikasi data yang melanggar kejujuran penyusunan laporan.</li><li><strong>E</strong>: Merupakan pembersihan data sepihak yang mengaburkan kelemahan riil di lapangan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 43) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Membaca, menganalisis, dan memanfaatkan data dan informasi • guna perbaikan pekerjaan secara terus menerus</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Perilaku Efektif pada nilai kesempurnaan menuntut pegawai memiliki visi yang maju agar mampu...", "options": ["Memperoleh fasilitas kendaraan dinas operasional terbaru", "Menyusun rencana yang relevan dan matang serta evaluatif", "Membatasi penugasan dinas di wilayah daerah-daerah terpencil", "Mendapatkan promosi kenaikan pangkat tanpa proses seleksi", "Menyederhanakan pertanggungjawaban kuitansi belanja dinas"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Visi Maju dalam Penyusunan Rencana Kerja</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai wajib: <strong>Memiliki visi yang maju agar mampu menyusun rencana yang relevan dan matang, serta memeriksa hasil pekerjaan yang dilakukan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat tentang perencanaan kerja visioner aparatur. Pandangan jauh ke depan memungkinkan pegawai mengantisipasi dinamika fiskal, perkembangan teknologi, dan tuntutan masyarakat sehingga program kerja yang dirancang berbobot dan matang. Visi maju birokrasi ditujukan ditujukan agar mampu menyusun rencana relevan dan memeriksa hasilnya. Maka dari itu, opsi B adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>B. Menyusun rencana yang relevan dan matang serta evaluatif</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan tuntutan fasilitas fisik kedinasan yang bukan esensi visi kemajuan.</li><li><strong>C</strong>: Merupakan keengganan berkorban yang bertentangan dengan kesiapan bertugas ASN.</li><li><strong>D</strong>: Merupakan keinginan promosi tanpa meritokrasi yang mencederai keadilan karier.</li><li><strong>E</strong>: Merupakan pengurangan standar akuntabilitas yang membahayakan tata kelola kas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 43) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Memiliki visi yang maju agar mampu menyusun rencana • yang relevan dan matang, serta memeriksa hasil pekerjaan yang dilakukan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Selain menyusun rencana kerja matang, kebiasaan profesional yang wajib dipraktikkan pegawai adalah...", "options": ["Melimpahkan seluruh pekerjaan verifikasi kepada atasan unit", "Menerima kekeliruan kecil dalam penyusunan lembar laporan", "Memeriksa secara teliti hasil pekerjaan yang telah dilakukan", "Mengirimkan draf dokumen ke pihak luar sebelum diperiksa pimpinan", "Menyimpan berkas tanpa melakukan rekapitulasi data akhir"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pemeriksaan Hasil Pekerjaan Sendiri</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai dituntut memiliki visi yang maju serta senantiasa <strong>memeriksa hasil pekerjaan yang dilakukan</strong> guna menjamin kualitas prima.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat tentang pengendalian mutu mandiri (*self-control*). Sebelum diserahkan kepada pimpinan atau dipublikasikan, produk kerja wajib ditelaah ulang untuk memastikan ketiadaan kesalahan ketik, akurasi perhitungan, dan keselarasan aturan. Pedoman kerja menegaskan pentingnya memeriksa hasil pekerjaan yang dilakukan. Oleh sebab itu, opsi C adalah tindakan profesional yang benar.</p><p>Jawaban tepat: <strong>C. Memeriksa secara teliti hasil pekerjaan yang telah dilakukan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan pelemparan tugas verifikasi dasar yang membebani rantai komando atasan.</li><li><strong>B</strong>: Merupakan sikap permisif terhadap kekeliruan yang menurunkan wibawa kualitas kantor.</li><li><strong>D</strong>: Merupakan pelanggaran kerahasiaan draf dinas yang belum sah disetujui pimpinan.</li><li><strong>E</strong>: Merupakan kelalaian penatausahaan berkas yang merusak kerapian administrasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 43) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Memiliki visi yang maju agar mampu menyusun rencana yang relevan dan matang, serta • memeriksa hasil pekerjaan yang dilakukan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C4 - Analisis", "question": "Tujuan akhir dari kebiasaan membaca dan menganalisis data informasi di lingkungan kerja adalah...", "options": ["Menemukan celah hukum demi kepentingan kelompok tertentu", "Menghambat pelaksanaan proyek strategis instansi lain", "Memperpanjang proses birokrasi pencairan anggaran kantor", "Perbaikan pekerjaan secara terus-menerus dan berkelanjutan", "Mengurangi alokasi bantuan sosial bagi warga masyarakat"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuan Akhir Analisis Data Informasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Membaca, menganalisis, dan memanfaatkan data dan informasi dilakukan <strong>guna perbaikan pekerjaan secara terus menerus</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat mengenai orientasi telaah data dalam manajemen Kemenkeu. Pengumpulan data tidak boleh berhenti sebagai tumpukan arsip statistik, melainkan harus diolah menjadi bahan bakar perbaikan mutu layanan, efisiensi anggaran, dan inovasi kerja berkesinambungan. Pedoman etika menegaskan bahwa tujuan akhirnya adalah perbaikan pekerjaan terus-menerus. Dengan demikian, opsi D adalah jawaban yang tepat.</p><p>Jawaban tepat: <strong>D. Perbaikan pekerjaan secara terus-menerus dan berkelanjutan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan pencarian celah hukum menyimpang yang melanggar nilai integritas.</li><li><strong>B</strong>: Merupakan hambatan kerja sama antarlembaga yang merusak semangat sinergi.</li><li><strong>C</strong>: Merupakan perlambatan birokrasi yang bertolak belakang dengan nilai pelayanan.</li><li><strong>E</strong>: Merupakan pemotongan sepihak hak publik yang mencederai keadilan sosial.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 43) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Membaca, menganalisis, dan memanfaatkan data dan informasi • guna perbaikan pekerjaan secara terus menerus</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Karakteristik rencana kerja yang bermutu dalam perencanaan dan pelaksanaan kerja harus bersifat...", "options": ["Tetap dan tidak boleh disesuaikan dengan situasi darurat", "Teoretis tanpa mempertimbangkan ketersediaan sumber daya", "Seragam di seluruh kantor dinas tanpa melihat potensi lokal", "Tertutup dan khusus diketahui oleh perencana tunggal kantor", "Relevan dengan kebutuhan organisasi serta disusun matang"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Karakter Rencana Kerja yang Bermutu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Visi yang maju menuntun pegawai agar mampu menyusun rencana yang <strong>relevan dan matang</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat tentang tolok ukur kualitas perencanaan program. Rencana yang baik wajib menjawab kebutuhan nyata instansi (*relevan*) serta diperhitungkan secara cermat tahapan, anggaran, dan mitigasi risikonya (*matang*). Perencanaan yang matang mencakup kedua sifat tersebut secara berdampingan. Oleh sebab itu, opsi E adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>E. Relevan dengan kebutuhan organisasi serta disusun matang</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan kekakuan rencana yang menghalangi kemampuan adaptasi situasi krisis.</li><li><strong>B</strong>: Merupakan utopia teoretis yang gagal diimplementasikan di dunia kerja nyata.</li><li><strong>C</strong>: Merupakan penyeragaman buta yang menafikan keunikan tantangan geografis daerah.</li><li><strong>D</strong>: Merupakan ketertutupan rencana yang menghambat kolaborasi tim kerja pelaksana.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 43) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menyusun rencana • yang relevan dan matang</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Regulasi yang mengatur pedoman pelaksanaan penguatan budaya di lingkungan Kementerian Keuangan diterbitkan melalui...", "options": ["Surat Edaran Nomor SE-15/MK.1/2022", "Keputusan Menteri Keuangan Nomor KMK-577/2019", "Peraturan Menteri Keuangan Nomor PMK-190/2018", "Instruksi Menteri Keuangan Nomor IMK-235/2020", "Surat Edaran Sekretaris Jenderal Nomor SE-08/2021"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dasar Hukum Penguatan Budaya Kementerian Keuangan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Penguatan budaya di Kementerian Keuangan diatur melalui <strong>Surat Edaran Nomor SE-15/MK.1/2022</strong> tentang <strong>Panduan Pelaksanaan Penguatan Budaya</strong> di lingkungan Kementerian Keuangan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang benar mengenai instrumen kebijakan penguatan budaya organisasi. Menteri Keuangan menerbitkan Surat Edaran Nomor SE-15/MK.1/2022 sebagai panduan resmi pelaksanaan penguatan budaya bagi seluruh unit. Ketentuan ini menjadi landasan terpadu dalam menumbuhkan sikap dasar dan perilaku efektif aparatur. Oleh karena itu, SE-15/MK.1/2022 adalah dasar regulasi yang tepat.</p><p>Jawaban tepat: <strong>A. Surat Edaran Nomor SE-15/MK.1/2022</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: KMK-577/2019 mengatur tentang manajemen kinerja pegawai dan bukan panduan teknis penguatan budaya SE-15/MK.1/2022.</li><li><strong>C</strong>: PMK-190/2018 mengatur tentang kode etik dan kode perilaku pegawai negeri sipil di lingkungan Kementerian Keuangan.</li><li><strong>D</strong>: Instruksi Menteri Keuangan Nomor IMK-235/2020 bukan merupakan dasar aturan panduan pelaksanaan penguatan budaya kerja.</li><li><strong>E</strong>: Surat Edaran Sekretaris Jenderal Nomor SE-08/2021 bukan regulasi tingkat menteri tentang penguatan budaya Kemenkeu.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 44) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>SE-15/MK.1/2022 • Panduan Pelaksanaan Penguatan Budaya</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Menurut SE-15/MK.1/2022, panduan pelaksanaan penguatan budaya kerja ditujukan secara khusus kepada...", "options": ["Seluruh staf pelaksana dan calon pegawai baru", "Pimpinan unit dan unit pengelola kepegawaian", "Para widyaiswara dan pengelola diklat keuangan", "Auditor kepatuhan internal dan pengawas bidang", "Duta transformasi dan perwakilan serikat kerja"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sasaran Panduan Penguatan Budaya Organisasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Surat Edaran Nomor SE-15/MK.1/2022 ditujukan sebagai <strong>panduan pimpinan unit dan unit pengelola kepegawaian</strong> untuk melakukan upaya menumbuhkan dan membudayakan <strong>Sikap Dasar dan Perilaku Efektif</strong> pegawai.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat sesuai substansi SE-15/MK.1/2022. Dokumen panduan tersebut dirancang khusus agar pimpinan unit dan pengelola kepegawaian mampu memandu internalisasi nilai budaya di unit masing-masing. Pimpinan memegang peranan kunci dalam keteladanan serta pembinaan perilaku aparatur. Dengan demikian, pimpinan unit dan unit pengelola kepegawaian adalah sasaran utama panduan tersebut.</p><p>Jawaban tepat: <strong>B. Pimpinan unit dan unit pengelola kepegawaian</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Staf pelaksana dan calon pegawai merupakan target pembudayaan dan bukan pihak yang secara khusus dituju sebagai pemandu utama.</li><li><strong>C</strong>: Para widyaiswara berperan dalam ranah pembelajaran kedinasan dan bukan sasaran spesifik dari penerbitan panduan unit SE-15/MK.1/2022.</li><li><strong>D</strong>: Auditor kepatuhan internal bertugas melakukan pengawasan kode etik dan bukan sasaran utama panduan pimpinan unit.</li><li><strong>E</strong>: Duta transformasi bertindak sebagai katalis perubahan dan bukan pihak penerima mandat langsung tata kelola unit kepegawaian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 44) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>panduan pimpinan unit dan unit pengelola kepegawaian • Sikap Dasar dan Perilaku Efektif</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Penumbuhan Sikap Dasar dan Perilaku Efektif pegawai Kemenkeu bertujuan strategis sebagai...", "options": ["Pemenuhan syarat administratif penyusunan sasaran kinerja pegawai", "Penyelarasan standar operasional prosedur teknis perbendaharaan negara", "Perwujudan Core Values ASN BerAKHLAK dan nilai Kementerian Keuangan", "Penggantian seluruh tata kelola pengawasan disiplin aparatur sipil", "Pembentukan unit kepatuhan internal terpadu di setiap kantor daerah"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuan Pembudayaan Sikap Dasar dan Perilaku Efektif</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Upaya pembudayaan sikap dasar dan perilaku efektif dilakukan sebagai <strong>perwujudan Core Values ASN BerAKHLAK</strong> dan nilai-nilai Kementerian Keuangan guna <strong>memperkuat budaya di lingkungan Kementerian Keuangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat mengenai tujuan mendasar penguatan budaya. Penumbuhan sikap dasar dan perilaku efektif diformulasikan untuk menyelaraskan nilai luhur instansi dengan fondasi Core Values BerAKHLAK. Hal ini bertujuan memperkuat karakter aparatur demi tercapainya kinerja yang prima dan berintegritas. Oleh karena itu, perwujudan Core Values BerAKHLAK dan nilai Kemenkeu adalah tujuan intinya.</p><p>Jawaban tepat: <strong>C. Perwujudan Core Values ASN BerAKHLAK dan nilai Kementerian Keuangan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Bukan sekadar formalitas administratif sasaran kinerja, melainkan pembentukan budaya kerja dan internalisasi nilai yang nyata.</li><li><strong>B</strong>: Penyelarasan SOP teknis anggaran perbendaharaan adalah kegiatan proses bisnis dan bukan tujuan penumbuhan sikap dasar pegawai.</li><li><strong>D</strong>: Sikap dasar dan perilaku efektif memperkuat penegakan kode etik yang sudah ada dan bukan menggantikan tata kelola disiplin PNS.</li><li><strong>E</strong>: Pembentukan UKI merupakan langkah penguatan kelembagaan pengawasan dan bukan tujuan utama dari perumusan sikap dasar pegawai.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 44) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>perwujudan Core Values ASN BerAKHLAK • memperkuat budaya di lingkungan Kementerian Keuangan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Target tingkat kematangan minimal bagi mahasiswa PKN STAN dan CPNS atau pelaksana kurang dari satu tahun adalah...", "options": ["Mengajak pihak eksternal mempraktikkan nilai budaya", "Menjadi teladan utama penggerak etika birokrasi", "Menerapkan seluruh sikap dasar secara komprehensif", "Sadar pentingnya sikap dasar atau aware the values", "Mengembangkan sistem evaluasi integritas organisasi"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tingkat Kematangan Mahasiswa PKN STAN dan CPNS</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Target tingkat kematangan minimal bagi <strong>Mahasiswa PKN STAN</strong> dan <strong>CPNS/Pelaksana kurang dari 1 tahun</strong> adalah <strong>Sadar pentingnya Sikap Dasar dan Perilaku Efektif (aware the values)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat sesuai matriks tingkat kematangan budaya. Pada jenjang awal bagi mahasiswa dan calon pegawai baru, sasaran pembinaan difokuskan pada penanaman kesadaran akan arti penting nilai instansi. Kesadaran ini menjadi fondasi awal sebelum melangkah ke tahap pemahaman dan penerapan menyeluruh. Dengan demikian, sadar pentingnya sikap dasar (aware the values) adalah target minimalnya.</p><p>Jawaban tepat: <strong>D. Sadar pentingnya sikap dasar atau aware the values</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Mengajak pihak eksternal menerapkan nilai merupakan tingkatan kepemilikan nilai (owning the values) bagi Pejabat Pimpinan Tinggi.</li><li><strong>B</strong>: Menjadi teladan utama penggerak etika merupakan peran bagi pejabat struktural dan pimpinan unit organisasi.</li><li><strong>C</strong>: Menerapkan seluruh sikap dasar (implementing the values) merupakan target kematangan minimal bagi Pejabat Pengawas dan Administrator.</li><li><strong>E</strong>: Mengembangkan sistem evaluasi organisasi merupakan tanggung jawab unit perumus kebijakan dan bukan target mahasiswa atau CPNS.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 44) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Mahasiswa PKN STAN • aware the values</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Bentuk strategi internalisasi budaya bagi kelompok mahasiswa PKN STAN dan pegawai baru Kemenkeu dilaksanakan melalui...", "options": ["Penerapan sistem evaluasi berkala kinerja pimpinan unit", "Pembuatan klausul pakta integritas bersama mitra kerja", "Pelaksanaan sertifikasi profesi pengawas anggaran negara", "Pengangkatan langsung menjadi pejabat pengelola kepatuhan", "Penguatan budaya dalam kurikulum serta pelatihan dasar"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Strategi Internalisasi Budaya Mahasiswa dan CPNS</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Strategi internalisasi untuk mahasiswa PKN STAN dan CPNS meliputi penguatan budaya dalam <strong>kurikulum PKN STAN</strong>, program habituasi budaya, dan penguatan melalui <strong>pelatihan dasar CPNS/ pegawai baru Kemenkeu</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat mengenai strategi internalisasi bagi pemula. Mahasiswa dan calon pegawai diperkenalkan pada nilai-nilai instansi secara terstruktur melalui kurikulum perkuliahan dan diklat prajabatan. Strategi ini memastikan penanaman nilai berlangsung sejak masa pendidikan hingga awal pengangkatan. Oleh karena itu, penguatan kurikulum dan pelatihan dasar adalah strategi yang tepat.</p><p>Jawaban tepat: <strong>E. Penguatan budaya dalam kurikulum serta pelatihan dasar</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Evaluasi kinerja pimpinan unit ditujukan bagi jenjang manajerial dan bukan strategi pembinaan mahasiswa serta CPNS.</li><li><strong>B</strong>: Klausul integritas bersama mitra kerja merupakan strategi eksternalisasi bagi Pejabat Pimpinan Tinggi.</li><li><strong>C</strong>: Sertifikasi profesi pengawas merupakan peningkatan kompetensi teknis lanjutan pegawai dan bukan internalisasi budaya dasar.</li><li><strong>D</strong>: Pengangkatan pejabat kepatuhan diperuntukkan bagi pejabat senior dan bukan strategi bagi mahasiswa serta pegawai baru.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 44) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>kurikulum PKN STAN • pelatihan dasar CPNS/ pegawai baru</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Program pembiasaan nilai yang menjadi salah satu strategi internalisasi budaya bagi CPNS dan mahasiswa PKN STAN adalah...", "options": ["Program Habituasi Budaya", "Program Magang Struktural", "Program Audit Kepegawaian", "Program Konseling Jabatan", "Program Rotasi Kedinasan"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Program Habituasi Budaya</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Salah satu strategi internalisasi budaya bagi <strong>Mahasiswa PKN STAN</strong> dan CPNS adalah pelaksanaan <strong>Program Habituasi Budaya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat sesuai rincian strategi internalisasi nilai. Program habituasi dirancang untuk membiasakan peserta mengamalkan sikap dasar dan perilaku efektif dalam kehidupan sehari-hari dan masa penugasan. Pembiasaan berulang membentuk pola pikir dan tindakan yang selaras dengan nilai instansi. Dengan demikian, Program Habituasi Budaya adalah bentuk program yang dimaksud.</p><p>Jawaban tepat: <strong>A. Program Habituasi Budaya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Program Magang Struktural bukan nama program pembiasaan nilai yang tercantum dalam strategi internalisasi pedoman kerja.</li><li><strong>C</strong>: Program Audit Kepegawaian merupakan fungsi pengawasan regulasi dan bukan program pembudayaan nilai bagi mahasiswa.</li><li><strong>D</strong>: Program Konseling Jabatan adalah kegiatan bimbingan karir pegawai dan bukan strategi internalisasi budaya di panduan strategi pembudayaan.</li><li><strong>E</strong>: Program Rotasi Kedinasan adalah kebijakan mutasi jabatan dan bukan program habituasi penanaman sikap dasar.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 44) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Program Habituasi Budaya • Mahasiswa PKN STAN</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Strategi eksternalisasi budaya yang dirancang bagi mahasiswa PKN STAN dan pegawai baru dilaksanakan dalam bentuk...", "options": ["Pembentukan pos pengaduan layanan terpadu instansi", "Tugas desain grafis atau video sosialisasi eksternal", "Penyelenggaraan forum dengar pendapat bersama publik", "Publikasi laporan berkala kepatuhan etika aparatur", "Penandatanganan nota kesepahaman pengawasan bersama"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Strategi Eksternalisasi Mahasiswa dan CPNS</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Strategi eksternalisasi bagi mahasiswa PKN STAN dan CPNS adalah kompetisi atau <strong>tugas desain grafis atau video</strong> terkait budaya sebagai bahan <strong>sosialisasi kepada pihakpihak eksternal</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat mengenai wujud eksternalisasi bagi pemula. Mahasiswa dan pegawai baru dilibatkan dalam pembuatan konten kreatif seperti desain grafis dan video budaya untuk disebarluaskan kepada publik. Pendekatan ini melatih kreativitas sekaligus menyebarkan nilai positif Kemenkeu ke ranah eksternal. Oleh karena itu, tugas desain grafis atau video sosialisasi eksternal adalah strategi yang tepat.</p><p>Jawaban tepat: <strong>B. Tugas desain grafis atau video sosialisasi eksternal</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pos pengaduan terpadu merupakan sarana pelayanan publik dan bukan penugasan kreatif eksternalisasi mahasiswa.</li><li><strong>C</strong>: Forum dengar pendapat publik diselenggarakan oleh pejabat pimpinan unit dan bukan tugas eksternalisasi mahasiswa.</li><li><strong>D</strong>: Publikasi laporan kepatuhan merupakan wewenang Inspektorat Jenderal dan bukan tugas penugasan kreatif bagi CPNS.</li><li><strong>E</strong>: Penandatanganan nota kesepahaman kerja sama adalah kewenangan Pejabat Pimpinan Tinggi dan bukan mahasiswa STAN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 44) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>tugas desain grafis atau video • sosialisasi kepada pihakpihak eksternal</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Target tingkat kematangan minimal bagi pegawai pelaksana dengan masa kerja satu tahun atau lebih adalah...", "options": ["Memimpin pembentukan regulasi kode perilaku instansi", "Mengembangkan seluruh instrumen audit kepatuhan kerja", "Paham pentingnya dan penerapan sebagian sikap dasar", "Menjadi penasihat utama perumusan tata kelola nilai", "Mengorganisasi kerja sama lintas kementerian lembaga"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tingkat Kematangan Pelaksana Masa Kerja 1 Tahun</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Target tingkat kematangan minimal bagi <strong>Pelaksana masa kerja 1 tahun atau lebih</strong> adalah <strong>Paham pentingnya dan Penerapan sebagian Sikap Dasar dan Perilaku Efektif (understand the values)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat sesuai panduan strategi pembudayaan. Pegawai yang telah berdinas lebih dari satu tahun diharapkan telah melampaui fase pengenalan dasar dan mulai memahami serta menerapkan nilai-nilai kerja. Penerapan sebagian sikap dasar ini menjadi bukti internalisasi dalam pekerjaan harian. Oleh karena itu, target minimalnya adalah understand the values.</p><p>Jawaban tepat: <strong>C. Paham pentingnya dan penerapan sebagian sikap dasar</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merumuskan regulasi kode perilaku adalah wewenang unit pengelola kepegawaian kementerian dan bukan staf pelaksana.</li><li><strong>B</strong>: Instrumen audit kepatuhan kerja disusun oleh Inspektorat Jenderal dan bukan tanggung jawab pelaksana.</li><li><strong>D</strong>: Peran penasihat utama tata kelola nilai diemban oleh pejabat pimpinan tinggi instansi.</li><li><strong>E</strong>: Pengorganisasian kerja sama kementerian dan lembaga merupakan tugas pimpinan struktural kedinasan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 45) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pelaksana masa kerja 1 tahun atau lebih • understand the values</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Penggerak internalisasi budaya bagi pelaksana yang bertindak sebagai agen perubahan di unit kerja adalah...", "options": ["Petugas pengelola aset kantor", "Pejabat pemeriksa kas bendahara", "Pengawas urusan rumah tangga", "Duta Transformasi unit kerja", "Petugas keamanan gedung dinas"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Peran Duta Transformasi dalam Internalisasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Salah satu bentuk strategi internalisasi bagi pelaksana adalah pelaksanaan <strong>Internalisasi oleh Duta Transformasi</strong> di lingkungan kerja.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat mengenai figur agen penggerak budaya. Duta Transformasi dipilih dan dilatih khusus untuk menjadi panutan serta penyambung pesan perubahan budaya kepada rekan sejawat. Peran aktif mereka memastikan pesan dan nilai organisasi tersampaikan secara komunikatif. Dengan demikian, Duta Transformasi adalah pihak yang tepat.</p><p>Jawaban tepat: <strong>D. Duta Transformasi unit kerja</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Petugas pengelola aset berfokus pada administrasi barang milik negara dan bukan agen khusus pembudayaan nilai.</li><li><strong>B</strong>: Pemeriksa kas bertugas melakukan verifikasi pembukuan keuangan negara dan bukan duta penggerak budaya.</li><li><strong>C</strong>: Pengawas rumah tangga mengurusi kebersihan dan ketertiban fasilitas kantor semata.</li><li><strong>E</strong>: Petugas keamanan gedung bertugas menjaga keamanan fisik lingkungan kantor secara umum.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 45) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Internalisasi oleh Duta Transformasi • Pelaksana masa kerja 1 tahun atau lebih</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Unit kerja yang diperkuat perannya dalam strategi institusionalisasi budaya kerja kementerian adalah...", "options": ["Biro Organisasi dan Tata Laksana Sekretariat Jenderal", "Pusat Pendidikan dan Pelatihan Keuangan Terpadu", "Badan Standardisasi dan Kebijakan Fiskal Negara", "Komite Pengawas Perpajakan dan Bea Cukai Publik", "Inspektorat Jenderal dan Unit Kepatuhan Internal"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Institusionalisasi Peran Pengawasan Internal</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Strategi institusionalisasi budaya mencakup <strong>Penguatan peran Inspektorat Jenderal, Unit Kepatuhan Internal (UKI), dan Kehumasan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat sesuai rincian strategi institusionalisasi dalam etika kedinasan. Penguatan peran pengawasan internal sangat penting untuk menjamin kepatuhan pegawai terhadap kode etik dan nilai kementerian. Inspektorat Jenderal dan UKI bertindak sebagai garda terdepan pengawalan integritas. Oleh karena itu, penguatan Itjen dan UKI adalah langkah institusionalisasi yang tepat.</p><p>Jawaban tepat: <strong>E. Inspektorat Jenderal dan Unit Kepatuhan Internal</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Biro Organisasi dan Tata Laksana menangani struktur organisasi umum dan bukan fokus utama butir institusionalisasi pengawasan.</li><li><strong>B</strong>: Pusdiklat bertugas menyelenggarakan pelatihan kedinasan dan bukan institusi pengawasan kepatuhan etika.</li><li><strong>C</strong>: Badan Kebijakan Fiskal bertugas merumuskan kebijakan fiskal negara dan bukan pengawas perilaku internal pegawai.</li><li><strong>D</strong>: Komite Pengawas Perpajakan mengawasi kebijakan penerimaan negara dan bukan instrumen internalisasi perilaku aparatur.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 45) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Penguatan peran Inspektorat Jenderal • Unit Kepatuhan Internal (UKI)</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Dokumen formal perwujudan komitmen moral pegawai yang dibuat dalam strategi institusionalisasi adalah...", "options": ["Pakta Integritas aparatur sipil", "Berita Acara Rekonsiliasi Kas", "Surat Pernyataan Tanggung Jawab", "Lembar Konfirmasi Kinerja Pegawai", "Catatan Hasil Pengawasan Rutin"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pakta Integritas dalam Institusionalisasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Salah satu bentuk nyata strategi <strong>Institusionalisasi</strong> budaya bagi aparatur Kementerian Keuangan adalah <strong>Membuat Pakta Integritas</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat mengenai instrumen formal komitmen etika. Pakta Integritas mengikat setiap pegawai secara moral dan hukum untuk menjaga integritas dan menjauhi penyimpangan. Penandatanganan dokumen ini memperkuat pelembagaan nilai kejujuran di setiap unit kerja. Dengan demikian, Pakta Integritas adalah instrumen yang tepat.</p><p>Jawaban tepat: <strong>A. Pakta Integritas aparatur sipil</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Berita Acara Rekonsiliasi Kas merupakan dokumen pembukuan teknis bendahara pengeluaran.</li><li><strong>C</strong>: Surat Pernyataan Tanggung Jawab berkaitan dengan pertanggungjawaban penggunaan anggaran belanja.</li><li><strong>D</strong>: Lembar Konfirmasi Kinerja Pegawai mencatat capaian target output sasaran kinerja bulanan.</li><li><strong>E</strong>: Catatan Hasil Pengawasan Rutin merupakan dokumen kerja audit operasional kantor.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 45) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Membuat Pakta Integritas • Institusionalisasi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Target tingkat kematangan minimal bagi Pejabat Pengawas dan Administrator di Kementerian Keuangan adalah...", "options": ["Sadar akan arti penting etika atau aware the values", "Penerapan seluruh sikap dasar atau implementing values", "Menggagas regulasi baru kepatuhan aparatur negara", "Membentuk panitia pemilihan duta transformasi unit", "Mengawasi pelaksanaan belanja kementerian dan lembaga"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tingkat Kematangan Pejabat Pengawas dan Administrator</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Target tingkat kematangan minimal bagi <strong>Pejabat Pengawas/ Administrator /Pejabat fungsional setara</strong> adalah <strong>Penerapan seluruh Sikap Dasar dan Perilaku Efektif (implementing the values)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat mengenai tingkat kematangan level manajerial menengah. Pejabat Pengawas dan Administrator dituntut untuk mempraktikkan seluruh sikap dasar dalam kepemimpinan dan tugas pengawasan harian. Mereka menjadi jembatan operasional penerapan budaya kerja di unitnya. Oleh karena itu, target minimalnya adalah implementing the values.</p><p>Jawaban tepat: <strong>B. Penerapan seluruh sikap dasar atau implementing values</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Sadar pentingnya nilai (aware the values) adalah target minimal mahasiswa PKN STAN dan CPNS.</li><li><strong>C</strong>: Menggagas regulasi baru kepatuhan merupakan wewenang tingkat pimpinan tinggi pembuat kebijakan.</li><li><strong>D</strong>: Membentuk panitia duta transformasi adalah kegiatan teknis operasional kepegawaian.</li><li><strong>E</strong>: Pengawasan pelaksanaan belanja merupakan tugas substantif perbendaharaan dan bukan tingkat kematangan budaya.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 45) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pejabat Pengawas/ Administrator • implementing the values</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Salah satu strategi eksternalisasi bagi Pejabat Pengawas dan Administrator di lingkungan kerjanya adalah...", "options": ["Menyusun rancangan undang-undang keuangan negara", "Melakukan pemeriksaan fisik kas perbendaharaan daerah", "Menjadi role model budaya bagi unit kerja terkecilnya", "Menerbitkan sertifikat kepatuhan laporan tahunan dinas", "Memfasilitasi pendaftaran beasiswa studi lanjut pegawai"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Peran Keteladanan Pejabat Pengawas dan Administrator</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Strategi eksternalisasi bagi Pejabat Pengawas/Administrator meliputi implementasi budaya dan <strong>menjadi role model bagi unit terkecilnya</strong> serta melakukan aksi nyata penerapan budaya.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat mengenai peran keteladanan pejabat pengawas dan administrator. Sebagai pimpinan langsung di seksi atau bidangnya, perilaku mereka menjadi cerminan langsung bagi staf bawahan. Keteladanan nyata memberikan dampak dorongan yang kuat terhadap pengamalan nilai kerja. Oleh karena itu, menjadi role model bagi unit terkecilnya adalah langkah yang tepat.</p><p>Jawaban tepat: <strong>C. Menjadi role model budaya bagi unit kerja terkecilnya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penyusunan RUU keuangan negara merupakan ranah strategis kementerian dan lembaga legislatif.</li><li><strong>B</strong>: Pemeriksaan kas bendahara adalah fungsi verifikasi rutin anggaran operasional.</li><li><strong>D</strong>: Penerbitan sertifikat kepatuhan bukan bentuk keteladanan budaya dalam strategi eksternalisasi.</li><li><strong>E</strong>: Fasilitasi beasiswa pendidikan merupakan tugas bagian pengembangan kepegawaian kantor pusat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 45) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menjadi role model bagi unit terkecilnya • Pejabat Pengawas/ Administrator</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Bentuk eksternalisasi budaya yang dapat diwujudkan oleh pegawai pelaksana di lingkungan masyarakat adalah...", "options": ["Merumuskan pedoman teknis pengelolaan anggaran dinas", "Menyusun instrumen penilaian risiko pengadaan barang", "Mengesahkan perjanjian kerja sama antarkantor cabang", "Menerapkan budaya kerja yang bermanfaat bagi masyarakat", "Menilai kelayakan permohonan izin operasional kantor"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Strategi Eksternalisasi Memberi Manfaat bagi Publik</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Strategi eksternalisasi bagi pelaksana mencakup <strong>Mendukung pelaksanaan penerapan budaya Kemenkeu yang memberi manfaat bagi masyarakat/ instansi lain</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat sesuai butir eksternalisasi pegawai pelaksana. Nilai-nilai budaya kerja yang dianut pegawai Kementerian Keuangan harus mampu dirasakan manfaat positifnya oleh masyarakat luas dan instansi mitra. Hal ini membuktikan bahwa budaya organisasi tidak hanya berhenti di lingkungan internal. Oleh karena itu, menerapkan budaya kerja yang memberi manfaat bagi masyarakat adalah wujud nyatanya.</p><p>Jawaban tepat: <strong>D. Menerapkan budaya kerja yang bermanfaat bagi masyarakat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merumuskan pedoman teknis anggaran merupakan tugas tim perumus regulasi kantor pusat.</li><li><strong>B</strong>: Penyusunan instrumen risiko pengadaan barang adalah tugas fungsional pengelola pengadaan.</li><li><strong>C</strong>: Mengesahkan naskah perjanjian kerja sama merupakan wewenang pimpinan definitif kantor.</li><li><strong>E</strong>: Penilaian kelayakan izin operasional adalah kewenangan teknis pejabat perizinan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 45) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>memberi manfaat bagi masyarakat • instansi lain</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Materi budaya kementerian diinstitusikan bagi Pejabat Pengawas dan Administrator melalui...", "options": ["Ujian kompetensi kenaikan jenjang pangkat berkala", "Forum konsultasi publik rancangan undang-undang", "Program pertukaran pegawai antarlembaga swasta", "Penyusunan laporan pertanggungjawaban hibah luar", "Materi diklat kepemimpinan atau soft skill pegawai"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Institusionalisasi Budaya dalam Diklat Kepemimpinan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Salah satu bentuk institusionalisasi bagi Pejabat Pengawas dan Administrator adalah <strong>Memasukkan dalam materi diklat kepemimpinan atau soft skill pegawai</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat sesuai panduan strategi internalisasi. Penanaman nilai pada jenjang pejabat pengawas dan administrator dilembagakan melalui materi kepemimpinan dan pelatihan keterampilan lunak (*soft skill*). Pendekatan ini memperkuat kapasitas manajerial sekaligus kepemimpinan berbasis etika. Oleh karena itu, materi diklat kepemimpinan atau soft skill pegawai adalah jalur pelembagaan yang tepat.</p><p>Jawaban tepat: <strong>E. Materi diklat kepemimpinan atau soft skill pegawai</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Ujian kompetensi pangkat mengukur kualifikasi teknis kepegawaian umum dan bukan materi institusionalisasi budaya.</li><li><strong>B</strong>: Konsultasi publik merupakan mekanisme penyusunan peraturan perundang-undangan dengan masyarakat.</li><li><strong>C</strong>: Program pertukaran pegawai swasta bukan bagian dari strategi institusionalisasi budaya aparatur pedoman kerja.</li><li><strong>D</strong>: Laporan hibah luar negeri adalah administrasi perbendaharaan pembiayaan pembangunan negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 46) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>diklat kepemimpinan atau soft skill pegawai • Institusionalisasi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Target tingkat kematangan minimal bagi Pejabat Pimpinan Tinggi di Kementerian Keuangan adalah...", "options": ["Memiliki dan mengembangkan nilai atau owning values", "Menghadiri seminar internasional tata kelola dinas", "Mengamati pelaksanaan sistem informasi kepegawaian", "Menerapkan sebagian tata tertib kedinasan kantor", "Sadar akan arti penting budaya atau aware values"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tingkat Kematangan Pejabat Pimpinan Tinggi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Target tingkat kematangan minimal bagi <strong>Pejabat Pimpinan Tinggi/ Pejabat fungsional setara</strong> adalah <strong>Memiliki, mengajak orang lain untuk menerapkan, serta mengembangkan Sikap Dasar dan Perilaku Efektif (owning the values)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat mengenai puncak tingkat kematangan budaya. Pejabat pimpinan tinggi memegang tanggung jawab tertinggi untuk menjiwai, menularkan, dan mengembangkan nilai organisasi ke seluruh jajaran. Tingkat kepemilikan nilai (*owning the values*) mewajibkan mereka menjadi motor utama penggerak budaya. Dengan demikian, memiliki dan mengembangkan nilai adalah target minimalnya.</p><p>Jawaban tepat: <strong>A. Memiliki dan mengembangkan nilai atau owning values</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Menghadiri seminar internasional merupakan pengembangan wawasan global dan bukan definisi tingkat kematangan budaya.</li><li><strong>C</strong>: Mengamati sistem kepegawaian adalah tugas pengawasan operasional dan bukan target penguasaan nilai pimpinan.</li><li><strong>D</strong>: Menerapkan sebagian tata tertib bukan standar bagi pimpinan tinggi yang wajib memiliki dan menjiwai seluruh nilai.</li><li><strong>E</strong>: Sadar akan arti penting nilai (aware the values) merupakan target kematangan awal bagi CPNS dan mahasiswa.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 46) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pejabat Pimpinan Tinggi • owning the values</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Salah satu strategi eksternalisasi bagi Pejabat Pimpinan Tinggi dalam budaya kementerian adalah...", "options": ["Menjadi bendahara pengeluaran proyek strategis dinas", "Menjadi role model bagi unitnya dan instansi lain", "Melakukan inventarisasi fisik barang milik negara", "Menyusun laporan presensi kehadiran staf harian", "Melakukan audit kepatuhan internal kantor vertikal"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Keteladanan Eksternal Pejabat Pimpinan Tinggi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Strategi eksternalisasi bagi <strong>Pejabat Pimpinan Tinggi</strong> salah satunya adalah <strong>Menjadi role model bagi unitnya dan instansi lain</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat mengenai pengaruh keteladanan pimpinan. Pejabat Pimpinan Tinggi tidak hanya dituntut menjadi teladan bagi jajaran internal di unitnya, tetapi juga menjadi contoh integritas bagi instansi pemerintah lainnya. Keteladanan ini memperkuat citra positif birokrasi Kementerian Keuangan. Oleh karena itu, menjadi role model bagi unitnya dan instansi lain adalah bentuk eksternalisasi yang tepat.</p><p>Jawaban tepat: <strong>B. Menjadi role model bagi unitnya dan instansi lain</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Bendahara pengeluaran diemban oleh pejabat fungsional perbendaharaan dan bukan pejabat pimpinan tinggi.</li><li><strong>C</strong>: Inventarisasi barang milik negara merupakan tugas pengelola barang dan bagian perlengkapan kantor.</li><li><strong>D</strong>: Laporan presensi staf harian disusun oleh unit kepegawaian tingkat pelaksana.</li><li><strong>E</strong>: Audit kepatuhan internal vertikal dilakukan oleh fungsional auditor Inspektorat Jenderal.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 46) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Menjadi role model bagi unitnya dan instansi lain • Pejabat Pimpinan Tinggi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Wujud eksternalisasi Pejabat Pimpinan Tinggi melalui pembuatan kebijakan di unit kerjanya adalah...", "options": ["Menetapkan tarif penerimaan negara bukan pajak", "Menerbitkan surat peringatan disiplin pegawai kantor", "Membuat keputusan sebagai wujud budaya kementerian", "Mengesahkan jadwal piket pelayanan publik terpadu", "Menghitung ulang alokasi pagu anggaran tahunan"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengambilan Keputusan Berlandaskan Budaya Kerja</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Salah satu butir strategi eksternalisasi pimpinan tinggi adalah <strong>Membuat keputusan/kebijakan yang merupakan perwujudan dari budaya Kemenkeu di unitnya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat sesuai tata kelola kepemimpinan. Pimpinan tinggi menuangkan nilai budaya secara nyata dalam setiap garis kebijakan strategis dan keputusan manajerial yang diambil. Hal ini menjamin bahwa seluruh arah kebijakan unit selaras dengan prinsip moral organisasi. Dengan demikian, membuat keputusan sebagai perwujudan budaya kementerian adalah wujud nyatanya.</p><p>Jawaban tepat: <strong>C. Membuat keputusan sebagai wujud budaya kementerian</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Menetapkan tarif PNBP merupakan kebijakan fiskal berbasis peraturan pemerintah dan undang-undang penerimaan.</li><li><strong>B</strong>: Peringatan disiplin pegawai merupakan penegakan sanksi administratif dan bukan eksternalisasi perwujudan budaya.</li><li><strong>D</strong>: Jadwal piket pelayanan merupakan pengelolaan tugas teknis harian kantor oleh kepala seksi.</li><li><strong>E</strong>: Penghitungan alokasi anggaran adalah tugas teknis penganggaran belanja satker.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 46) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>perwujudan dari budaya Kemenkeu • di unitnya</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Peran pimpinan tinggi dalam memperluas sosialisasi budaya kementerian tercermin melalui...", "options": ["Penandatanganan bukti kas transfer kas daerah", "Verifikasi berkas tagihan rekanan penyedia jasa", "Penetapan daftar inventaris kendaraan kantor dinas", "Menjadi narasumber materi budaya ke dalam dan luar", "Pemeriksaan kelengkapan sarana prasarana gedung"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pimpinan sebagai Narasumber Penguatan Budaya</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pejabat Pimpinan Tinggi berperan aktif dengan <strong>Menjadi narasumber yang memasukkan budaya dalam materi sosialisasi baik ke dalam maupun ke luar</strong> organisasi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat sesuai panduan penguatan budaya. Sebagai narasumber di berbagai forum, pimpinan tinggi berkesempatan menyisipkan pesan dan nilai kementerian kepada khalayak luas. Penyampaian langsung oleh figur pimpinan memberikan bobot kredibilitas yang tinggi. Oleh karena itu, menjadi narasumber materi budaya ke dalam dan ke luar adalah peran yang tepat.</p><p>Jawaban tepat: <strong>D. Menjadi narasumber materi budaya ke dalam dan luar</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penandatanganan bukti kas transfer daerah merupakan fungsi pejabat perbendaharaan operasional.</li><li><strong>B</strong>: Verifikasi berkas tagihan penyedia jasa dilakukan oleh Pejabat Pembuat Komitmen dan staf penguji.</li><li><strong>C</strong>: Penetapan daftar inventaris kendaraan kantor adalah wewenang pengelola sarana umum.</li><li><strong>E</strong>: Pemeriksaan kelengkapan sarana gedung dilakukan oleh pengawas bagian rumah tangga kantor.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 46) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Menjadi narasumber yang memasukkan budaya • baik ke dalam maupun ke luar</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Strategi eksternalisasi Pejabat Pimpinan Tinggi yang mengikat mitra kerja dilakukan dengan...", "options": ["Mewajibkan mitra membuka rekening bank milik negara", "Mengadakan kompetisi karya tulis ilmiah bagi publik", "Menugaskan mitra mengaudit kepatuhan kode etik staf", "Membentuk panitia bersama pengelolaan sarana dinas", "Memasukkan klausul nilai budaya dalam naskah kerja sama"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penyisipan Nilai dalam Klausul Kerja Sama Eksternal</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Bentuk eksternalisasi pimpinan tinggi adalah <strong>Memasukkan ketentuan yang dilandasi core values ASN dan nilai-nilai Kementerian Keuangan ke dalam klausul kerja sama dengan pihak eksternal</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat mengenai penguatan integritas dalam kemitraan. Dengan mencantumkan klausul nilai budaya dan Core Values ASN dalam perjanjian kerja sama, mitra eksternal diwajibkan menjunjung standar etika yang setara. Hal ini meminimalkan potensi pelanggaran integritas dalam hubungan kerja. Dengan demikian, memasukkan klausul nilai budaya dalam kerja sama adalah strategi yang tepat.</p><p>Jawaban tepat: <strong>E. Memasukkan klausul nilai budaya dalam naskah kerja sama</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kewajiban rekening bank operasional diatur dalam regulasi perbankan penerimaan negara dan bukan klausul budaya.</li><li><strong>B</strong>: Kompetisi karya tulis ilmiah merupakan kegiatan edukasi publik umum dan bukan perikatan kemitraan.</li><li><strong>C</strong>: Audit kepatuhan kode etik pegawai adalah wewenang mutlak Itjen dan bukan penugasan kepada pihak mitra.</li><li><strong>D</strong>: Pengelolaan sarana kantor dilakukan secara swakelola kedinasan internal kementerian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 46) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>klausul kerja sama dengan pihak eksternal • core values ASN dan nilai-nilai Kementerian Keuangan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Program pelatihan yang disiapkan untuk membekali agen perubahan budaya di kementerian adalah...", "options": ["Training of Trainers bagi Duta Transformasi", "Kursus Singkat Akuntansi Keuangan Pemerintah", "Pelatihan Teknis Tata Naskah Dinas Kedinasan", "Lokakarya Penanganan Perkara Administrasi", "Bimbingan Teknis Pengadaan Barang dan Jasa"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Training of Trainers bagi Duta Transformasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Institusionalisasi penguatan budaya mencakup pelaksanaan <strong>Training of Trainers terkait Sikap Dasar/Perilaku Efektif bagi Duta Transformasi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat sesuai rincian strategi dalam etika kedinasan. Pelatihan calon pelatih (*Training of Trainers*) membekali para Duta Transformasi dengan metode komunikasi, fasilitasi, dan pemahaman mendalam tentang nilai budaya. Melalui bekal ini, para duta siap mengedukasi dan menginspirasi rekan-rekan kerjanya. Oleh karena itu, ToT bagi Duta Transformasi adalah program yang dimaksud.</p><p>Jawaban tepat: <strong>A. Training of Trainers bagi Duta Transformasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kursus akuntansi pemerintah berkaitan dengan kompetensi penyusunan laporan keuangan negara.</li><li><strong>C</strong>: Pelatihan tata naskah dinas berfokus pada administrasi persuratan resmi kantor.</li><li><strong>D</strong>: Lokakarya perkara hukum menangani sengketa peradilan tata usaha negara.</li><li><strong>E</strong>: Bimbingan teknis pengadaan barang/jasa ditujukan bagi pejabat pembuat komitmen dan panitia tender.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 46) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Training of Trainers • Duta Transformasi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Secara konseptual, nilai dirumuskan sebagai keyakinan mengenai hal yang dipandang baik atau buruk yang...", "options": ["Membatasi perkembangan kebebasan berpendapat publik", "Memengaruhi perilaku individu dalam melakukan tindakan", "Mengatur tata cara teknis penyusunan peraturan dinas", "Menentukan besaran penghasilan aparatur sipil negara", "Mewajibkan pegawai mengikuti seluruh agenda pimpinan"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Nilai dalam Bab Penutup</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Nilai</strong> dirumuskan secara konseptual sebagai <strong>sebuah keyakinan tentang apa yang dianggap baik atau buruk</strong> yang <strong>memengaruhi perilaku orang dalam melakukan tindakan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat sesuai rumusan konseptual nilai secara konseptual. Keyakinan mendasar tentang baik dan buruk menjadi kompas internal yang membimbing perbuatan setiap manusia. Hal ini secara langsung mengarahkan pilihan sikap dan perilaku dalam bertindak. Oleh karena itu, memengaruhi perilaku individu dalam melakukan tindakan adalah inti definisi nilai.</p><p>Jawaban tepat: <strong>B. Memengaruhi perilaku individu dalam melakukan tindakan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Nilai etika tidak bertujuan membatasi kebebasan publik, melainkan membimbing perilaku ke arah kebaikan bersama.</li><li><strong>C</strong>: Tata cara teknis penyusunan peraturan merupakan substansi legal drafting dan bukan batasan konseptual nilai.</li><li><strong>D</strong>: Besaran penghasilan aparatur diatur melalui kebijakan remunerasi dan standar gaji pokok pemerintah.</li><li><strong>E</strong>: Nilai menuntun kepatuhan moral yang tulus dan bukan kewajiban buta mengikuti seluruh agenda pimpinan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 47) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>sebuah keyakinan tentang apa yang dianggap baik atau buruk • memengaruhi perilaku orang</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Nilai-nilai organisasi merupakan keyakinan yang dianut seluruh pemangku kepentingan untuk...", "options": ["Memperoleh penghargaan kinerja dari instansi pembina", "Menjaga stabilitas penerimaan anggaran tahun berjalan", "Berpikir, bertindak, dan berperilaku menuju tujuan organisasi", "Membentuk jejaring persahabatan di luar lingkungan kerja", "Menyusun daftar inventaris aset tetap secara mandiri"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengertian Nilai-Nilai Organisasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Nilai-Nilai Organisasi</strong> merupakan keyakinan dan kepercayaan yang diyakini oleh individu, kelompok dan seluruh pemangku kepentingan dalam organisasi untuk <strong>berpikir, bertindak, dan berperilaku untuk menuju sebuah tujuan organisasi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat sesuai pengertian nilai-nilai organisasi. Nilai organisasi menyatukan cara pandang dan tindakan seluruh elemen pegawai agar seirama menuju visi instansi. Keterikatan bersama ini menciptakan konsistensi dalam pola pikir dan tindakan kerja. Dengan demikian, berpikir, bertindak, dan berperilaku menuju tujuan organisasi adalah fungsi hakikinya.</p><p>Jawaban tepat: <strong>C. Berpikir, bertindak, dan berperilaku menuju tujuan organisasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Mendapatkan penghargaan kinerja merupakan apresiasi eksternal dan bukan tujuan filosofis penanaman nilai organisasi.</li><li><strong>B</strong>: Stabilitas penerimaan anggaran adalah target fiskal tahunan dan bukan definisi nilai organisasi.</li><li><strong>D</strong>: Persahabatan di luar lingkungan kerja merupakan hubungan sosial pribadi dan bukan komitmen nilai organisasi.</li><li><strong>E</strong>: Inventarisasi aset fisik adalah tugas administratif barang milik negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 47) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>berpikir, bertindak, dan berperilaku • tujuan organisasi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Faktor yang menjadi prasyarat bagi organisasi dalam meningkatkan kinerjanya melalui budaya organisasi adalah...", "options": ["Alokasi anggaran operasional yang bertambah", "Ketersediaan sarana teknologi mutakhir kantor", "Penambahan jumlah kantor layanan perwakilan", "Kohesi antarindividu dan kesatuan organisasi", "Pengurangan frekuensi rapat koordinasi kerja"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kohesi sebagai Prasyarat Peningkatan Kinerja</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sebuah <strong>kohesi antar individu, kesatuan kelompok dan kesatuan organisasi</strong> merupakan prasyarat sebuah organisasi untuk <strong>meningkatkan kinerjanya</strong>, melalui budaya organisasi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat mengenai prasyarat keberhasilan organisasi. Kinerja organisasi yang tinggi tidak dapat dicapai tanpa adanya keterikatan erat antarpegawai dan kesatuan visi di setiap unit. Kohesi sosial dan kekompakan kelompok menjadi modal utama terciptanya kolaborasi efektif. Oleh karena itu, kohesi antarindividu dan kesatuan organisasi adalah prasyarat mutlaknya.</p><p>Jawaban tepat: <strong>D. Kohesi antarindividu dan kesatuan organisasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penambahan alokasi anggaran adalah dukungan logistik dan bukan prasyarat budaya organisasi dalam prinsip manajemen kinerja.</li><li><strong>B</strong>: Sarana teknologi mutakhir merupakan sarana pendukung kerja dan bukan prasyarat keutuhan budaya organisasi.</li><li><strong>C</strong>: Penambahan kantor perwakilan adalah ekspansi fisik layanan publik kementerian.</li><li><strong>E</strong>: Frekuensi rapat merupakan metode komunikasi teknis dan bukan faktor fundamental pendorong kinerja.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 47) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>kohesi antar individu • meningkatkan kinerjanya</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Penetapan Nilai-nilai Dasar ASN BerAKHLAK diposisikan sebagai tonggak baru bagi aparatur agar...", "options": ["Memiliki jenjang karier struktural yang seragam", "Mengikuti pola seleksi pengadaan pegawai tunggal", "Mendapatkan fasilitas tunjangan jabatan setara", "Mempermudah mutasi antarinstansi pemerintah daerah", "Memiliki langkah yang sama membangun budaya ASN"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tonggak Nilai Dasar ASN BerAKHLAK</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Penetapan Nilai-nilai Dasar ASN BerAKHLAK merupakan <strong>tonggak baru bagi seluruh ASN agar memiliki langkah yang sama dalam membangun budaya ASN</strong> yang sesuai dengan harapan seluruh masyarakat.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat mengenai urgensi Core Values BerAKHLAK. Penyeragaman nilai dasar memberikan kerangka acuan perilaku yang seragam bagi seluruh abdi negara di seluruh Indonesia. Dengan langkah gerak yang sama, harapan masyarakat terhadap birokrasi yang melayani dapat diwujudkan. Oleh karena itu, memiliki langkah yang sama membangun budaya ASN adalah tujuan penetapannya.</p><p>Jawaban tepat: <strong>E. Memiliki langkah yang sama membangun budaya ASN</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Karier struktural disesuaikan dengan formasi jabatan dan kompetensi masing-masing instansi pembina.</li><li><strong>B</strong>: Pola seleksi pegawai diatur oleh Panitia Seleksi Nasional dan bukan tujuan penetapan core values etika.</li><li><strong>C</strong>: Fasilitas tunjangan disesuaikan dengan kemampuan keuangan negara dan kelas jabatan masing-masing.</li><li><strong>D</strong>: Mutasi antarinstansi diatur dalam tata kelola mobilitas talenta ASN nasional.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 47) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>tonggak baru bagi seluruh ASN • membangun budaya ASN</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Konsep dasar yang mempertemukan Nilai Dasar ASN dengan Nilai Kementerian Keuangan adalah agar pegawai mampu...", "options": ["Mengelola pribadi selaras tujuan pelaksanaan tugas", "Menghadiri penugasan dinas tanpa batasan waktu", "Membatasi komunikasi formal dengan instansi daerah", "Mengutamakan kepentingan unit kerja di atas segalanya", "Menyelesaikan pekerjaan rutin secara mandiri saja"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Keselarasan Nilai ASN dan Nilai Kementerian Keuangan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Nilai Dasar ASN dan Nilai-nilai Kementerian Keuangan saling mendukung karena memiliki konsep dasar yang sama, yaitu agar ASN atau pegawai dapat <strong>mengelola pribadi yang selaras dengan tujuan dari pelaksanaan tugasnya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat mengenai titik temu kedua kerangka nilai. Baik nilai BerAKHLAK maupun nilai Kementerian Keuangan membimbing individu untuk menyelaraskan integritas pribadi dengan tuntutan tugas kedinasan. Keselarasan ini melahirkan kinerja yang profesional dan akuntabel. Dengan demikian, mengelola pribadi yang selaras dengan pelaksanaan tugas adalah konsep dasarnya.</p><p>Jawaban tepat: <strong>A. Mengelola pribadi selaras tujuan pelaksanaan tugas</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Penugasan dinas tetap mempertimbangkan manajemen waktu kerja dan bukan esensi filosofis penyelarasan nilai.</li><li><strong>C</strong>: Komunikasi antarinstansi justru harus diperluas untuk memperkuat kolaborasi harmonis aparatur.</li><li><strong>D</strong>: Mengutamakan unit kerja secara sepihak bertentangan dengan semangat sinergi dan kesatuan organisasi kementerian.</li><li><strong>E</strong>: Pekerjaan kedinasan memerlukan koordinasi tim kerja terpadu dan tidak dapat diselesaikan mandiri semata.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 47) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>mengelola pribadi yang selaras • tujuan dari pelaksanaan tugasnya</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Makna nilai Integritas menuntut komitmen etis bagi seluruh pegawai untuk...", "options": ["Menyimpan data keuangan secara tertutup dari pihak lain", "Berpikir, berkata, berbuat benar dan memegang kode etik", "Menyesuaikan seluruh keputusan dengan instruksi lisan pimpinan", "Menyelesaikan pekerjaan kedinasan dengan orientasi bisnis", "Mengamankan berkas perbendaharaan dari pemeriksaan berkala"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Ringkasan Materi Nilai Integritas</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Integritas</strong> berarti seluruh pegawai harus <strong>berpikir, berkata, berperilaku, dan bertindak dengan baik dan benar</strong> serta selalu memegang teguh <strong>Kode Etik dan prinsip-prinsip moral</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat mengenai intisari nilai Integritas. Keselarasan antara pikiran, perkataan, dan perbuatan yang dilandasi prinsip moral merupakan inti keteguhan integritas abdi negara. Nilai ini menjadi benteng utama penegakan etika di lingkungan Kementerian Keuangan. Oleh karena itu, berpikir, berkata, berbuat benar dan memegang teguh kode etik adalah maknanya.</p><p>Jawaban tepat: <strong>B. Berpikir, berkata, berbuat benar dan memegang kode etik</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Data keuangan publik dikelola secara transparan dan akuntabel sesuai ketentuan keterbukaan informasi publik.</li><li><strong>C</strong>: Keputusan pegawai harus selalu berlandaskan peraturan hukum yang sah dan bukan instruksi lisan di luar aturan.</li><li><strong>D</strong>: Instansi pemerintah berorientasi pada pelayanan masyarakat prima dan bukan berorientasi laba komersial bisnis.</li><li><strong>E</strong>: Pengamanan berkas perbendaharaan tetap harus terbuka penuh bagi tim pemeriksa pengawasan kepatuhan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 47) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>berpikir, berkata, berperilaku, dan bertindak dengan baik dan benar • Kode Etik dan prinsip-prinsip moral</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Makna nilai Sinergi dalam prinsip kerja sama Kementerian Keuangan menekankan komitmen untuk...", "options": ["Memusatkan seluruh keputusan pada satu pimpinan unit", "Menjaga kerahasiaan proses kerja dari rekan sebidang", "Membangun kerja sama internal dan kemitraan harmonis", "Mengambil alih seluruh fungsi kerja unit bawahan kantor", "Memisahkan target kerja antarbidang dalam organisasi"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Ringkasan Materi Nilai Sinergi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Sinergi</strong> berarti seluruh pegawai harus berkomitmen untuk membangun dan memastikan <strong>hubungan kerja sama internal yang produktif</strong> serta <strong>kemitraan yang harmonis dengan para pemangku kepentingan</strong>, untuk menghasilkan karya yang bermanfaat dan berkualitas.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat sesuai rumusan nilai Sinergi. Kerjasama tim yang produktif di lingkungan internal serta hubungan harmonis dengan mitra eksternal merupakan pilar utama keberhasilan pencapaian visi kementerian. Sinergi menghasilkan karya berbobot tinggi bagi kemaslahatan publik. Dengan demikian, membangun kerja sama internal dan kemitraan harmonis adalah penekanan intinya.</p><p>Jawaban tepat: <strong>C. Membangun kerja sama internal dan kemitraan harmonis</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Sentralisasi keputusan sepihak bertentangan dengan prinsip koordinasi kemitraan yang setara.</li><li><strong>B</strong>: Menutup diri dari rekan sebidang bertentangan langsung dengan semangat keterbukaan informasi dan kolaborasi.</li><li><strong>D</strong>: Pengambilalihan fungsi bawahan menyalahi pendelegasian wewenang dan pembagian tugas yang sehat.</li><li><strong>E</strong>: Pemisahan target kerja memicu sekat pembatas antarunit yang menghambat tercapainya tujuan bersama.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 47) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>hubungan kerja sama internal yang produktif • kemitraan yang harmonis dengan para pemangku kepentingan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Makna nilai Pelayanan di lingkungan kementerian diwujudkan dengan memberikan layanan yang...", "options": ["Menyesuaikan besaran alokasi anggaran belanja kantor", "Mengutamakan pihak yang membawa rekomendasi pimpinan", "Dilaksanakan terbatas pada jam operasional tertentu", "Memenuhi kepuasan pemangku kepentingan sepenuh hati", "Diberikan secara khusus kepada perwakilan pemda mitra"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Intisari Materi Nilai Pelayanan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Pelayanan</strong> berarti seluruh pegawai harus memberikan pelayanan untuk <strong>memenuhi kepuasan para pemangku kepentingan</strong> dan dilaksanakan dengan <strong>sepenuh hati, transparan, cepat, akurat, dan aman</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat mengenai hakikat nilai Pelayanan. Pelayanan publik aparatur Kementerian Keuangan didedikasikan untuk memberikan kepuasan maksimal kepada pemangku kepentingan secara tulus dan profesional. Sikap sepenuh hati menjadi cerminan dedikasi abdi negara dalam melayani rakyat. Oleh karena itu, memenuhi kepuasan pemangku kepentingan sepenuh hati adalah intisari maknanya.</p><p>Jawaban tepat: <strong>D. Memenuhi kepuasan pemangku kepentingan sepenuh hati</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Layanan prima diberikan tanpa bergantung pada fluktuasi penyerapan alokasi anggaran belanja kantor.</li><li><strong>B</strong>: Pelayanan aparatur menjunjung kesetaraan perlakuan dan melarang pengutamaan rekomendasi subjektif.</li><li><strong>C</strong>: Komitmen melayani diwujudkan secara konsisten dan tanggap sesuai standar operasional yang ditetapkan.</li><li><strong>E</strong>: Layanan kementerian terbuka adil bagi seluruh pemangku kepentingan tanpa perlakuan khusus tertentu.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 48) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>memenuhi kepuasan para pemangku kepentingan • sepenuh hati, transparan, cepat, akurat, dan aman</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Pelaksanaan nilai Pelayanan di lingkungan Kementerian Keuangan wajib diselenggarakan secara...", "options": ["Periodik, terpusat, fleksibel, tertutup, dan bersyarat", "Bertahap, kondisional, terstruktur, formal, dan santai", "Khusus, terpisah, berjangka, terbatas, dan sistematis", "Eksklusif, terukur, mandiri, konseptual, dan normatif", "Sepenuh hati, transparan, cepat, akurat, dan aman"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Karakteristik Pelaksanaan Nilai Pelayanan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pelayanan di Kementerian Keuangan dilaksanakan dengan karakteristik <strong>sepenuh hati, transparan, cepat, akurat, dan aman</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat sesuai rumusan standar mutu pelayanan kementerian. Kelima karakteristik ini menjadi standar mutu utama pelayanan kementerian dalam melayani pemangku kepentingan. Kombinasi ketulusan, keterbukaan, kecepatan, ketepatan, dan keamanan menjamin kepuasan publik. Dengan demikian, sepenuh hati, transparan, cepat, akurat, dan aman adalah karakteristik yang tepat.</p><p>Jawaban tepat: <strong>E. Sepenuh hati, transparan, cepat, akurat, dan aman</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Karakteristik tertutup dan bersyarat bertentangan langsung dengan prinsip transparansi layanan publik.</li><li><strong>B</strong>: Layanan publik tidak dilaksanakan secara santai, melainkan profesional, cepat, dan akurat.</li><li><strong>C</strong>: Pelayanan kementerian tidak bersifat terbatas atau berbayar, melainkan inklusif bagi seluruh pemangku kepentingan.</li><li><strong>D</strong>: Pelayanan aparatur bersifat terbuka bagi semua kalangan dan bukan bersifat eksklusif.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 48) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>sepenuh hati, transparan, cepat, akurat, dan aman • memberikan pelayanan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Makna nilai Kesempurnaan menuntut komitmen aparatur kementerian untuk senantiasa...", "options": ["Melakukan upaya perbaikan menjadi dan memberi terbaik", "Mempertahankan metode kerja lama yang dirasa nyaman", "Mengganti seluruh sarana operasional dinas tiap tahun", "Menyerahkan urusan pembaruan sistem kepada pihak luar", "Membatasi inisiatif baru demi menjaga ketenangan kerja"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Intisari Materi Nilai Kesempurnaan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Kesempurnaan</strong> berarti seluruh pegawai harus senantiasa melakukan <strong>upaya perbaikan di segala bidang</strong> untuk <strong>menjadi dan memberikan yang terbaik</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat mengenai hakikat nilai Kesempurnaan. Nilai Kesempurnaan mendorong setiap aparatur untuk tidak cepat berpuas diri dengan capaian yang ada dan terus berinovasi. Semangat perbaikan berkelanjutan ini mengantarkan organisasi menuju kinerja kelas dunia. Oleh karena itu, melakukan upaya perbaikan menjadi dan memberi yang terbaik adalah maknanya.</p><p>Jawaban tepat: <strong>A. Melakukan upaya perbaikan menjadi dan memberi terbaik</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Mempertahankan cara kerja lama tanpa mau berubah bertentangan dengan prinsip perbaikan berkelanjutan.</li><li><strong>C</strong>: Kesempurnaan berfokus pada kualitas proses dan budaya kerja, bukan sekadar penggantian fisik sarana kantor.</li><li><strong>D</strong>: Inovasi lahir dari partisipasi aktif seluruh pegawai internal dan bukan bergantung pada pihak luar semata.</li><li><strong>E</strong>: Membatasi inisiatif baru menghambat perbaikan sistem dan bertolak belakang dengan nilai kesempurnaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 48) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>upaya perbaikan di segala bidang • menjadi dan memberikan yang terbaik</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Tujuan utama dari dilakukannya upaya perbaikan di segala bidang pada nilai Kesempurnaan adalah untuk...", "options": ["Mengurangi alokasi tenaga kerja pelaksana kantor dinas", "Menjadi dan memberikan yang terbaik bagi organisasi", "Mengamankan perolehan tunjangan kinerja setiap pegawai", "Memperoleh sertifikat akreditasi berbayar internasional", "Membatasi penugasan mutasi dinas pegawai ke luar wilayah"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuan Perbaikan di Segala Bidang</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Upaya perbaikan di segala bidang dalam nilai Kesempurnaan ditujukan secara mendasar untuk <strong>menjadi dan memberikan yang terbaik</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat sesuai tujuan luhur nilai kesempurnaan. Seluruh proses evaluasi, penyempurnaan sistem, dan penyesuaian teknologi diarahkan untuk mempersembahkan karya kerja yang paling optimal bagi bangsa dan negara. Upaya tanpa henti ini mencerminkan dedikasi tertinggi pegawai kementerian. Dengan demikian, menjadi dan memberikan yang terbaik adalah tujuan utamanya.</p><p>Jawaban tepat: <strong>B. Menjadi dan memberikan yang terbaik bagi organisasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Perbaikan kerja bertujuan meningkatkan produktivitas dan bukan sekadar pengurangan tenaga kerja pelaksana.</li><li><strong>C</strong>: Tunjangan kinerja merupakan kompensasi resmi pemerintah dan bukan motivasi filosofis perbaikan nilai.</li><li><strong>D</strong>: Akreditasi sertifikat adalah pengakuan formal eksternal dan bukan tujuan hakiki perbaikan budaya.</li><li><strong>E</strong>: Mutasi jabatan merupakan pembinaan karier pegawai dan tidak berkaitan dengan orientasi nilai kesempurnaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 48) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>upaya perbaikan di segala bidang • memberikan yang terbaik</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Orientasi utama dari pelaksanaan nilai Pelayanan oleh aparatur Kementerian Keuangan adalah...", "options": ["Memaksimalkan penyerapan pagu belanja dinas operasional", "Menyelesaikan laporan pertanggungjawaban teknis kantor", "Memenuhi kepuasan pemangku kepentingan secara transparan", "Memenangkan penghargaan inovasi pelayanan publik dinas", "Membatasi akses permohonan informasi bagi publik umum"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Orientasi Kepuasan Pemangku Kepentingan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Fokus utama dari pemberian layanan pada nilai Pelayanan adalah <strong>memenuhi kepuasan para pemangku kepentingan</strong> secara <strong>transparan</strong> dan akurat.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat mengenai orientasi pelayanan publik. Aparatur kementerian menempatkan kepuasan pemangku kepentingan sebagai tolok ukur utama keberhasilan pelaksanaan tugas layanan. Transparansi dan akurasi menjadi jaminan terciptanya rasa percaya dari masyarakat. Oleh karena itu, memenuhi kepuasan pemangku kepentingan secara transparan adalah orientasi utamanya.</p><p>Jawaban tepat: <strong>C. Memenuhi kepuasan pemangku kepentingan secara transparan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penyerapan pagu belanja merupakan target manajerial anggaran dan bukan orientasi kepuasan layanan publik.</li><li><strong>B</strong>: Laporan teknis pertanggungjawaban adalah kewajiban administratif dan bukan orientasi mutu pelayanan.</li><li><strong>D</strong>: Penghargaan publik merupakan dampak positif dari inovasi, bukan sasaran esensial orientasi melayani.</li><li><strong>E</strong>: Membatasi permohonan informasi bertentangan dengan prinsip keterbukaan publik yang diwajibkan aturan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 48) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>memenuhi kepuasan para pemangku kepentingan • memberikan pelayanan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Sikap keterbukaan proses dan kejelasan informasi dalam nilai Pelayanan dicerminkan oleh prinsip...", "options": ["Akuntabel dan formal", "Cepat dan selektif", "Aman dan kondisional", "Transparan dan akurat", "Rapi dan berkelanjutan"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Prinsip Keterbukaan dan Ketepatan Layanan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pelayanan di Kementerian Keuangan harus diselenggarakan dengan prinsip <strong>transparan, cepat, akurat, dan aman</strong> guna memberikan kepuasan pemangku kepentingan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat mengenai prinsip transparansi layanan. Keterbukaan prosedur kerja diwujudkan melalui sifat transparan, sedangkan kebenaran data diwujudkan melalui ketelitian yang akurat. Dua prinsip ini memastikan pemangku kepentingan menerima kepastian layanan tanpa keraguan. Dengan demikian, transparan dan akurat adalah cerminan yang tepat.</p><p>Jawaban tepat: <strong>D. Transparan dan akurat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Akuntabel dan formal merupakan terminologi tata kelola umum dan bukan rumusan standar mutu nilai pelayanan.</li><li><strong>B</strong>: Prinsip selektif bertentangan dengan asas kesetaraan layanan publik bagi seluruh warga negara.</li><li><strong>C</strong>: Prinsip kondisional mengindikasikan ketidakpastian layanan yang tidak sesuai dengan standar pelayanan.</li><li><strong>E</strong>: Rapi dan berkelanjutan merupakan istilah operasional umum dan bukan rumusan eksplisit pedoman etika.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 48) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>transparan, cepat, akurat, dan aman • memberikan pelayanan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Pengamalan lima nilai utama Kementerian Keuangan bermuara pada lima nilai utama Kementerian Keuangan bermuara pada komitmen untuk senantiasa...", "options": ["Mempertahankan birokrasi konvensional secara monoton", "Mengutamakan administrasi dokumen daripada mutu publik", "Membatasi kerja sama dengan instansi pengawas negara", "Menyeragamkan seluruh proses teknis perbendaharaan", "Memberikan pelayanan prima dan perbaikan berkelanjutan"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Esensi Pengamalan Nilai Kementerian Keuangan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kelima <strong>Nilai-Nilai Kementerian Keuangan</strong> (Integritas, Profesionalisme, Sinergi, Pelayanan, Kesempurnaan) bermuara pada pemberian pelayanan terbaik serta <strong>upaya perbaikan di segala bidang</strong> secara berkelanjutan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat sebagai rangkuman utuh seluruh nilai kementerian. Pengamalan integritas, profesionalisme, dan sinergi berujung pada pelayanan sepenuh hati kepada masyarakat serta semangat inovasi tanpa henti untuk menjadi yang terbaik. Komitmen ganda ini mencerminkan visi luhur Kementerian Keuangan. Oleh karena itu, memberikan pelayanan prima dan perbaikan berkelanjutan adalah esensi muaranya.</p><p>Jawaban tepat: <strong>E. Memberikan pelayanan prima dan perbaikan berkelanjutan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Birokrasi konvensional dan kaku bertentangan langsung dengan semangat adaptif dan inovatif nilai kesempurnaan.</li><li><strong>B</strong>: Administrasi dokumen merupakan pendukung akuntabilitas dan bukan tujuan yang mengalahkan mutu layanan publik.</li><li><strong>C</strong>: Kerja sama dengan instansi pengawas justru harus diperkuat melalui komitmen sinergi dan integritas.</li><li><strong>D</strong>: Menyeragamkan proses teknis merupakan kegiatan standarisasi SOP dan bukan muara filosofis pengamalan nilai.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 48) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Nilai-Nilai Kementerian Keuangan • upaya perbaikan di segala bidang</strong></div></div>"}];
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
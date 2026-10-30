<?php
/**
 * STANGOLDEN.exam • Tata Aturan Kepegawaian Paket 06 (Halaman 62–70)
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
// $DB->get_record("stgtak_p06_rank") => tabel fisik "mdlhv_stgtak_p06_rank".
// Jangan menambahkan prefix secara manual di konstanta berikut.
// Penyimpanan bersama melalui database Moodle. Nama tabel pendek, kompatibel dengan batas Moodle.
const STG_LB_T = 'stgtak_p06_rank';
const STG_REVIEW_T = 'stgtak_p06_rev';
const STG_ANNO_T = 'stgtak_p06_anno';
const STG_KEY = [1, 2, 3, 4, 0, 2, 3, 4, 0, 1, 4, 0, 1, 2, 3, 0, 1, 2, 3, 4, 3, 4, 0, 1, 2, 4, 0, 1, 2, 3, 2, 3, 4, 0, 1, 3, 4, 0, 1, 2, 1, 2, 3, 4, 0, 2, 3, 4, 0, 1, 0, 1, 2, 3, 4, 1, 2, 3, 4, 0, 3, 4, 0, 1, 2, 4, 0, 1, 2, 3, 2, 3, 4, 0, 1, 3, 4, 0, 1, 2, 1, 2, 3, 4, 0, 2, 3, 4, 0, 1, 0, 4, 1, 3, 2, 1, 3, 2, 0, 4];
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
            error_log('STANGOLDEN TAK P06 HLM62-70 SETUP: ' . $e->getMessage());
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
    }catch(Throwable $e){error_log('STANGOLDEN TAK P06 HLM62-70: '.$e->getMessage());stgtak_json(['success'=>false,'message'=>($e instanceof RuntimeException ? $e->getMessage() : 'Penyimpanan belum tersedia. Hubungi administrator.')],500);}
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
<title>Latihan Tata Aturan Kepegawaian Paket 06 (Hlm. 62–70) • STANGOLDEN</title>
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
      <span class="series-tag">Paket 06 • Hlm. 62–70</span>
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
<h1>Latihan Nilai-Nilai Kemenkeu <span class="series-tag">Paket 06 • Hlm. 62–70</span></h1>
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
        <h2 class="leaderboard-title" id="rankingModalTitle">🏆 Lihat Ranking Tata Aturan Kepegawaian Paket 06</h2>
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
const STG_LOCAL_KEY="stg_tak_p06_review_user_<?= (int)$USER->id ?>";
const HAS_FULL_REVIEW=true;
const IS_SITE_ADMIN=<?= is_siteadmin($USER) ? "true" : "false" ?>;
const LEADERBOARD_ENDPOINT=window.location.href.split("?")[0].split("#")[0];
const questions=[{"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan regulasi manajemen kepegawaian, hak atas cuti sakit bagi PNS diberikan untuk waktu paling lama...", "options": ["6 (enam) bulan berjalan", "1 (satu) tahun kalender", "3 (tiga) bulan dinas", "2 (dua) tahun berturut-turut", "3 (tiga) tahun penugasan"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Batas Waktu Awal Cuti Sakit Berat PNS</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Hak atas <strong>cuti sakit</strong> diberikan untuk waktu paling lama <strong>1 (satu) tahun</strong> dan dapat ditambah paling lama 6 bulan berdasarkan keterangan tim penguji kesehatan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena ketentuan cuti sakit membatasi durasi awal paling lama 1 tahun kalender. Apabila setelah 1 tahun belum sembuh, barulah dapat dipertimbangkan perpanjangan maksimal 6 bulan. Oleh sebab itu, opsi A benar.</p><p>Jawaban tepat: <strong>B. 1 (satu) tahun kalender</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Enam bulan merupakan batas maksimal perpanjangan tambahan, bukan durasi awal cuti sakit.</li><li><strong>C</strong>: Tiga bulan merupakan durasi standar untuk Cuti Melahirkan atau Cuti Besar.</li><li><strong>D</strong>: Dua tahun melebihi batas maksimal akumulasi cuti sakit (1 tahun + 6 bulan).</li><li><strong>E</strong>: Tiga tahun merupakan batas durasi awal Cuti di Luar Tanggungan Negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 62) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Hak atas cuti sakit diberikan untuk waktu paling lama 1 (satu) tahun • tim penguji kesehatan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Perpanjangan cuti sakit bagi PNS yang belum sembuh setelah satu tahun dapat ditambah untuk waktu paling lama...", "options": ["3 (tiga) bulan masa pemulihan", "1 (satu) tahun secara berkala", "6 (enam) bulan apabila diperlukan", "9 (sembilan) bulan kalender", "12 (dua belas) bulan penuh"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Perpanjangan Cuti Sakit PNS</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Cuti sakit <strong>dapat ditambah untuk paling lama 6 (enam) bulan</strong> apabila diperlukan, berdasarkan surat keterangan tim penguji kesehatan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena batas waktu perpanjangan cuti sakit tambahan adalah maksimal 6 bulan. Perpanjangan ini wajib disokong rekomendasi tim penguji kesehatan pemerintah. Dengan demikian, opsi A benar.</p><p>Jawaban tepat: <strong>C. 6 (enam) bulan apabila diperlukan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Tiga bulan bukan batas perpanjangan cuti sakit melainkan batas standar cuti melahirkan.</li><li><strong>B</strong>: Satu tahun adalah batas waktu durasi awal cuti sakit, bukan batas perpanjangannya.</li><li><strong>D</strong>: Sembilan bulan tidak dikenal dalam nomenklatur durasi perpanjangan cuti sakit ASN.</li><li><strong>E</strong>: Dua belas bulan setara dengan 1 tahun dan bukan batas waktu perpanjangan tambahan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 62) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>dapat ditambah untuk paling lama 6 (enam) bulan • tim penguji kesehatan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C2 - Pemahaman", "question": "PNS wanita yang mengalami musibah gugur kandungan berhak mengajukan cuti sakit untuk jangka waktu paling lama...", "options": ["Dua bulan berturut-turut", "Tiga bulan hari kerja", "Satu bulan penuh", "Satu setengah bulan kalender", "Empat puluh lima hari kerja"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Hak Cuti Gugur Kandungan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>PNS yang mengalami <strong>gugur kandungan</strong> berhak atas cuti sakit untuk paling lama <strong>satu setengah bulan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena aturan manajemen cuti menetapkan durasi cuti sakit gugur kandungan maksimal 1,5 bulan kalender. Cuti ini diberikan guna pemulihan kondisi kesehatan fisik dan psikologis pegawai. Dengan demikian, opsi A benar.</p><p>Jawaban tepat: <strong>D. Satu setengah bulan kalender</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Dua bulan melebihi hak normatif cuti gugur kandungan yang dibatasi 1,5 bulan.</li><li><strong>B</strong>: Tiga bulan merupakan durasi cuti melahirkan normal, bukan cuti gugur kandungan.</li><li><strong>C</strong>: Satu bulan merupakan batas maksimal Cuti Karena Alasan Penting.</li><li><strong>E</strong>: Empat puluh lima hari kerja tidak tepat karena penghitungan cuti gugur kandungan berbasis bulan kalender.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 62) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>PNS yang mengalami gugur kandungan • paling lama satu setengah bulan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C2 - Pemahaman", "question": "PNS yang menderita sakit lebih dari 14 (empat belas) hari wajib mengajukan permintaan cuti dengan melampirkan surat...", "options": ["Rekomendasi pejabat kepegawaian", "Keterangan dokter swasta pratama", "Pernyataan atasan langsung satker", "Rujukan klinik pratama daerah", "Keterangan dokter pemerintah"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Syarat Pengajuan Cuti Sakit Lebih dari 14 Hari</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>PNS yang sakit <strong>lebih dari 14 hari</strong> berhak atas cuti sakit dengan mengajukan tertulis serta melampirkan <strong>surat keterangan dokter pemerintah</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena ketentuan pasal cuti sakit lebih dari 14 hari mensyaratkan surat keterangan dari dokter pemerintah. Legalitas dokter pemerintah memastikan objektivitas penetapan diagnosa medis. Oleh karena itu, opsi A benar.</p><p>Jawaban tepat: <strong>E. Keterangan dokter pemerintah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Rekomendasi pejabat kepegawaian merupakan tindak lanjut administrasi, bukan lampiran medis awal.</li><li><strong>B</strong>: Dokter swasta hanya dibolehkan untuk sakit sampai dengan 14 hari, bukan di atas 14 hari.</li><li><strong>C</strong>: Surat pernyataan atasan bukan dokumen pembuktian medis kelayakan cuti sakit.</li><li><strong>D</strong>: Rujukan klinik pratama bukan dokumen pembuktian sah pengganti surat dokter pemerintah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 62) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>PNS yang menderita sakit lebih dari 14 (empat belas) hari • dokter pemerintah</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Hak cuti melahirkan diberikan kepada PNS atau CPNS wanita untuk persalinan anaknya yang...", "options": ["Pertama, kedua, dan ketiga", "Pertama dan kedua saja", "Pertama sampai anak keempat", "Kelahiran semua anak kandung", "Kedua, ketiga, dan keempat"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Ruang Lingkup Hak Cuti Melahirkan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Cuti Melahirkan</strong> merupakan hak PNS/CPNS wanita untuk <strong>persalinan anaknya yang pertama, kedua, dan ketiga</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena alokasi hak cuti melahirkan secara eksplisit dibatasi untuk anak pertama, kedua, dan ketiga. Kelahiran anak keempat dan seterusnya diakomodasi melalui skema cuti besar. Maka dari itu, opsi A benar.</p><p>Jawaban tepat: <strong>A. Pertama, kedua, dan ketiga</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Dua anak saja tidak tepat karena aturan kepegawaian menjamin hak cuti melahirkan hingga anak ketiga.</li><li><strong>C</strong>: Kelahiran anak keempat menggunakan cuti besar, bukan cuti melahirkan.</li><li><strong>D</strong>: Tidak mencakup semua anak karena persalinan anak ke-4 ke atas beralih ke skema cuti besar.</li><li><strong>E</strong>: Anak pertama tetap memiliki hak cuti melahirkan, sehingga tidak dimulai dari anak kedua.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 62) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Cuti Melahirkan • pertama, kedua, dan ketiga</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Penggunaan cuti bersalin oleh CPNS wanita untuk persalinan anak pertama akan berakibat...", "options": ["Menangguhkan hak cuti tahunan pada tahun berjalan berikutnya", "Memerlukan permohonan persetujuan khusus kepala perwakilan", "Mengurangi hak cuti persalinan setelah menjadi PNS", "Menangguhkan penerbitan kartu identitas pegawai resmi", "Menghentikan seluruh tunjangan melekat CPNS"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Ketentuan Cuti Bersalin bagi CPNS Wanita</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Cuti bersalin yang digunakan oleh CPNS wanita untuk persalinan anak pertama <strong>mengurangi hak cuti bersalin setelah yang bersangkutan diangkat menjadi PNS</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat. Berdasarkan regulasi teknis cuti ASN, CPNS wanita berhak atas cuti bersalin untuk anak pertama, namun pemanfaatan hak tersebut diperhitungkan secara kumulatif sehingga mengurangi jatah cuti bersalin yang berhak diambil setelah resmi diangkat sebagai PNS.</p><p>Jawaban tepat: <strong>C. Mengurangi hak cuti persalinan setelah menjadi PNS</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat, penggunaan hak cuti bersalin tidak menangguhkan hak cuti tahunan pada tahun berjalan.</li><li><strong>B</strong>: Kurang tepat, persetujuan cuti bersalin mengikuti prosedur baku kepegawaian tanpa persetujuan luar negeri khusus.</li><li><strong>D</strong>: Penerbitan kartu identitas aparatur tetap diproses sesuai mekanisme resmi.</li><li><strong>E</strong>: Tunjangan yang melekat pada status CPNS tetap dibayarkan sesuai haknya.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 62) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>mengurangi hak cuti persalinan • CPNS wanita untuk persalinan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C2 - Pemahaman", "question": "PNS yang telah aktif bekerja kembali setelah menjalani cuti sakit maksimal 1 tahun 6 bulan berhak atas...", "options": ["Bebas tugas kedinasan satu semester", "Cuti di luar tanggungan negara", "Kenaikan pangkat anumerta dinas", "Cuti bersama dan cuti tahunan berjalan", "Uang pesangon pemulihan kesehatan"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Hak Cuti Setelah Aktif Kembali dari Cuti Sakit Maksimal</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>PNS yang aktif bekerja kembali setelah cuti sakit 1 tahun 6 bulan berhak atas: <strong>cuti bersama</strong>, <strong>cuti tahunan</strong> berjalan dan sisa, cuti besar, cuti bersalin, dan cuti penting.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena pegawai yang telah aktif kembali dipulihkan hak-hak cutinya, termasuk cuti bersama dan cuti tahunan. Pemulihan ini memberikan kepastian hak normatif pegawai setelah sembuh. Dengan demikian, opsi A benar.</p><p>Jawaban tepat: <strong>D. Cuti bersama dan cuti tahunan berjalan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Tidak ada fasilitas bebas tugas kedinasan 1 semester bagi pegawai yang sudah aktif bekerja.</li><li><strong>B</strong>: CLTN bukan hak otomatis pasca-sembuh melainkan cuti atas permohonan pribadi.</li><li><strong>C</strong>: Kenaikan pangkat anumerta hanya diberikan kepada pegawai yang tewas dalam tugas.</li><li><strong>E</strong>: Uang pesangon tidak dikenal dalam skema pemulihan hak cuti aktif PNS.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 62) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>PNS yang telah menggunakan cuti sakit • cuti bersama</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C3 - Aplikasi", "question": "Seorang staf jatuh sakit dan dirawat intensif selama 20 hari di rumah sakit. Prosedur administratif yang tepat adalah...", "options": ["Mengirimkan resep obat apotek swasta terdekat", "Cukup melampirkan surat izin dari kepala seksi", "Memotong langsung cuti tahunan tanpa berkas", "Menunggu sembuh baru melapor ke kepegawaian", "Mengajukan tertulis dengan surat dokter pemerintah"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penerapan Izin Cuti Sakit Melebihi Dua Minggu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>PNS yang sakit <strong>lebih dari 14 hari</strong> harus mengajukan permintaan tertulis kepada Pejabat Berwenang dengan melampirkan <strong>surat keterangan dokter pemerintah</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena durasi sakit 20 hari melampaui batas 14 hari sehingga wajib disahkan oleh dokter pemerintah. Permohonan tertulis ditujukan kepada Pejabat yang Berwenang Memberikan Cuti. Oleh sebab itu, opsi A benar.</p><p>Jawaban tepat: <strong>E. Mengajukan tertulis dengan surat dokter pemerintah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Resep apotek bukan merupakan bukti surat keterangan dokter yang sah secara hukum.</li><li><strong>B</strong>: Izin kepala seksi tidak mencukupi untuk legalitas cuti sakit lebih dari 14 hari.</li><li><strong>C</strong>: Cuti sakit bukan pemotong otomatis cuti tahunan jika ada bukti medis dokter pemerintah.</li><li><strong>D</strong>: Menunggu sembuh tanpa pemberitahuan tertulis melanggar tata tertib kehadiran ASN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 62) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>PNS yang menderita sakit lebih dari 14 (empat belas) hari • Pejabat yang Berwenang Memberikan Cuti</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C3 - Aplikasi", "question": "Seorang PNS wanita mengalami keguguran kandungan pada usia kehamilan 4 bulan. Durasi maksimal cuti sakit yang dapat diambil adalah...", "options": ["1,5 (satu setengah) bulan kalender", "3 (tiga) bulan hari kalender", "14 (empat belas) hari kerja", "6 (enam) bulan berturut-turut", "10 (sepuluh) hari kalender"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penerapan Cuti Gugur Kandungan pada PNS</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Berdasarkan regulasi kepegawaian, <strong>PNS yang mengalami gugur kandungan</strong> berhak atas cuti sakit untuk paling lama <strong>satu setengah bulan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena hak cuti sakit untuk keguguran kandungan dibatasi maksimal 1,5 bulan kalender. Cuti ini dibuktikan dengan surat keterangan dokter atau bidan yang merawat. Maka dari itu, opsi A benar.</p><p>Jawaban tepat: <strong>A. 1,5 (satu setengah) bulan kalender</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Tiga bulan merupakan alokasi durasi untuk persalinan anak lahir normal/hidup.</li><li><strong>C</strong>: Empat belas hari adalah ambang batas cuti sakit biasa dengan dokter swasta.</li><li><strong>D</strong>: Enam bulan merupakan perpanjangan cuti sakit berat tim penguji kesehatan.</li><li><strong>E</strong>: Sepuluh hari terlalu singkat dan mengabaikan hak normatif 1,5 bulan bagi pemulihan kandungan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 62) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>PNS yang mengalami gugur kandungan • paling lama satu setengah bulan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C3 - Aplikasi", "question": "Seorang CPNS wanita melahirkan anak pertama saat masa orientasi dinas. Implikasi terhadap hak cuti persalinan kelak saat menjadi PNS adalah...", "options": ["Hak cuti persalinan saat PNS gugur total", "Tersisa hak cuti persalinan untuk 2 anak", "Tetap berhak cuti persalinan untuk 3 anak", "Wajib mengganti jam kerja selama 3 bulan", "Status kepegawaian otomatis ditangguhkan"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Implementasi Penggunaan Cuti Melahirkan CPNS</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Cuti bersalin anak pertama saat CPNS <strong>mengurangi hak cuti persalinan setelah menjadi PNS</strong>, sehingga sisa haknya menjadi 2 anak (kedua dan ketiga).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena kuota maksimal cuti melahirkan adalah 3 anak. Karena 1 anak telah diambil saat CPNS, maka sisa kuota setelah menjadi PNS adalah untuk 2 kelahiran anak berikutnya. Dengan demikian, opsi A benar.</p><p>Jawaban tepat: <strong>B. Tersisa hak cuti persalinan untuk 2 anak</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Hak cuti persalinan tidak gugur total melainkan hanya berkurang satu kuota persalinan.</li><li><strong>C</strong>: Tidak dapat 3 anak lagi karena kuota anak pertama telah terpakai saat status CPNS.</li><li><strong>D</strong>: Tidak ada kewajiban mengganti jam kerja bagi pegawai yang mengambil cuti bersalin resmi.</li><li><strong>E</strong>: Status CPNS tidak ditangguhkan karena melahirkan adalah hak reproduksi yang dilindungi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 62) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pertama, kedua, dan ketiga • Cuti bersalin yang digunakan oleh CPNS wanita</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C4 - Analisis", "question": "PNS wanita yang telah selesai menggunakan hak cuti bersalin tetap memiliki hak cuti kepegawaian lainnya, KECUALI...", "options": ["Cuti besar sesuai ketentuan dinas", "Cuti bersama ketetapan pemerintah", "Cuti tahunan pada tahun berjalan", "Cuti tahunan yang tersisa sebelumnya", "Cuti sakit tanpa surat keterangan dokter"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Analisis Hak Cuti Pasca Cuti Melahirkan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>PNS pasca-cuti bersalin berhak atas: <strong>cuti bersama</strong>, <strong>cuti tahunan berjalan dan sisa</strong>, cuti besar, cuti sakit, dan cuti penting sesuai ketentuan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat sebagai pengecualian karena setiap cuti sakit wajib menyertakan surat keterangan dokter yang sah. Cuti sakit tanpa pembuktian medis merupakan pelanggaran administratif. Oleh sebab itu, opsi A benar.</p><p>Jawaban tepat: <strong>E. Cuti sakit tanpa surat keterangan dokter</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Cuti besar tetap dapat diajukan apabila syarat masa kerja telah terpenuhi.</li><li><strong>B</strong>: Cuti bersama secara eksplisit tetap menjadi hak pegawai yang selesai bersalin.</li><li><strong>C</strong>: Cuti tahunan berjalan tetap berhak dinikmati pasca-penggunaan cuti bersalin.</li><li><strong>D</strong>: Sisa cuti tahunan sebelum bersalin tetap sah digunakan sesuai aturan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 62) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>cuti tahunan pada tahun yang sedang • cuti bersama</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C2 - Pemahaman", "question": "PNS wanita yang telah memiliki anak sebelum diangkat menjadi PNS berhak atas cuti melahirkan untuk...", "options": ["Kelahiran sampai dengan 3 anak setelah jadi PNS", "Satu anak saja setelah resmi menjadi PNS", "Persalinan anak keempat dan anak seterusnya", "Tidak berhak atas cuti melahirkan sama sekali", "Dua anak kandung pertama setelah pelantikan"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Hak Cuti Melahirkan bagi PNS Beranak Sebelum Masuk ASN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>PNS Wanita yang telah memiliki anak sebelum menjadi PNS berhak atas cuti melahirkan untuk <strong>kelahiran sampai dengan 3 (tiga) anak setelah menjadi PNS</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena penghitungan kuota 3 anak cuti melahirkan baru dihitung sejak resmi berstatus PNS. Kelahiran anak sebelum menjadi CPNS/PNS tidak membatalkan kuota 3 anak tersebut. Oleh karena itu, opsi A benar.</p><p>Jawaban tepat: <strong>A. Kelahiran sampai dengan 3 anak setelah jadi PNS</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Satu anak saja tidak tepat karena aturan kepegawaian memberikan hak hingga 3 kelahiran setelah menjadi PNS.</li><li><strong>C</strong>: Persalinan anak keempat menggunakan skema cuti besar, bukan cuti melahirkan standar.</li><li><strong>D</strong>: PNS wanita tetap berhak atas cuti melahirkan dan hak tersebut tidak gugur karena anak sebelum PNS.</li><li><strong>E</strong>: Kuota resmi yang diatur adalah sampai 3 anak, bukan dibatasi hanya 2 anak.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 63) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>PNS Wanita yang telah memiliki anak sebelum menjadi PNS • sampai dengan 3 (tiga) anak</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan regulasi kepegawaian, lamanya cuti melahirkan bagi PNS wanita secara normatif adalah...", "options": ["60 (enam puluh) hari dinas", "3 (tiga) bulan kalender", "40 (empat puluh) hari kerja", "6 (enam) bulan berturut-turut", "1,5 (satu setengah) bulan"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Durasi Normatif Cuti Melahirkan PNS Wanita</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Lamanya cuti melahirkan adalah 3 (tiga) bulan</strong>, namun dalam kondisi tertentu PNS wanita dapat mengajukan kurang dari 3 bulan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena durasi baku cuti melahirkan bagi PNS wanita adalah 3 bulan. Pegawai dapat mengajukan waktu lebih singkat apabila merasa siap kembali berdinas. Oleh sebab itu, opsi A benar.</p><p>Jawaban tepat: <strong>B. 3 (tiga) bulan kalender</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Enam puluh hari dinas bukan standar baku regulasi yang menggunakan hitungan 3 bulan kalender.</li><li><strong>C</strong>: Empat puluh hari kerja merupakan masa nifas umum namun bukan batasan yuridis cuti ASN.</li><li><strong>D</strong>: Enam bulan merupakan perpanjangan cuti sakit, bukan durasi cuti melahirkan normatif.</li><li><strong>E</strong>: Satu setengah bulan merupakan batas maksimal cuti sakit karena gugur kandungan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 63) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Lamanya cuti melahirkan adalah 3 (tiga) bulan • kurang dari 3 (tiga bulan)</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C2 - Pemahaman", "question": "PNS wanita yang melahirkan anak keempat dan seterusnya diakomodasi hak istirahat persalinannya melalui...", "options": ["Pemberian cuti sakit darurat", "Perpanjangan cuti tahunan", "Pemberian hak cuti besar", "Cuti di luar tanggungan dinas", "Izin pembebasan jam dinas"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Skema Cuti Persalinan Anak Keempat dan Seterusnya</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>PNS wanita dapat diberikan <strong>cuti besar untuk persalinan anaknya yang keempat dan seterusnya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena persalinan anak ke-4 ke atas tidak lagi menggunakan cuti melahirkan, melainkan dialihkan ke fasilitas cuti besar. Hal ini merupakan solusi hukum agar hak kesehatan ibu dan anak tetap terlindungi. Dengan demikian, opsi A benar.</p><p>Jawaban tepat: <strong>C. Pemberian hak cuti besar</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Cuti sakit hanya diperuntukkan bagi gangguan kesehatan, bukan persalinan anak keempat.</li><li><strong>B</strong>: Cuti tahunan tidak dapat diperpanjang khusus untuk menggantikan cuti persalinan anak keempat.</li><li><strong>D</strong>: CLTN bukan skema persalinan rutin melainkan cuti tanpa penghasilan untuk urusan mendesak.</li><li><strong>E</strong>: Izin pembebasan jam dinas bukan status cuti resmi kepegawaian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 63) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>diberikan cuti besar untuk persalinan anaknya • dan seterusnya</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Konsekuensi bagi PNS wanita yang menggunakan cuti besar untuk persalinan anak keempat adalah...", "options": ["Dilarang naik pangkat reguler selama dua tahun", "Gaji pokok dipotong sebesar lima puluh persen", "Kehilangan hak atas seluruh cuti bersama", "Tidak berhak atas cuti tahunan tahun berjalan", "Wajib mengembalikan tunjangan kinerja dinas"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Konsekuensi Cuti Besar Persalinan Anak Keempat</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>PNS wanita yang menggunakan cuti besar untuk persalinan anak keempat <strong>tidak berhak lagi atas cuti tahunannya dalam tahun yang bersangkutan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena penggunaan cuti besar menggantikan alokasi cuti tahunan pada tahun yang sama. Pegawai hanya dapat memanfaatkan sisa cuti tahunan dari tahun sebelum cuti besar digunakan. Maka dari itu, opsi A benar.</p><p>Jawaban tepat: <strong>D. Tidak berhak atas cuti tahunan tahun berjalan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Tidak ada penalti penundaan pangkat bagi penggunaan hak cuti besar persalinan.</li><li><strong>B</strong>: Gaji pokok tetap dibayarkan penuh dan tidak dipotong 50% selama cuti besar.</li><li><strong>C</strong>: Pegawai bersangkutan tetap berhak atas cuti bersama yang ditetapkan pemerintah.</li><li><strong>E</strong>: Tidak ada kewajiban mengembalikan tunjangan kinerja yang telah sah diterima.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 63) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>tidak berhak lagi atas cuti • dalam tahun yang bersangkutan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Selain kehilangan cuti tahunan berjalan, PNS yang mengambil cuti besar persalinan anak keempat tetap berhak atas...", "options": ["Sisa cuti tahunan tahun sebelum cuti besar", "Tambahan cuti istirahat dua puluh hari", "Cuti tahunan penuh tahun berjalan", "Cuti khusus keagamaan tanpa batas", "Dispensasi bebas jam kantor setahun"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Hak Cuti Lain Saat Menjalani Cuti Besar Anak Keempat</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>PNS wanita yang menggunakan cuti besar persalinan berhak atas: <strong>cuti bersama</strong>, <strong>sisa cuti tahunan tahun sebelumnya</strong>, cuti sakit, dan cuti alasan penting.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena sisa cuti tahunan dari tahun sebelumnya tidak hangus dan tetap dapat dimanfaatkan. Ini menjamin sisa hak cuti masa lalu pegawai tetap terlindungi. Dengan demikian, opsi A benar.</p><p>Jawaban tepat: <strong>A. Sisa cuti tahunan tahun sebelum cuti besar</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Tidak ada fasilitas tambahan cuti istirahat 20 hari di luar regulasi cuti.</li><li><strong>C</strong>: Cuti tahunan tahun berjalan dinyatakan gugur/tidak berhak lagi diambil.</li><li><strong>D</strong>: Cuti keagamaan tetap tunduk pada ketentuan cuti besar atau alasan penting, tidak tanpa batas.</li><li><strong>E</strong>: Tidak ada dispensasi bebas jam kantor setahun bagi pegawai aktif.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 63) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>cuti tahunan yang tersisa pada tahun • cuti bersama</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C2 - Pemahaman", "question": "PNS yang telah kembali aktif bekerja pasca-menjalani CLTN berhak atas cuti besar setelah bekerja kembali paling kurang...", "options": ["5 (lima) tahun masa dinas aktif", "6 (enam) tahun secara terus-menerus", "3 (tiga) tahun kalender dinas", "1 (satu) tahun secara konsisten", "2 (dua) tahun masa penugasan"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Syarat Cuti Besar Pasca Cuti di Luar Tanggungan Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>PNS yang telah menggunakan CLTN berhak atas cuti besar setelah bekerja kembali <strong>paling kurang 6 (enam) tahun secara terus-menerus</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena ketentuan cuti mensyaratkan masa pengabdian 6 tahun terus-menerus pasca-CLTN untuk memperoleh cuti besar kembali. Aturan ini memulihkan akumulasi masa kerja cuti besar dari nol. Oleh karena itu, opsi A benar.</p><p>Jawaban tepat: <strong>B. 6 (enam) tahun secara terus-menerus</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Lima tahun merupakan syarat masa kerja awal saat hendak mengajukan CLTN, bukan syarat cuti besar pasca-CLTN.</li><li><strong>C</strong>: Tiga tahun merupakan durasi maksimal pemberian izin awal CLTN.</li><li><strong>D</strong>: Satu tahun merupakan syarat untuk memulihkan hak cuti tahunan setelah CLTN.</li><li><strong>E</strong>: Dua tahun tidak memiliki dasar hukum dalam persyaratan cuti besar pasca-CLTN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 63) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>cuti besar setelah bekerja kembali paling kurang 6 (enam) tahun • secara terus- menerus</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Kompensasi finansial yang diterima oleh PNS selama menjalankan Cuti Karena Alasan Penting adalah...", "options": ["Diberikan uang tunggu sebesar lima puluh persen", "Menerima tunjangan jabatan tanpa pembayaran gaji pokok", "Memperoleh penghasilan dan tunjangan sesuai aturan", "Tidak berhak menerima penghasilan dari kas negara", "Tunjangan kinerja dipotong sebesar delapan puluh persen"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Hak Finansial Selama Cuti Karena Alasan Penting</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Selama menjalankan cuti karena alasan penting, PNS <strong>memperoleh penghasilan dan tunjangan sesuai peraturan perundang-undangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena cuti karena alasan penting memberikan jaminan penghasilan dan tunjangan penuh bagi pegawai. Negara menjamin perlindungan finansial saat pegawai mengalami peristiwa mendesak. Oleh sebab itu, opsi A benar.</p><p>Jawaban tepat: <strong>C. Memperoleh penghasilan dan tunjangan sesuai aturan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Uang tunggu hanya diberikan bagi pegawai yang diberhentikan dengan hormat karena sakit/perampingan.</li><li><strong>B</strong>: Kurang tepat, selama cuti karena alasan penting PNS tetap menerima gaji pokok dan tunjangan sah.</li><li><strong>D</strong>: Penghentian penghasilan hanya berlaku pada Cuti di Luar Tanggungan Negara (CLTN).</li><li><strong>E</strong>: Tidak ada ketentuan pemotongan tunjangan kinerja hingga 80% dalam pasal cuti alasan penting.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 63) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>cuti karena alasan penting • memperoleh penghasilan dan tunjangan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C3 - Aplikasi", "question": "Ibu Ratna memiliki 2 anak sebelum lulus CPNS. Setelah menjadi PNS, ia melahirkan anak berikutnya. Hak cuti melahirkan yang sah adalah...", "options": ["Diberikan izin tidak masuk kantor sementara selama tiga pekan", "Diberikan hak pemanfaatan sisa cuti tahunan tahun berjalan", "Wajib mengajukan cuti di luar tanggungan", "Tetap berhak atas cuti melahirkan selama tiga bulan penuh", "Diberikan penyesuaian jam kerja fleksibel selama enam bulan"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penerapan Hak Cuti Melahirkan Pegawai Rekrutan Baru</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>PNS wanita yang telah beranak sebelum menjadi PNS berhak cuti melahirkan <strong>sampai dengan 3 anak setelah menjadi PNS</strong>, dengan durasi <strong>3 bulan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena kelahiran tersebut adalah kelahiran pertama Ibu Ratna setelah berstatus PNS. Karena kuota 3 anak dihitung sejak berstatus PNS, ia berhak penuh atas cuti melahirkan 3 bulan. Dengan demikian, opsi A benar.</p><p>Jawaban tepat: <strong>D. Tetap berhak atas cuti melahirkan selama tiga bulan penuh</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat, izin sementara tiga pekan tidak sesuai regulasi baku cuti melahirkan.</li><li><strong>B</strong>: Kurang tepat, cuti melahirkan merupakan hak tersendiri dan tidak menggantikan cuti tahunan.</li><li><strong>C</strong>: Tidak perlu mengajukan CLTN karena hak normatif cuti melahirkan masih tersedia.</li><li><strong>E</strong>: Kurang tepat, penyesuaian jam kerja bukan merupakan mekanisme pengganti cuti melahirkan resmi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 63) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>PNS Wanita yang telah memiliki anak sebelum menjadi PNS • setelah menjadi PNS</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C3 - Aplikasi", "question": "Seorang pejabat fungsional wanita mengajukan cuti untuk persalinan anaknya yang keempat. Administrasi kepegawaian wajib memprosesnya sebagai...", "options": ["Cuti di luar tanggungan negara selama enam bulan", "Cuti melahirkan biasa dengan pemotongan uang makan", "Cuti tahunan tambahan sebanyak tiga puluh hari", "Cuti sakit berat dengan surat dokter pemerintah", "Cuti besar persalinan dan menghapus cuti tahunan"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Implementasi Pengajuan Cuti Persalinan Anak Keempat</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Persalinan anak keempat diproses melalui <strong>cuti besar</strong>, dan yang bersangkutan <strong>tidak berhak lagi atas cuti tahunannya</strong> pada tahun tersebut.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena mekanisme yang sah untuk anak keempat adalah cuti besar dengan implikasi gugurnya hak cuti tahunan pada tahun berjalan. Ini menjamin tertib kepatuhan administrasi ASN. Oleh sebab itu, opsi A benar.</p><p>Jawaban tepat: <strong>E. Cuti besar persalinan dan menghapus cuti tahunan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: CLTN tidak relevan jika pegawai masih memiliki hak cuti besar kepegawaian.</li><li><strong>B</strong>: Bukan diproses sebagai cuti melahirkan biasa karena kuota cuti melahirkan dibatasi sampai anak ketiga.</li><li><strong>C</strong>: Tidak ada istilah cuti tahunan tambahan 30 hari untuk persalinan.</li><li><strong>D</strong>: Persalinan normal anak keempat bukan merupakan kategori cuti sakit berat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 63) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>diberikan cuti besar untuk persalinan anaknya • tidak berhak lagi atas cuti</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C3 - Aplikasi", "question": "Seorang PNS kembali bekerja pada tahun 2020 pasca-CLTN. Waktu tercepat bagi pegawai tersebut untuk dapat mengajukan cuti besar adalah pada tahun...", "options": ["2025 setelah 5 tahun dinas berjalan", "2021 setelah 1 tahun bekerja secara aktif", "2023 setelah 3 tahun berturut-turut", "2026 setelah 6 tahun bekerja terus-menerus", "2022 setelah masa orientasi 2 tahun"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penerapan Pemulihan Hak Cuti Besar Pasca-CLTN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Hak atas cuti besar bagi pegawai pasca-CLTN baru terbuka setelah <strong>bekerja kembali paling kurang 6 (enam) tahun secara terus-menerus</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena hitungan masa kerja 6 tahun dari tahun 2020 jatuh pada tahun 2026. Persyaratan ini wajib dipenuhi tanpa terputus cuti nir-tanggungan lainnya. Oleh sebab itu, opsi A benar.</p><p>Jawaban tepat: <strong>D. 2026 setelah 6 tahun bekerja terus-menerus</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Lima tahun (2025) kurang 1 tahun dari syarat minimal 6 tahun terus-menerus.</li><li><strong>B</strong>: Satu tahun (2021) hanya memulihkan hak atas cuti tahunan, bukan cuti besar.</li><li><strong>C</strong>: Tiga tahun (2023) belum memenuhi masa pengabdian 6 tahun.</li><li><strong>E</strong>: Dua tahun (2022) tidak memenuhi kriteria yuridis pemulihan hak cuti besar.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 63) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>cuti besar setelah bekerja kembali paling kurang 6 (enam) tahun • secara terus- menerus</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C4 - Analisis", "question": "PNS wanita yang telah selesai menggunakan fasilitas CLTN dan aktif bekerja kembali tetap memiliki hak cuti berikut, KECUALI...", "options": ["Cuti karena alasan penting sesuai regulasi", "Cuti bersama yang ditetapkan pemerintah", "Cuti tahunan pada tahun yang sedang berjalan", "Cuti tahunan yang tersisa sebelum menjalani CLTN", "Cuti besar langsung pada tahun pertama aktif"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Analisis Hak Cuti bagi PNS Pasca Cuti di Luar Tanggungan Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>PNS pasca-CLTN berhak atas <strong>cuti bersama</strong>, <strong>cuti tahunan</strong>, cuti sakit, dan cuti penting. Adapun <strong>cuti besar baru dapat diajukan setelah bekerja kembali 6 tahun terus-menerus</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat sebagai pengecualian karena cuti besar tidak dapat diambil langsung pada tahun pertama pasca-CLTN. Pegawai wajib mengabdi sekurang-kurangnya 6 tahun tanpa putus terlebih dahulu. Dengan demikian, opsi A benar.</p><p>Jawaban tepat: <strong>E. Cuti besar langsung pada tahun pertama aktif</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Cuti karena alasan penting tetap dapat diberikan jika musibah keluarga terjadi.</li><li><strong>B</strong>: Cuti bersama merupakan hak otomatis yang tetap diperoleh pegawai aktif.</li><li><strong>C</strong>: Cuti tahunan berjalan tetap berhak diperoleh sesuai ketentuan berlaku.</li><li><strong>D</strong>: Sisa cuti tahunan sebelum CLTN tetap diakui hak penggunaannya.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 63) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>PNS wanita yang telah menggunakan cuti di luar tanggungan negara • cuti bersama</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan regulasi manajemen ASN, Cuti Karena Alasan Penting diberikan untuk paling lama...", "options": ["1 (satu) bulan kalender", "3 (tiga) bulan hari dinas", "14 (empat belas) hari kerja", "60 (enam puluh) hari penuh", "6 (enam) bulan berturut-turut"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Batas Maksimal Cuti Karena Alasan Penting</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Cuti karena alasan penting diberikan untuk paling lama 1 (satu) bulan</strong> sesuai pertimbangan pejabat berwenang.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena durasi maksimal Cuti Karena Alasan Penting ditetapkan paling lama 1 bulan kalender. Waktu tersebut dinilai cukup untuk menyelesaikan urusan mendesak keluarga atau pemulihan bencana. Dengan demikian, opsi A benar.</p><p>Jawaban tepat: <strong>A. 1 (satu) bulan kalender</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Tiga bulan merupakan alokasi durasi cuti melahirkan atau cuti besar.</li><li><strong>C</strong>: Empat belas hari merupakan batas pengajuan cuti sakit biasa.</li><li><strong>D</strong>: Enam puluh hari melebihi batas legalitas 1 bulan untuk cuti alasan penting.</li><li><strong>E</strong>: Enam bulan merupakan durasi maksimal perpanjangan cuti sakit berat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 64) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Cuti karena alasan penting diberikan untuk paling lama 1 (satu) bulan • Cuti Karena Alasan Penting</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C2 - Pemahaman", "question": "PNS yang melangsungkan perkawinan yang sah berhak mengajukan cuti kedinasan melalui skema...", "options": ["Cuti tahunan tambahan daerah", "Cuti karena alasan penting", "Cuti di luar tanggungan negara", "Izin tidak masuk dinas harian", "Cuti besar istirahat panjang"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Cuti Alasan Penting untuk Melangsungkan Pernikahan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>PNS berhak atas <strong>cuti karena alasan penting</strong> apabila <strong>melangsungkan perkawinan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena pernikahan pertama maupun sah pegawai difasilitasi resmi melalui Cuti Karena Alasan Penting. Ini memberi legitimasi ketidakhadiran tanpa memotong hak cuti tahunan. Oleh sebab itu, opsi A benar.</p><p>Jawaban tepat: <strong>B. Cuti karena alasan penting</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Tidak ada nomenklatur cuti tahunan tambahan khusus pernikahan dalam tata aturan ASN.</li><li><strong>C</strong>: CLTN ditujukan untuk alasan mendesak jangka panjang tanpa menerima gaji negara.</li><li><strong>D</strong>: Izin tidak masuk dinas harian bukan status hukum cuti melainkan dispensasi absensi.</li><li><strong>E</strong>: Cuti besar memerlukan syarat kerja 5 tahun dan bukan skema pokok pernikahan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 64) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>melangsungkan perkawinan • cuti karena alasan penting</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Cuti karena alasan penting dapat diberikan kepada PNS laki-laki dengan alasan khusus yaitu...", "options": ["Membeli rumah tempat tinggal baru", "Mendampingi anak ujian sekolah kedinasan", "Istrinya melahirkan atau operasi sesar", "Menghadiri acara wisuda kerabat jauh", "Mengikuti kegiatan rekreasi keluarga tahunan"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Hak Cuti Suami Dampingi Istri Operasi Sesar</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Cuti karena alasan penting dapat diberikan untuk <strong>PNS laki-laki yang istrinya melahirkan/operasi sesar</strong> guna memberikan pendampingan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena regulasi kepegawaian memberikan hak cuti penting bagi suami saat istri menjalani proses persalinan atau operasi sesar. Hak ini menjamin peran ayah dalam mendampingi keluarga. Maka dari itu, opsi A benar.</p><p>Jawaban tepat: <strong>C. Istrinya melahirkan atau operasi sesar</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Membeli properti/rumah pribadi tidak termasuk kategori peristiwa mendesak.</li><li><strong>B</strong>: Mendampingi anak ujian bukan alasan yuridis yang sah untuk cuti alasan penting.</li><li><strong>D</strong>: Wisuda kerabat jauh merupakan urusan sosial biasa yang dapat menggunakan cuti tahunan.</li><li><strong>E</strong>: Rekreasi keluarga harus menggunakan cuti tahunan, bukan cuti alasan penting.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 64) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>PNS laki-laki yang istrinya melahirkan/operasi sesar • Cuti karena alasan penting</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Ruang lingkup keluarga yang sakit keras atau wafat sehingga PNS berhak cuti alasan penting mencakup...", "options": ["Kakek, nenek, paman, dan bibi sekandung", "Pasangan sah, anak kandung, serta saudara sepupu satu garis wali", "Seluruh keluarga besar hingga derajat ketiga", "Sepupu satu garis keturunan dan keponakan", "Ibu, bapak, istri/suami, anak, adik, kakak, mertua, menantu"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Ruang Lingkup Anggota Keluarga Sakit Keras atau Meninggal</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Cuti alasan penting diberikan jika <strong>ibu, bapak, istri atau suami, anak, adik, kakak, mertua, atau menantu sakit keras atau meninggal dunia</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena pasal cuti alasan penting menyebutkan secara limitatif daftar keluarga inti: orang tua, pasangan, anak, saudara kandung (adik/kakak), mertua, dan menantu. Dengan demikian, opsi A benar.</p><p>Jawaban tepat: <strong>E. Ibu, bapak, istri/suami, anak, adik, kakak, mertua, menantu</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kakek, nenek, paman, dan bibi tidak masuk dalam rumusan pasal Cuti Alasan Penting.</li><li><strong>B</strong>: Kurang tepat, saudara sepupu tidak termasuk dalam kategori keluarga inti yang sah untuk cuti alasan penting.</li><li><strong>C</strong>: Terlalu luas dan tidak sesuai rincian limitatif peraturan kepegawaian.</li><li><strong>D</strong>: Sepupu dan keponakan tidak tercantum dalam daftar keluarga inti penerima hak cuti.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 64) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>ibu, bapak, istri atau suami, anak, • sakit keras atau meninggal dunia</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C2 - Pemahaman", "question": "PNS yang ditempatkan pada perwakilan Republik Indonesia yang rawan dan/atau berbahaya dapat diberikan cuti penting untuk...", "options": ["Memulihkan kondisi kejiwaan pegawai", "Mencari peluang mutasi dinas baru", "Menambah tunjangan luar negeri", "Mengikuti program pembekalan diplomatik", "Melakukan penugasan intelijen tambahan"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Cuti Alasan Penting bagi PNS Perwakilan Luar Negeri Rawan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Cuti alasan penting dapat diberikan bagi PNS di kantor perwakilan rawan/berbahaya <strong>guna memulihkan kondisi kejiwaan PNS yang bersangkutan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena penugasan pada zona konflik atau daerah berbahaya berdampak psikologis tinggi, sehingga negara memberikan cuti penting guna pemulihan trauma/kejiwaan. Oleh karena itu, opsi A benar.</p><p>Jawaban tepat: <strong>A. Memulihkan kondisi kejiwaan pegawai</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Cuti tidak diberikan untuk mencari peluang mutasi dinas.</li><li><strong>C</strong>: Tunjangan luar negeri diatur tersendiri dan bukan tujuan pemberian cuti pemulihan jiwa.</li><li><strong>D</strong>: Kurang tepat, pembekalan diplomatik merupakan bagian dari pengembangan kompetensi, bukan tujuan cuti pemulihan kondisi rawan.</li><li><strong>E</strong>: Tugas intelijen bukan merupakan alasan yuridis pemberian hak cuti alasan penting.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 64) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Indonesia yang rawan dan/atau berbahaya • kondisi kejiwaan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan peraturan kepegawaian, PNS yang sedang menjalani masa tugas belajar berhak atas cuti...", "options": ["Cuti tahunan penuh selama dua belas hari kerja", "Cuti bersama, cuti melahirkan, dan cuti besar anak keempat", "Cuti besar untuk ibadah haji reguler pertama", "Cuti di luar tanggungan negara selama dua tahun", "Cuti tahunan bebas jam kuliah kampus"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Hak Cuti bagi PNS yang Sedang Tugas Belajar</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>PNS yang sedang tugas belajar berhak atas: <strong>cuti bersama</strong>, <strong>cuti melahirkan</strong>, dan <strong>cuti besar untuk persalinan anaknya yang keempat</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena hak cuti pegawai yang sedang tugas belajar dibatasi hanya pada cuti bersama, cuti melahirkan, dan cuti besar anak keempat. Cuti tahunan tidak diberikan selama tugas belajar. Maka dari itu, opsi A benar.</p><p>Jawaban tepat: <strong>B. Cuti bersama, cuti melahirkan, dan cuti besar anak keempat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Cuti tahunan tidak berlaku bagi PNS yang sedang menjalani status tugas belajar.</li><li><strong>C</strong>: Cuti besar untuk ibadah haji tidak dapat diambil selama masa aktif tugas belajar.</li><li><strong>D</strong>: CLTN tidak dapat digabungkan di tengah-tengah masa ikatan tugas belajar.</li><li><strong>E</strong>: Tidak ada cuti tahunan bebas jam kuliah dalam ketentuan administrasi ASN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 64) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>PNS yang sedang tugas belajar, berhak atas: • cuti melahirkan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C3 - Aplikasi", "question": "Rumah tinggal seorang penelaah pajak tertimpa tanah longsor dan rusak berat. Status cuti kedinasan yang dapat diajukan adalah...", "options": ["Cuti di luar tanggungan negara sementara", "Cuti tahunan yang dipotong otomatis", "Cuti karena alasan penting keadaan kahar", "Cuti sakit tanpa surat dokter klinik", "Izin libur tanpa persetujuan tertulis"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penerapan Cuti Akibat Bencana Alam</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>PNS berhak atas <strong>cuti karena alasan penting karena terjadinya kondisi force majeur, misalnya banjir, tanah longsor, kebakaran, dan gempa bumi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena musibah bencana alam tanah longsor masuk kategori keadaan kahar (force majeure) yang sah untuk Cuti Karena Alasan Penting. Ini memberi waktu pegawai memulihkan tempat tinggalnya. Dengan demikian, opsi A benar.</p><p>Jawaban tepat: <strong>C. Cuti karena alasan penting keadaan kahar</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: CLTN tidak relevan untuk musibah bencana alam jangka pendek.</li><li><strong>B</strong>: Bencana alam tidak boleh langsung memotong hak normatif cuti tahunan pegawai.</li><li><strong>D</strong>: Cuti sakit hanya untuk gangguan medis biologis, bukan kerusakan fisik bangunan rumah.</li><li><strong>E</strong>: Setiap cuti wajib diajukan dan disetujui tertulis oleh pejabat berwenang.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 64) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>tanah longsor, kebakaran, dan gempa bumi • cuti karena alasan penting</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C3 - Aplikasi", "question": "Seorang PNS pria mengajukan izin mendampingi istrinya yang menjalani operasi sesar darurat. Tindakan kepegawaian yang benar adalah...", "options": ["Mengharuskan penggunaan cuti besar enam bulan", "Mengarahkan penggunaan izin dispensasi harian selama tiga hari", "Memotong gaji pokok pegawai sebesar sepertiga", "Memberikan cuti alasan penting maksimal 1 bulan kalender", "Mewajibkan lembur pengganti selama dua pekan"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Implementasi Suami Dampingi Istri Melahirkan Caesar</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>PNS laki-laki berhak atas <strong>cuti karena alasan penting</strong> saat istri melahirkan/operasi sesar dengan durasi <strong>paling lama 1 bulan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena regulasi menjamin hak cuti alasan penting bagi PNS pria yang istrinya operasi sesar. Durasi diberikan sesuai pertimbangan kondisi medis hingga maksimal 1 bulan. Oleh sebab itu, opsi A benar.</p><p>Jawaban tepat: <strong>D. Memberikan cuti alasan penting maksimal 1 bulan kalender</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Cuti besar memerlukan masa kerja 5 tahun dan bukan prosedur pendampingan persalinan.</li><li><strong>B</strong>: Kurang tepat, dispensasi harian tidak mencukupi untuk pendampingan persalinan operasi sesar yang diwadahi melalui cuti penting hingga satu bulan.</li><li><strong>C</strong>: Penghasilan dan tunjangan tetap berhak diterima penuh tanpa pemotongan sepertiga.</li><li><strong>E</strong>: Tidak ada aturan kompensasi lembur pengganti bagi hak cuti alasan penting.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 64) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>PNS laki-laki yang istrinya melahirkan/operasi sesar • paling lama 1 (satu) bulan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C3 - Aplikasi", "question": "Ayah kandung seorang pejabat perbendaharaan wafat dan pegawai tersebut ditunjuk ahli waris mengurus hak pensiun taspen. Prosedur yang sah adalah...", "options": ["Mengajukan cuti sakit surat keterangan kelurahan", "Mangkir tugas karena alasan adat istiadat", "Mengajukan cuti alasan penting mengurus hak keluarga", "Meminta uang duka tanpa surat kematian resmi", "Mengalihkan pekerjaan ke rekan secara lisan"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penerapan Cuti Mengurus Hak Keluarga Meninggal</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>PNS berhak cuti alasan penting apabila anggota keluarga meninggal dan pegawai bersangkutan <strong>harus mengurus hak-hak dari anggota keluarganya yang meninggal</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena pengurusan hak administratif keluarga yang meninggal dunia dilindungi secara yuridis melalui Cuti Karena Alasan Penting. Ini memastikan legalitas kehadiran dinas pegawai. Maka dari itu, opsi A benar.</p><p>Jawaban tepat: <strong>C. Mengajukan cuti alasan penting mengurus hak keluarga</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Surat kelurahan bukan bukti medis untuk pengajuan cuti sakit.</li><li><strong>B</strong>: Mangkir kerja merupakan pelanggaran disiplin berat aparatur sipil negara.</li><li><strong>D</strong>: Pemberian hak finansial wajib disertai kelengkapan dokumen sah akta kematian.</li><li><strong>E</strong>: Pendelegasian tugas wajib diketahui dan disetujui atasan secara tertulis.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 64) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>harus mengurus hak-hak dari anggota keluarganya • cuti karena alasan penting</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C4 - Analisis", "question": "PNS yang telah selesai menggunakan Cuti Karena Alasan Penting tetap memiliki hak cuti kepegawaian lainnya, KECUALI...", "options": ["Sisa cuti tahunan sebelum cuti alasan penting", "Cuti bersama yang ditetapkan pemerintah", "Cuti tahunan pada tahun yang sedang berjalan", "Cuti bebas dinas tanpa persetujuan pimpinan", "Cuti besar sesuai persyaratan kepegawaian"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Analisis Hak Cuti Pasca Cuti Alasan Penting</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>PNS pasca-cuti alasan penting berhak atas: <strong>cuti bersama</strong>, <strong>cuti tahunan berjalan dan sisa</strong>, cuti besar, cuti sakit, dan cuti melahirkan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat sebagai pengecualian karena dalam manajemen kepegawaian tidak ada cuti bebas dinas tanpa persetujuan. Setiap cuti wajib mendapatkan penetapan dari pejabat yang berwenang. Oleh sebab itu, opsi A benar.</p><p>Jawaban tepat: <strong>D. Cuti bebas dinas tanpa persetujuan pimpinan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Sisa cuti tahunan sebelum peristiwa tetap dapat dimanfaatkan.</li><li><strong>B</strong>: Cuti bersama secara tegas tetap menjadi hak normatif pegawai.</li><li><strong>C</strong>: Cuti tahunan tahun berjalan tidak gugur akibat penggunaan cuti alasan penting.</li><li><strong>E</strong>: Cuti besar tetap dapat diajukan sesuai siklus lima tahunan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 64) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>PNS yang telah menggunakan cuti karena • cuti tahunan pada tahun yang sedang berjalan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C4 - Analisis", "question": "PNS yang sedang tugas belajar mengalami pembatasan hak cuti. Di antara jenis cuti berikut yang TIDAK BERHAK diambil selama tugas belajar adalah...", "options": ["Cuti besar persalinan anak keempat", "Cuti bersama nasional", "Cuti bersalin anak kedua", "Cuti melahirkan anak pertama", "Cuti tahunan reguler kantor"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Analisis Pembatasan Hak Cuti Masa Tugas Belajar</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>PNS yang sedang tugas belajar <strong>hanya berhak atas cuti bersama, cuti melahirkan, dan cuti besar persalinan anak keempat</strong>, sehingga <strong>tidak berhak atas cuti tahunan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena cuti tahunan ditiadakan bagi pegawai tugas belajar. Kalender akademik kampus telah menyediakan masa libur semester perkuliahan. Dengan demikian, opsi A benar.</p><p>Jawaban tepat: <strong>E. Cuti tahunan reguler kantor</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Cuti besar khusus persalinan anak keempat sah diberikan saat tugas belajar.</li><li><strong>B</strong>: Cuti bersama tetap dinikmati pegawai tugas belajar mengikuti hari libur nasional.</li><li><strong>C</strong>: Cuti melahirkan anak kedua tetap menjadi hak normatif wanita tugas belajar.</li><li><strong>D</strong>: Cuti melahirkan anak pertama sah diambil oleh mahasiswi tugas belajar.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 64) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>PNS yang sedang tugas belajar, berhak atas: • cuti bersama</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C2 - Pemahaman", "question": "PNS yang sedang tugas belajar di luar negeri mengajukan cuti bersalin kepada pejabat berwenang melalui...", "options": ["Kepala Perwakilan Republik Indonesia di negara terkait", "Atasan langsung kantor lama di tanah air", "Kementerian Luar Negeri secara langsung", "Lembaga beasiswa penyandang dana studi", "Organisasi perhimpunan pelajar Indonesia"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Mekanisme Pengajuan Cuti Persalinan Tugas Belajar</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Permohonan cuti bersalin bagi PNS tugas belajar diajukan kepada pejabat berwenang melalui <strong>Pimpinan Perguruan Tinggi atau Kepala Perwakilan Republik Indonesia</strong> di negara terkait.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena perwakilan RI (KBRI/KJRI) merupakan otoritas pembina resmi WNI/ASN di luar negeri. Saluran ini menjamin verifikasi status studi dan legalitas administrasi. Oleh karena itu, opsi A benar.</p><p>Jawaban tepat: <strong>A. Kepala Perwakilan Republik Indonesia di negara terkait</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Atasan lama di tanah air tidak memiliki yurisdiksi harian atas mahasiswa tugas belajar di luar negeri.</li><li><strong>C</strong>: Kementerian Luar Negeri pusat bukan jalur langsung pengajuan cuti pegawai Kemenkeu.</li><li><strong>D</strong>: Pemberi beasiswa hanya mengelola pendanaan, bukan izin cuti dinas kepegawaian.</li><li><strong>E</strong>: Organisasi pelajar adalah organisasi kemahasiswaan non-birokrasi pemerintah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 65) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Perguruan Tinggi atau Kepala Perwakilan Republik • pejabat yang berwenang memberikan cuti</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C2 - Pemahaman", "question": "PNS yang telah selesai tugas belajar berhak atas cuti tahunan berjalan setelah bekerja kembali sekurang-kurangnya...", "options": ["1 (satu) tahun kalender kerja", "3 (tiga) bulan masa dinas aktif", "6 (enam) bulan penugasan satker", "1 (satu) bulan masa penyesuaian", "2 (dua) tahun berturut-turut"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Syarat Pemulihan Cuti Tahunan Pasca Tugas Belajar</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>PNS yang telah selesai tugas belajar berhak atas cuti tahunan berjalan setelah <strong>bekerja kembali sekurang-kurangnya 3 (tiga) bulan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena regulasi mewajibkan pengabdian aktif minimal 3 bulan pasca-studi sebelum hak cuti tahunan berjalan dipulihkan kembali. Ini memastikan re-entry pegawai ke unit kerja berjalan stabil. Oleh sebab itu, opsi A benar.</p><p>Jawaban tepat: <strong>B. 3 (tiga) bulan masa dinas aktif</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Satu tahun merupakan masa tunggu pemulihan cuti tahunan pasca-CLTN, bukan pasca-tugas belajar.</li><li><strong>C</strong>: Enam bulan bukan batas minimal pemulihan cuti tahunan alumni tugas belajar.</li><li><strong>D</strong>: Satu bulan terlalu cepat dan belum memenuhi batas normatif 3 bulan bekerja.</li><li><strong>E</strong>: Dua tahun tidak memiliki dasar yuridis dalam pemulihan cuti tugas belajar.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 65) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>lingkungan Kementerian Keuangan sekurang-kurangnya 3 (tiga) • cuti tahunan pada tahun yang sedang berjalan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Syarat minimal masa kerja bagi PNS untuk dapat mengajukan Cuti di Luar Tanggungan Negara (CLTN) adalah...", "options": ["Telah lulus masa percobaan satu tahun", "Bekerja minimal 3 tahun sejak CPNS", "Bekerja sekurang-kurangnya 6 tahun dinas", "Bekerja paling kurang 5 tahun terus-menerus", "Mencapai masa kerja golongan delapan tahun"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Syarat Minimal Masa Kerja Pengajuan CLTN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>PNS yang telah <strong>bekerja paling kurang 5 (lima) tahun secara terus-menerus</strong> dapat diberikan cuti di luar tanggungan negara karena alasan pribadi dan mendesak.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena syarat mutlak pengajuan CLTN adalah masa pengabdian sekurang-kurangnya 5 tahun tanpa putus. Persyaratan ini membuktikan loyalitas dasar pegawai sebelum cuti panjang nir-gaji. Dengan demikian, opsi A benar.</p><p>Jawaban tepat: <strong>D. Bekerja paling kurang 5 tahun terus-menerus</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Masa percobaan 1 tahun hanya syarat pengangkatan CPNS menjadi PNS penuh.</li><li><strong>B</strong>: Tiga tahun belum memenuhi ambang batas 5 tahun masa kerja yang ditentukan aturan.</li><li><strong>C</strong>: Enam tahun merupakan syarat pengajuan cuti besar pasca-CLTN, bukan syarat awal CLTN.</li><li><strong>E</strong>: Delapan tahun melebihi batas normatif 5 tahun pengajuan CLTN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 65) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>bekerja paling kurang 5 (lima) tahun secara terusmenerus • Cuti di Luar Tanggungan Negara</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Pemberian izin Cuti di Luar Tanggungan Negara (CLTN) hanya dapat disetujui apabila didasarkan pada...", "options": ["Keikutsertaan dalam kampanye politik pemilu serentak", "Keinginan mencoba profesi lain di sektor swasta", "Kebutuhan istirahat jenuh dari rutinitas kedinasan", "Rencana membangun unit usaha bisnis komersial pribadi", "Alasan pribadi penting dan mendesak dengan bukti pendukung"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dasar Alasan Pengajuan CLTN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>CLTN diberikan karena <strong>alasan-alasan pribadi yang penting dan mendesak dengan bukti-bukti yang mendukung</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena CLTN merupakan fasilitas luar biasa yang menuntut adanya justifikasi mendesak dan bukti otentik, seperti mendampingi suami/istri dinas negara. Oleh karena itu, opsi A benar.</p><p>Jawaban tepat: <strong>E. Alasan pribadi penting dan mendesak dengan bukti pendukung</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Aparatur sipil negara wajib netral dan dilarang terlibat kampanye politik praktis.</li><li><strong>B</strong>: Mencoba profesi swasta dilarang dan berpotensi menimbulkan benturan kepentingan ASN.</li><li><strong>C</strong>: Istirahat jenuh rutinitas cukup diakomodasi melalui cuti tahunan atau cuti besar.</li><li><strong>D</strong>: Membangun bisnis komersial bukan alasan sah pemberian CLTN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 65) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>alasan-alasan pribadi yang penting dan mendesak • Cuti di Luar Tanggungan Negara</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan tata aturan kepegawaian, Cuti di Luar Tanggungan Negara dapat diberikan untuk waktu paling lama...", "options": ["3 (tiga) tahun kalender", "5 (lima) tahun berturut-turut", "1 (satu) tahun masa penugasan", "2 (dua) tahun dinas penuh", "6 (enam) bulan berjalan"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Batas Waktu Awal Pemberian CLTN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Cuti di luar tanggungan negara dapat diberikan untuk paling lama 3 (tiga) tahun</strong> dan dapat diperpanjang paling lama 1 tahun.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena batas waktu persetujuan awal CLTN adalah maksimal 3 tahun. Waktu ini diberikan agar pegawai dapat menuntaskan urusan pribadinya yang mendesak. Maka dari itu, opsi A benar.</p><p>Jawaban tepat: <strong>A. 3 (tiga) tahun kalender</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Lima tahun merupakan syarat masa kerja untuk mengajukan, bukan batas durasi CLTN.</li><li><strong>C</strong>: Satu tahun merupakan durasi maksimal perpanjangannya, bukan jangka waktu awal.</li><li><strong>D</strong>: Dua tahun bukan batas maksimal normatif undang-undang ASN.</li><li><strong>E</strong>: Enam bulan terlalu pendek dan bukan batas durasi awal CLTN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 65) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>paling lama 3 • Cuti di luar tanggungan negara dapat diberikan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Perpanjangan izin Cuti di Luar Tanggungan Negara (CLTN) dapat diberikan untuk waktu paling lama...", "options": ["3 (tiga) tahun secara otomatis", "1 (satu) tahun apabila ada alasan penting", "2 (dua) tahun berturut-turut", "6 (enam) bulan masa tambahan", "5 (lima) tahun masa perpanjangan"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Ketentuan Perpanjangan Izin CLTN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>CLTN <strong>dapat diperpanjang paling lama 1 (satu) tahun apabila ada alasan-alasan yang penting untuk memperpanjangnya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena perpanjangan CLTN dibatasi maksimal 1 tahun dan memerlukan alasan penting serta persetujuan tertulis instansi pembina. Dengan demikian, opsi A benar.</p><p>Jawaban tepat: <strong>B. 1 (satu) tahun apabila ada alasan penting</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Tiga tahun merupakan durasi awal, perpanjangan tidak dapat diberikan 3 tahun lagi.</li><li><strong>C</strong>: Dua tahun melampaui ketentuan batas waktu perpanjangan maksimal 1 tahun.</li><li><strong>D</strong>: Enam bulan bukan batas maksimal perpanjangan CLTN yang diatur pasal.</li><li><strong>E</strong>: Lima tahun melebihi batas kumulatif total CLTN (3 + 1 tahun).</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 65) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>dapat diperpanjang paling lama 1 (satu) tahun • alasan-alasan yang penting untuk memperpanjangnya</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C2 - Pemahaman", "question": "PNS yang baru saja selesai tugas belajar dan bekerja kembali langsung berhak atas cuti berikut tanpa menunggu 3 bulan, yaitu...", "options": ["Cuti bersama dan cuti rekreasi dinas", "Cuti tahunan berjalan dan cuti besar haji", "Cuti sakit, cuti melahirkan, dan cuti alasan penting", "Cuti tahunan penugasan luar negeri", "Cuti di luar tanggungan negara langsung"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Hak Cuti Langsung Pasca Selesai Tugas Belajar</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>PNS yang selesai tugas belajar dan aktif kembali <strong>langsung berhak atas: cuti besar persalinan anak ke-4, cuti sakit, cuti melahirkan, dan cuti karena alasan penting</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena cuti yang menyangkut kesehatan darurat (sakit, melahirkan, alasan penting) tidak memerlukan masa tunggu 3 bulan dan langsung melekat. Oleh karena itu, opsi A benar.</p><p>Jawaban tepat: <strong>C. Cuti sakit, cuti melahirkan, dan cuti alasan penting</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Cuti bersama baru dapat dinikmati setelah sekurang-kurangnya 3 bulan bekerja.</li><li><strong>B</strong>: Cuti tahunan berjalan dan cuti besar umum memerlukan masa dinas aktif minimal 3 bulan.</li><li><strong>D</strong>: Tidak ada nomenklatur cuti tahunan penugasan luar negeri tanpa masa tunggu.</li><li><strong>E</strong>: CLTN tidak dapat langsung diambil sesaat setelah lulus tugas belajar ikatan dinas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 65) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>PNS yang telah selesai tugas belajar dan bekerja kembali • cuti sakit</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C3 - Aplikasi", "question": "Seorang staf memiliki masa kerja 3 tahun dan ingin mengajukan CLTN untuk mendampingi pasangan. Keputusan pejabat kepegawaian adalah...", "options": ["Menyetujui dengan syarat bersedia dipotong gaji penuh", "Tidak dapat menyetujui karena syarat masa kerja minimal lima tahun", "Mengabulkan dengan durasi percobaan enam bulan", "Mengalihkan status pegawai menjadi honorer lepas", "Memberikan izin penugasan belajar pendamping tanpa pembiayaan dinas"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penerapan Syarat Masa Kerja Pengajuan CLTN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>CLTN hanya dapat diberikan bagi PNS yang telah <strong>bekerja paling kurang 5 tahun secara terus-menerus</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat. Berdasarkan ketentuan PP Manajemen PNS, cuti di luar tanggungan negara (CLTN) hanya dapat diberikan kepada aparatur yang telah bekerja sekurang-kurangnya 5 tahun secara terus-menerus. Pegawai yang baru bermasa kerja 3 tahun belum memenuhi syarat formal tersebut sehingga permohonan tidak dapat disetujui. Dengan demikian, keputusan pejabat kepegawaian sudah sesuai regulasi yang berlaku.</p><p>Jawaban tepat: <strong>B. Tidak dapat menyetujui karena syarat masa kerja minimal lima tahun</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pejabat kepegawaian tidak boleh melanggar syarat masa kerja 5 tahun meski pegawai bersedia nir-gaji.</li><li><strong>C</strong>: Tidak ada skema CLTN masa percobaan 6 bulan bagi masa kerja di bawah 5 tahun.</li><li><strong>D</strong>: Status PNS tidak dapat diubah menjadi tenaga honorer.</li><li><strong>E</strong>: Kurang tepat, penugasan belajar memiliki dasar hukum dan mekanisme tersendiri yang berbeda dengan permohonan CLTN urusan pribadi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 65) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>bekerja paling kurang 5 (lima) tahun secara terusmenerus • alasan-alasan pribadi yang penting dan mendesak</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C3 - Aplikasi", "question": "Seorang auditor selesai tugas belajar S2 pada 1 Februari 2026. Waktu paling cepat bagi auditor tersebut berhak cuti tahunan berjalan adalah...", "options": ["1 Agustus 2026 setelah 6 bulan bertugas", "1 Maret 2026 setelah 1 bulan bertugas", "1 Mei 2026 setelah 3 bulan bertugas", "1 Februari 2027 setelah 1 tahun bertugas", "Langsung pada tanggal 2 Februari 2026"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Implementasi Masa Tunggu Cuti Tahunan Pasca Tugas Belajar</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>PNS alumni tugas belajar berhak atas cuti tahunan berjalan setelah bekerja kembali <strong>sekurang-kurangnya 3 (tiga) bulan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena masa kerja aktif 3 bulan dihitung sejak 1 Februari 2026 sehingga hak cuti tahunan berjalan baru timbul mulai 1 Mei 2026. Dengan demikian, opsi A benar.</p><p>Jawaban tepat: <strong>C. 1 Mei 2026 setelah 3 bulan bertugas</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Enam bulan (1 Agustus) melebihi batas waktu yang disyaratkan regulasi.</li><li><strong>B</strong>: Satu bulan (1 Maret) belum memenuhi ambang batas waktu 3 bulan bekerja.</li><li><strong>D</strong>: Satu tahun (1 Februari 2027) berlaku bagi pasca-CLTN, bukan pasca-tugas belajar.</li><li><strong>E</strong>: Langsung pada hari berikutnya tidak sah karena cuti tahunan memerlukan masa pengabdian re-entry.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 65) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>lingkungan Kementerian Keuangan sekurang-kurangnya 3 (tiga) • cuti tahunan pada tahun yang sedang berjalan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C4 - Analisis", "question": "Berdasarkan ketentuan batas awal dan perpanjangannya, batas total akumulasi waktu terlama seorang PNS dapat menjalani CLTN adalah...", "options": ["2 (dua) tahun masa penundaan", "3 (tiga) tahun kalender murni", "5 (lima) tahun masa penugasan", "4 (empat) tahun berturut-turut", "6 (enam) tahun masa istirahat"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Analisis Total Durasi Maksimal Akumulasi CLTN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>CLTN diberikan paling lama <strong>3 tahun</strong> dan dapat diperpanjang paling lama <strong>1 tahun</strong>, sehingga akumulasi maksimalnya adalah <strong>4 tahun</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena penjumlahan waktu awal 3 tahun ditambah batas maksimal perpanjangan 1 tahun menghasilkan total 4 tahun cuti nir-tanggungan negara. Maka dari itu, opsi A benar.</p><p>Jawaban tepat: <strong>D. 4 (empat) tahun berturut-turut</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Dua tahun lebih rendah dari batas akumulasi maksimal yang diizinkan peraturan.</li><li><strong>B</strong>: Tiga tahun hanya batas pemberian awal sebelum adanya izin perpanjangan.</li><li><strong>C</strong>: Lima tahun melebihi batas maksimal akumulasi legalitas CLTN.</li><li><strong>E</strong>: Enam tahun tidak memiliki rujukan dalam pasal durasi akumulasi CLTN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 65) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>paling lama 3 • dapat diperpanjang paling lama 1 (satu) tahun</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C4 - Analisis", "question": "Perbedaan mendasar hak cuti bagi PNS yang baru selesai tugas belajar sebelum dan sesudah 3 bulan bekerja kembali terletak pada...", "options": ["Pemberian hak pensiun dini atas permintaan sendiri", "Pemberian tunjangan kinerja jabatan struktural", "Penyesuaian kenaikan pangkat penyesuaian ijazah", "Kewajiban mengikuti evaluasi kompetensi tahunan", "Timbulnya hak atas cuti tahunan berjalan dan cuti bersama"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Analisis Hak Cuti Bersama Pasca Tugas Belajar</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sebelum 3 bulan bekerja, PNS alumni tugas belajar hanya berhak cuti darurat (sakit, melahirkan, alasan penting). <strong>Setelah 3 bulan bekerja, barulah timbul hak atas cuti tahunan berjalan, cuti besar, dan cuti bersama</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena pemulihan cuti tahunan dan cuti bersama secara yuridis baru aktif setelah pegawai menuntaskan 3 bulan pengabdian pasca-belajar. Oleh karena itu, opsi A benar.</p><p>Jawaban tepat: <strong>E. Timbulnya hak atas cuti tahunan berjalan dan cuti bersama</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pensiun dini tunduk pada batas usia dan masa kerja minimal pensiun.</li><li><strong>B</strong>: Tunjangan kinerja diatur oleh SK penempatan jabatan, bukan semata-mata aturan cuti 3 bulan.</li><li><strong>C</strong>: Penyesuaian ijazah memerlukan ujian dinas tersendiri dan bukan materi pasal cuti.</li><li><strong>D</strong>: Evaluasi kompetensi berlaku bagi seluruh pegawai tanpa membedakan masa 3 bulan cuti.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 65) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>lingkungan Kementerian Keuangan sekurang-kurangnya 3 (tiga) • cuti bersama</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Alasan pribadi yang penting dan mendesak dalam pengajuan CLTN dapat dipertimbangkan oleh...", "options": ["Atasan langsung dengan bukti yang mendukung", "Rekan kerja sejawat dalam satu unit bagian", "Tim pemeriksa disiplin inspektorat jenderal", "Pengurus serikat pegawai kementerian keuangan", "Badan Kepegawaian Negara secara mandiri"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pertimbangan Permohonan CLTN oleh Atasan Langsung</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Alasan pribadi penting dan mendesak pengajuan CLTN <strong>dapat dipertimbangkan oleh atasan langsung PNS yang bersangkutan apabila disertai dengan bukti-bukti yang mendukung</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena atasan langsung adalah pihak yang paling mengetahui situasi kerja dan kebenaran urgensi pegawai, sehingga pertimbangannya menjadi rujukan utama persetujuan. Oleh karena itu, opsi A benar.</p><p>Jawaban tepat: <strong>A. Atasan langsung dengan bukti yang mendukung</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Rekan sejawat tidak memiliki kewenangan hierarkis memberikan pertimbangan cuti.</li><li><strong>C</strong>: Inspektorat jenderal menangani pengawasan disiplin, bukan verifikasi awal cuti rutin.</li><li><strong>D</strong>: Serikat pekerja bukan pejabat struktural pembina administrasi kepegawaian.</li><li><strong>E</strong>: BKN memproses persetujuan teknis akhir, bukan memberikan pertimbangan awal langsung.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 66) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>dipertimbangkan oleh atasan langsung • disertai dengan bukti-bukti yang mendukung</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Regulasi yang menjadi dasar hukum pengelolaan dan pemotongan iuran kesejahteraan Pegawai Negeri Sipil adalah...", "options": ["Peraturan Pemerintah Nomor 11 Tahun 2017", "Instruksi Presiden Nomor 10 Tahun 1980", "Keputusan Presiden Nomor 56 Tahun 1974", "Undang-Undang Nomor 17 Tahun 2003", "Peraturan Menteri Keuangan Nomor 216 Tahun 2018"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dasar Hukum Iuran Kesejahteraan PNS</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dasar hukum iuran kesejahteraan PNS adalah <strong>Keputusan Presiden Republik Indonesia Nomor 56 Tahun 1974</strong> tentang Pembagian, Penggunaan, Cara Pemotongan dan Penyetoran Iuran-Iuran.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat. Keppres Nomor 56 Tahun 1974 menjadi payung hukum historis yang sah dan masih berlaku dalam tata kelola pemotongan iuran wajib pegawai untuk program kesejahteraan, jaminan kesehatan, dan dana pensiun di birokrasi Indonesia.</p><p>Jawaban tepat: <strong>C. Keputusan Presiden Nomor 56 Tahun 1974</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: PP 11/2017 mengatur manajemen pembinaan dan karier pegawai negeri.</li><li><strong>B</strong>: Inpres 10/1980 bukan instrumen yang mengatur pemotongan iuran PNS.</li><li><strong>D</strong>: UU 17/2003 mengatur asas umum pengelolaan dan pertanggungjawaban keuangan.</li><li><strong>E</strong>: PMK 216/2018 mengatur mekanisme akuntansi dan pelaporan piutang satker.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 66) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Keputusan Presiden Republik Indonesia Nomor 56 Tahun 1974</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan Keppres Nomor 56 Tahun 1974, usaha kesejahteraan Pegawai Negeri Sipil mencakup empat program yaitu...", "options": ["Beasiswa luar negeri, tabungan emas, asuransi jiwa", "Transportasi, rekreasi, seragam, sembako murah", "Kredit usaha, kendaraan dinas, kuota, seminar", "Pensiun, kesehatan, perumahan, pendidikan anak", "Bantuan hukum, pesangon, bonus target, wisata"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Empat Program Pokok Kesejahteraan PNS</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Usaha kesejahteraan PNS meliputi: <strong>a) Pensiun dan hari tua, b) Asuransi kesehatan, c) Tabungan perumahan, dan d) Asuransi pendidikan putra putri PNS</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena keempat pilar kesejahteraan Keppres 56/1974 secara limitatif meliputi dana pensiun/hari tua, jaminan kesehatan, perumahan, dan asuransi pendidikan putra-putri. Maka dari itu, opsi A benar.</p><p>Jawaban tepat: <strong>D. Pensiun, kesehatan, perumahan, pendidikan anak</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Tabungan emas tidak ada dalam nomenklatur program resmi Keppres 56/1974.</li><li><strong>B</strong>: Fasilitas transportasi, rekreasi, dan sembako bukan 4 program resmi Keppres 56/1974.</li><li><strong>C</strong>: Kredit usaha dan kendaraan dinas bukan program kesejahteraan dasar yang dibiayai iuran.</li><li><strong>E</strong>: Pesangon dan bonus target komersial bukan program kesejahteraan pegawai negeri.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 66) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Usaha kesejahteraan Pegawai Negeri Sipil meliputi program: • Pensiun dan hari tua</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan Keppres 56/1974, persentase total iuran yang dipungut dari penghasilan PNS untuk bidang kesejahteraan adalah...", "options": ["8% (delapan persen)", "5% (lima persen)", "2,5% (dua setengah persen)", "12% (dua belas persen)", "10% (sepuluh persen)"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Besaran Persentase Iuran Kesejahteraan PNS</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Setiap PNS <strong>dipungut iuran 10% dari penghasilannya untuk membiayai usaha dalam bidang kesejahteraan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena Keppres 56/1974 menetapkan potongan wajib sebesar 10% dari gaji pegawai guna menopang seluruh program jaminan masa depan. Dengan demikian, opsi A benar.</p><p>Jawaban tepat: <strong>E. 10% (sepuluh persen)</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Delapan persen tidak sesuai dengan rumusan eksplisit pasal Keppres 56/1974.</li><li><strong>B</strong>: Lima persen merupakan persentase iuran jaminan kesehatan BPJS gabungan, bukan total iuran Keppres.</li><li><strong>C</strong>: Dua setengah persen adalah tarif standar zakat profesi, bukan iuran kesejahteraan ASN.</li><li><strong>D</strong>: Dua belas persen melebihi ketentuan tarif pemotongan 10%.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 66) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>dipungut iuran 10% dari penghasilannya • membiayai usaha dalam bidang kesejahteraan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Rincian pembagian iuran 10% penghasilan PNS untuk dana pensiun serta tabungan hari tua/perumahan adalah...", "options": ["4 3/4% dana pensiun dan 3 1/4% hari tua/perumahan", "5% dana pensiun dan 5% tabungan hari tua", "7% dana pensiun dan 3% tabungan perumahan", "6% dana pensiun dan 4% asuransi hari tua", "3 1/4% dana pensiun dan 4 3/4% perumahan"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Rincian Alokasi Iuran Kesejahteraan 10%</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Perincian iuran 10% mencakup: <strong>4 ¾% untuk iuran dana pensiun</strong> dan <strong>3 ¼% untuk iuran tabungan hari tua dan perumahan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena pasal Keppres 56/1974 secara tegas mengalokasikan porsi 4 3/4% bagi dana pensiun dan 3 1/4% bagi tabungan hari tua dan perumahan. Oleh karena itu, opsi A benar.</p><p>Jawaban tepat: <strong>A. 4 3/4% dana pensiun dan 3 1/4% hari tua/perumahan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Pembagian 5% dan 5% tidak sesuai angka riil Keppres 56/1974.</li><li><strong>C</strong>: Alokasi 7% dan 3% salah dalam rincian proporsional undang-undang.</li><li><strong>D</strong>: Alokasi 6% dan 4% bukan rincian resmi ketentuan iuran pegawai.</li><li><strong>E</strong>: Tertukar posisinya antara persentase dana pensiun dengan tabungan hari tua.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 66) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>4 ¾% untuk iuran dana pensiun • 3 ¼% untuk iuran tabungan hari tua dan perumahan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Peraturan Presiden yang mengatur proporsi pembayaran iuran jaminan kesehatan bagi aparatur sipil negara adalah...", "options": ["Peraturan Presiden Nomor 16 Tahun 2018", "Peraturan Presiden Nomor 75 Tahun 2019", "Peraturan Pemerintah Nomor 94 Tahun 2021", "Keputusan Presiden Nomor 56 Tahun 1974", "Undang-Undang Nomor 24 Tahun 2011"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dasar Hukum Iuran Jaminan Kesehatan PNS</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Iuran kesehatan diatur dalam <strong>Peraturan Presiden Nomor 75 tahun 2019</strong> tentang Perubahan atas Perpres Nomor 82 Tahun 2018 tentang <strong>Jaminan Kesehatan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena Perpres 75/2019 menetapkan skema pembagian iuran jaminan kesehatan antara instansi pemerintah dan pegawai. Oleh sebab itu, opsi A benar.</p><p>Jawaban tepat: <strong>B. Peraturan Presiden Nomor 75 Tahun 2019</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Perpres 16/2018 mengatur tentang Pengadaan Barang dan Jasa Pemerintah.</li><li><strong>C</strong>: PP 94/2021 mengatur tentang Disiplin Pegawai Negeri Sipil.</li><li><strong>D</strong>: Keppres 56/1974 mengatur iuran kesejahteraan pensiun dan perumahan secara umum.</li><li><strong>E</strong>: UU 24/2011 mengatur tentang Badan Penyelenggara Jaminan Sosial (BPJS).</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 66) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>iuran jaminan kesehatan PNS • Peraturan Presiden Nomor 75 tahun 2019</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan Perpres Nomor 75 Tahun 2019, proporsi iuran jaminan kesehatan PNS ditanggung bersama dengan ketentuan...", "options": ["4% pemberi kerja dan 1% oleh peserta", "3% pemberi kerja dan 2% oleh peserta", "2,5% pemberi kerja dan 2,5% peserta", "5% seluruhnya ditanggung pemerintah", "4,5% pemberi kerja dan 0,5% peserta"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Proporsi Iuran Jaminan Kesehatan PNS</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Iuran jaminan kesehatan PNS dibayar dengan ketentuan <strong>4% dibayar oleh Pemberi kerja</strong> dan <strong>1% dibayar oleh peserta</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena Perpres 75/2019 menetapkan total premi 5% di mana porsi mayoritas (4%) disubsidi instansi/pemberi kerja dan 1% dipotong dari gaji pegawai. Maka dari itu, opsi A benar.</p><p>Jawaban tepat: <strong>A. 4% pemberi kerja dan 1% oleh peserta</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Skema 3% dan 2% bukan proporsi yang diatur bagi segmen PNS.</li><li><strong>C</strong>: Skema pembagian 50:50 (2,5% dan 2,5%) tidak berlaku bagi ASN pemerintah.</li><li><strong>D</strong>: Pemerintah tidak menanggung 100% karena pegawai tetap memiliki kewajiban kontribusi 1%.</li><li><strong>E</strong>: Skema 4,5% dan 0,5% tidak dikenal dalam regulasi jaminan kesehatan nasional.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 66) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>4% (empat persen) dibayar oleh Pemberi kerja • persen) dibayar oleh peserta</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C3 - Aplikasi", "question": "Seorang PNS menerima penghasilan dasar Rp4.000.000 per bulan. Alokasi pemotongan untuk dana pensiun sesuai Keppres 56/1974 adalah...", "options": ["Rp130.000 (3,25% dari penghasilan)", "Rp190.000 (4,75% dari penghasilan)", "Rp400.000 (10% dari penghasilan)", "Rp200.000 (5% dari penghasilan)", "Rp160.000 (4% dari penghasilan)"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Implementasi Penghitungan Pemotongan Iuran Pensiun</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Porsi dana pensiun adalah <strong>4 ¾% (4,75%)</strong> dari total penghasilan yang dipungut.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena 4,75% dikalikan Rp4.000.000 menghasilkan Rp190.000 untuk iuran pensiun bulanan. Nilai ini disetorkan langsung ke kas pengelola dana pensiun negara. Oleh sebab itu, opsi A benar.</p><p>Jawaban tepat: <strong>B. Rp190.000 (4,75% dari penghasilan)</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Rp130.000 (3,25%) merupakan alokasi untuk tabungan hari tua dan perumahan.</li><li><strong>C</strong>: Rp400.000 (10%) adalah total potongan gabungan seluruh program kesejahteraan.</li><li><strong>D</strong>: Rp200.000 (5%) adalah total premi kesehatan BPJS, bukan iuran pensiun.</li><li><strong>E</strong>: Rp160.000 (4%) adalah porsi BPJS yang ditanggung pemberi kerja.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 66) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>4 ¾% untuk iuran dana pensiun • dipungut iuran 10% dari penghasilannya</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C3 - Aplikasi", "question": "Berdasarkan struk gaji bulanan, pemotongan iuran jaminan kesehatan BPJS yang dibebankan langsung kepada gaji pegawai adalah sebesar...", "options": ["5% (lima persen) dari penghasilan", "4% (empat persen) dari penghasilan", "1% (satu persen) dari penghasilan", "2% (dua persen) dari penghasilan", "0,5% (setengah persen) gaji pokok"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penerapan Iuran Jaminan Kesehatan bagi Pegawai</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Iuran jaminan kesehatan PNS <strong>1% dibayar oleh peserta</strong> melalui pemotongan penghasilan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena beban iuran mandiri pegawai hanyalah 1%, sedangkan porsi 4% dibayar langsung oleh negara selaku pemberi kerja. Dengan demikian, opsi A benar.</p><p>Jawaban tepat: <strong>C. 1% (satu persen) dari penghasilan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Lima persen adalah total akumulasi premi jaminan kesehatan gabungan.</li><li><strong>B</strong>: Empat persen merupakan beban yang dibayar negara/pemberi kerja, bukan dipotong dari peserta.</li><li><strong>D</strong>: Dua persen tidak sesuai dengan rumusan eksplisit Perpres 75/2019.</li><li><strong>E</strong>: Setengah persen terlalu kecil dan tidak memenuhi standar iuran peserta.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 66) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>persen) dibayar oleh peserta • Peraturan Presiden Nomor 75 tahun 2019</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C4 - Analisis", "question": "Keppres Nomor 56 Tahun 1974 menetapkan usaha kesejahteraan PNS sebagai perlindungan sosial wajib, KECUALI...", "options": ["Tabungan kepemilikan perumahan PNS", "Pensiun dan jaminan hari tua aparatur", "Asuransi pemeliharaan kesehatan pegawai", "Asuransi kerugian investasi modal saham", "Asuransi pendidikan putra putri pegawai"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Analisis Perbedaan Program Kesejahteraan dan Asuransi Komersial</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Program kesejahteraan Keppres 56/1974 hanya meliputi: <strong>pensiun/hari tua, kesehatan, perumahan, dan pendidikan anak</strong>, tanpa asuransi investasi saham komersial.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat sebagai pengecualian karena asuransi kerugian saham bukan instrumen perlindungan sosial aparatur pemerintah melainkan spekulasi pasar finansial. Maka dari itu, opsi A benar.</p><p>Jawaban tepat: <strong>D. Asuransi kerugian investasi modal saham</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Tabungan perumahan membantu kepemilikan hunian layak bagi pegawai.</li><li><strong>B</strong>: Pensiun dan hari tua adalah program utama jaminan masa purnatugas ASN.</li><li><strong>C</strong>: Asuransi kesehatan merupakan program perlindungan medis resmi aparatur.</li><li><strong>E</strong>: Asuransi pendidikan putra-putri menjamin keberlanjutan sekolah keluarga ASN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 66) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Usaha kesejahteraan Pegawai Negeri Sipil meliputi program: • Tabungan perumahan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C4 - Analisis", "question": "Perbandingan beban pembayaran premi jaminan kesehatan antara Pemberi Kerja (Pemerintah) dan Peserta (PNS) adalah...", "options": ["Lima banding nol (5:0)", "Satu banding satu (1:1)", "Tiga banding dua (3:2)", "Dua banding tiga (2:3)", "Empat banding satu (4:1)"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Analisis Perbandingan Beban Iuran Jaminan Kesehatan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Proporsi iuran BPJS PNS adalah 4% ditanggung pemberi kerja dan 1% ditanggung peserta, yang mencerminkan rasio <strong>4 banding 1</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena rasio 4% berbanding 1% menunjukkan kontribusi negara empat kali lebih besar daripada iuran mandiri pegawai dalam pembiayaan kesehatan. Oleh sebab itu, opsi A benar.</p><p>Jawaban tepat: <strong>E. Empat banding satu (4:1)</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Rasio 5:0 berarti peserta tidak membayar sama sekali, padahal ada kewajiban 1%.</li><li><strong>B</strong>: Rasio 1:1 keliru karena pemerintah menanggung 4% sedangkan pegawai hanya 1%.</li><li><strong>C</strong>: Rasio 3:2 mencerminkan porsi 3% dan 2% yang bukan aturan ASN.</li><li><strong>D</strong>: Rasio 2:3 terbalik dan merugikan pegawai.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 66) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>4% (empat persen) dibayar oleh Pemberi kerja • persen) dibayar oleh peserta</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Dalam manajemen aparatur negara, gaji didefinisikan sebagai...", "options": ["Bantuan sosial bulanan bagi kelangsungan hidup", "Balas jasa dan penghargaan atas prestasi kerja", "Kompensasi tetap atas kehadiran presensi harian", "Pemberian sukarela instansi sesuai laba tahunan", "Pinjaman modal kerja dinas tanpa bunga cicilan"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi dan Fungsi Gaji PNS</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Gaji adalah sebagai balas jasa dan penghargaan atas prestasi kerja Pegawai Negeri yang bersangkutan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena modul secara harfiah merumuskan gaji sebagai bentuk balas jasa dan penghargaan negara atas kontribusi dan prestasi kerja yang dihasilkan pegawai. Oleh sebab itu, opsi A benar.</p><p>Jawaban tepat: <strong>B. Balas jasa dan penghargaan atas prestasi kerja</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Gaji bukan bantuan sosial melainkan hak finansial atas pelaksanaan tugas kedinasan.</li><li><strong>C</strong>: Gaji bukan sekadar kompensasi kehadiran fisik melainkan apresiasi prestasi kerja.</li><li><strong>D</strong>: Gaji bersifat wajib berdasarkan undang-undang, bukan pemberian sukarela berbasis laba.</li><li><strong>E</strong>: Gaji adalah hak milik mutlak pegawai, bukan pinjaman modal kerja yang dicicil.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 67) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Gaji adalah sebagai balas jasa dan penghargaan atas prestasi kerja • sistem penggajian</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Sistem skala tunggal dalam penggajian pegawai negeri sipil didefinisikan sebagai sistem yang...", "options": ["Menghitung besaran gaji berdasarkan akumulasi jam kerja dinas", "Membedakan gaji pokok berdasarkan lokasi penugasan", "Memberi gaji sama bagi pangkat sama tanpa beda beban", "Memberikan bonus besar bagi pegawai berprestasi tinggi", "Menyerahkan penentuan gaji pada negosiasi kontrak"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengertian Sistem Skala Tunggal</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sistem skala tunggal adalah <strong>sistem penggajian yang memberikan gaji yang sama kepada pegawai yang berpangkat sama dengan tidak atau kurang memperhatikan sifat pekerjaan dan beratnya tanggung jawab</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena karakteristik mutlak skala tunggal adalah kesetaraan gaji murni berbasis pangkat semata tanpa memperhitungkan variasi risiko atau beban kerja riil. Dengan demikian, opsi A benar.</p><p>Jawaban tepat: <strong>C. Memberi gaji sama bagi pangkat sama tanpa beda beban</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat, akumulasi jam kerja digunakan untuk penghitungan uang lembur dan bukan penentu gaji pokok pada sistem skala tunggal.</li><li><strong>B</strong>: Pembedaan berbasis lokasi bukan ciri dari sistem skala tunggal murni.</li><li><strong>D</strong>: Bonus prestasi tinggi merupakan instrumen skala ganda atau tunjangan kinerja.</li><li><strong>E</strong>: Negosiasi kontrak merupakan karakteristik pegawai swasta, bukan sistem skala tunggal PNS.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 67) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>skala tunggal adalah system penggajian yang • tidak atau kurang</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Sistem penggajian yang menentukan besarnya gaji berdasarkan pangkat, sifat pekerjaan, prestasi kerja, dan tanggung jawab adalah...", "options": ["Sistem upah borongan", "Sistem skala tunggal", "Sistem komisi komersial", "Sistem skala ganda", "Sistem insentif harian"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengertian Sistem Skala Ganda</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Sistem skala ganda adalah sistem penggajian yang menentukan besarnya gaji bukan saja didasarkan pada pangkat, tetapi juga didasarkan pada sifat pekerjaan yang dilakukan, prestasi kerja yang dicapai dan beratnya tanggung jawab pekerjaannya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena pertimbangan komprehensif atas pangkat, bobot sifat pekerjaan, prestasi kerja, dan tingkat tanggung jawab merupakan definisi otentik sistem skala ganda. Oleh sebab itu, opsi A benar.</p><p>Jawaban tepat: <strong>D. Sistem skala ganda</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Upah borongan berlaku pada sektor pekerja kasar/proyek konstruksi non-ASN.</li><li><strong>B</strong>: Skala tunggal hanya melihat pangkat tanpa memperhatikan sifat pekerjaan dan tanggung jawab.</li><li><strong>C</strong>: Sistem komisi merupakan sistem penjualan swasta, bukan sistem penggajian ASN.</li><li><strong>E</strong>: Insentif harian bukan nomenklatur sistem penggajian pokok kepegawaian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 67) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Sistem skala ganda adalah sistem penggajian yang menentukan • sifat pekerjaan yang dilakukan, prestasi kerja</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Sistem penggajian ketiga yang memadukan skala tunggal pada gaji pokok dan skala ganda melalui tunjangan adalah...", "options": ["Sistem kompensasi campuran bebas", "Sistem skala majemuk murni", "Sistem bagi hasil kinerja", "Sistem tarif tunggal dinamis", "Sistem skala gabungan"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Konsep Sistem Skala Gabungan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Sistem skala gabungan</strong> merupakan <strong>perpaduan antara sistem skala tunggal dan sistem skala ganda</strong>, di mana gaji pokok sama bagi pangkat sama dan ditambah tunjangan bagi tanggung jawab lebih berat.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena sistem skala gabungan menggabungkan kepastian gaji pokok pangkat (skala tunggal) dengan diferensiasi beban tugas melalui tunjangan (skala ganda). Maka dari itu, opsi A benar.</p><p>Jawaban tepat: <strong>E. Sistem skala gabungan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kompensasi campuran bebas bukan konsep resmi manajemen kepegawaian negara.</li><li><strong>B</strong>: Skala majemuk murni bukan istilah teknis yang diakui dalam modul kepegawaian.</li><li><strong>C</strong>: Sistem bagi hasil merupakan konsep bisnis korporasi atau kemitraan.</li><li><strong>D</strong>: Tarif tunggal dinamis tidak dikenal dalam literatur penggajian ASN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 67) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>merupakan perpaduan antara sistem skala tunggal • gaji pokok ditentukan sama bagi pegawai</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Selain gaji pokok, komponen tunjangan resmi yang dapat diberikan kepada Pegawai Negeri Sipil meliputi...", "options": ["Tunjangan keluarga, jabatan, pangan, dan lain-lain", "Tunjangan hiburan, rekreasi, pulsa, dan pesangon", "Tunjangan liburan akhir pekan dan bonus belanja", "Tunjangan kepemilikan saham dan dividen satker", "Tunjangan hari raya swasta dan insentif lembur bebas"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Komponen Tunjangan Selain Gaji Pokok</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Selain gaji pokok, PNS dapat diberikan: <strong>a. Tunjangan keluarga, b. Tunjangan jabatan, c. Tunjangan pangan, dan d. Tunjangan lain-lain</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena rincian pasal tunjangan secara tegas menetapkan empat klaster: keluarga, jabatan struktural/fungsional, pangan/beras, serta tunjangan lain-lain yang sah. Oleh karena itu, opsi A benar.</p><p>Jawaban tepat: <strong>A. Tunjangan keluarga, jabatan, pangan, dan lain-lain</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Hiburan dan rekreasi bukan komponen tunjangan resmi gaji PNS.</li><li><strong>C</strong>: Bonus belanja pribadi tidak ada dalam nomenklatur belanja pegawai APBN.</li><li><strong>D</strong>: PNS dilarang menerima dividen satker karena instansi pemerintah berorientasi layanan publik.</li><li><strong>E</strong>: Tunjangan hari raya swasta bukan komponen tunjangan kedinasan ASN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 67) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Selain gaji pokok, kepada Pegawai Negeri Sipil dapat diberikan: • Tunjangan keluarga</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan PP Nomor 11 Tahun 2017, hak pengembangan kompetensi bagi setiap PNS dilakukan paling sedikit...", "options": ["80 (delapan puluh) jam pelajaran dalam 2 tahun", "40 (empat puluh) jam pelajaran dalam 1 tahun", "10 (sepuluh) jam pelajaran per semester", "20 (dua puluh) jam pelajaran dalam 1 tahun", "100 (seratus) jam pelajaran selama karier"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Standar Minimal Jam Pelajaran Pengembangan Kompetensi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Pengembangan kompetensi bagi setiap PNS dilakukan paling sedikit 20 (dua puluh) jam pelajaran dalam 1 (satu) tahun</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena standar baku PP 11/2017 mewajibkan instansi memberikan alokasi minimal 20 JP per tahun kalender bagi seluruh pegawai. Hal ini menjamin continuous professional development. Dengan demikian, opsi A benar.</p><p>Jawaban tepat: <strong>D. 20 (dua puluh) jam pelajaran dalam 1 tahun</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penetapan 80 JP dalam 2 tahun tidak sesuai dengan kewajiban evaluasi tahunan.</li><li><strong>B</strong>: Empat puluh jam pelajaran bukan batas minimal umum PP 11/2017 (meski berlaku khusus pada PPPK/tertentu).</li><li><strong>C</strong>: Regulasi menggunakan basis tahunan 20 JP, bukan semesteran 10 JP.</li><li><strong>E</strong>: Seratus jam pelajaran selama karier bertentangan dengan kewajiban belajar tahunan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 67) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>paling sedikit 20 (dua puluh) jam • Pengembangan kompetensi bagi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Dalam PP 11 Tahun 2017, pengembangan kompetensi didefinisikan sebagai upaya pemenuhan kebutuhan kompetensi PNS dengan...", "options": ["Tuntutan kenaikan tunjangan kinerja semata", "Keinginan pribadi pegawai di luar tugas kedinasan", "Target komersialisasi aset milik kementerian", "Instruksi politik praktis kepala daerah terpilih", "Standar kompetensi jabatan dan rencana karier"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuan Pengembangan Kompetensi ASN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pengembangan kompetensi merupakan upaya untuk <strong>pemenuhan kebutuhan kompetensi PNS dengan standar kompetensi Jabatan dan rencana pengembangan karier</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena orientasi pengembangan kompetensi adalah menjembatani profil pegawai agar selaras dengan standar kompetensi jabatan serta proyeksi jalur karier aparatur. Oleh sebab itu, opsi A benar.</p><p>Jawaban tepat: <strong>E. Standar kompetensi jabatan dan rencana karier</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Bukan instrumen instan mengejar kenaikan tunjangan semata.</li><li><strong>B</strong>: Bukan untuk memenuhi hobi atau keinginan pribadi yang tidak selaras dengan tugas dinas.</li><li><strong>C</strong>: Komersialisasi aset bukan tujuan pengembangan kompetensi SDM aparatur.</li><li><strong>D</strong>: Pengembangan kompetensi berlandaskan meritokrasi profesional, bukan instruksi politik.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 67) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pemenuhan kebutuhan kompetensi PNS dengan • Dalam PP 11 Tahun 2017</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Prinsip kesetaraan keikutsertaan PNS dalam pengembangan kompetensi wajib dilaksanakan dengan memperhatikan...", "options": ["Hasil penilaian kinerja dan penilaian kompetensi", "Kedekatan personal pegawai dengan pejabat struktural", "Tingkat senioritas usia tanpa melihat capaian kerja", "Asal almamater perguruan tinggi kelulusan pegawai", "Urutan abjad nama pegawai dalam daftar hadir dinas"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Prinsip Kesetaraan Hak Pengembangan Kompetensi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Setiap PNS memiliki <strong>hak dan kesempatan yang sama</strong> untuk diikutsertakan dalam pengembangan kompetensi dengan memperhatikan <strong>hasil penilaian kinerja dan penilaian kompetensi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena seleksi pengembangan kompetensi berlandaskan prinsip meritokrasi yang transparan dan akuntabel berbasis data kinerja dan kapasitas riil pegawai. Maka dari itu, opsi A benar.</p><p>Jawaban tepat: <strong>A. Hasil penilaian kinerja dan penilaian kompetensi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kedekatan personal merupakan praktik nepotisme yang dilarang undang-undang.</li><li><strong>C</strong>: Senioritas usia tanpa prestasi kerja mencederai prinsip meritokrasi sistem karir.</li><li><strong>D</strong>: Asal universitas dilarang menjadi dasar diskriminasi kesempatan belajar ASN.</li><li><strong>E</strong>: Urutan abjad nama bukan metode ilmiah penentuan kebutuhan pengembangan kompetensi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 67) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>memiliki hak dan kesempatan yang sama • penilaian kinerja dan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C3 - Aplikasi", "question": "Dua pegawai sama-sama bergolongan III/b, namun satu orang bertugas sebagai pemeriksa lapangan berisiko tinggi. Penggajian yang adil adalah...", "options": ["Gaji pokok langsung dinaikkan menjadi golongan IV/a", "Gaji pokok sama ditambah tunjangan beban kerja lebih tinggi", "Gaji pokok dan seluruh fasilitas disamaratakan persis", "Gaji pokok disesuaikan berdasarkan persentase realisasi target", "Gaji pokok dipertahankan dengan insentif kompensasi berkala"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penerapan Skala Gabungan pada Jabatan Berisiko Tinggi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam <strong>sistem skala gabungan</strong>, gaji pokok ditentukan sama bagi pangkat sama, di samping itu <strong>diberikan tunjangan bagi yang memikul tanggung jawab lebih berat</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena sistem skala gabungan menjamin kesetaraan gaji pokok pangkat sembari memberikan apresiasi risiko/tanggung jawab lebih berat melalui tunjangan tambahan. Oleh karena itu, opsi A benar.</p><p>Jawaban tepat: <strong>B. Gaji pokok sama ditambah tunjangan beban kerja lebih tinggi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kenaikan golongan pangkat memerlukan prosedur kenaikan pangkat resmi, bukan instan dinaikkan.</li><li><strong>C</strong>: Menyamaratakan persis merupakan kelemahan sistem skala tunggal yang tidak adil bagi tugas berisiko.</li><li><strong>D</strong>: Kurang tepat, penyesuaian gaji pokok berdasar target bukan karakteristik sistem skala gabungan.</li><li><strong>E</strong>: Kurang tepat, kompensasi berkala tidak menggantikan tunjangan risiko beban kerja spesifik.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 67) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>di samping itu diberikan tunjangan kepada • sistem skala gabungan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C3 - Aplikasi", "question": "Hingga triwulan IV seorang staf baru mengumpulkan 14 JP pembelajaran. Kewajiban unit pengelola kepegawaian adalah...", "options": ["Menunda kenaikan pangkat reguler selama satu periode", "Menjatuhkan sanksi pemotongan tunjangan kinerja bulanan", "Memfasilitasi pelatihan tambahan minimal 6 JP sebelum akhir tahun", "Menganggap target gugur dan mengulang dari nol tahun depan", "Mengalihkan kekurangan jam pelajaran ke tahun berikutnya"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Implementasi Pemenuhan Hak 20 JP bagi Pegawai</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Setiap PNS berhak atas pengembangan kompetensi <strong>paling sedikit 20 JP per tahun</strong>, sehingga kekurangan jam belajar wajib difasilitasi pemenuhannya oleh instansi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena pengembangan kompetensi merupakan hak pegawai yang wajib dipenuhi oleh organisasi dengan memfasilitasi sisa 6 JP melalui pelatihan atau e-learning sebelum tutup tahun. Dengan demikian, opsi A benar.</p><p>Jawaban tepat: <strong>C. Memfasilitasi pelatihan tambahan minimal 6 JP sebelum akhir tahun</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Tidak ada dasar hukum penundaan pangkat instan jika instansi belum memfasilitasi hak belajar.</li><li><strong>B</strong>: Kekurangan JP bukan serta merta pelanggaran disiplin pemotongan tunjangan melainkan kewajiban instansi memfasilitasi.</li><li><strong>D</strong>: Hak 20 JP harus diupayakan optimal dalam tahun berjalan, bukan diabaikan begitu saja.</li><li><strong>E</strong>: Prinsip pemenuhan target kompetensi adalah berbasis tahunan kalender.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 67) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>paling sedikit 20 (dua puluh) jam • Pengembangan kompetensi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C4 - Analisis", "question": "Perbedaan filosofis mendasar antara sistem skala tunggal dan sistem skala ganda terletak pada perlakuan terhadap...", "options": ["Tingkat pendidikan formal awal saat melamar CPNS", "Masa usia pensiun dan jenis jaminan hari tua aparatur", "Prosedur penjatuhan hukuman disiplin berat pegawai", "Mekanisme pengangkatan sumpah janji jabatan ASN", "Sifat pekerjaan, bobot tanggung jawab, dan prestasi kerja"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Analisis Perbedaan Skala Tunggal dan Skala Ganda</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Skala tunggal hanya melihat pangkat tanpa membedakan sifat tugas, sedangkan <strong>skala ganda secara esensial mempertimbangkan sifat pekerjaan, berat tanggung jawab, dan prestasi kerja</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena perbedaan pokok kedua sistem penggajian tersebut bermuara pada pengakuan terhadap variasi tingkat kesulitan kerja, tanggung jawab tugas, dan capaian prestasi riil. Maka dari itu, opsi A benar.</p><p>Jawaban tepat: <strong>E. Sifat pekerjaan, bobot tanggung jawab, dan prestasi kerja</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pendidikan formal awal hanya menentukan pangkat pengangkatan, bukan pembeda skala tunggal/ganda.</li><li><strong>B</strong>: Batas usia pensiun diatur seragam oleh undang-undang, bukan oleh sistem penggajian.</li><li><strong>C</strong>: Hukuman disiplin tunduk pada peraturan disiplin tersendiri (PP 94/2021).</li><li><strong>D</strong>: Sumpah janji jabatan seragam bagi seluruh aparatur negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 67) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>sistem skala tunggal • sistem skala ganda</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Langkah pertama dalam kewajiban Pejabat Pembina Kepegawaian (PPK) menyelenggarakan pengembangan kompetensi adalah...", "options": ["Menetapkan kebutuhan dan rencana pengembangan kompetensi", "Langsung menunjuk penyedia pelatihan swasta eksternal", "Membagikan sertifikat kelulusan diklat pegawai", "Melakukan evaluasi akhir hasil belajar secara mandiri", "Menjatuhkan teguran disiplin bagi pegawai tidak hadir"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Empat Kewajiban PPK dalam Pengembangan Kompetensi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kewajiban PPK yang pertama adalah: <strong>menetapkan kebutuhan dan rencana pengembangan kompetensi, yaitu inventarisasi jenis kompetensi yang perlu ditingkatkan dari setiap PNS</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena inventarisasi dan penetapan kebutuhan merupakan fondasi awal perencanaan sebelum penyusunan jadwal dan pelaksanaan diklat. Oleh karena itu, opsi A benar.</p><p>Jawaban tepat: <strong>A. Menetapkan kebutuhan dan rencana pengembangan kompetensi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Penunjukan penyedia bukan langkah pertama melainkan bagian teknis pengadaan.</li><li><strong>C</strong>: Pembagian sertifikat merupakan tahapan akhir pasca-pelaksanaan pelatihan.</li><li><strong>D</strong>: Evaluasi merupakan tahapan keempat dalam siklus kewajiban PPK.</li><li><strong>E</strong>: Teguran disiplin bukan tahapan siklus manajemen pengembangan kompetensi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 68) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menetapkan kebutuhan dan rencana pengembangan kompetensi • inventarisasi jenis kompetensi yang perlu ditingkatkan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Analisis kesenjangan kompetensi pegawai dilaksanakan dengan cara membandingkan...", "options": ["Hasil penilaian kinerja dengan target SKP tahunan", "Profil kompetensi PNS dengan standar kompetensi jabatan", "Nilai ijazah sekolah dengan pangkat golongan pegawai", "Masa kerja dinas dengan usia pensiun aparatur", "Tingkat kehadiran presensi dengan besaran tunjangan"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Metodologi Analisis Kesenjangan Kompetensi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Analisis kesenjangan kompetensi dilakukan dengan membandingkan profil kompetensi PNS dengan standar kompetensi Jabatan yang diduduki dan yang akan diduduki</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena perbandingan antara kapasitas riil pegawai (profil kompetensi) dan tuntutan kualifikasi tugas (standar kompetensi jabatan) adalah definisi mutlak uji kesenjangan kompetensi. Dengan demikian, opsi A benar.</p><p>Jawaban tepat: <strong>B. Profil kompetensi PNS dengan standar kompetensi jabatan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Perbandingan hasil kerja dengan target merupakan metodologi analisis kesenjangan kinerja, bukan kompetensi.</li><li><strong>C</strong>: Nilai ijazah bukan instrumen analisis kompetensi jabatan.</li><li><strong>D</strong>: Masa kerja dan usia pensiun merupakan data administrasi kepegawaian umum.</li><li><strong>E</strong>: Presensi dan tunjangan berkaitan dengan pembayaran uang makan dan kedisiplinan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 68) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>kompetensi dilakukan dengan membandingkan • profil kompetensi PNS dengan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Analisis kesenjangan kinerja aparatur sipil negara dilaksanakan melalui perbandingan antara...", "options": ["Anggaran belanja satker dengan realisasi penerimaan negara", "Profil kompetensi staf dengan kompetensi pimpinan unit", "Hasil penilaian kinerja PNS dengan target kinerja jabatan", "Jumlah jam belajar dengan standar minimal dua puluh JP", "Pangkat struktural dengan jenjang kepangkatan fungsional"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Metodologi Analisis Kesenjangan Kinerja</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Analisis kesenjangan kinerja dilakukan dengan membandingkan hasil penilaian kinerja PNS dengan target kinerja Jabatan yang diduduki</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena kesenjangan kinerja mengukur selisih antara output/prestasi nyata pegawai dengan sasaran kinerja yang telah disepakati pada perjanjian kerja jabatan. Oleh sebab itu, opsi A benar.</p><p>Jawaban tepat: <strong>C. Hasil penilaian kinerja PNS dengan target kinerja jabatan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Realisasi anggaran adalah analisis kinerja keuangan satker, bukan kesenjangan kinerja individu.</li><li><strong>B</strong>: Membandingkan staf dengan pimpinan bukan metode analisis kesenjangan kinerja jabatan.</li><li><strong>D</strong>: Jumlah jam belajar mengukur pemenuhan hak 20 JP, bukan kesenjangan kinerja hasil tugas.</li><li><strong>E</strong>: Pangkat struktural dan fungsional adalah urusan nomenklatur pola karier.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 68) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Analisis kesenjangan kinerja dilakukan dengan membandingkan • kinerja PNS dengan target kinerja Jabatan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Pengembangan kompetensi pegawai di lingkungan Kementerian Keuangan secara komprehensif diatur dalam...", "options": ["Keputusan Presiden Nomor 56 Tahun 1974", "Peraturan Menteri Keuangan Nomor 190/PMK.05/2012", "Peraturan Pemerintah Nomor 94 Tahun 2021", "Peraturan Menteri Keuangan Nomor 216/PMK.01/2018", "Undang-Undang Nomor 17 Tahun 2003"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Regulasi Manajemen Pengembangan SDM Kemenkeu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pengembangan kompetensi diatur dalam <strong>Peraturan Menteri Keuangan Nomor 216/PMK.01/2018 tentang Manajemen Pengembangan Sumber Daya Manusia di Lingkungan Kementerian Keuangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena PMK 216/PMK.01/2018 merupakan regulasi induk yang menetapkan tata kelola pengembangan kapasitas pegawai di Kemenkeu. Maka dari itu, opsi A benar.</p><p>Jawaban tepat: <strong>D. Peraturan Menteri Keuangan Nomor 216/PMK.01/2018</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Keppres 56/1974 mengatur iuran kesejahteraan pegawai.</li><li><strong>B</strong>: PMK 190/PMK.05/2012 mengatur tata cara pembayaran pelaksanaan APBN.</li><li><strong>C</strong>: PP 94/2021 mengatur tentang Disiplin Pegawai Negeri Sipil.</li><li><strong>E</strong>: UU 17/2003 adalah undang-undang tentang Keuangan Negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 68) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Peraturan Menteri Keuangan Nomor 216/PMK.01/2018 • Manajemen Pengembangan Sumber Daya Manusia di</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Ketentuan Manajemen Pengembangan SDM dalam PMK 216/PMK.01/2018 TIDAK BERLAKU bagi kelompok pegawai berikut, yaitu...", "options": ["Pejabat fungsional ahli madya yang aktif berdinas", "Pegawai pelaksana yang baru lulus masa orientasi", "Pegawai yang sedang menjalani penugasan di luar Kemenkeu", "Pegawai yang memiliki nilai kinerja sangat baik", "Seluruh pejabat pimpinan tinggi pratama kantor wilayah"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengecualian Keberlakuan PMK 216/PMK.01/2018</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Manajemen Pengembangan SDM PMK 216/2018 tidak berlaku bagi: <strong>a. Pegawai penugasan di luar Kemenkeu, b. Pegawai diberhentikan sementara, dan c. Pegawai sedang CLTN</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena pegawai yang sedang bertugas di instansi luar Kemenkeu tunduk pada manajemen pembinaan instansi tempat penugasan. Oleh sebab itu, opsi A benar.</p><p>Jawaban tepat: <strong>C. Pegawai yang sedang menjalani penugasan di luar Kemenkeu</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pejabat fungsional aktif merupakan sasaran utama pengembangan kompetensi.</li><li><strong>B</strong>: Pegawai pelaksana aktif wajib mengikuti manajemen pengembangan SDM PMK 216.</li><li><strong>D</strong>: Pegawai berkinerja sangat baik tetap diatur dalam peta talent pool PMK 216.</li><li><strong>E</strong>: Pejabat pimpinan tinggi pratama wajib menerapkan leadership framework PMK 216.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 68) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pengembangan SDM dalam PMK- 216 tahun • Pegawai yang sedang menjalani penugasan di luar Kementerian Keuangan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Selain penugasan di luar kementerian, dua status kepegawaian lain yang dikecualikan dari PMK 216/PMK.01/2018 adalah...", "options": ["Menduduki jabatan pelaksana dan pemeriksa pemula", "Sedang tugas belajar dan sedang cuti melahirkan", "Menjalani cuti tahunan dan cuti bersama dinas", "Diberhentikan sementara dan sedang menjalani CLTN", "Berdinas di wilayah perbatasan dan daerah terpencil"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Status Pegawai yang Dikecualikan dari PMK 216/2018</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pengecualian PMK 216/2018 mencakup: <strong>Pegawai yang diberhentikan sementara sebagai PNS</strong> dan <strong>Pegawai yang sedang menjalani cuti di luar tanggungan negara (CLTN)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena status pemberhentian sementara dan CLTN membekukan hak pembinaan kedinasan aktif pegawai. Dengan demikian, opsi A benar.</p><p>Jawaban tepat: <strong>D. Diberhentikan sementara dan sedang menjalani CLTN</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pelaksana dan pemeriksa pemula tetap terikat penuh ketentuan PMK 216.</li><li><strong>B</strong>: Pegawai tugas belajar dan cuti melahirkan tetap dalam naungan manajemen SDM Kemenkeu.</li><li><strong>C</strong>: Cuti tahunan dan bersama adalah cuti rutin yang tidak mengecualikan keberlakuan PMK.</li><li><strong>E</strong>: Pegawai di daerah perbatasan tetap menjadi sasaran pembinaan SDM kementerian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 68) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pegawai yang diberhentikan sementara sebagai pegawai negeri sipil • Pegawai yang sedang menjalani cuti di luar tanggungan negara</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Manajemen Pengembangan SDM Kemenkeu merupakan mekanisme pengembangan kompetensi yang mengacu pada...", "options": ["Opini wajar tanpa pengecualian dari audit BPK", "Survei kepuasan masyarakat terhadap unit pelayanan", "Laporan laba rugi badan layanan umum kementerian", "Indeks harga saham gabungan di bursa efek", "Arsitektur kepemimpinan dan box pemetaan pegawai"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Acuan Manajemen Pengembangan SDM Kemenkeu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Manajemen Pengembangan SDM mengacu pada <strong>arsitektur kepemimpinan (leadership framework) dengan memperhatikan jenjang Jabatan, box pemetaan Pegawai, serta kesenjangan kompetensi dan kinerja</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena modul secara eksplisit menyebut leadership framework dan pemetaan 9-box sebagai acuan strategis pengembangan SDM Kemenkeu. Oleh karena itu, opsi A benar.</p><p>Jawaban tepat: <strong>E. Arsitektur kepemimpinan dan box pemetaan pegawai</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Opini BPK adalah ukuran akuntabilitas laporan keuangan negara.</li><li><strong>B</strong>: Survei kepuasan masyarakat mengukur output layanan, bukan acuan arsitektur pengembangan SDM.</li><li><strong>C</strong>: Laporan BLU berkaitan dengan keuangan entitas layanan umum.</li><li><strong>D</strong>: Bursa efek tidak memiliki korelasi dengan pembinaan SDM birokrasi kementerian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 68) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>kepemimpinan (leadership framework) • box pemetaan Pegawai</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C3 - Aplikasi", "question": "Seorang pejabat fungsional pemeriksa diukur kompetensi audit digitalnya terhadap standar jabatan analis. Proses ini merupakan...", "options": ["Analisis kesenjangan kompetensi pegawai", "Evaluasi kesenjangan realisasi target kinerja", "Pemeriksaan dugaan pelanggaran kode etik", "Penilaian kepatuhan jam kerja fingerprint", "Uji kelayakan pemberian tunjangan kemahalan"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Implementasi Analisis Kesenjangan Kompetensi Jabatan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Membandingkan keterampilan dan kapasitas pegawai dengan standar kualifikasi jabatan merupakan wujud nyata dari <strong>analisis kesenjangan kompetensi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena fokus pengujian berada pada kesesuaian antara profil kemampuan teknis pegawai dan standar kompetensi jabatan pemeriksa. Maka dari itu, opsi A benar.</p><p>Jawaban tepat: <strong>A. Analisis kesenjangan kompetensi pegawai</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kesenjangan kinerja mengukur pencapaian target output, bukan kesesuaian kapasitas keahlian.</li><li><strong>C</strong>: Pemeriksaan kode etik dilakukan oleh majelis kehormatan atas laporan pelanggaran moral.</li><li><strong>D</strong>: Presensi fingerprint mengukur kedisiplinan kehadiran jam dinas.</li><li><strong>E</strong>: Tunjangan kemahalan ditentukan oleh indeks lokasi geografis penugasan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 68) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>profil kompetensi PNS dengan • analisis kesenjangan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C3 - Aplikasi", "question": "Penilaian akhir tahun menunjukkan realisasi penerimaan yang ditangani seorang AR hanya 70% dari target. Evaluasi ini mencerminkan...", "options": ["Gugurnya hak pengembangan kompetensi pegawai", "Adanya kesenjangan kinerja terhadap target jabatan", "Kesenjangan kompetensi manajerial kepemimpinan", "Pelanggaran larangan rangkap jabatan kedinasan", "Kewajiban mengembalikan gaji pokok satu tahun"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Implementasi Analisis Kesenjangan Kinerja Pegawai</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Selisih antara hasil capaian kerja 70% dengan sasaran kinerja target jabatan 100% merupakan indikasi timbulnya <strong>kesenjangan kinerja</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena membandingkan hasil kerja nyata terhadap target kinerja jabatan yang diduduki adalah definisi analisis kesenjangan kinerja. Oleh sebab itu, opsi A benar.</p><p>Jawaban tepat: <strong>B. Adanya kesenjangan kinerja terhadap target jabatan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kekurangan capaian kinerja justru mewajibkan instansi memberikan intervensi pengembangan kompetensi.</li><li><strong>C</strong>: Angka 70% merefleksikan gap capaian hasil kerja (kinerja), bukan langsung kompetensi manajerial.</li><li><strong>D</strong>: Capaian target 70% bukan delik pelanggaran rangkap jabatan.</li><li><strong>E</strong>: Tidak ada aturan pengembalian gaji pokok yang sah diterima akibat target belum tercapai.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 68) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>kinerja PNS dengan target kinerja Jabatan • analisis kesenjangan kinerja</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C4 - Analisis", "question": "Perbedaan mendasar antara analisis kesenjangan kompetensi dan analisis kesenjangan kinerja aparatur adalah...", "options": ["Kompetensi menentukan pensiun, kinerja menentukan cuti tahunan", "Kompetensi menilai jam kerja, kinerja menilai pakaian dinas", "Kompetensi mengukur aspek administratif, kinerja mengukur integritas", "Kompetensi menguji kapasitas profil, kinerja menguji hasil target", "Kompetensi dinilai auditor BPK, kinerja dinilai kepala kantor"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Analisis Pembeda Kesenjangan Kompetensi dan Kinerja</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kesenjangan kompetensi membandingkan profil kompetensi dengan standar jabatan, sedangkan kesenjangan kinerja membandingkan capaian hasil dengan target kinerja jabatan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena kompetensi berfokus pada kapasitas keahlian dan pengetahuan, sedangkan kinerja berfokus pada efektivitas hasil keluaran tugas terhadap sasaran organisasi. Dengan demikian, opsi A benar.</p><p>Jawaban tepat: <strong>D. Kompetensi menguji kapasitas profil, kinerja menguji hasil target</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pensiun dan cuti tahunan tidak ditentukan secara langsung oleh pembedaan analisis ini.</li><li><strong>B</strong>: Jam kerja dan pakaian dinas adalah aspek kedisiplinan aturan tata tertib.</li><li><strong>C</strong>: Kurang tepat, kompetensi mencakup pengetahuan, keterampilan, dan sikap kerja, bukan sekadar administrasi.</li><li><strong>E</strong>: Kedua analisis merupakan ranah internal manajemen kepegawaian instansi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 68) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>kesenjangan kompetensi dan analisis kesenjangan kinerja • standar kompetensi Jabatan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C4 - Analisis", "question": "Dokumen resmi kebutuhan dan rencana pengembangan kompetensi PNS wajib memuat 7 komponen berikut, KECUALI...", "options": ["Estimasi alokasi anggaran yang dibutuhkan", "Jenis kompetensi yang perlu dikembangkan", "Target PNS yang akan dikembangkan kapasitasnya", "Penyelenggara dan jadwal waktu pelaksanaan", "Daftar riwayat harta kekayaan keluarga pegawai"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Analisis Komponen Dokumen Rencana Kebutuhan Pembelajaran</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Rencana pengembangan kompetensi memuat: jenis kompetensi, target PNS, jenis/jalur, penyelenggara, jadwal, standar kurikulum, dan anggaran. Tidak memuat daftar harta kekayaan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat sebagai pengecualian karena daftar harta kekayaan merupakan dokumen LHKPN/LHKASN pengawasan integritas, bukan instrumen dokumen rencana diklat ASN. Oleh sebab itu, opsi A benar.</p><p>Jawaban tepat: <strong>E. Daftar riwayat harta kekayaan keluarga pegawai</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Anggaran yang dibutuhkan wajib dicantumkan guna ketersediaan DIPA satker.</li><li><strong>B</strong>: Jenis kompetensi merupakan komponen pertama dalam rencana pembelajaran.</li><li><strong>C</strong>: Target PNS penerima diklat wajib dirinci secara jelas dalam rencana.</li><li><strong>D</strong>: Penyelenggara dan jadwal waktu merupakan komponen pokok pelaksanaan diklat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 68) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Kebutuhan dan rencana pengembangan kompetensi meliputi: • anggaran yang dibutuhkan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Pendekatan desain pembelajaran pegawai di lingkungan Kementerian Keuangan dilaksanakan berdasarkan...", "options": ["Model 10:20:70 terintegrasi", "Metode ceramah klasikal murni", "Pendekatan belajar mandiri seratus persen", "Model penugasan proyek luar negeri", "Sistem hafalan modul regulasi dinas"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kerangka Desain Pembelajaran Model 10:20:70</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Desain Pembelajaran di Kementerian Keuangan dilaksanakan berdasarkan Model 10:20:70</strong>, yaitu perpaduan proporsional antara pembelajaran formal, kolaboratif, dan praktik langsung.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena modul secara spesifik menetapkan Model 10:20:70 sebagai kerangka baku desain pembelajaran modern di Kemenkeu. Oleh sebab itu, opsi A benar.</p><p>Jawaban tepat: <strong>A. Model 10:20:70 terintegrasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Ceramah klasikal hanya mengambil porsi 10%, bukan diterapkan secara murni.</li><li><strong>C</strong>: Belajar mandiri seratus persen mengabaikan interaksi sosial dan praktik tempat kerja.</li><li><strong>D</strong>: Penugasan luar negeri hanya salah satu bentuk khusus, bukan model umum.</li><li><strong>E</strong>: Sistem hafalan bertentangan dengan pendekatan pembelajaran berbasis pengalaman kerja.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 69) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Model 10:20:70 • Desain Pembelajaran di Kementerian Keuangan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Dalam Model 10:20:70, porsi 10% dialokasikan secara spesifik untuk aktivitas pembelajaran berupa...", "options": ["Praktik kerja lapangan dan magang di kantor cabang", "Metode ceramah, pelatihan jarak jauh, dan belajar mandiri", "Sesi mentoring intensif bersama pimpinan eselon satu", "Patok banding sistem ke instansi perpajakan luar negeri", "Perluasan tanggung jawab pekerjaan di unit asal"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Komposisi 10% Pembelajaran Klasikal</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Porsi <strong>10%</strong> adalah <strong>aktivitas Pembelajaran melalui metode ceramah dalam berbagai pelatihan di dalam kelas (klasikal) maupun di luar kelas, seperti pelatihan teknis, pelatihan jarak jauh, dan belajar mandiri</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena alokasi 10% difokuskan pada perolehan teori dasar melalui ceramah, e-learning/PJJ, dan studi mandiri. Maka dari itu, opsi A benar.</p><p>Jawaban tepat: <strong>B. Metode ceramah, pelatihan jarak jauh, dan belajar mandiri</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Magang dan praktik kerja menempati porsi 70%, bukan 10%.</li><li><strong>C</strong>: Mentoring merupakan bagian dari alokasi 20% pembelajaran sosial kolaboratif.</li><li><strong>D</strong>: Patok banding (benchmarking) termasuk dalam klaster 20% kolaboratif.</li><li><strong>E</strong>: Perluasan pekerjaan (job enlargement) berada pada kelompok 70% praktik kerja.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 69) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>10% (sepuluh persen) aktivitas Pembelajaran melalui metode ceramah • pelatihan jarak jauh, dan belajar mandiri</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan Model 10:20:70, alokasi 20% pembelajaran kolaboratif diwujudkan melalui kegiatan...", "options": ["Mengikuti ujian sertifikasi keahlian mandiri", "Membaca diktat modul di ruang perpustakaan", "Coaching, mentoring, dan patok banding (benchmarking)", "Menjalani detasering (secondment) di badan usaha", "Melakukan rotasi mutasi jabatan antar-wilayah"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Komposisi 20% Pembelajaran Kolaboratif</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Porsi <strong>20%</strong> adalah <strong>aktivitas Pembelajaran kolaboratif dalam sebuah komunitas maupun bimbingan, seperti coaching, mentoring, dan patok banding (benchmarking)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena porsi 20% menekankan proses belajar melalui interaksi sosial, keteladanan mentor, dan studi komparasi. Oleh karena itu, opsi A benar.</p><p>Jawaban tepat: <strong>C. Coaching, mentoring, dan patok banding (benchmarking)</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Ujian sertifikasi mandiri adalah evaluasi formal, bukan pembelajaran kolaboratif 20%.</li><li><strong>B</strong>: Membaca mandiri masuk dalam porsi 10% belajar mandiri.</li><li><strong>D</strong>: Detasering menempati porsi 70% pengalaman kerja langsung.</li><li><strong>E</strong>: Rotasi mutasi adalah kebijakan mutasi pegawai, bukan metode interaksi 20%.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 69) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>20% (dua puluh persen) aktivitas Pembelajaran kolaboratif • pembimbingan (coaching), pendampingan (mentoring)</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Porsi terbesar (70%) dalam desain pembelajaran Kementerian Keuangan dilaksanakan melalui aktivitas...", "options": ["Perkuliahan pascasarjana di gedung kampus", "Praktik langsung terintegrasi di tempat kerja", "Penyusunan karya tulis ilmiah kedinasan", "Pelatihan jarak jauh melalui aplikasi gawai", "Penyelenggaraan seminar akbar nasional satker"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Komposisi 70% Pembelajaran Terintegrasi Tempat Kerja</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Porsi terbesar yaitu <strong>70% adalah aktivitas Pembelajaran terintegrasi di tempat kerja melalui praktik langsung</strong>, seperti magang, secondment, job enlargement, dan job enrichment.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena modul menegaskan bahwa 70% kompetensi efektif dibangun langsung dari penanganan pekerjaan riil di tempat tugas. Dengan demikian, opsi A benar.</p><p>Jawaban tepat: <strong>B. Praktik langsung terintegrasi di tempat kerja</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kuliah formal adalah bentuk tugas belajar pendidikan, bukan porsi 70% tempat kerja.</li><li><strong>C</strong>: Karya tulis ilmiah adalah luaran akademik, bukan praktik operasional kerja harian.</li><li><strong>D</strong>: Pelatihan jarak jauh menempati alokasi 10% pembelajaran formal.</li><li><strong>E</strong>: Seminar akbar masuk dalam kategori pembelajaran klasikal 10%.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 69) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>70% (tujuh puluh persen) aktivitas Pembelajaran terintegrasi di tempat kerja • praktik langsung</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan KMK Nomor 982/KMK.01/2017, tiga ruang lingkup program pengembangan kompetensi PNS Kemenkeu adalah...", "options": ["Program tunjangan, pensiun, dan asuransi kesehatan", "Program mutasi, promosi, dan demosi kepegawaian", "Program pelatihan, pendidikan, dan re-entry program", "Program presensi, disiplin, dan hukuman aparatur", "Program pengadaan barang, jasa, dan audit satker"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tiga Ruang Lingkup Program KMK 982/KMK.01/2017</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>KMK Nomor 982/KMK.01/2017 menetapkan tiga ruang lingkup: <strong>a. Program pelatihan, b. Program pendidikan, dan c. Program pemanfaatan lulusan pendidikan (re-entry program)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena regulasi tersebut secara sistematis memayungi siklus pelatihan, tugas belajar pendidikan, hingga pemberdayaan alumni tugas belajar pasca-kelulusan. Maka dari itu, opsi A benar.</p><p>Jawaban tepat: <strong>C. Program pelatihan, pendidikan, dan re-entry program</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Tunjangan dan pensiun adalah skema kesejahteraan, bukan ruang lingkup program belajar.</li><li><strong>B</strong>: Mutasi, promosi, dan demosi adalah instrumen pola karier, bukan rumpun KMK 982.</li><li><strong>D</strong>: Presensi dan disiplin adalah penegakan tata tertib kerja ASN.</li><li><strong>E</strong>: Pengadaan barang dan jasa adalah fungsi anggaran keuangan negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 69) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Keputusan Menteri Keuangan RI Nomor 982/KMK.01/2017 • tiga ruang lingkup pengembangan kompetensi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Bentuk pengembangan kompetensi pegawai dalam PMK 216/PMK.01/2018 terdiri atas...", "options": ["Penilaian dan/atau penyidikan", "Penataran dan/atau indoktrinasi", "Pengawasan dan/atau investigasi", "Pendidikan dan/atau pembelajaran", "Ujian dinas dan/atau apel pagi"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Bentuk Pengembangan Kompetensi Pendidikan dan Pembelajaran</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Bentuk pengembangan Kompetensi terdiri atas pendidikan dan/atau pembelajaran</strong>. Pendidikan melalui tugas belajar formal, sedangkan pembelajaran melalui jalur klasikal dan nonklasikal.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena dua pilar utama pengembangan kompetensi yang diatur adalah jalur formal (pendidikan) dan jalur aplikatif (pembelajaran). Oleh sebab itu, opsi A benar.</p><p>Jawaban tepat: <strong>D. Pendidikan dan/atau pembelajaran</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penyidikan merupakan ranah penegakan hukum pidana.</li><li><strong>B</strong>: Penataran dan indoktrinasi bukan nomenklatur modern pengembangan kapasitas ASN.</li><li><strong>C</strong>: Pengawasan dan investigasi adalah tugas aparat pengawasan internal pemerintah (APIP).</li><li><strong>E</strong>: Apel pagi adalah pembinaan disiplin harian, bukan bentuk formal pengembangan kompetensi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 69) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pengembangan Kompetensi terdiri atas pendidikan dan/atau • Pendidikan dilakukan dengan pemberian tugas belajar</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C3 - Aplikasi", "question": "Seorang penelaah pajak ditugaskan magang kerja (secondment) di unit audit kepabeanan selama 3 bulan. Metode ini mencerminkan...", "options": ["Penjatuhan sanksi pemindahan penugasan dinas", "Pembelajaran jarak jauh metode ceramah porsi 10%", "Aktivitas pembimbingan kolaboratif porsi 20%", "Pendidikan formal tugas belajar bergelar sarjana", "Pembelajaran terintegrasi tempat kerja porsi 70%"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Implementasi Detasering dalam Klaster 70%</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Detasering (secondment) dan praktik kerja langsung merupakan bagian dari <strong>70% aktivitas Pembelajaran terintegrasi di tempat kerja</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena secondment memberikan pengalaman kerja nyata secara langsung di lingkungan operasional, yang merupakan esensi porsi 70% model 10:20:70. Dengan demikian, opsi A benar.</p><p>Jawaban tepat: <strong>E. Pembelajaran terintegrasi tempat kerja porsi 70%</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Secondment adalah program pembelajaran peningkatan kompetensi, bukan sanksi hukuman.</li><li><strong>B</strong>: Ceramah dan PJJ berada pada porsi 10% teori kelas.</li><li><strong>C</strong>: Coaching dan mentoring sosial mengisi porsi 20%, bukan penugasan kerja operasional.</li><li><strong>D</strong>: Detasering bukan pendidikan formal yang menghasilkan gelar akademik.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 69) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>magang atau praktik kerja • aktivitas Pembelajaran terintegrasi di tempat kerja</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C3 - Aplikasi", "question": "Seorang pegawai baru menyelesaikan studi master di luar negeri dan diarahkan dalam program transfer pengetahuan. Program ini merupakan...", "options": ["Program pemanfaatan lulusan (re-entry program)", "Program orientasi CPNS rekrutan umum", "Program prajabatan pembentukan karakter ASN", "Program pensiun persiapan masa purnatugas", "Program penugasan khusus daerah tertinggal"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Implementasi Re-entry Program Alumni Tugas Belajar</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pemberdayaan pegawai yang baru kembali dari tugas belajar guna mendiseminasikan keahlian barunya diwadahi dalam <strong>Program pemanfaatan lulusan pendidikan (re-entry program)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena re-entry program dirancang khusus untuk memastikan ilmu baru alumni tugas belajar dapat langsung diterapkan pada proses bisnis kementerian. Maka dari itu, opsi A benar.</p><p>Jawaban tepat: <strong>A. Program pemanfaatan lulusan (re-entry program)</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Orientasi CPNS ditujukan bagi calon pegawai baru, bukan lulusan master tugas belajar.</li><li><strong>C</strong>: Prajabatan/Latsar dilaksanakan pada awal karier CPNS.</li><li><strong>D</strong>: Persiapan purnatugas diperuntukkan bagi pegawai menjelang usia pensiun.</li><li><strong>E</strong>: Re-entry program tidak identik dengan pengiriman ke daerah tertinggal.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 69) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Program pemanfaatan lulusan pendidikan (re-entry program) • Keputusan Menteri Keuangan RI Nomor 982/KMK.01/2017</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C3 - Aplikasi", "question": "Pimpinan menambah variasi tugas dan memperluas wewenang pengambilan keputusan staf guna mengasah kapasitasnya. Metode ini mencakup...", "options": ["Sesi patok banding benchmarking porsi 20%", "Metode ceramah klasikal di dalam ruang kelas 10%", "Job enlargement dan job enrichment pada porsi 70%", "Pemberian tugas belajar pendidikan formal bergelar", "Pemberian hukuman disiplin penambahan beban kerja"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penerapan Job Enlargement dan Job Enrichment</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Perluasan jenis tugas (<strong>job enlargement</strong>) dan pengayaan bobot tanggung jawab (<strong>job enrichment</strong>) merupakan metode pembelajaran di tempat kerja dalam <strong>porsi 70%</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena penambahan variasi dan pengayaan kewenangan langsung mematangkan keterampilan pegawai dalam praktik tugas nyata di kantor. Oleh karena itu, opsi A benar.</p><p>Jawaban tepat: <strong>C. Job enlargement dan job enrichment pada porsi 70%</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Benchmarking adalah studi banding ke instansi lain, bukan penataan beban tugas staf sendiri.</li><li><strong>B</strong>: Ceramah klasikal tidak melibatkan intervensi desain struktur pekerjaan di kantor.</li><li><strong>D</strong>: Bukan tugas belajar perkuliahan formal ke universitas.</li><li><strong>E</strong>: Job enlargement/enrichment adalah metode pengembangan kompetensi yang konstruktif, bukan hukuman.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 69) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pekerjaan job enlargement) • pengayaan pekerjaan job enrichment)</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C4 - Analisis", "question": "Pergeseran paradigma utama pembelajaran melalui Model 10:20:70 di lingkungan birokrasi kementerian adalah...", "options": ["Mewajibkan seluruh pegawai magang di perusahaan swasta", "Mengutamakan program magang kerja profesional di sektor industri swasta", "Menjadikan jam perkuliahan formal sebagai tolok ukur tunggal", "Beralih dari dominasi kelas teori ke praktik nyata di tempat kerja", "Menitikberatkan pembelajaran mandiri berbasis digital tanpa pendampingan"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Analisis Pergeseran Paradigma Pembelajaran Modern</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Model 10:20:70 menggeser fokus dari diklat di ruang kelas (hanya 10%) menuju <strong>penguatan praktik langsung di lingkungan kerja (70%) dan pembelajaran kolaboratif (20%)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena model ini mengakui bahwa keahlian profesional sejati terbentuk dari pengalaman langsung menyelesaikan masalah kedinasan di meja kerja. Oleh sebab itu, opsi A benar.</p><p>Jawaban tepat: <strong>D. Beralih dari dominasi kelas teori ke praktik nyata di tempat kerja</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pertukaran swasta adalah opsi fleksibel, bukan kewajiban bagi seluruh pegawai.</li><li><strong>B</strong>: Kurang tepat, model 10:20:70 tidak mewajibkan magang di sektor swasta melainkan fokus pada pengalaman kerja kedinasan.</li><li><strong>C</strong>: Perkuliahan formal bukan ukuran tunggal melainkan hanya salah satu jalur pengembangan.</li><li><strong>E</strong>: Kurang tepat, pendampingan atau mentoring justru mengambil porsi 20 persen dalam model tersebut.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 69) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Model 10:20:70 • 70% (tujuh puluh persen) aktivitas Pembelajaran terintegrasi di tempat kerja</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C4 - Analisis", "question": "Berdasarkan tata kelola pengembangan SDM Kemenkeu, penyelenggaraan program pelatihan wajib dilaksanakan melalui...", "options": ["Undian acak nomor induk pegawai dalam sistem absensi", "Penetapan kuota seragam tanpa melihat profil kinerja", "Permintaan spontan peserta tanpa persetujuan pimpinan", "Tawaran paket promosi komersial dari lembaga kursus", "Mekanisme Analisis Kebutuhan Pembelajaran (AKP)"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Analisis Mekanisme Penyelenggaraan Pelatihan Melalui AKP</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Program pelatihan dilakukan melalui mekanisme Analisis Kebutuhan Pembelajaran (AKP)</strong> guna menjamin kesesuaian antara materi diklat dengan kebutuhan organisasi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena AKP merupakan instrumen terstandar yang menganalisis gap pengetahuan, keterampilan, dan sikap sebelum suatu program diklat dirancang dan digelar. Dengan demikian, opsi A benar.</p><p>Jawaban tepat: <strong>E. Mekanisme Analisis Kebutuhan Pembelajaran (AKP)</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Undian acak mencederai prinsip meritokrasi dan objektivitas perencanaan SDM.</li><li><strong>B</strong>: Penetapan kuota buta tanpa analisis gap melanggar prinsip efisiensi anggaran diklat.</li><li><strong>C</strong>: Pelatihan kedinasan harus berbasis kebutuhan organisasi, bukan selera subjektif individu.</li><li><strong>D</strong>: Pelatihan ASN dirancang berdasarkan kurikulum instansi pembina, bukan promosi vendor swasta.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 69) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Program Pelatihan • Program pelatihan dilakukan melalui mekanisme Analisis</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Pedoman Analisis Kebutuhan Pembelajaran (AKP) di lingkungan Kementerian Keuangan secara resmi diatur dalam...", "options": ["PMK Nomor 45/PMK.011/2018", "PMK Nomor 190/PMK.05/2012", "PP Nomor 94 Tahun 2021", "KMK Nomor 982/KMK.01/2017", "Keppres Nomor 56 Tahun 1974"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Regulasi Pedoman Analisis Kebutuhan Pembelajaran</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pedoman AKP diatur dalam <strong>Peraturan Menteri Keuangan Nomor 45/PMK.011/2018 tentang Pedoman Analisis Kebutuhan Pembelajaran di Lingkungan Kementerian Keuangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena PMK 45/PMK.011/2018 merupakan regulasi rujukan operasional analisis kebutuhan diklat bagi seluruh unit di Kemenkeu. Oleh sebab itu, opsi A benar.</p><p>Jawaban tepat: <strong>A. PMK Nomor 45/PMK.011/2018</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: PMK 190/PMK.05/2012 mengatur tata cara pembayaran dalam rangka pelaksanaan APBN.</li><li><strong>C</strong>: PP 94/2021 mengatur tata tertib dan hukuman disiplin PNS.</li><li><strong>D</strong>: KMK 982/2017 memuat kerangka program pengembangan kompetensi Kemenkeu.</li><li><strong>E</strong>: Keppres 56/1974 mengatur iuran kesejahteraan pensiun dan perumahan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 70) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>45/PMK.011/2018 tentang Pedoman Analisis Kebutuhan Pembelajaran • AKP adalah serangkaian proses analisis</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Analisis Kebutuhan Pembelajaran (AKP) didefinisikan sebagai serangkaian proses analisis terhadap...", "options": ["Selisih penerimaan pajak terhadap target pendapatan kas", "Kesenjangan pengetahuan, keterampilan, dan sikap pegawai", "Kebutuhan anggaran sarana dan prasarana gedung kantor", "Laporan pengaduan masyarakat atas pelanggaran kode etik", "Tingkat kepatuhan presensi jam kerja harian aparatur"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Analisis Kebutuhan Pembelajaran (AKP)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>AKP adalah serangkaian proses analisis terhadap kesenjangan pengetahuan, keterampilan, dan sikap dalam rangka pengembangan sumber daya manusia dengan program Pembelajaran guna mendukung target kinerja organisasi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena ketiga pilar kompetensi—knowledge (pengetahuan), skill (keterampilan), dan attitude (sikap)—merupakan objek inti yang dipetakan kesenjangannya dalam AKP. Dengan demikian, opsi A benar.</p><p>Jawaban tepat: <strong>B. Kesenjangan pengetahuan, keterampilan, dan sikap pegawai</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Target penerimaan pajak adalah indikator kinerja fiskal DJP, bukan objek AKP.</li><li><strong>C</strong>: Kebutuhan sarana gedung adalah urusan manajemen aset barang milik negara (BMN).</li><li><strong>D</strong>: Pengaduan kode etik diperiksa oleh atasan dan unit kepatuhan internal.</li><li><strong>E</strong>: Presensi jam kerja diukur melalui aplikasi kehadiran, bukan metodologi AKP.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 70) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>kesenjangan pengetahuan, keterampilan, dan sikap • AKP adalah serangkaian proses analisis</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Pelaksanaan Analisis Kebutuhan Pembelajaran di lingkungan Kementerian Keuangan dilaksanakan bersama oleh...", "options": ["BPPK (Unit Pengelola) dan Unit Pengguna koordinasi Setjen", "KPK dan BPK RI secara eksternal", "Inspektorat Jenderal dan Badan Kepegawaian Negara", "Lembaga Administrasi Negara dan Konsultan Swasta", "Biro Umum dan Kantor Wilayah Perbendaharaan"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pelaksana Analisis Kebutuhan Pembelajaran</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>AKP <strong>dilaksanakan oleh Unit Pengelola (BPPK) dan Unit Pengguna (Unit Eselon I) serta berkoordinasi dengan Sekretariat Jenderal</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena modul secara spesifik menetapkan kolaborasi tripartit internal Kemenkeu: BPPK selaku pengelola pembelajaran, Unit Eselon I sebagai pengguna, dan Setjen sebagai koordinator. Maka dari itu, opsi A benar.</p><p>Jawaban tepat: <strong>A. BPPK (Unit Pengelola) dan Unit Pengguna koordinasi Setjen</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: KPK dan BPK adalah lembaga pemeriksa/penegak hukum eksternal.</li><li><strong>C</strong>: Itjen dan BKN bukan penyelenggara utama pelaksanaan teknis AKP Kemenkeu.</li><li><strong>D</strong>: LAN adalah pembina nasional, namun pelaksana internal Kemenkeu adalah BPPK.</li><li><strong>E</strong>: Biro Umum mengelola perlengkapan logistik rumah tangga kementerian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 70) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>dilaksanakan oleh Unit Pengelola (BPPK) dan Unit Pengguna • berkoordinasi dengan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan PMK 45/PMK.011/2018, Analisis Kebutuhan Pembelajaran terbagi menjadi dua jenis yaitu...", "options": ["AKP Teknis dan AKP Nonteknis", "AKP Mandiri dan AKP Kelompok", "AKP Tahunan dan AKP Bulanan", "AKP Formal dan AKP Informal", "AKP Reguler dan AKP Insidental"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dua Jenis Analisis Kebutuhan Pembelajaran</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>AKP terdiri dari dua jenis, yaitu AKP Reguler dan AKP Insidental</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena klasifikasi yuridis yang ditetapkan PMK 45/2018 hanya membagi AKP ke dalam dua kelompok: pelaksanaan terjadwal tahunan (Reguler) dan pelaksanaan responsif sewaktu-waktu (Insidental). Oleh sebab itu, opsi A benar.</p><p>Jawaban tepat: <strong>E. AKP Reguler dan AKP Insidental</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Teknis dan nonteknis merupakan rumpun substansi kompetensi, bukan jenis AKP.</li><li><strong>B</strong>: Mandiri dan kelompok bukan klasifikasi resmi jenis AKP dalam regulasi.</li><li><strong>C</strong>: Tahunan dan bulanan bukan pembagian tata kelola nomenklatur AKP.</li><li><strong>D</strong>: Formal dan informal merujuk pada bentuk pembelajaran, bukan jenis AKP.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 70) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>AKP terdiri dari dua jenis, yaitu AKP Reguler dan AKP Insidental • dua jenis, yaitu AKP Reguler</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Karakteristik pelaksanaan AKP Reguler adalah dilaksanakan...", "options": ["Secara mendadak saat terjadi pergantian pejabat", "Secara terjadwal sebelum tahun pembelajaran berjalan", "Sepanjang tahun berjalan tanpa jadwal tertulis", "Setiap lima tahun sekali bersamaan sensus pegawai", "Berdasarkan permohonan mandiri pegawai yang diajukan ke dinas"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Waktu dan Karakteristik Pelaksanaan AKP Reguler</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>AKP Reguler adalah AKP yang dilaksanakan secara terjadwal sebelum tahun pembelajaran berjalan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena sifat dasar AKP Reguler adalah terencana dan terjadwal secara periodik menjelang dimulainya kalender anggaran/pembelajaran tahun berikutnya. Dengan demikian, opsi A benar.</p><p>Jawaban tepat: <strong>B. Secara terjadwal sebelum tahun pembelajaran berjalan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pelaksanaan mendadak responsif merupakan ciri dari AKP Insidental.</li><li><strong>C</strong>: Pelaksanaan sepanjang tahun berjalan merupakan karakteristik AKP Insidental.</li><li><strong>D</strong>: AKP dilaksanakan setiap tahun, bukan siklus lima tahunan sensus.</li><li><strong>E</strong>: Kurang tepat, AKP Reguler merupakan inisiatif terencana instansi sebelum tahun anggaran berjalan, bukan pengajuan perorangan ad-hoc.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 70) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>adalah AKP yang dilaksanakan secara terjadwal • sebelum tahun pembelajaran</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C2 - Pemahaman", "question": "AKP Reguler yang diselenggarakan secara terjadwal mencakup tiga komponen analisis yaitu...", "options": ["AKP Teori, AKP Praktik, dan AKP Ujian Sertifikasi", "AKP Pimpinan, AKP Bawahan, dan AKP Pensiunan", "AKP Pusat, AKP Wilayah, dan AKP Pelaksana Teknis", "AKP Strategis, AKP Jabatan, dan AKP Individu", "AKP Anggaran, AKP Sarana, dan AKP Kurikulum Diklat"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tiga Komponen dalam Rumpun AKP Reguler</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>AKP Reguler <strong>terdiri atas AKP Strategis, AKP Jabatan dan AKP Individu</strong> yang saling melengkapi dari level makro organisasi hingga mikro pegawai.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena tata kelola AKP Reguler membedah kebutuhan pembelajaran dari tiga tingkatan terstruktur: arah kebijakan organisasi (Strategis), standar fungsi tugas (Jabatan), dan profil gap pegawai (Individu). Maka dari itu, opsi A benar.</p><p>Jawaban tepat: <strong>D. AKP Strategis, AKP Jabatan, dan AKP Individu</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Teori dan praktik adalah metode pelaksanaan materi diklat.</li><li><strong>B</strong>: Pensiunan tidak lagi menjadi sasaran pembinaan pembelajaran AKP aktif.</li><li><strong>C</strong>: Pusat dan wilayah adalah tingkat wilayah kerja birokrasi, bukan klasifikasi AKP.</li><li><strong>E</strong>: Anggaran dan sarana adalah sarana pendukung penyelenggaraan pelatihan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 70) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>terdiri atas AKP Strategis, AKP Jabatan dan AKP Individu • AKP Reguler</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Tujuan utama dilaksanakannya AKP Strategis adalah untuk mendukung...", "options": ["Pengisian jabatan kosong akibat pegawai memasuki masa pensiun", "Kebutuhan perorangan staf yang ingin melanjutkan kuliah pribadi", "Pencapaian kebutuhan strategis dan target kinerja Unit Pengguna", "Penyusunan laporan pertanggungjawaban barang milik negara", "Pelaksanaan audit kepatuhan internal oleh inspektorat jenderal"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi dan Tujuan AKP Strategis</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>AKP Strategis adalah AKP yang dilaksanakan untuk mendukung pencapaian kebutuhan strategis dan target kinerja Unit Pengguna</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena AKP Strategis diarahkan agar kompetensi pegawai mampu mengeksekusi prioritas kebijakan strategis unit eselon I yang bersangkutan. Oleh sebab itu, opsi A benar.</p><p>Jawaban tepat: <strong>C. Pencapaian kebutuhan strategis dan target kinerja Unit Pengguna</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pengisian jabatan kosong merupakan tugas manajemen perencanaan formasi karier.</li><li><strong>B</strong>: Kebutuhan perorangan dipetakan melalui instrumen AKP Individu, bukan Strategis.</li><li><strong>D</strong>: Pertanggungjawaban BMN merupakan tugas pengelola logistik perbendaharaan.</li><li><strong>E</strong>: Audit kepatuhan adalah instrumen pengawasan pengawalan akuntabilitas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 70) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Strategis adalah AKP yang dilaksanakan untuk • mendukung pencapaian target kinerja</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Fungsi pokok dari pelaksanaan AKP Jabatan pada Unit Pengguna adalah untuk...", "options": ["Menentukan besaran tarif sewa gedung asrama pelatihan", "Mendukung pemenuhan kompetensi pemangku jabatan", "Menetapkan besaran tunjangan kinerja jabatan struktural", "Menghitung kebutuhan kendaraan dinas operasional eselon", "Mengatur jadwal piket pengamanan kantor di hari libur"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi dan Fungsi AKP Jabatan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>AKP Jabatan adalah AKP yang dilaksanakan untuk mendukung pemenuhan kompetensi pemangku jabatan pada Unit Pengguna</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena AKP Jabatan berfokus pada standar kompetensi yang wajib dikuasai oleh siapa pun yang menduduki formasi jabatan tersebut agar tugas terlaksana sempurna. Dengan demikian, opsi A benar.</p><p>Jawaban tepat: <strong>B. Mendukung pemenuhan kompetensi pemangku jabatan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Tarif sewa asrama merupakan ranah pengelolaan penerimaan negara bukan pajak (PNBP).</li><li><strong>C</strong>: Besaran tunjangan kinerja ditetapkan melalui peraturan presiden tentang remunerasi.</li><li><strong>D</strong>: Kendaraan dinas diatur melalui standar biaya masukan kementerian keuangan.</li><li><strong>E</strong>: Piket pengamanan merupakan tata tertib rumah tangga kantor.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 70) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>adalah AKP yang dilaksanakan untuk • pemenuhan kompetensi pemangku</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Sasaran utama pelaksanaan AKP Individu adalah mendukung pengembangan kompetensi individu dan...", "options": ["Mengajukan mutasi kedaerahan sesuai domisili keluarga", "Memperoleh beasiswa studi lanjut tanpa ikatan dinas", "Mendapatkan keringanan pemenuhan jam kerja fingerprint", "Memenuhi kesenjangan kinerja dengan target kinerja jabatan", "Menyusun pembelaan hukum atas sanksi hukuman disiplin"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi dan Fungsi AKP Individu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>AKP Individu adalah AKP yang dilaksanakan untuk mendukung pengembangan kompetensi individu dan memenuhi kesenjangan kinerja dengan target kinerja jabatan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena AKP Individu menyasar gap riil yang dialami seorang pegawai terhadap sasaran kinerjanya, sehingga intervensi pembelajaran menjadi tepat sasaran. Oleh karena itu, opsi A benar.</p><p>Jawaban tepat: <strong>D. Memenuhi kesenjangan kinerja dengan target kinerja jabatan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Mutasi kedaerahan diatur melalui pola rotasi kepegawaian BKN/Kemenkeu.</li><li><strong>B</strong>: Tugas belajar diatur tersendiri dan tetap terikat ikatan dinas pengabdian.</li><li><strong>C</strong>: AKP bukan sarana meminta kompensasi pengurangan jam kerja dinas.</li><li><strong>E</strong>: Pembelaan sanksi dilakukan melalui prosedur keberatan dan banding administratif.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 70) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>AKP Individu adalah AKP yang dilaksanakan untuk • kinerja dengan target kinerja jabatan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C2 - Pemahaman", "question": "AKP yang dilaksanakan sepanjang tahun pembelajaran berjalan untuk merespons kebutuhan mendesak yang timbul adalah...", "options": ["AKP Periodik", "AKP Reguler", "AKP Insidental", "AKP Tahunan", "AKP Terpusat"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Karakteristik dan Fungsi AKP Insidental</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>AKP Insidental adalah AKP yang dilaksanakan sepanjang tahun pembelajaran berjalan untuk memenuhi kebutuhan strategis, jabatan, atau individu</strong> yang belum terakomodasi dalam rencana reguler.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena AKP Insidental memiliki sifat dinamis dan responsif untuk merespons dinamika regulasi baru atau perubahan proses bisnis di tengah tahun berjalan. Maka dari itu, opsi A benar.</p><p>Jawaban tepat: <strong>C. AKP Insidental</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Periodik identik dengan jadwal rutin tahunan reguler.</li><li><strong>B</strong>: AKP Reguler dilaksanakan terjadwal sebelum tahun berjalan dimulai.</li><li><strong>D</strong>: AKP tahunan mengacu pada sifat pelaksanaan AKP Reguler.</li><li><strong>E</strong>: Terpusat mengacu pada sentralisasi organisasi, bukan klasifikasi fleksibilitas waktu AKP.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 70) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>yang dilaksanakan sepanjang tahun pembelajaran berjalan • kebutuhan strategis, jabatan, atau individu</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C3 - Aplikasi", "question": "Kementerian Keuangan meluncurkan sistem inti perpajakan baru (Cortex). Analisis kebutuhan pelatihan masif terkait program prioritas ini masuk dalam...", "options": ["AKP Strategis untuk mendukung target organisasi", "AKP Individu atas inisiatif mandiri pegawai", "Ujian kenaikan pangkat penyesuaian ijazah", "Program persiapan purnatugas pejabat eselon", "Evaluasi presensi fingerprint berkala satker"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Implementasi AKP Strategis pada Transformasi Digital Satker</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pelatihan untuk mendukung agenda transformasi makro dan target prioritas instansi merupakan implementasi langsung dari <strong>AKP Strategis</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena peluncuran sistem inti penerimaan negara merupakan target strategis Unit Pengguna yang menuntut kesiapan keahlian seluruh aparatur secara terstruktur. Oleh sebab itu, opsi A benar.</p><p>Jawaban tepat: <strong>A. AKP Strategis untuk mendukung target organisasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Bukan AKP Individu karena sistem baru berlaku secara sistemik bagi seluruh institusi.</li><li><strong>C</strong>: Ujian kenaikan pangkat hanya menguji kualifikasi ijazah formal kepegawaian.</li><li><strong>D</strong>: Bukan program purnatugas karena ditujukan bagi pegawai aktif pelaksana sistem.</li><li><strong>E</strong>: Presensi fingerprint hanya memantau kedisiplinan jam datang dan pulang kantor.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 70) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Strategis adalah AKP yang dilaksanakan untuk • target kinerja Unit Pengguna</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 06", "difficulty": "C4 - Analisis", "question": "Perbedaan pokok antara AKP Reguler dan AKP Insidental dalam manajemen pembelajaran Kemenkeu terletak pada...", "options": ["Hasil akhir (diberikan sertifikat kelulusan vs tanpa sertifikat)", "Instansi pelaksana (dilakukan BPPK vs dilakukan kementerian lain)", "Status peserta (dikhususkan pejabat eselon vs dikhususkan staf)", "Biaya pelaksanaan (dibiayai APBN vs dibiayai pribadi pegawai)", "Waktu pelaksanaan (terjadwal pra-tahun vs fleksibel tahun berjalan)"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Analisis Pembeda Pokok AKP Reguler dan AKP Insidental</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>AKP Reguler dilaksanakan secara terjadwal sebelum tahun pembelajaran berjalan, sedangkan AKP Insidental dilaksanakan secara fleksibel sepanjang tahun berjalan guna merespons dinamika kebutuhan baru.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena dimensi waktu dan pola perencanaan merupakan pembeda pokok: Reguler terencana sebelum tahun berjalan, sedangkan Insidental responsif sepanjang tahun anggaran berjalan. Dengan demikian, opsi A benar.</p><p>Jawaban tepat: <strong>E. Waktu pelaksanaan (terjadwal pra-tahun vs fleksibel tahun berjalan)</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Sertifikat tetap diterbitkan selama peserta memenuhi standar kelulusan diklat.</li><li><strong>B</strong>: Kedua jenis AKP tetap diselenggarakan oleh BPPK bersama Unit Pengguna Kemenkeu.</li><li><strong>C</strong>: Kedua jenis AKP dapat diikuti oleh seluruh jenjang jabatan aparatur.</li><li><strong>D</strong>: Kedua jalur pembelajaran dinas didanai oleh anggaran resmi DIPA negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 70) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>adalah AKP yang dilaksanakan secara terjadwal • yang dilaksanakan sepanjang tahun pembelajaran berjalan</strong></div></div>"}];
let current=0;const answers=new Array(questions.length).fill(null);const letters=["A","B","C","D","E"];let seconds=65*60;let submitted=false;


// Anotasi hanya dalam pembahasan. Satu set coretan untuk setiap nomor soal.
const ANNO_KEY='stangolden_tak_p06_review_annot_v1_user_<?= (int)$USER->id ?>';
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
 try{localStorage.setItem('stg_tak_p06_map_visible',show?'1':'0')}catch(e){}
}
try{if(localStorage.getItem('stg_tak_p06_map_visible')==='0')toggleQuestionMap(false)}catch(e){}
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

  if(modalTitle) modalTitle.textContent="🏆 Lihat Ranking Tata Aturan Kepegawaian Paket 06";
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
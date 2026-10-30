<?php
/**
 * STANGOLDEN.exam • Tata Aturan Kepegawaian Paket 05 (Halaman 52–61)
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
// $DB->get_record("stgtak_p05_rank") => tabel fisik "mdlhv_stgtak_p05_rank".
// Jangan menambahkan prefix secara manual di konstanta berikut.
// Penyimpanan bersama melalui database Moodle. Nama tabel pendek, kompatibel dengan batas Moodle.
const STG_LB_T = 'stgtak_p05_rank';
const STG_REVIEW_T = 'stgtak_p05_rev';
const STG_ANNO_T = 'stgtak_p05_anno';
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
            error_log('STANGOLDEN TAK P05 HLM52-61 SETUP: ' . $e->getMessage());
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
    }catch(Throwable $e){error_log('STANGOLDEN TAK P05 HLM52-61: '.$e->getMessage());stgtak_json(['success'=>false,'message'=>($e instanceof RuntimeException ? $e->getMessage() : 'Penyimpanan belum tersedia. Hubungi administrator.')],500);}
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
<title>Latihan Tata Aturan Kepegawaian Paket 05 (Hlm. 52–61) • STANGOLDEN</title>
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
      <span class="series-tag">Paket 05 • Hlm. 52–61</span>
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
<h1>Latihan Nilai-Nilai Kemenkeu <span class="series-tag">Paket 05 • Hlm. 52–61</span></h1>
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
        <h2 class="leaderboard-title" id="rankingModalTitle">🏆 Lihat Ranking Tata Aturan Kepegawaian Paket 05</h2>
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
const STG_LOCAL_KEY="stg_tak_p05_review_user_<?= (int)$USER->id ?>";
const HAS_FULL_REVIEW=true;
const IS_SITE_ADMIN=<?= is_siteadmin($USER) ? "true" : "false" ?>;
const LEADERBOARD_ENDPOINT=window.location.href.split("?")[0].split("#")[0];
const questions=[{"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C2 - Pemahaman", "question": "PNS yang tidak mengutamakan kepentingan negara dan berdampak negatif pada Unit Kerja dijatuhi...", "options": ["Hukuman disiplin sedang", "Hukuman disiplin ringan", "Hukuman disiplin berat", "Pemberhentian tidak dengan hormat", "Pembebasan jabatan fungsional"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Hukuman Ringan Mengutamakan Kepentingan Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Hukuman disiplin ringan dijatuhkan bagi PNS yang tidak memenuhi ketentuan <strong>mengutamakan kepentingan negara daripada kepentingan pribadi, seseorang, dan/atau golongan apabila berdampak negatif pada Unit Kerja</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena regulasi PP Nomor 94 Tahun 2021 mengklasifikasikan pelanggaran pengutamaan kepentingan negara yang dampaknya terbatas pada lingkup Unit Kerja sebagai pelanggaran berkadar disiplin ringan. Oleh karena itu, opsi A benar.</p><p>Jawaban tepat: <strong>B. Hukuman disiplin ringan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Hukuman sedang dijatuhkan apabila pelanggaran berdampak negatif pada instansi kementerian.</li><li><strong>C</strong>: Hukuman berat dijatuhkan apabila pelanggaran berdampak negatif pada negara/pemerintah.</li><li><strong>D</strong>: Pemberhentian merupakan hukuman berat atas pelanggaran pidana atau pembangkangan berat.</li><li><strong>E</strong>: Pembebasan jabatan merupakan jenis sanksi disiplin tingkat berat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 52) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>mengutamakan kepentingan negara daripada kepentingan • Unit Kerja</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Hukuman teguran lisan dijatuhkan kepada PNS yang tidak masuk kerja tanpa alasan sah secara kumulatif selama...", "options": ["11 (sebelas) hari kerja berturut-turut", "7 (tujuh) hari kerja dalam 1 (satu) semester", "3 (tiga) hari kerja dalam 1 (satu) tahun", "15 (lima belas) hari kerja dalam sebulan", "20 (dua puluh) hari kerja kumulatif dinas"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sanksi Teguran Lisan Jam Kerja</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Hukuman disiplin ringan berupa <strong>teguran lisan bagi PNS yang tidak masuk kerja tanpa alasan yang sah secara kumulatif selama 3 (tiga) hari kerja dalam 1 (satu) tahun</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena ambang batas pertama ketidakhadiran tanpa alasan sah yang dijatuhi sanksi teguran lisan menurut PP 94/2021 adalah tepat 3 hari kerja secara kumulatif dalam 1 tahun dinas. Dengan demikian, opsi A adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>C. 3 (tiga) hari kerja dalam 1 (satu) tahun</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Ketidakhadiran 11 hari masuk kategori hukuman disiplin tingkat sedang.</li><li><strong>B</strong>: Ketidakhadiran 7 hari dijatuhi sanksi pernyataan tidak puas secara tertulis.</li><li><strong>D</strong>: Ketidakhadiran 15 hari dijatuhi pemotongan tunjangan kinerja 9 bulan.</li><li><strong>E</strong>: Ketidakhadiran 20 hari dijatuhi pemotongan tunjangan kinerja 12 bulan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 52) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>teguran lisan bagi PNS yang tidak masuk kerja • 3 (tiga) hari kerja</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C2 - Pemahaman", "question": "PNS yang tidak masuk kerja tanpa alasan sah secara kumulatif selama 4 sampai 6 hari kerja dalam setahun dijatuhi...", "options": ["Pemotongan tunjangan kinerja 25%", "Teguran lisan", "Pernyataan tidak puas tertulis", "Teguran tertulis", "Penurunan pangkat setingkat lebih rendah"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sanksi Teguran Tertulis Ketidakhadiran</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>PNS yang tidak masuk kerja tanpa alasan sah secara kumulatif selama <strong>4 sampai dengan 6 hari kerja dalam 1 tahun</strong> dijatuhi hukuman disiplin <strong>teguran tertulis</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena rentang waktu absen 4 s.d. 6 hari kerja berada pada hierarki kedua hukuman ringan, yaitu teguran tertulis dari pejabat yang berwenang menghukum. Oleh sebab itu, opsi A benar.</p><p>Jawaban tepat: <strong>D. Teguran tertulis</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pemotongan tunjangan kinerja dijatuhkan untuk ketidakhadiran mulai 11 hari kerja.</li><li><strong>B</strong>: Teguran lisan dijatuhkan bagi yang tidak masuk kerja 3 hari kerja.</li><li><strong>C</strong>: Pernyataan tidak puas tertulis untuk ketidakhadiran 7 sampai 10 hari kerja.</li><li><strong>E</strong>: Penurunan pangkat/jabatan merupakan sanksi hukuman disiplin tingkat berat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 52) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>4 (empat) sampai dengan • 6 (enam) hari kerja dalam 1 (satu) tahun</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Pernyataan tidak puas secara tertulis dijatuhkan kepada PNS yang tidak masuk kerja tanpa alasan sah selama...", "options": ["25 sampai dengan 30 hari kerja dinas", "1 sampai dengan 2 hari kerja dalam sebulan", "12 sampai dengan 15 hari kerja berturut-turut", "18 sampai dengan 20 hari kerja kalender", "7 sampai dengan 10 hari kerja dalam setahun"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sanksi Pernyataan Tidak Puas Ketidakhadiran</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Hukuman disiplin ringan berupa <strong>pernyataan tidak puas secara tertulis bagi PNS yang tidak masuk kerja tanpa alasan yang sah secara kumulatif selama 7 (tujuh) sampai dengan 10 (sepuluh) hari kerja dalam 1 (satu) tahun</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena rentang 7 hingga 10 hari kerja mangkir kerja tanpa izin sah merupakan batas maksimal kategori pelanggaran jam kerja tingkat ringan yang dijatuhi surat pernyataan tidak puas tertulis. Maka, opsi A benar.</p><p>Jawaban tepat: <strong>E. 7 sampai dengan 10 hari kerja dalam setahun</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Absen 25-30 hari dijatuhi sanksi hukuman disiplin tingkat berat.</li><li><strong>B</strong>: Absen 1-2 hari belum mencapai ambang batas sanksi disiplin resmi (minimal 3 hari).</li><li><strong>C</strong>: Absen 12-15 hari dijatuhi hukuman disiplin tingkat sedang (pemotongan tukin).</li><li><strong>D</strong>: Absen 18-20 hari dijatuhi pemotongan tukin 25% selama 12 bulan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 52) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>7 (tujuh) sampai dengan\n10 (sepuluh) hari kerja dalam 1 (satu) tahun • PNS yang tidak masuk kerja tanpa alasan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C2 - Pemahaman", "question": "PNS yang lalai menggunakan dan memelihara BMN hingga berdampak negatif pada Unit Kerja dijatuhi...", "options": ["Hukuman disiplin ringan", "Hukuman disiplin sedang", "Hukuman disiplin berat", "Pemberhentian sementara status pegawai", "Penyitaan seluruh tabungan pensiun"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kewajiban Penggunaan BMN Dampak Unit Kerja</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kelalaian dalam <strong>menggunakan dan memelihara barang milik negara dengan sebaik-baiknya apabila berdampak negatif pada Unit Kerja</strong> dijatuhi <strong>hukuman disiplin ringan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena skala dampak kelalaian pemeliharaan BMN yang terlokalisasi di Unit Kerja diatur sebagai pelanggaran ringan. Jika dampaknya meluas ke instansi, barulah masuk kategori sedang. Oleh karena itu, opsi A benar.</p><p>Jawaban tepat: <strong>A. Hukuman disiplin ringan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Hukuman sedang berlaku jika kelalaian pemeliharaan BMN berdampak pada instansi.</li><li><strong>C</strong>: Hukuman berat dikenakan bila merugikan keuangan negara dalam skala besar.</li><li><strong>D</strong>: Pemberhentian sementara ditujukan untuk proses perkara pidana penahanan.</li><li><strong>E</strong>: Penyitaan tabungan bukan bentuk sanksi disiplin aparatur sipil negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 52) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menggunakan dan memelihara barang milik negara dengan\nsebaik-baiknya • apabila pelanggaran berdampak negatif pada Unit Kerja</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C3 - Aplikasi", "question": "Seorang staf kantor pelayanan tercatat tidak hadir kerja tanpa keterangan sah selama 3 hari dalam setahun. Atasan wajib menjatuhkan...", "options": ["Pemindahan tugas ke kantor perbatasan terluar", "Pemotongan tunjangan kinerja sebesar 50 persen", "Teguran lisan secara tegas sebagai hukuman disiplin", "Penurunan jabatan menjadi pelaksana setingkat", "Pemberhentian dengan hormat atas kehendak dinas"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penerapan Sanksi Mangkir 3 Hari</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Ketidakhadiran tanpa alasan sah kumulatif 3 hari kerja dalam setahun dijatuhi hukuman <strong>teguran lisan</strong> yang dinyatakan secara tegas oleh atasan berwenang.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena berdasarkan PP 94/2021, sanksi pertama atas ketidakhadiran 3 hari kerja adalah teguran lisan. Teguran ini harus dinyatakan secara eksplisit sebagai hukuman disiplin agar sah secara yuridis kepegawaian. Dengan demikian, opsi A tepat.</p><p>Jawaban tepat: <strong>C. Teguran lisan secara tegas sebagai hukuman disiplin</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pemindahan paksa wilayah bukan sanksi atas ketidakhadiran 3 hari.</li><li><strong>B</strong>: Pemotongan tukin 50% bukan formula hukuman disiplin PP 94/2021.</li><li><strong>D</strong>: Penurunan jabatan adalah hukuman disiplin berat untuk pelanggaran akumulatif berat.</li><li><strong>E</strong>: Pemberhentian merupakan sanksi ketidakhadiran minimal 28 hari kerja.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 52) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>tidak masuk kerja tanpa alasan\nyang sah secara kumulatif selama 3 (tiga) hari kerja • teguran lisan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C3 - Aplikasi", "question": "Seorang bendahara pembantu tidak masuk dinas tanpa izin selama 5 hari kerja kumulatif dalam satu tahun. Sanksi yang tepat adalah...", "options": ["Penurunan pangkat setahun", "Teguran lisan", "Pemotongan tukin 6 bulan", "Teguran tertulis", "Pemberhentian sementara"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Implementasi Sanksi Ketidakhadiran 5 Hari Kerja</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Ketidakhadiran kumulatif <strong>4 sampai dengan 6 hari kerja dalam 1 tahun</strong> dijatuhi sanksi disiplin ringan berupa <strong>teguran tertulis</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena ketidakhadiran 5 hari kerja masuk dalam rentang 4 sampai dengan 6 hari kerja, yang secara spesifik diatur untuk dikenakan sanksi teguran tertulis. Jadi, pilihan D adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>D. Teguran tertulis</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penurunan pangkat/jabatan adalah hukuman berat untuk ketidakhadiran 21-24 hari.</li><li><strong>B</strong>: Teguran lisan hanya untuk pelanggaran ketidakhadiran 3 hari kerja.</li><li><strong>C</strong>: Pemotongan tukin dikenakan apabila ketidakhadiran mencapai minimal 11 hari kerja.</li><li><strong>E</strong>: Pemberhentian sementara bukan sanksi atas mangkir kerja ringan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 52) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>4 (empat) sampai dengan\n6 (enam) hari kerja • teguran</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C3 - Aplikasi", "question": "Pegawai pada bagian umum tidak hadir kerja tanpa alasan sah selama 9 hari kerja kumulatif. Bentuk hukuman disiplinnya adalah...", "options": ["Pembebasan tugas kedinasan kantor", "Peringatan lisan biasa tanpa berkas", "Pemotongan tunjangan kinerja 25% 9 bulan", "Penurunan kelas jabatan setingkat", "Pernyataan tidak puas secara tertulis"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Implementasi Sanksi Mangkir 9 Hari Kerja</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Ketidakhadiran kumulatif <strong>7 sampai dengan 10 hari kerja dalam 1 tahun</strong> dijatuhi <strong>pernyataan tidak puas secara tertulis</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena akumulasi mangkir 9 hari kerja berada pada interval 7-10 hari kerja, sehingga jenis hukuman yang wajib dijatuhkan adalah pernyataan tidak puas secara tertulis. Oleh sebab itu, opsi A benar.</p><p>Jawaban tepat: <strong>E. Pernyataan tidak puas secara tertulis</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pembebasan tugas bukan sanksi atas ketidakhadiran 9 hari kerja.</li><li><strong>B</strong>: Peringatan biasa bukan hukuman disiplin resmi dan tidak proporsional untuk 9 hari.</li><li><strong>C</strong>: Pemotongan tukin 9 bulan untuk ketidakhadiran 14-16 hari kerja.</li><li><strong>D</strong>: Penurunan kelas jabatan adalah hukuman berat untuk ketidakhadiran 21-24 hari.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 52) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>7 (tujuh) sampai dengan\n10 (sepuluh) hari kerja • Hukuman disiplin ringan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C4 - Analisis", "question": "Pembeda utama antara hukuman ringan dan hukuman sedang pada pelanggaran pengutamaan kepentingan negara terletak pada...", "options": ["Skala dampak negatif terhadap Unit Kerja vs Instansi", "Status pegawai apakah berstatus CPNS atau PNS", "Tingkat pendidikan formal terakhir yang dimiliki", "Besaran saldo simpanan tabungan pribadi pegawai", "Lokasi kantor apakah berada di pusat atau daerah"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pembedaan Dampak Pelanggaran Disiplin</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pelanggaran pengutamaan kepentingan negara dijatuhi <strong>hukuman ringan jika berdampak negatif pada Unit Kerja</strong>, dan <strong>hukuman sedang jika berdampak negatif pada instansi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena PP 94/2021 mengadopsi gradasi sanksi berdasarkan tingkat keparahan dampak (locus of impact): dampak pada unit kerja dijatuhi sanksi ringan, sedangkan dampak meluas pada instansi kementerian dijatuhi sanksi sedang. Maka, opsi A adalah analisis yang tepat.</p><p>Jawaban tepat: <strong>A. Skala dampak negatif terhadap Unit Kerja vs Instansi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Aturan disiplin berlaku setara bagi CPNS dan PNS tanpa diskriminasi tingkat sanksi.</li><li><strong>C</strong>: Ijazah dan tingkat pendidikan bukan faktor pembeda hierarki hukuman disiplin.</li><li><strong>D</strong>: Kondisi ekonomi pribadi tidak mempengaruhi kualifikasi tingkat hukuman disiplin.</li><li><strong>E</strong>: Letak geografis kantor tidak mengubah kualifikasi yuridis tingkat dampak pelanggaran.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 52) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Hukuman disiplin ringan dijatuhkan bagi PNS yang tidak\nmemenuhi ketentuan: • mengutamakan kepentingan negara</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C4 - Analisis", "question": "Berdasarkan PP 94/2021, batas akumulasi ketidakhadiran maksimal yang masih masuk dalam kategori hukuman disiplin ringan adalah...", "options": ["3 hari kerja dalam sebulan", "10 hari kerja dalam setahun", "14 hari kerja dalam setahun", "20 hari kerja dalam setahun", "27 hari kerja dalam setahun"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Analisis Ambang Batas Hukuman Ringan Jam Kerja</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Batas akumulasi tertinggi untuk hukuman disiplin ringan adalah <strong>10 (sepuluh) hari kerja dalam 1 (satu) tahun</strong> (sanksi pernyataan tidak puas tertulis).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena interval sanksi ringan jam kerja adalah 3 hari (lisan), 4-6 hari (tertulis), dan 7-10 hari (pernyataan tidak puas tertulis). Melewati batas 10 hari (mulai hari ke-11) otomatis masuk hukuman disiplin sedang. Oleh karena itu, opsi A benar.</p><p>Jawaban tepat: <strong>B. 10 hari kerja dalam setahun</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Tiga hari merupakan batas minimal hukuman lisan, bukan batas maksimal kategori ringan.</li><li><strong>C</strong>: Empat belas hari kerja sudah masuk kategori hukuman disiplin tingkat sedang.</li><li><strong>D</strong>: Dua puluh hari kerja adalah batas atas pemotongan tukin 12 bulan (hukuman sedang).</li><li><strong>E</strong>: Dua puluh tujuh hari kerja masuk kategori hukuman disiplin tingkat berat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 52) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>masuk kerja dan menaati ketentuan jam kerja • Hukuman disiplin ringan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Atasan yang menghalangi bawahan mengembangkan kompetensi hingga berdampak negatif pada Unit Kerja dijatuhi...", "options": ["Pemberhentian tidak dengan hormat", "Hukuman disiplin sedang", "Hukuman disiplin berat", "Penurunan jabatan pelaksana", "Hukuman disiplin ringan"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kewajiban Memberi Kesempatan Pengembangan Bawahan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>PNS yang tidak memenuhi ketentuan <strong>memberikan kesempatan kepada bawahan untuk mengembangkan kompetensi apabila berdampak negatif pada Unit Kerja dijatuhi hukuman disiplin ringan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena menghalangi pengembangan kompetensi bawahan di lingkup unit kerja dikategorikan sebagai pelanggaran pembinaan manajerial tingkat ringan. Jika dampaknya meluas ke instansi barulah menjadi sedang. Maka dari itu, opsi A adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>E. Hukuman disiplin ringan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pemberhentian tidak hormat dijatuhkan pada tindak pidana korupsi atau kejahatan jabatan.</li><li><strong>B</strong>: Hukuman sedang berlaku jika pelanggaran berdampak negatif pada instansi.</li><li><strong>C</strong>: Hukuman berat dijatuhkan untuk pelanggaran kedaulatan negara atau fraud.</li><li><strong>D</strong>: Penurunan jabatan pelaksana merupakan sanksi hukuman disiplin tingkat berat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 53) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>memberikan\nkesempatan\nkepada\nbawahan\nuntuk\nmengembangkan kompetensi • Unit Kerja</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Larangan bertindak sewenang-wenang terhadap bawahan yang berdampak negatif pada Unit Kerja diancam hukuman...", "options": ["Disiplin ringan", "Disiplin sedang", "Disiplin berat", "Pidana kurungan", "Ganti rugi BMN"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Larangan Bertindak Sewenang-wenang</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pelanggaran terhadap larangan <strong>bertindak sewenang-wenang terhadap bawahan apabila berdampak negatif pada Unit Kerja dijatuhi hukuman disiplin ringan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena perbuatan sewenang-wenang yang dampaknya terlokalisasi di lingkungan internal Unit Kerja digolongkan sebagai pelanggaran etika kepemimpinan berkadar disiplin ringan. Dengan demikian, pilihan A benar.</p><p>Jawaban tepat: <strong>A. Disiplin ringan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Hukuman sedang dijatuhkan jika dampak kesewenang-wenangan meluas pada instansi.</li><li><strong>C</strong>: Hukuman berat untuk pelanggaran berkonsekuensi hukum berat pada negara.</li><li><strong>D</strong>: Pidana kurungan merupakan ranah pengadilan umum tindak pidana.</li><li><strong>E</strong>: Ganti rugi BMN terkait dengan kerusakan fisik aset kebendaan milik negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 53) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>bertindak sewenang-wenang terhadap bawahan • apabila pelanggaran berdampak negatif pada Unit Kerja</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Pelanggaran kewajiban menjaga persatuan bangsa yang berdampak negatif pada unit kerja/instansi dijatuhi...", "options": ["Hukuman disiplin ringan", "Hukuman disiplin sedang", "Hukuman teguran lisan", "Penghargaan karya satya", "Pemberian cuti istimewa"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kewajiban Menjaga Persatuan Bangsa</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Hukuman disiplin sedang dijatuhkan bagi pelanggaran terhadap kewajiban <strong>menjaga persatuan dan kesatuan bangsa, apabila berdampak negatif pada Unit Kerja dan/atau instansi yang bersangkutan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena memecah belah kerukunan dan persatuan di lingkungan kerja instansi merupakan perbuatan yang mengganggu soliditas birokrasi, sehingga dijatuhi sanksi disiplin sedang. Oleh karena itu, opsi A benar.</p><p>Jawaban tepat: <strong>B. Hukuman disiplin sedang</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Bukan hukuman ringan karena persatuan bangsa merupakan kewajiban fundamental ASN.</li><li><strong>C</strong>: Teguran lisan terlalu ringan untuk pelanggaran isu persatuan dan kesatuan.</li><li><strong>D</strong>: Penghargaan diberikan atas prestasi, bukan atas perbuatan pelanggaran.</li><li><strong>E</strong>: Cuti istimewa bukan instrumen sanksi penegakan disiplin pegawai.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 53) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menjaga persatuan dan kesatuan bangsa • Hukuman disiplin sedang dijatuhkan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Membocorkan rahasia jabatan yang berdampak negatif pada instansi yang bersangkutan dijatuhi hukuman...", "options": ["Bebas dari sanksi", "Disiplin ringan", "Disiplin sedang", "Peringatan santai", "Kenaikan golongan"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kewajiban Menyimpan Rahasia Jabatan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pelanggaran kewajiban <strong>menyimpan rahasia jabatan dan hanya mengemukakannya sesuai ketentuan apabila berdampak negatif pada instansi dijatuhi hukuman disiplin sedang</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena kerahasiaan data kedinasan dilindungi hukum negara. Membocorkannya hingga merugikan nama baik atau operasional instansi kementerian diancam hukuman disiplin sedang (pemotongan tukin). Maka dari itu, opsi A benar.</p><p>Jawaban tepat: <strong>C. Disiplin sedang</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Tidak ada pembebasan sanksi atas pembocoran dokumen rahasia kedinasan.</li><li><strong>B</strong>: Bukan hukuman ringan karena kebocoran rahasia instansi memiliki risiko reputasi tinggi.</li><li><strong>D</strong>: Peringatan informal bukan prosedur resmi penegakan disiplin PP 94/2021.</li><li><strong>E</strong>: Sangat keliru karena pelanggaran rahasia jabatan menghalangi kenaikan pangkat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 53) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menyimpan rahasia jabatan dan hanya dapat mengemukakan • ketentuan peraturan perundangundangan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Menolak penempatan kerja di wilayah NKRI yang berdampak negatif pada instansi diancam dengan hukuman...", "options": ["Kompensasi tunjangan", "Teguran lisan", "Disiplin ringan", "Disiplin sedang", "Mutasi permintaan sendiri"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kewajiban Bersedia Ditempatkan di Seluruh NKRI</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pelanggaran kewajiban <strong>bersedia ditempatkan di seluruh wilayah NKRI apabila berdampak negatif pada instansi dijatuhi hukuman disiplin sedang</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena kesiapan mengabdi di seluruh pelosok tanah air adalah komitmen sumpah aparatur negara. Penolakan mutasi yang merugikan instansi dijatuhi hukuman disiplin tingkat sedang. Jadi, pilihan D benar.</p><p>Jawaban tepat: <strong>D. Disiplin sedang</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Menolak penempatan tidak berhak atas kompensasi finansial apa pun.</li><li><strong>B</strong>: Teguran lisan terlalu ringan untuk pembangkangan perintah penempatan wilayah.</li><li><strong>C</strong>: Bukan hukuman ringan karena komitmen wilayah berskala nasional instansi.</li><li><strong>E</strong>: Mutasi permintaan sendiri mensyaratkan persetujuan dinas, bukan kompensasi penolakan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 53) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>bersedia ditempatkan di seluruh wilayah NKRI • Hukuman disiplin sedang</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C3 - Aplikasi", "question": "Seorang staf mengunci ruang arsip sehingga pemeriksaan internal tertunda dan berdampak pada seksi kerjanya. Pegawai tersebut dijatuhi...", "options": ["Hukuman disiplin ringan karena menghalangi tugas di unit kerja", "Pemberhentian tidak dengan hormat sebagai aparatur negara", "Penurunan pangkat setingkat lebih rendah selama tiga tahun", "Pemberian tugas belajar ke luar negeri selama dua tahun", "Pembebasan dari seluruh pengawasan atasan langsung"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penerapan Larangan Menghalangi Tugas Kedinasan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Larangan <strong>menghalangi berjalannya tugas kedinasan apabila pelanggaran berdampak negatif pada Unit Kerja dijatuhi hukuman disiplin ringan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena tindakan menghalangi kelancaran tugas dinas yang dampaknya terbatas pada seksi/unit kerja setempat secara spesifik diatur sebagai pelanggaran larangan yang dijatuhi hukuman disiplin ringan. Oleh sebab itu, opsi A benar.</p><p>Jawaban tepat: <strong>A. Hukuman disiplin ringan karena menghalangi tugas di unit kerja</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Pemberhentian tidak hormat tidak proporsional untuk insiden terlokalisasi unit.</li><li><strong>C</strong>: Penurunan pangkat tiga tahun adalah aturan lama PP 53/2010 yang tidak berlaku.</li><li><strong>D</strong>: Tugas belajar adalah hak pengembangan kapasitas, bukan sanksi pelanggaran.</li><li><strong>E</strong>: Pengawasan atasan justru diperketat pascapelanggaran disiplin.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 53) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menghalangi berjalannya tugas kedinasan • apabila pelanggaran berdampak negatif pada Unit Kerja</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C3 - Aplikasi", "question": "Seorang pegawai meminjamkan laptop dinas kepada pihak swasta tanpa izin dan merugikan operasional seksi kerjanya. Pegawai dijatuhi...", "options": ["Penurunan jabatan 12 bulan", "Hukuman disiplin ringan", "Pemotongan tukin 12 bulan", "Pemberhentian tidak hormat", "Pemindahan ke luar negeri"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penerapan Larangan Meminjamkan Aset BMN Tanpa Hak</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Larangan <strong>meminjamkan atau menyewakan BMN secara tidak sah apabila berdampak negatif pada Unit Kerja dijatuhi hukuman disiplin ringan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena peminjaman laptop tanpa izin yang dampaknya terbatas pada gangguan kerja di unit kerja setempat memenuhi unsur pelanggaran larangan penggunaan aset dengan sanksi disiplin ringan. Maka dari itu, opsi A benar.</p><p>Jawaban tepat: <strong>B. Hukuman disiplin ringan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penurunan jabatan adalah hukuman berat, tidak diterapkan untuk dampak unit kerja.</li><li><strong>C</strong>: Pemotongan tukin 12 bulan berlaku bila berdampak negatif pada instansi kementerian.</li><li><strong>D</strong>: Pemberhentian tidak hormat untuk tindak kejahatan korupsi terbukti pengadilan.</li><li><strong>E</strong>: Bukan bentuk penegakan hukum disiplin aparatur.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 53) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menjual, membeli, menggadaikan, menyewakan, atau\nmeminjamkan barang • secara tidak sah</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C3 - Aplikasi", "question": "Pejabat fungsional bersikap arogan dalam pelayanan publik hingga viral dan mencoreng nama instansi kementerian. Sanksinya adalah...", "options": ["Pemberian kenaikan pangkat", "Teguran lisan non formal", "Hukuman disiplin sedang", "Bebas dari segala tuntutan", "Pemberian uang kompensasi"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Implementasi Kewajiban Integritas dan Keteladanan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pelanggaran kewajiban <strong>menunjukkan integritas dan keteladanan sikap, perilaku, ucapan apabila berdampak negatif pada instansi dijatuhi hukuman disiplin sedang</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena kegagalan menjaga keteladanan yang mencoreng citra instansi di mata publik (viral luas) memenuhi kriteria dampak negatif pada instansi, sehingga dijatuhi hukuman disiplin sedang. Dengan demikian, opsi A tepat.</p><p>Jawaban tepat: <strong>C. Hukuman disiplin sedang</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Sangat keliru karena pelanggaran integritas menghalangi hak kenaikan pangkat.</li><li><strong>B</strong>: Teguran non formal tidak memiliki dasar hukum dan tidak proporsional bagi nama instansi.</li><li><strong>D</strong>: Aparatur terikat pertanggungjawaban etika dan hukum atas sikapnya.</li><li><strong>E</strong>: Tidak ada kompensasi bagi pelaku pelanggaran disiplin kedinasan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 53) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menunjukkan integritas dan keteladanan dalam sikap, perilaku,\nucapan, dan tindakan • instansi yang\nbersangkutan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C4 - Analisis", "question": "Di antara opsi berikut, pelanggaran larangan yang dijatuhi hukuman disiplin ringan karena berdampak pada Unit Kerja adalah...", "options": ["Menyalahgunakan barang milik negara berdampak negatif bagi negara", "Menjadi anggota partai politik tanpa mengajukan surat pengunduran diri", "Membocorkan dokumen rahasia kedinasan yang berdampak pada instansi", "Melakukan kegiatan yang merugikan negara berdampak pada unit kerja", "Meminjamkan aset dinas tanpa izin atasan berdampak pada instansi"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Analisis Pelanggaran Larangan Berkadar Ringan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Menurut PP 94/2021, pelanggaran larangan melakukan kegiatan yang merugikan negara dijatuhi <strong>hukuman disiplin ringan</strong> apabila <strong>berdampak pada Unit Kerja</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena dokumen kepegawaian merinci kegiatan yang merugikan negara dengan skala dampak terlokalisir pada Unit Kerja masuk kategori hukuman disiplin ringan. Opsi B, C, D, dan E masuk kategori hukuman sedang atau berat. Oleh karena itu, opsi A benar.</p><p>Jawaban tepat: <strong>D. Melakukan kegiatan yang merugikan negara berdampak pada unit kerja</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat, penyalahgunaan barang milik negara berdampak negatif bagi negara tergolong hukuman disiplin berat.</li><li><strong>B</strong>: Kurang tepat, keterlibatan aktif dalam partai politik tanpa pengunduran diri diancam hukuman disiplin berat.</li><li><strong>C</strong>: Kurang tepat, membocorkan dokumen rahasia kedinasan berdampak instansi tergolong hukuman disiplin sedang.</li><li><strong>E</strong>: Kurang tepat, peminjaman aset dinas berdampak instansi masuk kategori hukuman disiplin sedang.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 53) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Hukuman disiplin ringan dijatuhkan bagi PNS yang melanggar\nketentuan larangan: • Unit Kerja</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C4 - Analisis", "question": "Kriteria yuridis utama yang menetapkan suatu pelanggaran kewajiban masuk kategori hukuman disiplin sedang adalah...", "options": ["Pelanggaran tidak dicatat oleh atasan langsung di buku catatan", "Pelanggaran berdampak langsung pada operasional internal unit kerja", "Pelanggaran dilakukan oleh pegawai yang belum menikah", "Pelanggaran terjadi pada hari libur nasional resmi", "Pelanggaran menimbulkan dampak negatif pada instansi bersangkutan"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Analisis Parameter Dampak Hukuman Sedang</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Hukuman disiplin sedang dijatuhkan atas pelanggaran kewajiban <strong>apabila pelanggaran berdampak negatif pada instansi yang bersangkutan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena parameter baku pembagian derajat hukuman dalam PP 94/2021 mengaitkan hukuman sedang dengan pembuktian adanya dampak negatif terhadap kelembagaan instansi. Ini menjadi landasan kepastian hukum penjatuhan sanksi. Maka, opsi A adalah analisis yang tepat.</p><p>Jawaban tepat: <strong>E. Pelanggaran menimbulkan dampak negatif pada instansi bersangkutan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pencatatan atasan adalah aspek pembuktian, bukan kriteria dampak yuridis.</li><li><strong>B</strong>: Kurang tepat, dampak operasional internal unit kerja umumnya menjadi parameter penjatuhan sanksi tingkat ringan.</li><li><strong>C</strong>: Status pernikahan tidak memiliki relevansi hukum dengan kualifikasi sanksi.</li><li><strong>D</strong>: Waktu hari libur tidak mengubah derajat dampak negatif terhadap instansi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 53) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Hukuman disiplin sedang dijatuhkan bagi pelanggaran terhadap\nkewajiban: • apabila pelanggaran berdampak negatif pada instansi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C2 - Pemahaman", "question": "PNS yang tidak menghadiri dan mengucapkan sumpah/janji PNS tanpa alasan yang sah dijatuhi...", "options": ["Pemberian izin cuti", "Teguran lisan biasa", "Peringatan santai", "Hukuman disiplin sedang", "Kenaikan kelas pangkat"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sanksi Tidak Menghadiri Sumpah PNS</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Hukuman disiplin sedang dijatuhkan bagi PNS yang tidak memenuhi ketentuan <strong>menghadiri dan mengucapkan sumpah/janji PNS, apabila pelanggaran dilakukan tanpa alasan yang sah</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena mengucapkan sumpah merupakan kewajiban konstitusional dan moral mendasar seorang aparatur. Ketidakhadiran tanpa dasar pembenar yang sah langsung diancam sanksi disiplin tingkat sedang. Dengan demikian, opsi A adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>D. Hukuman disiplin sedang</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Cuti bukan tindakan hukum disiplin atas mangkir dari upacara sumpah.</li><li><strong>B</strong>: Teguran lisan terlalu ringan dan tidak sesuai dengan ancaman pasal PP 94/2021.</li><li><strong>C</strong>: Peringatan santai tidak dikenal dalam hukum disiplin kepegawaian.</li><li><strong>E</strong>: Pelanggaran sumpah jabatan menghalangi promosi dan kenaikan pangkat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 54) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menghadiri dan mengucapkan sumpah/janji PNS • tanpa alasan yang sah</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Pejabat administrator dan pejabat fungsional yang tidak melaporkan harta kekayaan (LHKPN/LHK) dijatuhi...", "options": ["Pembebasan kewajiban perpajakan", "Hukuman disiplin ringan", "Hukuman disiplin berat", "Pemberhentian tidak dengan hormat", "Hukuman disiplin sedang"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kewajiban LHKPN Pejabat Administrator & Fungsional</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Tidak melaporkan harta kekayaan sesuai peraturan <strong>yang dilakukan pejabat administrator dan pejabat fungsional dijatuhi hukuman disiplin sedang</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena transparansi aset bagi administrator dan fungsional diatur di bawah ancaman hukuman sedang. Sedangkan untuk Pejabat Pimpinan Tinggi (JPT), pelanggaran ini diancam hukuman berat. Oleh sebab itu, opsi A benar.</p><p>Jawaban tepat: <strong>E. Hukuman disiplin sedang</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kewajiban perpajakan tetap mengikat setiap warga negara.</li><li><strong>B</strong>: Laporan harta kekayaan bukan pelanggaran ringan karena terkait integritas pencegahan korupsi.</li><li><strong>C</strong>: Hukuman berat untuk ketidakpatuhan LHKPN Pejabat Pimpinan Tinggi (JPT).</li><li><strong>D</strong>: Pemberhentian tidak hormat dijatuhkan jika terbukti tindak pidana korupsi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 54) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>melaporkan harta kekayaan kepada pejabat yang berwenang • pejabat administrator dan pejabat fungsional</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C2 - Pemahaman", "question": "PNS yang tidak masuk kerja tanpa alasan sah kumulatif 11 s.d. 13 hari kerja dalam setahun dijatuhi...", "options": ["Pemotongan tukin 25% selama 6 bulan", "Pemotongan tukin 25% selama 9 bulan", "Pemotongan tukin 25% selama 12 bulan", "Teguran tertulis dari atasan langsung", "Pernyataan tidak puas secara tertulis"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sanksi Mangkir 11-13 Hari Kerja</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Ketidakhadiran kumulatif <strong>11 sampai dengan 13 hari kerja dalam 1 tahun</strong> dijatuhi <strong>pemotongan tunjangan kinerja sebesar 25% selama 6 (enam) bulan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena rentang 11-13 hari mangkir merupakan ambang pertama kategori hukuman sedang yang dikenakan pemotongan tunjangan kinerja 25% dengan durasi 6 bulan. Maka, opsi A adalah jawaban yang tepat.</p><p>Jawaban tepat: <strong>A. Pemotongan tukin 25% selama 6 bulan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Pemotongan tukin 9 bulan untuk ketidakhadiran 14 sampai dengan 16 hari kerja.</li><li><strong>C</strong>: Pemotongan tukin 12 bulan untuk ketidakhadiran 17 sampai dengan 20 hari kerja.</li><li><strong>D</strong>: Teguran tertulis adalah sanksi ringan untuk ketidakhadiran 4-6 hari kerja.</li><li><strong>E</strong>: Pernyataan tidak puas untuk ketidakhadiran 7-10 hari kerja.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 54) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pemotongan tunjangan kinerja sebesar 25% • 11\n(sebelas) sampai dengan 13 (tiga belas) hari kerja</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Hukuman pemotongan tunjangan kinerja sebesar 25% selama 9 bulan dijatuhkan bagi PNS yang mangkir selama...", "options": ["7 sampai dengan 10 hari kerja dalam setahun", "14 sampai dengan 16 hari kerja dalam setahun", "11 sampai dengan 13 hari kerja dalam setahun", "17 sampai dengan 20 hari kerja dalam setahun", "21 sampai dengan 24 hari kerja dalam setahun"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sanksi Mangkir 14-16 Hari Kerja</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Hukuman pemotongan tunjangan kinerja 25% selama <strong>9 (sembilan) bulan</strong> dijatuhkan atas ketidakhadiran tanpa alasan sah <strong>14 sampai dengan 16 hari kerja dalam 1 tahun</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena interval 14 s.d. 16 hari mangkir kerja secara kumulatif menghasilkan sanksi pemotongan tunjangan kinerja 25% dengan masa berlaku tepat 9 bulan. Dengan demikian, opsi A adalah pilihan yang benar.</p><p>Jawaban tepat: <strong>B. 14 sampai dengan 16 hari kerja dalam setahun</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Absen 7-10 hari dijatuhi pernyataan tidak puas secara tertulis.</li><li><strong>C</strong>: Absen 11-13 hari dijatuhi pemotongan tukin 25% selama 6 bulan.</li><li><strong>D</strong>: Absen 17-20 hari dijatuhi pemotongan tukin 25% selama 12 bulan.</li><li><strong>E</strong>: Absen 21-24 hari dijatuhi penurunan jabatan setingkat 12 bulan (hukuman berat).</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 54) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>14 (empat belas) sampai dengan 16 (enam belas) hari kerja • Pemotongan tunjangan kinerja sebesar 25%</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Pemotongan tunjangan kinerja 25% selama 12 bulan dijatuhkan kepada PNS yang tidak masuk kerja tanpa izin selama...", "options": ["11 sampai dengan 13 hari kerja dalam setahun", "5 sampai dengan 8 hari kerja dalam setahun", "17 sampai dengan 20 hari kerja dalam setahun", "25 sampai dengan 27 hari kerja dalam setahun", "28 hari kerja atau lebih dalam setahun"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sanksi Mangkir 17-20 Hari Kerja</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Ketidakhadiran kumulatif <strong>17 sampai dengan 20 hari kerja dalam 1 tahun</strong> dijatuhi <strong>pemotongan tunjangan kinerja sebesar 25% selama 12 (dua belas) bulan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena rentang mangkir 17 hingga 20 hari kerja adalah batas teratas kategori hukuman sedang dengan sanksi pemotongan tunjangan kinerja 25% selama 1 tahun penuh. Maka, opsi A benar.</p><p>Jawaban tepat: <strong>C. 17 sampai dengan 20 hari kerja dalam setahun</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Absen 11-13 hari kerja dijatuhi pemotongan tukin 25% selama 6 bulan.</li><li><strong>B</strong>: Absen 5-8 hari kerja masuk kategori hukuman disiplin ringan.</li><li><strong>D</strong>: Absen 25-27 hari kerja dijatuhi pembebasan jabatan menjadi pelaksana (hukuman berat).</li><li><strong>E</strong>: Absen 28 hari kerja atau lebih dijatuhi pemberhentian sebagai PNS (hukuman terberat).</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 54) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>17 (tujuh belas) sampai dengan 20 (dua puluh) hari kerja • selama 12 (dua belas) bulan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C2 - Pemahaman", "question": "PNS yang mengetahui ancaman terhadap keuangan negara namun tidak segera lapor hingga merugikan instansi dijatuhi...", "options": ["Pemberian penghargaan resmi", "Teguran lisan dari bawahan", "Bebas dari segala sanksi", "Pemberian kenaikan tukin", "Hukuman disiplin sedang"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kewajiban Melaporkan Ancaman Keamanan/Keuangan Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Tidak melaporkan dengan segera hal yang <strong>membahayakan keamanan negara atau merugikan keuangan negara apabila berdampak negatif pada instansi dijatuhi hukuman disiplin sedang</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena pembiaran atas indikasi kerugian keuangan negara melanggar prinsip akuntabilitas pengawasan. Bila berdampak pada instansi, pelaku pembiaran dijatuhi sanksi hukuman disiplin sedang. Oleh sebab itu, opsi A benar.</p><p>Jawaban tepat: <strong>E. Hukuman disiplin sedang</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penghargaan hanya diberikan bagi pelapor yang menyelamatkan aset negara.</li><li><strong>B</strong>: Bawahan tidak memiliki kewenangan menghukum atasannya.</li><li><strong>C</strong>: Pembiaran terhadap kerugian negara merupakan pelanggaran disiplin serius.</li><li><strong>D</strong>: Sangat keliru karena pelaku pembiaran wajib disanksi pemotongan tunjangan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 54) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>melaporkan dengan segera kepada atasannya apabila\nmengetahui • membahayakan keamanan\nnegara atau merugikan keuangan negara</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C3 - Aplikasi", "question": "Seorang penata laksana keuangan tercatat tidak hadir tanpa alasan sah selama 12 hari kerja dalam setahun. Sanksinya adalah...", "options": ["Pemotongan tunjangan kinerja 25% selama 6 bulan", "Pemberhentian pembayaran gaji pokok seumur hidup", "Penurunan pangkat setingkat lebih rendah 12 bulan", "Teguran tertulis dari pejabat pembina kepegawaian", "Pemberhentian tidak dengan hormat sebagai aparatur"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Implementasi Sanksi Mangkir 12 Hari Kerja</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Ketidakhadiran kumulatif 12 hari kerja masuk rentang <strong>11 s.d. 13 hari kerja</strong>, yang dijatuhi sanksi <strong>pemotongan tunjangan kinerja 25% selama 6 bulan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena angka 12 hari secara presisi berada di dalam interval 11 sampai 13 hari kerja menurut PP 94/2021, sehingga hukuman sedang yang dijatuhkan adalah pemotongan tukin 25% selama 6 bulan. Jadi, pilihan A benar.</p><p>Jawaban tepat: <strong>A. Pemotongan tunjangan kinerja 25% selama 6 bulan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Gaji pokok tidak pernah dipotong seumur hidup dalam skema sanksi disiplin PP 94/2021.</li><li><strong>C</strong>: Penurunan jabatan dikenakan jika ketidakhadiran mencapai 21-24 hari kerja.</li><li><strong>D</strong>: Teguran tertulis merupakan sanksi ringan untuk ketidakhadiran 4-6 hari kerja.</li><li><strong>E</strong>: Pemberhentian tidak hormat tidak diterapkan pada kasus mangkir kerja 12 hari.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 54) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>11\n(sebelas) sampai dengan 13 (tiga belas) hari kerja • Pemotongan tunjangan kinerja sebesar 25% (dua puluh lima\npersen) selama 6 (enam) bulan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C3 - Aplikasi", "question": "Seorang pemeriksa pajak tidak masuk kerja tanpa kabar sah selama 15 hari kumulatif dalam satu tahun dinas. Sanksinya adalah...", "options": ["Pemotongan tunjangan kinerja 25% selama 6 bulan", "Pemotongan tunjangan kinerja 25% selama 9 bulan", "Pemotongan tunjangan kinerja 25% selama 12 bulan", "Penurunan jabatan menjadi pelaksana 12 bulan", "Pemberhentian dengan hormat tidak atas permintaan sendiri"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penerapan Sanksi Mangkir 15 Hari Kerja</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Ketidakhadiran kumulatif 15 hari kerja berada dalam interval <strong>14 s.d. 16 hari kerja</strong>, dengan sanksi <strong>pemotongan tukin 25% selama 9 bulan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena ketidakhadiran 15 hari kerja secara yuridis dikenai hukuman disiplin sedang tingkat kedua, yaitu pemotongan tukin sebesar seperempat (25%) selama 9 bulan. Dengan demikian, opsi A tepat.</p><p>Jawaban tepat: <strong>B. Pemotongan tunjangan kinerja 25% selama 9 bulan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pemotongan 6 bulan untuk ketidakhadiran 11 sampai 13 hari kerja.</li><li><strong>C</strong>: Pemotongan 12 bulan untuk ketidakhadiran 17 sampai 20 hari kerja.</li><li><strong>D</strong>: Penurunan menjadi pelaksana dikenakan jika ketidakhadiran mencapai 25-27 hari kerja.</li><li><strong>E</strong>: Pemberhentian dikenakan jika ketidakhadiran mencapai 28 hari kerja atau lebih.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 54) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>belas) hari kerja • 9 (sembilan) bulan • 14 (empat belas) sampai dengan 16 (enam belas) hari kerja</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C3 - Aplikasi", "question": "Staf kepegawaian tidak masuk kantor tanpa izin sah selama 18 hari kumulatif. Berdasarkan PP 94/2021, sanksi yang dijatuhkan adalah...", "options": ["Pemotongan tukin 25% selama 9 bulan", "Pemotongan tukin 25% selama 6 bulan", "Pemotongan tukin 25% selama 12 bulan", "Penurunan jabatan setingkat lebih rendah", "Teguran lisan dari atasan langsung"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penerapan Sanksi Mangkir 18 Hari Kerja</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Ketidakhadiran 18 hari kerja berada pada rentang <strong>17 s.d. 20 hari kerja</strong>, yang diancam <strong>pemotongan tunjangan kinerja 25% selama 12 bulan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena angka 18 hari mangkir kerja masuk dalam klausul sanksi sedang terberat, yaitu pemotongan tukin 25% selama 12 bulan kalender. Maka dari itu, opsi A benar.</p><p>Jawaban tepat: <strong>C. Pemotongan tukin 25% selama 12 bulan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Durasi 9 bulan diperuntukkan bagi mangkir kerja 14-16 hari.</li><li><strong>B</strong>: Durasi 6 bulan diperuntukkan bagi mangkir kerja 11-13 hari.</li><li><strong>D</strong>: Penurunan jabatan baru dijatuhkan apabila ketidakhadiran mencapai 21-24 hari kerja.</li><li><strong>E</strong>: Teguran lisan hanya untuk mangkir kerja 3 hari.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 54) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>belas) hari kerja • 17 (tujuh belas) sampai dengan 20 (dua puluh) hari kerja • 12 (dua belas) bulan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C4 - Analisis", "question": "Analisis yang tepat mengenai pola hukuman disiplin sedang atas pelanggaran jam kerja menurut PP 94/2021 adalah...", "options": ["Gaji pokok dipotong bertahap sebesar seperempat setiap tiga bulan", "Persentase potongan tukin naik berjenjang dari 25%, 50%, hingga 75%", "Durasi potongan tukin tetap 6 bulan, namun persentase potongan naik", "Persentase potongan tukin tetap 25%, durasi berjenjang 6, 9, 12 bulan", "Hukuman sedang jam kerja mengharuskan kerja lembur tanpa bayaran"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Analisis Pola Durasi Hukuman Sedang Jam Kerja</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Hukuman disiplin sedang jam kerja menerapkan <strong>persentase tunggal pemotongan tunjangan kinerja sebesar 25% dengan durasi bertingkat: 6 bulan, 9 bulan, atau 12 bulan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena dalam konstruksi PP 94/2021, pembobotan tingkat keparahan pelanggaran sedang tidak membedakan persentase (selalu 25%), melainkan membedakan lama masa hukumannya (6 bulan untuk 11-13 hari, 9 bulan untuk 14-16 hari, dan 12 bulan untuk 17-20 hari). Oleh karena itu, opsi A adalah analisis yang tepat.</p><p>Jawaban tepat: <strong>D. Persentase potongan tukin tetap 25%, durasi berjenjang 6, 9, 12 bulan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Gaji pokok tidak dipotong dalam kategori sanksi hukuman disiplin sedang.</li><li><strong>B</strong>: Persentase potongan tidak pernah 50% atau 75% dalam ketentuan sanksi PP 94/2021.</li><li><strong>C</strong>: Durasinya yang berjenjang (6, 9, 12 bulan), bukan persentasenya.</li><li><strong>E</strong>: Sanksi kerja lembur paksa tidak dikenal dalam sistem disiplin aparatur sipil negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 54) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>masuk kerja dan menaati ketentuan jam kerja berupa: • Pemotongan tunjangan kinerja sebesar 25%</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C2 - Pemahaman", "question": "PNS yang melakukan pungutan di luar ketentuan (pungli) berdampak negatif pada unit kerja/instansi dijatuhi...", "options": ["Peringatan ringan tanpa berkas", "Teguran lisan santai", "Hukuman disiplin sedang", "Pemberian dispensasi tugas", "Kenaikan peringkat jabatan"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Larangan Pungutan Liar (Pungli)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pelanggaran terhadap larangan <strong>melakukan pungutan di luar ketentuan, apabila berdampak negatif pada Unit Kerja dan/atau instansi yang bersangkutan dijatuhi hukuman disiplin sedang</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena pungutan liar merupakan pelanggaran integritas serius yang merusak kepercayaan masyarakat terhadap birokrasi, sehingga dijatuhi sanksi disiplin sedang berupa pemotongan tunjangan kinerja. Dengan demikian, opsi A adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>C. Hukuman disiplin sedang</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pungli tidak boleh diselesaikan dengan peringatan ringan tanpa pencatatan.</li><li><strong>B</strong>: Teguran santai bukan tindakan formal penegakan hukum integritas.</li><li><strong>D</strong>: Pelaku pungli dilarang diberi dispensasi kedinasan.</li><li><strong>E</strong>: Sangat keliru karena pelaku pungli harus dihukum dan dibatalkan promosinya.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 55) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>melakukan pungutan di luar ketentuan • apabila pelanggaran\nberdampak negatif pada Unit Kerja dan/atau instansi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C2 - Pemahaman", "question": "PNS yang menjadi peserta kampanye pemilu dengan mengenakan atribut partai politik atau atribut PNS dijatuhi...", "options": ["Pujian atas partisipasi", "Hukuman disiplin ringan", "Teguran lisan atasan", "Hukuman disiplin sedang", "Pemberian dana operasional"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Larangan Netralitas Kampanye Pemilu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Memberikan dukungan kepada calon Presiden/Kepala Daerah/DPR dengan cara <strong>menjadi peserta kampanye dengan menggunakan atribut partai atau atribut PNS dijatuhi hukuman disiplin sedang</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena penggunaan atribut dinas atau partai dalam kampanye secara terang-terangan melanggar asas netralitas aparatur sipil negara, yang diancam dengan hukuman disiplin tingkat sedang. Maka dari itu, opsi A benar.</p><p>Jawaban tepat: <strong>D. Hukuman disiplin sedang</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Aparatur sipil negara dilarang menunjukkan keberpihakan politik terbuka.</li><li><strong>B</strong>: Pelanggaran netralitas politik kampanye tidak digolongkan sebagai sanksi ringan.</li><li><strong>C</strong>: Teguran lisan tidak memadai untuk penindakan pelanggaran netralitas pemilu.</li><li><strong>E</strong>: Penggunaan anggaran negara untuk kampanye parpol adalah tindak pidana korupsi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 55) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>peserta kampanye dengan menggunakan atribut\npartai atau atribut PNS • memberikan dukungan kepada calon</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Pelanggaran kewajiban setia dan taat sepenuhnya kepada Pancasila dan UUD 1945 diancam dengan jenis...", "options": ["Pernyataan tidak puas tertulis", "Hukuman disiplin sedang", "Hukuman disiplin ringan", "Teguran tertulis dinas", "Hukuman disiplin berat"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kewajiban Setia kepada Pancasila & UUD 1945</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Hukuman disiplin berat dijatuhkan bagi pelanggaran terhadap kewajiban <strong>setia dan taat sepenuhnya kepada Pancasila, UUD 1945, NKRI, dan Pemerintah</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena kesetiaan kepada ideologi negara dan konstitusi adalah prasyarat mutlak eksistensi aparatur negara. Pengkhianatan atau pembangkangan terhadap pilar negara langsung dijatuhi hukuman disiplin tingkat berat. Jadi, pilihan E benar.</p><p>Jawaban tepat: <strong>E. Hukuman disiplin berat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pernyataan tidak puas tertulis merupakan bagian dari hukuman ringan.</li><li><strong>B</strong>: Bukan hukuman sedang karena menyangkut kedaulatan dan keselamatan ideologi negara.</li><li><strong>C</strong>: Sangat keliru bila pelanggaran ideologi Pancasila hanya dihukum ringan.</li><li><strong>D</strong>: Teguran tertulis merupakan bagian dari hukuman ringan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 55) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>setia dan taat sepenuhnya kepada Pancasila, UUD Negara • Hukuman disiplin berat</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Tindakan yang merugikan pihak yang dilayani dan berdampak negatif pada instansi diancam hukuman...", "options": ["Disiplin sedang", "Disiplin ringan", "Bebas tindakan", "Kompensasi cuti", "Kenaikan tunjangan"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Larangan Merugikan Pihak yang Dilayani</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Melakukan tindakan atau tidak melakukan tindakan yang <strong>dapat mengakibatkan kerugian bagi yang dilayani apabila berdampak negatif pada instansi dijatuhi hukuman disiplin sedang</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena kelalaian dalam memberikan pelayanan prima yang menimbulkan kerugian pemangku kepentingan dan merusak reputasi instansi dikenakan sanksi disiplin sedang. Oleh sebab itu, opsi A benar.</p><p>Jawaban tepat: <strong>A. Disiplin sedang</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Bukan hukuman ringan karena dampaknya sudah merugikan pihak eksternal dan instansi.</li><li><strong>C</strong>: Setiap perbuatan merugikan pengguna layanan wajib diproses secara disiplin.</li><li><strong>D</strong>: Cuti bukan instrumen penyelesaian pelanggaran pelayanan publik.</li><li><strong>E</strong>: Tunjangan tidak dinaikkan bagi pegawai yang merugikan pengguna layanan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 55) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>melakukan tindakan atau tidak melakukan tindakan yang dapat\nmengakibatkan\nkerugian\nbagi\nyang\ndilayani • apabila pelanggaran berdampak negatif pada instansi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C3 - Aplikasi", "question": "Petugas loket meminta biaya percepatan dokumen Rp100.000 kepada wajib pajak tanpa tanda terima sah. Pegawai tersebut dijatuhi...", "options": ["Pemberian bonus kinerja atas inisiatif penerimaan bukan pajak", "Hukuman disiplin atas pelanggaran larangan pungutan di luar ketentuan", "Penghargaan karya satya pengabdian masyarakat daerah", "Pemberian dispensasi bebas jam kerja selama dua semester", "Pengangkatan menjadi widyaiswara luar biasa kementerian"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Implementasi Larangan Menarik Uang Pelayanan (Pungli)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Menarik uang tanpa dasar hukum sah di luar tarif resmi merupakan pelanggaran atas <strong>larangan melakukan pungutan di luar ketentuan</strong> yang diancam hukuman disiplin.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena penarikan biaya percepatan tidak resmi adalah wujud nyata pungutan liar (pungli) yang dilarang keras dan wajib dijatuhi hukuman disiplin kepegawaian. Dengan demikian, opsi A tepat.</p><p>Jawaban tepat: <strong>B. Hukuman disiplin atas pelanggaran larangan pungutan di luar ketentuan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pungutan ilegal bukan PNBP sah dan tidak berhak mendapatkan bonus.</li><li><strong>C</strong>: Penghargaan hanya diberikan atas jasa pengabdian berintegritas.</li><li><strong>D</strong>: Pelaku pungli tidak diberi dispensasi bebas kerja melainkan pemeriksaan BAP.</li><li><strong>E</strong>: Widyaiswara mensyaratkan keteladanan moral dan sertifikasi kompetensi mengajar.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 55) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>melakukan pungutan di luar ketentuan • Unit Kerja dan/atau instansi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C3 - Aplikasi", "question": "Seorang staf kantor bea cukai menghadiri kampanye terbuka pemilihan bupati mengenakan seragam dinas kementerian. Sanksinya adalah...", "options": ["Pemberian sanksi administratif berupa teguran lisan oleh pejabat pembina", "Pemberian pembinaan mental oleh unit kepatuhan internal tanpa sanksi", "Penundaan pengusulan kenaikan pangkat reguler selama satu periode", "Penjatuhan hukuman disiplin sedang akibat ketidaknetralan dalam pemilu", "Pemindahan tugas sementara ke bagian pelayanan administrasi persuratan"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penerapan Netralitas Pemilu Berseragam Dinas</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Menghadiri kampanye pemilu dengan atribut dinas atau partai politik merupakan pelanggaran netralitas yang diancam <strong>hukuman disiplin sedang</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat. Aparatur sipil negara wajib menjunjung tinggi netralitas politik dalam setiap tahapan pemilu. Berdasarkan ketentuan PP 94/2021, tindakan menghadiri kampanye terbuka mengenakan seragam dinas merupakan pelanggaran netralitas yang dijatuhi hukuman disiplin tingkat sedang. Dengan demikian, penjatuhan hukuman disiplin sedang adalah konsekuensi yuridis yang sah.</p><p>Jawaban tepat: <strong>D. Penjatuhan hukuman disiplin sedang akibat ketidaknetralan dalam pemilu</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat, teguran lisan merupakan jenis hukuman disiplin ringan yang tidak sebanding dengan pelanggaran netralitas politik.</li><li><strong>B</strong>: Kurang tepat, pelanggaran netralitas politik berpakaian dinas wajib diproses melalui sidang disiplin formal.</li><li><strong>C</strong>: Kurang tepat, penundaan pangkat bukan merupakan bentuk tunggal hukuman disiplin sedang dalam PP 94/2021.</li><li><strong>E</strong>: Kurang tepat, pemindahan tugas sementara bukan sanksi definitif penjatuhan disiplin tingkat sedang.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 55) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menjadi peserta kampanye dengan menggunakan atribut\npartai • memberikan dukungan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C3 - Aplikasi", "question": "Pejabat pengadaan membocorkan harga perkiraan sendiri (HPS) kepada rekanan tender hingga merugikan nama instansi. Pegawai dijatuhi...", "options": ["Pembebasan audit BPK", "Teguran lisan ringan", "Peringatan santai", "Pemberian piagam jasa", "Hukuman disiplin sedang"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penerapan Larangan Kegiatan Merugikan Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Melakukan kegiatan yang <strong>merugikan negara, apabila pelanggaran berdampak negatif pada instansi yang bersangkutan dijatuhi hukuman disiplin sedang</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena pembocoran dokumen rahasia lelang yang merugikan proses pengadaan dan citra instansi memenuhi unsur perbuatan merugikan negara berdampak instansi, yang diancam hukuman sedang. Jadi, pilihan E benar.</p><p>Jawaban tepat: <strong>E. Hukuman disiplin sedang</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Audit BPK tetap berjalan mutlak tanpa pengecualian.</li><li><strong>B</strong>: Teguran lisan tidak sebanding dengan pembocoran dokumen rahasia tender.</li><li><strong>C</strong>: Bukan penegakan hukum disiplin aparatur sipil negara.</li><li><strong>D</strong>: Piagam penghargaan dilarang diberikan kepada pembocor dokumen lelang.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 55) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>melakukan\nkegiatan\nyang\nmerugikan\nnegara,\napabila\npelanggaran\nberdampak\nnegatif\npada\ninstansi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C4 - Analisis", "question": "Perbedaan derajat hukuman antara pelanggaran pungutan di luar ketentuan berdampak instansi dengan ketidakhadiran 3 hari kerja terletak pada...", "options": ["Pungutan dijatuhi hukuman sedang, mangkir 3 hari dijatuhi sanksi ringan", "Pungutan dijatuhi hukuman ringan, mangkir 3 hari dijatuhi sanksi berat", "Keduanya sama-sama dikenakan penjatuhan sanksi teguran lisan pembinaan", "Pungutan dijatuhi sanksi teguran, mangkir 3 hari dijatuhi sanksi sedang", "Keduanya sama-sama dikenakan penjatuhan sanksi penurunan jabatan dinas"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pembedaan Sanksi Pungli dan Jam Kerja Ringan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Melakukan <strong>pungutan di luar ketentuan berdampak pada instansi</strong> dijatuhi <strong>hukuman disiplin sedang</strong>, sedangkan <strong>tidak masuk kerja 3 hari kerja</strong> dijatuhi <strong>hukuman disiplin ringan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat. Berdasarkan PP 94/2021, melakukan pungutan di luar ketentuan yang menimbulkan dampak negatif pada instansi diklasifikasikan sebagai pelanggaran berbobot sedang. Sementara itu, ketidakhadiran tanpa keterangan sah selama 3 hari kerja secara kumulatif masuk dalam ranah hukuman disiplin ringan berupa teguran lisan. Oleh sebab itu, pembedaan derajat hukuman tersebut tepat.</p><p>Jawaban tepat: <strong>A. Pungutan dijatuhi hukuman sedang, mangkir 3 hari dijatuhi sanksi ringan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kurang tepat, posisi tingkat hukuman terbalik antara pungutan tidak sah dan ketidakhadiran tiga hari.</li><li><strong>C</strong>: Kurang tepat, pungutan di luar ketentuan berdampak instansi melampaui batas sanksi teguran lisan.</li><li><strong>D</strong>: Kurang tepat, ketidakhadiran 3 hari kerja belum memenuhi syarat ambang batas penjatuhan sanksi sedang.</li><li><strong>E</strong>: Kurang tepat, penurunan jabatan merupakan sanksi tingkat berat yang tidak diterapkan pada kedua pelanggaran ini.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 55) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Hukuman disiplin sedang dijatuhkan bagi pelanggaran terhadap\nlarangan: • melakukan pungutan di luar ketentuan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C4 - Analisis", "question": "Unsur esensial yang menyebabkan seorang PNS dijatuhi hukuman disiplin sedang dalam kegiatan kampanye pemilu adalah...", "options": ["Menggunakan hak pilih rahasia di dalam bilik suara pemilu", "Menjadi peserta kampanye dengan memakai atribut partai atau PNS", "Menonton siaran debat calon presiden melalui televisi di rumah", "Membaca berita visi misi pasangan calon melalui media massa", "Mendaftarkan diri dalam daftar pemilih tetap di tempat tinggal"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Analisis Pelanggaran Netralitas Pemilu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pelanggaran netralitas kampanye terjadi apabila PNS <strong>memberikan dukungan dengan cara menjadi peserta kampanye dengan menggunakan atribut partai atau atribut PNS</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena penggunaan atribut simbolik dinas atau parpol dalam kampanye terbuka menunjukkan afiliasi partisan publik yang merusak netralitas birokrasi. Menggunakan hak pilih secara rahasia justru dilindungi konstitusi. Dengan demikian, opsi A benar.</p><p>Jawaban tepat: <strong>B. Menjadi peserta kampanye dengan memakai atribut partai atau PNS</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Menggunakan hak suara di TPS adalah hak konstitusional sah setiap WNI.</li><li><strong>C</strong>: Menonton debat di rumah adalah hak informasi warga negara yang sah.</li><li><strong>D</strong>: Mempelajari visi misi kandidat bukan pelanggaran netralitas pemilu.</li><li><strong>E</strong>: Terdaftar dalam DPT merupakan hak elektoral sah warga negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 55) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>peserta kampanye dengan menggunakan atribut\npartai atau atribut PNS • calon Presiden/Wakil Presiden</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C4 - Analisis", "question": "Alasan yuridis mengapa pelanggaran kesetiaan terhadap Pancasila dan NKRI langsung diancam hukuman disiplin berat adalah...", "options": ["Untuk mempercepat proses alih status menjadi pegawai pemerintah PPPK", "Pelanggaran tersebut berpotensi melemahkan persatuan dan stabilitas negara", "Pancasila dan NKRI merupakan konsensus dasar eksistensi negara", "Karena sanksi hukuman ringan dan sedang sudah ditiadakan di Kemenkeu", "Penegakan disiplin nasional mengutamakan pembinaan ideologi terpadu"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Analisis Hierarki Sanksi terhadap Kesetiaan Ideologi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kesetiaan pada <strong>Pancasila, UUD 1945, NKRI, dan Pemerintah</strong> merupakan fondasi eksistensi ASN selaku perekat dan pemersatu bangsa.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena aparatur negara adalah pilar penjaga kedaulatan bangsa. Setiap bentuk ketidaksetiaan atau makar terhadap konsensus dasar negara mengancam kelangsungan republik, sehingga secara mutlak dijatuhi hukuman berat hingga pemecatan. Maka, opsi A adalah analisis yang tepat.</p><p>Jawaban tepat: <strong>C. Pancasila dan NKRI merupakan konsensus dasar eksistensi negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pelaku pembangkangan ideologi dipecat, bukan dialihkan menjadi PPPK.</li><li><strong>B</strong>: Kurang tepat, stabilitas negara memang penting namun alasan yuridis formal utamanya adalah kedudukan Pancasila dan NKRI sebagai konsensus fundamental.</li><li><strong>D</strong>: Sanksi ringan dan sedang tetap berlaku penuh untuk pelanggaran relevan lainnya.</li><li><strong>E</strong>: Kurang tepat, pembinaan ideologi terpadu merupakan upaya preventif dan bukan dasar yuridis penetapan ancaman sanksi berat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 55) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Hukuman disiplin berat • setia dan taat sepenuhnya</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Pelanggaran kewajiban kedinasan dijatuhi hukuman disiplin tingkat berat apabila pelanggaran menimbulkan dampak negatif pada...", "options": ["Satu rekan kerja satu ruangan", "Negara dan/atau pemerintah", "Pengurus kantin kantor pelayanan", "Petugas kebersihan taman kantor", "Toko penyedia perlengkapan alat tulis"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Parameter Dampak Pelanggaran Disiplin Berat</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pelanggaran terhadap kewajiban kedinasan dijatuhi hukuman disiplin berat <strong>apabila pelanggaran berdampak negatif pada negara</strong> (dan/atau pemerintah).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena puncak hierarki keparahan dampak (locus of impact) dalam regulasi PP 94/2021 adalah dampak negatif terhadap negara atau pemerintah Republik Indonesia, yang mewajibkan penjatuhan hukuman disiplin tingkat berat. Oleh sebab itu, opsi A benar.</p><p>Jawaban tepat: <strong>B. Negara dan/atau pemerintah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Dampak antarpribadi rekan kerja diselesaikan melalui pembinaan internal.</li><li><strong>C</strong>: Kantin kantor bukan entitas penentu skala dampak yuridis negara.</li><li><strong>D</strong>: Petugas kebersihan bukan tolak ukur dampak hukum ketatanegaraan.</li><li><strong>E</strong>: Hubungan perdata penyedia barang tidak menentukan dampak yuridis negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 56) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>apabila pelanggaran berdampak negatif pada negara • Hukuman disiplin berat dijatuhkan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Pejabat pimpinan tinggi yang tidak melaporkan harta kekayaan kepada pejabat yang berwenang dijatuhi...", "options": ["Teguran lisan dari atasan", "Hukuman disiplin ringan", "Hukuman disiplin berat", "Pemberian dispensasi khusus", "Kenaikan tunjangan jabatan"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kewajiban LHKPN Pejabat Pimpinan Tinggi (JPT)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>PNS yang tidak memenuhi ketentuan <strong>melaporkan harta kekayaan kepada pejabat yang berwenang, yang dilakukan pejabat pimpinan tinggi dan pejabat lainnya dijatuhi hukuman disiplin berat</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena pejabat pimpinan tinggi mengemban mandat kepemimpinan strategis dan teladan integritas, sehingga ketidakpatuhan dalam pelaporan LHKPN langsung diancam sanksi hukuman disiplin berat. Dengan demikian, opsi A adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>C. Hukuman disiplin berat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Teguran lisan hanya untuk pelanggaran ringan staf biasa.</li><li><strong>B</strong>: Bukan sanksi ringan karena kewajiban LHKPN JPT berkorelasi dengan akuntabilitas negara.</li><li><strong>D</strong>: Tidak ada dispensasi bagi pejabat publik yang menolak transparansi aset.</li><li><strong>E</strong>: Pelanggaran LHKPN justru menghalangi penerimaan hak tunjangan jabatan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 56) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>melaporkan harta kekayaan kepada pejabat yang berwenang • pejabat pimpinan tinggi dan pejabat lainnya</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Hukuman penurunan jabatan setingkat lebih rendah selama 12 bulan dijatuhkan atas ketidakhadiran tanpa izin sah selama...", "options": ["17 sampai dengan 20 hari kerja dalam setahun", "11 sampai dengan 13 hari kerja dalam setahun", "14 sampai dengan 16 hari kerja dalam setahun", "21 sampai dengan 24 hari kerja dalam setahun", "28 hari kerja atau lebih dalam setahun"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sanksi Mangkir 21-24 Hari Kerja</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Hukuman disiplin berat berupa <strong>penurunan jabatan setingkat lebih rendah selama 12 bulan bagi PNS yang tidak masuk kerja tanpa alasan sah secara kumulatif selama 21 sampai dengan 24 hari kerja dalam 1 tahun</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena interval ketidakhadiran 21 s.d. 24 hari kerja merupakan anak tangga pertama dalam rumpun hukuman disiplin berat dengan bentuk penurunan jabatan satu level selama setahun. Maka dari itu, opsi A benar.</p><p>Jawaban tepat: <strong>D. 21 sampai dengan 24 hari kerja dalam setahun</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Absen 17-20 hari dijatuhi pemotongan tukin 25% selama 12 bulan (hukuman sedang).</li><li><strong>B</strong>: Absen 11-13 hari dijatuhi pemotongan tukin 25% selama 6 bulan (hukuman sedang).</li><li><strong>C</strong>: Absen 14-16 hari dijatuhi pemotongan tukin 25% selama 9 bulan (hukuman sedang).</li><li><strong>E</strong>: Absen 28 hari kerja atau lebih dijatuhi pemberhentian sebagai PNS (hukuman terberat).</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 56) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>penurunan jabatan setingkat lebih rendah selama 12 (dua\nbelas) bulan • 21 (dua puluh satu)\nsampai dengan 24 (dua puluh empat) hari kerja</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Tidak mengutamakan kepentingan negara yang menimbulkan dampak negatif pada negara dan/atau pemerintah diancam...", "options": ["Pemberian piagam penghargaan", "Hukuman disiplin ringan", "Teguran lisan santai", "Pemberian promosi jabatan", "Hukuman disiplin berat"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kewajiban Pengutamaan Kepentingan Negara Skala Nasional</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Tidak mengutamakan kepentingan negara <strong>apabila pelanggaran berdampak negatif pada negara dan/atau pemerintah dijatuhi hukuman disiplin berat</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena pengutamaan kepentingan pribadi yang menimbulkan kerugian bagi negara atau pemerintah RI masuk kategori pelanggaran terberat dalam kelompok kewajiban aparatur. Oleh karenanya, opsi A adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>E. Hukuman disiplin berat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Piagam penghargaan dilarang diberikan kepada pelaku pelanggaran negara.</li><li><strong>B</strong>: Hukuman ringan hanya berlaku bila dampaknya sebatas pada Unit Kerja.</li><li><strong>C</strong>: Teguran santai tidak dikenal dalam prosedur formal hukum disiplin ASN.</li><li><strong>D</strong>: Pelanggaran kepentingan negara dilarang keras dipromosikan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 56) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>mengutamakan kepentingan negara daripada kepentingan\npribadi • apabila pelanggaran\nberdampak negatif pada negara dan/atau pemerintah</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C2 - Pemahaman", "question": "PNS yang membiarkan hal yang merugikan keuangan negara hingga berdampak negatif pada negara diancam sanksi...", "options": ["Hukuman disiplin berat", "Hukuman disiplin sedang", "Hukuman disiplin ringan", "Teguran lisan atasan", "Pemberian tugas belajar"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pembiaran Kerugian Keuangan Negara Skala Nasional</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Tidak segera melaporkan hal yang merugikan keuangan negara <strong>apabila berdampak negatif pada negara dan/atau pemerintah dijatuhi hukuman disiplin berat</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena pembiaran yang berakibat pada kerugian keuangan negara berskala makro mengancam kestabilan fiskal dan perekonomian nasional, sehingga dijatuhi sanksi hukuman disiplin tingkat berat. Jadi, pilihan A benar.</p><p>Jawaban tepat: <strong>A. Hukuman disiplin berat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Hukuman sedang berlaku jika dampak pembiaran hanya terbatas pada instansi kementerian.</li><li><strong>C</strong>: Hukuman ringan tidak berlaku untuk ancaman kerugian keuangan negara skala nasional.</li><li><strong>D</strong>: Teguran lisan tidak proporsional untuk ancaman kerugian keuangan negara.</li><li><strong>E</strong>: Tugas belajar bukan sanksi penegakan hukum aparatur.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 56) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>melaporkan dengan segera kepada atasannya • apabila pelanggaran\nberdampak negatif pada negara dan/atau pemerintah</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C3 - Aplikasi", "question": "Seorang kepala seksi (pejabat pengawas) tidak masuk dinas tanpa izin selama 22 hari kumulatif dalam setahun. Sanksinya adalah...", "options": ["Teguran tertulis dari pejabat pembina kepegawaian", "Pemotongan tunjangan kinerja sebesar 25% selama 6 bulan", "Penurunan jabatan setingkat lebih rendah selama 12 bulan", "Pemberhentian tidak dengan hormat seketika dari dinas", "Pemindahan tugas ke luar negeri dengan promosi"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Implementasi Sanksi Mangkir 22 Hari Kerja</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Ketidakhadiran kumulatif 22 hari kerja berada pada rentang <strong>21 s.d. 24 hari kerja</strong>, yang dikenai sanksi <strong>penurunan jabatan setingkat lebih rendah selama 12 bulan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena 22 hari mangkir kerja secara presisi memenuhi kriteria sanksi disiplin berat tingkat pertama, di mana pejabat pengawas tersebut diturunkan jabatannya setingkat lebih rendah (menjadi pelaksana) selama 12 bulan. Dengan demikian, opsi A tepat.</p><p>Jawaban tepat: <strong>C. Penurunan jabatan setingkat lebih rendah selama 12 bulan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Teguran tertulis untuk ketidakhadiran 4-6 hari kerja.</li><li><strong>B</strong>: Pemotongan tukin 6 bulan hanya untuk ketidakhadiran 11-13 hari kerja.</li><li><strong>D</strong>: Pemberhentian tidak hormat bukan sanksi atas ketidakhadiran 22 hari kerja.</li><li><strong>E</strong>: Sangat keliru karena pejabat yang melanggar disiplin berat tidak boleh dipromosikan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 56) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>(dua puluh • 21 (dua puluh satu)\nsampai dengan 24 (dua puluh empat) hari kerja • penurunan jabatan setingkat lebih rendah</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C3 - Aplikasi", "question": "Seorang Pejabat Pimpinan Tinggi Madya menolak menyerahkan LHKPN periodik tanpa alasan sah. Sanksi yang tepat adalah...", "options": ["Pemberian tambahan fasilitas pengawalan dinas", "Teguran lisan biasa dari staf administrasi umum", "Pembebasan dari kewajiban transparansi publik", "Hukuman disiplin berat bagi pejabat pimpinan tinggi", "Pemberian penghargaan tanda kehormatan negara"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Implementasi Kewajiban LHKPN Direktur Jenderal</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Ketidakpatuhan pelaporan LHKPN oleh <strong>pejabat pimpinan tinggi dan pejabat lainnya dijatuhi hukuman disiplin berat</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena regulasi PP 94/2021 secara tegas menetapkan bahwa ketidakpatuhan LHKPN oleh pejabat pimpinan tinggi (Eselon I dan II) langsung diganjar hukuman disiplin berat karena perannya sebagai panutan integritas. Maka dari itu, opsi A benar.</p><p>Jawaban tepat: <strong>D. Hukuman disiplin berat bagi pejabat pimpinan tinggi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pelanggar etika dan disiplin tidak diberi tambahan fasilitas negara.</li><li><strong>B</strong>: Staf administrasi tidak berwenang menegur Direktur Jenderal.</li><li><strong>C</strong>: Pejabat publik wajib patuh pada asas transparansi dan keterbukaan aset.</li><li><strong>E</strong>: Tanda kehormatan dilarang diberikan kepada pejabat yang menolak transparansi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 56) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pejabat pimpinan tinggi dan pejabat lainnya • melaporkan harta kekayaan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C3 - Aplikasi", "question": "Seorang analis membeberkan dokumen rahasia rencana kebijakan nilai tukar rupiah ke publik dan mengguncang stabilitas pasar. Sanksinya...", "options": ["Pemberian izin cuti di luar tanggungan negara", "Hukuman disiplin ringan karena bersumber dari kelalaian", "Teguran lisan atasan tanpa berita acara pemeriksaan", "Pemberian perlindungan kekebalan hukum diplomatik", "Hukuman disiplin berat karena berdampak pada negara"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penerapan Pembocoran Rahasia Dampak Nasional</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pelanggaran kewajiban menyimpan rahasia jabatan <strong>apabila berdampak negatif pada negara dijatuhi hukuman disiplin berat</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena pembocoran rahasia kebijakan yang menimbulkan keguncangan pasar keuangan berdampak langsung pada stabilitas negara, sehingga memenuhi syarat mutlak penjatuhan sanksi disiplin tingkat berat. Oleh sebab itu, opsi A benar.</p><p>Jawaban tepat: <strong>E. Hukuman disiplin berat karena berdampak pada negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Cuti tidak dapat dijadikan pelarian dari penegakan hukum disiplin.</li><li><strong>B</strong>: Bukan hukuman ringan karena dampaknya merusak perekonomian negara.</li><li><strong>C</strong>: Kasus kebocoran rahasia negara wajib melalui pemeriksaan BAP resmi.</li><li><strong>D</strong>: PNS kementerian tidak memiliki kekebalan hukum pidana atau disiplin.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 56) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menyimpan rahasia jabatan • apabila pelanggaran berdampak negatif pada negara</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C4 - Analisis", "question": "Perbedaan tingkat sanksi ketidakpatuhan LHKPN antara Pejabat Pimpinan Tinggi (JPT) dengan Pejabat Administrator terletak pada...", "options": ["JPT dijatuhi hukuman berat, sedangkan administrator dijatuhi hukuman sedang", "JPT dijatuhi hukuman ringan, sedangkan administrator dijatuhi hukuman berat", "JPT dibebaskan dari sanksi, sedangkan administrator dijatuhi hukuman lisan", "Keduanya sama-sama dijatuhi hukuman pemotongan tunjangan kinerja 6 bulan", "Keduanya langsung diberhentikan tidak dengan hormat sebagai pegawai negeri"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Analisis Perbedaan Sanksi LHKPN JPT vs Administrator</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Ketidakpatuhan LHKPN oleh <strong>pejabat administrator dan fungsional dijatuhi hukuman sedang</strong>, sedangkan oleh <strong>pejabat pimpinan tinggi (JPT) dijatuhi hukuman berat</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena regulasi menuntut tanggung jawab moral kepemimpinan yang lebih tinggi pada level pimpinan tinggi (JPT) sehingga pelanggarannya langsung diklasifikasikan sebagai hukuman berat, berbeda dengan level administrator yang berada pada taraf hukuman sedang. Maka, opsi A adalah analisis yang benar.</p><p>Jawaban tepat: <strong>A. JPT dijatuhi hukuman berat, sedangkan administrator dijatuhi hukuman sedang</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Sangat keliru karena level JPT memikul tanggung jawab lebih berat dari administrator.</li><li><strong>C</strong>: Tidak ada pembebasan sanksi bagi pejabat pimpinan tinggi.</li><li><strong>D</strong>: Tingkat hukumannya berbeda (berat vs sedang), tidak identik pemotongan 6 bulan.</li><li><strong>E</strong>: Pemberhentian tidak hormat bukan sanksi otomatis pertama ketidakpatuhan LHKPN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 56) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>melaporkan harta kekayaan kepada pejabat yang berwenang • pejabat pimpinan tinggi dan pejabat lainnya</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C4 - Analisis", "question": "Transisi ambang batas ketidakhadiran kumulatif dari hukuman disiplin sedang ke hukuman disiplin berat terjadi saat...", "options": ["Ketidakhadiran mencapai 11 hari kerja dalam satu semester", "Ketidakhadiran melewati 20 hari dan mencapai 21 hari kerja", "Ketidakhadiran mencapai 16 hari kerja dalam kurun waktu satu semester", "Pegawai tidak hadir selama 5 hari berturut-turut pada awal tahun", "Ketidakhadiran mencapai 14 hari kerja pada kantor vertikal"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Analisis Transisi Sanksi Jam Kerja Sedang ke Berat</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Batas tertinggi hukuman sedang adalah 20 hari kerja (pemotongan tukin 12 bulan). Begitu masuk <strong>21 sampai dengan 24 hari kerja</strong>, sanksi beralih ke <strong>hukuman disiplin berat (penurunan jabatan 12 bulan)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena hari ke-21 merupakan titik pemisah yuridis: hari ke-17 s.d. 20 dijatuhi sanksi sedang, sedangkan mulai hari ke-21 sampai 24 secara resmi masuk ke dalam rezim hukuman disiplin berat. Dengan demikian, opsi A adalah analisis transisi yang tepat.</p><p>Jawaban tepat: <strong>B. Ketidakhadiran melewati 20 hari dan mencapai 21 hari kerja</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Sebelas hari merupakan awal masuk sanksi sedang, bukan transisi ke sanksi berat.</li><li><strong>C</strong>: Kurang tepat, angka 16 hari kerja masih berada dalam rentang hukuman disiplin sedang (11-13 hari teguran/potong tukin, 14-16 hari, 17-20 hari).</li><li><strong>D</strong>: Lima hari berturut-turut masih dalam kategori hukuman disiplin ringan.</li><li><strong>E</strong>: Empat belas hari kerja masih berada di pertengahan kategori hukuman sedang (tukin 9 bulan).</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 56) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>21 (dua puluh satu)\nsampai dengan 24 (dua puluh empat) hari kerja • masuk kerja dan menaati ketentuan jam kerja</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Hukuman pembebasan dari jabatannya menjadi jabatan pelaksana selama 12 bulan dijatuhkan bagi PNS yang mangkir selama...", "options": ["25 sampai dengan 27 hari kerja dalam setahun", "11 sampai dengan 13 hari kerja dalam setahun", "14 sampai dengan 16 hari kerja dalam setahun", "17 sampai dengan 20 hari kerja dalam setahun", "21 sampai dengan 24 hari kerja dalam setahun"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sanksi Mangkir 25-27 Hari Kerja</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Hukuman pembebasan jabatan menjadi jabatan pelaksana selama 12 bulan dijatuhkan bagi PNS yang <strong>tidak masuk kerja tanpa alasan sah secara kumulatif selama 25 sampai dengan 27 hari kerja dalam 1 tahun</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena interval ketidakhadiran 25 s.d. 27 hari kerja merupakan tingkat kedua hukuman disiplin berat, di mana pejabat yang bersangkutan dicopot dari posisinya dan dijadikan pelaksana selama satu tahun. Maka, opsi A adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>A. 25 sampai dengan 27 hari kerja dalam setahun</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Absen 11-13 hari dijatuhi pemotongan tukin 25% selama 6 bulan.</li><li><strong>C</strong>: Absen 14-16 hari dijatuhi pemotongan tukin 25% selama 9 bulan.</li><li><strong>D</strong>: Absen 17-20 hari dijatuhi pemotongan tukin 25% selama 12 bulan.</li><li><strong>E</strong>: Absen 21-24 hari dijatuhi penurunan jabatan setingkat lebih rendah 12 bulan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 57) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pembebasan dari jabatannya menjadi jabatan pelaksana • 25\n(dua puluh lima) sampai dengan 27 (dua puluh tujuh) hari\nkerja</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C2 - Pemahaman", "question": "PNS yang tidak masuk kerja tanpa alasan sah secara kumulatif selama 28 hari kerja atau lebih dalam setahun dijatuhi...", "options": ["Teguran lisan dari pejabat fungsional pembina", "Pemberhentian dengan hormat tidak atas permintaan sendiri", "Pemotongan tunjangan kinerja sebesar 10 persen", "Pemberian tugas belajar selama satu semester dinas", "Penurunan kelas jabatan sementara selama tiga bulan"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sanksi Mangkir 28 Hari Kerja Kumulatif</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>PNS yang tidak masuk kerja tanpa alasan sah secara kumulatif selama <strong>28 hari kerja atau lebih dalam 1 tahun dijatuhi pemberhentian dengan hormat tidak atas permintaan sendiri sebagai PNS</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena akumulasi mangkir mencapai 28 hari kerja atau lebih mencerminkan pengabaian total kewajiban dinas, sehingga diancam sanksi terberat yaitu pemberhentian dari korps PNS. Oleh sebab itu, opsi A benar.</p><p>Jawaban tepat: <strong>B. Pemberhentian dengan hormat tidak atas permintaan sendiri</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Teguran lisan hanya untuk ketidakhadiran awal 3 hari kerja.</li><li><strong>C</strong>: Pemotongan tukin 10% tidak ada dalam klasifikasi sanksi PP 94/2021.</li><li><strong>D</strong>: Tugas belajar bukan hukuman atas pelanggaran ketidakhadiran masif.</li><li><strong>E</strong>: Penurunan jabatan durasinya 12 bulan, bukan 3 bulan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 57) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pemberhentian dengan hormat tidak atas permintaan sendiri\nsebagai PNS • 28 (dua puluh delapan)\nhari kerja atau lebih dalam 1 (satu) tahun</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C2 - Pemahaman", "question": "PNS yang tidak masuk kerja tanpa alasan yang sah secara terus menerus selama 10 hari kerja dijatuhi...", "options": ["Pernyataan tidak puas secara tertulis oleh atasan", "Pemotongan tunjangan kinerja selama 6 bulan kalender", "Pemberhentian dengan hormat tidak atas permintaan sendiri", "Teguran lisan yang dicatat pada buku harian", "Pemberian dispensasi waktu kerja selama satu pekan"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sanksi Mangkir 10 Hari Kerja Terus Menerus</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Hukuman <strong>pemberhentian dengan hormat tidak atas permintaan sendiri sebagai PNS</strong> dijatuhkan bagi PNS yang tidak masuk kerja tanpa alasan yang sah <strong>secara terus menerus selama 10 (sepuluh) hari kerja</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena absen berturut-turut tanpa kabar selama 10 hari kerja merupakan bentuk disersi/mangkir kontinu yang berakibat langsung pada sanksi pemecatan (pemberhentian dengan hormat tidak atas permintaan sendiri). Dengan demikian, opsi A adalah pilihan yang benar.</p><p>Jawaban tepat: <strong>C. Pemberhentian dengan hormat tidak atas permintaan sendiri</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pernyataan tidak puas untuk absen kumulatif 7-10 hari yang terputus-putus.</li><li><strong>B</strong>: Pemotongan tukin 6 bulan untuk absen kumulatif 11-13 hari, bukan kontinu 10 hari.</li><li><strong>D</strong>: Teguran lisan hanya untuk ketidakhadiran 3 hari kerja.</li><li><strong>E</strong>: Dispensasi tidak dapat diberikan bagi pegawai yang mangkir terus-menerus.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 57) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>secara terus menerus selama 10 (sepuluh) hari\nkerja • pemberhentian dengan hormat tidak atas permintaan sendiri</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Kewajiban PNS terkait integritas penerimaan hadiah menurut PP 94/2021 adalah...", "options": ["Menyimpan uang tanda terima kasih dalam kas operasional kantor", "Menerima seluruh pemberian pihak ketiga selama tidak meminta", "Membagikan uang pemberian rekanan secara merata kepada staf", "Tidak menerima pemberian apapun terkait tugas kedinasan dan fungsi", "Mewajibkan pengguna layanan memberikan bingkisan hari raya"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kewajiban Menolak Gratifikasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Menurut PP 94/2021, PNS berkewajiban <strong>tidak menerima segala bentuk pemberian</strong> yang berkaitan dengan tugas dan fungsinya di luar penghasilan sah.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat. Integritas aparatur sipil negara diatur secara tegas dalam PP 94/2021 yang melarang penerimaan imbalan atau hadiah dalam bentuk apapun yang bertalian dengan wewenang jabatan kedinasan. Penolakan pemberian di luar hak legalitas bertujuan menjaga objektivitas dan mencegah timbulnya konflik kepentingan. Maka dari itu, opsi D mencerminkan kewajiban integritas yang benar.</p><p>Jawaban tepat: <strong>D. Tidak menerima pemberian apapun terkait tugas kedinasan dan fungsi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Menampung dana ilegal dalam kas non-budgeter melanggar UU Keuangan Negara.</li><li><strong>B</strong>: Menerima gratifikasi pasif tetap dilarang dan wajib dilaporkan ke UPG/KPK.</li><li><strong>C</strong>: Membagi-bagikan uang suap tetap merupakan tindak pidana korupsi berjamaah.</li><li><strong>E</strong>: Meminta bingkisan merupakan pemerasan jabatan yang diancam pidana.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 57) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menolak segala bentuk pemberian yang berkaitan dengan\ntugas dan fungsi • ketentuan\nperaturan perundang-undangan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Surat pemanggilan pemeriksaan kepada PNS yang diduga melanggar disiplin disampaikan paling lambat...", "options": ["3 (tiga) hari kerja sejak laporan pengaduan", "1 (satu) hari kalender sebelum pemeriksaan", "14 (empat belas) hari kerja setelah pemeriksaan", "30 (tiga puluh) hari kalender sebelum sidang", "7 (tujuh) hari kerja sebelum tanggal pemeriksaan"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tenggat Waktu Pemanggilan Pemeriksaan Disiplin</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pemanggilan kepada PNS yang diduga melakukan pelanggaran disiplin dilakukan <strong>paling lambat 7 (tujuh) hari kerja sebelum tanggal pemeriksaan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena batas waktu minimal 7 hari kerja memberikan kesempatan wajar bagi terperiksa untuk menyiapkan bahan pembelaan dan bukti pendukung sebelum menjalani pemeriksaan resmi. Oleh sebab itu, opsi A adalah jawaban yang tepat.</p><p>Jawaban tepat: <strong>E. 7 (tujuh) hari kerja sebelum tanggal pemeriksaan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Bukan tenggat pemanggilan resmi menurut tata cara PP 94/2021.</li><li><strong>B</strong>: Satu hari terlalu mendadak dan melanggar hak pembelaan yang adil bagi aparatur.</li><li><strong>C</strong>: Pemanggilan dilakukan sebelum pemeriksaan, bukan setelah pemeriksaan.</li><li><strong>D</strong>: Tiga puluh hari terlalu lama dan memperlambat kepastian penegakan disiplin.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 57) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pemanggilan kepada PNS yang diduga melakukan pelanggaran • paling lambat 7 (tujuh) hari kerja sebelum tanggal\npemeriksaan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Apabila pada tanggal pemeriksaan pertama PNS tidak hadir, pemanggilan kedua dilakukan paling lambat...", "options": ["14 (empat belas) hari kalender setelah tahun dinas berakhir", "7 (tujuh) hari kerja sejak tanggal seharusnya diperiksa", "3 (tiga) hari kerja sebelum pergantian pimpinan satker", "21 (dua puluh satu) hari kerja sejak surat pertama", "30 (tiga puluh) hari kalender sejak laporan inspektorat"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Prosedur Pemanggilan Kedua Pemeriksaan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Apabila PNS tidak hadir pada pemeriksaan pertama, <strong>maka dilakukan pemanggilan kedua paling lambat 7 (tujuh) hari kerja sejak tanggal seharusnya yang bersangkutan diperiksa</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena tenggat 7 hari kerja sejak mangkirnya pemeriksaan pertama menjadi batas waktu wajib bagi atasan langsung untuk menerbitkan surat panggilan kedua. Dengan demikian, opsi A adalah pilihan yang benar.</p><p>Jawaban tepat: <strong>B. 7 (tujuh) hari kerja sejak tanggal seharusnya diperiksa</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Tidak menunggu akhir tahun anggaran karena pemeriksaan harus segera tuntas.</li><li><strong>C</strong>: Proses hukum disiplin tidak bergantung pada agenda pergantian pimpinan.</li><li><strong>D</strong>: Dua puluh satu hari melanggar prinsip percepatan penyelesaian sengketa disiplin.</li><li><strong>E</strong>: Tiga puluh hari bukan ketentuan tenggat pemanggilan kedua PP 94/2021.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 57) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pemanggilan kedua paling\nlambat 7 (tujuh) hari kerja • tidak hadir</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C3 - Aplikasi", "question": "Kepala subbagian tata usaha tidak hadir tanpa keterangan sah selama 26 hari kumulatif dalam setahun. Sanksinya adalah...", "options": ["Pemotongan tunjangan kinerja sebesar 25% selama satu semester", "Penurunan jabatan setingkat lebih rendah selama 6 bulan kalender", "Pembebasan dari jabatannya menjadi jabatan pelaksana selama 12 bulan", "Teguran lisan dari atasan langsung di hadapan seluruh pegawai", "Pemberhentian sementara status kepegawaian selama masa dinas"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Implementasi Sanksi Mangkir 26 Hari Kerja</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Ketidakhadiran 26 hari kerja berada dalam interval <strong>25 s.d. 27 hari kerja</strong>, yang diancam hukuman <strong>pembebasan dari jabatannya menjadi jabatan pelaksana selama 12 bulan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena angka 26 hari secara tepat berada di tengah interval 25-27 hari kerja, yang mewajibkan pencopotan pejabat tersebut menjadi pelaksana (staf biasa) selama 12 bulan. Maka, opsi A benar.</p><p>Jawaban tepat: <strong>C. Pembebasan dari jabatannya menjadi jabatan pelaksana selama 12 bulan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pemotongan tukin 6 bulan untuk ketidakhadiran 11-13 hari kerja.</li><li><strong>B</strong>: Penurunan jabatan durasinya 12 bulan untuk mangkir 21-24 hari kerja.</li><li><strong>D</strong>: Teguran lisan hanya untuk ketidakhadiran awal 3 hari kerja.</li><li><strong>E</strong>: Pemberhentian sementara bukan sanksi administratif atas mangkir kerja 26 hari.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 57) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>(dua puluh • 25\n(dua puluh lima) sampai dengan 27 (dua puluh tujuh) hari\nkerja • pembebasan dari jabatannya menjadi jabatan pelaksana</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C3 - Aplikasi", "question": "Seorang staf menghilang dan tidak masuk kerja tanpa kabar sama sekali selama 10 hari kerja berturut-turut. Pejabat berwenang wajib...", "options": ["Menaikkan pangkat satu tingkat lebih tinggi karena masa kerja", "Memberikan perpanjangan cuti tahunan secara otomatis tanpa batas", "Menjatuhkan teguran tertulis ringan tanpa proses pemanggilan", "Menjatuhkan pemberhentian dengan hormat tidak atas permintaan sendiri", "Memindahkan pegawai ke perwakilan kementerian di luar negeri"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Implementasi Mangkir 10 Hari Berturut-turut</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Mangkir tanpa alasan sah <strong>secara terus menerus selama 10 hari kerja</strong> dijatuhi <strong>pemberhentian dengan hormat tidak atas permintaan sendiri sebagai PNS</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena meninggalkan tugas 10 hari berturut-turut merupakan pelanggaran berat yang secara tegas diatur dengan sanksi pemberhentian sebagai pegawai negeri sipil. Jadi, pilihan D adalah tindakan hukum yang benar.</p><p>Jawaban tepat: <strong>D. Menjatuhkan pemberhentian dengan hormat tidak atas permintaan sendiri</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Sangat keliru karena pelaku mangkir kerja berat dilarang dipromosikan.</li><li><strong>B</strong>: Cuti tahunan wajib diajukan dan disetujui sebelumnya, bukan alasan pembenaran mangkir.</li><li><strong>C</strong>: Teguran tertulis tidak memadai dan melanggar ketentuan sanksi mangkir terus menerus.</li><li><strong>E</strong>: Penugasan luar negeri bukan sarana penyelesaian pegawai bermasalah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 57) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>secara terus menerus selama 10 (sepuluh) hari\nkerja • pemberhentian dengan hormat</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C4 - Analisis", "question": "Berdasarkan PP 94/2021, dua kondisi ketidakhadiran yang berujung pada sanksi pemberhentian sebagai PNS adalah...", "options": ["Izin sakit tanpa resep dokter selama 7 hari kalender", "Kumulatif 14 hari kerja atau berturut-turut 3 hari kerja", "Kumulatif 20 hari kerja atau berturut-turut 5 hari kerja", "Kumulatif 25 hari kerja atau berturut-turut 7 hari kerja", "Kumulatif minimal 28 hari kerja atau kontinu 10 hari kerja"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Analisis Dua Pintu Pemecatan Jam Kerja</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dua klausul pemecatan jam kerja adalah: <strong>1) kumulatif 28 hari kerja atau lebih dalam 1 tahun; atau 2) terus menerus selama 10 hari kerja</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena regulasi memberikan dua tolok ukur berbeda: akumulasi tahunan (28 hari kerja kumulatif) dan ketidakhadiran berurutan (10 hari kerja terus-menerus). Kedua jalur ini berakibat sanksi pemecatan dengan hormat tidak atas permintaan sendiri. Maka, opsi A benar.</p><p>Jawaban tepat: <strong>E. Kumulatif minimal 28 hari kerja atau kontinu 10 hari kerja</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Izin sakit adalah hak cuti sakit yang diatur dalam rezim terpisah.</li><li><strong>B</strong>: Empat belas hari hanya dipotong tukin 9 bulan, dan 3 hari hanya teguran lisan.</li><li><strong>C</strong>: Dua puluh hari dipotong tukin 12 bulan, bukan sanksi pemberhentian.</li><li><strong>D</strong>: Kurang tepat, kumulatif 25 hari kerja diancam sanksi berat penurunan jabatan atau pembebasan jabatan, belum pemberhentian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 57) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pemberhentian dengan hormat tidak atas permintaan sendiri • 28 (dua puluh delapan)\nhari kerja atau lebih • secara terus menerus selama 10</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C4 - Analisis", "question": "Kewajiban menyampaikan surat panggilan tertulis minimal 7 hari sebelum pemeriksaan bertujuan menegakkan asas...", "options": ["Hak pembelaan yang adil dan transparansi proses hukum (due process)", "Penyelesaian administrasi kepegawaian secara singkat di tingkat satker", "Penghapusan seluruh catatan rekam jejak pelanggaran kepegawaian", "Kewajiban bagi atasan untuk mencabut seluruh tuduhan pelanggaran", "Penetapan status kepegawaian sementara selama proses verifikasi fakta"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Analisis Asas Akuntabilitas Prosedur Pemeriksaan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pemanggilan tertulis paling lambat 7 hari kerja sebelum tanggal pemeriksaan menjamin <strong>keadilan prosedural (due process of law) dan hak membela diri bagi PNS terperiksa</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena hukum disiplin modern menjunjung tinggi asas praduga tak bersalah dan hak didengar secara adil (audi et alteram partem). Masa 7 hari kerja memberi kepastian hukum dan ruang pembelaan sebelum vonis dijatuhkan. Oleh karena itu, opsi A adalah analisis yang tepat.</p><p>Jawaban tepat: <strong>A. Hak pembelaan yang adil dan transparansi proses hukum (due process)</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kurang tepat, batas waktu 7 hari justru mencegah penyelesaian tergesa-gesa tanpa telaah komprehensif.</li><li><strong>C</strong>: Rekam jejak tidak dihapus melalui mekanisme surat panggilan.</li><li><strong>D</strong>: Atasan tidak wajib mencabut tuduhan jika bukti permulaan cukup kuat.</li><li><strong>E</strong>: Kurang tepat, pemanggilan pemeriksaan tidak secara otomatis mengubah status kepegawaian aktif aparatur.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 57) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>PNS yang diduga melakukan pelanggaran disiplin dipanggil secara\ntertulis • Tata cara penjatuhan hukuman disiplin</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Hasil pemeriksaan dugaan pelanggaran disiplin oleh pejabat pemeriksa wajib dituangkan dalam bentuk...", "options": ["Buku kas umum pembantu bendahara", "Kwitansi tanda terima belanja barang", "Surat perjanjian sewa menyewa gedung", "Berita Acara Pemeriksaan (BAP)", "Daftar hadir piket malam satuan pengamanan"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Bentuk Formal Hasil Pemeriksaan Disiplin (BAP)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Hasil pemeriksaan dugaan pelanggaran disiplin wajib dituangkan <strong>dalam bentuk berita acara pemeriksaan (BAP)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena BAP merupakan dokumen otentik berkekuatan hukum yang merekam seluruh tanya jawab, pengakuan, dalil pembelaan, dan alat bukti saat pemeriksaan kepegawaian. BAP menjadi landasan terbitnya SK hukuman. Maka dari itu, opsi A benar.</p><p>Jawaban tepat: <strong>D. Berita Acara Pemeriksaan (BAP)</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Buku kas pembantu adalah sarana pembukuan akuntansi perbendaharaan.</li><li><strong>B</strong>: Kwitansi merupakan bukti pertanggungjawaban belanja keuangan kas negara.</li><li><strong>C</strong>: Surat perjanjian sewa adalah dokumen hukum perdata kebendaan.</li><li><strong>E</strong>: Daftar piket adalah catatan operasional keamanan fasilitas kantor.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 58) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>dalam bentuk berita acara pemeriksaan • hasil\npemeriksaan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Upaya administratif yang dapat ditempuh oleh PNS yang tidak puas atas hukuman disiplin terdiri atas...", "options": ["Pengajuan uji materiil ke Mahkamah Konstitusi", "Gugatan perdata dan praperadilan pidana", "Aksi unjuk rasa dan mogok kerja bersama", "Penolakan tugas dinas dan penyegelan kantor", "Keberatan dan banding administratif"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dua Bentuk Upaya Administratif Disiplin</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Upaya administratif dapat dilakukan dengan cara pengajuan keberatan dan banding administratif</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena regulasi kepegawaian secara limitatif menyediakan dua jalur koreksi internal administratif bagi pegawai yang merasa keberatan atas keputusan sanksi, yaitu keberatan kepada atasan penghukum dan banding ke BAPEK. Oleh sebab itu, opsi A benar.</p><p>Jawaban tepat: <strong>E. Keberatan dan banding administratif</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Uji materiil di MK untuk menguji undang-undang, bukan keputusan sanksi individu.</li><li><strong>B</strong>: Gugatan perdata dan praperadilan adalah ranah hukum peradilan umum.</li><li><strong>C</strong>: Mogok kerja dan demonstrasi dilarang keras bagi aparatur sipil negara.</li><li><strong>D</strong>: Penyegelan kantor merupakan tindakan pidana anarkisme dan perusakan aset.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 58) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Upaya administratif dapat dilakukan dengan cara • keberatan dan banding administratif</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Upaya keberatan diajukan oleh PNS yang tidak puas atas hukuman disiplin kepada...", "options": ["Atasan pejabat yang berwenang menghukum", "Pengadilan Tata Usaha Negara tingkat pertama", "Kepala Kepolisian Resor setempat", "Pimpinan redaksi media cetak nasional", "Ketua Komisi Pengawas Persaingan Usaha"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi dan Tujuan Pengajuan Keberatan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Keberatan</strong> adalah upaya administratif yang diajukan oleh PNS kepada <strong>atasan pejabat yang berwenang menghukum</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena mekanisme keberatan merupakan peninjauan bertingkat satu tingkat di atas pejabat yang menjatuhkan sanksi (misal SK diterbitkan Eselon III, keberatan ditujukan kepada Eselon II atasannya). Jadi, pilihan A adalah jawaban yang tepat.</p><p>Jawaban tepat: <strong>A. Atasan pejabat yang berwenang menghukum</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Gugatan ke PTUN hanya dapat ditempuh setelah seluruh upaya administratif selesai.</li><li><strong>C</strong>: Polres tidak berwenang mengadili sengketa hukuman disiplin internal ASN.</li><li><strong>D</strong>: Media massa bukan lembaga peradilan atau penyelesai sengketa aparatur.</li><li><strong>E</strong>: KPPU mengawasi persaingan usaha bisnis, bukan disiplin kepegawaian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 58) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Keberatan adalah upaya administratif • atasan pejabat\nyang berwenang menghukum</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Banding administratif atas sanksi hukuman pemberhentian sebagai PNS diajukan kepada...", "options": ["Lembaga Sensor Film Nasional", "Badan Pertimbangan Kepegawaian (BAPEK)", "Otoritas Jasa Keuangan Bidang Perbankan", "Badan Narkotika Nasional Kota", "Pengadilan Hubungan Industrial Swasta"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Lembaga Banding Administratif (BAPEK)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Banding administratif</strong> adalah upaya administratif bagi PNS atas sanksi pemberhentian yang diajukan kepada <strong>Badan Pertimbangan Kepegawaian</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena BAPEK adalah majelis khusus tingkat nasional yang berwenang menyidangkan dan memutus sengketa banding administratif khusus atas sanksi pemecatan/pemberhentian PNS. Dengan demikian, opsi A adalah pilihan yang benar.</p><p>Jawaban tepat: <strong>B. Badan Pertimbangan Kepegawaian (BAPEK)</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: LSF berwenang dalam penyensoran tayangan film publik.</li><li><strong>C</strong>: OJK bertugas mengawasi industri jasa keuangan dan pasar modal.</li><li><strong>D</strong>: BNN bertugas dalam pencegahan dan pemberantasan narkotika.</li><li><strong>E</strong>: PHI mengadili perselisihan ketenagakerjaan sektor swasta buruh.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 58) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Banding administratif adalah upaya administratif • Badan Pertimbangan Kepegawaian</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Hukuman disiplin yang dijatuhkan oleh Presiden Republik Indonesia memiliki ketentuan...", "options": ["Dapat dibatalkan melalui surat keputusan kepala dinas daerah", "Wajib diulang pemeriksaannya oleh atasan langsung kantor", "Tidak dapat diajukan upaya administratif keberatan atau banding", "Otomatis gugur apabila pegawai mengajukan mutasi mandiri", "Harus disetujui terlebih dahulu oleh serikat pekerja kementerian"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kekebalan Keputusan Disiplin Presiden dari Upaya Administratif</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Hukuman disiplin yang <strong>tidak dapat diajukan upaya administratif adalah hukuman yang dijatuhkan oleh Presiden</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena Presiden adalah pemegang kekuasaan pemerintahan tertinggi dan Pejabat Pembina Kepegawaian tertinggi nasional, sehingga keputusan hukum disiplin yang diterbitkannya bersifat final di ranah administratif internal. Oleh karena itu, opsi A benar.</p><p>Jawaban tepat: <strong>C. Tidak dapat diajukan upaya administratif keberatan atau banding</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kepala dinas daerah tidak berwenang membatalkan Keppres.</li><li><strong>B</strong>: Atasan langsung tidak memiliki hierarki untuk menguji keputusan Presiden.</li><li><strong>D</strong>: Mutasi mandiri tidak menggugurkan ketetapan sanksi hukum Presiden.</li><li><strong>E</strong>: Serikat pekerja tidak memiliki hak veto atas keputusan Presiden.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 58) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>hukuman yang tidak dapat diajukan upaya administratif\nadalah hukuman yang dijatuhkan oleh: • Presiden</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C3 - Aplikasi", "question": "Dari hasil BAP, pelanggaran staf terbukti berkadar sedang yang wewenang menghukumnya berada pada Kepala Kantor. Tindakan atasan langsung adalah...", "options": ["Meminta uang ganti rugi kepada staf yang bersangkutan", "Langsung menerbitkan keputusan hukuman pemotongan tunjangan kinerja", "Menyimpan berkas BAP di laci meja tanpa tindak lanjut dinas", "Menghancurkan berkas BAP demi menjaga kerukunan ruangan", "Melaporkan secara hierarki disertai Berita Acara Pemeriksaan"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penerapan Pelaporan Hierarki Berita Acara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Apabila kewenangan menghukum merupakan kewenangan pejabat yang lebih tinggi, <strong>atasan langsung wajib melaporkan secara hierarki disertai berita acara pemeriksaan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena atasan langsung terikat asas kepatuhan wewenang. Jika sanksi berada di luar jurisdiksinya, ia wajib meneruskan berkas hasil pemeriksaan secara berjenjang kepada pejabat yang sah berwenang menghukum. Maka dari itu, opsi A benar.</p><p>Jawaban tepat: <strong>E. Melaporkan secara hierarki disertai Berita Acara Pemeriksaan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Meminta uang adalah pemerasan jabatan dan pelanggaran pidana korupsi.</li><li><strong>B</strong>: Menerbitkan SK di luar wewenang menyebabkan keputusan cacat wewenang/batal hukum.</li><li><strong>C</strong>: Menyimpan berkas tanpa tindak lanjut merupakan bentuk pembiaran pelanggaran.</li><li><strong>D</strong>: Menghancurkan BAP adalah tindak pidana penghilangan barang bukti dinas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 58) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pejabat yang lebih tinggi maka atasan langsung tersebut wajib\nmelaporkan secara hierarki • berita acara pemeriksaan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C3 - Aplikasi", "question": "Pejabat eselon III menjatuhkan sanksi pemotongan tukin 25% kepada seorang staf. Staf yang tidak puas berhak mengajukan keberatan kepada...", "options": ["Pejabat pimpinan tinggi pratama (Eselon II) atasan penghukum", "Bupati atau walikota tempat tinggal pribadi pegawai", "Lembaga bantuan hukum swasta di kota setempat", "Pengurus rukun tetangga dan rukun warga komplek", "Majelis ulama indonesia perwakilan daerah"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penerapan Prosedur Keberatan Sanksi Sedang</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Keberatan diajukan kepada <strong>atasan pejabat yang berwenang menghukum</strong> (pejabat satu tingkat di atas pejabat penerbit sanksi).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena atasan dari pejabat eselon III adalah pejabat eselon II (Kepala Kantor Wilayah / Direktur). Ke sanalah surat keberatan administratif wajib dialamatkan secara formal. Jadi, pilihan A adalah langkah yang tepat.</p><p>Jawaban tepat: <strong>A. Pejabat pimpinan tinggi pratama (Eselon II) atasan penghukum</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Bupati/walikota tidak memiliki yurisdiksi atas instansi vertikal Kemenkeu.</li><li><strong>C</strong>: LBH swasta bukan saluran pengajuan keberatan administratif birokrasi.</li><li><strong>D</strong>: Pengurus RT/RW tidak berwenang mengadili surat sanksi kepegawaian negara.</li><li><strong>E</strong>: MUI lembaga keagamaan, bukan instrumen peradilan banding dinas ASN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 58) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Keberatan adalah upaya administratif yang dapat ditempuh oleh\nPNS yang tidak puas • kepada atasan pejabat</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C3 - Aplikasi", "question": "Seorang PNS dijatuhi hukuman pemberhentian tidak dengan hormat oleh menteri. Upaya administratif yang sah adalah...", "options": ["Mengajukan permohonan kredit baru pada bank daerah", "Mengajukan banding administratif kepada Badan Pertimbangan Kepegawaian", "Melakukan mogok makan di gerbang pintu masuk kantor", "Mendaftarkan diri menjadi calon pegawai negeri sipil baru", "Menyita barang inventaris kantor sebagai jaminan pesangon"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Implementasi Banding Administratif atas Pemecatan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>PNS yang tidak puas atas hukuman pemberhentian dapat menempuh <strong>banding administratif kepada Badan Pertimbangan Kepegawaian (BAPEK)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena satu-satunya forum banding administratif resmi yang diakui regulasi atas putusan pemberhentian PNS adalah BAPEK. Jalur hukum ini sah dan berkeadilan bagi aparatur. Oleh karenanya, opsi A adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>B. Mengajukan banding administratif kepada Badan Pertimbangan Kepegawaian</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pengajuan kredit bank tidak berkaitan dengan sengketa pemberhentian kepegawaian.</li><li><strong>C</strong>: Mogok makan bukan prosedur hukum yang diakui dalam tata tertib ASN.</li><li><strong>D</strong>: Pegawai yang diberhentikan tidak hormat dilarang mendaftar kembali sebagai CPNS.</li><li><strong>E</strong>: Menyita BMN secara sepihak merupakan tindak pidana pencurian aset negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 58) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Banding administratif adalah upaya administratif • pemberhentian dengan hormat tidak atas permintaan sendiri\natau pemberhentian tidak dengan hormat</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C4 - Analisis", "question": "Alasan yuridis mengapa hukuman disiplin ringan (teguran lisan, tertulis, tidak puas tertulis) TIDAK dapat diajukan upaya administratif adalah...", "options": ["Untuk mempercepat proses alih status menjadi tenaga swasta", "Karena sanksi ringan otomatis menghapus kewajiban pembayaran pajak", "Sifat sanksi bersifat pembinaan langsung tanpa pengurangan hak kepegawaian", "Kewenangan pembatalan sanksi ringan berada mutlak pada pimpinan satker", "Agar pejabat yang menghukum tidak dapat dievaluasi oleh menteri"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Analisis Jenis Hukuman Tanpa Upaya Administratif</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sanksi disiplin ringan <strong>tidak dapat diajukan upaya administratif</strong> karena bersifat pembinaan moral internal atasan kepada bawahan tanpa mereduksi hak kepegawaian (seperti pangkat atau tunjangan).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena sanksi ringan bertujuan mengoreksi perilaku kerja secara cepat dan langsung oleh atasan. Ketiadaan jalur keberatan menjaga efektivitas pembinaan harian tanpa membebani birokrasi peradilan administratif. Maka dari itu, opsi A adalah simpulan analisis yang benar.</p><p>Jawaban tepat: <strong>C. Sifat sanksi bersifat pembinaan langsung tanpa pengurangan hak kepegawaian</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Sanksi ringan tidak mengalihkan status pegawai menjadi swasta.</li><li><strong>B</strong>: Sanksi ringan tidak mempengaruhi kewajiban perpajakan aparatur.</li><li><strong>D</strong>: Kurang tepat, pembatalan sanksi tetap tunduk pada prinsip legalitas administrasi pemerintahan.</li><li><strong>E</strong>: Atasan tetap diawasi dan dievaluasi dalam sistem pengawasan manajerial.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 58) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>hukuman yang tidak dapat diajukan upaya administratif • teguran lisan, teguran tertulis,\ndan pernyataan tidak puas secara tertulis</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C4 - Analisis", "question": "Perbedaan mendasar antara upaya keberatan dan banding administratif terletak pada aspek...", "options": ["Jumlah saksi ahli hukum yang wajib dihadirkan saat pemeriksaan", "Kewajiban melampirkan surat keterangan sehat dari puskesmas", "Besaran biaya pendaftaran perkara yang wajib disetorkan ke kas", "Lembaga tujuan pengajuan serta jenis tingkatan sanksi yang disengketakan", "Agama dan keyakinan spiritual yang dianut oleh pemohon banding"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pembedaan Keberatan dan Banding Administratif</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Keberatan</strong> ditujukan kepada <strong>atasan penghukum</strong> untuk sanksi selain pemberhentian, sedangkan <strong>banding administratif</strong> diajukan kepada <strong>BAPEK</strong> khusus untuk <strong>sanksi pemberhentian</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena pembeda utamanya mencakup instansi penerima (atasan langsung vs lembaga BAPEK) dan lingkup sanksinya (hukuman non-pemberhentian vs hukuman pemecatan). Ini membagi kedua mekanisme secara tegas. Dengan demikian, opsi A tepat.</p><p>Jawaban tepat: <strong>D. Lembaga tujuan pengajuan serta jenis tingkatan sanksi yang disengketakan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pemeriksaan administratif internal tidak mensyaratkan kuota saksi ahli.</li><li><strong>B</strong>: Surat sehat puskesmas bukan syarat utama penerimaan sengketa disiplin.</li><li><strong>C</strong>: Upaya administratif dalam kepegawaian negara bebas biaya perkara.</li><li><strong>E</strong>: Sistem hukum ASN netral dan tidak membedakan latar belakang agama pemohon.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 58) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Keberatan adalah upaya administratif • Banding administratif adalah upaya administratif • tidak puas terhadap hukuman disiplin</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Tujuan utama dari pemberian hak cuti kedinasan bagi Pegawai Negeri Sipil adalah untuk menjamin...", "options": ["Ketersediaan alokasi anggaran belanja pegawai satker", "Kelancaran suksesi peralihan kepemimpinan unit kerja", "Kesegaran jasmani dan rohani dalam bekerja", "Kenaikan peringkat jabatan struktural secara berkala", "Kepastian masa persiapan pensiun aparatur sipil"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Empat Bentuk Perlindungan PNS Berdasarkan UU ASN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Cuti adalah keadaan tidak masuk kerja yang diizinkan dalam jangka waktu tertentu dengan tujuan untuk <strong>menjamin kesegaran jasmani dan rohani</strong> aparatur.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat. Berdasarkan materi hak cuti PNS pada modul, cuti diberikan bukan sekadar hak libur, melainkan instrumen pemulihan fisik dan mental bagi aparatur sipil negara agar produktivitas kerja dan kesehatan psikologisnya tetap terjaga optimal.</p><p>Jawaban tepat: <strong>C. Kesegaran jasmani dan rohani dalam bekerja</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Anggaran belanja pegawai dikelola melalui mekanisme perencanaan DIPA.</li><li><strong>B</strong>: Suksesi kepemimpinan dijalankan melalui pembinaan pola karier dan mutasi.</li><li><strong>D</strong>: Kenaikan peringkat diproses berdasarkan capaian prestasi dan asesmen.</li><li><strong>E</strong>: Persiapan masa pensiun diwadahi dalam jenis masa persiapan pensiun.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 59) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>keadaan tidak masuk kerja yang diijinkan • kesegaran jasmani dan rohani</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Implementasi perlindungan bantuan hukum bagi aparatur di Kementerian Keuangan dilaksanakan melalui...", "options": ["Penolakan terhadap seluruh surat panggilan aparat penegak hukum", "Pemberian dana tunai sewa pengacara swasta tanpa batas", "Penggantian kerugian uang pribadi yang dikorupsi pegawai", "Pendampingan oleh unit Bantuan Hukum (Bankum) institusi", "Pemberian status kekebalan hukum diplomatik bagi pejabat"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Bantuan Hukum Kedinasan di Kemenkeu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Perlindungan di Kementerian Keuangan berupa <strong>bantuan hukum secara institusi (Pendampingan oleh Bankum)</strong> terkait pelaksanaan tugas kedinasan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena Kementerian Keuangan menyediakan advokasi kedinasan melalui Biro Advokasi (Bankum) untuk mendampingi pegawai yang menghadapi persoalan hukum akibat pelaksanaan tugas dan wewenang dinas yang sah. Oleh sebab itu, opsi A benar.</p><p>Jawaban tepat: <strong>D. Pendampingan oleh unit Bantuan Hukum (Bankum) institusi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Aparatur wajib kooperatif mematuhi panggilan resmi penegak hukum.</li><li><strong>B</strong>: Kementerian tidak mendanai advokat swasta perseorangan secara bebas.</li><li><strong>C</strong>: Negara dilarang mengganti kerugian atas perbuatan korupsi individu pegawai.</li><li><strong>E</strong>: PNS tidak memiliki kekebalan hukum diplomatik di dalam yurisdiksi nasional.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 59) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>bantuan hukum secara institusi (Pendampingan oleh Bankum) • Perlindungan di Kementerian Keuangan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Perlindungan jaminan kematian bagi pegawai di lingkungan Kementerian Keuangan diwujudkan dalam bentuk...", "options": ["Penyediaan fasilitas pemakaman kenegaraan di taman makam", "Pembangunan monumen patung pahlawan di kantor wilayah", "Pemberian hak mewariskan nomor induk pegawai (NIP)", "Pembebasan pajak properti bagi seluruh keluarga pegawai", "Pemberian uang duka wafat dan uang duka tewas"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Jaminan Kematian di Lingkungan Kemenkeu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Perlindungan jaminan kematian di Kementerian Keuangan diwujudkan melalui pemberian <strong>uang duka wafat dan uang duka tewas</strong> bagi ahli waris pegawai.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena dokumen kepegawaian merinci bentuk santunan kematian melalui uang duka wafat (meninggal biasa) dan uang duka tewas (gugur dalam menjalankan tugas dinas). Santunan ini membantu keluarga yang ditinggalkan. Jadi, pilihan E benar.</p><p>Jawaban tepat: <strong>E. Pemberian uang duka wafat dan uang duka tewas</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pemakaman kenegaraan diperuntukkan bagi tokoh penerima bintang tanda jasa.</li><li><strong>B</strong>: Monumen patung bukan program jaminan sosial rutin kepegawaian.</li><li><strong>C</strong>: NIP melekat pada individu dan dilarang diwariskan kepada keturunan.</li><li><strong>D</strong>: Pajak daerah diatur oleh undang-undang perpajakan, bukan jaminan kematian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 59) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>jaminan kematian (uang duka tewas dan uang duka wafat) • Perlindungan di Kementerian Keuangan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Tata cara pemberian cuti Pegawai Negeri Sipil diatur secara teknis nasional melalui...", "options": ["Peraturan Kepala BKN Nomor 24 Tahun 2017", "Peraturan Menteri Keuangan Nomor 161 Tahun 2017", "Keputusan Menteri Keuangan Nomor 300 Tahun 2022", "Undang-Undang Nomor 20 Tahun 2009", "Peraturan Presiden Nomor 16 Tahun 2018"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Regulasi BKN Mengenai Tata Cara Cuti PNS</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Tata cara cuti PNS diatur dalam <strong>Peraturan Kepala BKN Nomor 24 Tahun 2017 tentang Tata Cara Pemberian Cuti Pegawai Negeri Sipil</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena Perka BKN 24/2017 merupakan regulasi rujukan operasional teknis nasional yang memuat syarat, prosedur, dan wewenang pemberian berbagai jenis cuti bagi seluruh ASN. Dengan demikian, opsi A adalah pilihan yang benar.</p><p>Jawaban tepat: <strong>A. Peraturan Kepala BKN Nomor 24 Tahun 2017</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Mengatur tentang sistem manajemen talenta Kementerian Keuangan masa lalu.</li><li><strong>C</strong>: Mengatur tentang sistem manajemen kinerja di Kementerian Keuangan.</li><li><strong>D</strong>: Mengatur tentang gelar, tanda jasa, dan tanda kehormatan negara.</li><li><strong>E</strong>: Mengatur tentang tata cara pengadaan barang dan jasa pemerintah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 59) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Peraturan Kepala BKN Nomor 24 Tahun 2017 • Tata Cara\nPemberian Cuti Pegawai Negeri Sipil</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Pelaksanaan cuti bagi pegawai di lingkungan internal Kementerian Keuangan dipedomani melalui...", "options": ["Surat Edaran Direktur Jenderal Pajak Nomor 1 Tahun 2015", "Surat Edaran Menteri Keuangan Nomor SE-15/MK.1/2018", "Instruksi Presiden Republik Indonesia Nomor 5 Tahun 2020", "Peraturan Bersama Menteri Keuangan dan Menteri Agama", "Surat Edaran Kepala Kantor Wilayah Perbendaharaan"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Surat Edaran Pelaksanaan Cuti di Kemenkeu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dasar hukum cuti internal Kemenkeu antara lain <strong>Surat Edaran Menteri Keuangan Nomor SE-15/MK.1/2018 tentang Pelaksanaan Cuti Bagi Pegawai Negeri Sipil di Lingkungan Kementerian Keuangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena SE-15/MK.1/2018 menjadi pedoman teknis internal kementerian dalam pengajuan, penatausahaan, serta pemotongan tunjangan selama pelaksanaan cuti pegawai. Maka dari itu, opsi A benar.</p><p>Jawaban tepat: <strong>B. Surat Edaran Menteri Keuangan Nomor SE-15/MK.1/2018</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Surat edaran sektoral pajak, bukan pedoman cuti menyeluruh kementerian.</li><li><strong>C</strong>: Inpres bukan instrumen pedoman teknis operasional cuti kementerian.</li><li><strong>D</strong>: Bukan dasar hukum pelaksanaan cuti di Kementerian Keuangan.</li><li><strong>E</strong>: Surat edaran vertikal daerah bersumber dari SE Menteri Keuangan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 59) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Surat Edaran Menteri Keuangan Nomor SE-15/MK.1/2018 • Pelaksanaan Cuti Bagi Pegawai Negeri Sipil di Lingkungan Kementerian\nKeuangan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C3 - Aplikasi", "question": "Pejabat Pembuat Komitmen (PPK) digugat perdata oleh rekanan tender atas pembatalan kontrak yang sesuai aturan. Hak perlindungannya adalah...", "options": ["Penurunan pangkat setingkat lebih rendah secara otomatis", "Pemberian izin cuti di luar tanggungan negara selama masa sidang", "Pembebasan penuh dari jabatan menjadi pelaksana administrasi", "Pendampingan advokasi hukum institusi oleh Bankum kementerian", "Kewajiban menanggung seluruh biaya ganti rugi secara pribadi"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penerapan Bantuan Hukum bagi Pejabat PPK</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>PNS berhak atas <strong>bantuan hukum secara institusi (Pendampingan oleh Bankum)</strong> dalam perkara yang dihadapi di pengadilan terkait pelaksanaan tugas kedinasan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena tindakan kedinasan yang sah yang digugat pihak ketiga dilindungi secara kelembagaan oleh kementerian melalui pendampingan advokat Bankum Kemenkeu di pengadilan. Ini mewujudkan hak perlindungan aparatur. Oleh sebab itu, opsi A benar.</p><p>Jawaban tepat: <strong>D. Pendampingan advokasi hukum institusi oleh Bankum kementerian</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penurunan pangkat merupakan sanksi disiplin berat.</li><li><strong>B</strong>: CLTN bukan mekanisme perlindungan bagi pejabat yang menjalankan tugas sah.</li><li><strong>C</strong>: Pembebasan jabatan adalah hukuman disiplin, tidak boleh dijatuhkan pada pejabat taat aturan.</li><li><strong>E</strong>: Tindakan kedinasan sah yang digugat perdata dibela oleh negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 59) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>bantuan hukum secara institusi • Pendampingan oleh Bankum</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C3 - Aplikasi", "question": "Petugas bea cukai gugur saat operasi patroli laut menumpas penyelundup. Hak jaminan kematian yang diterima ahli warisnya adalah...", "options": ["Pemberian sanksi pembebasan dari status kepegawaian", "Kewajiban mengembalikan seluruh gaji yang pernah diterima", "Pencabutan seluruh tanda kehormatan yang dimiliki", "Pemotongan tunjangan kinerja masa lalu sebesar 25 persen", "Pemberian santunan uang duka tewas bagi aparatur"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Implementasi Hak Jaminan Gugur dalam Tugas</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Bagi PNS yang meninggal dalam pelaksanaan tugas dinas resmi, ahli waris berhak menerima perlindungan berupa <strong>uang duka tewas</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena gugur saat tugas operasi laut diklasifikasikan sebagai tewas dalam dinas, sehingga negara mengapresiasi pengorbanannya dengan memberikan hak uang duka tewas kepada keluarga yang ditinggalkan. Jadi, pilihan E adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>E. Pemberian santunan uang duka tewas bagi aparatur</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pemberhentian karena tewas dinas dihormati setinggi-tingginya oleh negara.</li><li><strong>B</strong>: Gaji yang sah diterima tidak pernah dituntut pengembaliannya.</li><li><strong>C</strong>: Pahlawan dinas justru dianugerahi kenaikan pangkat anumerta/tanda jasa.</li><li><strong>D</strong>: Tidak ada pemotongan tunjangan bagi aparatur yang tewas dalam tugas mulia.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 59) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>uang duka tewas • jaminan kematian</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C3 - Aplikasi", "question": "Bagian kepegawaian menyusun pedoman teknis cuti bersama tahunan. Landasan hukum peraturan pemerintah rujukan utamanya adalah...", "options": ["PP Nomor 11 Tahun 2017 jo PP Nomor 17 Tahun 2020", "Peraturan Pemerintah Nomor 30 Tahun 2019", "Peraturan Pemerintah Nomor 94 Tahun 2021", "Peraturan Pemerintah Nomor 35 Tahun 2010", "Peraturan Pemerintah Nomor 42 Tahun 2004"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penerapan Hirarki Dasar Hukum Cuti</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dasar hukum cuti PNS adalah <strong>Peraturan Pemerintah Nomor 11 Tahun 2017 sebagaimana telah diubah dengan PP Nomor 17 Tahun 2020</strong> tentang Manajemen Pegawai Negeri Sipil.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat. Ketentuan mengenai macam cuti, mekanisme permohonan, kewenangan pejabat pemberi cuti, hingga tata cara penetapan cuti bersama bersumber dari PP Nomor 11 Tahun 2017 yang disempurnakan dengan PP Nomor 17 Tahun 2020.</p><p>Jawaban tepat: <strong>A. PP Nomor 11 Tahun 2017 jo PP Nomor 17 Tahun 2020</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: PP 30/2019 mengatur penilaian kinerja pegawai aparatur sipil negara.</li><li><strong>C</strong>: PP 94/2021 mengatur ketentuan tata tertib dan hukuman disiplin PNS.</li><li><strong>D</strong>: PP 35/2010 mengatur tata cara pemberian gelar dan tanda kehormatan.</li><li><strong>E</strong>: PP 42/2004 mengatur pembinaan jiwa korps dan kode etik PNS.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 59) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>PP Nomor 11 Tahun 2017 • diubah dengan PP 17 tahun</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C4 - Analisis", "question": "Berdasarkan prinsip perlindungan ASN, pendampingan hukum kedinasan (Bankum) diberikan dalam perkara...", "options": ["Sengketa perceraian dan pembagian harta gono-gini pribadi", "Terkait pelaksanaan tugas dan wewenang kedinasan yang sah", "Tindak pidana perjudian online yang dilakukan di luar dinas", "Wanprestasi utang piutang pinjaman uang komersial mandiri", "Pelanggaran lalu lintas penilangan kendaraan pribadi dinas"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Analisis Batasan Hak Bantuan Hukum Kedinasan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Bantuan hukum institusi diberikan kepada PNS <strong>terkait dengan pelaksanaan tugas dan fungsi kedinasan yang sah</strong>, bukan urusan pribadi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena bantuan hukum negara dibiayai anggaran dinas khusus untuk melindungi pejabat/staf dalam mengambil keputusan kebijakan demi kepentingan negara. Perkara perdata atau pidana pribadi di luar dinas wajib diselesaikan secara mandiri. Oleh karenanya, opsi A adalah analisis yang tepat.</p><p>Jawaban tepat: <strong>B. Terkait pelaksanaan tugas dan wewenang kedinasan yang sah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Sengketa perceraian adalah ranah perdata keluarga pribadi aparatur.</li><li><strong>C</strong>: Judi online adalah tindak pidana murni pribadi dan pelanggaran disiplin.</li><li><strong>D</strong>: Utang piutang perorangan merupakan perikatan hukum perdata privat.</li><li><strong>E</strong>: Pelanggaran lalu lintas adalah tanggung jawab pribadi pengendara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 59) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>bantuan hukum • pasal 92 ayat (1) UU Nomor 5 Tahun 2014</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C4 - Analisis", "question": "Harmonisasi regulasi cuti di Kemenkeu mengintegrasikan ketentuan PP Manajemen PNS, Perka BKN, dan SE Menkeu guna menjamin...", "options": ["Pembatasan frekuensi pengajuan permohonan istirahat dalam satu tahun", "Penghapusan seluruh hak cuti tahunan pegawai di daerah terpencil", "Kepastian pemenuhan hak cuti tanpa mengganggu pelayanan publik", "Pemberian kebebasan bagi pegawai tidak masuk kantor tanpa izin", "Penyeragaman seluruh masa kerja pensiun menjadi 50 tahun"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Analisis Harmonisasi Peraturan Cuti Kemenkeu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pengaturan cuti yang terstruktur bertujuan menjamin <strong>keseimbangan antara pemenuhan hak istirahat pegawai dan kontinuitas kelancaran tugas pelayanan instansi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena keterpaduan regulasi memastikan setiap pegawai dapat menikmati hak istirahatnya secara adil, sementara mekanisme penjadwalan dan persetujuan atasan menjaga agar fungsi pelayanan negara tidak terbengkalai. Dengan demikian, opsi A adalah analisis yang benar.</p><p>Jawaban tepat: <strong>C. Kepastian pemenuhan hak cuti tanpa mengganggu pelayanan publik</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat, regulasi cuti mengatur tata cara pengajuan dan batasan hari, bukan pembatasan frekuensi sepihak.</li><li><strong>B</strong>: Hak cuti dijamin undang-undang dan tidak boleh dihapus sepihak di daerah.</li><li><strong>D</strong>: Meninggalkan tugas tanpa izin merupakan pelanggaran disiplin, bukan tujuan cuti.</li><li><strong>E</strong>: Batas usia pensiun diatur dalam undang-undang terpisah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 59) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Dasar hukum: • Cuti Pegawai Negeri Sipil • Surat Edaran Menteri Keuangan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Hak cuti tahunan merupakan hak PNS dan CPNS yang telah bekerja secara terus-menerus paling kurang selama...", "options": ["6 (enam) bulan", "1 (satu) tahun", "2 (dua) tahun", "3 (tiga) tahun", "5 (lima) tahun"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Syarat Masa Kerja Memperoleh Hak Cuti Tahunan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Hak Cuti Tahunan merupakan hak PNS, termasuk CPNS yang <strong>telah bekerja secara terus menerus selama 1 (satu) tahun</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena ketentuan secara tegas mematok masa bakti minimal 1 tahun kerja tanpa jeda sebelum pegawai (baik CPNS maupun PNS) berhak mengajukan cuti tahunan penuh. Oleh sebab itu, opsi A adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>B. 1 (satu) tahun</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Enam bulan belum memenuhi syarat minimal 1 tahun masa kerja.</li><li><strong>C</strong>: Dua tahun merupakan masa branding pegawai, bukan syarat awal cuti tahunan.</li><li><strong>D</strong>: Tiga tahun bukan batas awal perolehan cuti tahunan.</li><li><strong>E</strong>: Lima tahun merupakan syarat masa kerja untuk mengajukan cuti besar.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 60) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Merupakan hak PNS, termasuk CPNS yang telah bekerja secara terus\nmenerus selama 1 (satu) tahun • Hak Cuti Tahunan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan regulasi cuti, Calon Pegawai Negeri Sipil (CPNS) hanya berhak atas...", "options": ["Cuti di luar tanggungan negara selama tiga tahun", "Cuti besar untuk menunaikan ibadah haji", "Cuti tahunan, kecuali ada pertimbangan kemanusiaan", "Cuti tahunan yang diakumulasikan selama sepuluh tahun", "Pembebasan penugasan tanpa izin dari atasan langsung"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pembatasan Hak Cuti bagi CPNS</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>CPNS hanya berhak atas cuti tahunan, kecuali ditentukan lain oleh pejabat yang berwenang memberikan cuti berdasarkan pertimbangan kemanusiaan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena status CPNS masih dalam masa pengujian, sehingga hak cutinya dibatasi hanya pada cuti tahunan. Cuti lain (seperti cuti sakit berat/bersalin) hanya dapat diberikan atas dasar kemanusiaan. Maka dari itu, opsi A benar.</p><p>Jawaban tepat: <strong>C. Cuti tahunan, kecuali ada pertimbangan kemanusiaan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: CLTN mensyaratkan masa kerja PNS minimal 5 tahun terus-menerus.</li><li><strong>B</strong>: Cuti besar mensyaratkan masa kerja PNS minimal 5 tahun terus-menerus.</li><li><strong>D</strong>: Cuti tahunan hanya dapat diakumulasikan maksimal 2 tahun berturut-turut.</li><li><strong>E</strong>: Meninggalkan tugas tanpa izin merupakan pelanggaran disiplin berat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 60) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>CPNS hanya berhak atas cuti tahunan • berdasarkan pertimbangan\nkemanusiaan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Sisa hak cuti tahunan yang tidak digunakan dalam tahun berjalan dapat digunakan pada tahun berikutnya paling banyak...", "options": ["24 (dua puluh empat) hari kerja", "12 (dua belas) hari kerja", "18 (delapan belas) hari kerja", "6 (enam) hari kerja", "30 (tiga puluh) hari kerja"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Maksimal Sisa Cuti yang Dapat Ditransfer ke Tahun Depan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Sisa hak atas cuti tahunan yang tidak digunakan dalam tahun bersangkutan dapat digunakan pada tahun berikutnya paling banyak 6 (enam) hari kerja</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena regulasi menetapkan batas maksimal sisa cuti yang dapat dialihkan ke tahun berikutnya adalah 6 hari kerja (sehingga total cuti tahun berikutnya maksimal 12 + 6 = 18 hari kerja). Jadi, pilihan D adalah jawaban yang tepat.</p><p>Jawaban tepat: <strong>D. 6 (enam) hari kerja</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Dua puluh empat hari kerja adalah total akumulasi jika tidak cuti 2 tahun berturut-turut.</li><li><strong>B</strong>: Dua belas hari kerja adalah hak cuti tahunan normal dalam satu tahun.</li><li><strong>C</strong>: Delapan belas hari kerja adalah total gabungan cuti berjalan dan sisa cuti (12+6).</li><li><strong>E</strong>: Tiga puluh hari bukan angka perhitungan cuti tahunan ASN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 60) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>dapat digunakan pada tahun berikutnya paling banyak\n6 (enam) hari kerja • Sisa hak atas cuti tahunan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Total hak cuti tahunan pada tahun berjalan ditambah sisa cuti tahun sebelumnya yang dapat digunakan adalah paling lama...", "options": ["36 (tiga puluh enam) hari kerja", "6 (enam) hari kerja", "12 (dua belas) hari kerja", "24 (dua puluh empat) hari kerja", "18 (delapan belas) hari kerja"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Maksimal Akumulasi Cuti Tahunan 1 Tahun</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Hak atas cuti tahunan yang tersisa dapat digunakan dalam tahun berikutnya untuk <strong>paling lama 18 (delapan belas) hari kerja termasuk cuti tahunan dalam tahun berjalan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena formulanya adalah hak cuti tahun berjalan (12 hari) ditambah sisa maksimal tahun lalu (6 hari) sehingga total plafon maksimal yang dapat diambil adalah 18 hari kerja. Dengan demikian, opsi A benar.</p><p>Jawaban tepat: <strong>E. 18 (delapan belas) hari kerja</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Tiga puluh enam hari kerja bukan ketentuan akumulasi cuti tahunan ASN.</li><li><strong>B</strong>: Enam hari kerja adalah plafon batas sisa yang boleh dialihkan saja.</li><li><strong>C</strong>: Dua belas hari kerja adalah hak normal satu tahun dinas.</li><li><strong>D</strong>: Dua puluh empat hari kerja berlaku jika tidak digunakan 2 tahun berturut-turut.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 60) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>paling\nlama 18 (delapan belas) hari kerja termasuk cuti tahunan dalam tahun\nberjalan • dapat digunakan dalam tahun berikutnya</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Hak cuti tahunan yang tidak digunakan 2 tahun atau lebih berturut-turut dapat digunakan pada tahun berikutnya paling lama...", "options": ["24 (dua puluh empat) hari kerja", "18 (delapan belas) hari kerja", "12 (dua belas) hari kerja", "30 (tiga puluh) hari kerja", "6 (enam) hari kerja"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Akumulasi Cuti Tidak Digunakan 2 Tahun Berturut-turut</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Hak atas cuti tahunan yang <strong>tidak digunakan 2 (dua) tahun atau lebih berturut-turut, dapat digunakan dalam tahun berikutnya untuk paling lama 24 (dua puluh empat) hari kerja termasuk hak cuti tahun berjalan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena bila pegawai tidak pernah mengambil cuti selama dua tahun berturut-turut, regulasi memberikan hak kompensasi akumulasi tertinggi hingga 24 hari kerja pada tahun berikutnya. Oleh sebab itu, opsi A benar.</p><p>Jawaban tepat: <strong>A. 24 (dua puluh empat) hari kerja</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Delapan belas hari kerja untuk sisa cuti dari 1 tahun sebelumnya.</li><li><strong>C</strong>: Dua belas hari kerja adalah hak standar satu tahun berjalan.</li><li><strong>D</strong>: Tiga puluh hari melebihi batas maksimal regulasi kepegawaian.</li><li><strong>E</strong>: Enam hari kerja adalah batas sisa maksimal dari satu tahun sebelumnya.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 60) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>tidak digunakan 2 (dua) tahun atau lebih\nberturut-turut • paling\nIama 24 (dua puluh empat) hari kerja termasuk</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Karena kepentingan dinas mendesak, hak cuti tahunan dapat ditangguhkan oleh pejabat berwenang untuk paling lama...", "options": ["6 (enam) bulan", "3 (tiga) bulan", "1 (satu) tahun", "2 (dua) tahun", "5 (lima) tahun"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penangguhan Cuti Tahunan oleh Pejabat Berwenang</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Hak atas cuti tahunan <strong>dapat ditangguhkan penggunaannya oleh Pejabat yang Berwenang Memberikan Cuti untuk paling lama 1 (satu) tahun, apabila terdapat kepentingan dinas mendesak</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena penangguhan cuti dinas dibatasi maksimal 1 tahun kalender dinas. Hak cuti yang ditangguhkan ini tidak hangus dan dapat diambil secara penuh pada tahun berikutnya. Jadi, pilihan C adalah jawaban yang tepat.</p><p>Jawaban tepat: <strong>C. 1 (satu) tahun</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Enam bulan bukan batas penangguhan yang ditetapkan dalam Perka BKN 24/2017.</li><li><strong>B</strong>: Tiga bulan bukan batas waktu penangguhan maksimal regulasi cuti.</li><li><strong>D</strong>: Dua tahun terlalu lama dan merugikan hak kesehatan kerja pegawai.</li><li><strong>E</strong>: Lima tahun merupakan batas syarat pengajuan cuti besar.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 60) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>ditangguhkan penggunaannya oleh\nPejabat yang Berwenang Memberikan Cuti • paling lama 1 (satu)\ntahun</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C3 - Aplikasi", "question": "Seorang pegawai perbendaharaan memerlukan izin cuti untuk keperluan keluarga mendesak selama 1 hari kerja. Permohonan ini...", "options": ["Dikenakan pemotongan tunjangan kinerja sebesar 25 persen", "Wajib ditolak karena cuti tahunan harus diambil minimal 5 hari kerja", "Wajib diganti menjadi cuti di luar tanggungan negara", "Dapat disetujui karena batas minimal cuti tahunan adalah 1 hari kerja", "Dapat diproses apabila pegawai telah memiliki masa kerja minimal 5 tahun"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penerapan Batas Minimal Pengambilan Cuti</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Penggunaan cuti tahunan dapat diberikan untuk paling kurang 1 (satu) hari kerja</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena regulasi menetapkan batas minimal penggunaan hak cuti tahunan adalah tepat 1 hari kerja, sehingga permohonan cuti 1 hari adalah sah dan dapat dikabulkan oleh atasan langsung. Dengan demikian, opsi A tepat.</p><p>Jawaban tepat: <strong>D. Dapat disetujui karena batas minimal cuti tahunan adalah 1 hari kerja</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Cuti tahunan yang sah tidak memotong tunjangan kinerja pegawai.</li><li><strong>B</strong>: Tidak ada kewajiban mengambil cuti minimal 5 hari kerja.</li><li><strong>C</strong>: CLTN bukan untuk keperluan 1 hari melainkan minimal 1 tahun.</li><li><strong>E</strong>: Kurang tepat, syarat minimal masa kerja cuti tahunan adalah 1 tahun kerja terus-menerus, bukan 5 tahun.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 60) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Penggunaan cuti tahunan dapat diberikan untuk paling kurang 1 (satu) hari\nkerja</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C3 - Aplikasi", "question": "Pada tahun 2025 seorang pegawai masih memiliki sisa hak cuti 9 hari kerja. Sisa cuti yang dapat dipindahkan ke tahun 2026 adalah...", "options": ["Dikonversi menjadi uang lembur tunai", "Penuh seluruhnya 9 hari kerja", "Paling banyak 3 hari kerja", "Tidak dapat dipindahkan dan hangus", "Paling banyak 6 hari kerja"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Implementasi Perhitungan Sisa Cuti Tahunan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sisa cuti tahunan tahun berjalan yang dapat digunakan pada tahun berikutnya <strong>dibatasi paling banyak 6 (enam) hari kerja</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena meskipun pegawai memiliki sisa 9 hari, ketentuan membatasi hak pengalihan maksimal hanya 6 hari kerja. Sisa 3 hari lainnya dinyatakan gugur/hangus. Maka dari itu, opsi A benar.</p><p>Jawaban tepat: <strong>E. Paling banyak 6 hari kerja</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Cuti tahunan yang tidak terpakai tidak dapat diuangkan dalam sistem ASN.</li><li><strong>B</strong>: Tidak dapat dipindahkan seluruh 9 hari karena melebihi plafon maksimal 6 hari.</li><li><strong>C</strong>: Tiga hari adalah sisa yang hangus, bukan hak maksimal yang dipindahkan.</li><li><strong>D</strong>: Sisa cuti tidak hangus seluruhnya melainkan dialihkan maksimal 6 hari.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 60) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Sisa hak atas cuti tahunan yang tidak digunakan • paling banyak\n6 (enam) hari kerja</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C3 - Aplikasi", "question": "Karena penugasan proyek strategis, staf tidak pernah cuti selama tahun 2024 dan 2025. Pada tahun 2026, ia berhak mengambil cuti maksimal...", "options": ["24 hari kerja termasuk cuti tahun berjalan", "36 hari kerja penuh tanpa potongan tunjangan", "12 hari kerja saja seperti tahun normal", "6 hari kerja dari akumulasi masa lalu", "48 hari kerja dengan dispensasi kementerian"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Implementasi Plafon Cuti 2 Tahun Tidak Cuti</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai yang tidak cuti 2 tahun berturut-turut berhak atas cuti tahun berikutnya <strong>paling lama 24 hari kerja termasuk cuti tahun berjalan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena regulasi memberikan plafon khusus sebesar 24 hari kerja (12 hari hak berjalan + 12 hari kompensasi 2 tahun tidak cuti) sebagai batas tertinggi cuti tahunan gabungan. Oleh sebab itu, opsi A benar.</p><p>Jawaban tepat: <strong>A. 24 hari kerja termasuk cuti tahun berjalan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Tiga puluh enam hari melebihi batas maksimal akumulasi 24 hari kerja.</li><li><strong>C</strong>: Bukan 12 hari karena pegawai berhak atas akumulasi kompensasi 2 tahun.</li><li><strong>D</strong>: Enam hari kerja hanya untuk sisa 1 tahun sebelumnya.</li><li><strong>E</strong>: Empat puluh delapan hari tidak memiliki dasar hukum dalam regulasi cuti.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 60) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>tidak digunakan 2 (dua) tahun atau lebih\nberturut-turut • paling\nIama 24 (dua puluh empat) hari kerja</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C4 - Analisis", "question": "Prinsip utama yang mendasari pemberian kewenangan penangguhan cuti tahunan kepada pimpinan satker adalah...", "options": ["Menghemat kas pengeluaran belanja pegawai akhir tahun", "Menjamin kelangsungan fungsi dinas saat ada kebutuhan mendesak", "Mengatur pemerataan beban kerja pegawai pada masa puncak pelayanan publik", "Mewajibkan pegawai bekerja penuh tanpa batas istirahat", "Mendorong penyelesaian target kinerja program strategis unit kerja tepat waktu"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Analisis Kewenangan Penangguhan Cuti</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Penangguhan cuti hanya dapat dilakukan <strong>apabila terdapat kepentingan dinas mendesak</strong> demi menjaga pelayanan publik.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena penangguhan cuti bukanlah bentuk hukuman, melainkan instrumen manajerial untuk memastikan operasional pelayanan publik dan target mendesak negara tidak terganggu saat terjadi kekurangan personel. Dengan demikian, opsi A adalah simpulan analisis yang tepat.</p><p>Jawaban tepat: <strong>B. Menjamin kelangsungan fungsi dinas saat ada kebutuhan mendesak</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Cuti tahunan tidak mengurangi atau menambah belanja modal anggaran satker.</li><li><strong>C</strong>: Kurang tepat, pemerataan beban kerja merupakan fungsi manajerial rutin operasional satker.</li><li><strong>D</strong>: Hak cuti tetap dijamin dan dapat diambil penuh pada tahun berikutnya.</li><li><strong>E</strong>: Kurang tepat, pemenuhan target program strategis dicapai melalui perencanaan kerja terpadu.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 60) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pejabat yang Berwenang Memberikan Cuti • apabila terdapat kepentingan dinas mendesak</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Hak cuti besar merupakan hak bagi PNS yang telah bekerja secara terus-menerus paling kurang selama...", "options": ["5 (lima) tahun", "1 (satu) tahun", "3 (tiga) tahun", "7 (tujuh) tahun", "10 (sepuluh) tahun"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Syarat Masa Kerja Hak Cuti Besar</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Hak Cuti Besar merupakan hak PNS yang <strong>telah bekerja paling kurang 5 (lima) tahun secara terus menerus</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena regulasi cuti PNS menetapkan masa pengabdian minimal 5 tahun kerja tanpa jeda sebagai prasyarat utama sebelum pegawai berhak mengajukan cuti besar (durasi hingga 3 bulan). Oleh karena itu, opsi A benar.</p><p>Jawaban tepat: <strong>A. 5 (lima) tahun</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Satu tahun merupakan syarat untuk memperoleh cuti tahunan normal.</li><li><strong>C</strong>: Tiga tahun belum memenuhi syarat minimal 5 tahun cuti besar.</li><li><strong>D</strong>: Tujuh tahun melebihi ambang batas minimal yang dipersyaratkan aturan.</li><li><strong>E</strong>: Sepuluh tahun merupakan syarat penganugerahan Satyalancana Karya Satya perunggu.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 61) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>telah bekerja paling kurang 5 (lima) tahun\nsecara terus menerus • Hak Cuti Besar</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Di antara tujuan sah penggunaan cuti besar oleh Pegawai Negeri Sipil adalah untuk...", "options": ["Mengikuti program pelatihan sertifikasi kompetensi profesi mandiri", "Bekerja paruh waktu pada perusahaan swasta asing", "Menjadi tim sukses kampanye pasangan calon bupati", "Membuka usaha perdagangan mandiri di luar negeri", "Memenuhi kewajiban agama seperti ibadah haji"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuan Penggunaan Cuti Besar</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Cuti besar dapat digunakan oleh PNS antara lain untuk: <strong>memenuhi kewajiban agama</strong>, persalinan anak keempat, atau keperluan lain pertimbangan pejabat berwenang.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena penunaian kewajiban ibadah keagamaan (seperti ibadah haji yang memakan waktu lebih dari satu bulan) merupakan salah satu peruntukan utama cuti besar yang diatur dokumen kepegawaian. Maka dari itu, opsi A benar.</p><p>Jawaban tepat: <strong>E. Memenuhi kewajiban agama seperti ibadah haji</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat, pelatihan pengembangan kompetensi mandiri tidak termasuk alasan utama pemberian hak cuti besar.</li><li><strong>B</strong>: Bekerja di perusahaan swasta dilarang bagi aparatur sipil negara aktif.</li><li><strong>C</strong>: Terlibat kampanye politik praktis melanggar asas netralitas ASN.</li><li><strong>D</strong>: Membuka bisnis komersial di luar negeri bukan peruntukan sah cuti besar.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 61) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Memenuhi kewajiban agama • Cuti besar dapat digunakan oleh PNS untuk</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C2 - Pemahaman", "question": "PNS wanita dapat menggunakan hak cuti besar untuk persalinan anaknya yang...", "options": ["Pertama setelah pengangkatan menjadi calon pegawai", "Keempat dan seterusnya apabila memiliki hak cuti besar", "Kedua dengan persetujuan rekan kerja satu seksi", "Ketiga dengan memotong gaji pokok selama tiga bulan", "Tunggal tanpa memerlukan surat keterangan persalinan"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Cuti Besar untuk Persalinan Anak Keempat</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Cuti besar dapat digunakan untuk <strong>persalinan anaknya yang keempat apabila PNS yang bersangkutan mempunyai hak cuti besar menjelang persalinan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena untuk persalinan anak pertama, kedua, dan ketiga diwadahi melalui Cuti Bersalin (Cuti Melahirkan). Sedangkan persalinan anak keempat dialihkan menggunakan hak Cuti Besar jika memenuhi syarat masa kerja 5 tahun. Jadi, pilihan B benar.</p><p>Jawaban tepat: <strong>B. Keempat dan seterusnya apabila memiliki hak cuti besar</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Persalinan anak pertama menggunakan hak Cuti Melahirkan (Cuti Bersalin).</li><li><strong>C</strong>: Persalinan anak kedua menggunakan hak Cuti Melahirkan (Cuti Bersalin).</li><li><strong>D</strong>: Persalinan anak ketiga menggunakan Cuti Bersalin, bukan Cuti Besar.</li><li><strong>E</strong>: Surat keterangan persalinan dokter/bidan wajib dilampirkan mutlak.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 61) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Persalinan anaknya yang keempat • mempunyai hak cuti besar menjelang persalinan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C2 - Pemahaman", "question": "PNS yang telah melaksanakan cuti tahunan lalu mengambil cuti besar pada tahun yang sama wajib...", "options": ["Menyerahkan kembali seluruh tanda kehormatan negara", "Membayar denda administrasi sebesar satu bulan gaji", "Mengajukan pengunduran diri dari jabatan struktural", "Mengembalikan TKPKN yang diterima selama cuti tahunan", "Menjalani pemeriksaan disiplin oleh inspektorat jenderal"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Ketentuan Pengembalian TKPKN Cuti Tahunan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>PNS yang telah melaksanakan cuti tahunan dan akan mengambil cuti besar pada tahun yang bersangkutan <strong>harus mengembalikan TKPKN yang diterimanya selama melaksanakan cuti tahunan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena aturan kementerian melarang duplikasi tunjangan cuti dalam satu tahun dinas. Jika cuti besar diambil setelah cuti tahunan, maka TKPKN (tunjangan khusus/kinerja) cuti tahunan sebelumnya wajib disetor kembali ke kas negara. Dengan demikian, opsi A benar.</p><p>Jawaban tepat: <strong>D. Mengembalikan TKPKN yang diterima selama cuti tahunan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Tanda kehormatan tidak berkaitan dengan prosedur administrasi cuti.</li><li><strong>B</strong>: Bukan denda pelanggaran melainkan penyesuaian hak administrasi tunjangan.</li><li><strong>C</strong>: Pengambilan cuti besar yang sah tidak mewajibkan mundur dari jabatan.</li><li><strong>E</strong>: Bukan pelanggaran disiplin yang diperiksa oleh inspektorat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 61) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>harus mengembalikan TKPKN\nyang diterimanya selama melaksanakan cuti tahunan • telah melaksanakan cuti tahunan dan akan mengambil cuti\nbesar</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Hak cuti sakit diberikan kepada PNS yang menderita sakit serta PNS/CPNS wanita yang...", "options": ["Mengikuti kegiatan retret keagamaan", "Menjalani operasi kecantikan mandiri", "Mengalami gugur kandungan", "Menemani suami dinas ke luar daerah", "Melakukan wisata kesehatan ke luar negeri"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Subjek Penerima Hak Cuti Sakit</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Hak Cuti Sakit merupakan hak PNS yang menderita sakit dan/atau PNS/CPNS wanita yang mengalami gugur kandungan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena kondisi medis gugur kandungan (keguguran) secara sah disetarakan dengan kondisi sakit medis yang memerlukan masa pemulihan fisik dan psikis melalui hak Cuti Sakit. Maka dari itu, opsi A adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>C. Mengalami gugur kandungan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Retret keagamaan menggunakan hak cuti tahunan atau izin dinas tertentu.</li><li><strong>B</strong>: Operasi estetika nonmedis tidak berhak atas cuti sakit kepegawaian.</li><li><strong>D</strong>: Menemani suami penugasan diwadahi melalui Cuti Alasan Penting / CLTN.</li><li><strong>E</strong>: Wisata kesehatan menggunakan hak cuti tahunan, bukan cuti sakit resmi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 61) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Hak Cuti Sakit merupakan hak PNS yang menderita sakit • wanita yang mengalami gugur kandungan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C2 - Pemahaman", "question": "PNS yang sakit selama 1 sampai dengan 14 hari wajib menyampaikan surat permohonan dengan melampirkan...", "options": ["Bukti pelunasan pajak bumi dan bangunan", "Surat keterangan dokter", "Kuitansi pembelian vitamin di apotek swasta", "Surat rekomendasi dari kepala desa setempat", "Salinan ijazah pendidikan formal terakhir"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Syarat Cuti Sakit 1-14 Hari Kerja</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>PNS yang sakit 1 s.d. 14 hari menyampaikan surat keterangan sakit tertulis kepada atasan langsung dengan <strong>melampirkan surat keterangan dokter</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena keabsahan medis atas ketidakhadiran kerja karena sakit dibuktikan secara formal melalui surat keterangan resmi dari dokter yang memiliki izin praktik sah. Oleh sebab itu, opsi A benar.</p><p>Jawaban tepat: <strong>B. Surat keterangan dokter</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pajak bumi dan bangunan tidak relevan dengan kondisi kesehatan kerja pegawai.</li><li><strong>C</strong>: Kuitansi pembelian obat bukan bukti medis kelayakan istirahat sakit.</li><li><strong>D</strong>: Kepala desa bukan tenaga medis yang berwenang menerbitkan surat sakit.</li><li><strong>E</strong>: Ijazah pendidikan tidak berkaitan dengan verifikasi medis cuti sakit.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 61) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>sakit 1 (satu) hari sampai dengan 14 (empat belas) hari • melampirkan surat keterangan dokter</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C3 - Aplikasi", "question": "Seorang PNS dengan masa kerja 8 tahun terpilih berangkat haji selama 40 hari. Jenis cuti yang tepat diajukan adalah...", "options": ["Cuti tahunan yang diambil secara terputus-putus", "Cuti sakit berkepanjangan dengan opname", "Cuti di luar tanggungan negara selama 3 tahun", "Cuti besar untuk memenuhi kewajiban agama", "Izin meninggalkan kantor tanpa surat resmi"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penerapan Cuti Besar Ibadah Haji</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>PNS dengan masa kerja minimal 5 tahun berhak menggunakan <strong>cuti besar untuk memenuhi kewajiban agama</strong> (seperti ibadah haji) hingga 3 bulan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena masa dinas telah mencapai 8 tahun (>5 tahun) dan durasi ibadah haji (40 hari) melampaui plafon cuti tahunan, sehingga tepat diwadahi melalui Cuti Besar keagamaan. Jadi, pilihan D adalah tindakan yang benar.</p><p>Jawaban tepat: <strong>D. Cuti besar untuk memenuhi kewajiban agama</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Plafon cuti tahunan maksimal hanya 12-18 hari, tidak mencukupi 40 hari.</li><li><strong>B</strong>: Pegawai dalam kondisi sehat walafiat, tidak berhak atas cuti sakit.</li><li><strong>C</strong>: CLTN membekukan gaji dan status aktif, tidak tepat untuk ibadah haji sah.</li><li><strong>E</strong>: Meninggalkan tugas tanpa surat resmi merupakan pelanggaran disiplin berat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 61) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Memenuhi kewajiban agama • telah bekerja paling kurang 5 (lima) tahun</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C3 - Aplikasi", "question": "Pada bulan Maret pegawai mengambil cuti tahunan 4 hari dengan TKPKN penuh. Bulan Agustus ia mengambil cuti besar. Pegawai wajib...", "options": ["Mengganti hari cuti besar menjadi jadwal kerja lembur malam", "Membayar ganti rugi pemakaian aset kantor selama satu tahun", "Mengembalikan TKPKN 4 hari yang diterima saat cuti tahunan", "Menyerahkan separuh gaji pokok kepada atasan langsung", "Menerima sanksi teguran tertulis dari pejabat kepegawaian"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penerapan Kewajiban Penyetoran Kembali TKPKN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>PNS yang telah cuti tahunan lalu mengambil cuti besar pada tahun yang sama <strong>wajib mengembalikan TKPKN yang telah diterimanya selama cuti tahunan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena ketentuan mengharuskan pengembalian tunjangan kinerja (TKPKN) yang telah dicairkan atas 4 hari cuti tahunan terdahulu agar tidak terjadi tumpang tindih hak remunerasi cuti besar. Dengan demikian, opsi A tepat.</p><p>Jawaban tepat: <strong>C. Mengembalikan TKPKN 4 hari yang diterima saat cuti tahunan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Cuti besar adalah hak istirahat sah, bukan utang jam lembur.</li><li><strong>B</strong>: Bukan ganti rugi aset kantor melainkan penyesuaian administrasi tunjangan.</li><li><strong>D</strong>: Gaji pokok tidak dipotong dan dilarang diserahkan kepada atasan.</li><li><strong>E</strong>: Pengambilan cuti sah bukan pelanggaran yang dijatuhi sanksi disiplin.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 61) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>harus mengembalikan TKPKN\nyang diterimanya selama melaksanakan cuti tahunan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C4 - Analisis", "question": "PNS yang sedang atau telah menggunakan hak cuti besar tetap berhak atas fasilitas cuti berikut, KECUALI...", "options": ["Cuti tambahan bebas tugas seumur hidup", "Cuti tahunan sisa tahun sebelumnya", "Cuti sakit apabila menderita sakit", "Cuti bersalin untuk anak pertama s.d. ketiga", "Cuti bersama resmi pemerintah"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Analisis Hak-Hak yang Tetap Melekat Saat Cuti Besar</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>PNS cuti besar tetap berhak atas: <strong>1) cuti bersama; 2) cuti tahunan sisa tahun sebelum; 3) cuti sakit; 4) cuti bersalin anak 1-3; 5) cuti alasan penting</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat sebagai pengecualian karena bebas tugas seumur hidup tidak dikenal dalam sistem manajemen aparatur negara dan bertentangan dengan kewajiban dinas. Kelima hak lainnya secara eksplisit dijamin tetap melekat pada pegawai penerima cuti besar. Oleh karena itu, opsi E benar.</p><p>Jawaban tepat: <strong>A. Cuti tambahan bebas tugas seumur hidup</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan hak sah yang tetap melekat pada penerima cuti besar.</li><li><strong>C</strong>: Merupakan hak sah yang tetap melekat pada penerima cuti besar.</li><li><strong>D</strong>: Merupakan hak sah yang tetap melekat pada penerima cuti besar.</li><li><strong>E</strong>: Merupakan hak sah yang tetap melekat pada penerima cuti besar.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 61) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>PNS yang akan/telah menggunakan cuti besar berhak atas: • cuti bersama;</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 05", "difficulty": "C4 - Analisis", "question": "Konstruksi pembedaan antara Cuti Bersalin dan Cuti Besar terkait persalinan dalam regulasi kepegawaian didasarkan pada...", "options": ["Tingkat kelulusan nilai indeks prestasi kumulatif saat kuliah", "Jenis kelamin anak yang dilahirkan apakah laki-laki atau perempuan", "Lokasi rumah sakit bersalin apakah di dalam atau luar negeri", "Pangkat dan golongan ruang kepegawaian suami yang bersangkutan", "Urutan kelahiran anak, di mana anak keempat dialihkan ke cuti besar"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Analisis Pengelompokan Cuti Persalinan vs Cuti Besar</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Persalinan anak <strong>pertama, kedua, dan ketiga</strong> menggunakan <strong>Cuti Bersalin</strong>, sedangkan persalinan anak <strong>keempat</strong> menggunakan hak <strong>Cuti Besar</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena kebijakan kependudukan dan cuti aparatur membatasi fasilitas Cuti Bersalin khusus hingga anak ketiga. Kelahiran anak keempat dan seterusnya diakomodasi melalui hak Cuti Besar apabila memenuhi syarat masa kerja minimal 5 tahun. Maka dari itu, opsi A adalah analisis yang tepat.</p><p>Jawaban tepat: <strong>E. Urutan kelahiran anak, di mana anak keempat dialihkan ke cuti besar</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Prestasi akademik masa lalu tidak berkaitan dengan hak persalinan.</li><li><strong>B</strong>: Jenis kelamin bayi tidak mempengaruhi hak cuti persalinan.</li><li><strong>C</strong>: Lokasi fasilitas kesehatan tidak mengubah nomenklatur hak cuti.</li><li><strong>D</strong>: Pangkat suami tidak menjadi penentu hak cuti persalinan istri ASN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 61) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>cuti bersalin untuk persalinan anaknya yang pertama, kedua, dan\nketiga • cuti besar menjelang persalinan</strong></div></div>"}];
let current=0;const answers=new Array(questions.length).fill(null);const letters=["A","B","C","D","E"];let seconds=65*60;let submitted=false;


// Anotasi hanya dalam pembahasan. Satu set coretan untuk setiap nomor soal.
const ANNO_KEY='stangolden_tak_p05_review_annot_v1_user_<?= (int)$USER->id ?>';
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
 try{localStorage.setItem('stg_tak_p05_map_visible',show?'1':'0')}catch(e){}
}
try{if(localStorage.getItem('stg_tak_p05_map_visible')==='0')toggleQuestionMap(false)}catch(e){}
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

  if(modalTitle) modalTitle.textContent="🏆 Lihat Ranking Tata Aturan Kepegawaian Paket 05";
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
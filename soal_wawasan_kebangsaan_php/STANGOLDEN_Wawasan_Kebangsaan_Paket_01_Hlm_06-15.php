<?php
/**
 * STANGOLDEN.exam • Wawasan Kebangsaan Paket 01 (Halaman 06–15)
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
// $DB->get_record("stgwk_p01_rank") => tabel fisik "mdlhv_stgwk_p01_rank".
// Jangan menambahkan prefix secara manual di konstanta berikut.
// Penyimpanan bersama melalui database Moodle. Nama tabel pendek, kompatibel dengan batas Moodle.
const STG_LB_T = 'stgwk_p01_rank';
const STG_REVIEW_T = 'stgwk_p01_rev';
const STG_ANNO_T = 'stgwk_p01_anno';
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
            error_log('STANGOLDEN WK P01 HLM06-15 SETUP: ' . $e->getMessage());
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
    }catch(Throwable $e){error_log('STANGOLDEN WK P01 HLM06-15: '.$e->getMessage());stgtak_json(['success'=>false,'message'=>($e instanceof RuntimeException ? $e->getMessage() : 'Penyimpanan belum tersedia. Hubungi administrator.')],500);}
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
<title>Latihan Wawasan Kebangsaan Paket 01 (Hlm. 06–15) • STANGOLDEN</title>
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
      <span class="series-tag">Paket 01 • Hlm. 06–15</span>
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
<h1>Latihan Nilai-Nilai Kemenkeu <span class="series-tag">Paket 01 • Hlm. 06–15</span></h1>
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
        <h2 class="leaderboard-title" id="rankingModalTitle">🏆 Lihat Ranking Wawasan Kebangsaan Paket 01</h2>
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
const STG_LOCAL_KEY="stg_wk_p01_review_user_<?= (int)$USER->id ?>";
const HAS_FULL_REVIEW=true;
const IS_SITE_ADMIN=<?= is_siteadmin($USER) ? "true" : "false" ?>;
const LEADERBOARD_ENDPOINT=window.location.href.split("?")[0].split("#")[0];
const questions=[{"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Pembukaan UUD 1945 menegaskan bahwa tujuan didirikannya Negara Republik Indonesia antara lain adalah...", "options": ["Memajukan kesejahteraan umum dan mencerdaskan kehidupan bangsa", "Menciptakan stabilitas ekonomi moneter secara terpusat", "Menjalankan hubungan diplomatik bilateral secara mandiri", "Mengembangkan sistem pertahanan terpadu di kawasan regional", "Mewujudkan tata kelola kelembagaan birokrasi peradilan modern"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuan Pendirian Negara Republik Indonesia</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Tujuan didirikannya Negara Republik Indonesia sebagaimana tercantum dalam <strong>Pembukaan UUD 1945</strong> antara lain adalah untuk <strong>memajukan kesejahteraan umum dan mencerdaskan kehidupan bangsa</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena Pembukaan UUD 1945 secara eksplisit memuat amanat bahwa tujuan negara adalah melindungi segenap bangsa Indonesia, memajukan kesejahteraan umum, serta mencerdaskan kehidupan bangsa. Amanat ini menjadi landasan moral dan operasional seluruh penyelenggaraan pemerintahan negara. Pilihan lain merujuk pada fungsi teknis pemerintahan biasa yang tidak termaktub dalam teks pokok pendirian negara.</p><p>Jawaban tepat: <strong>A. Memajukan kesejahteraan umum dan mencerdaskan kehidupan bangsa</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan tugas teknis otoritas fiskal dan moneter, bukan tujuan pokok pendirian negara.</li><li><strong>C</strong>: Merupakan instrumen politik luar negeri, bukan rumusan tujuan bernegara pada pembukaan konstitusi.</li><li><strong>D</strong>: Merupakan ranah operasional pertahanan keamanan, bukan tujuan luhur berdirinya negara.</li><li><strong>E</strong>: Merupakan agenda reformasi kelembagaan peradilan, bukan rumusan tujuan pokok negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 06) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>memajukan kesejahteraan umum dan mencerdaskan kehidupan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Kewajiban negara memenuhi hak warga negara diwujudkan melalui sistem pemerintahan yang mendukung...", "options": ["Pemberian bantuan finansial langsung kepada masyarakat", "Penyelenggaraan pelayanan publik yang prima", "Penyusunan regulasi kelembagaan politik secara berkala", "Pelaksanaan sentralisasi wewenang pemerintahan daerah", "Pembentukan lembaga pemantau kinerja aparatur sipil"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kewajiban Negara dalam Pelayanan Publik</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Amanat pembukaan konstitusi mewajibkan negara memenuhi kebutuhan setiap warga negara melalui sistem pemerintahan yang mendukung <strong>terciptanya penyelenggaraan pelayanan publik yang prima</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena negara berkewajiban hadir melayani warga melalui sistem birokrasi yang mampu memberikan pelayanan prima. Pelayanan publik yang berkualitas tinggi menjamin hak konstitusional warga negara terpenuhi secara adil dan merata. Pilihan lain tidak mencerminkan sarana sistemik utama pemenuhan kewajiban negara yang ditegaskan dalam fondasi tata kelola pemerintahan.</p><p>Jawaban tepat: <strong>B. Penyelenggaraan pelayanan publik yang prima</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Bantuan finansial merupakan salah satu program jaminan sosial temporer, bukan sistem pemerintahan utama.</li><li><strong>C</strong>: Penyusunan regulasi politis adalah fungsi legislasi berkala, bukan sarana langsung pemenuhan hak sipil.</li><li><strong>D</strong>: Sentralisasi wewenang bertentangan dengan prinsip otonomi daerah dan desentralisasi pelayanan publik.</li><li><strong>E</strong>: Pembentukan komisi pengawas adalah fungsi audit kelembagaan, bukan inti sistem penyelenggaraan pelayanan prima.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 06) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>penyelenggaraan pelayanan publik yang prima</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Penyelenggaraan pelayanan publik prima dalam tata kelola birokrasi pemerintah diarahkan untuk memenuhi...", "options": ["Target perolehan pendapatan asli daerah tahunan", "Struktur hierarki kepangkatan pegawai aparatur negara", "Kebutuhan dasar dan hak sipil setiap warga negara", "Kebutuhan logistik operasional aparatur penegak hukum", "Standar kelayakan sarana prasarana fisik perkantoran"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuan Pelayanan Publik Prima</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Penyelenggaraan pelayanan publik prima dijalankan dalam rangka memenuhi <strong>kebutuhan dasar dan hak sipil setiap warga negara</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena orientasi tertinggi pelayanan publik aparatur sipil negara adalah terwujudnya pemenuhan hak-hak sipil serta kebutuhan mendasar masyarakat. Standar pelayanan prima bukan sekadar formalitas internal kantor, melainkan mandat konstitusional perlindungan warga. Pilihan lain berfokus pada kepentingan administratif atau fasilitas dinas internal instansi pemerintah.</p><p>Jawaban tepat: <strong>C. Kebutuhan dasar dan hak sipil setiap warga negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Perolehan pendapatan daerah adalah fungsi fiskal daerah, bukan sasaran akhir pemenuhan hak warga.</li><li><strong>B</strong>: Jenjang kepangkatan aparatur adalah tata kelola manajemen kepegawaian internal organisasi.</li><li><strong>D</strong>: Pengadaan logistik aparat penegak hukum adalah urusan belanja barang dinas keamanan.</li><li><strong>E</strong>: Sarana perkantoran adalah fasilitas penunjang kerja kedinasan, bukan sasaran esensial hak warga.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 06) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>kebutuhan dasar dan hak sipil setiap warga negara.</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Catatan historis perjalanan bangsa sejak proklamasi kemerdekaan menunjukkan bahwa penyelenggaraan pemerintahan masih...", "options": ["Bebas dari segala bentuk gesekan antargolongan", "Mengalami pergantian bentuk negara secara berkala", "Didominasi oleh kekuatan militer secara permanen", "Diwarnai banyak kemelut politik pengganggu stabilitas", "Berjalan tanpa perubahan regulasi ketatanegaraan nasional"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dinamika Politik Pascakemerdekaan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Perjalanan hidup bangsa sejak Proklamasi 17 Agustus 1945 menunjukkan dinamika di mana penyelenggaraan pemerintahan negara masih <strong>diwarnai banyak kemelut politik yang sangat mengganggu stabilitas nasional</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena refleksi historis perjalanan Republik Indonesia sejak proklamasi kemerdekaan menunjukkan adanya dinamika politik yang tinggi, di mana benturan politik elit kerap menimbulkan keguncangan stabilitas nasional. Kesadaran atas realitas ini penting untuk memelihara kedewasaan berdemokrasi. Pilihan lain tidak sesuai dengan fakta perjalanan sejarah politik ketatanegaraan Indonesia.</p><p>Jawaban tepat: <strong>D. Diwarnai banyak kemelut politik pengganggu stabilitas</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Sejarah membuktikan bangsa mengalami berbagai peristiwa gesekan politik dan sosial internal.</li><li><strong>B</strong>: Bentuk negara kesatuan tetap dipertahankan sejak proklamasi dan dekrit, bukan berganti terus-menerus.</li><li><strong>C</strong>: Indonesia menganut demokrasi konstitusional dan supremasi sipil, bukan rezim militer permanen.</li><li><strong>E</strong>: Sistem ketatanegaraan Indonesia telah melewati beberapa fase dinamika konstitusi dan regulasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 06) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>diwarnai banyak kemelut politik yang sangat</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Benturan politik pada tataran elit akibat perbedaan visi kenegaraan dapat berakibat pada...", "options": ["Peningkatan cadangan devisa perdagangan antarwilayah", "Pemantapan nilai kearifan lokal di daerah pedesaan", "Penyederhanaan regulasi izin usaha skala mikro", "Keseimbangan pertumbuhan industri manufaktur domestik", "Menurunnya kadar hubungan sosial masyarakat bawah"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dampak Benturan Politik Elit terhadap Masyarakat</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Benturan politik tataran elit akibat perbedaan visi dengan mudah merambah tata kehidupan bawah dan berpengaruh terhadap <strong>menurunnya kadar hubungan sosial masyarakat</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena friksi politik elit kerap terpolarisasi ke lapisan akar rumput sehingga merusak kohesi sosial dan persaudaraan sesama warga masyarakat. Kondisi ini menjadi ancaman serius bagi ketahanan sosial bangsa. Pilihan lain menyajikan kondisi ekonomi atau administratif yang tidak relevan dengan dampak langsung benturan politik.</p><p>Jawaban tepat: <strong>E. Menurunnya kadar hubungan sosial masyarakat bawah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Konflik politik cenderung mengganggu kepastian iklim usaha dan perdagangan nasional.</li><li><strong>B</strong>: Benturan elit justru merapuhkan kohesi kultural masyarakat jika tidak diredam dengan bijak.</li><li><strong>C</strong>: Polarisasi politik elit menghambat proses pembahasan kebijakan kemudahan berusaha daerah.</li><li><strong>D</strong>: Ketidakstabilan politik justru menurunkan kepercayaan investor pada sektor industri manufaktur.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 06) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menurunnya kadar hubungan sosial masyarakat.</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Segmentasi masyarakat akibat gesekan kepentingan politik kenegaraan berisiko mengakibatkan...", "options": ["Kondisi persatuan dan kesatuan bangsa semakin longgar", "Pembentukan sistem satu partai dalam parlemen negara", "Meningkatnya partisipasi masyarakat dalam pemilu kepala daerah", "Penguatan identitas kedaerahan di atas kepentingan nasional", "Pemberlakuan darurat militer di seluruh wilayah kepulauan"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dampak Segmentasi Sosial Masyarakat</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Akibat segmentasi masyarakat yang terjadi, <strong>kondisi persatuan dan kesatuan bangsa menjadi semakin longgar</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena perpecahan kelompok dalam masyarakat mengikis ikatan kebangsaan dan melonggarkan integrasi nasional yang telah diperjuangkan sejak kemerdekaan. Segmentasi ini melemahkan rasa senasib sepenanggungan antarwarga. Pilihan lain bukan dampak sosiologis primer yang ditegaskan dalam telaah kebangsaan tersebut.</p><p>Jawaban tepat: <strong>A. Kondisi persatuan dan kesatuan bangsa semakin longgar</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Sistem kepartaian diatur dalam undang-undang pemilu dan menganut multipartai sederhana.</li><li><strong>C</strong>: Segmentasi destruktif biasanya memicu apatisme atau polarisasi ekstrem, bukan partisipasi sehat.</li><li><strong>D</strong>: Penguatan kedaerahan berlebihan adalah bentuk etnosentrisme yang mencederai persatuan nasional.</li><li><strong>E</strong>: Darurat militer merupakan status hukum khusus presiden, bukan konsekuensi sosiologis langsung.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 06) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>kesatuan bangsa menjadi semakin longgar.</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Hambatan terbesar yang ditimbulkan oleh benturan kepentingan politik dalam berbangsa adalah terganggunya upaya...", "options": ["Penyelarasan batas daerah otonom pemekaran baru", "Mempercepat proses peningkatan kesejahteraan rakyat", "Penyeragaman kurikulum pendidikan umum di daerah", "Penataan ulang nomenklatur kementerian koordinator", "Pembangunan pos lintas batas negara di perbatasan"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Hambatan Pembangunan Akibat Konflik Politik</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Benturan-benturan kepentingan politik dapat sangat menghambat kemajuan bangsa, terutama dalam upaya <strong>mempercepat proses peningkatan kesejahteraan rakyat</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena energi bangsa yang terkuras untuk pertikaian politik elit mengalihkan fokus dan alokasi sumber daya dari pembangunan kesejahteraan rakyat. Akibatnya rakyat miskin menanggung beban keterlambatan kemajuan ekonomi nasional. Pilihan lain merupakan urusan birokrasi sektoral yang tidak mewakili dampak makro penghambatan kesejahteraan umum.</p><p>Jawaban tepat: <strong>B. Mempercepat proses peningkatan kesejahteraan rakyat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penyelarasan batas daerah adalah aspek batas wilayah teknis pada kementerian dalam negeri.</li><li><strong>C</strong>: Pendidikan nasional menghargai keberagaman kearifan lokal tanpa pemaksaan seragam kaku.</li><li><strong>D</strong>: Nomenklatur kementerian adalah hak prerogatif presiden dalam menyusun kabinet pemerintahan.</li><li><strong>E</strong>: Pembangunan pos perbatasan merupakan proyek infrastruktur pengamanan kawasan terluar.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 06) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>proses peningkatan kesejahteraan rakyat.</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Pelajaran sejarah membuktikan bahwa bangsa Indonesia berhasil mewujudkan kemerdekaan karena...", "options": ["Mendapat dukungan persenjataan modern pasukan sekutu", "Kekuatan ekonomi kaum bangsawan di kota pelabuhan", "Mengutamakan kehendak bersama demi satu tujuan bersama", "Perjanjian perundingan diplomasi tanpa perlawanan fisik", "Pemberian kedaulatan sukarela oleh pemerintah penjajah"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kunci Keberhasilan Merebut Kemerdekaan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Hanya <strong>dengan mengutamakan kehendak bersama dan demi satu tujuan bersama pula</strong>, bangsa ini berhasil mewujudkan cita-citanya yaitu merdeka lepas dari penjajahan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena persatuan kehendak seluruh elemen bangsa melampaui sekat suku, agama, dan daerah menjadi kekuatan maha dahsyat yang melumpuhkan kolonialisme. Solidaritas tujuan bersama inilah fondasi sejati lahirnya Republik Indonesia. Pilihan lain keliru dan bertentangan dengan fakta sejarah perjuangan bangsa.</p><p>Jawaban tepat: <strong>C. Mengutamakan kehendak bersama demi satu tujuan bersama</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Perjuangan kemerdekaan berakar pada tekad rakyat bersenjatakan bambu runcing dan rampasan.</li><li><strong>B</strong>: Perjuangan kemerdekaan bersifat kerakyatan lintas lapisan masyarakat, bukan monopoli bangsawan.</li><li><strong>D</strong>: Kemerdekaan diraih melalui kombinasi diplomasi gigih dan perjuangan bersenjata berdarah-darah.</li><li><strong>E</strong>: Kemerdekaan Indonesia direbut melalui pengorbanan pahlawan, bukan hadiah dari bangsa penjajah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 06) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>dengan mengutamakan kehendak bersama dan demi satu tujuan bersama pula,</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C4 - Analisis", "question": "Ketika segenap elemen bangsa melupakan tujuan bersama dan mengingkari konsensus, konsekuensi yang terjadi adalah...", "options": ["Penguatan peran diplomasi luar negeri kawasan regional", "Pertumbuhan pesat aktivitas ekspor komoditas alam", "Penurunan suku bunga pinjaman perbankan nasional", "Timbulnya berbagai bentuk konflik sosial antarkelompok", "Terwujudnya efisiensi birokrasi pemerintahan daerah"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dampak Pengingkaran Konsensus Kebangsaan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Ketika bangsa ini melupakan tujuan bersamanya serta mengingkari konsensus, maka yang terjadi adalah <strong>timbulnya berbagai bentuk konflik sosial</strong>, perlawanan bersenjata, dan ide separatis.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena hilangnya komitmen terhadap konsensus bersama melahirkan ketidakpercayaan antarkelompok, perselisihan komunal, dan instabilitas sosial berkepanjangan. Konsensus nasional adalah tali pengikat perdamaian bangsa yang majemuk. Pilihan lain menunjukkan dampak positif ekonomi dan diplomasi yang mustahil terwujud saat terjadi perpecahan.</p><p>Jawaban tepat: <strong>D. Timbulnya berbagai bentuk konflik sosial antarkelompok</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Konflik sosial internal justru melemahkan wibawa dan posisi tawar diplomasi internasional.</li><li><strong>B</strong>: Gangguan stabilitas dalam negeri melumpuhkan rantai pasok dan produktivitas ekspor komoditas.</li><li><strong>C</strong>: Krisis sosial politik memicu inflasi dan meningkatkan risiko stabilitas sektor perbankan.</li><li><strong>E</strong>: Disintegrasi sosial mengacaukan operasional tata pemerintahan dan pelayanan publik daerah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 06) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>berbagai bentuk konflik sosial,</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Ancaman kedaulatan terhadap keutuhan wilayah negara yang timbul akibat pengingkaran konsensus bersama adalah...", "options": ["Meningkatnya angka urbanisasi ke wilayah ibu kota", "Penurunan volume investasi portofolio asing di bursa", "Penyesuaian tarif pungutan bea keluar ekspor barang", "Pemekaran wilayah administratif tingkat kecamatan baru", "Munculnya berbagai gerakan ide separatis kewilayahan"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Ancaman Disintegrasi Akibat Ingkar Konsensus</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pengingkaran terhadap konsensus kehendak bersama memicu timbulnya konflik sosial, perlawanan bersenjata di dalam negeri, dan <strong>munculnya ide-ide separatis</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena gerakan separatis mengancam keutuhan NKRI secara langsung dengan berupaya memisahkan diri dari kedaulatan Republik Indonesia. Gerakan separatisme tumbuh subur ketika rasa keadilan dan konsensus bersama dinodai. Pilihan lain merupakan isu ekonomi atau demografi umum yang tidak mengancam keutuhan teritorial negara.</p><p>Jawaban tepat: <strong>E. Munculnya berbagai gerakan ide separatis kewilayahan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Urbanisasi adalah fenomena migrasi kependudukan yang bersifat sosiologis-demografis.</li><li><strong>B</strong>: Fluktuasi investasi saham merupakan dinamika pasar modal akibat sentimen ekonomi global.</li><li><strong>C</strong>: Penyesuaian tarif ekspor adalah kebijakan instrumen fiskal pada kementerian perbendaharaan.</li><li><strong>D</strong>: Pemekaran kecamatan adalah penataan wilayah administratif reguler dalam otonomi daerah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 06) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>munculnya ide‐ ide separatis.</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Kesadaran kebangsaan yang melahirkan cita-cita kemerdekaan Indonesia tumbuh dan berkembang atas...", "options": ["Dorongan kehendak bersama seluruh komponen masyarakat", "Intervensi kesepakatan geopolitik kekuatan adidaya dunia", "Ambisi politik pimpinan kerajaan feodal di Nusantara", "Keinginan memperluas wilayah kekuasaan militer regional", "Pengaruh hegemoni perdagangan kongsi saudagar asing"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dorongan Kelahiran Cita-Cita Kemerdekaan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kesadaran kebangsaan yang melahirkan cita-cita kemerdekaan pada dasarnya tumbuh dan berkembang oleh <strong>dorongan kehendak bersama seluruh komponen masyarakat budaya</strong> di seluruh Nusantara.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena kemerdekaan Indonesia merupakan hasil kehendak organik dan perjuangan kolektif seluruh rakyat budaya di Nusantara untuk hidup bersatu dalam wadah NKRI. Kehendak bersama ini mengalahkan sekat primordalisme masa lalu. Pilihan lain bertentangan secara diametral dengan hakikat kelahiran bangsa Indonesia.</p><p>Jawaban tepat: <strong>A. Dorongan kehendak bersama seluruh komponen masyarakat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kemerdekaan Indonesia lahir dari tekad mandiri bangsa, bukan produk kompromi adidaya asing.</li><li><strong>C</strong>: Perjuangan nasional bersifat inklusif kerakyatan, bukan demi mengembalikan hegemoni monarki feodal.</li><li><strong>D</strong>: Tujuan bernegara adalah perdamaian dan keadilan sosial, bukan ekspansionisme militer teritorial.</li><li><strong>E</strong>: Nasionalisme Indonesia justru bangkit untuk meruntuhkan eksploitasi kongsi dagang kolonial.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 07) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>dorongan kehendak bersama, seluruh</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Bangsa Indonesia dipahami sebagai suatu masyarakat dengan kesatuan spirit dan jiwa yang berkarakter...", "options": ["Individualistik dan kompetitif", "Karakter Gemeinschaft", "Gesellschaft pragmatis", "Birokratis sentralistik", "Feodal primordialistik"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Konsep Karakter Gemeinschaft Bangsa Indonesia</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Bangsa Indonesia pada hakikatnya merupakan masyarakat dengan <strong>kesatuan spirit/karakter (Karakter Gemeinschaft)</strong> yang mengikat batin segenap warganya.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena bangsa Indonesia dijiwai oleh karakter ikatan batiniah, kekeluargaan, dan kebersamaan yang mendalam layaknya konsep *Gemeinschaft* (paguyuban). Ikatan spirit ini menyatukan masyarakat majemuk menjadi satu tubuh kebangsaan. Pilihan lain merujuk pada pola hubungan mekanis tanpa ikatan jiwa kebersamaan.</p><p>Jawaban tepat: <strong>B. Karakter Gemeinschaft</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Paham individualistik memisahkan kepentingan pribadi dari tanggung jawab solidaritas sosial.</li><li><strong>C</strong>: Konsep *Gesellschaft* adalah patembayan dengan relasi berbasis kontrak pamrih rasional belaka.</li><li><strong>D</strong>: Sifat birokratis sentralistik merujuk pada administrasi kekuasaan, bukan jiwa karakter bangsa.</li><li><strong>E</strong>: Sikap feodal primordial memecah masyarakat dalam kasta dan fanatisme kesukuan sempit.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 07) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Karakter Gemeinschaft</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Gagasan kebangsaan Indonesia sejak awal berdirinya dirancang dengan prinsip bahwa persatuan...", "options": ["Mewajibkan peleburan seluruh bahasa daerah ke satu logat", "Mengharuskan asimilasi budaya lokal secara paksa", "Tidak diniatkan menyatukan keragaman menjadi keseragaman", "Menetapkan standarisasi adat istiadat di seluruh daerah", "Membatasi praktik tradisi masyarakat di tingkat lokal"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Prinsip Kebangsaan Terhadap Keberagaman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Ide kebangsaan Indonesia sejak awal <strong>tidak diniatkan untuk menyatukan segala bentuk keragaman yang ada ke dalam suatu keseragaman</strong>, melainkan mempertahankan budaya lokal.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena filosofi persatuan Indonesia mengakui dan merayakan kebinekaan sebagai kekayaan tak ternilai, bukan menghapusnya melalui penyeragaman yang mematikan identitas daerah. Bhinneka Tunggal Ika menjadi jiwa peradaban Nusantara. Pilihan lain mencerminkan tindakan asimilasi koersif yang mencederai hak budaya lokal.</p><p>Jawaban tepat: <strong>C. Tidak diniatkan menyatukan keragaman menjadi keseragaman</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Bahasa Indonesia berfungsi sebagai bahasa persatuan tanpa menghapus kekayaan bahasa daerah.</li><li><strong>B</strong>: Pemaksaan asimilasi budaya bertentangan dengan amanat konstitusi perlindungan budaya daerah.</li><li><strong>D</strong>: Adat istiadat lokal memiliki otonomi keunikan tersendiri yang wajib dihormati dan dilestarikan.</li><li><strong>E</strong>: Negara menjamin kebebasan masyarakat dalam memelihara dan mengembangkan tradisi budayanya.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 07) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>keseragaman. Budaya lokal justru tetap dipertahankan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Budaya lokal di seluruh kepulauan Nusantara senantiasa dipertahankan dan dikembangkan karena...", "options": ["Mampu menggantikan seluruh fungsi hukum positif negara", "Menjadi komoditas utama perdagangan antarprovinsi", "Dapat membatasi masuknya teknologi modern ke masyarakat", "Keragaman merupakan kekuatan lokal sekaligus kekuatan bangsa", "Mencegah terjadinya percampuran penduduk antarwilayah"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kedudukan Budaya Lokal sebagai Kekuatan Bangsa</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Budaya lokal tetap dipertahankan dan dikembangkan karena <strong>keragaman itu merupakan kekuatan lokal, yang dengan demikian juga merupakan kekuatan seluruh bangsa</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena keberagaman adat, tradisi, dan kearifan lokal memperkaya ketahanan budaya nasional serta menjadi daya lentur bangsa dalam menghadapi dinamika zaman. Kekuatan daerah adalah pilar penyangga kekuatan nasional. Pilihan lain memposisikan budaya secara keliru atau menjadikannya instrumen isolasionisme tertutup.</p><p>Jawaban tepat: <strong>D. Keragaman merupakan kekuatan lokal sekaligus kekuatan bangsa</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Hukum adat melengkapi ketertiban sosial namun tidak menggantikan sistem hukum nasional.</li><li><strong>B</strong>: Nilai budaya memiliki martabat keluhuran peradaban, bukan sekadar komoditas transaksi pasar.</li><li><strong>C</strong>: Budaya lokal bersifat adaptif terbuka terhadap kemajuan ilmu pengetahuan dan teknologi.</li><li><strong>E</strong>: Mobilitas penduduk antarpulau mempererat integrasi nasional dan perkawinan silang budaya.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 07) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>keragaman itu merupakan kekuatan lokal,</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Realita geografik kewilayahan tempat hidup bersama bangsa Indonesia bercirikan...", "options": ["Satu daratan benua luas yang terhubung tanpa batas laut", "Gugusan lembah sempit di kelilingi pegunungan terisolasi", "Gurun sahara luas dengan sumber mata air terbatas", "Dataran tinggi homogen dengan satu poros sungai utama", "Kumpulan pulau-pulau yang amat banyak jumlahnya"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Realita Geografik Wilayah Indonesia</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam kenyataannya (realita geografik), kesatuan wilayah tempat hidup bangsa Indonesia <strong>merupakan kumpulan pulau-pulau yang amat banyak jumlahnya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena Indonesia adalah negara kepulauan (*archipelagic state*) terbesar di dunia yang dipersatukan oleh perairan laut Nusantara. Menyadari realitas pulau-pulau ini memperkokoh urgensi wawasan kebangsaan dan persatuan maritim. Pilihan lain menggambarkan kondisi geomorfologi benua atau kawasan asing yang bukan wilayah Indonesia.</p><p>Jawaban tepat: <strong>E. Kumpulan pulau-pulau yang amat banyak jumlahnya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Indonesia bukan negara benua kontinental, melainkan bentang negara kepulauan maritim.</li><li><strong>B</strong>: Topografi kepulauan Nusantara sangat variatif mencakup pesisir, laut, dataran, dan pegunungan.</li><li><strong>C</strong>: Iklim Indonesia adalah tropis basah dengan hutan hujan lebat, bukan bentang alam gurun pasir.</li><li><strong>D</strong>: Wilayah Indonesia majemuk dengan ribuan pulau, bukan satu daratan dataran tinggi tunggal.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 07) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>kumpulan pulau‐pulau yang amat banyak jumlahnya.</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Menghadapi kenyataan geografis wilayah kepulauan, faktor perekat utama yang wajib dipelihara adalah...", "options": ["Kehendak untuk bersatu dan hidup bersama", "Penguasaan persenjataan perang di setiap pulau", "Pembatasan perpindahan warga antarwilayah kepulauan", "Sentralisasi seluruh perizinan usaha di ibu kota negara", "Penghapusan bahasa daerah demi efisiensi komunikasi"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Faktor Perekat Utama Bangsa Kepulauan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sadar akan kenyataan geografik kepulauan, maka <strong>kehendak untuk bersatu dan hidup bersama harus senantiasa terjaga dan terpelihara karena merupakan faktor perekat utama</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena laut bukan pemisah melainkan pemersatu pulau-pulau, di mana tekad batiniah untuk hidup rukun berdampingan menjadi fondasi utama keutuhan NKRI. Tanpa kehendak bersatu, realitas pulau rentan terhadap ancaman disintegrasi. Pilihan lain menawarkan langkah koersif represif yang mencederai demokrasi.</p><p>Jawaban tepat: <strong>A. Kehendak untuk bersatu dan hidup bersama</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kekuatan militer penting menjaga kedaulatan, namun perekat sejati bangsa adalah tekad persatuan.</li><li><strong>C</strong>: Pembatasan mobilitas penduduk melanggar hak asasi bepergian dan melemahkan integrasi ekonomi.</li><li><strong>D</strong>: Sentralisasi berlebihan memicu kecemburuan antardaerah yang merugikan persatuan nasional.</li><li><strong>E</strong>: Bahasa daerah adalah warisan kekayaan bangsa yang dijamin pelestariannya oleh UUD 1945.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 07) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>kehendak untuk bersatu dan hidup bersama harus</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Nilai-nilai kebangsaan Indonesia dirumuskan para pendiri bangsa dengan menggali...", "options": ["Doktrin tata negara bangsa penjajah dari Eropa barat", "Khasanah kehidupan masyarakat dan ajaran leluhur bangsa", "Sistem peradilan absolut yang diterapkan kekaisaran kuno", "Teori ekonomi pasar bebas kapitalistik liberal global", "Hukum adat suku bangsa asing di wilayah perbatasan"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sumber Perumusan Nilai Kebangsaan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Para pendiri bangsa berhasil mengangkat nilai-nilai yang terkandung di dalam <strong>khasanah kehidupan masyarakat Indonesia maupun ajaran para leluhur sebagai nilai-nilai kebangsaan Indonesia</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena Pancasila dan nilai kebangsaan digali langsung dari bumi pertiwi, mencakup gotong royong, musyawarah, religiusitas, dan welas asih warisan leluhur nusantara. Hal inilah yang menjadikannya orisinal dan mengakar kuat dalam sanubari rakyat. Pilihan lain mengambil rujukan doktrin asing yang tidak sejiwa dengan karakter bangsa.</p><p>Jawaban tepat: <strong>B. Khasanah kehidupan masyarakat dan ajaran leluhur bangsa</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Sistem tata negara penjajah bersifat menindas dan diskriminatif, bukan sumber nilai kebangsaan.</li><li><strong>C</strong>: Monarki absolut bertentangan dengan prinsip kedaulatan rakyat dan permusyawaratan perwakilan.</li><li><strong>D</strong>: Kapitalisme liberal individualis bertentangan dengan prinsip keadilan sosial dan kekeluargaan.</li><li><strong>E</strong>: Nilai kebangsaan digali dari kebudayaan rakyat Indonesia sendiri, bukan mengimpor adat bangsa asing.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 07) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>kehidupan masyarakat Indonesia maupun ajaran para leluhur</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Nilai-nilai kebangsaan dirumuskan secara konkret dan disepakati oleh pendiri bangsa untuk dijadikan...", "options": ["Alat pemaksaan kepentingan golongan penguasa daerah", "Instrumen komersial dalam perdagangan komoditas ekspor", "Landasan dan pedoman pembentukan serta penyelenggaraan negara", "Pedoman mutlak bagi penghentian modernisasi teknologi", "Bahan retorika kampanye politik pemilihan umum rutin"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Fungsi Nilai Kebangsaan yang Disepakati</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Nilai-nilai kebangsaan disepakati untuk dijadikan <strong>landasan dan pedoman di dalam pembentukan dan penyelenggaraan negara, serta di dalam menata kehidupan bermasyarakat, berbangsa dan bernegara</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena nilai-nilai kebangsaan menjadi *grundnorm* dan kompas penuntun seluruh aparatur dalam menyusun undang-undang, menetapkan kebijakan, dan mengelola pemerintahan. Kesepakatan luhur ini menjaga arah jalannya negara tetap berada pada rel kebenaran. Pilihan lain mereduksi nilai kebangsaan menjadi alat transaksi kekuasaan pragmatis.</p><p>Jawaban tepat: <strong>C. Landasan dan pedoman pembentukan serta penyelenggaraan negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Nilai kebangsaan berorientasi pada persatuan seluruh rakyat, bukan hegemoni kepentingan elit.</li><li><strong>B</strong>: Nilai luhur bangsa adalah pedoman etika moral kehidupan, bukan komoditas transaksi komersial.</li><li><strong>D</strong>: Nilai kebangsaan senantiasa dinamis dan terbuka membimbing kemajuan ilmu pengetahuan bangsa.</li><li><strong>E</strong>: Nilai kebangsaan harus diwujudkan dalam kerja nyata bernegara, bukan sekadar janji kampanye.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 07) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>landasan dan pedoman di dalam</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C4 - Analisis", "question": "Proses reformasi nasional yang berlangsung saat ini pada hakikatnya dipahami sebagai proses...", "options": ["Perombakan total batas teritorial wilayah perbatasan", "Penggantian seluruh dasar falsafah konstitusi negara", "Pengalihan kepemilikan aset publik ke pihak swasta", "Reinventing and rebuilding konsolidasi menuju masyarakat demokratis", "Pemisahan sistematis antara lembaga militer dan kepolisian"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Hakikat Proses Reformasi Nasional</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Proses reformasi pada dasarnya adalah <strong>sebuah proses reinventing and rebuilding serta konsolidasi bangsa Indonesia menuju masyarakat demokratis</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena reformasi bermakna menata dan membangun kembali institusi serta nilai-nilai demokrasi yang sempat terdistorsi, demi mewujudkan cita-cita proklamasi yang substantif. Reformasi adalah konsolidasi pematangan kehidupan bernegara. Pilihan lain keliru karena reformasi bukan bertujuan membubarkan konstitusi atau privatisasi serampangan.</p><p>Jawaban tepat: <strong>D. Reinventing and rebuilding konsolidasi menuju masyarakat demokratis</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Batas wilayah teritorial NKRI bersifat final dan dijaga keutuhannya sesuai hukum internasional.</li><li><strong>B</strong>: Pancasila dan Pembukaan UUD 1945 bersifat permanen dan tidak diubah dalam agenda reformasi.</li><li><strong>C</strong>: Privatisasi aset negara bukan hakikat filosofis dari gerakan reformasi kehidupan berbangsa.</li><li><strong>E</strong>: Pemisahan TNI dan Polri adalah agenda pembenahan kelembagaan keamanan, bukan definisi hakikat reformasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 07) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>sebuah proses reinventing and rebuilding serta</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Sebagai sebuah kesadaran korektif, agenda reformasi bangsa Indonesia ditujukan untuk...", "options": ["Menghapus seluruh warisan sejarah masa kepemimpinan lama", "Menyeragamkan pola kepemimpinan birokrasi di daerah", "Membatasi keterlibatan pers dalam pengawasan kinerja pemerintah", "Menjadikan kekuasaan lembaga peradilan berada di bawah eksekutif", "Menata kembali kehidupan demi pencapaian cita-cita nasional"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Reformasi sebagai Kesadaran Korektif</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Proses reformasi merupakan <strong>kesadaran korektif untuk kembali menata kehidupannya agar menjadi lebih baik demi pencapaian tujuan dan cita-cita nasional</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena kesadaran korektif menuntut introspeksi kebangsaan untuk memperbaiki kelemahan tata kelola masa lalu agar gerak bangsa semakin cepat mencapai kemakmuran dan keadilan sosial. Koreksi dilakukan demi menyempurnakan pencapaian cita-cita kemerdekaan. Pilihan lain mencerminkan langkah kemunduran demokrasi yang otoriter.</p><p>Jawaban tepat: <strong>E. Menata kembali kehidupan demi pencapaian cita-cita nasional</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Sejarah masa lalu menjadi cermin pembelajaran berharga, bukan sesuatu yang dihapus begitu saja.</li><li><strong>B</strong>: Otonomi daerah menuntut kepemimpinan inovatif lokal, bukan penyeragaman kaku birokrasi.</li><li><strong>C</strong>: Kebebasan pers yang bertanggung jawab merupakan salah satu pilar penopang demokrasi reformasi.</li><li><strong>D</strong>: Kekuasaan kehakiman dijamin mandiri merdeka terbebas dari intervensi kekuasaan eksekutif.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 07) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>kesadaran korektif untuk kembali menata kehidupannya</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Upaya nyata untuk memantapkan kembali nilai-nilai kebangsaan generasi penerus bangsa harus dirancang secara...", "options": ["Terorganisasi terencana secara sistematis dan terukur", "Spontanitas mengikuti pergantian pejabat birokrasi daerah", "Sentralistik absolut tanpa mempertimbangkan dinamika daerah", "Mandiri tanpa perlu koordinasi lintas lembaga pemerintah", "Sementara berdasarkan anggaran sisa operasional dinas"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Upaya Pemantapan Nilai Kebangsaan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sebagai wujud tanggung jawab terhadap nasib bangsa, pemantapan kembali nilai-nilai kebangsaan harus dilakukan melalui <strong>upaya nyata yang terorganisasi terencana secara sistematis dan terukur</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena penanaman nilai kebangsaan membutuhkan peta jalan pembudayaan yang berkesinambungan dan memiliki indikator keberhasilan yang jelas. Perencanaan terukur memastikan efektivitas internalisasi nilai di seluruh elemen aparatur dan masyarakat. Pilihan lain mencerminkan pendekatan sporadis yang tidak efektif.</p><p>Jawaban tepat: <strong>A. Terorganisasi terencana secara sistematis dan terukur</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Penanaman nilai bukan kegiatan insidental yang bergantung pada masa jabatan figur politik.</li><li><strong>C</strong>: Pola pemantapan nilai harus kontekstual dan adaptif, bukan indoktrinasi yang bersifat kaku.</li><li><strong>D</strong>: Penguatan wawasan kebangsaan menuntut sinergi kolaboratif lintas kementerian dan lembaga.</li><li><strong>E</strong>: Program kebangsaan membutuhkan komitmen alokasi sumber daya yang berkesinambungan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 08) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>terencana secara sistematis dan terukur,</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Perjalanan proses konsolidasi demokrasi dalam reformasi bangsa Indonesia bertransformasi dari tahapan...", "options": ["Pemberian otonomi menuju sistem komando hierarki militer", "Hal prosedural menuju kultural dan substantif", "Sentralisasi kekuasaan menuju federasi antarpulau", "Demokrasi perwakilan menuju sistem monarki konstitusional", "Pemisahan wewenang menuju peleburan cabang kekuasaan"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Arah Transformasi Reformasi Demokrasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Proses reformasi merupakan konsolidasi demokrasi dalam perjalanan bangsa yang harus dilalui, <strong>dari hal-hal yang bersifat prosedural menuju hal-hal yang bersifat kultural dan substantif</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena demokrasi sejati bukan sekadar terpenuhinya mekanisme pemilu berkala (prosedural), melainkan terwujudnya internalisasi budaya adil, jujur, serta keadilan sosial nyata (substantif). Transformasi ini menjadi tolok ukur kematangan peradaban politik Indonesia. Pilihan lain memuat arah perubahan sistem pemerintahan yang keliru.</p><p>Jawaban tepat: <strong>B. Hal prosedural menuju kultural dan substantif</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Demokrasi mengutamakan supremasi sipil dan desentralisasi, bukan komando kemiliteran.</li><li><strong>C</strong>: Bentuk negara Indonesia adalah negara kesatuan yang tidak diubah menjadi bentuk federasi.</li><li><strong>D</strong>: Sistem pemerintahan Indonesia adalah republik demokratis, bukan monarki kekeluargaan.</li><li><strong>E</strong>: Prinsip pemisahan dan perimbangan kekuasaan (*checks and balances*) tetap ditegakkan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 08) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>hal yang bersifat kultural dan substantif.</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Nilai-nilai kebangsaan yang wajib diangkat kembali demi meneguhkan jati diri bangsa terangkum dalam...", "options": ["Rancangan Anggaran Pendapatan dan Belanja Negara", "Piagam Perjanjian Kerjasama Perdagangan Antarnegara", "Konsensus Dasar Nasional", "Buku Pedoman Standar Tata Kelola Organisasi Daerah", "Naskah Akademik Perubahan Undang-Undang Pemilihan"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Komponen Konsensus Dasar Nasional</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Nilai-nilai kebangsaan yang perlu diangkat kembali demi jati diri bangsa terkandung di dalam <strong>Konsensus Dasar Nasional</strong>, yang terdiri dari Pancasila, UUD 1945, Bhinneka Tunggal Ika, dan NKRI.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena Konsensus Dasar Nasional merupakan payung filosofis dan konstitusional bersama yang menjamin kelangsungan hidup berbangsa dan bernegara. Konsensus ini merupakan kesepakatan agung para pendiri Republik Indonesia. Pilihan lain merujuk pada dokumen teknis operasional administratif yang berada di bawah level konsensus dasar.</p><p>Jawaban tepat: <strong>C. Konsensus Dasar Nasional</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: RAPBN adalah instrumen kebijakan keuangan tahunan negara, bukan landasan filosofis kebangsaan.</li><li><strong>B</strong>: Perjanjian perdagangan adalah instrumen kerja sama ekonomi kawasan bilateral maupun multilateral.</li><li><strong>D</strong>: Buku standar organisasi daerah mengatur aspek kelembagaan teknis instansi pemerintah.</li><li><strong>E</strong>: Naskah akademik undang-undang pemilu merupakan telaah hukum penyusunan peraturan perundang-undangan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 08) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Konsensus Dasar Nasional,</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C4 - Analisis", "question": "Empat pilar pokok yang terkandung di dalam Konsensus Dasar Nasional Republik Indonesia meliputi...", "options": ["Falsafah liberalisme, GBHN, sumpah pemuda, dan konvensi", "Sistem peradilan, dewan menteri, dekrit, dan otonomi", "Wawasan nusantara, doktrin pertahanan, traktat, dan federasi", "Falsafah Pancasila, UUD 1945, Bhinneka Tunggal Ika, dan NKRI", "Piagam Jakarta, piagam PBB, hukum adat, dan kearifan lokal"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Empat Pilar dalam Konsensus Dasar Nasional</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Empat pilar kebangsaan di dalam <strong>Konsensus Dasar Nasional</strong> yaitu <strong>falsafah bangsa Pancasila, Undang-Undang Dasar 1945, semboyan Bhinneka Tunggal Ika, serta prinsip NKRI</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena rumusan resmi konsensus dasar nasional mencakup empat pilar fundamental yang mengikat persatuan dan kedaulatan bangsa Indonesia. Keempat elemen ini saling menopang dan tidak dapat dipisahkan satu sama lain. Pilihan lain memuat istilah doktrin asing atau instrumen hukum yang bukan bagian konsensus dasar.</p><p>Jawaban tepat: <strong>D. Falsafah Pancasila, UUD 1945, Bhinneka Tunggal Ika, dan NKRI</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Liberalisme bertentangan dengan Pancasila, dan GBHN sudah tidak digunakan pasca-amandemen.</li><li><strong>B</strong>: Sistem peradilan dan dewan menteri adalah struktur kelembagaan, bukan pilar konsensus dasar.</li><li><strong>C</strong>: Traktat dan federasi bukan prinsip ketatanegaraan Indonesia yang menganut negara kesatuan.</li><li><strong>E</strong>: Piagam PBB adalah instrumen hukum internasional, bukan bagian pilar internal kebangsaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 08) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>falsafah bangsa Pancasila, Undang‐Undang Dasar</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Peneguhan kembali jati diri bangsa melalui Konsensus Dasar Nasional ditujukan agar...", "options": ["Indonesia menutup diri dari hubungan perdagangan luar negeri", "Pemerintah membatasi mobilitas penduduk antarwilayah pulau", "Sistem perekonomian beralih ke pola tradisional agraris", "Anggaran belanja pertahanan meningkat melampaui pos lain", "Terjaga integritas bangsa dan identitas Negara Kesatuan"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuan Peneguhan Jati Diri Bangsa</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Peneguhan jati diri bangsa dilakukan agar <strong>dapat tetap terjaga integritas bangsa dan identitas Negara Kesatuan Republik Indonesia</strong> di tengah arus globalisasi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena jati diri yang kokoh menjadi benteng pertahanan moral dan kultural yang melindungi bangsa dari disintegrasi serta lunturnya identitas nasional. Integritas bangsa memastikan persatuan tetap terjaga di tengah keterbukaan dunia. Pilihan lain mengusulkan kebijakan proteksionisme ekstrem yang merugikan pembangunan.</p><p>Jawaban tepat: <strong>E. Terjaga integritas bangsa dan identitas Negara Kesatuan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Indonesia tetap berperan aktif dalam perekonomian global tanpa mengorbankan kedaulatan.</li><li><strong>B</strong>: Mobilitas penduduk dijamin undang-undang untuk mempercepat integrasi sosial ekonomi nasional.</li><li><strong>C</strong>: Perekonomian nasional terus berkembang menuju industrialisasi maju berbasis teknologi ramah.</li><li><strong>D</strong>: Alokasi anggaran belanja negara diatur proporsional sesuai prioritas pembangunan nasional.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 08) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>dapat tetap terjaga integritas bangsa dan identitas Negara</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Tantangan eksternal terhadap eksistensi jati diri dan identitas nasional Indonesia bersumber dari...", "options": ["Arus globalisasi yang bersifat multidimensional", "Kenaikan bea masuk barang industri di kawasan eropa", "Perbedaan zona waktu internasional di kawasan pasifik", "Standarisasi mata uang tunggal kawasan asia tenggara", "Persaingan eksplorasi teknologi antariksa dunia luar"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Karakter Tantangan Globalisasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Ketahanan jati diri bangsa diuji <strong>di tengah terpaan arus globalisasi yang bersifat multidimensional</strong>, mencakup bidang ekonomi, politik, sosial budaya, dan teknologi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena globalisasi menyerang seluruh sendi kehidupan bermasyarakat secara serentak, membawa nilai-nilai individualisme, konsumerisme, dan transnasionalisme yang dapat mengikis nilai luhur bangsa. Penegasan sifat multidimensional menuntut ketahanan komprehensif. Pilihan lain merupakan isu teknis perdagangan atau sains yang berlingkup sempit.</p><p>Jawaban tepat: <strong>A. Arus globalisasi yang bersifat multidimensional</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Tarif bea masuk barang adalah instrumen kebijakan perdagangan bilateral reguler.</li><li><strong>C</strong>: Zona waktu adalah ketetapan rotasi geografis bumi yang tidak memengaruhi karakter ideologi.</li><li><strong>D</strong>: Kawasan Asia Tenggara tidak memiliki mata uang tunggal dan tetap berdaulat secara moneter.</li><li><strong>E</strong>: Riset antariksa adalah ranah kemajuan ilmu pengetahuan astronomi global antarbangsa.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 08) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>arus globalisasi yang bersifat</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Pemerintahan yang stabil dan dinamis diwujudkan melalui sistem penyelenggaraan pemerintahan yang mampu...", "options": ["Meredam segala bentuk kritik publik melalui media pers", "Mendukung pelaksanaan pembangunan mencapai tujuan nasional", "Mengambil alih fungsi yudikatif dalam penyelesaian perkara", "Menetapkan tarif komoditas pangan tanpa koordinasi pasar", "Menyeragamkan struktur aparatur sipil di seluruh wilayah"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sistem Pemerintahan Penopang Pembangunan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Komitmen pemerintahan diwujudkan melalui upaya membangun sistem penyelenggaraan pemerintahan yang mampu <strong>mendukung pelaksanaan pembangunan untuk mencapai tujuan nasional</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena aparatur pemerintah bertugas menggerakkan program pembangunan terencana agar target kemakmuran dan kecerdasan bangsa tercapai optimal. Stabilitas politik dan kedinamisan birokrasi menjadi fondasi akselerasi pembangunan. Pilihan lain mencerminkan tindakan kesewenang-wenangan birokrasi yang melanggar hukum.</p><p>Jawaban tepat: <strong>B. Mendukung pelaksanaan pembangunan mencapai tujuan nasional</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kritik publik dan kebebasan pers adalah sarana pengawasan penting dalam negara demokrasi.</li><li><strong>C</strong>: Kekuasaan kehakiman bersifat mandiri dan tidak boleh diintervensi oleh jajaran eksekutif.</li><li><strong>D</strong>: Penetapan harga pangan melibatkan mekanisme pasar berkeadilan dan koordinasi multipihak.</li><li><strong>E</strong>: Struktur aparatur disusun berdasarkan analisis beban kerja dan karakteristik kebutuhan riil daerah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 08) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>mendukung pelaksanaan pembangunan untuk mencapai tujuan nasional.</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Komitmen kebangsaan dalam tata kelola pemerintahan negara diarahkan untuk menciptakan sistem yang...", "options": ["Monopolistik dan berorientasi pada pemusatan laba", "Otokratis dan tidak responsif terhadap tuntutan publik", "Stabil dan dinamis dalam mendukung pembangunan", "Proteksionis dan mengisolasi diri dari perkembangan", "Permanen tanpa fleksibilitas menghadapi perubahan zaman"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Karakteristik Tata Pemerintahan yang Dicita-citakan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Penyelenggaraan negara menuntut <strong>komitmen untuk menciptakan pemerintahan yang stabil dan dinamis</strong> demi mendukung kelancaran pembangunan nasional.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena pemerintahan yang stabil menjamin kepastian hukum dan ketertiban sosial, sedangkan kedinamisan menjamin responsivitas dan inovasi pelayanan dalam menghadapi perubahan zaman. Keduanya saling melengkapi demi kesuksesan pembangunan. Pilihan lain mencirikan tata kelola buruk yang bertentangan dengan prinsip reformasi.</p><p>Jawaban tepat: <strong>C. Stabil dan dinamis dalam mendukung pembangunan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Tata kelola pemerintahan bertugas melayani masyarakat, bukan memonopoli keuntungan usaha.</li><li><strong>B</strong>: Pemerintahan demokratis wajib menjunjung transparansi dan akuntabilitas aspirasi warga.</li><li><strong>D</strong>: Sikap mengisolasi diri menghambat penyerapan ilmu pengetahuan dan investasi produktif.</li><li><strong>E</strong>: Pemerintahan statis tanpa adaptabilitas akan gagal menjawab tuntutan masyarakat modern.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 08) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Komitmen untuk menciptakan pemerintahan yang stabil dan dinamis</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Pokok bahasan substantif pembelajaran wawasan kebangsaan aparatur negara secara mendasar berkenaan dengan...", "options": ["Teknik pembukuan keuangan dan audit saldo anggaran kas", "Sistem perpolitikan luar negeri negara adidaya dunia", "Tata cara pengadaan barang dan jasa instansi swasta", "Falsafah Pancasila, UUD 1945, Bhinneka Tunggal Ika, dan NKRI", "Strategi promosi pemasaran produk komoditas ekspor daerah"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pokok Bahasan Pembelajaran Wawasan Kebangsaan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pokok bahasan wawasan kebangsaan <strong>berkenaan dengan falsafah Pancasila, Undang-Undang Dasar 1945, dan semboyan Bhinneka Tunggal Ika serta prinsip Negara Kesatuan Republik Indonesia</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena substansi wawasan kebangsaan dirancang untuk mengokohkan pemahaman aparatur terhadap empat konsensus dasar negara sebagai pedoman berpikir dan bersikap dalam menjalankan tugas publik. Penguasaan empat konsensus ini memperkuat integritas nasional. Pilihan lain merupakan keterampilan teknis fungsional bidang lain.</p><p>Jawaban tepat: <strong>D. Falsafah Pancasila, UUD 1945, Bhinneka Tunggal Ika, dan NKRI</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pembukuan dan audit kas adalah kompetensi teknis perbendaharaan dan akuntansi keuangan.</li><li><strong>B</strong>: Politik luar negeri adidaya merupakan kajian ilmu hubungan internasional dan diplomasi.</li><li><strong>C</strong>: Pengadaan barang jasa swasta adalah lingkup manajemen rantai pasok korporasi privat.</li><li><strong>E</strong>: Pemasaran ekspor adalah bidang keahlian niaga perdagangan pada kementerian perdagangan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 08) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Kebangsaan ini berkenaan dengan falsafah Pancasila, Undang-Undang Dasar 1945,</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Manfaat utama yang diharapkan dari penguasaan materi wawasan kebangsaan bagi aparatur sipil adalah...", "options": ["Mendapatkan kenaikan pangkat istimewa secara otomatis", "Menghafalkan pasal hukum pidana di luar tugas kedinasan", "Menguasai keterampilan negosiasi kontrak proyek swasta", "Menduduki jabatan pimpinan organisasi partai politik daerah", "Dapat memahami arti Pancasila, UUD 1945, dan integritas NKRI"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Manfaat Pembelajaran Wawasan Kebangsaan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Berbekal pembelajaran ini, peserta <strong>dapat memahami arti Pancasila, UUD 1945, dan wawasan kebangsaan Negara Kesatuan Republik Indonesia</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena pemahaman mendalam atas dasar negara dan konstitusi menuntun aparatur dalam bertindak profesional, netral, serta berorientasi pada persatuan bangsa saat melayani publik. Pemahaman ini menjadi kompas integritas dalam setiap keputusan kedinasan. Pilihan lain menyajikan tujuan pragmatis kepegawaian yang tidak relevan.</p><p>Jawaban tepat: <strong>E. Dapat memahami arti Pancasila, UUD 1945, dan integritas NKRI</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kenaikan pangkat aparatur diatur berdasarkan prestasi kerja dan ketentuan regulasi manajemen ASN.</li><li><strong>B</strong>: Penghafalan hukum pidana secara khusus merupakan ranah fungsional penegak hukum peradilan.</li><li><strong>C</strong>: Negosiasi proyek komersial adalah ranah bisnis swasta di luar koridor pengabdian negara.</li><li><strong>D</strong>: Aparatur sipil negara dilarang terlibat dalam kepengurusan partai politik demi asas netralitas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 08) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>dapat memahami arti Pancasila,</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Nilai-nilai luhur yang terkandung di dalam Pancasila secara historis telah diterapkan sejak...", "options": ["Zaman Sriwijaya dan Majapahit dalam kemasyarakatan", "Kedatangan ekspedisi penjelajah samudra bangsa barat", "Pembentukan kongsi dagang VOC di pelabuhan Batavia", "Perang pasifik meletus di kawasan perairan Asia timur", "Berdirinya persekutuan kerajaan feodal di Malaka"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sejarah Nilai Pancasila Zaman Kuno</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Istilah Pancasila telah dikenal <strong>sejak zaman Sriwijaya dan Majapahit dimana nilai-nilai yang terkandung di dalam Pancasila sudah diterapkan dalam kehidupan kemasyarakatan maupun kenegaraan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena akar nilai toleransi, musyawarah, dan keadilan telah lama hidup dalam peradaban Nusantara jauh sebelum masa kolonialisme datang. Pancasila bukan barang asing, melainkan mutiara kepribadian bangsa sendiri yang telah mengakar berabad-abad. Pilihan lain merujuk pada masa penjajahan bangsa asing.</p><p>Jawaban tepat: <strong>A. Zaman Sriwijaya dan Majapahit dalam kemasyarakatan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Ekspedisi penjelajah barat membawa misi kolonialisme yang merugikan kedaulatan nusantara.</li><li><strong>C</strong>: VOC adalah kongsi dagang penjajah yang menerapkan monopoli perdagangan rempah-rempah.</li><li><strong>D</strong>: Perang pasifik adalah palagan perang antarnegara imperialis pada pertengahan abad kedua puluh.</li><li><strong>E</strong>: Kerajaan Malaka merupakan pusat bandar perniagaan maritim di jalur selat Malaka.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 09) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>sejak zaman Sriwijaya dan Majapahit dimana nilai-nilai</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Pada zaman kejayaan Majapahit, istilah Pancasila tertulis dalam karya sastra karangan Mpu Prapanca yaitu...", "options": ["Kitab Arjunawiwaha", "Buku Negara Kertagama", "Kitab Smaradhahana", "Serat Centhini", "Babad Tanah Jawi"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kitab Kuno Pemuat Istilah Pancasila</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Istilah Pancasila telah dikenal sejak zaman Majapahit sebagaimana <strong>tertulis dalam buku Negara Kertagama karangan Mpu Prapanca</strong> dan buku Sutasoma karangan Mpu Tantular.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena Kakawin Nagarakretagama (Desawarnana) karya Mpu Prapanca yang ditulis pada masa Raja Hayam Wuruk memuat dokumentasi istilah dan praktik ketatanegaraan yang bersumber dari lima sendi moral. Kitab ini menjadi bukti autentik sejarah peradaban Indonesia. Pilihan lain adalah karya sastra Jawa kuno lainnya yang bukan ditulis Mpu Prapanca.</p><p>Jawaban tepat: <strong>B. Buku Negara Kertagama</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kitab Arjunawiwaha merupakan kakawin sastra karangan Mpu Kanwa pada zaman Kerajaan Kediri.</li><li><strong>C</strong>: Kitab Smaradhahana adalah karya sastra Jawa kuno yang digubah oleh Mpu Dharmaja.</li><li><strong>D</strong>: Serat Centhini adalah karya sastra ensiklopedis Jawa yang ditulis pujangga istana Surakarta.</li><li><strong>E</strong>: Babad Tanah Jawi merupakan teks historiografi tradisional yang memuat silsilah raja Mataram.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 09) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>tertulis dalam buku Negara Kertagama karangan Mpu Prapanca</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Dalam buku Sutasoma karangan Mpu Tantular, istilah Pancasila dimaknai secara etis sebagai...", "options": ["Lima panglima perang pelindung kedaulatan keraton", "Kewajiban upeti hasil pertanian bagi lima kadipaten", "Berbatu sendi yang lima dan pelaksanaan kesusilaan yang lima", "Pedoman pembagian wilayah maritim menjadi lima dermaga", "Sumpah kesetiaan prajurit istana kepada lima penasihat"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Arti Pancasila dalam Buku Sutasoma</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam buku Sutasoma karangan Mpu Tantular, istilah <strong>Pancasila mempunyai arti berbatu sendi yang lima, pelaksanaan kesusilaan yang lima</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena Mpu Tantular mengartikan Pancasila sebagai lima asas pokok pembentuk watak susila, mencakup larangan membunuh, mencuri, berzina, berdusta, dan meminum minuman memabukkan (*Pancasila Krama*). Landasan moral ini membentuk ketertiban hidup bermasyarakat. Pilihan lain mengaitkannya secara keliru dengan urusan militer atau upeti kerajaan.</p><p>Jawaban tepat: <strong>C. Berbatu sendi yang lima dan pelaksanaan kesusilaan yang lima</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Buku Sutasoma memuat ajaran etika kesusilaan dan toleransi beragama, bukan gelar kemiliteran.</li><li><strong>B</strong>: Ketentuan upeti diatur dalam hukum tata kelola perpajakan kerajaan, bukan pengertian Pancasila.</li><li><strong>D</strong>: Pembagian zona pelabuhan adalah urusan syahbandar maritim pada masa kerajaan.</li><li><strong>E</strong>: Sumpah kesetiaan prajurit merupakan ikrar kedinasan militer, bukan rumusan istilah Pancasila.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 09) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pancasila mempunyai arti berbatu sendi yang lima, pelaksanaan kesusilaan yang</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Secara etimologi bahasa Sansekerta, kata Pancasila terbentuk dari gabungan dua kata yang berarti...", "options": ["Panca berarti persatuan dan Sila berarti kedaulatan", "Panca berarti kekuasaan dan Sila berarti permusyawaratan", "Panca berarti keadilan dan Sila berarti kemakmuran", "Panca berarti lima dan Sila berarti dasar atau asas", "Panca berarti kerakyatan dan Sila berarti kebijaksanaan"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Etimologi Kata Pancasila</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Istilah Pancasila berasal dari bahasa <strong>Sansekerta yaitu Panca berarti lima dan Sila berarti dasar atau asas</strong>. Jadi Pancasila sebagai dasar negara terdiri dari lima asas.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena secara harfiah *panca* merujuk pada jumlah bilangan lima, sedangkan *sila* merujuk pada batu sendi, alas, dasar, atau prinsip moral tingkah laku yang baik. Gabungan keduanya melandasi fondasi berdirinya Negara Kesatuan Republik Indonesia. Pilihan lain mencampuradukkan makna etimologis dengan nama-nama sila.</p><p>Jawaban tepat: <strong>D. Panca berarti lima dan Sila berarti dasar atau asas</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Persatuan adalah makna sila ketiga, bukan terjemahan harfiah dari kata bilangan panca.</li><li><strong>B</strong>: Kekuasaan dan permusyawaratan bukan arti leksikal dari unsur bahasa Sansekerta tersebut.</li><li><strong>C</strong>: Keadilan dan kemakmuran merupakan cita-cita tujuan sila kelima, bukan arti dasar kata.</li><li><strong>E</strong>: Kerakyatan dan kebijaksanaan adalah penggalan frasa dari rumusan sila keempat Pancasila.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 09) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Sansekerta yaitu Panca berarti lima</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Nama Pancasila sebagai dasar negara resmi lahir pada tanggal 1 Juni 1945 atas usulan tokoh bangsa...", "options": ["Mohammad Hatta dalam sidang Panitia Persiapan", "Mr. Muhammad Yamin dalam pidato pembukaan rapat", "Prof. Dr. Soepomo dalam sidang panitia kecil sembilan", "Radjiman Wedyodiningrat dalam kapasitas ketua sidang", "Ir. Soekarno dalam sidang pertama BPUPKI"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kelahiran Nama Pancasila 1 Juni 1945</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Istilah nama Pancasila sebagai dasar negara lahir pada tanggal 1 Juni 1945, <strong>sebagaimana diusulkan Ir. Soekarno dalam sidang pertama BPUPKI</strong>, yang kemudian disetujui sidang.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena pada hari terakhir sidang pertama BPUPKI tanggal 1 Juni 1945, Ir. Soekarno menyampaikan pidato monumental tanpa teks yang memperkenalkan nama Pancasila sebagai *philosofische grondslag* dasar negara Indonesia merdeka. Tanggal ini diperingati sebagai Hari Lahir Pancasila. Pilihan lain menunjuk tokoh yang berperan dalam kapasitas atau momentum berbeda.</p><p>Jawaban tepat: <strong>E. Ir. Soekarno dalam sidang pertama BPUPKI</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Mohammad Hatta adalah proklamator dan wakil presiden, bukan pengusul awal nama Pancasila 1 Juni.</li><li><strong>B</strong>: Mr. Muhammad Yamin menyampaikan gagasan asas pada 29 Mei 1945 namun belum menyebut nama Pancasila.</li><li><strong>C</strong>: Prof. Soepomo menguraikan teori negara integralistik pada sidang BPUPKI tanggal 31 Mei 1945.</li><li><strong>D</strong>: Dr. Radjiman Wedyodiningrat bertindak sebagai ketua yang memimpin jalannya sidang BPUPKI.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 09) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>sebagaimana diusulkan Ir. Soekarno dalam sidang pertama BPUPKI,</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Badan yang bertugas merumuskan usulan dasar negara Indonesia pada masa persiapan kemerdekaan adalah...", "options": ["Badan Penyelidik Usaha-Usaha Persiapan Kemerdekaan Indonesia", "Komite Nasional Indonesia Pusat pengganti parlemen sementara", "Dewan Pertimbangan Agung penasihat presiden pertama", "Majelis Musyawarah Rakyat bentukan pemerintah karesidenan", "Panitia Perancang Keuangan Pembagian Harta Warisan Penjajah"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Lembaga Perumus Dasar Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pancasila sebagai dasar negara dirumuskan oleh <strong>Badan Penyelidik Usaha-Usaha Persiapan Kemerdekaan Indonesia (BPUPKI)</strong> pada sidang-sidangnya di tahun 1945.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena BPUPKI (*Dokuritsu Zyunbi Tyoosakai*) dibentuk untuk menyelidiki dan merumuskan syarat-syarat tata kelola berdirinya negara merdeka, termasuk merumuskan dasar negara dan rancangan undang-undang dasar. Forum inilah kawah candradimuka konsepsi Pancasila. Pilihan lain adalah lembaga yang dibentuk pasca-kemerdekaan.</p><p>Jawaban tepat: <strong>A. Badan Penyelidik Usaha-Usaha Persiapan Kemerdekaan Indonesia</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: KNIP dibentuk pada 18 Agustus 1945 untuk membantu presiden sebelum terbentuknya MPR dan DPR.</li><li><strong>C</strong>: DPA adalah lembaga tinggi penasihat negara yang dibentuk berdasarkan UUD 1945 pasca-proklamasi.</li><li><strong>D</strong>: Majelis musyawarah karesidenan bukan forum perumus konstitusi pendirian negara Republik Indonesia.</li><li><strong>E</strong>: Panitia perancang keuangan merupakan panitia bagian internal BPUPKI pimpinan Moh. Hatta.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 09) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Badan Penyelidik Usaha-Usaha Persiapan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Pancasila secara yuridis konstitusional disahkan sebagai dasar negara Republik Indonesia oleh...", "options": ["Sidang Umum Gabungan Serikat Pekerja di Kota Surabaya", "Panitia Persiapan Kemerdekaan Indonesia pada 18 Agustus 1945", "Dewan Perwakilan Rakyat Gotong Royong pada era peralihan", "Musyawarah Kerja Pemuda Angkatan Empat Puluh Lima", "Komite Eksekutif Tentara Keamanan Rakyat di Markas Agung"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengesahan Pancasila sebagai Dasar Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pancasila disahkan sebagai dasar negara oleh <strong>Panitia Persiapan Kemerdekaan Indonesia (PPKI) pada tanggal 18 Agustus 1945</strong> bersamaan dengan penetapan UUD 1945.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena pada sidang tanggal 18 Agustus 1945, PPKI secara sah menetapkan Pembukaan dan Batang Tubuh UUD 1945, di mana alinea keempat Pembukaan memuat rumusan sah Pancasila. Pengesahan ini memberi legitimasi yuridis mutlak bagi berlakunya Pancasila. Pilihan lain bukan badan representatif pembentuk negara berdaulat.</p><p>Jawaban tepat: <strong>B. Panitia Persiapan Kemerdekaan Indonesia pada 18 Agustus 1945</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Serikat pekerja merupakan organisasi profesi buruh, bukan lembaga pembentuk dasar negara.</li><li><strong>C</strong>: DPR-GR merupakan lembaga legislatif bentukan masa Demokrasi Terpimpin tahun 1960.</li><li><strong>D</strong>: Musyawarah pemuda adalah konsolidasi eksponen revolusi, bukan badan penetap konstitusi.</li><li><strong>E</strong>: Tentara Keamanan Rakyat dibentuk pada Oktober 1945 sebagai institusi pertahanan bersenjata.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 09) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Persiapan Kemerdekaan Indonesia (PPKI) pada tanggal 18 Agustus 1945.</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Rumusan sah sila-sila Pancasila yang wajib diamalkan oleh segenap bangsa Indonesia tercantum dalam...", "options": ["Penjelasan umum pasal-pasal aturan peralihan konstitusi", "Alinea pertama naskah deklarasi proklamasi kemerdekaan", "Alinea keempat Pembukaan UUD 1945 hasil penetapan PPKI", "Batang tubuh konstitusi pada pasal jaminan hak warga", "Koleksi naskah pidato rancangan pembukaan panitia sembilan"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Rumusan Sah Sila-Sila Pancasila</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Rumusan sila-sila Pancasila yang sah yang wajib diamalkan bangsa Indonesia adalah <strong>rumusan sila-sila Pancasila yang tercantum dalam alinea keempat Pembukaan UUD 1945</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena teks yang mengikat secara yuridis-formal bagi seluruh warga dan penyelenggara negara adalah lima sila yang termuat dalam alinea keempat Pembukaan UUD 1945 yang disahkan PPKI. Rumusan inilah yang mengakhiri perdebatan naskah pra-kemerdekaan. Pilihan lain menunjuk bagian teks lain yang bukan rumusan resmi lima sila.</p><p>Jawaban tepat: <strong>C. Alinea keempat Pembukaan UUD 1945 hasil penetapan PPKI</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Aturan peralihan memuat ketentuan transisi hukum pasca-amandemen, bukan teks lima sila.</li><li><strong>B</strong>: Teks Proklamasi 17 Agustus 1945 terdiri dari dua paragraf singkat pernyataan kemerdekaan.</li><li><strong>D</strong>: Batang tubuh menjabarkan norma hukum konstitusi, sedangkan lima sila berada di Pembukaan.</li><li><strong>E</strong>: Piagam Jakarta memuat rancangan awal sebelum disempurnakan pada sidang PPKI 18 Agustus 1945.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 09) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>tercantum dalam alinea keempat Pembukaan UUD</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Pemahaman terhadap hakikat kedudukan Pancasila pada dasarnya dikembalikan kepada dua pengertian pokok yaitu...", "options": ["Sistem peradilan modern dan doktrin kedaulatan wilayah", "Hukum adat kebiasaan dan tata cara peribadatan", "Panduan perdagangan maritim dan perjanjian diplomasi luar", "Pandangan hidup bangsa dan sebagai dasar negara", "Pedoman kepemimpinan militer dan strategi logistik perang"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dua Pengertian Pokok Hakikat Pancasila</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pemahaman terhadap Pancasila pada hakikatnya dikembalikan kepada dua pengertian pokok yaitu <strong>pengertian Pancasila sebagai pandangan hidup dan sebagai dasar negara</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena sebagai pandangan hidup (*weltanschauung*), Pancasila menjadi pedoman moral tingkah laku sehari-hari, sedangkan sebagai dasar negara (*staatsfundamentalnorm*), Pancasila menjadi sumber hukum tata kelola negara. Dua dimensi ini menyatu dalam kepribadian bangsa. Pilihan lain merujuk pada bidang teknis sektoral di luar kedudukan pokok dasar negara.</p><p>Jawaban tepat: <strong>D. Pandangan hidup bangsa dan sebagai dasar negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Sistem peradilan dan doktrin wilayah adalah implementasi teknis konstitusi hukum acara.</li><li><strong>B</strong>: Hukum adat dan ibadah merupakan bagian budaya dan agama, bukan dua pengertian pokok Pancasila.</li><li><strong>C</strong>: Aturan dagang dan diplomasi adalah instrumen kerja sama ekonomi kawasan dan hubungan luar negeri.</li><li><strong>E</strong>: Doktrin militer adalah pedoman pertahanan bersenjata, bukan pengertian hakikat dasar Pancasila.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 09) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pengertian Pancasila sebagai pandangan hidup</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Berdasarkan pengertian pokoknya, Pancasila dalam kehidupan kenegaraan menjalankan fungsi sebagai...", "options": ["Dasar sementara, pedoman diplomasi, dan komando operasi", "Regulasi bisnis, jaminan perbankan, dan pengawas moneter", "Aturan kepangkatan, etika peradilan, dan pembagi wilayah", "Doktrin pertahanan, tata tertib pemilu, dan aliansi", "Dasar statis fundamental, tuntunan dinamis, dan ikatan pemersatu"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tiga Fungsi Pokok Pancasila</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pancasila berfungsi sebagai <strong>dasar yang statis dan fundamental, tuntunan yang dinamis dan ikatan yang dapat mempersatukan bangsa Indonesia</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena Pancasila sebagai dasar statis menjadi pondasi berdirinya NKRI yang kekal, sebagai tuntunan dinamis memandu arah kemajuan bangsa mencapai cita-cita luhur, dan sebagai ikatan pemersatu menjamin hak hidup layak seluruh warga tanpa diskriminasi. Tiga fungsi ini menjaga kelangsungan hidup negara. Pilihan lain memuat istilah operasional birokrasi biasa.</p><p>Jawaban tepat: <strong>E. Dasar statis fundamental, tuntunan dinamis, dan ikatan pemersatu</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pancasila adalah dasar abadi dan fundamental, bukan dasar negara yang bersifat sementara.</li><li><strong>B</strong>: Regulasi bisnis dan moneter adalah ranah hukum dagang serta kebijakan otoritas perbankan.</li><li><strong>C</strong>: Kepangkatan aparatur adalah tata tertib kepegawaian dalam manajemen kepegawaian sipil.</li><li><strong>D</strong>: Doktrin pertahanan dan pemilu merupakan hukum positif operasional di bawah konstitusi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 09) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pancasila berfungsi sebagai dasar yang statis dan fundamental, tuntunan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Fungsi pokok dan utama dari Pancasila dalam kedudukannya sebagai dasar negara Republik Indonesia adalah...", "options": ["Fungsi yuridis ketatanegaraan", "Fungsi komersial perdagangan", "Fungsi pertahanan teritorial", "Fungsi kehumasan kementerian", "Fungsi diplomasi konsuler"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Fungsi Pokok Pancasila sebagai Dasar Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pancasila memiliki <strong>fungsi yuridis ketatanegaraan yang merupakan fungsi pokok dan fungsi utama sebagai dasar negara</strong>, di samping fungsi sosiologis serta fungsi etis dan filosofis.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena fungsi yuridis ketatanegaraan menempatkan Pancasila sebagai landasan tertinggi pembentukan seluruh tata hukum positif dan penyelenggaraan kekuasaan negara. Seluruh norma hukum perundang-undangan wajib bersumber dan tidak boleh bertentangan dengan Pancasila. Pilihan lain merupakan fungsi sektoral kementerian yang bukan fungsi dasar negara.</p><p>Jawaban tepat: <strong>A. Fungsi yuridis ketatanegaraan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Fungsi komersial berkaitan dengan aktivitas dunia usaha perniagaan swasta dan BUMN.</li><li><strong>C</strong>: Fungsi pertahanan teritorial dijalankan jajaran angkatan bersenjata demi keutuhan wilayah.</li><li><strong>D</strong>: Fungsi kehumasan adalah sarana penyebaran informasi publik dinas pada instansi pemerintah.</li><li><strong>E</strong>: Fungsi konsuler adalah pelayanan administrasi perwakilan keduataan di luar negeri.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 10) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>fungsi yuridis ketatanegaraan yang merupakan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Selain fungsi yuridis ketatanegaraan, Pancasila dalam tata kehidupan bangsa juga menjalankan...", "options": ["Fungsi militeristis doktriner", "Fungsi sosiologis serta fungsi etis dan filosofis", "Fungsi fiskal moneter regional", "Fungsi adjudikasi sengketa perdata", "Fungsi regulasi pasar modal bursa"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tiga Dimensi Fungsi Pancasila</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pancasila memiliki fungsi yuridis ketatanegaraan sebagai fungsi pokok, serta <strong>fungsi sosiologis serta fungsi etis dan filosofis</strong> dalam membimbing pergaulan kemasyarakatan dan kepribadian pribadi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena Pancasila tidak sekadar mengatur struktur kelembagaan negara, melainkan juga mengakar sebagai pengatur ketertiban sosial (sosiologis) serta penuntun moral budi pekerti individu (etis dan filosofis). Tiga dimensi ini membentuk kepribadian utuh manusia Indonesia. Pilihan lain merujuk pada wewenang lembaga teknis perbankan dan peradilan.</p><p>Jawaban tepat: <strong>B. Fungsi sosiologis serta fungsi etis dan filosofis</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pancasila menjunjung tinggi perikemanusiaan dan musyawarah, bukan indoktrinasi kemiliteran.</li><li><strong>C</strong>: Kebijakan fiskal dan moneter dikelola oleh kementerian keuangan bersama bank sentral.</li><li><strong>D</strong>: Adjudikasi sengketa perdata merupakan kewenangan badan peradilan umum di pengadilan negeri.</li><li><strong>E</strong>: Regulasi pasar modal merupakan tugas otoritas jasa keuangan dan bursa efek Indonesia.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 10) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>fungsi pokok dan fungsi utama sebagai dasar negara, fungsi sosiologis serta fungsi</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan Undang-Undang Nomor 12 Tahun 2011, kedudukan hukum Pancasila ditegaskan sebagai...", "options": ["Hukum acara peradilan tata usaha negara", "Peraturan teknis operasional lembaga kepresidenan", "Sumber dari segala sumber hukum negara", "Konvensi ketatanegaraan pelengkap undang-undang", "Traktat perbatasan wilayah perairan kepulauan"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pancasila Sumber Segala Sumber Hukum</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kedudukan hukum Pancasila selain sebagai dasar negara juga sebagai <strong>sumber dari segala sumber hukum negara sebagaimana tercantum dalam Undang-Undang Nomor 12 Tahun 2011</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena Pancasila menempati kedudukan tertinggi sebagai dasar moral, jiwa, dan cita hukum nasional (*rechtsidee*), sehingga seluruh materi muatan peraturan perundang-undangan tidak boleh menyimpang darinya. Prinsip ini ditegaskan dalam undang-undang pembentukan peraturan. Pilihan lain adalah cabang hukum kodifikasi teknis biasa.</p><p>Jawaban tepat: <strong>C. Sumber dari segala sumber hukum negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Hukum acara PTUN adalah prosedur peradilan penyelesaian sengketa pejabat administrasi.</li><li><strong>B</strong>: Peraturan operasional presiden berkedudukan di bawah konstitusi dan undang-undang.</li><li><strong>D</strong>: Konvensi ketatanegaraan adalah kebiasaan tata negara tidak tertulis yang melengkapi pasal.</li><li><strong>E</strong>: Traktat perbatasan adalah perjanjian internasional antarnegara mengenai batas teritorial.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 10) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Kedudukan hukum Pancasila selain sebagai dasar negara juga sebagai sumber dari</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Undang-Undang Nomor 12 Tahun 2011 tentang Pembentukan Peraturan Perundang-undangan telah diubah kedua kalinya dengan...", "options": ["Undang-Undang Nomor 15 Tahun 2019", "Undang-Undang Nomor 17 Tahun 2003", "Undang-Undang Nomor 23 Tahun 2014", "Undang-Undang Nomor 13 Tahun 2022", "Undang-Undang Nomor 30 Tahun 2014"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Regulasi Pembentukan Peraturan Perundang-undangan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>UU No. 12 Tahun 2011 telah diubah dengan <strong>Undang-Undang Nomor 13 Tahun 2022</strong> tentang Perubahan Kedua atas UU No. 12 Tahun 2011 tentang Pembentukan Peraturan Perundang-undangan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat. Undang-Undang Nomor 13 Tahun 2022 mengesahkan perubahan kedua regulasi pembentukan peraturan perundang-undangan, salah satunya untuk mengakomodasi metode omnibus law dan penguatan partisipasi masyarakat bermakna (meaningful participation).</p><p>Jawaban tepat: <strong>D. Undang-Undang Nomor 13 Tahun 2022</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: UU 15/2019 merupakan perubahan pertama atas UU 12/2011.</li><li><strong>B</strong>: UU 17/2003 mengatur asas umum pengelolaan keuangan negara.</li><li><strong>C</strong>: UU 23/2014 mengatur penyelenggaraan pemerintahan daerah.</li><li><strong>E</strong>: UU 30/2014 mengatur tata kelola administrasi pemerintahan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 10) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Undang-Undang Nomor 13 Tahun</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Dalam hubungannya dengan Undang-Undang Dasar 1945, kedudukan nilai-nilai Pancasila berfungsi...", "options": ["Menggantikan seluruh rumusan naskah pasal konstitusi", "Menjadi lampiran pelengkap operasional kementerian", "Membatasi kewenangan peradilan mahkamah konstitusi", "Menetapkan rincian teknis pembagian anggaran daerah", "Menjiwai Pembukaan dan pasal-pasal UUD 1945"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Hubungan Pancasila dengan UUD 1945</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam hubungannya dengan Undang-Undang Dasar 1945 (UUD 1945), <strong>Pancasila menjiwai Pembukaan dan pasal-pasal UUD 1945</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena Pancasila merupakan roh dan asas kerohanian yang mengalir ke dalam setiap alinea Pembukaan serta dijabarkan lebih lanjut ke dalam pasal-pasal konstitusi. Seluruh pasal konstitusi adalah penjabaran norma dasar Pancasila. Pilihan lain keliru memposisikan Pancasila sebagai sekadar lampiran atau pembatas yudisial.</p><p>Jawaban tepat: <strong>E. Menjiwai Pembukaan dan pasal-pasal UUD 1945</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pancasila menjiwai pasal-pasal konstitusi, bukan menggantikan teks pasal batang tubuh.</li><li><strong>B</strong>: Pancasila adalah norma dasar fundamental negara, bukan sekadar lampiran teknis birokrasi.</li><li><strong>C</strong>: Pancasila menjadi batu uji bagi Mahkamah Konstitusi dalam menguji keabsahan undang-undang.</li><li><strong>D</strong>: Rincian alokasi anggaran dibahas dalam undang-undang APBN, bukan fungsi langsung norma dasar.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 10) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>1945), Pancasila menjiwai Pembukaan dan pasal-pasal UUD 1945.</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Sebagai bagian yang tidak terpisahkan dari Pembukaan UUD 1945, Pancasila memiliki...", "options": ["Kedudukan hukum yang kuat", "Kedudukan hukum yang fleksibel bersyarat", "Sifat sementara menunggu amandemen total", "Kekuatan setara peraturan pemerintah pengganti", "Kekuatan hukum di bawah undang-undang sektoral"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kedudukan Hukum Pancasila dalam Pembukaan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam kaitan dengan fungsi pokoknya sebagai dasar negara, Pancasila sebagai <strong>bagian dari Pembukaan UUD 1945 mempunyai kedudukan hukum yang kuat</strong> dan tidak dapat diubah.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena Pembukaan UUD 1945 yang memuat Pancasila berkedudukan sebagai *staatsfundamentalnorm* yang bersifat tetap, mengikat, dan tidak dapat diubah oleh siapapun termasuk MPR hasil pemilu. Mengubah pembukaan sama artinya dengan membubarkan negara proklamasi. Pilihan lain merendahkan derajat yuridis tertinggi Pancasila.</p><p>Jawaban tepat: <strong>A. Kedudukan hukum yang kuat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kedudukan hukum Pancasila bersifat mengikat mutlak dan permanen, bukan bersyarat lentur.</li><li><strong>C</strong>: MPR telah menyepakati bahwa Pembukaan UUD 1945 tidak akan diamandemen sedikitpun.</li><li><strong>D</strong>: Perppu berada di bawah undang-undang, jauh di bawah derajat konstitusi dan Pancasila.</li><li><strong>E</strong>: Undang-undang sektoral wajib tunduk dan bersumber pada Pancasila, bukan sebaliknya.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 10) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>bagian dari Pembukaan UUD 1945 mempunyai kedudukan hukum yang kuat.</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Pokok-pokok pikiran dalam Pembukaan UUD 1945 hakikatnya adalah Pancasila yang berkedudukan sebagai...", "options": ["Doktrin komando militer pertahanan", "Cita-cita hukum atau rechtsidee negara", "Prosedur baku pelayanan perizinan daerah", "Kode etik profesi pengacara peradilan", "Tata tertib persidangan dewan perwakilan"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Konsep Cita Hukum (Rechtsidee)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pembukaan UUD 1945 mengandung pokok-pokok pikiran yang tidak lain adalah Pancasila yang merupakan <strong>cita-cita hukum (rechtsidee) yang menguasai hukum dasar</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena *rechtsidee* berfungsi sebagai pemandu (*guiding principle*) dan batu uji bagi segala produk perundang-undangan agar selalu berorientasi pada keadilan, kemanusiaan, dan persatuan. Cita hukum ini mengarahkan tujuan pembentukan hukum nasional. Pilihan lain merupakan tata aturan administratif operasional profesi.</p><p>Jawaban tepat: <strong>B. Cita-cita hukum atau rechtsidee negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Doktrin militer adalah pedoman taktis pertempuran bersenjata, bukan cita hukum nasional.</li><li><strong>C</strong>: Prosedur perizinan daerah adalah standar operasional pelayanan publik teknis dinas.</li><li><strong>D</strong>: Kode etik advokat adalah standar integritas moral internal organisasi profesi hukum.</li><li><strong>E</strong>: Tata tertib sidang adalah aturan mekanisme persidangan internal lembaga perwakilan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 10) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pancasila yang merupakan cita-cita hukum (rechtsidee) yang menguasai hukum</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Cita-cita hukum (rechtsidee) Pancasila menguasai hukum dasar negara Republik Indonesia yang meliputi...", "options": ["Ruang lingkup hukum pidana formil kepolisian", "Kebijakan tarif pajak daerah kepulauan terluar", "Hukum dasar tertulis maupun hukum dasar tidak tertulis", "Tata kelola anggaran belanja badan usaha swasta", "Perjanjian perdagangan komoditas ekspor antarpulau"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Rechtsidee Menguasai Hukum Dasar</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Rechtsidee Pancasila menguasai <strong>hukum dasar, baik hukum dasar tertulis maupun hukum dasar tidak tertulis (konvensi)</strong> dalam ketatanegaraan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena baik undang-undang dasar tertulis (UUD 1945) maupun konvensi ketatanegaraan yang tumbuh dalam praktik peradilan dan kenegaraan wajib berjiwa Pancasila. Kesatuan penguasaan norma ini menjamin kepastian dan moralitas hukum bernegara. Pilihan lain hanya mencakup satu aspek pajak, pidana, atau bisnis privat.</p><p>Jawaban tepat: <strong>C. Hukum dasar tertulis maupun hukum dasar tidak tertulis</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Hukum pidana hanya sebagian kecil hukum tertulis positif dalam sistem peradilan pidana.</li><li><strong>B</strong>: Tarif pajak daerah adalah regulasi fiskal daerah di bawah undang-undang pajak.</li><li><strong>D</strong>: Anggaran korporasi swasta diatur dalam ketentuan hukum perseroan terbatas komersial.</li><li><strong>E</strong>: Perdagangan antarpulau merupakan urusan distribusi niaga dan hukum perikatan perdata.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 10) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>dasar, baik hukum dasar tertulis maupun hukum dasar tidak tertulis (konvensi).</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Alinea pertama Pembukaan UUD 1945 memuat penegasan mendasar tentang...", "options": ["Penetapan susunan kabinet kementerian pertama", "Pengangkatan pejabat presiden dan wakil presiden", "Pembagian wilayah karesidenan di seluruh pulau", "Pernyataan hak kemerdekaan bagi setiap bangsa", "Tata cara pengesahan rancangan anggaran pendapatan"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Muatan Pokok Alinea Pertama Pembukaan UUD 1945</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pembukaan UUD 1945 terdiri dari 4 alinea, di mana alinea pertama memuat: <strong>Pernyataan hak kemerdekaan bagi setiap bangsa</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena kalimat 'Bahwa sesungguhnya kemerdekaan itu ialah hak segala bangsa dan oleh sebab itu, maka penjajahan di atas dunia harus dihapuskan' menegaskan dalil universal pengakuan martabat hak asasi kemerdekaan setiap bangsa. Dalil moral ini menjadi komitmen abadi Indonesia menentang kolonialisme. Pilihan lain adalah hasil sidang PPKI dalam bidang kelembagaan.</p><p>Jawaban tepat: <strong>D. Pernyataan hak kemerdekaan bagi setiap bangsa</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Susunan kabinet pertama dibentuk oleh presiden Soekarno pasca-proklamasi September 1945.</li><li><strong>B</strong>: Pemilihan presiden dan wakil presiden pertama diputuskan dalam sidang PPKI 18 Agustus.</li><li><strong>C</strong>: Pembagian wilayah provinsi/karesidenan diputuskan pada sidang lanjutan PPKI 19 Agustus.</li><li><strong>E</strong>: Tata cara pengesahan anggaran diatur dalam bab keuangan negara pada batang tubuh UUD.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 10) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pernyataan hak kemerdekaan bagi setiap bangsa;</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Muatan pokok yang termaktub di dalam alinea kedua Pembukaan UUD 1945 berkenaan dengan...", "options": ["Sistem permodalan bank pemerintah daerah", "Pedoman hubungan luar negeri antarnegara sekutu", "Struktur hierarki pengadilan tinggi agama", "Tata cara pengangkatan perwira angkatan perang", "Pernyataan hasil perjuangan kemerdekaan bangsa"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Muatan Pokok Alinea Kedua Pembukaan UUD 1945</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Alinea kedua Pembukaan UUD 1945 memuat: <strong>Pernyataan tentang hasil perjuangan kemerdekaan bangsa Indonesia</strong> yang mengantarkan ke depan pintu gerbang kemerdekaan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena alinea kedua merefleksikan keberhasilan pergerakan kemerdekaan Indonesia yang telah mengantarkan bangsa bersatu, berdaulat, adil, dan makmur. Kalimat ini menghargai jasa para pahlawan perjuangan nasional. Pilihan lain merujuk pada regulasi teknis kelembagaan yang tidak relevan dengan mukadimah konstitusi.</p><p>Jawaban tepat: <strong>E. Pernyataan hasil perjuangan kemerdekaan bangsa</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Sistem permodalan perbankan daerah diatur dalam regulasi badan usaha milik daerah.</li><li><strong>B</strong>: Hubungan antarnegara sekutu adalah fakta diplomasi perang dunia, bukan teks Pembukaan.</li><li><strong>C</strong>: Hierarki pengadilan agama diatur dalam undang-undang tentang peradilan agama.</li><li><strong>D</strong>: Pengangkatan perwira militer diatur dalam undang-undang pertahanan dan kemiliteran.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 10) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pernyataan tentang hasil perjuangan kemerdekaan bangsa Indonesia;</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Tiga pernyataan pertama di dalam Pembukaan UUD 1945 pada hakikatnya memuat penjelasan mengenai...", "options": ["Keadaan dan peristiwa mendahului terbentuknya NKRI", "Rincian teknis pembagian departemen kementerian", "Tata cara pelaksanaan pemilihan anggota dewan", "Sistem sanksi hukum perdata bagi para pelanggar", "Ketentuan penataan perbatasan wilayah kepulauan"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Karakteristik Tiga Pernyataan Pertama Pembukaan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Tiga pernyataan pertama dalam Pembukaan adalah mengenai <strong>keadaan-keadaan atau peristiwa-peristiwa yang mendahului terbentuknya Negara Kesatuan Republik Indonesia</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena alinea 1, 2, dan 3 melukiskan latar belakang penjajahan, perjuangan pergerakan kemerdekaan, serta pengakuan berkat rahmat Allah yang melahirkan proklamasi kemerdekaan. Ketiganya merupakan premis historis lahirnya negara. Pilihan lain memuat aturan teknis administratif yang diatur dalam undang-undang.</p><p>Jawaban tepat: <strong>A. Keadaan dan peristiwa mendahului terbentuknya NKRI</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Pembagian departemen kementerian diatur dalam undang-undang tentang kementerian negara.</li><li><strong>C</strong>: Pemilihan anggota dewan legislatif diatur dalam undang-undang penyelenggaraan pemilu.</li><li><strong>D</strong>: Sanksi hukum perdata diatur dalam kitab undang-undang hukum perdata di pengadilan.</li><li><strong>E</strong>: Batas wilayah kepulauan diatur dalam undang-undang wilayah negara dan konvensi UNCLOS.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 11) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>yang mendahului terbentuknya Negara Kesatuan Republik Indonesia. Ketiga</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C4 - Analisis", "question": "Secara yuridis ketatanegaraan, hubungan antara tiga pernyataan pertama Pembukaan dengan pasal-pasal UUD 1945 adalah...", "options": ["Memiliki hubungan subordinasi komando hierarki militer", "Tidak mempunyai hubungan organis dengan pasal-pasal UUD", "Dapat membatalkan keberlakuan seluruh pasal peradilan", "Menjadi acuan teknis penghitungan pembagian devisa", "Berfungsi sebagai aturan peralihan sementara konstitusi"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Hubungan Alinea 1-3 dengan Batang Tubuh</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Ketiga pernyataan pertama (alinea 1 s/d 3) <strong>tidak mempunyai hubungan organis dengan pasal-pasal UUD 1945</strong>, berbeda dengan alinea keempat yang berakar organis.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena alinea 1 sampai 3 berkedudukan sebagai deklarasi historis dan filosofis penyebab lahirnya kemerdekaan, sehingga tidak dijabarkan langsung menjadi pasal-pasal batang tubuh organisasi negara. Penjabaran organis baru terjadi dari alinea keempat. Pilihan lain memberikan penafsiran hukum yang keliru.</p><p>Jawaban tepat: <strong>B. Tidak mempunyai hubungan organis dengan pasal-pasal UUD</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pembukaan UUD bukan instrumen komando militer bersenjata, melainkan landasan moral.</li><li><strong>C</strong>: Pembukaan tidak membatalkan pasal peradilan, melainkan menjiwai keadilan konstitusi.</li><li><strong>D</strong>: Penghitungan devisa adalah kebijakan moneter bank sentral, bukan fungsi alinea pembukaan.</li><li><strong>E</strong>: Aturan peralihan termuat di bagian akhir batang tubuh UUD 1945, bukan di Pembukaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 11) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pernyataan itu tidak mempunyai hubungan organis dengan pasal-pasal UUD 1945,</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Pernyataan keempat Pembukaan UUD 1945 memiliki keterkaitan erat dengan batang tubuh karena mempunyai...", "options": ["Sifat terpisah dan saling bertentangan secara yurisdiksi", "Kedudukan lebih rendah dari aturan operasional dinas", "Hubungan kausal dan organis dengan pasal-pasal UUD", "Kedudukan sejajar dengan peraturan bupati di daerah", "Kewenangan membatalkan putusan perdata pengadilan negeri"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Hubungan Kausal dan Organis Alinea Keempat</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pernyataan keempat mengandung pokok pikiran ajaran Pancasila, <strong>sehingga dengan demikian mempunyai hubungan kausal dan organis dengan Pasal-pasal UUD 1945</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena hubungan kausal bermakna Pembukaan merupakan penyebab lahirnya batang tubuh, sedangkan hubungan organis bermakna pokok-pokok pikiran Pancasila di alinea keempat dijabarkan secara sistemik ke dalam seluruh pasal UUD 1945. Keduanya membentuk satu kesatuan naskah konstitusi. Pilihan lain mendegradasi wibawa konstitusi negara.</p><p>Jawaban tepat: <strong>C. Hubungan kausal dan organis dengan pasal-pasal UUD</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pembukaan dan batang tubuh UUD 1945 harmonis menyatu, bukan saling bertentangan.</li><li><strong>B</strong>: Pembukaan berkedudukan sebagai norma tertinggi, bukan berada di bawah aturan dinas.</li><li><strong>D</strong>: Peraturan bupati merupakan regulasi daerah terendah, jauh di bawah konstitusi negara.</li><li><strong>E</strong>: Kewenangan pembatalan putusan perdata berada di Mahkamah Agung melalui jalur kasasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 11) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pancasila, sehingga dengan demikian mempunyai hubungan kausal dan organis</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Alinea keempat Pembukaan UUD 1945 sangat fundamental bagi bangunan hukum negara karena memuat...", "options": ["Ketentuan batas yurisdiksi perairan teritorial pulau", "Pedoman besaran bunga pinjaman modal perbankan", "Mekanisme pengangkatan diplomat ke luar negeri", "Dasar kerohanian falsafah Pancasila sebagai dasar negara", "Rincian tata cara penyusunan anggaran belanja daerah"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dasar Kerohanian Pancasila dalam Alinea Keempat</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pernyataan keempat memuat <strong>tentang dasar kerohanian (falsafah) Pancasila sebagai dasar negara</strong> yang mengandung pokok pikiran penuntun pasal-pasal UUD 1945.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena dasar kerohanian Pancasila memberikan jiwa dan arah bagi pembentukan lembaga negara, penyelenggaraan pemerintahan, dan perlindungan hak warga negara. Tanpa dasar kerohanian ini, negara kehilangan pedoman moralitas keadilan. Pilihan lain merupakan urusan teknis perbankan, perbatasan, atau diplomasi.</p><p>Jawaban tepat: <strong>D. Dasar kerohanian falsafah Pancasila sebagai dasar negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Batas wilayah teritorial diatur dalam undang-undang kelautan dan deklarasi Djuanda.</li><li><strong>B</strong>: Suku bunga pinjaman ditentukan oleh mekanisme perbankan dan kebijakan bank sentral.</li><li><strong>C</strong>: Pengangkatan duta besar diatur dalam pasal 13 UUD 1945 dengan pertimbangan DPR.</li><li><strong>E</strong>: Tata cara anggaran belanja daerah diatur dalam undang-undang keuangan daerah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 11) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>tetapi pernyataan keempat yaitu tentang dasar kerohanian (falsafah) Pancasila</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Pentingnya memahami suasana kebatinan di samping pasal-pasal hukum dasar dijelaskan oleh tokoh hukum...", "options": ["Mr. Kasman Singodimedjo", "Prof. Dr. Djokosoetono SH", "Mr. Johannes Latuharhary", "Dr. K.R.T. Radjiman Wedyodiningrat", "Prof. Dr. Soepomo SH"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pandangan Prof. Dr. Soepomo SH tentang Konstitusi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Semangat kejiwaan UUD 1945 ditekankan <strong>sebagaimana dijelaskan oleh Prof. Dr. Soepomo SH, bahwa untuk memahami hukum dasar suatu negara tidak cukup hanya memahami pasal-pasalnya saja</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena Prof. Dr. Soepomo SH selaku arsitek utama rancangan UUD 1945 dalam sidang BPUPKI menegaskan bahwa pasal hukum hanyalah kerangka luar, sedangkan jiwa penggeraknya adalah suasana kebatinan atau cita-cita kejiwaan yang melatarbelakanginya. Penafsiran konstitusi wajib merujuk suasana kebatinan tersebut. Pilihan lain adalah tokoh pendiri bangsa lainnya.</p><p>Jawaban tepat: <strong>E. Prof. Dr. Soepomo SH</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Mr. Kasman Singodimedjo berperan penting sebagai ketua KNIP pertama pada masa awal kemerdekaan.</li><li><strong>B</strong>: Prof. Djokosoetono adalah begawan ilmu hukum tata negara dan pendiri PTIK serta Fakultas Hukum UI.</li><li><strong>C</strong>: Mr. Johannes Latuharhary adalah tokoh perumus kemerdekaan asal Maluku dalam anggota PPKI.</li><li><strong>D</strong>: Dr. Radjiman Wedyodiningrat adalah ketua BPUPKI yang memimpin perumusan awal konstitusi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 11) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>UUD 1945, sebagaimana dijelaskan oleh Prof. Dr. Soepomo SH, bahwa untuk</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Dalam menafsirkan ketentuan konstitusi, aparatur penegak hukum tidak cukup membaca bunyi teks pasal, melainkan wajib...", "options": ["Memahami suasana kebatinan dan semangat kejiwaan hukum", "Menyesuaikan dengan kepentingan partai politik berkuasa", "Mengutamakan keuntungan finansial pihak penuntut umum", "Mengadopsi aturan hukum pidana negara asing secara utuh", "Menyerahkan putusan akhir kepada lembaga kepolisian daerah"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Konsep Suasana Kebatinan Hukum Dasar</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Untuk memahami hukum dasar suatu negara <strong>tidak cukup hanya memahami pasal-pasalnya saja, melainkan harus dipahami pula suasana kebatinan (semangat kejiwaan) dari hukum dasar itu</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena penafsiran gramatikal literal semata tanpa memahami filosofi dan cita hukum Pancasila dapat memicu keadilan formalistik yang kering dan menindas nurani keadilan rakyat. Suasana kebatinan mengarahkan penerapan hukum pada tujuan kemaslahatan bersama. Pilihan lain mencerminkan penyimpangan hukum dan keberpihakan politis.</p><p>Jawaban tepat: <strong>A. Memahami suasana kebatinan dan semangat kejiwaan hukum</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Penegakan hukum wajib netral dan bebas dari segala intervensi kepentingan partai politik.</li><li><strong>C</strong>: Aparatur penegak hukum bertugas menegakkan keadilan, bukan mencari keuntungan komersial.</li><li><strong>D</strong>: Sistem hukum nasional berakar pada Pancasila dan budaya bangsa, bukan menjiplak aturan asing.</li><li><strong>E</strong>: Kewenangan memutus perkara peradilan berada di tangan hakim peradilan yang merdeka.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 11) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>saja, melainkan harus dipahami pula suasana kebatinan (semangat kejiwaan) dari</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Pokok pikiran pertama Pembukaan UUD 1945 yang mewajibkan negara melindungi segenap bangsa berakar pada...", "options": ["Sila kesatu Ketuhanan Yang Maha Esa", "Sila ketiga persatuan Indonesia", "Sila kedua Kemanusiaan Yang Adil dan Beradab", "Sila keempat Kerakyatan dalam permusyawaratan", "Sila kelima Keadilan sosial bagi seluruh rakyat"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pokok Pikiran Pertama Pembukaan UUD 1945</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pokok pikiran pertama menegaskan bahwa <strong>negara melindungi segenap bangsa Indonesia dengan berdasarkan persatuan (sila ketiga)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena pokok pikiran persatuan menegaskan paham negara persatuan yang mengatasi segala paham golongan dan perseorangan. Negara berkewajiban menyatukan seluruh suku, agama, dan daerah demi keutuhan nasional. Pilihan lain menunjuk sila yang menjadi sumber pokok pikiran alinea lainnya.</p><p>Jawaban tepat: <strong>B. Sila ketiga persatuan Indonesia</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Sila kesatu merupakan landasan pokok pikiran keempat bersama sila kedua.</li><li><strong>C</strong>: Sila kedua merupakan landasan pokok pikiran keempat tentang dasar kemanusiaan.</li><li><strong>D</strong>: Sila keempat merupakan landasan pokok pikiran ketiga tentang kedaulatan rakyat.</li><li><strong>E</strong>: Sila kelima merupakan landasan pokok pikiran kedua tentang keadilan sosial.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 11) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>persatuan (sila ketiga).</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Tujuan negara untuk mewujudkan kesejahteraan bagi segenap warganya dirumuskan dalam pokok pikiran kedua yang berbunyi...", "options": ["Negara berkedaulatan rakyat melalui permusyawaratan dewan", "Negara menjamin kebebasan pers dan berserikat warga", "Negara mewujudkan keadilan sosial bagi seluruh rakyat", "Negara berdasarkan persatuan di atas segala golongan", "Negara menjamin keterbukaan perbankan internasional"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pokok Pikiran Kedua Pembukaan UUD 1945</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pokok pikiran <strong>kedua, negara Indonesia mewujudkan keadilan sosial bagi seluruh rakyat Indonesia (sila kelima)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena pokok pikiran kedua menempatkan keadilan sosial sebagai cita-cita yang hendak dicapai oleh negara dengan mewajibkan penyelenggara negara menyusun kebijakan ekonomi yang berpihak pada pemerataan kemakmuran rakyat. Keadilan sosial meniadakan eksploitasi antarsesama. Pilihan lain merujuk pada pokok pikiran persatuan atau kedaulatan.</p><p>Jawaban tepat: <strong>C. Negara mewujudkan keadilan sosial bagi seluruh rakyat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kedaulatan rakyat melalui permusyawaratan merupakan rumusan pokok pikiran ketiga.</li><li><strong>B</strong>: Kebebasan berserikat dan pers diatur dalam pasal 28 UUD 1945 batang tubuh.</li><li><strong>D</strong>: Paham negara persatuan di atas segala golongan merupakan bunyi pokok pikiran pertama.</li><li><strong>E</strong>: Keterbukaan perbankan adalah isu teknis perekonomian yang tidak termaktub dalam mukadimah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 11) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Kedua, negara Indonesia mewujudkan keadilan sosial bagi seluruh rakyat</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Pokok pikiran ketiga Pembukaan UUD 1945 menjabarkan sistem ketatanegaraan yang bersumber dari...", "options": ["Sila kedua Kemanusiaan Yang Adil dan Beradab", "Sila kesatu Ketuhanan Yang Maha Esa semata", "Sila kelima Keadilan sosial bagi rakyat", "Sila keempat kerakyatan dan permusyawaratan perwakilan", "Sila ketiga Persatuan Indonesia yang berdaulat"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pokok Pikiran Ketiga Pembukaan UUD 1945</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pokok pikiran ketiga menegaskan bahwa negara berkedaulatan rakyat, <strong>berdasarkan atas kerakyatan dan permusyawaratan/ perwakilan (sila keempat)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena pokok pikiran ini menetapkan bahwa kedaulatan berada di tangan rakyat dan dilaksanakan menurut undang-undang dasar melalui sistem perwakilan dan musyawarah mufakat. Demokrasi Indonesia berakar pada asas kekeluargaan, bukan voting mayoritas mutlak. Pilihan lain merupakan landasan pokok pikiran yang berbeda.</p><p>Jawaban tepat: <strong>D. Sila keempat kerakyatan dan permusyawaratan perwakilan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Sila kedua melandasi pokok pikiran keempat bersama sila pertama Ketuhanan.</li><li><strong>B</strong>: Sila kesatu melandasi pokok pikiran keempat mengenai dasar moralitas bernegara.</li><li><strong>C</strong>: Sila kelima melandasi pokok pikiran kedua tentang pencapaian keadilan sosial.</li><li><strong>E</strong>: Sila ketiga melandasi pokok pikiran pertama mengenai negara persatuan nasional.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 11) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>permusyawaratan/ perwakilan (sila keempat).</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Pokok pikiran keempat Pembukaan UUD 1945 menegaskan komitmen moral pemerintahan negara yang berakar pada...", "options": ["Sila ketiga persatuan dan sila kelima keadilan", "Sila kedua kemanusiaan dan sila keempat kerakyatan", "Sila kelima keadilan sosial dan sila keempat musyawarah", "Sila ketiga persatuan dan sila pertama religiusitas", "Sila kesatu Ketuhanan dan sila kedua kemanusiaan"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pokok Pikiran Keempat Pembukaan UUD 1945</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pokok pikiran keempat menegaskan negara berdasar Ketuhanan Yang Maha Esa, <strong>menurut dasar kemanusiaan yang adil dan beradab (sila kesatu dan kedua)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena pokok pikiran keempat menuntut seluruh penyelenggara negara dan pimpinan pemerintahan memiliki budi pekerti kemanusiaan yang luhur serta memegang teguh cita-cita moral religius yang murni. Moral ketuhanan dan kemanusiaan mencegah kesewenang-wenangan kekuasaan. Pilihan lain mengombinasikan pasangan sila yang tidak tepat.</p><p>Jawaban tepat: <strong>E. Sila kesatu Ketuhanan dan sila kedua kemanusiaan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Sila ketiga melandasi pokok pikiran pertama dan sila kelima melandasi pokok pikiran kedua.</li><li><strong>B</strong>: Sila keempat melandasi pokok pikiran ketiga tentang kedaulatan rakyat permusyawaratan.</li><li><strong>C</strong>: Kombinasi sila kelima dan keempat merujuk pada pokok pikiran kedua dan ketiga.</li><li><strong>D</strong>: Sila ketiga dan pertama bukan pasangan pembentuk pokok pikiran keempat konstitusi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 11) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>kemanusiaan yang adil dan beradab (sila kesatu dan kedua).</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Makna pokok Pancasila dalam kedudukannya sebagai dasar yang statis dan fundamental adalah...", "options": ["Pondasi tempat didirikan bangunan negara yang kekal", "Pedoman taktis pertempuran pasukan pengamanan perbatasan", "Instrumen pinjaman modal bagi badan usaha swasta", "Tata cara pengangkatan dewan pengawas keuangan daerah", "Aturan pembagian kuota ekspor komoditas perkebunan"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pancasila sebagai Dasar Statis Fundamental</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pancasila berfungsi sebagai <strong>dasar yang statis/ fundamental, di mana di atasnya didirikan bangunan negara Indonesia yang kekal</strong>. Inilah fungsi pokok yang tercantum dalam Pembukaan UUD 1945.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena sebagai dasar statis, Pancasila merupakan landasan kokoh yang tidak boleh goyah atau diubah oleh siapapun demi menjamin kelangsungan eksistensi NKRI. Ibarat pondasi sebuah gedung, Pancasila menopang seluruh konstruksi ketatanegaraan. Pilihan lain merujuk pada urusan operasional bisnis atau pertahanan.</p><p>Jawaban tepat: <strong>A. Pondasi tempat didirikan bangunan negara yang kekal</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Pedoman pertempuran adalah doktrin militer bersenjata, bukan dasar statis negara.</li><li><strong>C</strong>: Pinjaman modal usaha adalah transaksi komersial perbankan dan lembaga pembiayaan.</li><li><strong>D</strong>: Pengangkatan pengawas keuangan diatur dalam tata kelola kepegawaian internal BUMD.</li><li><strong>E</strong>: Kuota komoditas perkebunan adalah urusan kebijakan tata niaga kementerian perdagangan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 12) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Dasar yang statis/ fundamental, di mana di atasnya didirikan bangunan negara</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Fungsi Pancasila sebagai tuntunan yang dinamis dalam kehidupan bernegara bermakna sebagai...", "options": ["Prosedur baku verifikasi pajak bumi bangunan", "Cita-cita dan tujuan ke mana negara digerakkan", "Tata cara rotasi kepemimpinan organisasi kepemudaan", "Sistem pembagian dividen perusahaan milik pemerintah", "Format laporan pertanggungjawaban panitia pengadaan barang"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pancasila sebagai Tuntunan Dinamis</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pancasila berfungsi sebagai <strong>tuntunan yang dinamis, yaitu ke arah mana negara Indonesia akan digerakkan, atau dengan kata lain sebagai cita-cita dan tujuan bangsa Indonesia</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena sebagai tuntunan dinamis, Pancasila tidak bersifat pasif melainkan menjadi bintang pemandu (*leitstar*) yang menggerakkan pembangunan nasional menuju keadilan, peradaban, dan kemakmuran rakyat. Kedinamisan ini memungkinkan bangsa beradaptasi secara progresif. Pilihan lain merupakan format teknis tata kelola keuangan atau administrasi.</p><p>Jawaban tepat: <strong>B. Cita-cita dan tujuan ke mana negara digerakkan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Verifikasi pajak bumi adalah urusan administrasi pendapatan daerah pada dinas perpajakan.</li><li><strong>C</strong>: Rotasi kepemimpinan pemuda adalah agenda internal organisasi kemasyarakatan pemuda.</li><li><strong>D</strong>: Dividen perusahaan pemerintah merupakan pendapatan negara bukan pajak dari laba BUMN.</li><li><strong>E</strong>: Format laporan pengadaan adalah dokumen teknis administratif unit layanan pengadaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 12) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Tuntunan yang dinamis, yaitu ke arah mana negara Indonesia akan digerakkan,</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Perwujudan fungsi Pancasila sebagai ikatan yang dapat mempersatukan bangsa Indonesia diwujudkan melalui...", "options": ["Penyeragaman tradisi upacara adat di setiap suku", "Pemberian hak istimewa politik bagi kelompok mayoritas", "Jaminan hak hidup secara layak bagi semua golongan", "Pembatasan keterlibatan warga daerah dalam birokrasi", "Pemusatan pengelolaan hasil tambang di ibu kota"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pancasila sebagai Ikatan Pemersatu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pancasila berfungsi sebagai ikatan pemersatu bangsa, <strong>di mana Pancasila menjamin hak hidup secara layak bagi semua warga negara dan semua golongan tanpa ada perbedaan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena persatuan yang sejati hanya dapat tumbuh kokoh jika setiap warga negara dan golongan merasakan keadilan, perlindungan hukum, dan kelayakan hidup yang setara dari negara. Rasa saling memiliki ini merekatkan kebinekaan menjadi kesatuan. Pilihan lain mencerminkan diskriminasi dan penyeragaman yang mencederai persatuan.</p><p>Jawaban tepat: <strong>C. Jaminan hak hidup secara layak bagi semua golongan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Tradisi adat lokal dilindungi keanekaragamannya, bukan diseragamkan secara paksa.</li><li><strong>B</strong>: Demokrasi Pancasila menolak tirani mayoritas maupun dominasi minoritas dalam politik.</li><li><strong>D</strong>: Setiap warga negara memiliki hak yang sama untuk mengabdi dalam jajaran aparatur sipil.</li><li><strong>E</strong>: Pengelolaan tambang diarahkan sebesar-besarnya untuk kemakmuran seluruh rakyat secara adil.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 12) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menjamin hak hidup secara layak bagi semua warga negara dan semua golongan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Jaminan kelayakan hidup bagi segenap warga dalam bingkai persatuan Pancasila ditegakkan dengan asas...", "options": ["Keutamaan bagi pemilik modal finansial terbesar", "Prioritas bagi golongan pendukung pejabat terpilih", "Keringanan sanksi bagi aparatur berkedudukan tinggi", "Persamaan hak bagi seluruh warga tanpa perbedaan", "Kekhususan perlakuan bagi warga kepulauan pusat"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Prinsip Nondiskriminasi Pemersatu Bangsa</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Fungsi pemersatu Pancasila menjamin pemenuhan hak asasi dan kehidupan yang layak bagi semua warga negara <strong>tanpa ada perbedaan</strong> suku, agama, ras, dan antargolongan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena asas nondiskriminasi adalah pilar keadilan sosial yang menjamin kesetaraan di hadapan hukum dan pemerintahan (*equality before the law*). Seluruh rakyat berhak menikmati hasil kemerdekaan tanpa dibeda-bedakan. Pilihan lain bertentangan secara frontal dengan asas keadilan Pancasila.</p><p>Jawaban tepat: <strong>D. Persamaan hak bagi seluruh warga tanpa perbedaan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pancasila mengutamakan asas kekeluargaan, bukan hegemoni pemodal atau pemilik kapital.</li><li><strong>B</strong>: Pelayanan publik wajib diberikan secara netral tanpa memandang afiliasi pilihan politik.</li><li><strong>C</strong>: Hukum berlaku sama bagi setiap warga negara tanpa memandang pangkat atau kedudukan.</li><li><strong>E</strong>: Pembangunan nasional berorientasi pada prinsip pemerataan dari kawasan pinggiran dan pulau terluar.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 12) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>tanpa ada perbedaan.</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Dilihat dari lingkup jangkauan sasarannya, kedudukan Pancasila sebagai dasar negara berwujud dalam...", "options": ["Fungsi eksekutif penegakan tata tertib dinas", "Fungsi perantara penyelesaian kredit korporasi", "Fungsi pengawasan operasional logistik daerah", "Fungsi teknis pembukuan neraca saldo anggaran", "Fungsi yuridis ketatanegaraan sebagai fungsi utama"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Fungsi Yuridis Ketatanegaraan Berdasarkan Sasaran</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Lingkup jangkauan sasaran Pancasila meliputi: <strong>Fungsi Yuridis Ketatanegaraan, yang merupakan fungsi pokok atau fungsi utama dari Pancasila sebagai dasar negara</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena fungsi yuridis ketatanegaraan mengikat seluruh institusi negara, organ pemerintahan, dan produk undang-undang dalam suatu tatanan hukum yang bersumber dari norma dasar Pancasila. Fungsi ini memberikan kepastian konstitusional bagi Republik Indonesia. Pilihan lain merujuk pada urusan operasional keuangan dan logistik.</p><p>Jawaban tepat: <strong>E. Fungsi yuridis ketatanegaraan sebagai fungsi utama</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penegakan tata tertib dinas adalah disiplin internal kepegawaian pada unit organisasi.</li><li><strong>B</strong>: Penyelesaian kredit adalah perkara hubungan hukum perdata bisnis perbankan dan debitur.</li><li><strong>C</strong>: Operasional logistik adalah pengelolaan rantai pasok material perlengkapan perkantoran.</li><li><strong>D</strong>: Pembukuan neraca saldo anggaran adalah teknis pembukuan akuntansi perbendaharaan negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 12) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Fungsi Yuridis Ketatanegaraan, yang merupakan fungsi pokok atau fungsi utama</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Apabila dilihat peranannya sebagai pengatur kehidupan kemasyarakatan pada umumnya, fungsi Pancasila disebut...", "options": ["Fungsi sosiologis", "Fungsi diplomatik", "Fungsi kepabeanan", "Fungsi kearsipan", "Fungsi keimigrasian"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Fungsi Sosiologis Pancasila</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pancasila memiliki <strong>Fungsi Sosiologis, yaitu apabila dilihat sebagai pengatur hidup kemasyarakatan pada umumnya</strong>, menjaga kerukunan dan kohesi sosial.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena fungsi sosiologis berkaitan langsung dengan integrasi norma dan nilai dalam interaksi antarwarga masyarakat di kehidupan sehari-hari, seperti gotong royong dan toleransi. Nilai ini menjaga keharmonisan pergaulan sosial. Pilihan lain merupakan urusan pelayanan teknis kementerian atau kepabeanan.</p><p>Jawaban tepat: <strong>A. Fungsi sosiologis</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Fungsi diplomatik dijalankan dalam hubungan kerja sama internasional antarnegara berdaulat.</li><li><strong>C</strong>: Fungsi kepabeanan berkaitan dengan pengawasan lalu lintas arus barang lintas negara.</li><li><strong>D</strong>: Fungsi kearsipan adalah tata kelola pemeliharaan dan pengamanan rekaman dokumen dinas.</li><li><strong>E</strong>: Fungsi keimigrasian mengatur perlintasan orang masuk dan keluar wilayah yurisdiksi negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 12) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Fungsi Sosiologis, yaitu apabila dilihat sebagai pengatur hidup kemasyarakatan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Pancasila yang diposisikan sebagai pengatur tingkah laku budi pekerti individu menjalankan...", "options": ["Fungsi mekanis komando", "Fungsi etis dan filosofis", "Fungsi audit perbendaharaan", "Fungsi pertahanan semesta", "Fungsi redistribusi fiskal"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Fungsi Etis dan Filosofis Pancasila</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pancasila menjalankan <strong>Fungsi Etis dan Filosofis, yaitu apabila fungsinya sebagai pengatur tingkah laku pribadi</strong> yang membimbing nurani dan tindakan setiap individu.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena fungsi etis dan filosofis menuntut setiap manusia Indonesia merefleksikan nilai kejujuran, integritas, dan kebajikan moral dalam perkataan dan perbuatannya sehari-hari. Pancasila menjadi kompas moral batiniah individu. Pilihan lain adalah ranah akuntansi anggaran atau strategi pertahanan militer.</p><p>Jawaban tepat: <strong>B. Fungsi etis dan filosofis</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Fungsi komando militer bersifat instruksional hierarkis, bukan etika moral pribadi.</li><li><strong>C</strong>: Audit perbendaharaan adalah pemeriksaan kepatuhan belanja negara oleh aparat pengawas.</li><li><strong>D</strong>: Pertahanan semesta adalah konsep pertahanan total seluruh warga menghadapi ancaman luar.</li><li><strong>E</strong>: Redistribusi fiskal adalah mekanisme penyeimbangan transfer dana perimbangan ke daerah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 12) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Fungsi Etis dan Filosofis, yaitu apabila fungsinya sebagai pengatur tingkah laku</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Dalam menjalankan fungsi etis dan filosofis kepribadian, Pancasila berperan sebagai...", "options": ["Instrumen pemantau mutasi kepegawaian", "Aturan pembatasan masa kerja aparatur", "Philosophical way of thinking atau philosophical system", "Pedoman akuntansi pencatatan aset inventaris", "Manual teknis pengoperasian aplikasi kepegawaian"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pancasila sebagai Philosophical Way of Thinking</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sebagai pengatur tingkah laku pribadi, <strong>Pancasila berfungsi sebagai philosophical way of thinking atau philosophical system</strong> dalam pola pikir dan sikap hidup manusia Indonesia.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena *philosophical way of thinking* menjadikan lima sila sebagai kerangka berpikir rasional dan moral yang membimbing seseorang saat mengambil keputusan dan memecahkan dilema etika. Pancasila meresap ke dalam alam bawah sadar kognitif bangsa. Pilihan lain adalah pedoman teknologi atau akuntansi aset kantor.</p><p>Jawaban tepat: <strong>C. Philosophical way of thinking atau philosophical system</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pemantauan mutasi pegawai adalah fungsi manajemen sumber daya manusia pada biro SDM.</li><li><strong>B</strong>: Masa kerja dan batas usia pensiun diatur dalam regulasi undang-undang manajemen ASN.</li><li><strong>D</strong>: Pencatatan aset barang milik negara diatur dalam sistem penatausahaan kekayaan negara.</li><li><strong>E</strong>: Manual aplikasi adalah panduan petunjuk teknis pengoperasian perangkat lunak digital.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 12) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pribadi, dalam hal ini Pancasila berfungsi sebagai philosophical way of thinking</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C4 - Analisis", "question": "Berdasarkan lingkup jangkauan sasarannya, fungsi-fungsi Pancasila secara komprehensif dibedakan menjadi...", "options": ["Fungsi fiskal moneter, fungsi niaga daerah, dan fungsi aliansi", "Fungsi perdata khusus, fungsi tata usaha dinas, dan fungsi audit", "Fungsi diplomasi maritim, fungsi kearsipan, dan fungsi komando", "Fungsi yuridis ketatanegaraan, sosiologis, serta etis filosofis", "Fungsi kepabeanan, fungsi keimigrasian, dan fungsi perpajakan"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tiga Lingkup Sasaran Fungsi Pancasila</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dilihat dari lingkup jangkauan sasarannya, fungsi Pancasila dibedakan menjadi: <strong>Fungsi Yuridis Ketatanegaraan, Fungsi Sosiologis, dan Fungsi Etis dan Filosofis</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena pembagian ini mencakup seluruh ranah kehidupan, mulai dari tingkatan tatanan hukum negara (yuridis), interaksi kelompok dalam masyarakat (sosiologis), hingga penghayatan budi pekerti individu (etis filosofis). Ketiganya menyusun kerangka jangkauan sasaran nilai dasar negara. Pilihan lain memadukan fungsi birokrasi sektoral yang tidak relevan.</p><p>Jawaban tepat: <strong>D. Fungsi yuridis ketatanegaraan, sosiologis, serta etis filosofis</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Fiskal dan moneter adalah ranah ekonomi, bukan pembagian jangkauan filosofis dasar negara.</li><li><strong>B</strong>: Perdata dan tata usaha adalah ranah hukum acara peradilan di bawah kementerian kehakiman.</li><li><strong>C</strong>: Diplomasi maritim dan kearsipan adalah bidang kerja dinas teknis pemerintahan tertentu.</li><li><strong>E</strong>: Pabean, imigrasi, dan pajak merupakan instrumen fiskal pelayanan perbatasan negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 12) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>dapat dibedakan sebagai berikut:</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Fungsi pokok Pancasila sebagai dasar statis tempat didirikannya bangunan negara tercantum secara resmi dalam...", "options": ["Penjelasan pasal aturan peralihan kedua", "Risalah rapat panitia kerja perancang keuangan", "Dekrit pembentukan susunan dewan perwakilan", "Naskah perjanjian kerja sama kawasan regional", "Pembukaan UUD 1945 alinea keempat"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tempat Tercantumnya Fungsi Pokok Pancasila</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Fungsi pokok Pancasila sebagai dasar yang statis dan fundamental <strong>tercantum dalam Pembukaan UUD 1945</strong>, di mana di atasnya didirikan bangunan negara yang kekal.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena alinea keempat Pembukaan UUD 1945 merumuskan prinsip berdirinya kemerdekaan kebangsaan Indonesia dalam suatu undang-undang dasar negara yang tersusun atas lima sila Pancasila. Teks mukadimah ini menjadi konsensus dasar pendirian negara. Pilihan lain menunjuk dokumen sementara yang bukan naskah pokok pembukaan konstitusi.</p><p>Jawaban tepat: <strong>E. Pembukaan UUD 1945 alinea keempat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Aturan peralihan memuat ketentuan teknis penyesuaian hukum pasca-perubahan UUD 1945.</li><li><strong>B</strong>: Risalah panitia keuangan membahas pembagian keuangan negara pada masa sidang BPUPKI.</li><li><strong>C</strong>: Dekrit presiden mengatur tindakan hukum darurat penyelamatan negara dalam krisis konstitusi.</li><li><strong>D</strong>: Perjanjian regional adalah instrumen traktat kerja sama internasional antarbangsa tetangga.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 12) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pembukaan UUD 1945.</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Secara historis dan etimologi bahasa, kata filsafat diserap ke dalam bahasa Indonesia melalui...", "options": ["Bahasa Arab falsafah dan bahasa Yunani philosophia", "Bahasa Latin philosophus dan bahasa Portugis farsa", "Bahasa Belanda wijsbegeerte dan bahasa Sanskerta sastra", "Bahasa Jerman weltanschauung dan bahasa Prancis raison", "Bahasa Inggris philosophy dan dialek Tionghoa konghu"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Etimologi Istilah Filsafat</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Istilah filsafat berasal dari <strong>bahasa Arab falsafah. Secara etimologi, falsafah berasal dari bahasa Yunani 'philosophia'</strong>, yang terdiri dari kata philo dan sophia.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena transmisi keilmuan filsafat ke Nusantara melewati kebudayaan Islam dan Arab yang menyerap kata *falsafah* dari induk bahasa Yunani *philosophia*. Asal usul istilah ini menegaskan keterkaitan peradaban ilmu pengetahuan. Pilihan lain memadukan rumpun bahasa asing yang keliru.</p><p>Jawaban tepat: <strong>A. Bahasa Arab falsafah dan bahasa Yunani philosophia</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Bahasa Portugis tidak menjadi jalur utama penyerapan istilah filsafat dalam bahasa Indonesia.</li><li><strong>C</strong>: *Wijsbegeerte* adalah istilah bahasa Belanda untuk ilmu filsafat, bukan asal kata etimologi pokok.</li><li><strong>D</strong>: *Weltanschauung* adalah istilah pandangan hidup dunia, bukan asal serapan kata filsafat.</li><li><strong>E</strong>: Bahasa Inggris menyerap dari sumber yang sama namun bukan jalur langsung kata falsafah di Indonesia.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 13) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Istilah filsafat berasal dari bahasa Arab falsafah. Secara etimologi, falsafah berasal</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Secara harfiah dalam bahasa Yunani, suku kata philein dan sophia masing-masing mengandung arti...", "options": ["Philein berarti memerintah dan sophia berarti undang-undang", "Philein berarti mencari mencintai dan sophia berarti kebenaran kebijaksanaan", "Philein berarti menaklukkan dan sophia berarti wilayah kekuasaan", "Philein berarti mengumpulkan dan sophia berarti harta perbendaharaan", "Philein berarti meragukan dan sophia berarti kenyataan empiris"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Arti Suku Kata Philein dan Sophia</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam bahasa Yunani, <strong>philein berarti mencari, mencintai dan sophia berarti kebenaran, kearifan, atau kebijaksanaan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena *philein* mencerminkan hasrat jiwa manusia yang terdorong untuk mendambakan sesuatu secara luhur, dan *sophia* adalah kebijaksanaan hidup serta kebenaran sejati. Gabungan kedua kata ini melahirkan makna cinta kebijaksanaan. Pilihan lain memberikan terjemahan fiktif tentang kekuasaan atau perbendaharaan.</p><p>Jawaban tepat: <strong>B. Philein berarti mencari mencintai dan sophia berarti kebenaran kebijaksanaan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Memerintah dan undang-undang adalah ranah *nomos* dan *arkhein*, bukan arti kata philo-sophia.</li><li><strong>C</strong>: Menaklukkan wilayah adalah konsep militer ekspansionis imperium, bukan filsafat cinta kebenaran.</li><li><strong>D</strong>: Mengumpulkan harta perbendaharaan adalah konsep komersial niaga materi kekayaan.</li><li><strong>E</strong>: Meragukan kenyataan merujuk pada aliran skeptisisme metodis, bukan arti harfiah philein.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 13) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Philein berarti mencari, mencintai dan sophia berarti kebenaran, kearipan, atau</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan perpaduan unsurnya, kata majemuk philosophia didefinisikan secara filosofis sebagai...", "options": ["Metode penyusunan strategi pertempuran maritim antarpulau", "Tata cara pengumpulan upeti dan pajak daerah kekuasaan", "Daya upaya pemikiran manusia mencari kebenaran kebijaksanaan", "Pedoman pembagian hak waris keluarga bangsawan istana", "Teknik pembukuan transaksi niaga pelabuhan samudra"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Arti Kata Majemuk Philosophia</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kata majemuk philosophia berarti <strong>'daya upaya pemikiran manusia untuk mencari kebenaran atau kebijaksanaan'</strong> secara radikal, sistematis, dan universal.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena filsafat merupakan ikhtiar intelektual dan spiritual akal budi manusia untuk menguak hakikat terdalam dari kenyataan, moralitas, dan tujuan hidup demi memperoleh kebijaksanaan. Pengertian ini menuntun manusia pada kerendahan hati ilmiah. Pilihan lain mengaitkannya dengan masalah warisan, pajak, atau taktik perang.</p><p>Jawaban tepat: <strong>C. Daya upaya pemikiran manusia mencari kebenaran kebijaksanaan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penyusunan taktik maritim adalah keahlian strategi angkatan laut militer bersenjata.</li><li><strong>B</strong>: Pengumpulan pajak merupakan administrasi perpajakan perbendaharaan kas negara.</li><li><strong>D</strong>: Pembagian hak waris diatur dalam hukum perdata dan hukum adat waris kekeluargaan.</li><li><strong>E</strong>: Pembukuan niaga pelabuhan adalah tata kelola administrasi syahbandar perniagaan laut.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 13) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>kebijaksanaan. Dengan demikian, kata majemuk philosophia berarti “daya upaya</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Hakikat mendasar dari seseorang yang berfilsafat adalah orang yang bersikap...", "options": ["Merasa dirinya paling menguasai seluruh kebenaran dunia", "Mengesampingkan pandangan orang lain yang berbeda", "Mengutamakan status sosial di atas kejujuran berpikir", "Mencintai dan mencari kebenaran bukan memiliki kebenaran", "Memaksakan kehendak pribadinya kepada masyarakat luas"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Hakikat Orang yang Berfilsafat</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Orang yang berfilsafat berarti <strong>orang yang mencintai dan mencari kebenaran, bukan memiliki kebenaran</strong>, sehingga senantiasa rendah hati dan terus belajar.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena sikap filosofis menuntut keterbukaan pikiran dan kesadaran bahwa kebenaran manusia selalu terbatas, sehingga seorang pemikir sejati terus mencari kebijaksanaan tanpa merasa jumawa memiliki kebenaran mutlak. Sikap ini mencegah timbulnya fanatisme dogmatis yang sempit. Pilihan lain mencerminkan keangkuhan intelektual yang anti-filsafat.</p><p>Jawaban tepat: <strong>D. Mencintai dan mencari kebenaran bukan memiliki kebenaran</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merasa paling menguasai kebenaran adalah kesombongan epistemologis yang merusak kebijaksanaan.</li><li><strong>B</strong>: Sikap mengesampingkan pendapat lain bertentangan dengan semangat dialogis dan keterbukaan filsafat.</li><li><strong>C</strong>: Filsafat berorientasi pada kemurnian kebenaran nilai, bukan prestise status sosial duniawi.</li><li><strong>E</strong>: Memaksakan kehendak merupakan sikap tirani yang bertentangan dengan keadilan moral.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 13) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>berfilsafat berarti orang yang mencintai dan mencari kebenaran, bukan memiliki</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Kebenaran hasil pemikiran manusia di dunia pada hakikatnya bersifat relatif karena...", "options": ["Setiap undang-undang dapat diubah oleh keputusan dinas", "Akal budi manusia tidak mampu memikirkan kebaikan sosial", "Pengetahuan manusia dibatasi oleh garis keturunan keluarga", "Hukum alam senantiasa berubah tanpa pola yang teratur", "Kebenaran masa kini belum tentu dianggap demikian di masa depan"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sifat Kebenaran Pemikiran Manusia</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kebenaran itu <strong>relatif sifatnya, dalam arti bahwa apa yang kita anggap benar saat ini, belum tentu dianggap demikian di masa yang akan datang</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena pemikiran manusia dipengaruhi oleh keterbatasan ruang, waktu, dan perkembangan ilmu pengetahuan sehingga senantiasa membuka ruang perbaikan dan penyempurnaan. Kesadaran relativitas manusiawi ini melahirkan sikap toleran dan bijak. Pilihan lain memberikan alasan pseudailmiah yang keliru.</p><p>Jawaban tepat: <strong>E. Kebenaran masa kini belum tentu dianggap demikian di masa depan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Perubahan undang-undang tunduk pada prosedur konstitusional ketat, bukan sekadar keputusan dinas.</li><li><strong>B</strong>: Akal budi manusia dianugerahi potensi luhur untuk menalar kebaikan dan kebajikan hidup.</li><li><strong>C</strong>: Kapasitas intelektual terbuka bagi siapa saja melalui proses belajar dan pendidikan merata.</li><li><strong>D</strong>: Hukum alam (*sunnatullah*) memiliki keteraturan hukum sebab akibat yang pasti dan teruji.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 13) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Namun, sebagaimana diketahui kebenaran itu relatif sifatnya, dalam arti bahwa apa</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Dalam tinjauan filosofis kebangsaan Indonesia, sumber kebenaran yang bersifat mutlak berada...", "options": ["Di tangan dan milik Tuhan Yang Maha Esa", "Pada putusan sidang majelis dewan perwakilan", "Dalam naskah perjanjian aliansi pertahanan kawasan", "Pada putusan kasasi peradilan mahkamah agung", "Dalam risalah pidato pejabat kepala daerah terpilih"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kebenaran Mutlak Milik Tuhan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam perspektif filsafat Pancasila, <strong>kebenaran yang mutlak adalah di tangan/ milik Tuhan Yang Maha Esa</strong>, sedangkan kebenaran manusia bersifat relatif.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena sila pertama Ketuhanan Yang Maha Esa menjiwai pandangan ontologis bangsa bahwa kesempurnaan dan kebenaran hakiki yang absolut hanya milik Sang Pencipta semesta alam. Penyelenggara negara harus tunduk pada hukum moral ketuhanan. Pilihan lain menunjuk lembaga manusia yang keputusannya tetap dapat memuat kekeliruan.</p><p>Jawaban tepat: <strong>A. Di tangan dan milik Tuhan Yang Maha Esa</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Keputusan dewan perwakilan merupakan produk politik musyawarah manusia yang dapat direvisi.</li><li><strong>C</strong>: Perjanjian aliansi adalah produk kompromi kepentingan diplomasi antarpemerintahan.</li><li><strong>D</strong>: Putusan peradilan adalah kebenaran hukum formil manusiawi yang tunduk pada upaya hukum.</li><li><strong>E</strong>: Pidato kepala daerah adalah pernyataan kebijakan publik pejabat eksekutif wilayah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 13) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>datang. Kebenaran yang mutlak adalah di tangan/ milik Tuhan Yang Maha Esa. Dalam</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Dalam ruang lingkup wawasan kebangsaan, Filsafat Pancasila didefinisikan secara akademis sebagai...", "options": ["Sistem peradilan administrasi penyelesaian kredit macet", "Ilmu pengetahuan yang mendalam tentang Pancasila", "Metode penyusunan tata tertib kepegawaian kementerian", "Aturan pembagian laba badan usaha milik daerah", "Standar penilaian kelayakan gedung kantor kedinasan"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Filsafat Pancasila</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Filsafat Pancasila adalah ilmu pengetahuan yang mendalam tentang Pancasila</strong>, yang menyelidiki hakikat inti tiap-tiap sila secara ilmiah dan kritis.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena filsafat Pancasila mempelajari asas-asas terdalam, landasan ontologis, epistemologis, dan aksiologis dari nilai-nilai luhur yang dirumuskan para pendiri bangsa. Kajian mendalam ini membuktikan kebenaran falsafah Pancasila secara rasional dan objektif. Pilihan lain merupakan urusan teknis perkantoran atau perbankan.</p><p>Jawaban tepat: <strong>B. Ilmu pengetahuan yang mendalam tentang Pancasila</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penyelesaian kredit macet diatur dalam hukum perbankan dan piutang kekayaan negara.</li><li><strong>C</strong>: Tata tertib dinas diatur dalam peraturan menteri penertiban aparatur negara kepegawaian.</li><li><strong>D</strong>: Pembagian laba BUMD diatur dalam peraturan daerah tentang pengelolaan dividen badan usaha.</li><li><strong>E</strong>: Kelayakan fisik gedung adalah standar sarana prasarana dinas pada kementerian pekerjaan umum.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 13) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>adalah meliputi aspek filsafat dari Pancasila. Filsafat Pancasila adalah ilmu</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Pancasila sebagai pandangan hidup bangsa dijadikan tuntunan mengatur sikap manusia Indonesia dalam hubungannya dengan...", "options": ["Partai politik, pemegang saham, dan serikat buruh", "Pejabat lelang, pembeli agunan, dan dinas perizinan", "Tuhan, masyarakat, dan alam semesta", "Otoritas bursa, emiten saham, dan pialang pasar", "Penyidik pidana, jaksa penuntut, dan majelis hakim"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pancasila sebagai Pandangan Hidup Bangsa</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pancasila sebagai pandangan hidup bangsa berarti nilai-nilainya dijadikan tuntunan mengatur sikap dan tingkah laku manusia Indonesia dalam hubungannya dengan <strong>Tuhan, masyarakat, dan alam semesta</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena kepribadian manusia Indonesia mencakup relasi spiritual transendental (dengan Tuhan), relasi sosial horizontal berkeadilan (dengan sesama masyarakat), serta relasi ekologis selaras (dengan alam semesta). Tiga relasi ini mewujudkan keselarasan hidup kosmik. Pilihan lain merujuk pada relasi profesi bisnis atau lembaga peradilan.</p><p>Jawaban tepat: <strong>C. Tuhan, masyarakat, dan alam semesta</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Partai dan serikat buruh adalah relasi hubungan ketenagakerjaan dan politik praktis.</li><li><strong>B</strong>: Lelang dan perizinan merupakan urusan operasional transaksi kantor pelayanan perbendaharaan.</li><li><strong>D</strong>: Bursa dan pialang saham adalah pelaku perdagangan pasar modal di sektor permodalan.</li><li><strong>E</strong>: Penyidik dan hakim adalah aparatur penegak hukum dalam sistem peradilan pidana terpadu.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 13) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Tuhan, masyarakat, dan alam semesta.</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Makna kedudukan Pancasila sebagai dasar negara Republik Indonesia ditegaskan sebagai landasan dalam...", "options": ["Menentukan besaran denda sanksi administratif parkir", "Mengatur jadwal operasional transportasi antarpulau", "Menetapkan besaran komisi penjualan properti daerah", "Mengatur tata kehidupan bernegara sebagaimana diatur UUD", "Menyusun pembagian waktu kerja dinas instansi swasta"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pancasila sebagai Dasar Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pancasila sebagai dasar negara berarti nilai-nilai yang terkandung di dalamnya <strong>dijadikan dasar dan pedoman dalam mengatur tata kehidupan bernegara sebagaimana yang diatur oleh UUD 1945</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena seluruh sendi penyelenggaraan kekuasaan eksekutif, legislatif, dan yudikatif serta perumusan undang-undang wajib berpedoman pada Pancasila demi menjaga tujuan nasional. Dasar negara adalah pengatur tatanan kekuasaan publik yang berdaulat. Pilihan lain merupakan urusan teknis dinas perhubungan atau bisnis privat.</p><p>Jawaban tepat: <strong>D. Mengatur tata kehidupan bernegara sebagaimana diatur UUD</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Denda parkir diatur dalam peraturan daerah dinas perhubungan mengenai retribusi daerah.</li><li><strong>B</strong>: Jadwal transportasi adalah urusan operasional logistik pelabuhan dan operator kapal laut.</li><li><strong>C</strong>: Komisi properti merupakan transaksi komersial perikatan perdata di sektor perumahan.</li><li><strong>E</strong>: Jam kerja instansi swasta diatur dalam peraturan perusahaan dan undang-undang ketenagakerjaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 13) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>dalam Pancasila itu dijadikan dasar dan pedoman dalam mengatur tata</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Untuk kepentingan praktis operasional, tata urutan hierarki peraturan perundang-undangan diatur dalam regulasi...", "options": ["Undang-Undang Nomor 1 Tahun 2004", "Undang-Undang Nomor 15 Tahun 2004", "Undang-Undang Nomor 25 Tahun 2009", "Undang-Undang Nomor 28 Tahun 1999", "Undang-Undang Nomor 12 Tahun 2011 juncto UU 13/2022"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tata Urutan Peraturan Perundang-undangan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Untuk kepentingan praktis operasional, <strong>tata urutan peraturan perundang-undangan diatur dalam UU No. 12 Tahun 2011 sebagaimana telah diubah dengan Undang-Undang Nomor 13 Tahun 2022</strong>, di mana UUD 1945 menempati urutan tertinggi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena undang-undang ini menetapkan secara pasti hierarki peraturan perundang-undangan di Indonesia: UUD 1945, Ketetapan MPR, Undang-Undang/Perppu, Peraturan Pemerintah, Peraturan Presiden, Perda Provinsi, dan Perda Kabupaten/Kota. Tata urutan ini menjamin kepastian hierarki hukum nasional. Pilihan lain mengatur perbendaharaan, audit BPK, atau pelayanan publik.</p><p>Jawaban tepat: <strong>E. Undang-Undang Nomor 12 Tahun 2011 juncto UU 13/2022</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Undang-Undang Nomor 1 Tahun 2004 mengatur tentang Perbendaharaan Negara.</li><li><strong>B</strong>: Undang-Undang Nomor 15 Tahun 2004 mengatur Pemeriksaan Pengelolaan Keuangan Negara.</li><li><strong>C</strong>: Undang-Undang Nomor 25 Tahun 2009 mengatur tentang Pelayanan Publik bagi masyarakat.</li><li><strong>D</strong>: Undang-Undang Nomor 28 Tahun 1999 mengatur Penyelenggaraan Negara yang Bersih Bebas KKN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 13) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>kepentingan kegiatan praktis operasional diatur dalam UU No. 12 Tahun 2011</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Falsafah Pancasila yang abstrak di dalam Pembukaan UUD 1945 pada hakikatnya merupakan...", "options": ["Uraian terperinci dari Proklamasi 17 Agustus 1945", "Pedoman pembagian dividen perusahaan milik pemerintah daerah", "Aturan teknis penghitungan tarif pajak komoditas ekspor", "Standar kelayakan sarana fisik perkantoran instansi kementerian", "Manual operasional aplikasi kepegawaian aparatur sipil negara"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Falsafah Pancasila dalam Pembukaan UUD 1945</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Falsafah Pancasila yang abstrak tercermin dalam Pembukaan UUD 1945 <strong>merupakan uraian terperinci dari Proklamasi 17 Agustus 1945 yang dijiwai Pancasila</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena teks Proklamasi menyatakan tekad kemerdekaan bangsa, sedangkan Pembukaan UUD 1945 memerinci alasan, dasar filosofis Pancasila, bentuk negara, serta tujuan nasional berdirinya Republik Indonesia. Keduanya memiliki hubungan kejiwaan yang tidak dapat dipisahkan. Pilihan lain merujuk pada regulasi keuangan atau operasional kantor.</p><p>Jawaban tepat: <strong>A. Uraian terperinci dari Proklamasi 17 Agustus 1945</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Dividen perusahaan pemerintah merupakan bagian pengelolaan kekayaan negara dipisahkan.</li><li><strong>C</strong>: Tarif pajak komoditas adalah instrumen kepabeanan fiskal kementerian keuangan.</li><li><strong>D</strong>: Standar sarana perkantoran adalah urusan logistik fasilitas umum instansi dinas.</li><li><strong>E</strong>: Manual aplikasi digital adalah panduan petunjuk teknis teknologi informasi aparatur.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 14) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>merupakan uraian terperinci dari Proklamasi 17 Agustus 1945 yang dijiwai</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Sila-sila Pancasila yang tercantum di dalam Pembukaan UUD 1945 tersusun sebagai...", "options": ["Pilihan terpisah yang dapat dipilih sebagian warga", "Suatu kebulatan yang utuh tidak dapat dipisahkan", "Kumpulan norma sementara menjelang pemilihan umum berkala", "Rangkaian pasal yang dapat dibatalkan oleh menteri", "Aturan bersyarat yang penerapannya bergantung pada anggaran"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kesatuan Utuh Pancasila dalam Pembukaan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pancasila yang tercantum dalam Pembukaan UUD 1945 merupakan <strong>suatu kebulatan yang utuh yang tidak dapat dipisahkan</strong>, di mana setiap sila saling menjiwai.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena lima sila Pancasila adalah satu kesatuan sistem filsafat yang organis dan saling mengkualifikasi, sehingga tidak boleh dipisahkan atau diamalkan secara parsial. Mengabaikan satu sila berarti meruntuhkan keutuhan seluruh dasar negara. Pilihan lain memandang Pancasila secara parsial atau kondisional.</p><p>Jawaban tepat: <strong>B. Suatu kebulatan yang utuh tidak dapat dipisahkan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pancasila mengikat seluruh warga negara secara utuh tanpa pemilahan sila tertentu.</li><li><strong>C</strong>: Pancasila adalah dasar abadi bangsa, bukan norma temporer momentum politik pemilu.</li><li><strong>D</strong>: Menteri tidak memiliki kewenangan membatalkan konstitusi atau dasar negara sedikitpun.</li><li><strong>E</strong>: Pengamalan nilai-nilai luhur Pancasila merupakan kewajiban moral yang mutlak tanpa syarat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 14) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>kebulatan yang utuh yang tidak dapat dipisahkan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Kedudukan seluruh pasal di dalam Undang-Undang Dasar 1945 pada hakikatnya merupakan...", "options": ["Pengecualian mutlak terhadap cita-cita hukum proklamasi kemerdekaan", "Pembatasan hak politik warga negara di daerah kepulauan", "Penjelmaan pokok-pokok pikiran Pembukaan perwujudan jiwa Pancasila", "Pedoman operasional pembagian bantuan keuangan lembaga swadaya asing", "Aturan teknis pengadaan perlengkapan militer di kawasan terluar"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pasal-Pasal sebagai Penjelmaan Pembukaan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pasal-pasal <strong>UUD 1945 merupakan penjelmaan pokok-pokok pikiran yang terkandung dalam Pembukaan UUD 1945 sebagai perwujudan dari jiwa Pancasila</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena batang tubuh konstitusi tidak berdiri sendiri, melainkan merupakan derivasi yuridis normatif dari empat pokok pikiran Pembukaan UUD 1945 yang berakar pada nilai Pancasila. Setiap norma hukum konstitusi adalah konkretisasi nilai dasar negara. Pilihan lain memuat tafsir hukum yang keliru.</p><p>Jawaban tepat: <strong>C. Penjelmaan pokok-pokok pikiran Pembukaan perwujudan jiwa Pancasila</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pasal konstitusi sejalan dan memperkuat cita-cita proklamasi kemerdekaan Republik.</li><li><strong>B</strong>: UUD 1945 menjamin perlindungan hak politik seluruh warga negara tanpa diskriminasi.</li><li><strong>D</strong>: Konstitusi mengatur tata negara berdaulat, bukan pedoman bantuan organisasi swasta asing.</li><li><strong>E</strong>: Pengadaan logistik kemiliteran adalah ranah belanja dinas kementerian pertahanan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 14) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>UUD 1945 merupakan penjelmaan pokok-pokok pikiran yang terkandung dalam</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Dalam praktik kehidupan bernegara, penafsiran terhadap sila-sila Pancasila secara sah wajib...", "options": ["Menyesuaikan dengan kepentingan investor pemegang saham perseroan", "Mengikuti doktrin ideologi perpolitikan negara-negara adidaya barat", "Didasarkan pada opini mayoritas pengguna media sosial digital", "Bersumber dan berdasarkan Pembukaan dan pasal-pasal UUD 1945", "Ditentukan secara bebas oleh masing-masing pimpinan organisasi daerah"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Prinsip Penafsiran Sila-Sila Pancasila</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Penafsiran sila-sila Pancasila harus bersumber dan berdasarkan Pembukaan dan Pasal-pasal UUD 1945</strong>, menjaga otentisitas cita-cita pendiri bangsa.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena UUD 1945 adalah penafsiran autentik yang mengikat secara yuridis terhadap maksud dan tujuan sila-sila Pancasila, sehingga penafsiran di luar kerangka konstitusi berisiko menyimpang dari cita hukum pendiri bangsa. Hal ini menjamin kepastian ideologis negara. Pilihan lain membuka peluang penafsiran liar pragmatis.</p><p>Jawaban tepat: <strong>D. Bersumber dan berdasarkan Pembukaan dan pasal-pasal UUD 1945</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penafsiran dasar negara berorientasi kedaulatan rakyat, bukan kepentingan bisnis pemodal.</li><li><strong>B</strong>: Pancasila memiliki kepribadian mandiri yang khas, bukan kepanjangan ideologi asing.</li><li><strong>C</strong>: Opini dunia digital bersifat dinamis dan tidak memiliki legitimasi yuridis konstitusional.</li><li><strong>E</strong>: Penafsiran konstitusional bersifat nasional terpadu, bukan diserahkan pada selera lokal.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 14) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Penafsiran sila-sila Pancasila harus bersumber dan berdasarkan Pembukaan dan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Secara teoritis dalam kehidupan berbangsa, suatu nilai terbentuk atas dasar pertimbangan...", "options": ["Instruksi komando pimpinan pasukan keamanan wilayah", "Penetapan tarif bea cukai di pelabuhan samudra", "Klausul perjanjian pinjaman modal keuangan perbankan", "Target perolehan suara pemilihan kepala daerah serentak", "Cipta rasa karsa seseorang atau sekelompok masyarakat"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Proses Terbentuknya Nilai</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Nilai terbentuk atas dasar pertimbangan-pertimbangan cipta, rasa, karsa dari seseorang atau sekelompok masyarakat/ bangsa</strong> secara sadar dan berkeyakinan, tidak dapat dipaksakan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena nilai merupakan produk kebudayaan dan olah budi rohani manusia yang memadukan akal pikiran (cipta), kepekaan nurani estetika (rasa), serta tekad kehendak moral (karsa). Perpaduan cipta, rasa, dan karsa melahirkan keyakinan mendalam. Pilihan lain merupakan produk kebijakan administratif atau ekonomi.</p><p>Jawaban tepat: <strong>E. Cipta rasa karsa seseorang atau sekelompok masyarakat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Instruksi komando adalah relasi disiplin kemiliteran, bukan sumber terbentuknya nilai.</li><li><strong>B</strong>: Tarif bea cukai adalah instrumen kebijakan fiskal perdagangan antarnegara perbatasan.</li><li><strong>C</strong>: Klausul pinjaman adalah kontrak perikatan hukum perdata komersial perbankan.</li><li><strong>D</strong>: Target pemilu merupakan strategi pemenangan partai politik dalam kontestasi kekuasaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 14) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Nilai terbentuk atas dasar pertimbangan-pertimbangan cipta, rasa, karsa dari</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Hasil penilaian atau pertimbangan baik dan tidak baik terhadap sesuatu yang menjadi dasar motivasi bertindak disebut...", "options": ["Nilai", "Sanksi", "Dekrit", "Traktat", "Vonis"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Nilai sebagai Dasar Motivasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Nilai</strong> secara singkat dapat dikatakan sebagai hasil penilaian/ pertimbangan 'baik/ tidak baik' terhadap sesuatu, yang kemudian dipergunakan sebagai dasar alasan (motivasi) melakukan atau tidak melakukan sesuatu.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena nilai memberikan bobot keberhargaan dan makna bagi manusia untuk menentukan sikap dan perbuatannya. Ketika sesuatu dinilai baik, ia menjadi motivasi untuk diwujudkan, dan sebaliknya bila dinilai tidak baik akan dihindari. Pilihan lain merujuk pada akibat hukum atau produk hukum formal.</p><p>Jawaban tepat: <strong>A. Nilai</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Sanksi adalah ancaman hukuman akibat pelanggaran norma, bukan pertimbangan nilai awal.</li><li><strong>C</strong>: Dekrit adalah keputusan kepala negara yang memiliki kekuatan hukum dalam situasi luar biasa.</li><li><strong>D</strong>: Traktat adalah perjanjian internasional antarnegara berdaulat dalam kerja sama luar negeri.</li><li><strong>E</strong>: Vonis adalah putusan hakim peradilan terhadap terdakwa dalam sidang perkara pengadilan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 14) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>(motivasi) melakukan atau tidak melakukan sesuatu. Norma (kaidah) adalah petunjuk</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Petunjuk perilaku yang memuat keharusan dan larangan dalam kehidupan sehari-hari disertai sanksi disebut...", "options": ["Cipta rasa", "Norma atau kaidah", "Aset barang", "Neraca saldo", "Buku kas"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengertian Norma atau Kaidah</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Norma (kaidah) adalah petunjuk tingkah laku (perilaku) yang harus dilakukan dan tidak boleh dilakukan dalam hidup sehari-hari</strong> berdasarkan suatu alasan tertentu dengan disertai sanksi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena norma merupakan pelembagaan nilai ke dalam aturan konkret yang mengatur ketertiban hubungan antarmanusia dalam masyarakat dengan daya ikat sanksi bagi pelanggarnya. Norma menjaga harmoni pergaulan sosial. Pilihan lain adalah istilah kejiwaan batin atau pembukuan keuangan.</p><p>Jawaban tepat: <strong>B. Norma atau kaidah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Cipta dan rasa adalah daya kemampuan akal budi jiwa manusia, bukan petunjuk ber-sanksi.</li><li><strong>C</strong>: Aset barang merupakan kekayaan fisik kebendaan milik organisasi atau individu.</li><li><strong>D</strong>: Neraca saldo adalah laporan akuntansi daftar akun keuangan pada periode penutupan buku.</li><li><strong>E</strong>: Buku kas adalah buku catatan pembukuan transaksi penerimaan dan pengeluaran uang.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 14) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>tingkah laku (perilaku) yang harus dilakukan dan tidak boleh dilakukan dalam hidup</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Ancaman atau akibat nyata yang akan diterima seseorang apabila suatu kaidah tingkah laku tidak dipatuhi adalah...", "options": ["Falsafah", "Kearifan", "Sanksi", "Karsa", "Budi"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Sanksi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Sanksi adalah ancaman/ akibat yang akan diterima apabila norma (kaidah) tidak dilakukan</strong> atau dilanggar dalam pergaulan hidup bermasyarakat.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena sanksi berfungsi sebagai instrumen penegak agar aturan norma dipatuhi oleh anggota masyarakat demi terciptanya ketertiban dan kepastian hukum. Tanpa sanksi, norma kehilangan daya pemaksa efektif. Pilihan lain adalah konsep nilai moral budi pekerti batin.</p><p>Jawaban tepat: <strong>C. Sanksi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Falsafah adalah pandangan hidup dan daya pemikiran mendalam manusia mencari kebenaran.</li><li><strong>B</strong>: Kearifan adalah kebijaksanaan budi pekerti dalam menyikapi persoalan kehidupan.</li><li><strong>D</strong>: Karsa adalah daya kehendak dan kemauan bebas manusia dalam bertindak.</li><li><strong>E</strong>: Budi adalah paduan akal pikiran dan nurani manusia yang menuntun kebajikan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 14) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Sedangkan sanksi adalah ancaman/ akibat yang akan diterima apabila norma</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Dalam tatanan pergaulan hidup bermasyarakat, pelanggaran terhadap norma kesusilaan mendatangkan sanksi berupa...", "options": ["Hukuman denda ganti rugi perdata perbankan", "Pencabutan izin operasional tempat usaha dinas", "Hukuman kurungan penjara di lembaga pemasyarakatan", "Rasa malu dan menyesal terhadap dirinya sendiri", "Penyitaan seluruh aset kekayaan milik keluarga"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sanksi Norma Kesusilaan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Norma Kesusilaan bersumber dari hati nurani manusia, <strong>dengan sanksi rasa malu dan menyesal terhadap dirinya sendiri</strong> apabila melanggar nilai moral batiniah.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena norma kesusilaan berakar pada suara hati sanubari manusia (*conscience*) yang menilai kebaikan batin, sehingga hukumannya bersifat internal berupa siksaan batin, penyesalan mendalam, dan rasa malu pribadi. Suara hati menegur perbuatan yang salah. Pilihan lain merupakan sanksi yuridis norma hukum negara.</p><p>Jawaban tepat: <strong>D. Rasa malu dan menyesal terhadap dirinya sendiri</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Denda ganti rugi perdata merupakan sanksi pelanggaran norma hukum di pengadilan negeri.</li><li><strong>B</strong>: Pencabutan izin usaha adalah sanksi hukum administratif dari pejabat berwenang.</li><li><strong>C</strong>: Hukuman kurungan penjara merupakan sanksi pidana norma hukum positif negara.</li><li><strong>E</strong>: Penyitaan aset adalah tindakan eksekusi hukum acara perdata atau pidana peradilan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 14) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>2.Norma Kesusilaan, dengan sanksi rasa malu dan menyesal terhadap dirinya</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Karakteristik khas norma hukum yang membedakannya dari norma kesusilaan dan sopan santun adalah memiliki sanksi...", "options": ["Kutukan gaib yang datang dari roh nenek moyang", "Pengucilan informal dalam kelompok arisan keluarga", "Teguran lisan dari para tetangga sekitar rumah", "Rasa cemas tanpa adanya ancaman hukuman lahiriah", "Tegas dari pemerintah melalui alat-alat negara"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sanksi Norma Hukum oleh Pemerintah</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Norma Hukum ditegakkan dengan sanksi dari pemerintah (alat-alat negara)</strong> yang bersifat tegas, nyata, dan dapat dipaksakan secara sah melalui aparatur berwenang.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena norma hukum memiliki daya paksa eksternal (*coercive power*) yang didukung oleh aparat penegak hukum resmi seperti polisi, jaksa, dan pengadilan demi menjamin kepatuhan seluruh warga negara. Daya paksa resmi inilah ciri pembeda norma hukum. Pilihan lain merupakan sanksi sosiologis atau kepercayaan adat.</p><p>Jawaban tepat: <strong>E. Tegas dari pemerintah melalui alat-alat negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kutukan mistik adalah sanksi mitologis tradisi animisme, bukan norma hukum positif.</li><li><strong>B</strong>: Pengucilan arisan merupakan sanksi sosial informal pergaulan norma sopan-santun.</li><li><strong>C</strong>: Teguran tetangga adalah sanksi teguran moral norma kesopanan di lingkungan masyarakat.</li><li><strong>D</strong>: Rasa cemas internal adalah gejala penyesalan batiniah pada norma kesusilaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 14) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>4.Norma Hukum dengan sanksi dari pemerintah (alat-alat negara)</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Guru besar filsafat hukum Pancasila yang membagi nilai menjadi tiga kelompok besar adalah...", "options": ["Prof. Dr. Notonegoro", "Prof. Dr. Selo Soemardjan", "Prof. Dr. Koentjaraningrat", "Prof. Dr. Mochtar Kusumaatmadja", "Prof. Dr. Hazairin SH"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pembagian Tiga Nilai Menurut Prof. Dr. Notonegoro</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Prof. Dr. Notonegoro, membagi nilai menjadi 3 yakni: Nilai Material, Nilai Vital, dan Nilai Kerohanian</strong> dalam filsafat nilai Pancasila.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena Prof. Dr. Notonegoro adalah begawan filsafat Indonesia yang merumuskan teori nilai aksiologis Pancasila secara mendalam dan sistematis, membuktikan keselarasan jasmani dan rohani manusia Indonesia. Pembagian tiga nilai ini menjadi rujukan klasik ketatanegaraan. Pilihan lain adalah pakar sosiologi, antropologi, atau hukum internasional.</p><p>Jawaban tepat: <strong>A. Prof. Dr. Notonegoro</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Prof. Selo Soemardjan adalah bapak sosiologi Indonesia yang meneliti perubahan sosial.</li><li><strong>C</strong>: Prof. Koentjaraningrat adalah perintis dan tokoh utama pengembangan ilmu antropologi budaya.</li><li><strong>D</strong>: Prof. Mochtar Kusumaatmadja adalah pakar hukum laut internasional pencetus wawasan nusantara.</li><li><strong>E</strong>: Prof. Hazairin adalah pakar hukum adat dan hukum kewarisan bilateral di Indonesia.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 15) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Prof. Dr. Notonegoro, membagi nilai menjadi 3 yakni:</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Segala sesuatu yang berguna bagi pemenuhan unsur raga jasmani manusia dikategorikan sebagai...", "options": ["Nilai religius", "Nilai material", "Nilai estetis", "Nilai kebenaran", "Nilai moral"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengertian Nilai Material</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Nilai Material, yaitu segala sesuatu yang berguna bagi unsur manusia (unsur jasmani manusia)</strong>, seperti pangan, sandang, dan papan untuk menunjang kehidupan ragawi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena nilai material berkaitan dengan kebutuhan kebendaan fisik manusia agar dapat bertahan hidup sehat dan sejahtera di dunia nyata. Pemenuhan kebutuhan jasmani merupakan syarat awal manusia beraktivitas. Pilihan lain tergolong ke dalam rumpun nilai kerohanian batiniah.</p><p>Jawaban tepat: <strong>B. Nilai material</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Nilai religius berkaitan dengan keimanan kepada Tuhan Yang Maha Esa, bukan benda raga.</li><li><strong>C</strong>: Nilai estetis berkaitan dengan keindahan rasa seni manusia, bukan kebutuhan fisik biologis.</li><li><strong>D</strong>: Nilai kebenaran bersumber pada akal pikiran rasional manusia, bukan unsur kebendaan.</li><li><strong>E</strong>: Nilai moral bersumber pada kebaikan budi pekerti kodrat manusia, bukan kebendaan fisik.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 15) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>1.Nilai Material, yaitu segala sesuatu yang berguna bagi unsur manusia</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Sesuatu yang berguna bagi manusia untuk dapat menjalankan berbagai kegiatan dan aktivitas disebut...", "options": ["Nilai estetis", "Nilai religius", "Nilai vital", "Nilai kebenaran", "Nilai moral"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengertian Nilai Vital</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Nilai Vital, yaitu sesuatu yang berguna bagi manusia untuk dapat mengadakan kegiatan dan aktivitas</strong> dalam kehidupan sehari-hari.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena nilai vital mencakup sarana, energi, kebugaran, dan alat penunjang yang memungkinkan manusia berkarya, bergerak aktif, serta berdaya guna dalam lingkungan sosialnya. Tanpa nilai vital, kegiatan produktif manusia terhambat. Pilihan lain adalah cabang dari nilai kerohanian jiwa.</p><p>Jawaban tepat: <strong>C. Nilai vital</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Nilai estetis memuaskan rasa keindahan batin, bukan sarana penggerak kegiatan fisik.</li><li><strong>B</strong>: Nilai religius membimbing keimanan spiritual batiniah kepada Tuhan Yang Maha Esa.</li><li><strong>D</strong>: Nilai kebenaran menguji keabsahan pengetahuan logika akal, bukan sarana aktivitas kerja.</li><li><strong>E</strong>: Nilai moral menuntun kepatutan perilaku etis, bukan daya penggerak aktivitas raga.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 15) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>2.Nilai Vital, yaitu sesuatu yang berguna bagi manusia untuk dapat mengadakan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Dalam pembagian nilai kerohanian, nilai kebenaran atau kenyataan berakar pada unsur...", "options": ["Kekuatan tenaga otot fisik manusia", "Kepemilikan modal uang tunai perbankan", "Keindahan lukisan karya seniman daerah", "Akal budi dan rasio pikiran manusia", "Perjanjian perikatan jual beli perdata"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Nilai Kebenaran Bersumber pada Akal</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Nilai kebenaran yang bersumber pada unsur akal manusia</strong> (rasio budi manusia) untuk menemukan kenyataan yang sahih dan logis.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena akal manusia dirancang untuk menalar, menimbang bukti, mencari kebenaran ilmiah, serta membedakan mana yang fakta dan mana yang ilusi atau kebohongan. Akal budi melahirkan ilmu pengetahuan bagi peradaban. Pilihan lain merujuk pada kekuatan jasmani, transaksi modal, atau seni rupa.</p><p>Jawaban tepat: <strong>D. Akal budi dan rasio pikiran manusia</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Tenaga otot fisik adalah unsur jasmani materiil, bukan sumber nilai kebenaran rasional.</li><li><strong>B</strong>: Modal uang tunai adalah instrumen likuiditas keuangan komersial, bukan akal budi.</li><li><strong>C</strong>: Karya seni lukis adalah ekspresi keindahan rasa estetis manusia, bukan penalaran akal.</li><li><strong>E</strong>: Perikatan jual beli adalah transaksi perjanjian perdata dalam hukum perikatan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 15) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Nilai kebenaran yang bersumber pada unsur akal manusia</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Nilai keindahan atau nilai aestetis dalam klasifikasi nilai kerohanian bersumber pada...", "options": ["Kepatuhan terhadap sanksi tilang kepolisian", "Perhitungan laba rugi transaksi perdagangan", "Kekuatan persenjataan artileri medan perang", "Penguasaan wilayah pelabuhan perikanan laut", "Unsur rasa indah pada nurani manusia"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Nilai Keindahan (Estetis)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Nilai keindahan yang bersumber pada unsur rasa indah manusia</strong> (unsur estetis rasa batiniah) yang mengapresiasi keselarasan dan harmoni alam.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena kepekaan rasa indah manusia memungkinkan timbulnya apresiasi seni, keindahan budi pekerti, serta keharmonisan dalam berinteraksi dengan sesama dan alam sekitar. Rasa estetis menghaluskan peradaban manusia. Pilihan lain adalah ranah sanksi hukum, perdagangan, atau peperangan.</p><p>Jawaban tepat: <strong>E. Unsur rasa indah pada nurani manusia</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Sanksi tilang lalu lintas adalah penegakan hukum positif kepolisian di jalan raya.</li><li><strong>B</strong>: Laba rugi transaksi adalah kalkulasi akuntansi keuangan perniagaan komersial.</li><li><strong>C</strong>: Artileri medan adalah alat persenjataan tempur pasukan militer darat bersenjata.</li><li><strong>D</strong>: Penguasaan dermaga pelabuhan adalah urusan logistik transportasi maritim perikanan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 15) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Nilai keindahan yang bersumber pada unsur rasa indah manusia</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Nilai kebaikan atau nilai moral dalam klasifikasi nilai kerohanian bersumber pada...", "options": ["Unsur kodrat kehendak manusia dalam segala dimensinya", "Instruksi tertulis pimpinan kantor cabang bank", "Kemenangan mutlak fraksi partai dalam pemilu", "Besaran persentase bagi hasil penanaman saham", "Ketentuan batas administratif daerah pemekaran"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Nilai Moral Bersumber pada Kodrat Manusia</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Nilai kebaikan atau nilai moral yang bersumber pada unsur kodrat manusia (manusia dalam segala dimensinya)</strong> dan unsur kehendak (karsa).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena moralitas berakar pada kodrat manusia sebagai makhluk berakal budi yang memiliki kehendak bebas untuk memilih kebajikan dan berbuat adil kepada sesamanya. Kodrat moral ini melandasi etika peradaban bangsa. Pilihan lain merujuk pada instruksi dinas perbankan atau politik praktis.</p><p>Jawaban tepat: <strong>A. Unsur kodrat kehendak manusia dalam segala dimensinya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Instruksi kantor bank adalah aturan pelaksanaan tata tertib operasional perbankan.</li><li><strong>C</strong>: Kemenangan pemilu adalah capaian politik suara partai dalam kompetisi demokrasi.</li><li><strong>D</strong>: Bagi hasil saham merupakan pembagian dividen laba investasi modal perseroan.</li><li><strong>E</strong>: Batas administratif adalah penetapan wilayah teritorial hukum tata pemerintahan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 15) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Nilai kebaikan atau nilai moral yang bersumber pada unsur kodrat manusia</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Nilai kerohanian tertinggi yang bersifat mutlak dan bersumber pada kepercayaan serta keyakinan manusia adalah...", "options": ["Nilai material jasmani", "Nilai religius Ketuhanan", "Nilai vital aktivitas", "Nilai estetis rasa", "Nilai logis akal"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Nilai Religius Bersumber pada Keyakinan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Nilai religius yang merupakan nilai Ketuhanan, kerohanian yang tinggi dan mutlak. Nilai religius ini bersumber pada kepercayaan/ keyakinan manusia</strong> kepada Tuhan Yang Maha Esa.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena nilai religius melampaui kepentingan duniawi sementara, menempati puncak piramida nilai yang memberi dasar kesucian moral bagi seluruh sila lainnya dalam Pancasila. Keimanan kepada Tuhan mengarahkan kehidupan manusia pada kebenaran sejati. Pilihan lain adalah nilai jasmani, vital, atau nilai cipta rasa.</p><p>Jawaban tepat: <strong>B. Nilai religius Ketuhanan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Nilai material berorientasi pada kebutuhan fisik biologis, bukan transendensi mutlak.</li><li><strong>C</strong>: Nilai vital berorientasi pada kebugaran kegiatan aktivitas kerja jasmani raga.</li><li><strong>D</strong>: Nilai estetis berakar pada rasa keindahan seni manusia, bukan keyakinan Ketuhanan.</li><li><strong>E</strong>: Nilai logis akal berakar pada penalaran rasio manusia yang bersifat relatif.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 15) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>mutlak. Nilai religius ini bersumber pada kepercayaan/ keyakinan manusia.</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C4 - Analisis", "question": "Dalam taksonomi nilai Notonegoro, kedudukan Pancasila secara utuh diklasifikasikan sebagai...", "options": ["Nilai material kebendaan semata tanpa rohani", "Nilai vital jasmani yang memisahkan nilai spiritual", "Nilai kerohanian yang meyakini adanya nilai material dan vital", "Nilai sekuler bebas nilai tanpa ikatan moralitas", "Nilai komersial bisnis yang berorientasi pasar bebas"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pancasila sebagai Nilai Kerohanian yang Komprehensif</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Pancasila tergolong nilai kerohanian, tetapi nilai kerohanian yang meyakini adanya nilai material dan nilai vital</strong>, di mana terkandung nilai lengkap dan harmonis.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena Pancasila tidak bersifat asketis yang mengabaikan kebutuhan jasmani atau menolak kemajuan ekonomi material, melainkan memadukan secara harmonis antara kemakmuran lahiriah dan keluhuran rohaniah. Keseimbangan ini adalah esensi manusia seutuhnya (*monopluralis*). Pilihan lain memandang Pancasila secara materialistis atau sekuler.</p><p>Jawaban tepat: <strong>C. Nilai kerohanian yang meyakini adanya nilai material dan vital</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Materialisme murni bertentangan dengan Ketuhanan Yang Maha Esa pada sila pertama.</li><li><strong>B</strong>: Pancasila menjunjung tinggi keluhuran rohani dan keimanan spiritual manusia.</li><li><strong>D</strong>: Pancasila sarat dengan nilai etika moral ketuhanan, bukan ajaran sekuler tanpa norma.</li><li><strong>E</strong>: Pancasila berasas kekeluargaan dan keadilan sosial, bukan kapitalisme komersial pasar.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 15) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pancasila tergolong nilai kerohanian, tetapi nilai kerohanian yang meyakini adanya</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Nilai luhur yang terkandung secara khusus di dalam sila ketiga Persatuan Indonesia adalah...", "options": ["Nilai individualisme liberal", "Nilai persaingan modal bebas", "Nilai primordalisme suku", "Nilai persatuan bangsa", "Nilai hegemoni mayoritas"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Nilai Khusus dalam Sila Ketiga</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Dalam sila ketiga Persatuan Indonesia terkandung nilai persatuan bangsa</strong> yang menempatkan keutuhan bangsa di atas kepentingan pribadi dan golongan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena sila ketiga menegaskan komitmen kebangsaan seluruh warga untuk bersatu padu membela tanah air dan memelihara persaudaraan sejati di tengah keragaman Nusantara. Nilai persatuan bangsa menjadi perisai pertahanan NKRI. Pilihan lain adalah paham perpecahan atau egoisme yang dilarang Pancasila.</p><p>Jawaban tepat: <strong>D. Nilai persatuan bangsa</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Paham individualisme mementingkan diri sendiri di atas kepentingan bersama bangsa.</li><li><strong>B</strong>: Persaingan modal bebas menciptakan ketimpangan ekonomi dan merusak solidaritas sosial.</li><li><strong>C</strong>: Primordalisme kesukuan memicu fanatisme sempit yang merusak integrasi nasional.</li><li><strong>E</strong>: Hegemoni mayoritas bertentangan dengan prinsip keadilan bagi segenap warga negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 15) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>3.Dalam sila ketiga Persatuan Indonesia terkandung nilai persatuan bangsa</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 01", "difficulty": "C4 - Analisis", "question": "Struktur susunan lima sila Pancasila yang dimulai dari Ketuhanan hingga Keadilan Sosial bercirikan...", "options": ["Susunan acak yang urutannya dapat dibolak-balik", "Daftar terpisah yang masing-masing berdiri sendiri", "Aturan sementara yang masa berlakunya terbatas", "Rangkaian klausul yang posisinya dapat digeser menteri", "Susunan sistematis hierarkis berbentuk piramidal"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Susunan Sistematis Hierarkis Piramidal</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Keseimbangan nilai-nilai Pancasila dibuktikan dengan <strong>susunan sila-sila yang sistematis hierarkis yang dimulai sila pertama sampai dengan sila kelima</strong> berbentuk piramidal.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena hierarkis piramidal bermakna sila pertama menjiwai dan mendasari keempat sila lainnya, sedangkan sila kelima dijiwai oleh keempat sila sebelumnya. Urutan ini memiliki makna filosofis dan logis yang mutlak dan tidak boleh diubah susunannya. Pilihan lain meremehkan keterikatan sistematis lima sila.</p><p>Jawaban tepat: <strong>E. Susunan sistematis hierarkis berbentuk piramidal</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Urutan lima sila bersifat matematis logis hierarkis permanen, bukan daftar acak.</li><li><strong>B</strong>: Lima sila saling mengkualifikasi dan terikat organis, bukan bagian yang terpisah.</li><li><strong>C</strong>: Pancasila adalah dasar abadi negara Republik Indonesia, bukan konsensus sementara.</li><li><strong>D</strong>: Susunan sila termaktub dalam Pembukaan UUD 1945 yang tidak dapat diubah oleh siapapun.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 15) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>seimbang, sebagaimana dibuktikan dengan susunan sila-sila yang sistematis</strong></div></div>"}];
let current=0;const answers=new Array(questions.length).fill(null);const letters=["A","B","C","D","E"];let seconds=65*60;let submitted=false;


// Anotasi hanya dalam pembahasan. Satu set coretan untuk setiap nomor soal.
const ANNO_KEY='stangolden_wk_p01_review_annot_v1_user_<?= (int)$USER->id ?>';
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
 try{localStorage.setItem('stg_wk_p01_map_visible',show?'1':'0')}catch(e){}
}
try{if(localStorage.getItem('stg_wk_p01_map_visible')==='0')toggleQuestionMap(false)}catch(e){}
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

  if(modalTitle) modalTitle.textContent="🏆 Lihat Ranking Wawasan Kebangsaan Paket 01";
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
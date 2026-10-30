<?php
/**
 * STANGOLDEN.exam • Wawasan Kebangsaan Paket 03 (Halaman 26–35)
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
// $DB->get_record("stgwk_p03_rank") => tabel fisik "mdlhv_stgwk_p03_rank".
// Jangan menambahkan prefix secara manual di konstanta berikut.
// Penyimpanan bersama melalui database Moodle. Nama tabel pendek, kompatibel dengan batas Moodle.
const STG_LB_T = 'stgwk_p03_rank';
const STG_REVIEW_T = 'stgwk_p03_rev';
const STG_ANNO_T = 'stgwk_p03_anno';
const STG_KEY = [1, 4, 0, 2, 3, 1, 0, 3, 2, 4, 2, 0, 3, 1, 4, 0, 2, 4, 1, 3, 0, 3, 1, 4, 2, 3, 0, 1, 4, 2, 3, 1, 4, 0, 2, 1, 4, 3, 2, 0, 4, 2, 0, 3, 1, 2, 4, 0, 3, 1, 0, 4, 2, 1, 3, 4, 1, 2, 0, 3, 1, 2, 3, 0, 4, 2, 3, 1, 4, 0, 3, 0, 4, 2, 1, 0, 3, 4, 1, 2, 4, 1, 2, 3, 0, 1, 4, 2, 3, 0, 2, 3, 0, 4, 1, 3, 2, 0, 4, 1];
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
            error_log('STANGOLDEN WK P03 HLM26-35 SETUP: ' . $e->getMessage());
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
    }catch(Throwable $e){error_log('STANGOLDEN WK P03 HLM26-35: '.$e->getMessage());stgtak_json(['success'=>false,'message'=>($e instanceof RuntimeException ? $e->getMessage() : 'Penyimpanan belum tersedia. Hubungi administrator.')],500);}
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
<title>Latihan Wawasan Kebangsaan Paket 03 (Hlm. 26–35) • STANGOLDEN</title>
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
      <span class="series-tag">Paket 03 • Hlm. 26–35</span>
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
<h1>Latihan Nilai-Nilai Kemenkeu <span class="series-tag">Paket 03 • Hlm. 26–35</span></h1>
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
        <h2 class="leaderboard-title" id="rankingModalTitle">🏆 Lihat Ranking Wawasan Kebangsaan Paket 03</h2>
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
const STG_LOCAL_KEY="stg_wk_p03_review_user_<?= (int)$USER->id ?>";
const HAS_FULL_REVIEW=true;
const IS_SITE_ADMIN=<?= is_siteadmin($USER) ? "true" : "false" ?>;
const LEADERBOARD_ENDPOINT=window.location.href.split("?")[0].split("#")[0];
const questions=[{"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Panglima militer Jepang yang memanggil tiga tokoh bangsa ke Dalath pada 9 Agustus 1945 adalah...", "options": ["Laksamana Tadashi Maeda di pangkalan Batavia", "Jenderal Besar Terauchi di markas Dalath", "Letnan Jenderal Kumakichi Harada di Jawa", "Mayor Jenderal Seisaburo Okasaki di Bandung", "Marsekal Hitoshi Imamura di markas Asia Tenggara"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pemanggilan Tokoh Bangsa ke Dalath</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pada <strong>9 Agustus 1945</strong>, tiga tokoh bangsa dipanggil oleh <strong>Jenderal Besar Terauchi</strong> ke <strong>Dalath</strong>, Vietnam.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena Jenderal Besar Terauchi merupakan panglima tentara Jepang wilayah selatan yang memanggil delegasi Indonesia ke Dalath. Pemanggilan tersebut bertujuan menyampaikan persetujuan kemerdekaan yang diserahkan pelaksanaannya kepada PPKI. Fakta sejarah ini menjadi tonggak krusial menjelang proklamasi kemerdekaan.</p><p>Jawaban tepat: <strong>B. Jenderal Besar Terauchi di markas Dalath</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Laksamana Maeda adalah perwira penghubung Angkatan Laut di Jakarta, bukan pemanggil di Dalath.</li><li><strong>C</strong>: Kumakichi Harada adalah panglima tentara ke-16 di Jawa pada periode sebelumnya.</li><li><strong>D</strong>: Seisaburo Okasaki bertugas sebagai kepala staf militer di Jakarta, bukan panglima Dalath.</li><li><strong>E</strong>: Hitoshi Imamura adalah panglima bala tentara masa awal pendudukan militer Jepang di Jawa.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 26) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pada tanggal 9 Agustus 1945 • Genderal Besar Terauchi di Dalath</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Selain Soekarno dan Mohammad Hatta, tokoh yang turut dipanggil ke Dalath adalah...", "options": ["Mr. Achmad Soebardjo selaku menteri luar negeri", "K.H. Wachid Hasyim selaku wakil golongan keagamaan", "Mr. Mohammad Yamin selaku perumus teks konstitusi", "Prof. Dr. Soepomo selaku ketua perancang undang-undang", "Dr. K.R.T. Radjiman Wedyodiningrat mantan pimpinan BPUPKI"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tokoh Bangsa yang Dipanggil ke Dalath</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Tiga tokoh bangsa yang dipanggil ke Dalath adalah <strong>Ir. Soekarno</strong>, <strong>Drs. Moh. Hatta</strong>, dan <strong>Dr. K.R.T. Radjiman Wedyodiningrat</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena Dr. Radjiman Wedyodiningrat selaku mantan Ketua BPUPKI mendampingi pimpinan PPKI memenuhi panggilan ke Dalath. Kehadiran beliau melambangkan kesinambungan proses perumusan dasar negara dari BPUPKI ke PPKI. Hal ini memperkuat legitimasi persiapan kemerdekaan Indonesia.</p><p>Jawaban tepat: <strong>E. Dr. K.R.T. Radjiman Wedyodiningrat mantan pimpinan BPUPKI</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Achmad Soebardjo berada di Jakarta menjembatani komunikasi golongan muda dan tua.</li><li><strong>B</strong>: K.H. Wachid Hasyim memimpin koordinasi konsensus tokoh keagamaan di tanah air.</li><li><strong>C</strong>: Mohammad Yamin bertugas di Jakarta menyusun rancangan dokumen kenegaraan.</li><li><strong>D</strong>: Prof. Soepomo memfokuskan kajian pada perumusan pasal-pasal undang-undang di Jawa.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 26) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Mantan Ketua BPUPKI • dipanggil oleh Genderal Besar Terauchi</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan catatan kronologi sejarah kemerdekaan, Jepang menyerah tanpa syarat kepada Sekutu pada...", "options": ["Tanggal 15 Agustus 1945 mengakhiri perang Pasifik", "Tanggal 9 Agustus 1945 pasca pengeboman Nagasaki", "Tanggal 16 Agustus 1945 bersamaan peristiwa Rengasdengklok", "Tanggal 17 Agustus 1945 sebelum proklamasi berkumandang", "Tanggal 18 Agustus 1945 saat sidang perdana PPKI"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penyerahan Jepang Tanpa Syarat</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pada <strong>15 Agustus 1945</strong>, pihak <strong>Jepang resmi menyerah tanpa syarat kepada Sekutu</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena menyerahnya Jepang tanpa syarat kepada Sekutu terjadi pada 15 Agustus 1945 setelah dijatuhkannya bom atom. Peristiwa ini menciptakan kekosongan kekuasaan di kepulauan Indonesia. Situasi tersebut segera dimanfaatkan pemuda dan pendiri bangsa untuk mempercepat kemerdekaan.</p><p>Jawaban tepat: <strong>A. Tanggal 15 Agustus 1945 mengakhiri perang Pasifik</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Tanggal 9 Agustus 1945 adalah hari pemanggilan Soekarno-Hatta ke Dalath oleh Terauchi.</li><li><strong>C</strong>: Tanggal 16 Agustus 1945 adalah hari penegasan larangan sidang PPKI dan pengamanan pemuda.</li><li><strong>D</strong>: Tanggal 17 Agustus 1945 adalah waktu pembacaan teks Proklamasi Kemerdekaan Indonesia.</li><li><strong>E</strong>: Tanggal 18 Agustus 1945 merupakan momentum pelaksanaan sidang pertama lembaga PPKI.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 26) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Jepang menyerah tanpa • syarat kepada Sekutu</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Penegasan pihak penguasa militer Jepang kepada pimpinan PPKI pada 16 Agustus 1945 adalah...", "options": ["Kewajiban membentuk pemerintahan transisi bentukan sekutu", "Perintah penyerahan dokumen perumusan undang-undang dasar", "Larangan bagi PPKI untuk mempersiapkan kemerdekaan Indonesia", "Pemberian izin proklamasi dengan kawalan tentara bala tentara", "Kewajiban membubarkan seluruh organisasi badan pergerakan rakyat"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Larangan Militer Jepang pada 16 Agustus 1945</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pada <strong>16 Agustus 1945</strong>, penguasa militer Jepang memanggil pimpinan PPKI dan menegaskan <strong>larangan mempersiapkan kemerdekaan Indonesia</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena pihak Jepang membatalkan janji sebelumnya dan melarang segala aktivitas persiapan kemerdekaan setelah menyerah kepada Sekutu. Fakta ini membuktikan bahwa proklamasi kemerdekaan lahir murni dari perjuangan rakyat Indonesia sendiri. Sikap tegas pendiri bangsa mendasari tindakan proklamasi tanpa campur tangan asing.</p><p>Jawaban tepat: <strong>C. Larangan bagi PPKI untuk mempersiapkan kemerdekaan Indonesia</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pemerintahan transisi Sekutu bukan perintah resmi yang disampaikan perwira Jepang saat itu.</li><li><strong>B</strong>: Penyitaan dokumen kenegaraan tidak menjadi topik instruksi pertemuan pimpinan militer.</li><li><strong>D</strong>: Jepang melarang kegiatan kemerdekaan dan tidak memberikan fasilitas pengawalan militer.</li><li><strong>E</strong>: Pembubaran organisasi telah terjadi sebelumnya pada fase awal pendudukan militer Dai Nippon.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 26) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>ditegaskan bahwa PPKI dilarang untuk • mempersiapkan kemerdekaan Indonesia</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Utusan yang menghadap Mohammad Hatta pasca proklamasi menyampaikan aspirasi keberatan dari...", "options": ["Kelompok serikat buruh pelabuhan niaga Tanjung Priok", "Perkumpulan saudagar pribumi perantauan Kalimantan Barat", "Delegasi kesultanan berdaulat di pesisir Sumatra Timur", "Masyarakat kepulauan wilayah Indonesia Bagian Timur", "Perwakilan prajurit pembela tanah air keresidenan Jawa"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Keberatan Utusan Indonesia Bagian Timur</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Utusan yang menghadap Drs. Moh. Hatta adalah pihak yang mewakili rakyat <strong>Indonesia Bagian Timur</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena perwakilan masyarakat wilayah timur menyampaikan aspirasi demi keutuhan wilayah republik yang baru merdeka. Aspirasi ini menghendaki kesetaraan kedudukan seluruh warga negara dalam dasar falsafah konstitusi. Mohammad Hatta merespons aspirasi ini dengan penuh kearifan dan mengedepankan persatuan.</p><p>Jawaban tepat: <strong>D. Masyarakat kepulauan wilayah Indonesia Bagian Timur</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Serikat buruh pelabuhan berfokus pada pengambilalihan aset fasilitas transportasi perkapalan.</li><li><strong>B</strong>: Saudagar perantauan Kalimantan tidak mengajukan usulan tertulis terkait rumusan sila pertama.</li><li><strong>C</strong>: Kesultanan Sumatra Timur saat itu mendukung penuh maklumat resmi kedaulatan republik.</li><li><strong>E</strong>: Prajurit PETA di Jawa bertugas mengamankan jalannya proklamasi di kediaman Pegangsaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 26) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>mewakili rakyat • Indonesia Bagian Timur</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Substansi frasa tujuh kata yang menjadi pokok keberatan wakil wilayah timur berbunyi...", "options": ["Negara berdasar atas hukum dan kedaulatan peradilan rakyat", "Kewajiban menjalankan syariat Islam bagi pemeluk-pemeluknya", "Kedaulatan berada di tangan rakyat melalui majelis perwakilan", "Persatuan segenap suku bangsa di seluruh kepulauan nusantara", "Keadilan sosial kemakmuran merata bagi seluruh rakyat jelata"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Rumusan Tujuh Kata Sila Pertama</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Frasa tujuh kata dalam naskah Piagam Jakarta berbunyi <strong>kewajiban menjalankan syariat Islam bagi pemeluk-pemeluknya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena kalimat tersebut tercantum dalam rancangan pembukaan hasil kesepakatan Panitia Sembilan. Frasa ini dinilai dapat memicu diskriminasi oleh masyarakat di luar mayoritas bila dijadikan dasar falsafah bersama. Penghapusan tujuh kata ini menjadi keputusan historis bagi keselamatan persatuan nasional.</p><p>Jawaban tepat: <strong>B. Kewajiban menjalankan syariat Islam bagi pemeluk-pemeluknya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Negara berdasar atas hukum merupakan isi rumusan penjelasan pasal undang-undang dasar.</li><li><strong>C</strong>: Kedaulatan di tangan rakyat merupakan materi pokok pengaturan sistem pemerintahan negara.</li><li><strong>D</strong>: Persatuan segenap suku bangsa adalah penjabaran prinsip sila kebangsaan Indonesia.</li><li><strong>E</strong>: Keadilan kemakmuran merata merupakan muatan normatif pokok pikiran keadilan sosial.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 26) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>tujuh kata • kewajiban menjalankan syariat Islam bagi pemeluk-pemeluknya</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Sidang PPKI segera digelar pada 18 Agustus 1945 terutama disebabkan oleh kondisi...", "options": ["UUD belum disahkan serta belum terpilihnya presiden dan wakil", "Adanya desakan pimpinan armada laut sekutu di pelabuhan", "Tuntutan pembagian kursi kementerian antarpartai politik", "Ancaman pertempuran terbuka dengan bala tentara pendudukan", "Kebutuhan mencetak mata uang resmi pemerintah republik pertama"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Alasan Penyelenggaraan Sidang PPKI 18 Agustus 1945</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sidang PPKI 18 Agustus 1945 diadakan karena <strong>UUD belum disahkan dan presiden serta wakil presiden belum terpilih</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena proklamasi kemerdekaan harus segera dilengkapi perangkat hukum dasar dan pemimpin eksekutif sah. Tanpa konstitusi dan kepala negara, struktur kedaulatan republik belum lengkap secara hukum tata negara. PPKI bergerak cepat menyelenggarakan sidang pengesahan pada 18 Agustus 1945.</p><p>Jawaban tepat: <strong>A. UUD belum disahkan serta belum terpilihnya presiden dan wakil</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Armada tentara Sekutu baru mendarat di kepulauan Indonesia beberapa pekan setelah proklamasi.</li><li><strong>C</strong>: Sistem kepartaian belum terbentuk saat sidang perdana lembaga persiapan kemerdekaan digelar.</li><li><strong>D</strong>: Fokus sidang adalah penguatan legitimasi hukum negara, bukan kesepakatan gencatan senjata.</li><li><strong>E</strong>: Penerbitan mata uang Oeang Republik Indonesia baru diputuskan pada masa revolusi berikutnya.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 26) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>belum disahkan • belum terpilih presiden dan wakil</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Salah satu tokoh Islam yang diajak berdiskusi oleh Mohammad Hatta sebelum sidang PPKI adalah...", "options": ["H.O.S. Tjokroaminoto dari pergerakan Sarekat Islam", "Dr. Soetomo pendiri perkumpulan pemuda pergerakan", "H. Samanhudi perintis serikat perniagaan nusantara", "Ki Bagus Hadikusumo tokoh terkemuka Muhammadiyah", "K.H. Ahmad Dahlan perintis lembaga pendidikan Islam"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tokoh Pembahasan Usul Penghapusan Tujuh Kata</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Tokoh yang diajak Hatta berunding sebelum sidang PPKI mencakup <strong>Ki Bagus Hadikusumo</strong>, <strong>K.H. Wachid Hasyim</strong>, <strong>Mr. Kasman Singodimedjo</strong>, dan <strong>Mr. Teuku M. Hasan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena Ki Bagus Hadikusumo merupakan representasi utama tokoh Islam Muhammadiyah dalam panitia persiapan. Kesediaan beliau menerima penghapusan frasa tujuh kata mencerminkan kenegarawanan luhur demi persatuan bangsa. Dialog berjalan singkat dengan semangat menjaga keutuhan tanah air.</p><p>Jawaban tepat: <strong>D. Ki Bagus Hadikusumo tokoh terkemuka Muhammadiyah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: H.O.S. Tjokroaminoto telah wafat jauh sebelum era pendudukan Jepang dan sidang kemerdekaan.</li><li><strong>B</strong>: Dr. Soetomo memimpin perkumpulan Budi Utomo dan wafat pada dekade sembilan belas tiga puluhan.</li><li><strong>C</strong>: H. Samanhudi adalah tokoh pergerakan pedagang batik era kebangkitan nasional awal.</li><li><strong>E</strong>: K.H. Ahmad Dahlan adalah pendiri persyarikatan yang telah wafat puluhan tahun sebelumnya.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 26) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>membicarakan usul penghapusan • Ki Bagus Hadikusumo, Mr Kasman Singadimedjo</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Tokoh asal Sumatra yang dilibatkan Hatta dalam musyawarah pendahuluan 18 Agustus 1945 adalah...", "options": ["Sutan Sjahrir perintis pemuda sosialis demokratis", "Amir Sjarifuddin tokoh pergerakan pemuda perlawanan", "Mr. Teuku Mohammad Hasan perwakilan tanah Sumatra", "Tan Malaka konseptor republik kebangsaan pertama", "Mohammad Natsir aktivis intelektual pergerakan pemuda"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Perwakilan Tokoh Daerah dalam Rapat Pendahuluan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Tokoh asal Sumatra yang diajak Hatta adalah <strong>Mr. Teuku Mohammad Hasan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena Teuku Mohammad Hasan merupakan wakil anggota PPKI dari wilayah Sumatra yang memiliki integritas tinggi. Keterlibatan beliau memastikan representasi aspirasi wilayah barat dalam menyepakati rumusan dasar negara. Musyawarah menghasilkan konsensus bulat demi keutuhan nusantara.</p><p>Jawaban tepat: <strong>C. Mr. Teuku Mohammad Hasan perwakilan tanah Sumatra</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Sutan Sjahrir bergerak di jalur pergerakan pemuda bawah tanah di luar keanggotaan PPKI.</li><li><strong>B</strong>: Amir Sjarifuddin tidak ikut serta dalam rapat pendahuluan empat tokoh menjelang sidang PPKI.</li><li><strong>D</strong>: Tan Malaka berada di luar lingkar persidangan resmi badan persiapan kemerdekaan negara.</li><li><strong>E</strong>: Mohammad Natsir aktif dalam pergerakan pemikiran Islam generasi selanjutnya pascarevolusi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 26) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Mr. Teuku • M. Hasan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C3 - Aplikasi", "question": "Penolakan pendiri bangsa terhadap larangan Jepang menunjukkan bahwa proklamasi merupakan...", "options": ["Kompensasi formal pemberian status persemakmuran sekutu", "Mandat penyerahan kekuasaan administrasi perwakilan kolonial", "Pelaksanaan kesepakatan perundingan damai antarpanglima perang", "Hadiah kemerdekaan yang dirancang oleh perwira jepang tokyo", "Hasil perjuangan mandiri seluruh rakyat dan pendiri bangsa"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Karakter Kelahiran Proklamasi Indonesia</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kemerdekaan Indonesia lahir <strong>berkat semangat para pendiri negara dan seluruh rakyat Indonesia</strong>, bukan hadiah pemberian penjajah.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena proklamasi diproklamirkan meskipun Jepang secara resmi mencabut janjinya dan melarang persiapan kemerdekaan. Keputusan berani para pendiri bangsa mencerminkan kedaulatan sejati bangsa Indonesia. Fakta historis ini mempertegas kemandirian lahirnya Negara Kesatuan Republik Indonesia.</p><p>Jawaban tepat: <strong>E. Hasil perjuangan mandiri seluruh rakyat dan pendiri bangsa</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Indonesia menolak segala bentuk status boneka atau persemakmuran di bawah pengaruh asing.</li><li><strong>B</strong>: Tidak pernah ada mandat penyerahan wewenang birokrasi dari penguasa kolonial kepada PPKI.</li><li><strong>C</strong>: Proklamasi bukan produk meja perundingan gencatan senjata antara pihak negara berperang.</li><li><strong>D</strong>: Jepang melarang kemerdekaan setelah menyerah sehingga kemerdekaan bukan hadiah kekaisaran.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 26) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>berkat semangat para • pendiri negara dan seluruh rakyat</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Setelah tujuh kata dihapus melalui mufakat para tokoh, rumusan Sila Pertama Pancasila menjadi...", "options": ["Ketuhanan yang berkebudayaan serta berbudi pekerti luhur", "Peri Ketuhanan yang menjunjung tinggi toleransi antarumat", "Ketuhanan Yang Maha Esa sebagai asas falsafah bersama", "Keimanan kepada Tuhan pencipta seluruh alam semesta", "Ketuhanan Yang Maha Kuasa atas berkat kemerdekaan bangsa"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Rumusan Akhir Sila Pertama Pancasila</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Setelah penghapusan tujuh kata, Sila Pertama Pancasila disepakati berbunyi <strong>Ketuhanan Yang Maha Esa</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena rumusan Ketuhanan Yang Maha Esa mampu merangkul seluruh penganut agama dan kepercayaan di nusantara. Rumusan ini mengukuhkan sifat inklusif dasar negara Indonesia yang majemuk tanpa sekat diskriminasi. Sila pertama ini menjadi fondasi moral religius bagi keempat sila berikutnya.</p><p>Jawaban tepat: <strong>C. Ketuhanan Yang Maha Esa sebagai asas falsafah bersama</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Ketuhanan yang berkebudayaan adalah usulan pribadi Soekarno dalam pidato tanggal 1 Juni.</li><li><strong>B</strong>: Peri Ketuhanan merupakan salah satu usulan lisan Mr. Mohammad Yamin pada tanggal 29 Mei.</li><li><strong>D</strong>: Frasa keimanan pencipta semesta bukan rumusan resmi teks dasar negara Republik Indonesia.</li><li><strong>E</strong>: Ketuhanan Yang Maha Kuasa adalah variasi kalimat pembukaan undang-undang pada alinea ketiga.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 27) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>setuju dan mufakat untuk menghapus tujuh kata • menjadi ”Ketuhanan Yang</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Ketetapan pertama yang diputuskan dalam sidang PPKI tanggal 18 Agustus 1945 adalah...", "options": ["Mengesahkan UUD 1945 yang memuat dasar negara Pancasila", "Membentuk Komite Nasional Indonesia Pusat pembantu presiden", "Membagi wilayah kedaulatan republik menjadi delapan provinsi", "Menetapkan susunan kementerian koordinator kabinet pertama", "Membentuk Badan Keamanan Rakyat penegak ketertiban umum"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Ketetapan Pertama Sidang PPKI</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Ketetapan sidang PPKI 18 Agustus 1945 yang pertama adalah <strong>mengesahkan UUD 1945 yang memuat dasar negara Pancasila</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena pengesahan UUD 1945 memberikan landasan hukum tertinggi bagi penyelenggaraan negara Indonesia merdeka. Di dalam alinea keempat Pembukaan UUD 1945 tercantum rumusan sah Pancasila sebagai dasar falsafah negara. Dokumen ini menjadi konstitusi tertulis pertama bangsa Indonesia.</p><p>Jawaban tepat: <strong>A. Mengesahkan UUD 1945 yang memuat dasar negara Pancasila</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Pembentukan KNIP ditetapkan pada agenda sidang lanjutan PPKI tanggal 22 Agustus 1945.</li><li><strong>C</strong>: Pembagian wilayah delapan provinsi diputuskan pada sidang PPKI kedua tanggal 19 Agustus 1945.</li><li><strong>D</strong>: Penetapan pos kementerian kabinet dibahas pada rapat panitia kecil tanggal 19 Agustus 1945.</li><li><strong>E</strong>: Pembentukan BKR disahkan pada sidang ketiga PPKI pada tanggal 22 Agustus 1945.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 27) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Dalam sidang PPKI pada tanggal 18 Agustus 1945 ditetapkan • Mengesahkan UUD 1945</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Ketetapan kedua yang diputuskan secara aklamasi dalam sidang PPKI 18 Agustus 1945 adalah...", "options": ["Menunjuk penasihat agung urusan hubungan diplomasi luar negeri", "Mengesahkan tata tertib persidangan dewan perwakilan rakyat", "Mengangkat panglima tertinggi angkatan bersenjata republik", "Memilih Presiden dan Wakil Presiden pertama Republik Indonesia", "Membentuk badan pemeriksa keuangan pengawas belanja negara"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Ketetapan Kedua Sidang PPKI</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Ketetapan kedua sidang PPKI adalah <strong>memilih Ir. Soekarno sebagai Presiden dan Drs. Moh. Hatta sebagai Wakil Presiden NKRI pertama</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena pemilihan dwitunggal Soekarno-Hatta atas usul Otto Iskandardinata melengkapi pucuk pimpinan eksekutif negara. Pemilihan dilakukan secara aklamasi dengan persetujuan bulat seluruh anggota sidang. Mandat ini memberikan kepemimpinan resmi bagi jalannya roda pemerintahan republik.</p><p>Jawaban tepat: <strong>D. Memilih Presiden dan Wakil Presiden pertama Republik Indonesia</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pengangkatan penasihat luar negeri tidak menjadi keputusan formatur dalam sidang pembukaan.</li><li><strong>B</strong>: Tata tertib parlemen belum disusun karena lembaga perwakilan legislatif belum terbentuk.</li><li><strong>C</strong>: Jabatan panglima militer baru dibentuk pascapenataan Tentara Keamanan Rakyat di Yogyakarta.</li><li><strong>E</strong>: Lembaga pemeriksa keuangan baru diatur kelembagaannya beberapa tahun setelah revolusi fisik.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 27) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Memilih Presiden dan Wakil Presiden pertama • Ir. Soekarno dan Drs. Moh. Hatta</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Perwira yang memfasilitasi komunikasi utusan Kaigun saat menghubungi Mohammad Hatta adalah...", "options": ["Mayor Kudo kepala urusan logistik garnisun militer", "Tuan Nishijima pembantu Laksamana Tadashi Maeda", "Kapten Yanagawa komandan instruktur pelatihan pemuda", "Kolonel Nomura perwira pengawas departemen perhubungan", "Letnan Sato komandan pengawalan markas pejabat tinggi"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kedatangan Utusan Opsir Kaigun</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Mohammad Hatta menerima telepon dari <strong>tuan Nishijima</strong>, pembantu Laksamana Tadashi Maeda, yang mendampingi <strong>opsir Kaigun</strong> sebagai juru bahasa.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena Nishijima bertindak sebagai penghubung dan juru bahasa bagi opsir Kaigun untuk menyampaikan aspirasi penting kawasan timur. Jalur komunikasi ini memungkinkan pesan genting tersebut sampai langsung kepada pimpinan bangsa. Hatta menerima mereka dengan terbuka demi mendengarkan aspirasi tersebut.</p><p>Jawaban tepat: <strong>B. Tuan Nishijima pembantu Laksamana Tadashi Maeda</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Mayor Kudo bertugas pada bagian logistik senjata dan tidak terkait diplomasi konstitusi.</li><li><strong>C</strong>: Kapten Yanagawa memimpin pelatihan korps perwira PETA di wilayah Bogor Jawa Barat.</li><li><strong>D</strong>: Kolonel Nomura memegang komando perhubungan darat dan bukan staf kantor Laksamana Maeda.</li><li><strong>E</strong>: Letnan Sato mengurus keprotokolan markas besar angkatan darat di daerah Gambir.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 27) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>tuan Nishijama, pembantu Admiral • seorang opsir Kaigun</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Keberatan atas kalimat sila pertama Piagam Jakarta yang disampaikan utusan Kaigun berasal dari...", "options": ["Pimpinan organisasi saudagar asing di pelabuhan Batavia", "Kelompok perwira instruktur militer markas pertahanan laut", "Konsulat jenderal perwakilan dagang negara-negara sekutu", "Pejabat pamong praja birokrasi peninggalan hindia belanda", "Wakil-wakil pemeluk Protestan dan Katolik di wilayah timur"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pihak yang Mengajukan Keberatan atas Sila Pertama</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Keberatan disampaikan oleh opsir Kaigun mewakili <strong>pemimpin-pemimpin Protestan dan Katolik</strong> di wilayah pendudukan Angkatan Laut Jepang.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena komunitas Protestan dan Katolik di kawasan timur merasa pencantuman klausul khusus dapat menciptakan pembedaan warga negara. Mereka menginginkan dasar negara bersifat netral dan menaungi seluruh pemeluk keyakinan secara setara. Sikap ini mendorong lahirnya mufakat kebangsaan yang inklusif.</p><p>Jawaban tepat: <strong>E. Wakil-wakil pemeluk Protestan dan Katolik di wilayah timur</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Saudagar asing tidak memiliki kepentingan politik atas perumusan teks konstitusi negara.</li><li><strong>B</strong>: Perwira instruktur Kaigun hanya bertindak sebagai kurir penyampai pesan, bukan pembuat aspirasi.</li><li><strong>C</strong>: Konsulat Sekutu belum beroperasi secara diplomatik di Jakarta pada tanggal 17 Agustus 1945.</li><li><strong>D</strong>: Pamong praja berfokus pada kelangsungan pelayanan birokrasi pemerintahan sehari-hari.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 27) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>wakil-wakil Protestan dan Katolik • berkeberatan sangat terhadap bagian kalimat</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Kekhawatiran utama tokoh minoritas terhadap pencantuman tujuh kata dalam dasar negara adalah...", "options": ["Terjadinya bentuk diskriminasi hukum terhadap golongan minoritas", "Timbulnya kewajiban pajak keagamaan bagi saudagar pribumi", "Hilangnya hak kepemilikan tanah adat di kepulauan daerah timur", "Kewajiban penggunaan bahasa daerah tertentu dalam birokrasi", "Penghapusan status kebebasan berniaga antarwilayah kepulauan"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Alasan Pokok Keberatan Golongan Minoritas</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pencantuman klausul yang hanya mengikat satu golongan dinilai dapat <strong>mengadakan diskriminasi terhadap golongan minoritas</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena dasar negara harus menjadi pijakan bersama bagi segenap warga bangsa tanpa diskriminasi. Ketentuan dasar yang mengikat satu kelompok keagamaan di dalam UUD dikhawatirkan memposisikan pemeluk lain sebagai warga negara kelas dua. Oleh karena itu, prinsip nondiskriminasi dijunjung tinggi demi keutuhan republik.</p><p>Jawaban tepat: <strong>A. Terjadinya bentuk diskriminasi hukum terhadap golongan minoritas</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Pajak keagamaan tidak pernah dibahas dalam draf naskah pembukaan undang-undang dasar.</li><li><strong>C</strong>: Pengaturan hak ulayat tanah adat merupakan domain materi perundang-undangan agraria.</li><li><strong>D</strong>: Bahasa Indonesia telah disepakati secara aklamasi sebagai bahasa persatuan sejak Sumpah Pemuda.</li><li><strong>E</strong>: Kebebasan perdagangan antarpulau dijamin dalam prinsip wawasan kesatuan ekonomi nusantara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 27) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>mengadakan diskriminasi • terhadap golongan minoritas</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Konsekuensi paling kritis yang diutarakan utusan wilayah timur jika klausul tersebut dipertahankan adalah...", "options": ["Penghentian pasokan hasil rempah-rempah ke pusat pelabuhan", "Permintaan pembentukan pangkalan militer bersenjata serikat", "Pilihan untuk memilih berdiri di luar Republik Indonesia", "Penyelenggaraan pemungutan suara perbatasan kawasan pulau", "Penyerahan tata kelola niaga perikanan kepada armada sekutu"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dampak Bila Diskriminasi Ditetapkan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Tokoh-tokoh wilayah timur menyatakan <strong>lebih suka berdiri di luar Republik Indonesia</strong> jika klausul diskriminatif tetap dipaksakan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena ancaman perpecahan ini sangat membahayakan eksistensi keutuhan negara proklamasi yang baru seumur jagung. Bila wilayah timur memisahkan diri, cita-cita persatuan seluruh wilayah bekas Hindia Belanda akan kandas. Ancaman ini menyadarkan Hatta untuk segera mengambil langkah penyelamatan integrasi nasional.</p><p>Jawaban tepat: <strong>C. Pilihan untuk memilih berdiri di luar Republik Indonesia</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pemboikotan komoditas rempah bukan bentuk ancaman disintegrasi kedaulatan politik yang diajukan.</li><li><strong>B</strong>: Pangkalan militer serikat adalah urusan pertahanan yang sama sekali dihindari para pendiri bangsa.</li><li><strong>D</strong>: Referendum perbatasan tidak menjadi opsi yang diusulkan oleh perwakilan masyarakat timur.</li><li><strong>E</strong>: Konsesi niaga perikanan laut tidak ada hubungannya dengan dasar moral konstitusi negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 27) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>lebih suka berdiri di luar republik • Indonesia</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Argumen awal Mohammad Hatta kepada opsir utusan sebelum memahami sensitivitas masalah tersebut adalah...", "options": ["Teks tersebut bersifat sementara hingga pemilihan umum legislatif", "Rumusan tersebut telah mendapatkan persetujuan komandan militer sekutu", "Klausul tersebut dapat disesuaikan pada tingkat aturan peraturan daerah", "Seluruh ketentuan hukum dasar wajib dipatuhi oleh seluruh warga", "Penetapan itu bukan diskriminasi karena hanya mengikat umat Islam"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penjelasan Awal Mohammad Hatta</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Hatta awalnya menjelaskan bahwa ketentuan itu <strong>bukan diskriminasi sebab penetapan itu hanya mengenai rakyat yang beragama Islam</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena dalam pandangan awal Panitia Sembilan, klausul syariat Islam dimaksudkan sebagai pedoman moral bagi pemeluknya sendiri. Namun, setelah menyadari persepsi dan dampak psikologis bagi keutuhan bangsa, Hatta segera berinisiatif menghapuskannya. Sikap Hatta menunjukkan kebesaran jiwa seorang negarawan sejati.</p><p>Jawaban tepat: <strong>E. Penetapan itu bukan diskriminasi karena hanya mengikat umat Islam</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penjelasan mengenai UUD sementara baru disampaikan saat Soekarno berpidato di sidang PPKI.</li><li><strong>B</strong>: Persetujuan komandan Sekutu tidak pernah menjadi rujukan legitimasi konstitusi Indonesia.</li><li><strong>C</strong>: Hatta tidak menawarkan solusi desentralisasi syariat pada perbincangan malam tanggal 17 Agustus.</li><li><strong>D</strong>: Hatta bersikap dialogis dan tidak memaksakan kepatuhan buta kepada pihak yang keberatan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 27) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Aku mengatakan bahwa itu bukan suatu diskriminasi • sebab penetapan itu hanya mengenai rakyat yang beragama Islam</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C3 - Aplikasi", "question": "Nilai luhur utama yang diteladankan para tokoh bangsa dalam mufakat penghapusan tujuh kata adalah...", "options": ["Kepatuhan mutlak birokrasi terhadap instruksi penguasa militer", "Keikhlasan mengedepankan persatuan bangsa di atas kepentingan golongan", "Keinginan mempercepat pembagian portofolio kementerian negara", "Sikap kompromistis demi menarik pinjaman bantuan moneter luar negeri", "Upaya mempertahankan pengaruh dominasi partai politik di parlemen"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Nilai Luhur Penghapusan Tujuh Kata</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Keputusan penghapusan tujuh kata diambil <strong>dengan mengedepankan persatuan dan kesatuan bangsa</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena para tokoh Islam rela mencoret klausul penting demi menyelamatkan eksistensi dan integrasi Negara Kesatuan Republik Indonesia. Keteladanan ini membuktikan komitmen kebangsaan para pendiri negara yang menomorsatukan kepentingan bersama di atas golongan. Jiwa kenegarawanan ini menjadi pilar kokoh kerukunan bangsa hingga kini.</p><p>Jawaban tepat: <strong>B. Keikhlasan mengedepankan persatuan bangsa di atas kepentingan golongan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Keputusan diambil secara merdeka oleh para pemimpin bangsa, bebas dari arahan militer Jepang.</li><li><strong>C</strong>: Sidang PPKI berfokus pada fondasi hukum negara dan bukan perebutan jabatan kabinet birokrasi.</li><li><strong>D</strong>: Pertimbangan murni untuk menyelamatkan integrasi wilayah nasional, bukan bantuan keuangan.</li><li><strong>E</strong>: Sistem politik multipartai belum hadir saat rapat konsensus 18 Agustus berlangsung.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 27) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Dengan mengkedepankan • persatuan dan kesatuan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Sumber sejarah primer yang mendokumentasikan dialog malam kedatangan utusan Kaigun secara rinci adalah...", "options": ["Buku harian dinas komandan batalyon pertahanan kota batavia", "Naskah steno persidangan kementerian penerangan luar negeri", "Laporan resmi markas komando armada pasifik tentara sekutu", "Buku memoir catatan pribadi Mohammad Hatta tahun 1979", "Arsip korespondensi kawat telegram gubernur jenderal belanda"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sumber Sejarah Penghapusan Tujuh Kata</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kisah pencoretan tujuh kata tersebut secara rinci dituturkan Mohammad Hatta dalam <strong>memoirnya (Hatta, Mohammad, 1979)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena catatan memoir Bung Hatta menjadi rujukan sejarah autentik mengenai peristiwa krusial malam 17 Agustus 1945. Beliau mencatat percakapan telepon Nishijima, pertemuannya dengan opsir Kaigun, hingga rapat pendahuluan empat tokoh. Dokumen ini membuka fakta historis di balik kelahiran rumusan Pancasila sah.</p><p>Jawaban tepat: <strong>D. Buku memoir catatan pribadi Mohammad Hatta tahun 1979</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Buku harian komandan kota mencatat patroli keamanan dan bukan perundingan politik konstitusi.</li><li><strong>B</strong>: Kementerian Penerangan baru didirikan setelah kabinet presidensial pertama terbentuk resmi.</li><li><strong>C</strong>: Armada Sekutu tidak memiliki akses intelijen terhadap pembicaraan privat di kediaman Hatta.</li><li><strong>E</strong>: Pemerintah Hindia Belanda sedang dalam masa pengasingan di luar wilayah kepulauan Indonesia.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 27) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Kisah pencoretan tujuh kata dalam Piagam Jakarta • M. Hatta menuturkan dalam memoirnya</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Sikap Mr. A.A. Maramis selaku tokoh Kristen saat menandatangani Piagam Jakarta pada 22 Juni 1945 adalah...", "options": ["Tidak mempunyai keberatan apa-apa dan ikut menandatanganinya", "Menyampaikan nota keberatan tertulis kepada ketua sidang", "Meminta penundaan pengesahan hingga seluruh anggota hadir", "Mengusulkan pemungutan suara tertutup antaranggota panitia", "Menyerahkan mandat persetujuan kepada wakil ketua panitia"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sikap Mr. Maramis dalam Panitia Sembilan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Saat perumusan Piagam Jakarta, <strong>Mr. Maramis tidak mempunyai keberatan apa-apa dan ikut menandatanganinya pada 22 Juni 1945</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena Mr. A.A. Maramis menandatangani rancangan Pembukaan UUD tanpa keberatan. Beliau menganggap klausul tersebut adalah kesepakatan wajar yang ditujukan bagi umat Islam yang mayoritas. Fakta ini dicatat Hatta untuk menjelaskan dinamika pemikiran saat itu.</p><p>Jawaban tepat: <strong>A. Tidak mempunyai keberatan apa-apa dan ikut menandatanganinya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Mr. Maramis tidak mengajukan nota keberatan diplomatik pada saat penandatanganan Piagam Jakarta.</li><li><strong>C</strong>: Penundaan pengesahan tidak pernah dimintakan oleh delegasi perumus naskah mukadimah.</li><li><strong>D</strong>: Seluruh keputusan Panitia Sembilan dicapai secara musyawarah mufakat, tanpa pemungutan suara.</li><li><strong>E</strong>: Mr. Maramis menandatangani sendiri naskah tersebut tanpa menyerahkan kuasa mandat perwakilan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 28) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Mr. Maramis yang ikut • serta dalam Panitia Sembilan, tidak mempunyai keberatan apa-apa</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Pertimbangan yang mendasari persetujuan awal Mr. Maramis terhadap frasa tujuh kata adalah...", "options": ["Adanya instruksi resmi dari pimpinan militer pendudukan jepang", "Harapan memperoleh bantuan persenjataan dari laskar pejuang", "Keinginan mempercepat pembubaran badan penyelidik usaha merdeka", "Anggapan bahwa klausul tersebut untuk umat Islam mayoritas 90%", "Keputusan menyerahkan perumusan kepada pimpinan rapat panitia"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pertimbangan Awal Mr. Maramis</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Mr. Maramis awalnya mengira bagian kalimat tersebut <strong>hanya untuk rakyat Islam yang 90% jumlahnya dan tidak mengikat pemeluk agama lain</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena Maramis berasumsi klausul tersebut murni mengatur tata ibadah internal pemeluk agama mayoritas. Beliau tidak mengira bahwa klausul tersebut akan dirasakan sebagai diskriminasi oleh masyarakat di daerah pendudukan Kaigun. Pemahaman ini melatarbelakangi sikap awal beliau pada 22 Juni 1945.</p><p>Jawaban tepat: <strong>D. Anggapan bahwa klausul tersebut untuk umat Islam mayoritas 90%</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Militer pendudukan Jepang tidak memberikan instruksi khusus terkait klausul syariat Islam.</li><li><strong>B</strong>: Kesepakatan tidak dipengaruhi motif transaksional persenjataan laskar perjuangan rakyat.</li><li><strong>C</strong>: Panitia Sembilan bekerja menyusun mukadimah dan bukan membubarkan lembaga persidangan.</li><li><strong>E</strong>: Seluruh anggota Panitia Sembilan terlibat aktif dalam musyawarah tanpa melepaskan tanggung jawab.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 28) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>bagian kalimat itu hanya untuk rakyat Islam yang • 90% jumlahnya dan tidak mengikat</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan pandangan Mohammad Hatta, Pembukaan UUD merupakan pokok dari pokok sehingga harus...", "options": ["Mengutamakan perlindungan kelompok mayoritas pemegang hak suara", "Teruntuk bagi seluruh bangsa Indonesia dengan tiada kecualinya", "Memuat pasal-pasal operasional tata kelola belanja kementerian", "Disusun secara ringkas mengikuti pedoman konstitusi negara barat", "Dapat diperbarui setiap tahun sesuai pergantian susunan kabinet"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kedudukan Pembukaan UUD sebagai Pokok dari Pokok</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pembukaan UUD adalah <strong>pokok dari pokok</strong>, sebab itu <strong>harus teruntuk bagi seluruh bangsa Indonesia dengan tiada kecualinya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena sebagai kaidah pokok negara fundamental, Pembukaan UUD menaungi segenap tumpah darah Indonesia tanpa terkecuali. Landasan negara tidak boleh memuat rumusan eksklusif yang memisahkan satu golongan dari golongan lainnya. Prinsip kesetaraan mutlak ini menjadi jiwa persatuan Indonesia.</p><p>Jawaban tepat: <strong>B. Teruntuk bagi seluruh bangsa Indonesia dengan tiada kecualinya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pembukaan UUD tidak boleh berpihak secara eksklusif hanya kepada kelompok mayoritas pemilih.</li><li><strong>C</strong>: Tata kelola anggaran kementerian diatur dalam peraturan teknis undang-undang keuangan.</li><li><strong>D</strong>: Pembukaan UUD dirumuskan berakar pada kepribadian bangsa dan bukan tiruan barat.</li><li><strong>E</strong>: Pembukaan UUD berkedudukan permanen dan tidak dapat diubah sewaktu-waktu oleh kabinet.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 28) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pembukaan Undang-Undang Dasar adalah pokok dari pokok • sebab itu harus teruntuk bagi seluruh bangsa</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Jika suatu dasar negara hanya mengikat sebagian rakyat, hal tersebut dirasakan minoritas sebagai...", "options": ["Penghargaan atas keistimewaan otonomi daerah perbatasan", "Peluang membentuk sistem pemerintahan federal mandiri", "Keringanan dari kewajiban mematuhi tata tertib peradilan", "Jaminan perlindungan khusus dari lembaga perwakilan rakyat", "Bentuk perlakuan diskriminasi dalam kehidupan bernegara"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Persepsi Golongan Minoritas terhadap Dasar Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Jika sebagian dasar negara hanya mengikat kelompok tertentu, hal tersebut <strong>dirasakan oleh golongan-golongan minoritas sebagai diskriminasi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena dasar falsafah sebuah republik harus berlaku merata dan melindungi seluruh warganya tanpa segregasi hukum. Adanya klausul khusus dalam mukadimah UUD memunculkan perasaan diperlakukan berbeda bagi golongan di luar kelompok tersebut. Inilah yang mendasari pentingnya perubahan rumusan menjadi universal.</p><p>Jawaban tepat: <strong>E. Bentuk perlakuan diskriminasi dalam kehidupan bernegara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Otonomi daerah diatur dalam pasal tata kelola wilayah dan bukan pembedaan asas falsafah.</li><li><strong>B</strong>: Gagasan negara federal bukan cita-cita kesatuan yang diperjuangkan dalam mukadimah konstitusi.</li><li><strong>C</strong>: Kewajiban kepatuhan hukum berlaku setara bagi setiap warga tanpa kecuali di seluruh wilayah.</li><li><strong>D</strong>: Perlindungan rakyat diberikan kepada segenap bangsa secara utuh tanpa klasifikasi kasta.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 28) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>dirasakan oleh • golongan-golongan minoritas sebagai diskriminasi</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Musyawarah terbatas empat tokoh Islam bersama Hatta pada pagi 18 Agustus 1945 dilaksanakan...", "options": ["Di tengah-tengah perdebatan sengit persidangan pleno umum", "Setelah pidato pengesahan presiden dan wakil presiden selesai", "Sebelum Sidang Panitia Persiapan Kemerdekaan Indonesia bermula", "Pada malam penutupan rangkaian seluruh sidang komite nasional", "Bersamaan dengan upacara pelantikan menteri-menteri koordinator"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pelaksanaan Rapat Pendahuluan 18 Agustus 1945</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Rapat pendahuluan diadakan <strong>pada pagi 18 Agustus 1945 sebelum Sidang Panitia Persiapan (PPKI) bermula</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena Hatta bergerak cepat menyelesaikan persoalan sensitif ini sebelum sidang pleno PPKI dibuka resmi. Dengan demikian, persidangan utama dapat berjalan lancar tanpa terhambat perdebatan tajam mengenai sila pertama. Langkah diplomasi awal ini menyelamatkan jalannya pengesahan konstitusi.</p><p>Jawaban tepat: <strong>C. Sebelum Sidang Panitia Persiapan Kemerdekaan Indonesia bermula</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Perdebatan tidak dibiarkan mencuat dalam sidang pleno terbuka demi menjaga kekhidmatan.</li><li><strong>B</strong>: Musyawarah dilakukan sebelum agenda pengesahan dan pemilihan pucuk pimpinan eksekutif.</li><li><strong>D</strong>: Penutupan sidang PPKI membahas rancangan pembagian wilayah dan pembentukan kementerian.</li><li><strong>E</strong>: Pelantikan menteri kabinet pertama baru berlangsung beberapa hari setelah sidang konstitusi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 28) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>sebelum Sidang Panitia Persiapan bermula • mengadakan suatu rapat pendahuluan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Tujuan utama mufakat para pemimpin bangsa menghapus bagian kalimat sila pertama adalah...", "options": ["Menyesuaikan naskah dengan piagam perjanjian perdamaian dunia", "Menyeragamkan seluruh hukum adat ke dalam hukum perdata", "Memenuhi syarat administratif pengakuan dari markas sekutu", "Supaya segenap komponen tidak terpecah belah sebagai bangsa", "Membatasi pengaruh kelompok keagamaan dalam pemilihan umum"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuan Utama Penghapusan Bagian Kalimat</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Mufakat tersebut diambil <strong>supaya kita jangan pecah sebagai bangsa</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena keutuhan dan integrasi bangsa yang baru merdeka merupakan prioritas tertinggi di atas segalanya. Perpecahan antarwilayah akan mematikan republik yang baru diproklamasikan. Para tokoh bersepakat mengganti klausul tersebut dengan kalimat yang merangkul segenap elemen bangsa.</p><p>Jawaban tepat: <strong>D. Supaya segenap komponen tidak terpecah belah sebagai bangsa</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Piagam perdamaian internasional bukan pertimbangan yang mendasari mufakat rapat kecil.</li><li><strong>B</strong>: Hukum adat tetap diakui eksistensinya dan tidak dihapuskan oleh rumusan dasar negara.</li><li><strong>C</strong>: Pengakuan Sekutu diperjuangkan lewat diplomasi resmi luar negeri pada masa-masa berikutnya.</li><li><strong>E</strong>: Sila Ketuhanan Yang Maha Esa justru memuliakan nilai keagamaan dalam etika bernegara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 28) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Supaya kita jangan pecah sebagai bangsa • kami mufakat untuk menghilangkan bagian kalimat</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan catatan Mohammad Hatta, sidang kecil penyelesaian masalah krusial tersebut berlangsung...", "options": ["Dalam waktu yang sangat singkat yaitu kurang dari 15 menit", "Selama lebih dari dua hari dua malam tanpa istirahat", "Sepanjang malam hingga fajar proklamasi kemerdekaan tiba", "Selama satu jam penuh dengan adu argumentasi naskah hukum", "Dalam beberapa tahapan pertemuan maraton antarutusan daerah"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Durasi Sidang Kecil Pendahuluan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Masalah serius yang membahayakan keutuhan negara tersebut diselesaikan dalam <strong>sidang kecil yang lamanya kurang dari 15 menit</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena waktu pertemuan yang kurang dari 15 menit menunjukkan tingginya kedewasaan politik dan ketulusan para pemimpin bangsa. Tidak ada perdebatan bertele-tele yang membuang energi. Keikhlasan mengorbankan kepentingan kelompok melahirkan konsensus cepat demi keselamatan negara.</p><p>Jawaban tepat: <strong>A. Dalam waktu yang sangat singkat yaitu kurang dari 15 menit</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Sidang berhari-hari terjadi pada masa perumusan BPUPKI bulan Mei hingga Juli 1945.</li><li><strong>C</strong>: Perumusan malam hingga fajar adalah proses penyusunan teks Proklamasi di rumah Maeda.</li><li><strong>D</strong>: Rapat pendahuluan berlangsung sangat cepat tanpa adu argumentasi berkepanjangan.</li><li><strong>E</strong>: Pertemuan berlangsung satu kali secara ringkas sebelum sidang pleno PPKI dibuka resmi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 28) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>diatasi dalam sidang kecil yang lamanya • kurang dari 15 menit</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C3 - Aplikasi", "question": "Penyelesaian masalah keutuhan negara dalam waktu kurang dari 15 menit membuktikan bahwa para pemimpin...", "options": ["Bersikap acuh tak acuh terhadap aspirasi konstituen pendukungnya", "Benar-benar mementingkan keselamatan nasib dan persatuan bangsa", "Terburu-buru karena khawatir terhadap serangan militer sekutu", "Menyerahkan urusan kenegaraan kepada keputusan sekelompok elite", "Memiliki keterbatasan pemahaman terhadap substansi ilmu hukum"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Makna Keberhasilan Sidang Cepat</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Keberhasilan tersebut adalah bukti bahwa <strong>pemimpin-pemimpin di waktu itu benar-benar mementingkan nasib dan persatuan bangsa</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena Hatta menegaskan bahwa kecepatan tercapainya konsensus mencerminkan kemurnian niat dan jiwa pengorbanan para pendiri republik. Mereka rela menanggalkan ego golongan demi nasib bersama ratusan juta rakyat Indonesia. Sikap ini adalah keteladanan tertinggi integritas kebangsaan.</p><p>Jawaban tepat: <strong>B. Benar-benar mementingkan keselamatan nasib dan persatuan bangsa</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Para pemimpin sangat bertanggung jawab dan memahami betul denyut aspirasi masyarakat luas.</li><li><strong>C</strong>: Keputusan diambil atas dasar kesadaran luhur kenegarawanan, bukan kepanikan semata.</li><li><strong>D</strong>: Keputusan diambil melalui perwakilan sah tokoh-tokoh utama yang disegani umat dan rakyat.</li><li><strong>E</strong>: Tokoh yang hadir seperti Kasman dan Hasan adalah ahli hukum terkemuka berpendidikan tinggi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 28) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pemimpin-pemimpin tersebut di waktu itu benar-benar • mementingkan nasib dan persatuan bangsa</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Empat tokoh bangsa yang bermusyawarah bersama Hatta pada pagi 18 Agustus 1945 adalah...", "options": ["Soekarno, Achmad Soebardjo, Mohammad Yamin, dan Maramis", "Samanhudi, H.O.S. Tjokroaminoto, Soetomo, dan Mas Mansyur", "Radjiman, Soepomo, Abikoesno Tjokrosoejoso, dan Agus Salim", "Sjahrir, Amir Sjarifuddin, Tan Malaka, dan Chaerul Saleh", "Ki Bagus Hadikusumo, Wahid Hasyim, Kasman, dan Teuku Hasan"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Komposisi Tokoh Rapat Konsensus</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Empat tokoh yang diajak Hatta adalah <strong>Ki Bagus Hadikusumo</strong>, <strong>Wahid Hasyim</strong>, <strong>Mr. Kasman Singodimedjo</strong>, dan <strong>Mr. Teuku Mohammad Hasan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena keempat tokoh ini merupakan pilar representasi umat Islam terkemuka dan wakil wilayah kepulauan luar Jawa. Mufakat di antara mereka menjamin diterimanya perubahan rumusan sila pertama secara bulat oleh seluruh komponen bangsa. Hal ini menjadi kunci sukses sidang PPKI.</p><p>Jawaban tepat: <strong>E. Ki Bagus Hadikusumo, Wahid Hasyim, Kasman, dan Teuku Hasan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kelompok tokoh ini merupakan formatur umum perumusan naskah proklamasi dan undang-undang.</li><li><strong>B</strong>: Tjokroaminoto dan Samanhudi adalah tokoh pergerakan dekade awal abad kedua puluh.</li><li><strong>C</strong>: Radjiman dan Soepomo bertugas memimpin persidangan dan materi pasal-pasal konstitusi.</li><li><strong>D</strong>: Sjahrir dan pemuda revolusioner memusatkan aksi pada mobilisasi massa di lapangan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 28) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Wahid Hasyim, Mr. Kasman Singodimedjo • Mr. Teuku Mohammad Hasan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Frasa pengganti yang disepakati untuk menghadirkan rasa persaudaraan segenap pemeluk agama adalah...", "options": ["Keadilan yang beradab dan berkebudayaan nusantara", "Persatuan segenap pemeluk agama dan kepercayaan", "Ketuhanan Yang Maha Esa sebagai titik temu bersama", "Keimanan kepada Tuhan pembimbing perjuangan bangsa", "Toleransi antarumat beragama berlandaskan kerukunan"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penggantian Rumusan Sila Pertama</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Bagian kalimat yang dipersoalkan digantikan dengan rumusan <strong>Ketuhanan Yang Maha Esa</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena rumusan Ketuhanan Yang Maha Esa bermakna monoteisme yang diakui oleh seluruh penganut agama di nusantara. Rumusan ini menjadi 'common denominator' atau titik temu yang mengikat batin segenap warga bangsa. Dengan demikian, persatuan Indonesia tetap kokoh terpelihara.</p><p>Jawaban tepat: <strong>C. Ketuhanan Yang Maha Esa sebagai titik temu bersama</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Keadilan yang beradab merupakan variasi rumusan dari prinsip sila kedua kemanusiaan.</li><li><strong>B</strong>: Persatuan segenap pemeluk bukan rumusan sila pertama yang disahkan dalam konstitusi.</li><li><strong>D</strong>: Keimanan pembimbing perjuangan merupakan frasa yang terdapat pada naskah proklamasi.</li><li><strong>E</strong>: Toleransi beragama adalah implementasi sikap dan bukan bunyi sila dasar falsafah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 28) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menusuk hati kaum Kristen itu dan • menggantikannya dengan Ketuhanan Yang Maha Esa</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Rumusan sah Pancasila yang menjadi pedoman penyelenggaraan kehidupan kenegaraan tercantum dalam...", "options": ["Batang tubuh Pasal 1 ayat (1) Undang-Undang Dasar 1945", "Aturan peralihan ketentuan penutup konstitusi negara", "Naskah piagam deklarasi proklamasi kemerdekaan Indonesia", "Alinea keempat teks Pembukaan Undang-Undang Dasar 1945", "Ketetapan perundingan meja bundar penyerahan kedaulatan"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tempat Tercantumnya Rumusan Sah Pancasila</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Rumusan sah Pancasila sebagai dasar negara tercantum dalam <strong>alinea keempat Pembukaan UUD 1945</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena secara yuridis formal, rumusan sila-sila Pancasila ditempatkan dalam alinea keempat Pembukaan UUD 1945 yang disahkan PPKI. Alinea ini memuat tujuan negara, ketentuan UUD, bentuk republik, dan dasar falsafah lima sila. Kedudukannya adalah tertib hukum tertinggi negara.</p><p>Jawaban tepat: <strong>D. Alinea keempat teks Pembukaan Undang-Undang Dasar 1945</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pasal 1 ayat (1) UUD 1945 mengatur bentuk negara kesatuan yang berbentuk republik.</li><li><strong>B</strong>: Aturan Peralihan mengatur keberlakuan badan dan peraturan lama sebelum dibentuk yang baru.</li><li><strong>C</strong>: Teks Proklamasi 17 Agustus 1945 memuat pernyataan kemerdekaan dan pemindahan kekuasaan.</li><li><strong>E</strong>: KMB adalah perundingan penyerahan kedaulatan tahun 1949 di Den Haag Belanda.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 29) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Rumusan Pancasila sebagai dasar negara yang tercantum dalam Alinea Keempat • Pembukaan UUD 1945</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Asal-usul rumusan dasar negara pada alinea keempat Pembukaan UUD 1945 bersumber dari...", "options": ["Dekrit Presiden 5 Juli yang diberlakukan di seluruh negeri", "Piagam Jakarta setelah penghapusan tujuh kata pada sila pertama", "Naskah usulan lisan rancangan undang-undang Mr. Muh. Yamin", "Pidato pembelaan pemimpin pergerakan di pengadilan kolonial", "Manifesto politik perhimpunan pelajar nusantara di negeri belanda"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Asal-Usul Pancasila Alinea Keempat</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pancasila dalam alinea keempat Pembukaan UUD 1945 <strong>berasal dari Piagam Jakarta setelah dihapuskan tujuh kata dalam sila pertama</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena rumusan sila kedua hingga kelima sama persis dengan yang disepakati Panitia Sembilan dalam Piagam Jakarta 22 Juni 1945. Satu-satunya modifikasi substansial adalah pada sila pertama untuk menjamin inklusivitas seluruh rakyat. Dengan demikian, Piagam Jakarta merupakan rahim historis dasar negara.</p><p>Jawaban tepat: <strong>B. Piagam Jakarta setelah penghapusan tujuh kata pada sila pertama</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Dekrit Presiden 5 Juli 1959 menetapkan berlakunya kembali UUD 1945 pada era demokrasi terpimpin.</li><li><strong>C</strong>: Usulan Yamin pada 29 Mei merupakan masukan awal dalam fase persidangan BPUPKI.</li><li><strong>D</strong>: Pidato 'Indonesia Menggugat' adalah pleidoi pembelaan Bung Karno di hadapan hakim Bandung.</li><li><strong>E</strong>: Manifesto politik 1925 merupakan rintisan gagasan kesatuan dari Perhimpunan Indonesia.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 29) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>adalah berasal dari Piagam Jakarta setelah dihapuskan • “tujuh kata” dalam sila pertama</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Alasan yuridis konstitusional yang mewajibkan pengamalan rumusan Pancasila alinea keempat adalah...", "options": ["Diterbitkan dalam lembaran berita militer tentara pendudukan", "Disusun berdasarkan tata kelola yurisprudensi mahkamah agung", "Mendapatkan verifikasi teknis dari perhimpunan advokat perdata", "Memuat sanksi pidana kurungan bagi pelanggar etika birokrasi", "Tercantum dan merupakan bagian tak terpisahkan dari konstitusi"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Alasan Pertama Keabsahan Rumusan Pancasila</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Rumusan alinea keempat memiliki <strong>kedudukan yuridis konstitusional yaitu tercantum atau merupakan bagian dari konstitusi (UUD)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena Pembukaan UUD 1945 memiliki kekuatan hukum mengikat tertinggi dalam hierarki peraturan perundang-undangan di Indonesia. Karena tercantum dalam naskah konstitusi, setiap warga negara dan penyelenggara negara terikat secara hukum untuk mengamalkannya. Hal ini menjamin kepatuhan konstitusional.</p><p>Jawaban tepat: <strong>E. Tercantum dan merupakan bagian tak terpisahkan dari konstitusi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pemberitaan militer bukan sumber legitimasi tata hukum ketatanegaraan Indonesia merdeka.</li><li><strong>B</strong>: Yurisprudensi peradilan merupakan sumber hukum pelengkap dan bukan pembentuk konstitusi dasar.</li><li><strong>C</strong>: Verifikasi asosiasi advokat tidak menjadi syarat keabsahan dokumen konstitusi negara.</li><li><strong>D</strong>: Sanksi pidana diatur dalam Kitab Undang-Undang Hukum Pidana dan undang-undang khusus.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 29) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Mempunyai kedudukan yuridis konstitusional • merupakan bagian dari konstitusi (UUD)</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Alasan legitimasi sosiopolitis rumusan Pancasila Pembukaan UUD 1945 wajib diamalkan adalah...", "options": ["Disahkan oleh lembaga PPKI yang mewakili seluruh bangsa Indonesia", "Ditetapkan melalui pemungutan suara perwakilan kamar dagang asing", "Merupakan hasil konsensus delegasi koalisi partai politik parlemen", "Memperoleh persetujuan dari perwakilan komando jenderal sekutu", "Diumumkan oleh korps pegawai negeri sipil keresidenan kota"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Alasan Kedua Keabsahan Rumusan Pancasila</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pancasila sah karena <strong>disahkan oleh lembaga atau badan yang mewakili seluruh bangsa Indonesia (PPKI)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena PPKI bertindak sebagai representasi seluruh rakyat dan wilayah kepulauan Indonesia saat proklamasi dikumandangkan. Pengesahan oleh PPKI mencerminkan kesepakatan agung (modus vivendi) seluruh elemen bangsa dari Sabang sampai Merauke. Hal ini memberikan legitimasi moral dan politik mutlak.</p><p>Jawaban tepat: <strong>A. Disahkan oleh lembaga PPKI yang mewakili seluruh bangsa Indonesia</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kamar dagang asing tidak memiliki hak legitimasi dalam pembentukan hukum dasar bangsa.</li><li><strong>C</strong>: Partai politik modern belum dibentuk pada hari pertama sidang pengesahan konstitusi.</li><li><strong>D</strong>: Pihak militer Sekutu tidak pernah memberikan persetujuan politik terhadap kemerdekaan.</li><li><strong>E</strong>: Korps kepegawaian bertugas menjalankan birokrasi dan bukan pengesah dasar negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 29) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Disahkan oleh lembaga atau badan yang mewakili seluruh bangsa • Indonesia (PPKI)</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Sebelum sah ditetapkan sebagai dasar negara, nilai-nilai Pancasila telah lama hidup sebagai...", "options": ["Doktrin tertulis yang diratifikasi oleh organisasi persemakmuran", "Kaidah hukum adat yang dibukukan oleh sarjana hukum eropa", "Kepribadian, pandangan hidup, serta jiwa kepribadian bangsa", "Instruksi dinas tata tertib pegawai pamong praja nusantara", "Sistem perniagaan barter di bandar-bandar pelabuhan maritim"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Perjalanan Panjang Penetapan Falsafah Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pancasila dari awalnya sudah merupakan <strong>kepribadian, pandangan hidup, maupun jiwa bangsa</strong>, sebelum akhirnya ditetapkan sebagai dasar negara.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena nilai gotong royong, musyawarah, religiositas, dan kemanusiaan telah mendarah daging dalam kebudayaan masyarakat nusantara sejak berabad-abad lampau. Para pendiri bangsa menggali nilai-nilai tersebut menjadi dasar falsafah formal negara. Pancasila bukanlah ideologi impor melainkan jati diri bangsa.</p><p>Jawaban tepat: <strong>C. Kepribadian, pandangan hidup, serta jiwa kepribadian bangsa</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Organisasi persemakmuran bentukan barat tidak memiliki kaitan dengan kepribadian nusantara.</li><li><strong>B</strong>: Kodifikasi hukum adat oleh sarjana Belanda bersifat parsial dan bukan pembentuk Pancasila.</li><li><strong>D</strong>: Pancasila bersumber dari kearifan hidup rakyat, bukan aturan birokrasi pamong praja kolonial.</li><li><strong>E</strong>: Sistem niaga barter adalah aktivitas perekonomian masa lalu, bukan sumber nilai kepribadian bangsa.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 29) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pandangan • hidup, maupun jiwa bangsa</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Perbandingan rumusan Piagam Jakarta dengan Pembukaan UUD 1945 menunjukkan bahwa...", "options": ["Terjadi perubahan menyeluruh pada susunan kelima sila dasar negara", "Sila kedua hingga sila kelima memiliki rumusan yang tetap sama", "Sila kelima dipindahkan menjadi sila kedua dalam pembukaan konstitusi", "Sila ketiga diubah rumusannya mengikuti susunan trisila soekarno", "Sila keempat disederhanakan tanpa memuat permusyawaratan perwakilan"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kesamaan Rumusan Sila Kedua hingga Kelima</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Rumusan sila kedua, ketiga, keempat, dan kelima antara Piagam Jakarta dan Pembukaan UUD 1945 <strong>memiliki rumusan yang tetap persis sama</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena dari kelima sila, perubahan naskah hanya terjadi pada sila pertama melalui pencoretan tujuh kata. Sila Kemanusiaan yang adil dan beradab, Persatuan Indonesia, Kerakyatan, dan Keadilan sosial tetap dipertahankan redaksinya secara utuh. Ini membuktikan konsensus kebangsaan tetap terjaga.</p><p>Jawaban tepat: <strong>B. Sila kedua hingga sila kelima memiliki rumusan yang tetap sama</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Hanya sila pertama yang mengalami perbaikan rumusan, bukan keseluruhan kelima sila.</li><li><strong>C</strong>: Urutan sila kelima tetap pada posisi kelima dan tidak pernah dipindahkan ke sila kedua.</li><li><strong>D</strong>: Sila ketiga tetap berbunyi Persatuan Indonesia, bukan konsep sosio nasionalisme Trisila.</li><li><strong>E</strong>: Sila keempat tetap mencantumkan klausul permusyawaratan/perwakilan secara lengkap.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 29) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Persatuan Indonesia • Keadilan sosial bagi Seluruh Rakyat Indonesia</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C3 - Aplikasi", "question": "Kewajiban warga negara terhadap rumusan sah Pancasila dalam kehidupan berbangsa adalah...", "options": ["Mempelajari sejarahnya tanpa keharusan menerapkan dalam pekerjaan", "Menjadikannya bahan diskusi akademis di lingkungan perguruan tinggi", "Mengganti rumusan pasal sesuai dengan kecenderungan tren global", "Membatasi penerapannya pada saat penyelenggaraan upacara bendera", "Melaksanakan dan mengamalkannya dalam tata kehidupan bernegara"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kewajiban Pengamalan Rumusan Sah</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Rumusan sah Pancasila wajib <strong>kita laksanakan dan diamalkan dalam tata kehidupan bermasyarakat, berbangsa, dan bernegara</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena Pancasila bukan sekadar teks historis atau pajangan formalitas, melainkan pedoman hidup operasional bagi seluruh warga negara. Nilai-nilai ketuhanan, kemanusiaan, persatuan, kerakyatan, dan keadilan wajib diwujudkan dalam tindakan nyata. Hal ini menjamin kelangsungan hidup bernegara yang harmonis.</p><p>Jawaban tepat: <strong>E. Melaksanakan dan mengamalkannya dalam tata kehidupan bernegara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pancasila menuntut pengamalan nyata dalam kehidupan sehari-hari dan bukan sekadar pengetahuan.</li><li><strong>B</strong>: Pancasila harus dibumikan dalam perbuatan masyarakat, bukan sebatas wacana perdebatan kampus.</li><li><strong>C</strong>: Dasar negara bersifat final dan tidak boleh diubah mengikuti arus pergolakan tren global.</li><li><strong>D</strong>: Penerapan Pancasila mencakup seluruh aspek kehidupan, tidak terbatas pada upacara formal.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 29) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>rumusan sah dari Pancasila yang wajib kita • laksanakan dan diamalkan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Penetapan Pancasila dalam alinea keempat Pembukaan UUD 1945 mengukuhkan kedudukannya sebagai...", "options": ["Hukum acara pidana khusus peradilan tata usaha militer", "Piagam kesepakatan gencatan senjata dengan perwakilan sekutu", "Peraturan teknis tata kerja kepegawaian kementerian negara", "Dasar negara serta dasar falsafah hidup bagi Negara Indonesia", "Petunjuk pelaksanaan anggaran belanja daerah tingkat provinsi"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kedudukan Pancasila sebagai Dasar Falsafah Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pancasila ditetapkan sebagai <strong>dasar negara atau dasar falsafah negara</strong> sebagaimana tercantum dalam Pembukaan UUD 1945.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena dasar falsafah negara (philosofische grondslag) berfungsi sebagai norma fundamental pembimbing seluruh peraturan hukum dan arah kebijakan nasional. Seluruh produk perundang-undangan harus bersumber dan tidak boleh bertentangan dengan Pancasila. Inilah kedudukan tertinggi Pancasila dalam tatanan ketatanegaraan.</p><p>Jawaban tepat: <strong>D. Dasar negara serta dasar falsafah hidup bagi Negara Indonesia</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Hukum acara pidana militer diatur dalam perundang-undangan khusus tentang peradilan militer.</li><li><strong>B</strong>: Pancasila adalah konstitusi moral bangsa merdeka, bukan piagam perundingan perang.</li><li><strong>C</strong>: Aturan teknis kepegawaian diatur melalui peraturan pemerintah dan peraturan menteri.</li><li><strong>E</strong>: Petunjuk teknis anggaran daerah diatur dalam peraturan menteri dalam negeri dan perda.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 29) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>ditetapkan sebagai dasar negara atau • dasar falsafah negara</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Rumusan sah Sila Keempat yang tercantum dalam alinea keempat Pembukaan UUD 1945 berbunyi...", "options": ["Kedaulatan rakyat yang dipimpin oleh kebijaksanaan dewan parlemen", "Demokrasi kerakyatan berdasarkan pemufakatan majelis permusyawaratan", "Kerakyatan yang dipimpin oleh hikmat kebijaksanaan dalam permusyawaratan/perwakilan", "Kerakyatan yang menjunjung tinggi suara mayoritas warga nusantara", "Kedaulatan di tangan rakyat berlandaskan permusyawaratan gotong royong"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Bunyi Utuh Sila Keempat Pancasila</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sila keempat berbunyi <strong>Kerakyatan yang dipimpin oleh hikmat kebijaksanaan dalam permusyawaratan/perwakilan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena teks resmi sila keempat menekankan kepemimpinan akal budi luhur (hikmat kebijaksanaan) dalam mekanisme musyawarah perwakilan. Asas ini membedakan demokrasi Indonesia dari sistem demokrasi liberal yang mengandalkan dominasi angka semata. Nilai ini menjamin keputusan diambil demi kebaikan bersama.</p><p>Jawaban tepat: <strong>C. Kerakyatan yang dipimpin oleh hikmat kebijaksanaan dalam permusyawaratan/perwakilan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kedaulatan dewan parlemen merujuk sistem perwakilan parlementer barat, bukan teks sila keempat.</li><li><strong>B</strong>: Pemufakatan majelis merupakan istilah umum kelembagaan dan bukan teks resmi sila keempat.</li><li><strong>D</strong>: Pancasila menolak tirani mayoritas maupun tirani minoritas dalam pengambilan keputusan.</li><li><strong>E</strong>: Kedaulatan di tangan rakyat adalah bunyi rumusan Pasal 1 ayat (2) UUD 1945.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 29) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Kerakyatan yang Dipimpin oleh Hikmat Kebijaksanaan dalam Permusyawaratan/ • Perwakilan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C3 - Aplikasi", "question": "Pengesahan rumusan Pancasila oleh PPKI pada 18 Agustus 1945 mengandung arti sosiologis bahwa...", "options": ["Dasar negara telah disepakati dan diterima oleh seluruh bangsa Indonesia", "Keputusan berlaku terbatas bagi masyarakat pulau Jawa dan Madura", "Pemberlakuan konstitusi menunggu penandatanganan traktat diplomatik", "Dasar negara bersifat percobaan sebelum diselenggarakannya pemilu pertama", "Pemerintah pusat dapat membatalkan rumusan sila sewaktu-waktu"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Makna Pengesahan oleh PPKI</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pengesahan oleh PPKI berarti dasar negara telah <strong>disepakati oleh seluruh bangsa Indonesia</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena PPKI mencerminkan keterwakilan sosiologis seluruh suku, agama, dan wilayah kepulauan Indonesia. Kesepakatan yang dicapai dalam sidang PPKI mengikat seluruh rakyat secara lahir dan batin sebagai suatu perjanjian luhur kebangsaan. Konsensus ini menjadi modal persatuan yang kokoh.</p><p>Jawaban tepat: <strong>A. Dasar negara telah disepakati dan diterima oleh seluruh bangsa Indonesia</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Anggota PPKI mewakili kepulauan Sumatra, Jawa, Sulawesi, Maluku, Kalimantan, dan Nusa Tenggara.</li><li><strong>C</strong>: Konstitusi berlaku serta merta sejak tanggal pengesahan tanpa menunggu traktat luar negeri.</li><li><strong>D</strong>: Pancasila disahkan sebagai dasar permanen negara berdaulat, bukan konstitusi percobaan.</li><li><strong>E</strong>: Pemerintah tidak memiliki wewenang sepihak membatalkan teks Pembukaan UUD 1945.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 29) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>berarti disepakati oleh • seluruh bangsa Indonesia</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Hakikat pengamalan Pancasila sebagai dasar negara bermuara pada tujuan pokok yaitu...", "options": ["Membangun dominasi militer kepulauan di panggung perpolitikan global", "Mewajibkan pendirian kantor perwakilan diplomatik di setiap benua", "Menata persaingan bebas korporasi swasta dalam penguasaan sumber daya", "Memusatkan pengelolaan pendapatan daerah ke dalam kas perbendaharaan", "Menyelenggarakan negara demi mewujudkan masyarakat adil dan makmur"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Hakikat Pengamalan Pancasila sebagai Dasar Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pengamalan Pancasila sebagai dasar negara adalah sebagai <strong>dasar penyelenggaraan negara untuk mewujudkan cita-cita bangsa sesuai tujuan nasional yaitu mewujudkan masyarakat adil makmur</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena fungsi utama dasar negara adalah memandu jalannya pemerintahan agar selalu berorientasi pada kemakmuran dan keadilan bagi seluruh rakyat. Seluruh instrumen kenegaraan digerakkan demi mencapai cita-cita kemerdekaan tersebut. Hal ini sejalan dengan amanat Pembukaan UUD 1945.</p><p>Jawaban tepat: <strong>E. Menyelenggarakan negara demi mewujudkan masyarakat adil dan makmur</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Indonesia menganut prinsip politik bebas aktif untuk perdamaian, bukan hegemoni militer.</li><li><strong>B</strong>: Pembukaan pos diplomatik adalah teknis hubungan luar negeri dan bukan cita-cita hakiki dasar negara.</li><li><strong>C</strong>: Pancasila menentang persaingan bebas liberal yang meminggirkan keadilan masyarakat kecil.</li><li><strong>D</strong>: Pancasila menganut desentralisasi yang adil dan seimbang dalam hubungan keuangan daerah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 30) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pengamalan Pancasila sebagai dasar negara adalah sebagai dasar • penyelenggaraan negara untuk mewujudkan cita-cita bangsa</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Secara konstitusional, ruang lingkup pengamalan Pancasila sebagai dasar negara mencakup...", "options": ["Perubahan pasal undang-undang dasar mengikuti perjanjian kredit luar negeri", "Pembatasan hak berorganisasi bagi masyarakat di wilayah pedalaman", "Pengamalan pokok pikiran Pembukaan dan prinsip-prinsip pasal UUD 1945", "Penerapan doktrin hukum pidana kolonial dalam seluruh sengketa perdata", "Penyerahan kewenangan moneter kepada lembaga perbankan multinasional"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dua Dimensi Pengamalan Dasar Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pengamalan Pancasila sebagai dasar negara meliputi <strong>pengamalan pokok-pokok pikiran Pembukaan UUD 1945 dan pelaksanaan prinsip-prinsip pasal UUD 1945</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena Pembukaan memuat norma fundamental negara sedangkan pasal-pasal memuat norma hukum operasional yang mengikat lembaga negara dan warga. Keduanya merupakan kesatuan utuh yang saling menopang dalam ketatanegaraan Indonesia. Mengamalkan dasar negara berarti mematuhi kedua aspek tersebut.</p><p>Jawaban tepat: <strong>C. Pengamalan pokok pikiran Pembukaan dan prinsip-prinsip pasal UUD 1945</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Amandemen konstitusi berakar pada kedaulatan rakyat dan bukan desakan kreditor asing.</li><li><strong>B</strong>: Hak berserikat dan berkumpul dijamin secara konstitusional bagi segenap warga negara.</li><li><strong>D</strong>: Hukum nasional diarahkan menuju dekolonisasi dan pembaruan hukum berlandaskan Pancasila.</li><li><strong>E</strong>: Kedaulatan moneter berada di tangan bank sentral independen milik Republik Indonesia.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 30) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pembukaan UUD 1945 • terkandung dalam pasal-pasal UUD 1945</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Makna operasional mengamalkan Pancasila sebagai dasar negara bagi birokrasi pemerintahan adalah...", "options": ["Menjadikan Pancasila sebagai dasar mengatur penyelenggaraan pemerintahan", "Menjadikan pertimbangan laba komersial sebagai ukuran utama pelayanan publik", "Memberikan hak istimewa perizinan kepada korporasi pemodal terbesar", "Mengharuskan setiap aparatur menjadi anggota salah satu partai politik", "Mendelegasikan fungsi pertahanan keamanan kepada perusahaan swasta"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Fungsi Pancasila Mengatur Pemerintahan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Mengamalkan Pancasila sebagai dasar negara berarti <strong>mengamalkan Pancasila sebagai dasar untuk mengatur penyelenggaraan negara, termasuk penyelenggaraan pemerintahan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena birokrasi pemerintahan berkewajiban merujuk pada nilai-nilai Pancasila dalam merumuskan kebijakan, menyusun anggaran, dan melayani masyarakat. Tata kelola pemerintahan tidak boleh dijalankan atas dasar kekuasaan sewenang-wenang. Pancasila menjadi kompas etika dan hukum tata negara.</p><p>Jawaban tepat: <strong>A. Menjadikan Pancasila sebagai dasar mengatur penyelenggaraan pemerintahan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Pelayanan publik berorientasi pada kemanfaatan umum dan keadilan, bukan mengejar laba bisnis.</li><li><strong>C</strong>: Pemberian fasilitas istimewa bertentangan dengan prinsip keadilan dan kesetaraan hukum.</li><li><strong>D</strong>: Aparatur sipil negara diwajibkan menjaga netralitas dari afiliasi kepentingan partai politik.</li><li><strong>E</strong>: Pertahanan keamanan adalah wewenang mutlak negara yang tidak boleh diserahkan ke swasta.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 30) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>dasar untuk mengatur penyelenggaraan negara • termasuk penyelenggaraan pemerintahan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Penuangan jiwa Proklamasi Kemerdekaan 17 Agustus 1945 ke dalam Pembukaan UUD 1945 terwujud dalam...", "options": ["Tiga pilar doktrin pembagian kekuasaan trias politika murni", "Dua belas butir rencana pembangunan jangka panjang nasional", "Sepuluh pedoman pelaksanaan pemilihan umum berkala serentak", "Empat pokok pikiran yang menjiwai seluruh batang tubuh konstitusi", "Lima program percepatan pemulihan ekonomi pasca kemerdekaan"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penuangan Jiwa Proklamasi dalam Pembukaan UUD 1945</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Penuangan jiwa Proklamasi Kemerdekaan ke dalam Pembukaan UUD 1945 <strong>mengandung empat pokok pikiran</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena Pembukaan UUD 1945 memuat empat pokok pikiran yang merupakan pancaran langsung dari sila-sila Pancasila. Keempat pokok pikiran ini menjadi asas kerohanian negara yang kemudian dijabarkan ke dalam pasal-pasal UUD 1945. Inilah konstruksi filosofis konstitusi Indonesia.</p><p>Jawaban tepat: <strong>D. Empat pokok pikiran yang menjiwai seluruh batang tubuh konstitusi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Indonesia menganut pembagian kekuasaan (distribution of power) dan bukan pemisahan murni.</li><li><strong>B</strong>: Rencana pembangunan nasional merupakan produk kebijakan perencanaan dan perundang-undangan.</li><li><strong>C</strong>: Ketentuan teknis pemilu diatur dalam undang-undang tersendiri dan bukan materi pokok pikiran.</li><li><strong>E</strong>: Program pemulihan ekonomi adalah kebijakan situasional kabinet pemerintahan awal revolusi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 30) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>penuangan jiwa Proklamasi Kemerdekaan • mengandung empat pokok pikiran</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Kewajiban fundamental setiap warga negara dalam rangka menjaga kelestarian dasar negara adalah...", "options": ["Menghafalkan urutan pasal-pasal pidana tanpa mempraktikkan etikanya", "Menghayati secara utuh serta mengamalkan nilai Pancasila secara konsisten", "Menyusun traktat kerja sama keamanan bilateral dengan negara tetangga", "Mendirikan partai politik berbasis kedaerahan di setiap provinsi", "Mengawasi jalannya peradilan militer di lingkungan komando daerah"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kewajiban Warga Negara terhadap Nilai Pancasila</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Menjadi kewajiban setiap warga negara untuk <strong>menghayati secara utuh nilai-nilai Pancasila agar dapat mengamalkannya secara konsisten dan bertanggung jawab</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena pemahaman yang parsial atau terputus-putus dapat memicu salah tafsir terhadap Pancasila. Kelima sila saling menjiwai dan merupakan kesatuan bulat yang tidak dapat dipisahkan. Penghayatan utuh melahirkan pengamalan konsisten dalam kehidupan bermasyarakat dan bernegara.</p><p>Jawaban tepat: <strong>B. Menghayati secara utuh serta mengamalkan nilai Pancasila secara konsisten</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Hafalan tanpa pengamalan etika tidak memberikan dampak positif bagi keadaban bangsa.</li><li><strong>C</strong>: Perjanjian pertahanan bilateral merupakan kewenangan pemerintah pusat dan menteri luar negeri.</li><li><strong>D</strong>: Partai politik di Indonesia wajib berasaskan kesatuan nasional dan bukan paham kedaerahan.</li><li><strong>E</strong>: Pengawasan peradilan militer dijalankan oleh instansi penegak hukum militer yang sah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 30) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>kewajiban setiap warga negara untuk menghayati • secara utuh nilai-nilai yang terkandung</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Substansi Pokok Pikiran Pertama dalam Pembukaan UUD 1945 menegaskan prinsip...", "options": ["Negara serikat yang memberikan hak kedaulatan mutlak kepada negara bagian", "Negara monarki konstitusional di bawah pimpinan seorang perdana menteri", "Negara persatuan yang melindungi segenap bangsa dan tumpah darah", "Negara korporatis yang mengutamakan kepentingan perserikatan dagang", "Negara persemakmuran yang terikat pada pakta pertahanan militer dunia"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Substansi Pokok Pikiran Pertama</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pokok Pikiran Pertama menegaskan <strong>Negara persatuan (sila ketiga) yang melindungi segenap bangsa Indonesia dan seluruh tumpah darah Indonesia</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena pokok pikiran pertama mewajibkan negara menjamin keselamatan seluruh rakyat dan menjaga keutuhan seluruh wilayah nusantara. Negara berdiri tegak menaungi segenap kemajemukan bangsa tanpa membedakan latar belakang suku atau wilayah. Inilah manifestasi sila persatuan Indonesia.</p><p>Jawaban tepat: <strong>C. Negara persatuan yang melindungi segenap bangsa dan tumpah darah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Bentuk negara serikat bertentangan dengan prinsip negara kesatuan yang dicita-citakan.</li><li><strong>B</strong>: Sistem monarki konstitusional tidak sesuai dengan bentuk republik kedaulatan rakyat.</li><li><strong>D</strong>: Negara korporatis menempatkan sindikat bisnis di atas kepentingan umum rakyat banyak.</li><li><strong>E</strong>: Indonesia menolak terikat pakta pertahanan militer demi menjaga politik luar negeri bebas aktif.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 30) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Negara persatuan (sila ketiga) • melindungi segenap bangsa Indonesia dan seluruh tumpah darah</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Dalam penyelenggaraan negara berdasarkan Pokok Pikiran Persatuan, kedudukan negara adalah...", "options": ["Alat pemaksa kekuasaan bagi partai politik pemenang pemilihan umum", "Wadah federasi antarsuku yang memiliki hak veto kebijakan pusat", "Pelindung khusus bagi golongan pemilik modal investasi perbankan", "Pemberi kebebasan tanpa batas bagi propaganda persaingan individu", "Mengatasi segala paham golongan dan mengatasi segala paham perorangan"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Prinsip Mengatasi Paham Golongan dan Perseorangan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam pokok pikiran persatuan, <strong>negara mengatasi segala paham golongan dan mengatasi segala paham perorangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena negara tidak boleh dikuasai atau berpihak secara eksklusif kepada kelompok tertentu, baik golongan mayoritas, bangsawan, maupun pemilik modal. Negara berdiri di atas semua kelompok demi mewujudkan keadilan bersama. Hal ini mencerminkan paham integralistik kebangsaan.</p><p>Jawaban tepat: <strong>E. Mengatasi segala paham golongan dan mengatasi segala paham perorangan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Negara bukan instrumen kekuasaan sepihak pemenang pemilu untuk menekan kelompok lain.</li><li><strong>B</strong>: Sistem veto suku bertentangan dengan musyawarah mufakat demi persatuan nasional.</li><li><strong>C</strong>: Perlindungan negara diberikan secara berkeadilan tanpa perlakuan istimewa kepada konglomerat.</li><li><strong>D</strong>: Kebebasan individu dibatasi oleh norma hukum, kesusilaan, dan hak-hak orang lain.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 30) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>negara mengatasi segala paham • golongan, mengatasi segala paham perorangan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C3 - Aplikasi", "question": "Sikap aparatur pemerintah yang mencerminkan Pokok Pikiran Persatuan dalam pelayanan adalah...", "options": ["Mengutamakan kepentingan bangsa dan rakyat di atas kepentingan pribadi", "Mendahulukan penyelesaian berkas rekan separtai sebelum memproses masyarakat", "Mengalihkan fasilitas kedinasan untuk mendukung kegiatan bisnis keluarga", "Membatasi pelayanan bagi warga dari daerah yang berbeda latar belakang budaya", "Menyimpan data publik sebagai konsumsi terbatas kelompok pertemanan"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Skala Prioritas Kepentingan Nasional</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Negara dan rakyat Indonesia <strong>mengutamakan kepentingan negara dan rakyat di atas kepentingan golongan dan kepentingan perorangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena aparatur sipil negara terikat kewajiban etika untuk mendahulukan kemaslahatan umum dibandingkan keuntungan pribadi atau kelompoknya. Loyalitas aparatur ditujukan sepenuhnya kepada bangsa dan negara. Penerapan sikap ini mencegah terjadinya korupsi, kolusi, dan nepotisme.</p><p>Jawaban tepat: <strong>A. Mengutamakan kepentingan bangsa dan rakyat di atas kepentingan pribadi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Mendahulukan rekan separtai merupakan praktik nepotisme yang melanggar netralitas aparatur.</li><li><strong>C</strong>: Pemanfaatan aset dinas untuk bisnis pribadi merupakan bentuk penyalahgunaan fasilitas negara.</li><li><strong>D</strong>: Diskriminasi pelayanan berdasarkan asal daerah bertentangan dengan kode etik aparatur.</li><li><strong>E</strong>: Penyembunyian informasi publik melanggar asas transparansi tata kelola pemerintahan yang baik.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 30) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>mengutamakan kepentingan negara dan rakyat di atas • kepentingan golongan dan kepentingan perorangan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Pengertian persatuan dalam Pembukaan UUD 1945 bersifat inklusif karena menghendaki persatuan...", "options": ["Terbatas di antara perkumpulan saudagar terkemuka kota pelabuhan", "Berdasarkan kesamaan garis keturunan biologis klan tertentu", "Terbatas pada wilayah pulau yang padat jumlah penduduk warganya", "Yang meliputi segenap komponen bangsa Indonesia secara menyeluruh", "Di antara kelompok elite birokrasi pemerintahan pusat ibu kota"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Cakupan Persatuan Pembukaan UUD 1945</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Negara menurut pengertian dalam Pembukaan UUD 1945 <strong>menghendaki persatuan, meliputi segenap bangsa Indonesia seluruhnya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena persatuan Indonesia merangkul seluruh suku bangsa, golongan sosial, dan pulau di nusantara tanpa pemisahan wilayah inti dan pinggiran. Segenap warga dipandang setara dalam hak dan kewajiban sebagai tumpah darah Indonesia. Inilah esensi persatuan yang utuh dan menyeluruh.</p><p>Jawaban tepat: <strong>D. Yang meliputi segenap komponen bangsa Indonesia secara menyeluruh</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Persatuan bangsa bukan aliansi kartel bisnis antarsaudagar di kawasan perdagangan.</li><li><strong>B</strong>: Konsep bangsa Indonesia bersifat politis-sosiologis kebangsaan, bukan ikatan klan rasial.</li><li><strong>C</strong>: Persatuan mencakup seluruh pulau dari Sabang sampai Merauke, termasuk pulau terluar.</li><li><strong>E</strong>: Persatuan adalah milik segenap rakyat jelata dan bukan monopoli kalangan elite penguasa.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 30) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menghendaki persatuan, meliputi segenap • bangsa Indonesia seluruhnya</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Klausul negara melindungi segenap bangsa dan seluruh tumpah darah secara eksplisit tercantum dalam...", "options": ["Alinea pertama naskah Pembukaan Undang-Undang Dasar 1945", "Alinea keempat naskah Pembukaan Undang-Undang Dasar 1945", "Alinea kedua teks pernyataan kemerdekaan bangsa Indonesia", "Penjelasan umum mengenai sistem pemerintahan presidensial", "Pasal aturan peralihan penyerahan inventaris aset negara"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Letak Klausul Melindungi Segenap Bangsa</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pernyataan melindungi segenap bangsa Indonesia dan seluruh tumpah darah terkandung dalam <strong>alinea keempat Pembukaan UUD 1945</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena alinea keempat memuat tujuan nasional pertama yaitu melindungi segenap bangsa dan tumpah darah. Tujuan ini menjadi mandat perlindungan keamanan dan kedaulatan negara. Kalimat ini mendahului penyebutan tujuan kesejahteraan dan pencerdasan bangsa.</p><p>Jawaban tepat: <strong>B. Alinea keempat naskah Pembukaan Undang-Undang Dasar 1945</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Alinea pertama menegaskan hak kemerdekaan segala bangsa dan penolakan atas penjajahan.</li><li><strong>C</strong>: Alinea kedua menegaskan keberhasilan pergerakan mengantar rakyat ke pintu gerbang merdeka.</li><li><strong>D</strong>: Penjelasan umum menjelaskan sistem ketatanegaraan tanpa memuat teks resmi tujuan nasional.</li><li><strong>E</strong>: Aturan peralihan mengatur kontinuitas hukum lama sebelum dibentuk peraturan baru.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 30) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pernyataan ini terkandung dalam • alinea keempat Pembukaan UUD 1945</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Substansi Pokok Pikiran Kedua dalam Pembukaan UUD 1945 memuat tujuan mewujudkan...", "options": ["Keadilan sosial bagi seluruh rakyat Indonesia (sila kelima)", "Persekutuan pertahanan bersama dengan negara adidaya dunia", "Pemusatan seluruh hak kepemilikan tanah ke dalam kas negara", "Kebebasan pasar monopoli bagi perserikatan niaga perkapalan", "Penghapusan seluruh subsidi sosial bagi masyarakat miskin"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Substansi Pokok Pikiran Kedua</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pokok Pikiran Kedua menegaskan <strong>Negara bertujuan mewujudkan keadilan sosial bagi seluruh rakyat dalam rangka mewujudkan Indonesia yang merdeka, bersatu, berdaulat, adil, dan makmur (sila kelima)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena keadilan sosial merupakan muara akhir dari seluruh perjuangan mendirikan negara merdeka. Negara tidak boleh membiarkan ketimpangan ekstrem antara kaya dan miskin terus berlanjut. Pokok pikiran ini menuntut negara memajukan kesejahteraan umum bagi segenap rakyat.</p><p>Jawaban tepat: <strong>A. Keadilan sosial bagi seluruh rakyat Indonesia (sila kelima)</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Aliansi militer asing bertentangan dengan prinsip non-blok politik luar negeri Indonesia.</li><li><strong>C</strong>: Hak milik perorangan tetap diakui dengan fungsi sosial, tidak dihapuskan sewenang-wenang.</li><li><strong>D</strong>: Monopoli pasar ditentang karena merusak sendi perekonomian dan mematikan usaha rakyat.</li><li><strong>E</strong>: Negara berkewajiban menyelenggarakan jaminan dan bantuan sosial bagi warga miskin.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 31) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Negara bertujuan mewujudkan keadilan sosial bagi seluruh rakyat • sila kelima</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Kewajiban negara yang tercantum dalam pokok pikiran keadilan sosial meliputi...", "options": ["Menetapkan tarif pajak tertinggi kepada sektor industri kecil", "Mewajibkan ekspor seluruh bahan mentah tanpa pengolahan hilirisasi", "Membatasi kuota penerimaan peserta didik dari keluarga prasejahtera", "Menghentikan pembangunan sarana kesehatan di daerah kepulauan", "Memajukan kesejahteraan umum dan mencerdaskan kehidupan bangsa"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tiga Kewajiban Negara dalam Pokok Pikiran Keadilan Sosial</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam pokok pikiran keadilan sosial, negara berkewajiban <strong>memajukan kesejahteraan umum, mencerdaskan kehidupan bangsa, dan mewujudkan keadilan sosial bagi seluruh rakyat Indonesia</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena memajukan kesejahteraan dan mencerdaskan kehidupan bangsa adalah pilar utama pemenuhan hak-hak dasar warga negara. Tanpa pendidikan bermutu dan kesejahteraan layak, keadilan sosial tidak akan pernah terwujud. Inilah tugas luhur negara yang dimandatkan konstitusi.</p><p>Jawaban tepat: <strong>E. Memajukan kesejahteraan umum dan mencerdaskan kehidupan bangsa</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pajak disusun secara progresif dan adil untuk melindungi industri kecil dan menengah.</li><li><strong>B</strong>: Perekonomian nasional mendorong kemandirian dan hilirisasi demi nilai tambah bangsa.</li><li><strong>C</strong>: Negara wajib menjamin akses pendidikan seluas-luasnya bagi seluruh anak bangsa.</li><li><strong>D</strong>: Fasilitas kesehatan wajib dibangun merata hingga ke pelosok wilayah perbatasan terluar.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 31) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>kewajiban memajukan kesejahteraan umum • mencerdaskan kehidupan bangsa</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Substansi Pokok Pikiran Ketiga dalam Pembukaan UUD 1945 adalah...", "options": ["Kekuasaan pemerintahan tertinggi dipimpin komando tentara", "Sistem perwakilan yang diisi eksklusif oleh kelompok ningrat feodal", "Negara berkedaulatan rakyat berdasar permusyawaratan perwakilan", "Kewenangan mutlak kepala negara menetapkan undang-undang sendiri", "Penghapusan lembaga perwakilan rakyat demi efisiensi birokrasi"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Substansi Pokok Pikiran Ketiga</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pokok Pikiran Ketiga menegaskan <strong>Negara berkedaulatan rakyat berdasarkan atas kerakyatan dalam permusyawaratan/perwakilan (sila keempat)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena pokok pikiran ini meletakkan fondasi demokrasi Indonesia di mana kedaulatan berada di tangan rakyat. Rakyat menyalurkan aspirasinya melalui lembaga perwakilan secara musyawarah mufakat. Asas ini mencegah lahirnya pemerintahan otoriter atau kediktatoran.</p><p>Jawaban tepat: <strong>C. Negara berkedaulatan rakyat berdasar permusyawaratan perwakilan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Komando militer tunduk pada supremasi sipil dan bukan pemegang kedaulatan tertinggi.</li><li><strong>B</strong>: Sistem feodalisme ningrat telah dihapuskan seiring tegaknya kedaulatan republik merdeka.</li><li><strong>D</strong>: Presiden membentuk undang-undang bersama lembaga legislatif melalui persetujuan bersama.</li><li><strong>E</strong>: Lembaga parlemen merupakan pilar pokok demokrasi perwakilan yang wajib dipertahankan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 31) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Negara berkedaulatan rakyat berdasarkan atas kerakyatan dalam • permusyawaratan/ perwakilan (sila keempat)</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Sistem pemerintahan demokrasi khas Indonesia yang berakar pada kedaulatan rakyat disebut...", "options": ["Demokrasi Liberal parlementer berbasis kebebasan individu", "Demokrasi Pancasila yang dipandu hikmat kebijaksanaan", "Demokrasi Proletariat berbasis kediktatoran satu golongan", "Demokrasi Monarki berbasis hak istimewa keturunan darah", "Demokrasi Korporatokrasi berbasis kekuasaan asosiasi modal"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Nama Sistem Demokrasi Indonesia</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sistem pemerintahan demokrasi di Indonesia disebut <strong>Demokrasi Pancasila</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena Demokrasi Pancasila memadukan kedaulatan rakyat dengan nilai ketuhanan, kemanusiaan, persatuan, dan keadilan sosial. Keputusan diambil melalui musyawarah mufakat yang berlandaskan akal budi luhur. Sistem ini membedakan Indonesia dari model demokrasi barat maupun timur.</p><p>Jawaban tepat: <strong>B. Demokrasi Pancasila yang dipandu hikmat kebijaksanaan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Demokrasi liberal menonjolkan individualisme dan tirani angka hasil pemungutan suara.</li><li><strong>C</strong>: Demokrasi proletariat meniadakan hak politik kelompok lain atas nama revolusi kelas.</li><li><strong>D</strong>: Sistem monarki kekeluargaan bertentangan dengan asas republik yang berkedaulatan rakyat.</li><li><strong>E</strong>: Korporatokrasi adalah dominasi korporasi bisnis yang merusak keadilan sosial masyarakat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 31) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>sistem pemerintahan demokrasi yang kita sebut • Demokrasi Pancasila</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Substansi Pokok Pikiran Keempat dalam Pembukaan UUD 1945 mengintegrasikan nilai...", "options": ["Materialisme dialektis serta doktrin sekularisme birokrasi", "Sentralisme kekuasaan mutlak dengan pembatasan hak beribadah", "Kedaulatan pasar bebas dengan proteksi perdagangan luar negeri", "Ketuhanan Yang Maha Esa dan kemanusiaan yang adil dan beradab", "Pragmatisme anggaran belanja dengan peniadaan norma moral"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Substansi Pokok Pikiran Keempat</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pokok Pikiran Keempat menegaskan <strong>Negara berdasarkan Ketuhanan Yang Maha Esa menurut dasar kemanusiaan yang adil dan beradab (sila pertama dan kedua)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena pokok pikiran ini menuntut pemerintah dan penyelenggara negara memiliki budi pekerti kemanusiaan yang luhur serta memegang teguh cita-cita moral rakyat yang mulia. Dimensi moral religius dipadukan dengan penghormatan harkat martabat manusia. Hal ini membentuk karakter keadaban birokrasi.</p><p>Jawaban tepat: <strong>D. Ketuhanan Yang Maha Esa dan kemanusiaan yang adil dan beradab</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Materialisme dialektis bertentangan dengan pengakuan keimanan terhadap Tuhan Yang Maha Esa.</li><li><strong>B</strong>: Pembatasan hak beribadah melanggar jaminan konstitusional kebebasan memeluk keyakinan.</li><li><strong>C</strong>: Doktrin pasar bebas adalah mazhab ekonomi kapitalis dan bukan pokok pikiran moral UUD.</li><li><strong>E</strong>: Penyelenggaraan negara wajib berlandaskan etika luhur dan tidak boleh terjebak pragmatisme.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 31) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Negara berdasarkan Ketuhanan Yang Maha Esa menurut dasar kemanusiaan • yang adil dan beradab</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Karakteristik hubungan agama dan negara Republik Indonesia menurut pokok pikiran keempat adalah...", "options": ["Negara theokrasi mutlak di bawah kepemimpinan otoritas ulama", "Negara monarki keagamaan dengan kewajiban satu agama tunggal", "Negara sekuler yang melarang seluruh simbol agama di ranah publik", "Negara ateis yang meniadakan keberadaan institusi rumah ibadah", "Bukan negara theokrasi dan juga bukan negara sekuler"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Karakteristik Hubungan Agama dan Negara di Indonesia</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Negara Indonesia <strong>bukan negara theokrasi, tetapi juga bukan negara sekuler</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena Indonesia tidak mendasarkan hukum pada satu agama tertentu (theokrasi), tetapi juga tidak memisahkan urusan keagamaan dari etika kehidupan bernegara (sekuler). Negara menjunjung tinggi semua agama dan kepercayaan serta memfasilitasi kerukunan hidup beragama. Keselarasan ini menjadi ciri khas kebangsaan Indonesia.</p><p>Jawaban tepat: <strong>E. Bukan negara theokrasi dan juga bukan negara sekuler</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Negara theokrasi mendasarkan kepemimpinan pada hukum agama tertentu secara eksklusif.</li><li><strong>B</strong>: Pancasila melindungi kemajemukan pemeluk agama resmi yang diakui tanpa monopoli tunggal.</li><li><strong>C</strong>: Negara sekuler memisahkan mutlak agama dari kehidupan kenegaraan dan kebijakan publik.</li><li><strong>D</strong>: Pancasila menegaskan Ketuhanan Yang Maha Esa dan secara mutlak menolak paham ateisme.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 31) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>bukan negara theokrasi, tetapi juga bukan • negara sekuler</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C3 - Aplikasi", "question": "Wujud pengamalan prinsip keselarasan bernegara dan beragama oleh aparatur pemerintah adalah...", "options": ["Menutup perizinan pembangunan sarana ibadah umat beragama lain", "Menjunjung tinggi toleransi serta kebebasan beribadah segenap pemeluk", "Mewajibkan warga pindah keyakinan mengikuti pejabat pimpinan satker", "Menghilangkan hari libur keagamaan dari kalender resmi nasional", "Menyusun regulasi daerah yang mendiskriminasi kelompok keyakinan"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Makna Keselarasan Bernegara dan Beragama</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Negara menjunjung tinggi semua agama dan kepercayaan dengan mewujudkan <strong>keselarasan kehidupan bernegara dan beragama</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena aparatur negara berkewajiban memberikan perlindungan dan jaminan kemerdekaan bagi tiap-tiap penduduk untuk memeluk agamanya dan beribadat menurut agamanya itu. Sikap toleran dan nondiskriminatif memperkokoh persaudaraan kebangsaan. Hal ini mencerminkan pengamalan sila pertama dan kedua.</p><p>Jawaban tepat: <strong>B. Menjunjung tinggi toleransi serta kebebasan beribadah segenap pemeluk</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Menghambat pembangunan rumah ibadah melanggar hak konstitusional kebebasan beragama.</li><li><strong>C</strong>: Pemaksaan keyakinan bertentangan dengan prinsip hak asasi manusia dalam konstitusi.</li><li><strong>D</strong>: Pengakuan hari libur keagamaan adalah wujud nyata apresiasi negara terhadap kemajemukan.</li><li><strong>E</strong>: Regulasi diskriminatif bertentangan dengan prinsip negara kesatuan yang menjamin kesetaraan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 31) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>keselarasan kehidupan bernegara dan beragama • menjunjung tinggi semua agama</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Prinsip hubungan internasional yang ditegaskan dalam alinea pertama Pembukaan UUD 1945 adalah...", "options": ["Persetujuan terhadap dominasi ekonomi negara pemenang perang", "Kewajiban mengikuti seluruh instruksi aliansi pakta militer barat", "Penolakan terhadap penjajahan karena bertentangan perikemanusiaan", "Pemberian kebebasan penguasaan teritorial pulau oleh tentara asing", "Pengakuan terhadap eksistensi kolonialisme di kawasan benua afrika"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penegasan Alinea Pertama Pembukaan UUD 1945</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Alinea pertama menegaskan bahwa kemerdekaan adalah hak segala bangsa, oleh sebab itu <strong>penjajahan di atas dunia harus dihapuskan karena tidak sesuai dengan perikemanusiaan dan perikeadilan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena alinea pertama meletakkan dasar politik luar negeri Indonesia yang anti-kolonialisme dan anti-imperialisme dalam segala bentuknya. Indonesia secara konsisten membela kemerdekaan bangsa-bangsa tertindas di forum internasional. Prinsip universal ini mengikat komitmen diplomasi Indonesia.</p><p>Jawaban tepat: <strong>C. Penolakan terhadap penjajahan karena bertentangan perikemanusiaan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Indonesia menentang neo-kolonialisme dan hegemoni ekonomi yang menindas kedaulatan bangsa.</li><li><strong>B</strong>: Indonesia berpegang teguh pada prinsip kedaulatan independen tanpa tunduk pada pakta asing.</li><li><strong>D</strong>: Kedaulatan wilayah tanah air tidak boleh diserahkan sejengkal pun kepada militer asing.</li><li><strong>E</strong>: Konstitusi menghendaki penghapusan seluruh penjajahan di atas muka bumi tanpa pengecualian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 31) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Negara Indonesia yang merdeka dan anti penjajahan • hak segala bangsa</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Lima predikat cita-cita negara merdeka yang ditegaskan dalam alinea kedua Pembukaan UUD 1945 adalah...", "options": ["Merdeka, bersatu, berdaulat, adil, dan makmur bagi rakyat", "Kaya, berkuasa, mandiri, disegani, dan berpangkat tinggi", "Serikat, otonom, liberal, industri, dan berorientasi ekspor", "Konvensional, feodal, sentralistik, seragam, dan tertutup", "Kekaisaran, ekspansif, dominan, proteksionis, dan militeristik"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penegasan Alinea Kedua Pembukaan UUD 1945</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Alinea kedua menegaskan bahwa perjuangan kemerdekaan mengantarkan rakyat ke pintu gerbang negara Indonesia yang <strong>merdeka, bersatu, berdaulat, adil, dan makmur</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena kelima predikat tersebut melambangkan cita-cita utuh bangsa: merdeka dari penindasan, bersatu wilayah dan jiwanya, berdaulat menentukan nasib sendiri, adil dalam tatanan hukum sosial, dan makmur taraf kesejahteraannya. Rangkaian kata ini adalah visi luhur Indonesia merdeka.</p><p>Jawaban tepat: <strong>A. Merdeka, bersatu, berdaulat, adil, dan makmur bagi rakyat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Keinginan berkuasa dan status gengsi bukan cita-cita normatif perjuangan kemerdekaan.</li><li><strong>C</strong>: Bentuk serikat dan doktrin liberal bertentangan dengan prinsip negara kesatuan Indonesia.</li><li><strong>D</strong>: Sistem feodal dan sentralistik kaku ditolak demi mewujudkan kedaulatan rakyat merata.</li><li><strong>E</strong>: Watak kekaisaran ekspansif bertentangan dengan amanat perdamaian abadi dalam konstitusi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 31) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>mengantarkan rakyat Indonesia ke pintu gerbang • merdeka, bersatu, berdaulat, adil, dan makmur</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Pokok Pikiran Keempat Pembukaan UUD 1945 secara substansial merupakan perwujudan langsung dari...", "options": ["Sila kelima Keadilan Sosial bagi Seluruh Rakyat Indonesia", "Sila keempat Kerakyatan dalam permusyawaratan perwakilan", "Sila ketiga Persatuan Indonesia dalam bingkai keragaman", "Sila pertama Ketuhanan YME dan sila kedua Kemanusiaan adil beradab", "Trisila Sosio Nasionalisme dan Sosio Demokrasi kerakyatan"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Landasan Keselarasan Pokok Pikiran Keempat</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pokok Pikiran Keempat merupakan <strong>perwujudan dari sila pertama (Ketuhanan Yang Maha Esa) dan sila kedua (Kemanusiaan yang Adil dan Beradab)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena pokok pikiran keempat secara eksplisit menghubungkan keimanan kepada Tuhan Yang Maha Esa dengan kewajiban memelihara budi pekerti kemanusiaan yang adil dan beradab. Penyelenggara negara diwajibkan memegang teguh cita-cita moral yang luhur. Inilah paduan harmonis antara dimensi vertikal religius dan horizontal kemanusiaan.</p><p>Jawaban tepat: <strong>D. Sila pertama Ketuhanan YME dan sila kedua Kemanusiaan adil beradab</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Sila kelima merupakan landasan substansial dari Pokok Pikiran Kedua (Keadilan Sosial).</li><li><strong>B</strong>: Sila keempat merupakan landasan substansial dari Pokok Pikiran Ketiga (Kedaulatan Rakyat).</li><li><strong>C</strong>: Sila ketiga merupakan landasan substansial dari Pokok Pikiran Pertama (Persatuan).</li><li><strong>E</strong>: Konsep Trisila bukan pembagian pokok pikiran resmi yang disahkan dalam Pembukaan UUD 1945.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 31) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>keselarasan • kehidupan bernegara dan beragama</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Konsep kodrat manusia Indonesia sebagai makhluk individu sekaligus sosial disebut...", "options": ["Materialistis mekanistik dalam mengejar produksi ekonomi", "Monodualistis dalam keseimbangan pribadi dan kemasyarakatan", "Egoistik individualistis yang menomorsatukan hak asasi pribadi", "Kolektivistis totaliter yang menghapuskan identitas pribadi", "Pragmatistis utilitarian yang mengukur manfaat kesenangan"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Hakikat Manusia Monodualistis</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Hakikat manusia Indonesia adalah sebagai makhluk individu sekaligus makhluk sosial dalam satu kesatuan yang disebut <strong>monodualistis</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena falsafah Pancasila memandang manusia tidak dapat dipisahkan antara eksistensi dirinya sebagai pribadi dan perannya sebagai anggota masyarakat. Kepentingan pribadi dan kepentingan bersama harus berjalan selaras dan seimbang tanpa saling meniadakan. Konsep ini menjadi fondasi relasi warga dan negara.</p><p>Jawaban tepat: <strong>B. Monodualistis dalam keseimbangan pribadi dan kemasyarakatan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Materialistis mekanistik memandang manusia sekadar alat produksi kerja ekonomi pabrik.</li><li><strong>C</strong>: Individualistik ekstrem hanya mengutamakan hak diri sendiri dengan mengabaikan orang lain.</li><li><strong>D</strong>: Totalitarianisme mereduksi kebebasan individu secara mutlak demi kepentingan kekuasaan negara.</li><li><strong>E</strong>: Pragmatisme utilitarian mendasarkan moralitas semata-mata pada utilitas atau kenikmatan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 32) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>makhluk individu sekaligus makhluk sosial dalam satu kesatuan • disebut “monodualistis”</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan bunyi Pasal 1 ayat (1) UUD 1945, bentuk negara dan pemerintahan Indonesia adalah...", "options": ["Negara monarki parlementer dengan perdana menteri berkuasa", "Negara federasi serikat dengan pembagian otonomi berdaulat", "Negara kesatuan dengan bentuk pemerintahan republik", "Negara uni personal di bawah pimpinan seorang gubernur jenderal", "Negara konfederasi provinsi dengan ikatan perjanjian perjanjian"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Bentuk Negara dan Pemerintahan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sesuai Pasal 1 ayat (1) UUD 1945, <strong>negara kita ialah negara kesatuan yang berbentuk republik</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena konstitusi menegaskan bahwa kedaulatan wilayah Indonesia tidak terbagi-bagi ke dalam negara-negara bagian yang berdaulat sendiri. Bentuk republik mengamanatkan kepemimpinan negara dipilih melalui mekanisme perwakilan rakyat, bukan diwariskan secara turun-temurun. Inilah pilihan final bentuk negara.</p><p>Jawaban tepat: <strong>C. Negara kesatuan dengan bentuk pemerintahan republik</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Monarki parlementer bertentangan dengan prinsip kedaulatan rakyat dan bentuk republik.</li><li><strong>B</strong>: Negara serikat pernah diberlakukan masa RIS 1949 dan segera ditinggalkan kembali ke NKRI.</li><li><strong>D</strong>: Uni personal adalah gabungan kerajaan di bawah satu mahkota raja, tidak dianut Indonesia.</li><li><strong>E</strong>: Konfederasi adalah perserikatan antarnegara merdeka berdaulat, bukan bentuk integrasi NKRI.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 32) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pasal 1 ayat (1) UUD 1945, negara kita ialah • negara kesatuan yang berbentuk republik</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Alasan utama bentuk negara kesatuan (Eenheidstaat) dinilai paling tepat bagi Indonesia adalah...", "options": ["Kemudahan dalam pengajuan pinjaman modal pembangunan perbankan", "Kewajiban mengikuti tata kelola administrasi monarki eropa", "Keinginan memusatkan perizinan industri di kementerian ibu kota", "Sesuai dengan sejarah perjuangan dan Wawasan Nusantara bangsa", "Instruksi dari komisi perundingan gencatan senjata perserikatan"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Alasan Pemilihan Bentuk Negara Kesatuan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Bentuk negara kesatuan <strong>(Eenheidstaat)</strong> adalah yang paling tepat karena <strong>sesuai dengan sejarah perjuangan dan perkembangan bangsa yang memiliki Wawasan Nusantara</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena pengalaman pahit penjajahan yang menggunakan politik pecah belah menyadarkan bangsa akan pentingnya persatuan bulat. Wawasan Nusantara memandang seluruh kepulauan sebagai satu kesatuan utuh lahir dan batin. Negara kesatuan menjamin persatuan wilayah dan rakyat secara lestari.</p><p>Jawaban tepat: <strong>D. Sesuai dengan sejarah perjuangan dan Wawasan Nusantara bangsa</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Bentuk negara tidak ditetapkan demi pertimbangan administratif pencairan pinjaman utang.</li><li><strong>B</strong>: Indonesia menolak meniru tatanan monarki feodal bekas negara penjajah bangsa eropa.</li><li><strong>C</strong>: Negara kesatuan tidak berarti sentralisasi perizinan yang mematikan inisiatif daerah.</li><li><strong>E</strong>: Pilihan negara kesatuan adalah kehendak murni rakyat, bukan arahan komisi luar negeri.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 32) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>bentuk negara kesatuan (Eenheidstaat) • sesuai dengan sejarah perjuangan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Aspek kesatuan Wawasan Nusantara yang mencakup ketahanan nasional disingkat dengan...", "options": ["Ipoleksosbud Hankamnas sebagai keterpaduan segenap gatra", "Sishankamrata sebagai doktrin pertahanan rakyat semesta", "Trigatra alamiah dalam tata letak geografis kepulauan", "Astaperintis sebagai pedoman perencanaan jangka menengah", "Nawacita pembangunan infrastruktur kawasan perbatasan"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Akronim Kesatuan Wawasan Nusantara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Wawasan Nusantara memandang kepulauan sebagai satu kesatuan <strong>Ipoleksosbud Hankamnas (Ideologi, Politik, Ekonomi, Sosial Budaya, Pertahanan Keamanan Nasional)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena akronim tersebut merangkum seluruh gatra kehidupan nasional yang terpadu dalam satu wadah ketahanan nasional. Ancaman terhadap satu gatra akan berdampak pada gatra lainnya. Keterpaduan ini memperkokoh integritas Negara Kesatuan Republik Indonesia.</p><p>Jawaban tepat: <strong>A. Ipoleksosbud Hankamnas sebagai keterpaduan segenap gatra</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Sishankamrata adalah sistem pertahanan dan keamanan semesta dalam pasal 30 UUD 1945.</li><li><strong>C</strong>: Trigatra mencakup aspek geografi, sumber kekayaan alam, dan kependudukan saja.</li><li><strong>D</strong>: Astaperintis bukan istilah resmi dalam konsep Wawasan Nusantara.</li><li><strong>E</strong>: Nawacita adalah istilah program prioritas kerja pemerintah pada masa kepresidenan tertentu.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 32) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Ipoleksosbud Hankamnas • satu kesatuan politik, satu kesatuan ekonomi</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Perbedaan mendasar pandangan HAM dalam masyarakat Pancasila dibanding individualisme adalah...", "options": ["Pemberian kebebasan mutlak kepada perorangan tanpa batas norma", "Pengabaian hak kaum minoritas demi dominasi kelompok mayoritas", "Peniadaan seluruh perlindungan hak milik kekayaan perorangan", "Penyerahan tata peradilan sepenuhnya kepada ketua rukun tetangga", "Pelaksanaan hak asasi secara seimbang dengan kewajiban asasi"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Keseimbangan Hak dan Kewajiban Asasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam masyarakat Pancasila, <strong>hak asasi dilaksanakan secara seimbang dengan kewajiban asasi</strong> karena manusia adalah makhluk monodualistis.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena individualisme liberal menuntut hak pribadi secara berlebihan hingga dapat merugikan ketertiban umum. Sebaliknya, Pancasila menempatkan pemenuhan hak asasi berdampingan dengan kewajiban menghormati hak orang lain dan kepentingan masyarakat. Keseimbangan ini melahirkan keharmonisan sosial.</p><p>Jawaban tepat: <strong>E. Pelaksanaan hak asasi secara seimbang dengan kewajiban asasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kebebasan tanpa batas adalah ciri individualisme anarkis yang bertentangan dengan etika.</li><li><strong>B</strong>: Pancasila melindungi segenap warga bangsa tanpa membedakan status mayoritas atau minoritas.</li><li><strong>C</strong>: Hak milik perorangan tetap diakui dan dilindungi dengan memperhatikan fungsi kemanfaatan sosial.</li><li><strong>D</strong>: Peradilan ditegakkan oleh kekuasaan kehakiman independen, bukan lembaga ketetanggaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 32) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Negara Pancasila menjunjung tinggi hak asasi • terdapat kewajiban asasi</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Kelemahan paham individualisme dalam pelaksanaan hak asasi manusia adalah...", "options": ["Ketiadaan kebebasan berserikat dan mengemukakan pendapat di umum", "Kewajiban warga negara mengikuti seluruh arahan penguasa dinas", "Tuntutan hak berlebih-lebihan hingga merugikan masyarakat keseluruhan", "Penghapusan kebebasan berniaga dan berusaha di sektor swasta", "Timbulnya monopoli perdagangan oleh badan usaha milik negara"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kelemahan Pandangan HAM Individualistis</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam masyarakat individualistis, pelaksanaan HAM cenderung <strong>berlebih-lebihan sehingga mungkin merugikan masyarakat sebagai keseluruhan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena individualisme menempatkan ego perorangan di atas kepentingan bersama, yang dapat merusak tatanan ketertiban umum. Ketika setiap orang menuntut hak mutlaknya tanpa mempedulikan orang lain, terjadi benturan kepentingan yang merugikan. Pancasila mengoreksi kelemahan ini melalui asas kekeluargaan.</p><p>Jawaban tepat: <strong>C. Tuntutan hak berlebih-lebihan hingga merugikan masyarakat keseluruhan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Masyarakat individualistis justru sangat menjunjung tinggi kebebasan berpendapat di muka umum.</li><li><strong>B</strong>: Kepatuhan mutlak pada dinas birokrasi adalah ciri totalitarianisme, bukan individualisme.</li><li><strong>D</strong>: Kebebasan berusaha swasta merupakan pilar utama perekonomian masyarakat individualis.</li><li><strong>E</strong>: Monopoli negara merupakan ciri sistem etatisme sosialis dan bukan sistem individualistik.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 32) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>tuntutan pelaksanaan hak-hak asasi manusia ada kecenderungan • berlebih-lebihan sehingga mungkin merugikan masyarakat</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Pasal-pasal dalam UUD 1945 yang secara khusus memuat ketentuan komprehensif hak asasi manusia adalah...", "options": ["Pasal 2 ayat (1) dan Pasal 3 ayat (2) mengenai permusyawaratan", "Pasal 13 ayat (1) dan Pasal 14 ayat (1) mengenai grasi amnesti", "Pasal 4 ayat (1) dan Pasal 5 ayat (1) mengenai kekuasaan presiden", "Pasal 27, 28, serta rumpun Pasal 28A sampai dengan 28J", "Pasal 16 dan Pasal 17 mengenai kementerian kementerian negara"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Rujukan Konstitusional Hak Asasi Manusia</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Perwujudan HAM berdasarkan Pancasila dapat dilihat dalam Pembukaan UUD 1945 dan <strong>Pasal-pasal 27, 28, 28A s/d 28J, 29, 30, 31, 33, dan 34 UUD 1945</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena rumpun Pasal 28A hingga 28J merupakan bab tersendiri tentang Hak Asasi Manusia hasil amandemen konstitusi. Di dalamnya diatur hak hidup, hak berkeluarga, hak kepastian hukum, hak kebebasan beragama, hingga kewajiban menghormati hak orang lain. Inilah rujukan hukum positif HAM Indonesia.</p><p>Jawaban tepat: <strong>D. Pasal 27, 28, serta rumpun Pasal 28A sampai dengan 28J</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pasal 2 dan 3 mengatur keanggotaan dan kewenangan Majelis Permusyawaratan Rakyat.</li><li><strong>B</strong>: Pasal 13 dan 14 mengatur penunjukan duta konsul serta pemberian grasi dan rehabilitasi.</li><li><strong>C</strong>: Pasal 4 dan 5 mengatur kekuasaan pemerintahan dan pembuatan undang-undang oleh presiden.</li><li><strong>E</strong>: Pasal 16 dan 17 mengatur dewan pertimbangan presiden dan kementerian negara kabinet.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 32) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pembukaan UUD 1945 • 28 A s/d 28 J</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C3 - Aplikasi", "question": "Penerapan etika kedinasan aparatur sipil negara yang mencerminkan kodrat makhluk sosial adalah...", "options": ["Enggan bekerja sama dengan unit kerja lain demi menjaga keunggulan tim", "Membangun kolaborasi sinergis dan saling mendukung dalam tugas kantor", "Menyelesaikan urusan pribadi pada saat jam pelayanan masyarakat dibuka", "Mengunci rapat data kedinasan dari akses koordinasi rekan sekantor", "Menyalahkan pihak lain atas kegagalan pencapaian target kinerja bersama"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Implementasi Keseimbangan Makhluk Sosial</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sebagai makhluk sosial, manusia <strong>hidup bermasyarakat dan saling membutuhkan kerja sama demi mencapai tujuan bersama</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena kodrat makhluk sosial menuntut setiap aparatur mengedepankan sinergi, kepedulian, dan gotong royong dalam pelaksanaan tugas pelayanan. Bekerja bersama rekan kerja mencerminkan tanggung jawab sosial dalam organisasi. Hal ini menjamin kelancaran fungsi birokrasi pemerintahan.</p><p>Jawaban tepat: <strong>B. Membangun kolaborasi sinergis dan saling mendukung dalam tugas kantor</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Menolak kerja sama antartim adalah wujud ego sektoral yang merusak kinerja organisasi.</li><li><strong>C</strong>: Mengutamakan urusan pribadi saat jam pelayanan melanggar disiplin dan sumpah jabatan.</li><li><strong>D</strong>: Menutup akses koordinasi menghambat efisiensi tata kelola pemerintahan yang terpadu.</li><li><strong>E</strong>: Melempar kesalahan kepada pihak lain bertentangan dengan integritas dan akuntabilitas kerja.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 32) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>makhluk individu • makhluk sosial</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Komitmen Negara Pancasila dalam menjunjung tinggi hak asasi manusia ditegakkan dengan berlandaskan...", "options": ["Prinsip pemisahan mutlak urusan keagamaan dari hukum positif", "Pemberian kebebasan bersenjata bagi setiap warga sipil masyarakat", "Kepentingan dominasi permodalan investasi korporasi multinasional", "Instruksi otoriter pengadilan arbitrase perdagangan internasional", "Nilai-nilai ketuhanan, kemanusiaan yang beradab, dan keadilan"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Komitmen Negara Pancasila terhadap HAM</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Negara Pancasila <strong>menjunjung tinggi hak asasi manusia berlandaskan nilai ketuhanan, kemanusiaan adil beradab, dan keadilan sosial</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena HAM dalam konteks Indonesia tidak bersifat sekuler atau bebas nilai, melainkan dibimbing oleh norma etika religius dan kemanusiaan luhur. Penghormatan terhadap hak orang lain dipandang sebagai amanah moral kepada Tuhan dan sesama warga bangsa. Inilah keistimewaan konsep HAM Pancasila.</p><p>Jawaban tepat: <strong>E. Nilai-nilai ketuhanan, kemanusiaan yang beradab, dan keadilan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pemisahan mutlak agama dari hukum merupakan ciri negara sekuler murni, bukan Indonesia.</li><li><strong>B</strong>: Kebebasan kepemilikan senjata api bertentangan dengan ketertiban umum dan keamanan nasional.</li><li><strong>C</strong>: Penegakan HAM bertujuan memuliakan martabat manusia, bukan melayani korporasi modal.</li><li><strong>D</strong>: Arbitrase niaga mengurus sengketa dagang dan bukan dasar falsafah perlindungan HAM.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 32) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Negara Pancasila menjunjung • tinggi hak asasi</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Dua bentuk negara yang dikenal dalam kajian ilmu hukum tata negara adalah...", "options": ["Bentuk negara serikat (federal) dan bentuk negara kesatuan", "Negara feodal bangsawan dan negara protektorat kolonial", "Negara dominion persemakmuran dan negara mandat internasional", "Negara garnisun militer dan negara kepulauan perhimpunan", "Negara kota mandiri dan negara perserikatan pedagang maritim"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dua Bentuk Negara dalam Teori Ketatanegaraan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Mengenai bentuk negara, antara lain kita mengenal <strong>bentuk negara serikat dan negara kesatuan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena dalam teori bentuk susunan negara modern, dikotomi utama terletak antara negara federal (serikat) dan negara kesatuan (unitaris). Pendiri bangsa mempertimbangkan kedua alternatif ini dan secara sadar memilih bentuk negara kesatuan bagi Republik Indonesia. Pilihan ini menjamin integrasi nasional.</p><p>Jawaban tepat: <strong>A. Bentuk negara serikat (federal) dan bentuk negara kesatuan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Negara feodal dan protektorat adalah bentuk wilayah subordinat masa lampau prakemerdekaan.</li><li><strong>C</strong>: Dominion dan mandat merupakan status wilayah persemakmuran bentukan liga bangsa-bangsa.</li><li><strong>D</strong>: Negara garnisun adalah tipologi rezim militeristik dan bukan klasifikasi bentuk negara.</li><li><strong>E</strong>: Negara kota (polis) adalah bentuk negara kuno peradaban Yunani dan bukan negara bangsa modern.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 32) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>mengenal • bentuk negara serikat dan negara kesatuan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan Pasal 26 UUD 1945, pihak yang menjadi warga negara Indonesia adalah...", "options": ["Setiap wisatawan asing yang menetap sementara di kota pelabuhan", "Personel militer pasukan perdamaian yang ditugaskan di perbatasan", "Pejabat perwakilan korporasi dagang yang menyewa tanah perkebunan", "Orang bangsa Indonesia asli dan bangsa lain yang disahkan undang-undang", "Seluruh penduduk kawasan kepulauan asia tenggara yang berniaga"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Ketentuan Warga Negara Pasal 26 UUD 1945</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam Pasal 26 UUD 1945 dinyatakan bahwa yang menjadi warga negara ialah <strong>orang-orang bangsa Indonesia asli dan orang-orang bangsa lain yang disahkan dengan undang-undang sebagai warga negara</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena rumusan konstitusi memberikan definisi legal yang inklusif bagi status kewarganegaraan Indonesia. Orang bangsa lain yang telah berasimilasi dan memenuhi persyaratan undang-undang diakui setara sebagai warga negara. Hal ini menjamin kepastian status hukum setiap penduduk.</p><p>Jawaban tepat: <strong>D. Orang bangsa Indonesia asli dan bangsa lain yang disahkan undang-undang</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Wisatawan asing berkunjung dengan visa izin tinggal terbatas dan bukan warga negara.</li><li><strong>B</strong>: Pasukan perdamaian asing bertugas berdasarkan mandat internasional tanpa hak kewarganegaraan.</li><li><strong>C</strong>: Pejabat korporasi pemegang paspor asing tetap berstatus warga negara asing berizin kerja.</li><li><strong>E</strong>: Warga negara asing di Asia Tenggara terikat pada status kedaulatan negaranya masing-masing.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 33) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Dalam Pasal 26 UUD 1945 dinyatakan • bangsa Indonesia asli</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Prinsip kesetaraan kedudukan warga negara dalam Pasal 27 ayat (1) UUD 1945 menegaskan...", "options": ["Bersamaan kedudukan dalam hukum dan pemerintahan tanpa kecualinya", "Hak istimewa bebas dari penuntutan pidana bagi keluarga bangsawan", "Kewajiban kepatuhan hukum ditujukan bagi masyarakat golongan bawah", "Keringanan sanksi peradilan bagi pejabat pemegang kekuasaan anggaran", "Pemberian imunitas hukum mutlak kepada pengurus perserikatan niaga"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Persamaan Kedudukan dalam Hukum dan Pemerintahan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pasal 27 ayat (1) UUD 1945 menyatakan <strong>segala warga negara bersamaan kedudukannya di dalam hukum dan pemerintahan dan wajib menjunjung hukum dan pemerintahan itu dengan tidak ada kecualinya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena prinsip persamaan di hadapan hukum (equality before the law) adalah pilar mutlak negara hukum yang demokratis. Tidak ada seorang pun warga negara yang kebal hukum, baik presiden, menteri, maupun rakyat biasa. Kesetaraan ini menjamin keadilan peradilan bagi seluruh rakyat.</p><p>Jawaban tepat: <strong>A. Bersamaan kedudukan dalam hukum dan pemerintahan tanpa kecualinya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Hak istimewa feodal bertentangan dengan prinsip negara hukum dan kedaulatan rakyat.</li><li><strong>C</strong>: Hukum mengikat seluruh strata sosial tanpa pembedaan kelas atas maupun kelas bawah.</li><li><strong>D</strong>: Pejabat anggaran yang melanggar hukum justru dikenakan sanksi pemberatan pidana.</li><li><strong>E</strong>: Pengurus perserikatan bisnis tunduk pada regulasi perundang-undangan hukum yang sama.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 33) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pasal 27 ayat (1) • segala warga negara bersamaan kedudukannya di dalam hukum dan pemerintahan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Empat pilar yang membentuk Kepulauan Nusantara sebagai satu kesatuan politik adalah...", "options": ["Kesatuan armada kapal, kesatuan mata uang, kesatuan pos, dan kesatuan bea", "Kesatuan tarif pajak, kesatuan dividen saham, kesatuan pasar, dan modal", "Kesatuan dialek daerah, kesatuan adat istiadat, kesatuan marga, dan klan", "Kesatuan partai politik, kesatuan kabinet dinas, kesatuan serikat, dan pers", "Kesatuan wilayah, kesatuan bangsa, kesatuan filsafat ideologi, dan hukum"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Empat Pilar Kesatuan Politik Nusantara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kepulauan Nusantara sebagai satu kesatuan politik meliputi: <strong>satu kesatuan wilayah, kesatuan bangsa, kesatuan filsafat dan ideologi (Pancasila), dan kesatuan hukum</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena kesatuan politik menuntut integrasi utuh pada empat bidang mendasar: teritorial wilayah tanah air, identitas kebangsaan, pandangan hidup Pancasila, dan tertib hukum nasional. Keterpaduan empat pilar ini mencegah ancaman disintegrasi bangsa. Inilah fondasi kedaulatan NKRI.</p><p>Jawaban tepat: <strong>E. Kesatuan wilayah, kesatuan bangsa, kesatuan filsafat ideologi, dan hukum</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Armada kapal dan pos tarif merupakan instrumen teknis logistik dan perhubungan laut.</li><li><strong>B</strong>: Tarif dan modal perbankan adalah instrumen gatra kesatuan ekonomi nasional.</li><li><strong>C</strong>: Pancasila menghormati kemajemukan adat dan bahasa daerah dalam bingkai Bhinneka Tunggal Ika.</li><li><strong>D</strong>: Sistem politik Indonesia menganut multipartai dan kebebasan pers yang bertanggung jawab.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 33) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Kepulauan Nusantara sebagai satu kesatuan politik meliputi • kesatuan wilayah, kesatuan bangsa</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan Pasal 33 ayat (1) UUD 1945, sistem perekonomian nasional disusun sebagai...", "options": ["Persaingan bebas antarpemilik modal untuk menguasai pasar komoditas", "Monopoli usaha oleh keluarga pimpinan satuan kerja kementerian", "Usaha bersama berlandaskan asas kekeluargaan demi kemakmuran bersama", "Penyerahan tata kelola niaga sepenuhnya kepada mekanisme pasar dunia", "Sistem ekonomi komando sentralistik yang melarang koperasi mandiri"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pasal 33 Ayat (1) Asas Kekeluargaan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pasal 33 ayat (1) UUD 1945 menegaskan <strong>Perekonomian disusun sebagai usaha bersama berdasarkan atas asas kekeluargaan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena demokrasi ekonomi Indonesia mengedepankan asas kekeluargaan dan gotong royong, di mana badan usaha yang paling cocok adalah koperasi. Perekonomian tidak boleh dibangun atas dasar pertarungan bebas yang mematikan kaum lemah. Kesejahteraan bersama menjadi tujuan utama sistem ekonomi.</p><p>Jawaban tepat: <strong>C. Usaha bersama berlandaskan asas kekeluargaan demi kemakmuran bersama</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Persaingan bebas liberal memicu eksploitasi manusia dan dilarang oleh konstitusi ekonomi.</li><li><strong>B</strong>: Asas kekeluargaan bermakna persaudaraan kebangsaan, bukan praktik nepotisme dinasti keluarga.</li><li><strong>D</strong>: Mekanisme pasar bebas murni tanpa campur tangan negara bertentangan dengan keadilan sosial.</li><li><strong>E</strong>: Ekonomi komando kaku meniadakan inisiatif rakyat dan bertentangan dengan demokrasi ekonomi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 33) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Perekonomian disusun sebagai usaha bersama • berdasarkan atas asas kekeluargaan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Ketentuan mengenai cabang produksi yang menguasai hajat hidup orang banyak diatur dalam...", "options": ["Pasal 27 ayat (2) mengenai hak atas pekerjaan dan penghidupan layak", "Pasal 33 ayat (2) bahwa cabang produksi vital dikuasai oleh negara", "Pasal 34 ayat (1) mengenai pemeliharaan fakir miskin oleh negara", "Pasal 23 ayat (1) mengenai anggaran pendapatan dan belanja negara", "Pasal 31 ayat (1) mengenai hak setiap warga memperoleh pendidikan"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pasal 33 Ayat (2) Penguasaan Cabang Produksi Vital</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pasal 33 ayat (2) UUD 1945 berbunyi <strong>Cabang-cabang produksi yang penting bagi negara dan yang menguasai hajat hidup orang banyak dikuasai oleh negara</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena sektor vital seperti air bersih, listrik, dan energi menyangkut kebutuhan mendasar jutaan rakyat. Jika sektor ini diserahkan kepada penguasaan perorangan atau swasta murni, rakyat dapat diperas melalui penentuan harga semena-mena. Oleh karena itu, negara wajib mengendalikannya demi kemaslahatan umum.</p><p>Jawaban tepat: <strong>B. Pasal 33 ayat (2) bahwa cabang produksi vital dikuasai oleh negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pasal 27 ayat (2) mengatur hak warga negara atas pekerjaan dan penghidupan yang layak.</li><li><strong>C</strong>: Pasal 34 ayat (1) mengatur kewajiban negara memelihara fakir miskin dan anak terlantar.</li><li><strong>D</strong>: Pasal 23 ayat (1) mengatur penetapan APBN secara terbuka dan bertanggung jawab.</li><li><strong>E</strong>: Pasal 31 ayat (1) mengatur hak warga negara untuk mendapatkan pengajaran pendidikan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 33) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Cabang-cabang produksi yang penting bagi negara • menguasai hajat hidup orang banyak dikuasai oleh negara</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Tujuan utama penguasaan bumi, air, dan kekayaan alam oleh negara pada Pasal 33 ayat (3) adalah...", "options": ["Dipergunakan untuk sebesar-besar kemakmuran dan kesejahteraan rakyat", "Memperkaya para pejabat pengelola badan usaha milik negara secara pribadi", "Dijadikan agunan jaminan pembayaran cicilan utang luar negeri", "Diserahkan konsesinya secara monopoli kepada korporasi transnasional", "Ditutup akses pemanfaatannya dari seluruh aktivitas kegiatan industri"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pasal 33 Ayat (3) Penguasaan Kekayaan Alam</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Bumi dan air dan kekayaan alam yang terkandung di dalamnya dikuasai oleh negara dan <strong>dipergunakan untuk sebesar-besar kemakmuran rakyat</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena hak penguasaan oleh negara bermakna mandat pengelolaan publik, bukan kepemilikan privat penguasa. Seluruh manfaat ekonomi dari sumber daya alam tambang, hutan, dan laut harus dikembalikan kepada rakyat melalui pembangunan dan pelayanan publik. Kemakmuran rakyat adalah tujuan mutlaknya.</p><p>Jawaban tepat: <strong>A. Dipergunakan untuk sebesar-besar kemakmuran dan kesejahteraan rakyat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kekayaan alam bukan komoditas untuk memperkaya pejabat atau kroni kekuasaan.</li><li><strong>C</strong>: Kedaulatan atas kekayaan alam tidak boleh digadaikan sebagai jaminan utang luar negeri.</li><li><strong>D</strong>: Konsesi monopoli asing bertentangan dengan kemandirian ekonomi nasional bangsa.</li><li><strong>E</strong>: Pemanfaatan kekayaan alam dilakukan secara terencana dengan prinsip kelestarian lingkungan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 33) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Bumi dan air dan kekayaan alam yang terkandung • dipergunakan untuk sebesar-besar kemakmuran rakyat</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Prinsip-prinsip penyelenggaraan perekonomian nasional berdasarkan Pasal 33 ayat (4) mencakup...", "options": ["Persaingan bebas, monopoli modal, privatisasi total, dan eksploitasi", "Deregulasian perbankan tanpa pengawasan, rente, dan inflasi bebas", "Sentralisme anggaran, subsidi nol, proteksionisme dogmatis, dan isolasi", "Kebersamaan, efisiensi berkeadilan, berkelanjutan, dan kemandirian", "Oligarki kepemilikan aset, kartel harga, dan ketergantungan impor"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Prinsip Demokrasi Ekonomi Pasal 33 Ayat (4)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Perekonomian nasional diselenggarakan atas dasar demokrasi ekonomi dengan prinsip <strong>kebersamaan, efisiensi berkeadilan, berkelanjutan, berwawasan lingkungan, kemandirian, serta menjaga keseimbangan kemajuan dan kesatuan ekonomi nasional</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena Pasal 33 ayat (4) hasil amandemen memberikan pedoman komprehensif bagi pembangunan ekonomi modern yang tetap setia pada nilai Pancasila. Efisiensi harus dipadukan dengan keadilan dan kelestarian alam demi generasi mendatang. Prinsip kemandirian menjaga ketahanan bangsa di era global.</p><p>Jawaban tepat: <strong>D. Kebersamaan, efisiensi berkeadilan, berkelanjutan, dan kemandirian</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Eksploitasi dan monopoli modal merusak sendi keadilan sosial yang diamanatkan konstitusi.</li><li><strong>B</strong>: Deregulasi tanpa pengawasan memicu krisis keuangan dan spekulasi yang merugikan publik.</li><li><strong>C</strong>: Isolasi perdagangan dan sentralisme kaku menghambat dinamika kemajuan ekonomi daerah.</li><li><strong>E</strong>: Oligarki dan kartel adalah musuh utama dari sistem demokrasi ekonomi Pancasila.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 33) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>prinsip kebersamaan, efisiensi, berkeadilan, berkelanjutan • berwawasan lingkungan, kemandirian</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Makna hakiki Kepulauan Nusantara sebagai satu kesatuan ekonomi bagi seluruh rakyat adalah...", "options": ["Pendapatan tambang daerah dinikmati eksklusif warga kelahiran setempat", "Pemberian hak monopoli pelayaran niaga antarpulau kepada serikat tertentu", "Kewajiban setiap provinsi menerbitkan mata uang dan tarif pabean sendiri", "Pemusatan seluruh kantor operasional perusahaan di kawasan pulau Jawa", "Kekayaan wilayah nusantara adalah modal dan milik bersama seluruh bangsa"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Makna Kesatuan Ekonomi Nusantara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kepulauan Nusantara sebagai kesatuan ekonomi berarti <strong>bahwa kekayaan wilayah Nusantara adalah modal dan milik bersama bangsa, dan tingkat perkembangan ekonomi harus serasi dan seimbang di seluruh Indonesia</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena seluruh potensi sumber daya alam di berbagai pulau merupakan kekayaan nasional yang diperuntukkan bagi kesejahteraan bersama. Tidak boleh ada ketimpangan mencolok di mana satu daerah kaya raya sementara daerah lain terbelakang. Pemerataan pembangunan adalah wujud keadilan sosial.</p><p>Jawaban tepat: <strong>E. Kekayaan wilayah nusantara adalah modal dan milik bersama seluruh bangsa</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Paham kedaerahan sempit bertentangan dengan prinsip kekayaan nusantara milik bersama bangsa.</li><li><strong>B</strong>: Monopoli pelayaran bertentangan dengan konektivitas logistik kelancaran niaga antarpulau.</li><li><strong>C</strong>: Mata uang tunggal rupiah berlaku di seluruh wilayah kedaulatan Negara Kesatuan Republik Indonesia.</li><li><strong>D</strong>: Pembangunan ekonomi didorong merata di luar pulau Jawa demi memutus kesenjangan kawasan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 33) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>kekayaan wilayah Nusantara adalah modal dan • milik bersama bangsa</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C3 - Aplikasi", "question": "Kebijakan fiskal kementerian keuangan yang mencerminkan kesatuan ekonomi serasi dan seimbang adalah...", "options": ["Memusatkan seluruh belanja modal infrastruktur eksklusif pada kota metropolitan", "Mengalokasikan dana transfer daerah secara adil untuk kawasan terluar", "Menghapus alokasi dana desa bagi masyarakat yang bermukim di perbatasan", "Mengenakan tarif pajak daerah yang memberatkan peredaran sembako pangan", "Menahan penyaluran bantuan operasional sekolah bagi kepulauan terpencil"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pemerataan dan Keseimbangan Antarwilayah</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kesatuan ekonomi menuntut bahwa <strong>tingkat perkembangan ekonomi harus serasi dan seimbang di seluruh Indonesia</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena Transfer ke Daerah dan Dana Desa (TKDD) merupakan instrumen fiskal strategis untuk mengatasi ketimpangan antardaerah. Pengalokasian dana secara berkeadilan memungkinkan wilayah tertinggal dan terluar membangun infrastruktur dan pelayanan dasar yang setara. Hal ini memperkokoh persatuan ekonomi bangsa.</p><p>Jawaban tepat: <strong>B. Mengalokasikan dana transfer daerah secara adil untuk kawasan terluar</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Memusatkan belanja pada kota besar memperlebar jurang disparitas kesenjangan antardaerah.</li><li><strong>C</strong>: Dana desa wajib disalurkan secara merata untuk menggerakkan perekonomian desa terluar.</li><li><strong>D</strong>: Pajak sembako yang tinggi membebani daya beli masyarakat berpenghasilan rendah.</li><li><strong>E</strong>: BOS wajib disalurkan tepat waktu ke daerah terpencil demi pemerataan kualitas pendidikan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 33) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>tingkat perkembangan ekonomi harus serasi dan • seimbang di seluruh Indonesia</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Amanat konstitusional Pasal 33 UUD 1945 mewajibkan pemerintah mewujudkan kesejahteraan umum berupa...", "options": ["Perlindungan kepemilikan modal konglomerasi bisnis perbankan", "Kewajiban pengenaan tarif bea masuk tinggi atas barang teknologi", "Keadilan sosial bagi seluruh rakyat dalam tatanan perekonomian", "Pemberian dana talangan tanpa batas kepada korporasi yang merugi", "Pengalihan fungsi kawasan cagar alam menjadi perkebunan kelapa"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kewajiban Pemerintah Mewujudkan Keadilan Sosial</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pemerintah negara Indonesia berkewajiban mewujudkan kesejahteraan umum, yaitu <strong>mewujudkan suatu keadilan sosial bagi seluruh rakyat Indonesia di bidang ekonomi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena perekonomian disusun bukan untuk memakmurkan segelintir orang melainkan untuk kemakmuran seluruh rakyat secara adil. Kebijakan moneter, fiskal, dan perdagangan harus bermuara pada penciptaan lapangan kerja dan pengentasan kemiskinan. Keadilan sosial adalah roh utama Pasal 33 UUD 1945.</p><p>Jawaban tepat: <strong>C. Keadilan sosial bagi seluruh rakyat dalam tatanan perekonomian</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Perlindungan eksklusif konglomerasi bertentangan dengan keadilan ekonomi Pancasila.</li><li><strong>B</strong>: Tarif bea masuk barang teknologi diatur proporsional demi mendorong alih teknologi nasional.</li><li><strong>D</strong>: Dana talangan sembarangan melanggar prinsip kehati-hatian tata kelola keuangan negara.</li><li><strong>E</strong>: Pengrusakan kawasan cagar alam melanggar prinsip ekonomi berkelanjutan berwawasan lingkungan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 33) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pemerintah negara Indonesia berkewajiban mewujudkan kesejahteraan • umum, yaitu mewujudkan suatu keadilan sosial</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan amanat Pasal 32 UUD 1945, kewajiban negara di bidang kebudayaan adalah...", "options": ["Menyeragamkan seluruh dialek bahasa daerah menjadi satu bahasa baku", "Mewajibkan adopsi kebudayaan asing tanpa penyaringan nilai etika", "Menghapus seluruh upacara adat istiadat yang berusia ratusan tahun", "Membatasi pementasan kesenian tradisional saat peringatan hari kemerdekaan", "Memajukan kebudayaan nasional serta memelihara bahasa daerah"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Ketentuan Kebudayaan Nasional Pasal 32 UUD 1945</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pasal 32 UUD 1945 menegaskan bahwa <strong>negara/pemerintah memajukan kebudayaan nasional, menghormati, dan memelihara bahasa daerah sebagai kekayaan budaya nasional</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena kebudayaan daerah merupakan puncak-puncak kebudayaan nasional yang wajib dilindungi dan dikembangkan kelestariannya. Bahasa daerah diakui sebagai warisan luhur yang memperkaya khazanah peradaban bangsa. Negara hadir menjamin kebebasan masyarakat dalam memelihara dan mengembangkan nilai budayanya.</p><p>Jawaban tepat: <strong>E. Memajukan kebudayaan nasional serta memelihara bahasa daerah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Bahasa daerah dipelihara kelestariannya, bukan diseragamkan secara paksa.</li><li><strong>B</strong>: Kebudayaan asing disaring secara selektif agar selaras dengan kepribadian Pancasila.</li><li><strong>C</strong>: Upacara adat yang luhur dilindungi sebagai warisan kekayaan budaya nusantara.</li><li><strong>D</strong>: Kesenian daerah didorong untuk terus berkembang aktif di tengah masyarakat setiap saat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 34) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Dalam Pasal 32 UUD 1945 disebutkan bahwa • memajukan kebudayaan nasional</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Syarat konstitusional penerimaan unsur kebudayaan asing ke dalam kebudayaan nasional adalah...", "options": ["Mendapat persetujuan dari dewan komisaris korporasi penanaman modal", "Tidak bertentangan Pancasila serta mengangkat harkat martabat manusia", "Berasal dari negara industri maju yang memiliki teknologi militer", "Mampu menggantikan seluruh fungsi kearifan lokal masyarakat adat", "Diterapkan secara menyeluruh tanpa melalui proses penyesuaian nilai"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Syarat Penerimaan Kebudayaan Asing</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Penerimaan unsur kebudayaan asing dibenarkan dengan ketentuan <strong>tidak bertentangan dengan nilai-nilai Pancasila dan dapat meningkatkan harkat dan martabat manusia Indonesia</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena Indonesia bersikap terbuka terhadap pengaruh global yang positif bagi kemajuan ilmu dan peradaban. Namun, setiap unsur asing wajib disaring agar tidak merusak moralitas dan jati diri kebangsaan. Pengaruh tersebut harus memperkaya, bukan mengikis peradaban nusantara.</p><p>Jawaban tepat: <strong>B. Tidak bertentangan Pancasila serta mengangkat harkat martabat manusia</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penyaringan kebudayaan adalah wilayah ketahanan peradaban, bukan urusan komisaris modal.</li><li><strong>C</strong>: Asal negara maju bukan jaminan kesesuaian nilai budaya asing dengan moral bangsa.</li><li><strong>D</strong>: Kebudayaan asing tidak boleh memusnahkan eksistensi kearifan lokal yang hidup.</li><li><strong>E</strong>: Penyerapan budaya tanpa filter adaptasi melahirkan krisis identitas dan degradasi moral.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 34) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>tidak bertentangan dengan nilai-nilai Pancasila • meningkatkan harkat dan martabat manusia</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Prinsip utama semboyan Bhinneka Tunggal Ika dalam memandang keberagaman suku dan adat adalah...", "options": ["Mempertentangkan perbedaan demi memicu kompetisi dominasi antarsuku", "Menghapuskan adat minoritas agar tercipta keseragaman tradisi tunggal", "Saling melengkapi dalam keanekaragaman sebagai khazanah kebudayaan bangsa", "Memisahkan pemukiman penduduk berdasarkan kesamaan suku asal daerah", "Melarang pernikahan antarsuku demi menjaga kemurnian garis keturunan"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Semboyan Bhinneka Tunggal Ika</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Bhinneka Tunggal Ika menuntut bahwa <strong>keanekaragaman tidak boleh dipertentangkan, melainkan saling melengkapi dan merupakan khazanah kebudayaan bangsa</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena semboyan Bhinneka Tunggal Ika mengajarkan persatuan dalam perbedaan. Kemajemukan suku, bahasa, dan adat istiadat bukanlah kelemahan atau sumber konflik, melainkan kekayaan peradaban yang memperkuat bangunan kebangsaan. Harmoni ini dijaga melalui sikap saling menghormati.</p><p>Jawaban tepat: <strong>C. Saling melengkapi dalam keanekaragaman sebagai khazanah kebudayaan bangsa</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Mempertentangkan perbedaan memicu perpecahan yang bertentangan dengan semboyan negara.</li><li><strong>B</strong>: Peleburan paksa adat minoritas melanggar prinsip pengakuan keragaman budaya nusantara.</li><li><strong>D</strong>: Segregasi pemukiman berbau politik apartheid dan merusak integrasi sosiologis bangsa.</li><li><strong>E</strong>: Asimilasi perkawinan antarsuku justru mempererat tali persaudaraan kebangsaan Indonesia.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 34) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menjunjung tinggi semboyan “Bhinneka Tunggal • Ika”</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Ketentuan konstitusional mengenai hak dan kewajiban bela negara ditegaskan dalam...", "options": ["Pasal 26 ayat (2) mengenai syarat naturalisasi warga negara asing", "Pasal 29 ayat (2) mengenai jaminan kemerdekaan beribadah penduduk", "Pasal 28E ayat (1) mengenai kebebasan memeluk agama dan beribadah", "Pasal 27 ayat (3) bahwa setiap warga berhak dan wajib membela negara", "Pasal 31 ayat (2) mengenai kewajiban mengikuti pendidikan dasar"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pasal 27 Ayat (3) Pembelaan Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pasal 27 ayat (3) UUD 1945 menegaskan bahwa <strong>setiap warga negara berhak dan wajib ikut serta dalam upaya pembelaan negara</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena membela kedaulatan negara bukan sekadar kewajiban hukum, melainkan juga hak kehormatan bagi setiap warga negara. Bela negara dapat diwujudkan melalui pengabdian profesi, karya nyata, hingga kesiapsiagaan menghadapi ancaman kedaulatan. Amanat pasal ini berlaku mengikat segenap warga.</p><p>Jawaban tepat: <strong>D. Pasal 27 ayat (3) bahwa setiap warga berhak dan wajib membela negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pasal 26 ayat (2) mengatur penduduk yang terdiri dari WNI dan orang asing yang bertempat tinggal.</li><li><strong>B</strong>: Pasal 29 ayat (2) mengatur jaminan negara terhadap kemerdekaan beribadah tiap penduduk.</li><li><strong>C</strong>: Pasal 28E ayat (1) mengatur kebebasan memeluk agama dan memilih pendidikan pengajaran.</li><li><strong>E</strong>: Pasal 31 ayat (2) mengatur kewajiban warga negara mengikuti pendidikan dasar pemerintah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 34) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Dalam pasal 27 ayat (3) UUD 1945 menegaskan • berhak dan wajib ikut serta dalam upaya pembelaan negara</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan Pasal 30 ayat (1) UUD 1945, keikutsertaan dalam usaha pertahanan dan keamanan negara adalah...", "options": ["Hak dan kewajiban yang melekat pada tiap-tiap warga negara Indonesia", "Kewajiban khusus yang dibebankan kepada anggota korps militer", "Tugas sukarela yang diserahkan pelaksanaannya kepada relawan pemuda", "Kewenangan eksklusif para pejabat pimpinan satuan kerja kepolisian", "Kegiatan berbayar yang diselenggarakan oleh badan usaha keamanan swasta"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pasal 30 Ayat (1) Usaha Pertahanan Keamanan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pasal 30 ayat (1) UUD 1945 menyatakan <strong>tiap-tiap warga negara berhak dan wajib ikut serta dalam usaha pertahanan dan keamanan negara</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena pertahanan negara menganut doktrin semesta di mana keamanan tanah air merupakan tanggung jawab bersama seluruh rakyat. Rakyat bukan penonton pasif, melainkan benteng ketahanan nasional yang kokoh. Hak dan kewajiban ini mengikat seluruh warga negara tanpa kecuali.</p><p>Jawaban tepat: <strong>A. Hak dan kewajiban yang melekat pada tiap-tiap warga negara Indonesia</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Militer adalah kekuatan utama, namun kewajiban pertahanan melekat pada segenap warga.</li><li><strong>C</strong>: Pertahanan dan keamanan adalah kewajiban konstitusional, bukan sekadar sukarela situasional.</li><li><strong>D</strong>: Kepolisian memelihara kamtibmas, tetapi usaha keamanan melibatkan seluruh komponen bangsa.</li><li><strong>E</strong>: Keamanan negara adalah fungsi kedaulatan publik, bukan jasa komersial berbayar swasta.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 34) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pasal 30 ayat (1) UUD 1945 dinyatakan, bahwa tiap-tiap warga • usaha pertahanan dan keamanan negara</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Komposisi kekuatan dalam Sishankamrata berdasarkan Pasal 30 ayat (2) UUD 1945 menempatkan...", "options": ["Rakyat sebagai kekuatan utama dan militer asing sebagai kekuatan pembantu", "TNI dan Polri sebagai kekuatan utama dan rakyat sebagai kekuatan pendukung", "Perusahaan keamanan swasta sebagai kekuatan utama dalam penindakan hukum", "Organisasi kemasyarakatan daerah sebagai pemegang komando pertahanan pulau", "Aparatur sipil birokrasi kementerian sebagai kesatuan tempur terdepan"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pasal 30 Ayat (2) Sishankamrata</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Usaha hankamneg dilaksanakan melalui sistem pertahanan dan keamanan rakyat semesta <strong>(Sishankamrata) oleh TNI dan Polri sebagai kekuatan utama dan rakyat sebagai kekuatan pendukung</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena TNI memegang fungsi pertahanan negara dan Polri memegang fungsi keamanan ketertiban masyarakat sebagai garda terdepan profesional. Seluruh rakyat dan sumber daya nasional disiapkan sebagai kekuatan pendukung yang siap dimobilisasi demi kedaulatan NKRI. Inilah doktrin Sishankamrata.</p><p>Jawaban tepat: <strong>B. TNI dan Polri sebagai kekuatan utama dan rakyat sebagai kekuatan pendukung</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Militer asing tidak memiliki tempat dalam sistem pertahanan kedaulatan mandiri Indonesia.</li><li><strong>C</strong>: Penggunaan senjata dan komando pertahanan berada di bawah institusi sah negara.</li><li><strong>D</strong>: Komando pertahanan berada di bawah Panglima TNI atas arahan Presiden Republik Indonesia.</li><li><strong>E</strong>: Aparatur birokrasi bertugas pada fungsi pelayanan sipil dan dukungan logistik pertahanan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 34) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>sistem pertahanan dan keamanan rakyat semesta • TNI dan Polri sebagai kekuatan utama</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Tujuan pertahanan negara dalam alinea keempat Pembukaan UUD 1945 dirumuskan dengan kalimat...", "options": ["Mengadakan ekspansi penaklukan pulau-pulau baru di kawasan samudra", "Mewajibkan dinas militer aktif kepada seluruh pelajar sekolah menengah", "Menyita seluruh aset kepemilikan modal asing tanpa ganti kerugian", "Membentuk persekutuan militer penyerang bersama negara kawasan eropa", "Melindungi segenap bangsa Indonesia dan seluruh tumpah darah Indonesia"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuan Pertahanan Negara dalam Pembukaan UUD</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam alinea keempat Pembukaan UUD 1945 dinyatakan tujuan negara yaitu <strong>melindungi segenap bangsa Indonesia dan seluruh tumpah darah Indonesia</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena tugas pertahanan negara pada hakikatnya bersifat defensif aktif, yaitu menjaga kedaulatan tanah air dan keselamatan rakyat dari setiap ancaman. Indonesia tidak pernah berniat menyerang atau menjajah bangsa lain. Perlindungan segenap tumpah darah adalah mandat konstitusi.</p><p>Jawaban tepat: <strong>E. Melindungi segenap bangsa Indonesia dan seluruh tumpah darah Indonesia</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Indonesia menghormati integritas wilayah negara tetangga dan menentang segala agresi militer.</li><li><strong>B</strong>: Wajib militer bagi pelajar bukan amanat langsung teks Pembukaan Undang-Undang Dasar.</li><li><strong>C</strong>: Nasionalisasi aset dilakukan sesuai hukum perundang-undangan dan perjanjian internasional.</li><li><strong>D</strong>: Indonesia setia pada prinsip politik bebas aktif dan tidak ikut serta pakta militer agresor.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 34) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>melindungi segenap bangsa Indonesia dan • seluruh tumpah darah Indonesia</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C3 - Aplikasi", "question": "Sikap yang dilarang dalam pengelolaan keragaman sosial budaya bangsa adalah...", "options": ["Mempelajari tarian tradisional daerah lain dalam festival kebudayaan", "Mempromosikan produk kerajinan tangan lokal ke pameran internasional", "Mempertentangkan perbedaan sifat, bentuk, dan wujud kebudayaan", "Mengadakan pertukaran pelajar antardaerah kepulauan nusantara", "Mendokumentasikan kosa kata bahasa daerah yang terancam punah"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Larangan dalam Pengelolaan Keragaman Budaya</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam hubungan kebudayaan, kita <strong>tidak boleh mempertentangkan perbedaan sifat, bentuk, dan wujud kebudayaan</strong> yang beraneka ragam.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena mempertentangkan tradisi antardaerah dapat menyulut gesekan sosial dan disintegrasi bangsa. Perbedaan harus disikapi secara dewasa sebagai ragam keindahan mozaik budaya nusantara. Sikap toleran dan saling mengapresiasi menjadi kunci kerukunan sosial.</p><p>Jawaban tepat: <strong>C. Mempertentangkan perbedaan sifat, bentuk, dan wujud kebudayaan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Mempelajari tari tradisional daerah lain merupakan wujud apresiasi budaya yang sangat positif.</li><li><strong>B</strong>: Promosi kerajinan daerah mengangkat perekonomian dan martabat peradaban bangsa di dunia.</li><li><strong>D</strong>: Pertukaran pelajar mempererat tali persaudaraan kebangsaan di kalangan generasi penerus.</li><li><strong>E</strong>: Dokumentasi bahasa daerah adalah upaya pelestarian warisan budaya yang diamanatkan konstitusi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 34) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>tidak boleh mempertentangkan perbedaan sifat, bentuk, dan • wujud kebudayaan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Kedudukan bahasa daerah dalam sistem sosial budaya Indonesia sebagaimana diatur konstitusi adalah...", "options": ["Penghambat kelancaran komunikasi perdagangan modern di kota besar", "Dialek kuno yang penggunaannya dibatasi pada lingkungan pedalaman", "Bahasa pengantar wajib dalam persidangan lembaga peradilan umum", "Kekayaan budaya nasional yang dihormati dan dipelihara kelestariannya", "Unsur kebudayaan yang wajib dihapus demi penyeragaman bahasa resmi"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kedudukan Bahasa Daerah dalam Konstitusi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Bahasa daerah berkedudukan sebagai <strong>kekayaan budaya nasional yang dihormati dan dipelihara oleh negara</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena ratusan bahasa daerah di nusantara memuat kearifan lokal, etika moral, dan sejarah peradaban suku bangsa Indonesia. Keberadaannya berdampingan harmonis dengan Bahasa Indonesia sebagai bahasa persatuan. Pelestariannya dijamin secara tegas dalam Pasal 32 ayat (2) UUD 1945.</p><p>Jawaban tepat: <strong>D. Kekayaan budaya nasional yang dihormati dan dipelihara kelestariannya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Bahasa daerah tidak menghambat perniagaan, melainkan memperkaya interaksi sosiologis.</li><li><strong>B</strong>: Bahasa daerah hidup di perkotaan maupun pedesaan dan tidak dibatasi penggunaannya.</li><li><strong>C</strong>: Persidangan peradilan resmi menggunakan Bahasa Indonesia sebagai bahasa hukum negara.</li><li><strong>E</strong>: Penghapusan bahasa daerah bertentangan secara frontal dengan amanat konstitusi UUD 1945.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 34) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menghormati, dan memelihara bahasa daerah • kekayaan budaya nasional</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Makna hakiki kepulauan Nusantara sebagai satu kesatuan sosial dan budaya adalah bahwa...", "options": ["Ragam budaya adalah kekayaan bersama yang harus dikembangkan seimbang", "Setiap warga wajib meninggalkan adat leluhur demi budaya metropolitan", "Kebudayaan suku mayoritas berhak menjadi tolak ukur standar budaya tunggal", "Pemerintah pusat berhak melarang tradisi kesenian yang bersifat religius", "Kegiatan festival kebudayaan daerah harus dipusatkan di ibu kota negara"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Wawasan Nusantara Bidang Sosial Budaya</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Corak ragam budaya menggambarkan <strong>kekayaan budaya bangsa yang harus dikembangkan untuk dapat dinikmati bersama</strong> dalam wadah Wawasan Nusantara.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena kesatuan sosial budaya menegaskan bahwa peradaban bangsa pada hakikatnya adalah satu, sedangkan corak ragamnya mencerminkan kekayaan budaya yang dinikmati seluruh rakyat. Kebudayaan daerah menjadi sumber inspirasi pembangunan nasional. Nilai ini memperkokoh ketahanan budaya bangsa.</p><p>Jawaban tepat: <strong>A. Ragam budaya adalah kekayaan bersama yang harus dikembangkan seimbang</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Masyarakat tidak boleh dipaksa meninggalkan identitas adat leluhur yang berkepribadian luhur.</li><li><strong>C</strong>: Tidak ada dominasi satu suku atas suku lain dalam tatanan kebudayaan Bhinneka Tunggal Ika.</li><li><strong>D</strong>: Tradisi bernilai luhur dan religius dilindungi sepenuhnya oleh negara sebagai khazanah.</li><li><strong>E</strong>: Festival budaya didorong berkembang aktif di setiap daerah untuk menghidupkan pariwisata lokal.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 34) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Wawasan Nusantara • Corak ragam budaya menggambarkan kekayaan budaya bangsa</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Prinsip dasar kesatuan pertahanan dan keamanan nasional menegaskan bahwa ancaman terhadap satu pulau...", "options": ["Merupakan tanggung jawab mandiri aparat pemerintah daerah setempat", "Dapat diselesaikan dengan menyerahkan pengelolaan pulau ke pihak asing", "Pada hakikatnya merupakan ancaman terhadap seluruh bangsa dan negara", "Tidak berdampak bagi kestabilan keamanan wilayah ibu kota negara", "Perlu ditangani apabila menimbulkan kerugian pendapatan kas daerah"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Hakikat Kesatuan Pertahanan Keamanan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kesatuan pertahanan keamanan berarti bahwa <strong>ancaman terhadap satu pulau atau satu daerah pada hakikatnya merupakan ancaman terhadap seluruh bangsa dan negara</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena kedaulatan wilayah Indonesia adalah satu kesatuan utuh yang tidak dapat dibagi-bagi. Gangguan keamanan di perbatasan terluar dirasakan sebagai ancaman nyata bagi kedaulatan segenap rakyat Indonesia. Prinsip solidaritas pertahanan ini menyatukan segenap komponen bangsa.</p><p>Jawaban tepat: <strong>C. Pada hakikatnya merupakan ancaman terhadap seluruh bangsa dan negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pertahanan kedaulatan adalah urusan absolut pemerintah pusat, bukan daerah semata.</li><li><strong>B</strong>: Sejengkal wilayah tanah air haram diserahkan kepada penguasaan militer asing.</li><li><strong>D</strong>: Kestabilan nasional saling terkait erat antarseluruh gugusan pulau di nusantara.</li><li><strong>E</strong>: Ancaman pertahanan diukur dari kedaulatan wilayah, bukan semata hitungan kas fiskal.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 35) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>ancaman terhadap satu pulau atau satu daerah pada hakikatnya • ancaman terhadap seluruh bangsa dan negara</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Penegasan Pasal 1 ayat (3) UUD 1945 bahwa Indonesia adalah negara hukum (Rechtsstaat) berarti negara...", "options": ["Menjadikan titah penguasa sebagai satu-satunya rujukan kebenaran mutlak", "Menjalankan pemerintahan tanpa pengawasan dari lembaga peradilan hukum", "Memberikan kekebalan hukum penuh kepada para pejabat eksekutif negara", "Berdasar atas hukum dan tidak berdasarkan atas kekuasaan belaka", "Mengesampingkan peraturan perundang-undangan demi efisiensi birokrasi"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Indonesia adalah Negara Hukum (Rechtsstaat)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Negara Indonesia adalah <strong>negara hukum (rechtsstaat) tidak berdasarkan kekuasaan belaka (machtsstaat)</strong> sebagaimana ditegaskan dalam Pasal 1 ayat (3) UUD 1945.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena dalam negara hukum (rechtsstaat), hukum berkedudukan sebagai panglima tertinggi (supremasi hukum) yang membatasi tindakan penguasa. Setiap keputusan birokrasi wajib memiliki dasar legitimasi hukum yang sah untuk mencegah kesewenang-wenangan. Prinsip ini menjamin kepastian hukum dan keadilan rakyat.</p><p>Jawaban tepat: <strong>D. Berdasar atas hukum dan tidak berdasarkan atas kekuasaan belaka</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kekuasaan yang bersandar pada titah penguasa adalah ciri negara kekuasaan (machtsstaat).</li><li><strong>B</strong>: Peradilan yang independen merupakan syarat mutlak dalam tegaknya negara hukum.</li><li><strong>C</strong>: Tidak ada seorang pun yang kebal hukum dalam tatanan negara hukum demokratis.</li><li><strong>E</strong>: Efisiensi birokrasi wajib tetap taat asas dan berlandaskan pada koridor hukum sah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 35) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Indonesia ialah negara yang berdasar atas hukum (Rechtsstaat) • tidak berdasarkan kekuasaan belaka (Machtsstaat)</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Pasal dalam batang tubuh UUD 1945 yang secara tegas menyatakan bahwa Indonesia adalah negara hukum adalah...", "options": ["Pasal 1 ayat (3) bahwa Negara Indonesia adalah negara hukum", "Pasal 1 ayat (1) mengenai bentuk negara kesatuan republik", "Pasal 2 ayat (1) mengenai majelis permusyawaratan rakyat", "Pasal 3 ayat (1) mengenai wewenang mengubah undang-undang", "Pasal 4 ayat (2) mengenai kewenangan wakil presiden membantu"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pasal Penegasan Negara Hukum</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam <strong>Pasal 1 ayat (3) UUD 1945</strong> ditegaskan bahwa <strong>Negara Indonesia adalah negara hukum</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena rumusan ini secara resmi diadopsi ke dalam batang tubuh konstitusi pada amandemen ketiga UUD 1945. Penegasan ini memperkuat asas legalitas, pemenuhan hak asasi, peradilan bebas, dan kesetaraan warga di hadapan hukum. Kedudukannya menjadi fondasi sistem ketatanegaraan.</p><p>Jawaban tepat: <strong>A. Pasal 1 ayat (3) bahwa Negara Indonesia adalah negara hukum</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Pasal 1 ayat (1) mengatur bahwa negara Indonesia adalah negara kesatuan berbentuk republik.</li><li><strong>C</strong>: Pasal 2 ayat (1) mengatur susunan keanggotaan Majelis Permusyawaratan Rakyat.</li><li><strong>D</strong>: Pasal 3 ayat (1) mengatur kewenangan MPR untuk mengubah dan menetapkan UUD.</li><li><strong>E</strong>: Pasal 4 ayat (2) mengatur bahwa presiden dibantu oleh satu orang wakil presiden.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 35) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Dalam pasal 1 ayat (3) UUD 1945 ditegaskan • bahwa Indonesia ialah negara hukum</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan prinsip Sistem Konstitusional dalam Pasal 4 ayat (1) UUD 1945, Presiden menjalankan kekuasaan...", "options": ["Mutlak tanpa batas selama masa periode jabatan belum berakhir", "Dengan wewenang membubarkan lembaga dewan perwakilan rakyat", "Mengikuti arahan instruksi tertulis dari pimpinan koalisi partai", "Atas dasar pertimbangan subjektif para penasihat keluarga istana", "Pemerintahan berdasarkan Undang-Undang Dasar sebagai konstitusi"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Prinsip Sistem Konstitusional</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam sistem konstitusional, <strong>presiden memegang kekuasaan pemerintahan berdasarkan UUD (konstitusi)</strong> sebagaimana diatur dalam Pasal 4 ayat (1) UUD 1945.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena sistem konstitusional mengikat presiden untuk selalu tunduk pada batasan-batasan hukum dasar negara. Kekuasaan presiden tidak bersifat tak terbatas (absolut). Setiap tindakan eksekutif wajib dapat dipertanggungjawabkan secara hukum dan konstitusi.</p><p>Jawaban tepat: <strong>E. Pemerintahan berdasarkan Undang-Undang Dasar sebagai konstitusi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kekuasaan mutlak tanpa batas adalah ciri sistem monarki absolut, bukan presidensial UUD 1945.</li><li><strong>B</strong>: Pasal 7C UUD 1945 secara tegas menyatakan presiden tidak dapat membubarkan dewan parlemen.</li><li><strong>C</strong>: Presiden bertindak demi kepentingan segenap rakyat dan bukan boneka koalisi kepartaian.</li><li><strong>D</strong>: Keputusan kenegaraan diambil melalui mekanisme resmi kabinet berdasarkan ketentuan hukum.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 35) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Sistem Konstitusional • presiden memegang kekuasaan pemerintahan berdasarkan UUD</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Ketentuan mengenai pemegang kekuasaan tertinggi dalam Pasal 1 ayat (2) UUD 1945 menegaskan...", "options": ["Kedaulatan berada di tangan majelis permusyawaratan rakyat sepenuhnya", "Kedaulatan berada di tangan rakyat dan dilaksanakan menurut UUD", "Kedaulatan berada di tangan presiden sebagai panglima tertinggi angkatan", "Kedaulatan berada di tangan para hakim agung mahkamah kehakiman", "Kedaulatan berada di tangan perwakilan pimpinan lembaga perbankan"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pasal 1 Ayat (2) Kedaulatan Rakyat</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pasal 1 ayat (2) UUD 1945 menegaskan bahwa <strong>Kedaulatan berada di tangan rakyat dan dilaksanakan menurut Undang-Undang Dasar</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena rumusan ini menegaskan supremasi rakyat yang dijalankan melalui aturan main konstitusi (konstitusionalisme). Kedaulatan tidak lagi terpusat secara mutlak pada satu lembaga perwakilan melainkan dibagi dan diatur pembagian wewenangnya oleh UUD 1945. Inilah esensi demokrasi modern Indonesia.</p><p>Jawaban tepat: <strong>B. Kedaulatan berada di tangan rakyat dan dilaksanakan menurut UUD</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Rumusan 'kedaulatan dilakukan sepenuhnya oleh MPR' adalah teks lama sebelum masa amandemen.</li><li><strong>C</strong>: Presiden adalah pelaksana mandat kekuasaan pemerintahan dan bukan pemilik kedaulatan rakyat.</li><li><strong>D</strong>: Hakim agung memegang kekuasaan kehakiman merdeka, bukan pemegang kedaulatan tertinggi.</li><li><strong>E</strong>: Lembaga keuangan tunduk pada regulasi perundang-undangan dan bukan pemegang kedaulatan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 35) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Kedaulatan berada di tangan rakyat dan dilaksanakan menurut • Undang-Undang Dasar</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Pernyataan bahwa negara Indonesia berbentuk republik yang berkedaulatan rakyat termaktub dalam...", "options": ["Alinea pertama naskah deklarasi piagam kemerdekaan", "Alinea ketiga surat ketetapan komite nasional pusat", "Alinea kedua teks pengantar penyerahan mandat menteri", "Alinea keempat naskah Pembukaan Undang-Undang Dasar 1945", "Batang tubuh aturan peralihan inventarisasi kepemilikan"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Prinsip Kedaulatan Rakyat Alinea Keempat</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Hal ini jelas dinyatakan dalam alinea keempat Pembukaan UUD 1945 yang menyatakan bahwa <strong>negara Indonesia adalah negara yang berbentuk republik yang berkedaulatan rakyat</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena alinea keempat memuat rumusan komprehensif mengenai susunan negara Republik Indonesia yang berkedaulatan rakyat dengan berdasar kepada lima sila Pancasila. Asas ini menegaskan bahwa legitimasi kekuasaan bersumber dari rakyat dan ditujukan demi kemaslahatan rakyat. Ini adalah jiwa demokrasi republik.</p><p>Jawaban tepat: <strong>D. Alinea keempat naskah Pembukaan Undang-Undang Dasar 1945</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Alinea pertama menegaskan prinsip kemerdekaan hak segala bangsa dan penolakan penjajahan.</li><li><strong>B</strong>: Ketetapan komite nasional memuat mandat legislasi darurat pada masa awal kemerdekaan.</li><li><strong>C</strong>: Pengantar mandat menteri bukan dokumen konstitusional pembentuk bentuk susunan negara.</li><li><strong>E</strong>: Aturan peralihan mengatur kontinuitas kelembagaan dan bukan perumusan kedaulatan rakyat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 35) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Kekuasaan negara yang tertinggi berada di • tangan rakyat</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Konsekuensi ketatanegaraan dari pemilihan Presiden secara langsung adalah...", "options": ["Presiden berhak membubarkan lembaga dewan perwakilan rakyat daerah", "Presiden tidak dapat dituntut pertanggungjawaban oleh lembaga manapun", "Presiden memegang kekuasaan dan bertanggung jawab kepada rakyat", "Presiden dibebaskan dari kewajiban mematuhi undang-undang dasar", "Presiden berwenang menunjuk pengganti jabatan secara dinasti keluarga"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Akuntabilitas Kepresidenan kepada Rakyat</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Mengacu pada ketentuan konstitusi, <strong>Presiden memegang kekuasaan pemerintahan dan bertanggung jawab kepada rakyat</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena presiden memperoleh mandat mandat politik langsung dari rakyat melalui pemilihan umum yang demokratis. Dengan demikian, pelaksanaan amanah pemerintahan harus senantiasa diorientasikan bagi pemenuhan aspirasi dan kesejahteraan rakyat. Akuntabilitas publik menjadi asas pokok kekuasaan kepresidenan.</p><p>Jawaban tepat: <strong>C. Presiden memegang kekuasaan dan bertanggung jawab kepada rakyat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Presiden tidak memiliki kewenangan membubarkan dewan perwakilan rakyat tingkat daerah.</li><li><strong>B</strong>: Presiden dapat dimakzulkan (impeachment) apabila terbukti melanggar hukum konstitusi.</li><li><strong>D</strong>: Presiden bersumpah di hadapan rakyat untuk memegang teguh dan taat pada UUD 1945.</li><li><strong>E</strong>: Jabatan presiden diisi melalui kontestasi pemilihan umum terbuka, bukan warisan dinasti.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 35) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Presiden memegang kekuasaan pemerintahan dan bertanggung jawab • kepada rakyat</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Mekanisme keterpilihan presiden secara langsung oleh rakyat secara konstitusional diatur dalam...", "options": ["Pasal 6A ayat (1) bahwa Presiden dan Wapres dipilih langsung rakyat", "Pasal 4 ayat (2) mengenai kewajiban tugas pokok pembantu presiden", "Pasal 7 mengenai batas masa periode lima tahun kepemimpinan", "Pasal 8 mengenai pengunduran diri dan kekosongan kepresidenan", "Pasal 9 mengenai sumpah dan janji presiden di depan persidangan"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pasal 6A Ayat (1) Pemilihan Presiden Langsung</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam <strong>Pasal 6A ayat (1) ditegaskan bahwa Presiden dan Wakil Presiden dipilih dalam satu pasangan secara langsung oleh rakyat</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena ketentuan ini menandai pergeseran sistem pemilihan dari perwakilan MPR menjadi demokrasi langsung oleh rakyat. Mandat langsung ini memperkuat derajat legitimasi kekuasaan eksekutif dalam sistem presidensial. Hal ini selaras dengan prinsip kedaulatan di tangan rakyat.</p><p>Jawaban tepat: <strong>A. Pasal 6A ayat (1) bahwa Presiden dan Wapres dipilih langsung rakyat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Pasal 4 ayat (2) mengatur bahwa presiden dibantu oleh seorang wakil presiden.</li><li><strong>C</strong>: Pasal 7 mengatur masa jabatan presiden selama 5 tahun dan dapat dipilih kembali 1 kali.</li><li><strong>D</strong>: Pasal 8 mengatur mekanisme kepemimpinan jika presiden mangkat atau diberhentikan.</li><li><strong>E</strong>: Pasal 9 mengatur lafal sumpah janji jabatan presiden sebelum memangku kewenangan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 35) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pasal 6A ayat (1) ditegaskan bahwa presiden • dipilih secara langsung oleh rakyat</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Pancasila dalam Pembukaan UUD 1945 dapat diterapkan dalam kehidupan ketatanegaraan nyata apabila...", "options": ["Dihapuskan seluruh peraturan pelaksanaan teknis tingkat kementerian", "Dibatasi penggunaannya pada kegiatan seremonial kenegaraan tahunan", "Diserahkan penafsirannya kepada para pemilik saham korporasi industri", "Diubah rumusannya mengikuti perubahan tren perpolitikan internasional", "Dirumuskan ke dalam ketentuan konkret yang tercantum dalam pasal UUD"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Hubungan Pembukaan dan Batang Tubuh UUD</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pancasila dalam Pembukaan UUD 1945 tidak mungkin dapat diterapkan sehari-hari <strong>bila tidak dirumuskan di dalam ketentuan-ketentuan yang konkret yang tercantum di dalam pasal-pasal UUD 1945</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena norma-norma filosofis Pancasila memerlukan wadah operasional berupa norma hukum positif dalam pasal-pasal UUD 1945 agar memiliki daya ikat dan sanksi. Tanpa penjabaran ke dalam pasal-pasal konstitusi dan undang-undang, nilai dasar Pancasila akan sulit ditegakkan dalam tindakan birokrasi nyata. Keduanya saling melengkapi secara utuh.</p><p>Jawaban tepat: <strong>E. Dirumuskan ke dalam ketentuan konkret yang tercantum dalam pasal UUD</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Peraturan teknis justru dibutuhkan untuk menjabarkan undang-undang ke ranah pelayanan.</li><li><strong>B</strong>: Pancasila menuntut pengamalan dalam setiap kebijakan, bukan sebatas seremonial semata.</li><li><strong>C</strong>: Penafsiran konstitusi adalah ranah lembaga kekuasaan kehakiman dan bukan korporasi modal.</li><li><strong>D</strong>: Nilai dasar Pancasila bersifat tetap dan tidak boleh digoyahkan oleh tren musiman.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 35) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pancasila yang terkandung di dalam Pembukaan UUD 1945 tidaklah mungkin • dapat kita terapkan di dalam kehidupan ketatanegaraan sehari-hari</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 03", "difficulty": "C3 - Aplikasi", "question": "Wujud pengamalan kesamaan hak dan kewajiban warga negara dalam pertahanan keamanan adalah...", "options": ["Membayar upah kepada pengawal pribadi demi mengamankan lingkungan satker", "Menjaga stabilitas ketertiban umum dan siap sedia membela kedaulatan negara", "Menuntut perlindungan fasilitas istimewa dari ancaman bencana kebakaran", "Enggan terlibat dalam kegiatan pos ronda ketertiban lingkungan", "Meninggalkan panggilan tugas kedinasan pada situasi darurat bencana alam"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kesamaan Hak dan Kewajiban Bela Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Tiap-tiap warga negara mempunyai <strong>hak dan kewajiban yang sama dalam rangka upaya pembelaan dan pertahanan keamanan negara dan bangsa</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena setiap warga negara, termasuk aparatur pemerintah, memikul tanggung jawab moral dan hukum untuk menjaga keamanan lingkungan dan siap membela kehormatan tanah air. Kesadaran bela negara diwujudkan melalui disiplin, kepatuhan hukum, dan integritas pengabdian. Kesamaan hak dan kewajiban ini mengikat seluruh warga negara.</p><p>Jawaban tepat: <strong>B. Menjaga stabilitas ketertiban umum dan siap sedia membela kedaulatan negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pengamanan pribadi bersifat komersial dan bukan wujud hak kewajiban bela negara bangsa.</li><li><strong>C</strong>: Perlindungan kedaruratan bencana diberikan setara kepada segenap warga tanpa diskriminasi.</li><li><strong>D</strong>: Pos ketertiban lingkungan merupakan wujud partisipasi riil sistem keamanan rakyat semesta.</li><li><strong>E</strong>: Aparatur negara wajib berada di garda terdepan penanganan darurat keselamatan masyarakat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 35) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>tiap-tiap warga negara mempunyai hak dan kewajiban yang sama • upaya pembelaan dan pertahanan keamanan negara</strong></div></div>"}];
let current=0;const answers=new Array(questions.length).fill(null);const letters=["A","B","C","D","E"];let seconds=65*60;let submitted=false;


// Anotasi hanya dalam pembahasan. Satu set coretan untuk setiap nomor soal.
const ANNO_KEY='stangolden_wk_p03_review_annot_v1_user_<?= (int)$USER->id ?>';
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
 try{localStorage.setItem('stg_wk_p03_map_visible',show?'1':'0')}catch(e){}
}
try{if(localStorage.getItem('stg_wk_p03_map_visible')==='0')toggleQuestionMap(false)}catch(e){}
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

  if(modalTitle) modalTitle.textContent="🏆 Lihat Ranking Wawasan Kebangsaan Paket 03";
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
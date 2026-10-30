<?php
/**
 * STANGOLDEN.exam • Nilai-Nilai Kemenkeu Paket 03 (Halaman 25–34)
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
// $DB->get_record("stgnk_p03_rank") => tabel fisik "mdlhv_stgnk_p03_rank".
// Jangan menambahkan prefix secara manual di konstanta berikut.
// Penyimpanan bersama melalui database Moodle. Nama tabel pendek, kompatibel dengan batas Moodle.
const STG_LB_T = 'stgnk_p03_rank';
const STG_REVIEW_T = 'stgnk_p03_rev';
const STG_ANNO_T = 'stgnk_p03_anno';
const STG_KEY = [0, 2, 4, 1, 3, 0, 4, 2, 1, 3, 1, 3, 0, 2, 4, 1, 3, 0, 2, 4, 2, 0, 4, 1, 3, 0, 2, 4, 1, 3, 1, 3, 0, 2, 4, 1, 3, 0, 2, 4, 0, 2, 4, 1, 3, 0, 4, 2, 1, 3, 2, 0, 4, 1, 3, 0, 2, 4, 1, 3, 0, 3, 1, 4, 2, 0, 3, 1, 4, 2, 1, 3, 0, 2, 4, 1, 3, 0, 2, 4, 0, 2, 4, 1, 3, 0, 2, 4, 1, 3, 2, 0, 4, 1, 3, 0, 2, 4, 1, 3];
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
            error_log('STANGOLDEN NK P03 HLM25-34 SETUP: ' . $e->getMessage());
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
    }catch(Throwable $e){error_log('STANGOLDEN NK P03 HLM25-34: '.$e->getMessage());stgnk_json(['success'=>false,'message'=>($e instanceof RuntimeException ? $e->getMessage() : 'Penyimpanan belum tersedia. Hubungi administrator.')],500);}
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
<title>Latihan Nilai-Nilai Kemenkeu Paket 03 (Hlm. 25–34) • STANGOLDEN</title>
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
      <span class="series-tag">Paket 03 • Hlm. 25–34</span>
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
<h1>Latihan Nilai-Nilai Kemenkeu <span class="series-tag">Paket 03 • Hlm. 25–34</span></h1>
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
        <h2 class="leaderboard-title" id="rankingModalTitle">🏆 Lihat Ranking Paket 03</h2>
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
const STG_LOCAL_KEY="stg_nk_p03_review_user_<?= (int)$USER->id ?>";
const HAS_FULL_REVIEW=true;
const IS_SITE_ADMIN=<?= is_siteadmin($USER) ? "true" : "false" ?>;
const LEADERBOARD_ENDPOINT=window.location.href.split("?")[0].split("#")[0];
const questions=[{"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan Kamus Besar Bahasa Indonesia (KBBI), sinergi diartikan secara lugas sebagai...", "options": ["Kegiatan atau operasi gabungan", "Hubungan kemitraan kerja sama", "Koordinasi terpadu antarlembaga", "Penyelarasan tugas kedinasan", "Penguatan jejaring kelembagaan"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Sinergi Menurut KBBI</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Menurut KBBI, <strong>sinergi</strong> diartikan secara lugas sebagai <strong>kegiatan atau operasi gabungan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat karena KBBI mendefinisikan sinergi sebagai kegiatan atau operasi gabungan dari berbagai unsur berbeda untuk menghasilkan kekuatan yang lebih besar. Penggabungan potensi ini melandasi terbangunnya koordinasi kerja sama yang efektif dalam organisasi. Dengan demikian, kegiatan atau operasi gabungan adalah makna leksikal resmi sinergi.</p><p>Jawaban tepat: <strong>A. Kegiatan atau operasi gabungan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kurang tepat karena hubungan kemitraan kerja sama merupakan bentuk perwujudan praktis dari sinergi.</li><li><strong>C</strong>: Kurang tepat karena koordinasi terpadu antarlembaga merupakan metode pelaksanaan sinergi di lapangan.</li><li><strong>D</strong>: Kurang tepat karena penyelarasan tugas kedinasan berfokus pada sinkronisasi uraian tugas aparatur.</li><li><strong>E</strong>: Kurang tepat karena penguatan jejaring kelembagaan merupakan sarana penunjang kemitraan organisasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 25) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Menurut KBBI, sinergi diartikan sebagai • kegiatan atau operasi gabungan</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Yang dimaksud dengan gabungan dalam konsep nilai sinergi aparatur adalah...", "options": ["Penyatuan target anggaran ke satu unit kerja", "Peleburan seluruh struktur instansi kementerian", "Gabungan berbagai unsur berbeda menjadi satu kekuatan", "Pengalihan wewenang administratif kepada pimpinan", "Penyeragaman seluruh latar belakang keilmuan pegawai"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Makna Unsur Gabungan dalam Sinergi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Yang dimaksud dengan gabungan adalah <strong>gabungan dari berbagai unsur yang berbeda menjadi satu kekuatan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat mengenai hakikat gabungan dalam nilai sinergi. Kekuatan bersama lahir bukan dari keseragaman mutlak, melainkan dari keterpaduan unsur-unsur yang beraneka ragam. Prinsip sinergi menegaskan bahwa gabungan bermakna menyatukan beragam elemen berbeda menjadi satu daya dorong organisasi. Dengan demikian, opsi C merupakan jawaban yang benar.</p><p>Jawaban tepat: <strong>C. Gabungan berbagai unsur berbeda menjadi satu kekuatan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan mekanisme konsolidasi fiskal, bukan konsep keterpaduan sinergi.</li><li><strong>B</strong>: Merupakan restrukturisasi lembaga fisik yang tidak disyaratkan dalam sinergi.</li><li><strong>D</strong>: Merupakan sentralisasi birokrasi, bukan hakikat integrasi kekuatan bersama.</li><li><strong>E</strong>: Merupakan upaya penyeragaman yang mengabaikan kekayaan variasi kompetensi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 25) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>gabungan dari berbagai unsur yang berbeda • menjadi satu kekuatan</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Sebutan lain dari konsep sinergi yang ditegaskan dalam kemitraan kerja adalah...", "options": ["Restrukturisasi birokrasi", "Divergensi kompetensi", "Spesialisasi fungsional", "Hierarki kewenangan", "Jaringan atau networking"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sebutan Lain Nilai Sinergi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sebutan lain dari sinergi adalah <strong>jaringan atau networking</strong>; membangun sinergi berarti <strong>membangun jaringan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat untuk padanan istilah sinergi dalam pedoman etika. Kemitraan kerja yang produktif berakar dari hubungan interkoneksi yang terjalin luas antarpihak. Sesuai prinsip kerja sama, istilah lain bagi sinergi adalah jaringan atau networking. Oleh karena itu, membangun sinergi dipersamakan dengan membangun jejaring kerja yang kokoh.</p><p>Jawaban tepat: <strong>E. Jaringan atau networking</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan penataan kelembagaan, bukan sebutan terminologis sinergi.</li><li><strong>B</strong>: Merupakan pemisahan keahlian yang bertolak belakang dengan kemitraan.</li><li><strong>C</strong>: Merupakan pembagian bidang tugas, bukan konsep kemitraan jaringan kerja.</li><li><strong>D</strong>: Merupakan garis komando struktural, bukan hubungan kerja sama kesetaraan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 25) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Sebutan lain dari sinergi adalah jaringan • networking</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Koordinasi dan kerja sama dalam nilai sinergi diwujudkan secara terarah demi memperoleh hasil yang...", "options": ["Seragam dan terstandarisasi penuh", "Lebih besar, berkualitas, dan cepat", "Tepat waktu, terukur, dan akuntabel", "Efisien, terpadu, dan berdaya guna", "Komprehensif dan berkelanjutan"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuan Koordinasi dalam Sinergi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sinergi diwujudkan dengan koordinasi dan kerja sama yang baik dengan pihak yang berkepentingan untuk <strong>memperoleh hasil yang lebih besar, berkualitas, dan cepat</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat karena integrasi kekuatan lintas unit mampu melipatgandakan output kerja dibandingkan penyelesaian secara parsial. Kecepatan dan keunggulan mutu hasil kerja menjadi keunggulan utama dari pola kerja kolaboratif. Oleh karena itu, hasil yang lebih besar, berkualitas, dan cepat adalah sasaran nilai sinergi.</p><p>Jawaban tepat: <strong>B. Lebih besar, berkualitas, dan cepat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena keseragaman kerja berfokus pada standardisasi operasional, bukan esensi sinergi.</li><li><strong>C</strong>: Kurang tepat karena tepat waktu dan akuntabel merupakan fokus utama pada nilai Akuntabel.</li><li><strong>D</strong>: Kurang tepat karena efisien dan terpadu merupakan prinsip umum manajemen penyelenggaraan tugas.</li><li><strong>E</strong>: Kurang tepat karena komprehensif dan berkelanjutan merupakan sasaran perbaikan nilai Kesempurnaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 25) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>koordinasi dan kerja sama yang • memperoleh hasil yang lebih besar,</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Nilai sinergi bukan hal baru bagi aparatur karena bangsa Indonesia memiliki kebiasaan luhur berupa...", "options": ["Musyawarah mufakat yang berkeadilan", "Ketaatan pada tatanan adat istiadat", "Kesantunan dalam bertutur dan bersikap", "Gotong royong yang telah mengakar kuat", "Semangat kemandirian dalam bertindak"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Akar Budaya Bangsa pada Nilai Sinergi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Nilai sinergi bagi pegawai Kementerian Keuangan bukan kebiasaan baru karena masyarakat Indonesia sangat erat dengan <strong>sifat gotong royong yang telah mengakar</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat karena tradisi gotong royong mengajarkan kebiasaan tolong-menolong secara sukarela demi kepentingan bersama masyarakat. Nilai kultural warisan leluhur ini menjadi fondasi sosio-kultural yang kokoh bagi pengamalan sinergi di lingkungan birokrasi kementerian. Dengan demikian, gotong royong adalah akar budaya sinergi bangsa.</p><p>Jawaban tepat: <strong>D. Gotong royong yang telah mengakar kuat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena musyawarah mufakat merupakan tradisi luhur dalam pengambilan keputusan mufakat.</li><li><strong>B</strong>: Kurang tepat karena ketaatan adat istiadat berfokus pada kepatuhan terhadap norma kedaerahan.</li><li><strong>C</strong>: Kurang tepat karena kesantunan bertutur kata merupakan etika pergaulan pada nilai pelayanan prima.</li><li><strong>E</strong>: Kurang tepat karena kemandirian bertindak berorientasi pada ketuntasan kerja mandiri pegawai.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 25) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>sifat gotong royong yang telah • membangun sifat mulia berupa gotong royong yang produktif</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C3 - Aplikasi", "question": "Dalam praktik kerja kedinasan, hubungan harmonis internal yang wajib dibangun oleh pegawai mencakup...", "options": ["Atasan, bawahan, dan rekan sejawat satu maupun lintas unit", "Aparat pengawas internal serta lembaga penegak hukum daerah", "Rekan kerja yang memiliki jenjang kepangkatan setara saja", "Pimpinan struktural tertinggi kementerian pada acara formal", "Pegawai pada direktorat teknis penerimaan perpajakan saja"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Cakupan Hubungan Harmonis Internal</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Hubungan harmonis yang harus dibangun meliputi <strong>hubungan dengan atasan, bawahan, dan teman sejawat</strong>, baik di lingkungan <strong>satu unit maupun lintas unit</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat untuk dimensi sinergi di lingkungan internal organisasi. Kemitraan kerja tidak boleh dibatasi oleh sekat struktural maupun pembagian unit eselon belaka. Dalam prinsip hubungan kerja, keharmonisan internal wajib merangkum atasan, bawahan, dan rekan sejawat baik seunit maupun lintas unit. Oleh karena itu, opsi A menyajikan cakupan relasi internal yang lengkap.</p><p>Jawaban tepat: <strong>A. Atasan, bawahan, dan rekan sejawat satu maupun lintas unit</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan relasi pengawasan kepatuhan, bukan dimensi relasi internal umum.</li><li><strong>C</strong>: Merupakan pembatasan relasi horizontal yang mengabaikan hubungan hierarkis.</li><li><strong>D</strong>: Merupakan interaksi protokoler seremonial yang bukan inti kerja sama harian.</li><li><strong>E</strong>: Merupakan pengotakan satu instansi tertentu yang menafikan unit lainnya.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 25) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>bawahan, dan teman sejawat • satu unit maupun lintas unit</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C3 - Aplikasi", "question": "Bentuk perwujudan sinergi eksternal Kementerian Keuangan dalam kemitraan strategis adalah kerja sama dengan...", "options": ["Konsultan swasta dalam penyusunan laporan keuangan dinas", "Lembaga penilai independen untuk audit belanja pegawai", "Penyedia jasa pengadaan barang pada lelang elektronik kantor", "Asosiasi profesi dalam merumuskan kode perilaku kepegawaian", "Kementerian atau lembaga lain sebagai mitra kerja eksternal"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sinergi dengan Pemangku Kepentingan Eksternal</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sinergi harus dibangun antara <strong>Kementerian Keuangan dengan kementerian atau lembaga lain</strong> sebagai pemangku kepentingan (*stakeholders*) dari luar sebagai mitra kerja.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat terkait kemitraan eksternal pemerintah. Pengelolaan keuangan negara membutuhkan koordinasi aktif dengan seluruh instansi pengguna dan pengelola anggaran publik. Pedoman kerja menerangkan bahwa sinergi eksternal berwujud kerja sama dengan kementerian atau lembaga lain sebagai mitra kerja. Maka dari itu, opsi E adalah bentuk kemitraan yang dimaksud secara konseptual.</p><p>Jawaban tepat: <strong>E. Kementerian atau lembaga lain sebagai mitra kerja eksternal</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan hubungan kontraktual jasa profesional, bukan sinergi antarlembaga.</li><li><strong>B</strong>: Merupakan pengawasan teknis eksternal, bukan kemitraan kelembagaan kementerian.</li><li><strong>C</strong>: Merupakan transaksi pengadaan dinas, bukan relasi sinergi pemangku kepentingan.</li><li><strong>D</strong>: Merupakan koordinasi organisasi profesi yang tidak mewakili kemitraan instansi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 25) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Keuangan dengan kementerian atau lembaga • (stakeholders) dari luar</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Keharmonisan kerja yang menumbuhkan iklim nyaman di kantor pada akhirnya bermuara pada...", "options": ["Penurunan alokasi anggaran operasional kantor secara drastis", "Penghapusan mekanisme pengawasan melekat oleh para atasan", "Peningkatan kinerja pegawai secara individu maupun kelembagaan", "Kelonggaran disiplin jam kehadiran kerja harian bagi pegawai", "Pengurangan frekuensi koordinasi teknis antarbidang tugas"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dampak Kenyamanan Iklim Kerja Sinergis</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kenyamanan iklim kerja di kantor akan menyebabkan seluruh potensi pegawai dimaksimalkan, sehingga <strong>kinerja pegawai baik secara individu maupun kelembagaan akan maksimal</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat mengenai dampak positif iklim kerja yang kondusif. Suasana kantor yang saling mendukung memungkinkan setiap pegawai mencurahkan kemampuan terbaiknya tanpa tekanan negatif. Secara psikologis, kenyamanan iklim kerja mendorong capaian kinerja individu dan lembaga hingga tingkat optimal. Oleh sebab itu, opsi C menjadi tujuan akhir pembentukan iklim harmonis.</p><p>Jawaban tepat: <strong>C. Peningkatan kinerja pegawai secara individu maupun kelembagaan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan efisiensi fiskal yang tidak berkorelasi langsung dengan iklim kerja.</li><li><strong>B</strong>: Merupakan pelemahan tata kelola disiplin yang dilarang dalam sistem aparatur.</li><li><strong>D</strong>: Merupakan pelanggaran kepatuhan waktu kerja yang menyimpang dari aturan.</li><li><strong>E</strong>: Merupakan isolasi komunikasi yang justru merusak semangat sinergi kerja.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 25) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Kenyamanan iklim kerja di kantor akan menyebabkan • kinerja pegawai baik secara individu</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Modal awal yang sangat mendasar dalam membangun hubungan kerja sama yang baik secara konseptual adalah...", "options": ["Kesamaan latar belakang disiplin keilmuan", "Persangkaan baik kepada rekan dan pimpinan", "Keahlian teknis operasional yang mumpuni", "Ketuntasan dalam menyelesaikan target kerja", "Kepatuhan penuh pada instruksi kedinasan"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Modal Awal Membangun Hubungan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Persangkaan baik kepada teman, atasan maupun bawahan</strong> adalah <strong>modal awal dalam membangun hubungan yang baik</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat karena prasangka positif menumbuhkan rasa aman psikologis dan keterbukaan antarsesama pegawai di tempat kerja. Tanpa adanya sangkaan baik, rasa saling curiga akan menghambat terciptanya koordinasi yang tulus dan produktif. Oleh sebab itu, persangkaan baik menjadi fondasi pemula hubungan yang baik.</p><p>Jawaban tepat: <strong>B. Persangkaan baik kepada rekan dan pimpinan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena kesamaan disiplin keilmuan bukan prasyarat utama membangun keharmonisan relasi kerja.</li><li><strong>C</strong>: Kurang tepat karena keahlian teknis operasional merupakan modal kapabilitas pada nilai Profesionalisme.</li><li><strong>D</strong>: Kurang tepat karena ketuntasan menyelesaikan target kerja merupakan tolok ukur capaian output aparatur.</li><li><strong>E</strong>: Kurang tepat karena kepatuhan instruksi dinas merupakan cerminan kedisiplinan dan loyalitas kerja.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 25) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Persangkaan baik kepada teman, atasan maupun bawahan • membangun hubungan yang baik</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C4 - Analisis", "question": "Berprasangka baik membuat pegawai merasa aman bekerja sama, yang selanjutnya secara psikologis menimbulkan...", "options": ["Ketergantungan penuh pada arahan teknis pimpinan", "Sikap pasrah dalam menerima keputusan pimpinan unit", "Penurunan kewaspadaan terhadap risiko tindak pidana", "Rasa saling percaya dan saling menghormati antarpegawai", "Persaingan ketat dalam memperoleh promosi kenaikan pangkat"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Hubungan Rasa Aman dan Sinergi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Rasa aman akan menimbulkan <strong>rasa saling percaya dan saling menghormati</strong>, yang kemudian mendorong terjadinya <strong>sinergi atau kerja sama</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat untuk mata rantai psikologis terbentuknya sinergi. Tanpa kekhawatiran disalahpahami atau dijatuhkan, antarrekan kerja akan tumbuh rasa hormat dan keyakinan tulus terhadap integritas rekannya. Budaya organisasi memaparkan bahwa rasa aman membuahkan saling percaya dan saling menghormati sebagai penggerak sinergi. Oleh sebab itu, opsi D melengkapi hubungan kausalitas tersebut.</p><p>Jawaban tepat: <strong>D. Rasa saling percaya dan saling menghormati antarpegawai</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan hilangnya inisiatif mandiri pegawai yang tidak diharapkan organisasi.</li><li><strong>B</strong>: Merupakan sikap apatis yang meredupkan dinamika inovasi dan perbaikan.</li><li><strong>C</strong>: Merupakan kelalaian sistem kendali yang berpotensi memicu pelanggaran hukum.</li><li><strong>E</strong>: Merupakan iklim rivalitas tidak sehat yang menghancurkan persaudaraan tim.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 25) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>aman akan menimbulkan • rasa saling percaya dan saling menghormati</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Tanda nyata bahwa seorang pegawai senantiasa berpikir positif dalam tata krama kerja terlihat pada cara...", "options": ["Menata prioritas target capaian kinerja", "Mengungkapkan sesuatu secara verbal dan nonverbal", "Menyusun laporan dinas secara tertib dan cermat", "Memilih metode penyelesaian masalah secara tepat", "Mengelola waktu penyelesaian tugas operasional"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Indikator Pegawai Senantiasa Berpikir Positif</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Tanda bahwa pegawai senantiasa berpikir positif terlihat di dalam <strong>cara mereka mengungkapkan sesuatu, baik secara verbal maupun nonverbal</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat karena pikiran positif memancar melalui pemilihan tutur kata yang santun serta gestur tubuh yang bersahabat dan menghargai orang lain. Cara berkomunikasi yang konstruktif mencerminkan kejernihan batin dan ketulusan niat pegawai dalam berinteraksi. Dengan demikian, ungkapan verbal dan nonverbal adalah indikator berpikir positif.</p><p>Jawaban tepat: <strong>B. Mengungkapkan sesuatu secara verbal dan nonverbal</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena penataan prioritas target kinerja berkaitan dengan manajemen efektivitas penugasan.</li><li><strong>C</strong>: Kurang tepat karena penyusunan laporan dinas secara cermat mencerminkan ketelitian nilai Akuntabel.</li><li><strong>D</strong>: Kurang tepat karena pemilihan metode pemecahan masalah mencerminkan kemampuan analitis Profesionalisme.</li><li><strong>E</strong>: Kurang tepat karena pengelolaan waktu operasional mencerminkan kedisiplinan kerja harian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 26) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>senantiasa berpikir positif terlihat di dalam cara mereka • secara verbal maupun nonverbal</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C3 - Aplikasi", "question": "Selain saling menyapa dan bersalaman, perilaku luhur yang penting dilakukan antarpegawai adalah...", "options": ["Memberikan apresiasi atas capaian kinerja tim", "Membantu penyusunan berkas administrasi dinas", "Berbagi referensi keahlian teknis pekerjaan", "Mendoakan rekan kerja agar sukses dan bahagia", "Mengajak berdiskusi mengenai strategi kantor"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kebiasaan Mendoakan Rekan Kerja</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Penting bagi pegawai untuk <strong>mendoakan agar teman-temannya sukses dan bahagia</strong>, baik diungkapkan secara langsung maupun sembunyi-sembunyi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat karena ketulusan mendoakan kebaikan rekan sekerja membersihkan hati dari rasa iri dengki serta mempererat ikatan persaudaraan korps. Doa yang tulus melahirkan empati dan kehangatan relasi yang memperkuat sinergi organisasi secara batiniah. Oleh karena itu, mendoakan kesuksesan rekan merupakan anjuran luhur nilai sinergi.</p><p>Jawaban tepat: <strong>D. Mendoakan rekan kerja agar sukses dan bahagia</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena pemberian apresiasi capaian kinerja tim merupakan penguatan motivasi manajerial.</li><li><strong>B</strong>: Kurang tepat karena bantuan penyusunan berkas administrasi merupakan praktik tolong-menolong teknis.</li><li><strong>C</strong>: Kurang tepat karena berbagi referensi keahlian teknis mencerminkan transfer pengetahuan Profesionalisme.</li><li><strong>E</strong>: Kurang tepat karena diskusi mengenai strategi kantor merupakan bagian dari forum koordinasi kerja.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 26) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>mendoakan agar teman-temannya sukses dan bahagia • secara langsung kepada temannya maupun sembunyi-sembunyi</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Perumusan dan pelaksanaan solusi terbaik dalam nilai sinergi wajib didukung oleh ketersediaan...", "options": ["Data dan informasi terkini, lengkap, dan akurat", "Petunjuk teknis operasional baku dari pimpinan", "Alokasi sarana prasarana kerja yang memadai", "Kesepakatan hasil musyawarah kelompok dinas", "Dukungan koordinasi kemitraan lintas satuan kerja"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Prasyarat Perumusan Solusi Terbaik</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Solusi yang baik hanya dapat diperoleh jika didukung oleh <strong>data dan informasi terkini, lengkap, dan akurat</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat karena keandalan suatu keputusan atau rekomendasi kebijakan sangat bergantung pada validitas fakta dan data pendukung. Keputusan berbasis data yang mutakhir menjamin ketepatan solusi dalam menyelesaikan akar persoalan kedinasan. Dengan demikian, data terkini, lengkap, dan akurat adalah syarat mutlak solusi terbaik.</p><p>Jawaban tepat: <strong>A. Data dan informasi terkini, lengkap, dan akurat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kurang tepat karena petunjuk teknis operasional merupakan panduan baku pelaksanaan tugas rutin.</li><li><strong>C</strong>: Kurang tepat karena alokasi sarana prasarana merupakan dukungan fasilitas fisik penunjang kerja.</li><li><strong>D</strong>: Kurang tepat karena kesepakatan hasil musyawarah merupakan wujud mufakat tim koordinasi kerja.</li><li><strong>E</strong>: Kurang tepat karena dukungan koordinasi kemitraan merupakan instrumen kelembagaan kerja sama.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 26) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Solusi yang baik hanya dapat diperoleh jika didukung oleh • data dan informasi terkini,</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C3 - Aplikasi", "question": "Saat menjumpai kendala dalam tugas, sikap proaktif pegawai yang tepat menurut nilai sinergi adalah...", "options": ["Menyerahkan telaah kendala kepada tim perumus", "Menyesuaikan target output dengan kendala tugas", "Berkontribusi menemukan ide penyelesaian masalah", "Melaporkan perkembangan berkas secara berkala", "Mengkaji regulasi pedoman operasional satker"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Inisiatif Pegawai dalam Pemecahan Masalah</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Seorang pegawai Kementerian Keuangan harus mampu <strong>berkontribusi menemukan ide penyelesaian setiap kali menemui persoalan</strong> dalam menjalankan tugas.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat karena aparatur yang sinergis dan bertanggung jawab tidak bersikap pasif atau sekadar menunggu perintah atasan saat menghadapi kendala. Inisiatif sumbangsih ide mempercepat penemuan jalan keluar terbaik demi kelancaran operasional satuan kerja. Oleh sebab itu, kontribusi ide pemecahan adalah perwujudan perilaku solusi terbaik.</p><p>Jawaban tepat: <strong>C. Berkontribusi menemukan ide penyelesaian masalah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena menyerahkan telaah kendala kepada tim lain mencerminkan sikap mengalihkan peran.</li><li><strong>B</strong>: Kurang tepat karena menyesuaikan target output dapat menurunkan standar pencapaian kinerja kantor.</li><li><strong>D</strong>: Kurang tepat karena pelaporan berkas berkala merupakan kepatuhan prosedur administratif formal.</li><li><strong>E</strong>: Kurang tepat karena kajian regulasi pedoman merupakan langkah normatif dalam telaah aturan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 26) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menemukan ide penyelesaian setiap kali • tidak boleh hanya menunggu instruksi dari pimpinan</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C4 - Analisis", "question": "Pegawai didorong berani berpikir out of the box dalam menggali solusi dengan syarat gagasan tersebut...", "options": ["Mendapat persetujuan bulat dari seluruh staf", "Memberikan efisiensi biaya operasional tinggi", "Memanfaatkan sarana teknologi informasi terkini", "Mengadopsi praktik terbaik institusi modern", "Tidak bertentangan dengan norma dan aturan berlaku"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Batasan Berpikir Out of the Box</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai didorong berpikir di luar kebiasaan (out of box), <strong>selama idenya tidak bertentangan dengan norma dan aturan yang berlaku</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat karena inovasi dan kreativitas birokrasi harus tetap berlandaskan pada prinsip legalitas serta moralitas penyelenggaraan negara. Terobosan gagasan baru tidak boleh melanggar koridor regulasi maupun kode etik kedinasan yang sah. Dengan demikian, ketaatan pada norma dan aturan menjadi batasan mutlak berpikir kreatif.</p><p>Jawaban tepat: <strong>E. Tidak bertentangan dengan norma dan aturan berlaku</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena persetujuan bulat seluruh staf bukan syarat mutlak keabsahan gagasan kreatif.</li><li><strong>B</strong>: Kurang tepat karena efisiensi biaya operasional merupakan salah satu manfaat positif inovasi kerja.</li><li><strong>C</strong>: Kurang tepat karena pemanfaatan teknologi informasi merupakan sarana penunjang modernisasi ide.</li><li><strong>D</strong>: Kurang tepat karena adopsi praktik terbaik merupakan metode pembanding dalam pengembangan gagasan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 26) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>berpikir out of box, berpikir • idenya tidak bertentangan dengan norma</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Mengenai sifat kemunculannya, secara psikologis ditegaskan bahwa kreativitas pegawai...", "options": ["Timbul ketika pegawai menerima insentif materiil dinas", "Tidak dibatasi oleh ruang dan waktu kerja", "Selalu bergantung pada kelengkapan ruang rapat", "Terbatas pada unit kerja bidang teknologi informasi", "Muncul pada momentum penyusunan anggaran belanja tahunan"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Karakteristik Fleksibilitas Kreativitas</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Kreativitas tidak dibatasi oleh ruang dan waktu</strong>, bisa muncul <strong>dimana pun dan kapan pun</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat untuk karakteristik fleksibilitas pemikiran kreatif. Inspirasi pemecahan masalah dapat hadir dalam interaksi informal, saat membaca, maupun dalam perenungan di luar lingkungan fisik kantor. Pedoman kerja menerangkan bahwa daya cipta tidak terikat sekat ruang dan waktu sehingga dapat berkembang bebas. Maka dari itu, opsi B menyatakan hakikat kreativitas secara benar.</p><p>Jawaban tepat: <strong>B. Tidak dibatasi oleh ruang dan waktu kerja</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan pandangan pamrih materiil yang menyimpang dari motivasi intrinsik ASN.</li><li><strong>C</strong>: Merupakan ketergantungan fasilitas fisik yang tidak membatasi proses berpikir kreatif.</li><li><strong>D</strong>: Merupakan pembatasan bidang yang menafikan kreativitas unit pengelola keuangan lain.</li><li><strong>E</strong>: Merupakan pengikatan musiman yang mempersempit ruang eksplorasi gagasan inovatif.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 26) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Kreativitas tidak dibatasi oleh ruang dan waktu • dimana pun dan kapan pun</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Tidak bergantung pada biaya dan tenaga besar, hal utama yang dibutuhkan dalam kreativitas adalah...", "options": ["Dukungan sponsor pendanaan dari pihak eksternal", "Peralatan komputer canggih generasi terbaru", "Waktu luang yang panjang di luar jam kedinasan", "Kemauan menggali ide dan keberanian berpikir beda", "Penugasan khusus melalui surat keputusan menteri"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kunci Utama Tumbuhnya Kreativitas</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kreativitas tidak tergantung pada biaya dan tenaga; yang dibutuhkan hanyalah <strong>kemauan menggali ide dan keberanian untuk berpikir di luar kebiasaan orang-orang berpikir</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat tentang faktor pemicu daya kreasi aparatur. Hambatan keterbatasan dana bukanlah alasan untuk berhenti melahirkan solusi kerja yang berbobot dan bernilai tambah tinggi. Secara konseptual, keberanian berpikir tidak lazim dan niat sungguh-sungguh menggali ide adalah modal terpenting kreativitas. Oleh karena itu, opsi D adalah faktor kunci keberhasilan inovasi.</p><p>Jawaban tepat: <strong>D. Kemauan menggali ide dan keberanian berpikir beda</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan bantuan dana luar yang tidak berkorelasi dengan orisinalitas ide.</li><li><strong>B</strong>: Merupakan instrumen teknologi perangkat yang sekadar alat bantu pemrosesan.</li><li><strong>C</strong>: Merupakan alasan penguluran waktu yang sering menghambat produktivitas kerja.</li><li><strong>E</strong>: Merupakan formalitas birokrasi yang membatasi spontanitas kemunculan gagasan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 26) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Kreativitas juga tidak tergantung pada biaya dan tenaga • kemauan menggali ide dan keberanian</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C3 - Aplikasi", "question": "Aktivitas positif yang disarankan guna memperkaya khazanah kreativitas pegawai kantor adalah...", "options": ["Memperbanyak diskusi dengan rekan dan membaca literatur", "Mengikuti pelatihan teknis manajemen risiko kedinasan", "Menata tata ruang kerja kantor agar lebih ergonomis", "Melakukan studi banding tata kelola berkas ke satker lain", "Menyusun telaah regulasi perbendaharaan secara berkala"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Aktivitas Pengaya Kreativitas Pegawai</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kreativitas dapat diperkaya dengan <strong>memperbanyak diskusi bersama teman-teman sekantor, membaca banyak literatur, berimajinasi</strong>, dan sebagainya.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat karena pertukaran wawasan melalui dialog terbuka serta kekayaan literasi memicu keterhubungan gagasan-gagasan baru yang inovatif. Membaca dan berimajinasi melatih kelenturan kognitif aparatur dalam merumuskan terobosan solusi kerja. Oleh sebab itu, diskusi dan literasi merupakan sarana utama pengaya kreativitas.</p><p>Jawaban tepat: <strong>A. Memperbanyak diskusi dengan rekan dan membaca literatur</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kurang tepat karena pelatihan manajemen risiko berfokus pada pengendalian mitigasi bahaya operasional.</li><li><strong>C</strong>: Kurang tepat karena penataan ruang kerja ergonomis berkaitan dengan kenyamanan fisik lingkungan dinas.</li><li><strong>D</strong>: Kurang tepat karena studi banding ke satker lain merupakan metode observasi komparatif kelembagaan.</li><li><strong>E</strong>: Kurang tepat karena telaah regulasi perbendaharaan berfokus pada pengkajian kepatuhan yuridis norma kas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 26) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Memperbanyak diskusi bersama teman-teman • membaca banyak literatur, berimajinasi dan sebagainya</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan KBBI, kata pelayanan memiliki tiga makna, yaitu perihal melayani, kemudahan jual beli, serta...", "options": ["Kewajiban bawahan mematuhi perintah atasan", "Pungutan resmi biaya izin operasional kantor", "Usaha melayani kebutuhan orang lain berimbalan", "Pemberian donasi sukarela kepada masyarakat", "Penegakan aturan kedisiplinan aparat negara"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tiga Arti Pelayanan Menurut KBBI</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Menurut KBBI, pelayanan memiliki tiga arti: (1) <strong>perihal atau cara melayani</strong>, (2) <strong>usaha melayani kebutuhan orang lain dengan memperoleh imbalan (uang) atau (jasa)</strong>, dan (3) <strong>kemudahan yang diberikan sehubungan dengan jual beli barang atau jasa</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat untuk butir kedua definisi pelayanan pada KBBI. Secara kebahasaan umum, pelayanan juga mencakup aktivitas usaha profesional yang menyediakan jasa bagi orang lain dengan imbal jasa tertentu. Secara leksikal, butir arti kedua KBBI adalah usaha melayani kebutuhan pihak lain dengan imbalan uang atau jasa. Oleh sebab itu, opsi C melengkapi rincian KBBI tersebut.</p><p>Jawaban tepat: <strong>C. Usaha melayani kebutuhan orang lain berimbalan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan kepatuhan struktural dalam organisasi, bukan makna kamus kata pelayanan.</li><li><strong>B</strong>: Merupakan penerimaan negara bukan pajak (PNBP), bukan hakikat kata pelayanan.</li><li><strong>D</strong>: Merupakan kegiatan filantropi sosial kemanusiaan, bukan batasan definisi KBBI.</li><li><strong>E</strong>: Merupakan penertiban aturan pegawai, bukan pengertian pelayanan bahasa Indonesia.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 26) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Menurut KBBI, pelayanan memiliki tiga arti • usaha melayani kebutuhan orang lain dengan memperoleh imbalan</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C4 - Analisis", "question": "Dalam budaya kerja organisasi, prasangka baik antarpegawai di kantor berangsur-angsur dapat pudar apabila...", "options": ["Sering diadakan rotasi pemindahan unit kerja", "Terdapat perbedaan jenjang pendidikan formal", "Beban kerja harian kantor semakin bertambah", "Pimpinan tidak memberikan penghargaan finansial", "Para pegawai tidak saling memberi salam bertegur sapa"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengaruh Saling Salam terhadap Prasangka Baik</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Prasangka baik akan hilang</strong> jika para pegawai di lingkungan Kementerian Keuangan <strong>tidak saling salam di antara mereka</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat mengenai dampak pengabaian interaksi dasar bersalaman. Sikap dingin dan keengganan bertegur sapa memicu prasangka negatif, kecurigaan, serta kerenggangan hubungan antarteman sekerja. Pedoman etika menegaskan bahwa prasangka baik akan hilang jika para pegawai berhenti saling mengucapkan salam saat bertemu. Maka dari itu, opsi E merupakan penyebab pudarnya prasangka baik.</p><p>Jawaban tepat: <strong>E. Para pegawai tidak saling memberi salam bertegur sapa</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan kebijakan penyegaran organisasi yang tidak otomatis merusak prasangka.</li><li><strong>B</strong>: Merupakan keragaman latar belakang pendidikan yang lazim dalam struktur ASN.</li><li><strong>C</strong>: Merupakan dinamika volume tugas yang dapat diatasi lewat koordinasi solid.</li><li><strong>D</strong>: Merupakan aspek penghargaan ekstrinsik yang bukan pemicu utama renggangnya sapaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 26) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>hilang jika para pegawai • tidak saling salam di</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Menurut Nilai-Nilai Kementerian Keuangan, nilai pelayanan didefinisikan sebagai pemberian layanan yang...", "options": ["Menjamin ketuntasan administrasi sesuai jadwal baku", "Menerapkan prinsip keterbukaan informasi secara terukur", "Memenuhi kepuasan pemangku kepentingan secara prima", "Mengutamakan efisiensi pemanfaatan sumber daya kantor", "Menyelaraskan prosedur dinas dengan standar mutu kerja"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Pelayanan Menurut Nilai Kemenkeu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pelayanan diartikan sebagai <strong>pemberian layanan yang memenuhi kepuasan pemangku kepentingan</strong> yang dilakukan dengan sepenuh hati, transparan, cepat, akurat, dan aman.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat karena orientasi utama pelayanan Kementerian Keuangan adalah memastikan kebutuhan pemangku kepentingan terlayani dengan standar kepuasan terbaik. Layanan prima diberikan secara tulus dan akuntabel demi memberikan manfaat nyata bagi pengguna jasa. Dengan demikian, pemenuhan kepuasan pemangku kepentingan adalah definisi hakiki nilai pelayanan.</p><p>Jawaban tepat: <strong>C. Memenuhi kepuasan pemangku kepentingan secara prima</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena ketuntasan administrasi sesuai jadwal mencerminkan pilar nilai Profesionalisme.</li><li><strong>B</strong>: Kurang tepat karena keterbukaan informasi terukur merupakan bagian dari tata kelola akuntabilitas publik.</li><li><strong>D</strong>: Kurang tepat karena efisiensi pemanfaatan sumber daya merupakan prinsip utama pada nilai Akuntabel.</li><li><strong>E</strong>: Kurang tepat karena penyelarasan prosedur dengan standar mutu berfokus pada manajemen kendali mutu.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 27) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>memenuhi kepuasan pemangku kepentingan • sepenuh hati, transparan, cepat, akurat, dan aman</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Prinsip pelayanan sepenuh hati diartikan secara mendasar sebagai pemberian layanan yang...", "options": ["Diberikan tanpa memandang siapa yang dilayani", "Diberikan khusus kepada wajib pajak kelas utama", "Mengutamakan pejabat struktural pemerintah daerah", "Menyesuaikan dengan tingkat kedudukan sosial warga", "Memprioritaskan pemohon dari lingkungan instansi sendiri"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Hakikat Pelayanan Sepenuh Hati</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Pelayanan sepenuh hati</strong> adalah <strong>pemberian pelayanan tanpa memandang siapa yang dilayani</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat tentang asas nondiskriminasi pelayanan publik. Setiap masyarakat, pemangku kepentingan, dan mitra kerja berhak memperoleh mutu perlakuan yang setara tanpa memandang status sosial ekonominya. Pedoman kerja menegaskan bahwa pelayanan sepenuh hati berarti melayani dengan tulus tanpa membeda-bedakan pihak yang dilayani. Oleh sebab itu, opsi A adalah batasan hakiki pelayanan sepenuh hati.</p><p>Jawaban tepat: <strong>A. Diberikan tanpa memandang siapa yang dilayani</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan pembedaan berbasis kontribusi setoran pajak yang melanggar asas keadilan.</li><li><strong>C</strong>: Merupakan perlakuan istimewa pejabat struktural yang mencederai kesetaraan hak warga.</li><li><strong>D</strong>: Merupakan stratifikasi sosial yang bertentangan dengan prinsip pelayanan aparatur.</li><li><strong>E</strong>: Merupakan sikap mementingkan kalangan sendiri yang tidak menjunjung profesionalisme.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 27) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pelayanan sepenuh hati adalah • pemberian pelayanan tanpa memandang siapa</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C4 - Analisis", "question": "Mengenai dampak kepuasan pelayanan, secara konseptual dipahami bahwa rasa kepuasan tersebut sesungguhnya...", "options": ["Diukur berdasarkan besaran kompensasi keuangan dinas", "Dicatat oleh tim pemeriksa kepatuhan internal kantor", "Dibatasi bagi pimpinan pengambil kebijakan strategis kantor", "Khusus diperuntukkan bagi masyarakat penerima bantuan sosial", "Dirasakan oleh penerima layanan dan juga oleh pemberi layanan"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dimensi Rasa Kepuasan dalam Pelayanan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kepuasan pelayanan <strong>tidak hanya dirasakan oleh penerima layanan, tetapi juga oleh pemberi layanan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat mengenai dampak timbal balik kebahagiaan pelayanan. Memberikan bantuan tulus yang tuntas menghadirkan kepuasan batin dan kebanggaan moral bagi pegawai yang melaksanakannya. Berdasarkan pedoman kerja, kepuasan hadir pada kedua belah pihak, yakni bagi pengguna layanan maupun pegawai yang melayani. Oleh karena itu, opsi E adalah simpulan yang benar.</p><p>Jawaban tepat: <strong>E. Dirasakan oleh penerima layanan dan juga oleh pemberi layanan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan orientasi materiil yang bertentangan dengan kebahagiaan melayani tulus.</li><li><strong>B</strong>: Merupakan pencatatan formal audit yang tidak menggambarkan rasa kepuasan batin.</li><li><strong>C</strong>: Merupakan pembatasan elitis yang mengabaikan perasaan pegawai pelaksana lapangan.</li><li><strong>D</strong>: Merupakan pembatasan segmen bantuan sosial yang tidak mencakup layanan umum.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 27) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>kepuasan pelayanan tidak hanya dirasakan • tetapi juga oleh pemberi layanan</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C3 - Aplikasi", "question": "Salah satu contoh konkret tindakan untuk memahami kebutuhan pengguna layanan secara terarah adalah...", "options": ["Mengadakan rapat evaluasi kinerja internal kantor", "Melakukan survei kebutuhan stakeholders secara berkala", "Menyusun pedoman standar operasional prosedur loket", "Mengoptimalkan pemanfaatan papan informasi dinas", "Mengembangkan sistem pelaporan capaian output tugas"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Contoh Upaya Memahami Kebutuhan Pemangku Kepentingan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Survei kebutuhan stakeholders</strong> adalah salah satu contoh usaha <strong>memahami kebutuhan pemangku kepentingan</strong> (pengguna layanan).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat karena survei berkala menyediakan data empiris langsung mengenai harapan dan kendala yang dialami oleh masyarakat pengguna layanan. Melalui umpan balik tersebut, unit kerja dapat menyempurnakan fasilitas dan mekanisme pelayanan secara tepat sasaran. Oleh sebab itu, survei kebutuhan adalah sarana konkret memahami pengguna layanan.</p><p>Jawaban tepat: <strong>B. Melakukan survei kebutuhan stakeholders secara berkala</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena rapat evaluasi kinerja internal berfokus pada penilaian output capaian organisasi.</li><li><strong>C</strong>: Kurang tepat karena penyusunan pedoman standar operasional loket bertujuan menjaga konsistensi alur.</li><li><strong>D</strong>: Kurang tepat karena pemanfaatan papan informasi dinas berkaitan dengan penyebarluasan info kedinasan.</li><li><strong>E</strong>: Kurang tepat karena pengembangan sistem pelaporan capaian tugas berkaitan dengan tata kelola akuntabilitas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 27) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Survei kebutuhan stakeholders adalah salah • memahami kebutuhan pemangku kepentingan</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Perilaku asertif pegawai Kementerian Keuangan kepada para pemangku kepentingan diartikan sebagai...", "options": ["Sikap lugas dalam menyampaikan ketentuan regulasi", "Ketegasan dalam menegakkan prosedur layanan publik", "Kecepatan dalam merespons disposisi surat kedinasan", "Perilaku ramah, sopan, menghargai, dan peduli sesama", "Ketelitian dalam memverifikasi berkas permohonan dinas"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengertian Perilaku Asertif dalam Pelayanan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Perilaku asertif</strong> adalah <strong>perilaku ramah, sopan, menghargai dan peduli kepada orang lain</strong> dalam memberikan pelayanan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat karena keasertifan dalam konteks pelayanan kementerian memadukan kesantunan sikap dengan kepedulian tulus terhadap kebutuhan masyarakat pemangku kepentingan. Sikap bersahabat ini menumbuhkan rasa nyaman dan kepercayaan publik terhadap profesionalitas aparatur. Dengan demikian, ramah, sopan, menghargai, dan peduli adalah makna perilaku asertif.</p><p>Jawaban tepat: <strong>D. Perilaku ramah, sopan, menghargai, dan peduli sesama</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena kelugasan menyampaikan regulasi berfokus pada kejelasan komunikasi hukum.</li><li><strong>B</strong>: Kurang tepat karena ketegasan menegakkan prosedur berkaitan dengan kedisiplinan operasional layanan.</li><li><strong>C</strong>: Kurang tepat karena kecepatan merespons disposisi surat mencerminkan ketangkasan nilai Pelayanan.</li><li><strong>E</strong>: Kurang tepat karena ketelitian memverifikasi berkas permohonan mencerminkan kecermatan Profesionalisme.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 27) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Perilaku asertif adalah • perilaku ramah, sopan, menghargai dan</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Perilaku utama kedua dari nilai pelayanan yang wajib dimiliki oleh para pegawai adalah...", "options": ["Bersikap proaktif dan cepat tanggap dalam melayani", "Melayani berorientasi kepuasan pemangku kepentingan", "Mempunyai keahlian dan pengetahuan yang luas", "Menemukan dan melaksanakan solusi terbaik bersama", "Melakukan perbaikan terus-menerus di segala bidang"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Perilaku Utama Kedua Nilai Pelayanan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Perilaku utama kedua dari nilai Pelayanan adalah <strong>bersikap proaktif dan cepat tanggap</strong> dalam melayani pemangku kepentingan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat karena petugas pelayanan dituntut peka mengenali kebutuhan pengguna jasa dan tanggap memberikan solusi tanpa harus menunggu diminta. Ketangkasan merespons ini menjadi tolok ukur keunggulan birokrasi modern yang berorientasi pada kepuasan masyarakat. Oleh sebab itu, proaktif dan cepat tanggap adalah perilaku utama kedua nilai pelayanan.</p><p>Jawaban tepat: <strong>A. Bersikap proaktif dan cepat tanggap dalam melayani</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kurang tepat karena melayani berorientasi kepuasan merupakan perilaku utama pertama nilai Pelayanan.</li><li><strong>C</strong>: Kurang tepat karena mempunyai keahlian luas merupakan perilaku utama pertama nilai Profesionalisme.</li><li><strong>D</strong>: Kurang tepat karena menemukan solusi terbaik merupakan perilaku utama kedua dari nilai Sinergi.</li><li><strong>E</strong>: Kurang tepat karena melakukan perbaikan terus-menerus merupakan perilaku utama nilai Kesempurnaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 27) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>b. Bersikap proaktif dan cepat tanggap • salah satu ciri pelayanan yang baik</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C3 - Aplikasi", "question": "Contoh perilaku proaktif dan cepat tanggap saat melihat tamu nampak kebingungan di kantor dinas adalah...", "options": ["Meminta petugas resepsionis memanggil pemohon bersangkutan", "Menunggu pemohon mendatangi loket pelayanan informasi", "Menghampiri dan menawarkan bantuan tanpa menunggu diminta", "Menyediakan brosur panduan alur perizinan di meja tunggu", "Menjelaskan jadwal loket layanan melalui pengeras suara"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Ilustrasi Praktik Proaktif dan Cepat Tanggap</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Jika ada seseorang nampak kebingungan, pegawai perlu <strong>menghampiri dan menawarkan bantuan tanpa disuruh atau diminta</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat karena inisiatif langsung menghampiri masyarakat yang membutuhkan panduan mencerminkan kepekaan empati aparatur dalam melayani. Pelayanan prima tidak bersifat pasif di balik meja, melainkan aktif menjemput kebutuhan pengguna jasa di ruang layanan. Dengan demikian, menawarkan bantuan langsung adalah wujud cepat tanggap.</p><p>Jawaban tepat: <strong>C. Menghampiri dan menawarkan bantuan tanpa menunggu diminta</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena meminta resepsionis memanggil pemohon mencerminkan sikap mengalihkan tanggung jawab.</li><li><strong>B</strong>: Kurang tepat karena menunggu pemohon mendatangi loket mencerminkan pola pelayanan pasif.</li><li><strong>D</strong>: Kurang tepat karena penyediaan brosur alur perizinan merupakan fasilitas informasi pasif di ruang tunggu.</li><li><strong>E</strong>: Kurang tepat karena pengumuman jadwal loket merupakan penyampaian informasi massal umum.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 27) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>tanpa disuruh atau diminta, seorang pegawai • perlu menghampiri dan menanyakan sesuatu</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Agar dapat segera menangkap pesan seseorang membutuhkan bantuan tanpa harus diminta, pegawai perlu memahami...", "options": ["Silsilah keturunan pemohon layanan", "Tingkat kekayaan ekonomi wajib pajak", "Riwayat pendidikan keluarga tamu kantor", "Alamat tempat tinggal resmi pemohon", "Bahasa tubuh dan isyarat nonverbal tamu"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Keterampilan Memahami Bahasa Tubuh</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Untuk dapat menumbuhkan perilaku proaktif dan cepat tanggap, para pegawai Kementerian Keuangan perlu <strong>mempelajari dan memahami bahasa tubuh</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat mengenai kepekaan komunikasi nonverbal aparatur. Gestur kebingungan, keraguan, dan tatapan mencari informasi sering kali lebih cepat terbaca sebelum terucap lewat kata-kata. Pelayanan prima menuntut perlunya pegawai memahami bahasa tubuh agar sigap menangkap kebutuhan bantuan orang lain. Oleh sebab itu, opsi E merupakan keterampilan yang dimaksud.</p><p>Jawaban tepat: <strong>E. Bahasa tubuh dan isyarat nonverbal tamu</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan data pribadi silsilah yang tidak berkaitan dengan kebutuhan pelayanan.</li><li><strong>B</strong>: Merupakan status kekayaan materiil yang dilarang menjadi tolok ukur kesigapan.</li><li><strong>C</strong>: Merupakan informasi latar belakang keluarga yang tidak relevan dengan pelayanan.</li><li><strong>D</strong>: Merupakan domisili tempat tinggal yang tercatat di arsip tanpa memicu kepekaan gestur.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 27) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Kementerian Keuangan perlu mempelajari dan • bahasa tubuh</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C3 - Aplikasi", "question": "Dalam mewujudkan pelayanan sepenuh hati, kesadaran batin yang harus ditumbuhkan dalam diri pegawai adalah...", "options": ["Mengharapkan tanda terima kasih berupa bingkisan cinderamata", "Tidak perlu mengingat seberapa besar dibayar atas layanan", "Menghitung nilai jam lembur tambahan di setiap pelayanan", "Membandingkan besaran penghasilan dengan pegawai instansi lain", "Melayani sebatas besaran gaji pokok bulanan yang diterima"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kesadaran Ikhlas dalam Melayani</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Perlu ditumbuhkan kesadaran bahwa saat melayani sepenuh hati kepada pengguna layanan, <strong>kita tidak perlu mengingat seberapa besar kita dibayar untuk pelayanan yang kita berikan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat tentang ketulusan pengabdian aparatur sipil negara. Menghitung-hitung imbalan saat berhadapan dengan warga hanya akan melahirkan sikap pamrih dan menurunkan mutu keramahan dinas. Dalam pedoman etika, kesadaran melayani sepenuh hati dibangun dengan tidak mengingat besaran bayaran yang diterima. Oleh sebab itu, opsi B mencerminkan keluhuran etika pelayanan.</p><p>Jawaban tepat: <strong>B. Tidak perlu mengingat seberapa besar dibayar atas layanan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan gratifikasi terlarang yang melanggar hukum pemberantasan korupsi.</li><li><strong>C</strong>: Merupakan kalkulasi pamrih finansial yang mengikis ketulusan melayani masyarakat.</li><li><strong>D</strong>: Merupakan perbandingan kecemburuan kompensasi yang menurunkan semangat kerja.</li><li><strong>E</strong>: Merupakan pembatasan kerja transaksional yang bertolak belakang dengan etika ASN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 27) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>melayani sepenuh hati kepada pengguna • tidak perlu mengingat seberapa besar kita dibayar</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Ukuran utama keberhasilan pelaksanaan pelayanan sepenuh hati dalam standar mutu pelayanan adalah...", "options": ["Banyaknya penghargaan piala yang diterima kantor", "Penghabisan seluruh pagu anggaran belanja tahunan", "Pujian lisan yang disampaikan oleh pimpinan instansi", "Tercapainya kepuasan para pemangku kepentingan", "Kecepatan menuntaskan disposisi dokumen tata usaha"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Ukuran Pelayanan Sepenuh Hati</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Ukuran dari pelayanan sepenuh hati</strong> adalah <strong>kepuasan pemangku kepentingan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat untuk tolok ukur hakiki mutu pelayanan instansi pemerintah. Indikator sejati kinerja layanan publik tidak terletak pada seremonial piala, melainkan pada kemanfaatan nyata yang melegakan dan memuaskan para pengguna layanan. Prinsip kerja sama menyebutkan bahwa ukuran pelayanan sepenuh hati adalah kepuasan pemangku kepentingan. Maka dari itu, opsi D adalah ukuran utama yang benar.</p><p>Jawaban tepat: <strong>D. Tercapainya kepuasan para pemangku kepentingan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan apresiasi simbolis kelembagaan yang bukan esensi langsung kepuasan warga.</li><li><strong>B</strong>: Merupakan tingkat serapan anggaran yang bukan penentu otomatis kebahagiaan publik.</li><li><strong>C</strong>: Merupakan pengakuan internal atasan yang belum tentu mencerminkan suara masyarakat.</li><li><strong>E</strong>: Merupakan ketepatan administratif surat yang sekadar prosedur teknis penunjang.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 27) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Ukuran dari pelayanan sepenuh hati adalah • kepuasan pemangku kepentingan</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Ungkapan bijak yang ditekankan dalam etos kerja agar dicamkan oleh pegawai Kementerian Keuangan berbunyi...", "options": ["Selesaikan pekerjaan hari ini tanpa menunda waktu esok", "Bekerjalah melebihi apa yang didapatkan demi hasil lebih", "Utamakan ketepatan prosedur di atas kecepatan layanan", "Jadikan integritas sebagai pedoman utama setiap tindakan", "Bangun sinergi harmonis demi keberhasilan tugas bersama"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Ungkapan Bijak Etos Kerja Melayani</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Ungkapan bijak yang perlu dicamkan pegawai berbunyi: <strong>bekerjalah Anda melebihi apa yang Anda dapatkan, maka Anda akan mendapatkan melebihi apa yang Anda kerjakan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat karena pepatah tersebut menanamkan semangat pengabdian total di mana aparatur memberikan karya terbaik melampaui imbalan materiil yang diterimanya. Dedikasi tulus ini membuka jalan bagi pencapaian prestasi dan keberkahan karier yang jauh melampaui ekspektasi. Oleh karena itu, bekerja melampaui imbalan adalah nasihat bijak yang ditekankan.</p><p>Jawaban tepat: <strong>B. Bekerjalah melebihi apa yang didapatkan demi hasil lebih</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena tidak menunda pekerjaan mencerminkan prinsip kedisiplinan manajemen waktu umum.</li><li><strong>C</strong>: Kurang tepat karena ketepatan prosedur di atas kecepatan merupakan kaidah kepatuhan administratif.</li><li><strong>D</strong>: Kurang tepat karena integritas sebagai pedoman tindakan mencerminkan amanat umum nilai moral.</li><li><strong>E</strong>: Kurang tepat karena sinergi harmonis demi keberhasilan tugas merupakan prinsip kerja sama tim.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 28) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>bekerjalah Anda melebihi apa yang • maka Anda akan mendapatkan melebihi apa yang Anda kerjakan</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Yang dimaksud dengan nilai Kesempurnaan dalam Nilai-Nilai Kementerian Keuangan adalah...", "options": ["Ketiadaan kesalahan secara mutlak dalam penyusunan neraca", "Pencapaian target serapan kas negara hingga seratus persen", "Pemberian sanksi administratif formal kepada seluruh bawahan", "Senantiasa melakukan perbaikan tiada henti di segala bidang", "Penguasaan mutlak seluruh bidang teknologi digital modern"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Nilai Kesempurnaan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Yang dimaksud dengan <strong>kesempurnaan</strong> di dalam Nilai-Nilai Kementerian Keuangan adalah <strong>senantiasa melakukan upaya perbaikan di segala bidang secara terus-menerus tiada henti untuk menjadi dan memberikan yang terbaik</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat untuk definisi nilai kesempurnaan. Karena kesempurnaan mutlak adalah milik Tuhan, manusia dituntut untuk berikhtiar terus menerus memperbaiki kekurangannya di setiap lini. Secara konseptual didefinisikan bahwa kesempurnaan sebagai ikhtiar perbaikan tiada henti di semua sektor demi memberikan hasil prima. Oleh sebab itu, opsi D merupakan batasan konsep yang benar.</p><p>Jawaban tepat: <strong>D. Senantiasa melakukan perbaikan tiada henti di segala bidang</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan klaim kesempurnaan absolut yang tidak realistis bagi manusia.</li><li><strong>B</strong>: Merupakan indikator realisasi anggaran kas yang bukan makna nilai kesempurnaan.</li><li><strong>C</strong>: Merupakan penegakan hukum disipliner yang berada di luar hakikat nilai kesempurnaan.</li><li><strong>E</strong>: Merupakan spesialisasi keahlian teknologi informasi yang sekadar sarana penunjang.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 28) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>senantiasa melakukan upaya perbaikan di segala bidang • secara terusmenerus tiada henti untuk menjadi dan memberikan yang terbaik</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C3 - Aplikasi", "question": "Terkait capaian prestasi tugas yang diraih saat ini, sikap mental yang wajib dimiliki pegawai adalah...", "options": ["Tidak cepat puas dengan prestasi yang sudah didapatkan", "Menjaga konsistensi metode kerja lama secara cermat", "Menjadikan prestasi sebagai standar pencapaian permanen", "Memfokuskan energi pada pemeliharaan sistem yang ada", "Mengupayakan pengakuan formal atas kinerja yang diraih"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sikap Mental Menghadapi Prestasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai Kementerian Keuangan perlu memiliki perilaku <strong>tidak cepat puas dengan prestasi yang sudah didapatkan saat ini</strong> guna memacu perbaikan tiada henti.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat karena rasa cepat puas dapat memicu kejenuhan dan stagnasi kinerja di tengah dinamika tantangan birokrasi yang terus berkembang. Sikap rendah hati dan senantiasa haus perbaikan mendorong lahirnya inovasi-inovasi baru demi kemajuan institusi. Dengan demikian, tidak cepat berpuas diri adalah sikap mental nilai Kesempurnaan.</p><p>Jawaban tepat: <strong>A. Tidak cepat puas dengan prestasi yang sudah didapatkan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kurang tepat karena mempertahankan metode lama secara kaku dapat menghambat adaptasi perubahan zaman.</li><li><strong>C</strong>: Kurang tepat karena menganggap prestasi sebagai standar permanen membatasi ruang pengembangan diri.</li><li><strong>D</strong>: Kurang tepat karena pemeliharaan sistem yang ada tanpa pembaruan menimbulkan stagnasi birokrasi.</li><li><strong>E</strong>: Kurang tepat karena mengejar pengakuan formal berorientasi pada pencitraan, bukan mutu kerja nyata.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 28) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pegawai Kementerian Keuangan perlu memiliki perilaku • dengan prestasi yang sudah didapatkan</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Cara praktis yang diterapkan secara berkelanjutan untuk melakukan perbaikan terus-menerus adalah...", "options": ["Menata jadwal pelatihan pegawai secara berkala", "Menyusun standar prosedur operasional baru", "Selalu mengevaluasi setiap hasil pekerjaan", "Memperkuat koordinasi tim lintas seksi kerja", "Mengembangkan sarana teknologi informasi dinas"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Metode Melakukan Perbaikan Terus Menerus</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Cara melakukan perbaikan secara terus menerus adalah <strong>dengan selalu mengevaluasi setiap hasil pekerjaan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat karena evaluasi berkala memungkinkan pendeteksian dini terhadap celah kelemahan atau ketidakefisienan dalam proses bisnis kantor. Temuan evaluasi tersebut menjadi landasan konkret perumusan langkah-langkah penyempurnaan di masa berikutnya. Oleh sebab itu, evaluasi hasil kerja adalah metode praktis perbaikan terus-menerus.</p><p>Jawaban tepat: <strong>C. Selalu mengevaluasi setiap hasil pekerjaan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena penataan jadwal pelatihan pegawai merupakan sarana pengembangan kapasitas SDM.</li><li><strong>B</strong>: Kurang tepat karena penyusunan standar prosedur baru merupakan tindak lanjut dari standardisasi kerja.</li><li><strong>D</strong>: Kurang tepat karena penguatan koordinasi tim lintas seksi merupakan instrumen pada nilai Sinergi.</li><li><strong>E</strong>: Kurang tepat karena pengembangan teknologi informasi merupakan modernisasi sarana prasarana dinas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 28) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Cara melakukan perbaikan secara • dengan selalu mengevaluasi setiap hasil pekerjaan</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C4 - Analisis", "question": "Melalui evaluasi rutin diperoleh temuan ketidaksempurnaan pekerjaan, yang secara positif berfungsi untuk...", "options": ["Menjadi dasar penyusunan kontrak kinerja baru", "Menata alokasi sumber daya secara proporsional", "Menyesuaikan target sasaran rencana operasional", "Memperbarui panduan standar pelayanan masyarakat", "Membuka ruang perbaikan demi keunggulan kerja"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Fungsi Evaluasi Hasil Pekerjaan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Penemuan kekurangan atau ketidaksempurnaan hasil pekerjaan berfungsi secara positif untuk <strong>membuka ruang perbaikan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat karena pengakuan atas kekurangan kerja bukanlah alasan saling menyalahkan, melainkan titik tolak penyempurnaan mutu kelembagaan. Kesadaran terhadap celah perbaikan memacu seluruh pegawai untuk terus berinovasi menghasilkan karya yang lebih sempurna. Dengan demikian, terbukanya ruang perbaikan adalah fungsi utama evaluasi hasil kerja.</p><p>Jawaban tepat: <strong>E. Membuka ruang perbaikan demi keunggulan kerja</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena penyusunan kontrak kinerja baru merupakan siklus tahunan manajemen kinerja ASN.</li><li><strong>B</strong>: Kurang tepat karena penataan alokasi sumber daya merupakan aspek perimbangan kapasitas satuan kerja.</li><li><strong>C</strong>: Kurang tepat karena penyesuaian target rencana operasional berfokus pada revisi target berkala.</li><li><strong>D</strong>: Kurang tepat karena pembaruan panduan standar pelayanan masyarakat merupakan aspek tata kelola loket.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 28) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Penemuan kekurangan atau ketidaksempurnaan ini • membuka ruang perbaikan</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Inovasi diartikan sebagai proses atau hasil pengembangan pengetahuan dan keterampilan yang...", "options": ["Mengharuskan pemakaian anggaran dinas dalam jumlah besar", "Memberikan nilai tambah yang signifikan bagi organisasi", "Meniru sepenuhnya metode kerja kementerian luar negeri", "Dikerjakan khusus oleh jajaran pimpinan struktural eselon", "Menghilangkan seluruh peran tenaga manusia di kantor dinas"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Inovasi dalam Buku Modul</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Inovasi</strong> diartikan sebagai proses maupun hasil pengembangan dan keterampilan untuk menciptakan atau memperbaiki produk/sistem baru yang <strong>memberikan nilai tambah secara signifikan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat untuk esensi pembaruan pada nilai kesempurnaan. Suatu perubahan baru hanya layak disebut inovasi apabila mampu mendatangkan perbaikan mutu dan kemanfaatan nyata yang berarti bagi instansi. Prinsip pembaruan menekankan bahwa inti inovasi terletak pada hadirnya nilai tambah yang signifikan. Oleh karena itu, opsi B merupakan jawaban yang benar.</p><p>Jawaban tepat: <strong>B. Memberikan nilai tambah yang signifikan bagi organisasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan asumsi biaya mahal yang bukan syarat hakiki sebuah inovasi efektif.</li><li><strong>C</strong>: Merupakan plagiasi utuh tanpa penyesuaian kebutuhan riil kementerian.</li><li><strong>D</strong>: Merupakan eksklusivitas jabatan yang membatasi hak inovasi seluruh pegawai.</li><li><strong>E</strong>: Merupakan otomatisasi ekstrem yang tidak menggambarkan hakikat inovasi layanan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 28) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Inovasi diartikan sebagai proses pengembangan • nilai tambah secara signifikan</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Nilai tambah signifikan yang dihasilkan dari proses inovasi dalam organisasi terutama berupa...", "options": ["Efisiensi waktu dalam alur birokrasi perizinan", "Keandalan sistem tata kelola administrasi naskah", "Ketepatan perhitungan target penerimaan negara", "Nilai ekonomi dan sosial bagi masyarakat publik", "Penguatan sinergi kemitraan lintas kementerian"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Bentuk Nilai Tambah Signifikan dari Inovasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Inovasi ditujukan untuk menciptakan produk atau sistem baru yang <strong>memberikan nilai tambah secara signifikan (terutama nilai ekonomi dan sosial)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat karena inovasi di sektor publik dinilai berdampak nyata apabila mampu mendorong pertumbuhan ekonomi nasional serta meningkatkan kesejahteraan masyarakat. Kemaslahatan sosial-ekonomi membuktikan bahwa pembaruan sistem birokrasi dirasakan langsung manfaatnya oleh rakyat. Oleh karena itu, nilai ekonomi dan sosial adalah nilai tambah utama inovasi.</p><p>Jawaban tepat: <strong>D. Nilai ekonomi dan sosial bagi masyarakat publik</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena efisiensi waktu birokrasi perizinan merupakan salah satu dampak operasional inovasi.</li><li><strong>B</strong>: Kurang tepat karena keandalan tata kelola naskah dinas merupakan dampak teknis kearsipan kantor.</li><li><strong>C</strong>: Kurang tepat karena ketepatan perhitungan target penerimaan merupakan sasaran teknis perbendaharaan.</li><li><strong>E</strong>: Kurang tepat karena sinergi kemitraan lintas kementerian merupakan sasaran hubungan pada nilai Sinergi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 28) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>nilai tambah secara signifikan • terutama nilai ekonomi dan sosial</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Dalam inovasi birokrasi, produk atau praktik baru pada umumnya banyak berkembang di...", "options": ["Sektor komersial atau dunia usaha bisnis", "Lembaga peradilan dan mahkamah hukum", "Pusat-pusat peribadatan dan keagamaan", "Organisasi kemasyarakatan nirlaba lokal", "Badan penanggulangan bencana daerah saja"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Asal Sektor Perkembangan Praktik Inovasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Inovasi memiliki arti sebagai produk atau praktik baru yang <strong>pada umumnya banyak berkembang di sektor komersial</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat mengenai latar belakang perkembangan praktik inovasi. Dinamika persaingan pasar mendorong dunia usaha bisnis lebih cepat beradaptasi melahirkan produk dan metode layanan termutakhir. Pedoman perilaku menerangkan bahwa inovasi pada mulanya dan umumnya banyak tumbuh subur di sektor komersial sebelum diadopsi instansi publik. Maka dari itu, opsi A adalah wujud nyata penerapan inovasi.</p><p>Jawaban tepat: <strong>A. Sektor komersial atau dunia usaha bisnis</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan lembaga peradilan yang berfokus pada penegakan hukum normatif.</li><li><strong>C</strong>: Merupakan institusi keagamaan yang memelihara nilai-nilai sakral moralitas.</li><li><strong>D</strong>: Merupakan entitas nirlaba yang gerak inovasinya bertumpu pada misi sosial.</li><li><strong>E</strong>: Merupakan unit tanggap darurat dengan protap kerja kedaruratan khusus.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 28) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>suatu produk atau praktik yang baru yang bisa disediakan • pada umumnya banyak berkembang di sektor komersial</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C4 - Analisis", "question": "Berikut ini merupakan klasifikasi tingkat kebaruan suatu inovasi dalam prinsip inovasi kerja, KECUALI...", "options": ["Bersifat baru bagi perusahaan atau aktor pembuat", "Bersifat baru bagi pasar atau pengguna layanan", "Bersifat baru bagi kepentingan pribadi perorangan pegawai", "Bersifat baru bagi suatu daerah atau tingkat negara", "Bersifat baru bagi peradaban dunia secara universal"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tingkat Kebaruan dalam Konteks Inovasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Tingkat kebaruan inovasi dibedakan bergantung pada konteksnya: <strong>baru bagi perusahaan (agen/aktor), baru bagi pasar (market), baru bagi daerah/negara, atau bahkan dunia</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat karena kepentingan pribadi sepihak bukanlah klasifikasi tingkat kebaruan inovasi. Inovasi selalu diukur dari kemanfaatannya bagi entitas lembaga, pasar, wilayah, hingga skala internasional. Karena yang dicari adalah pengecualian (KECUALI), maka opsi C merupakan butir yang tidak tepat.</p><p>Jawaban tepat: <strong>C. Bersifat baru bagi kepentingan pribadi perorangan pegawai</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan tingkatan kebaruan inovasi pada level internal unit kerja/aktor.</li><li><strong>B</strong>: Merupakan tingkatan kebaruan inovasi pada level pasar atau pengguna layanan.</li><li><strong>D</strong>: Merupakan tingkatan kebaruan inovasi pada level teritorial daerah atau negara.</li><li><strong>E</strong>: Merupakan tingkatan kebaruan tertinggi inovasi pada skala global dunia.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 28) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>(atau agen/aktor), baru bagi pasar • baru bagi suatu daerah atau</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Nilai kesempurnaan dijabarkan ke dalam dua perilaku utama pegawai Kementerian Keuangan, yaitu...", "options": ["Bekerja mandiri dan menjaga kerahasiaan arsip dinas", "Mematuhi atasan dan menghemat anggaran rapat kantor", "Memberi layanan gratis dan menjaga ketertiban jam kerja", "Menegakkan disiplin kehadiran dan memakai seragam rapi", "Perbaikan terus-menerus serta inovasi dan kreativitas"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dua Perilaku Utama Nilai Kesempurnaan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Nilai kesempurnaan dijabarkan ke dalam dua perilaku utama: (1) <strong>melakukan perbaikan terus menerus</strong> dan (2) <strong>mengembangkan inovasi baru dan kreativitas</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat karena perbaikan berkelanjutan menuntut sikap keterbukaan atas gagasan baru sekaligus keberanian melahirkan terobosan proses baru. Kedua perilaku utama tersebut saling melengkapi dalam mendorong perbaikan berkesinambungan. Oleh karena itu, opsi B memuat kedua perilaku utama yang tepat.</p><p>Jawaban tepat: <strong>E. Perbaikan terus-menerus serta inovasi dan kreativitas</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan kombinasi tugas teknis dan kerahasiaan dokumen arsip.</li><li><strong>B</strong>: Merupakan kepatuhan hierarkis dan efisiensi belanja dinas kantor.</li><li><strong>C</strong>: Merupakan pelaksanaan operasional layanan publik dan disiplin kantor.</li><li><strong>D</strong>: Merupakan penegakan tata tertib seragam yang masuk program budaya kerja.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 28) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Nilai kesempurnaan dalam Nilai-Nilai Kementerian • Melakukan perbaikan terus menerus • Mengembangkan inovasi baru dan kreativitas</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Untuk mampu mengembangkan inovasi dan kreativitas kerja, pegawai Kementerian Keuangan harus menjadi...", "options": ["Manusia pemelajar yang aktif memperbarui diri", "Praktisi manajemen yang disiplin pada aturan", "Pengkaji kebijakan yang cermat dan akuntabel", "Perencana strategi yang berorientasi ke depan", "Pelaksana operasional yang konsisten bertugas"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Karakter Manusia Pemelajar bagi Inovasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Untuk memiliki perilaku mengembangkan inovasi dan kreativitas, pegawai Kementerian Keuangan <strong>perlu menjadi manusia pemelajar</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat karena insan pemelajar memiliki kehausan intelektual untuk selalu mengeksplorasi ilmu baru dan berani mengadopsi kemajuan zaman ke dalam pekerjaannya. Sikap pembelajar berkelanjutan menjadi motor penggerak terciptanya gagasan segar dan terobosan metode kerja di instansi. Dengan demikian, menjadi manusia pemelajar adalah syarat mutlak inovasi.</p><p>Jawaban tepat: <strong>A. Manusia pemelajar yang aktif memperbarui diri</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kurang tepat karena disiplin aturan mencerminkan ketaatan terhadap prosedur pada nilai Integritas.</li><li><strong>C</strong>: Kurang tepat karena kecermatan pengkajian kebijakan mencerminkan ketelitian pada nilai Profesionalisme.</li><li><strong>D</strong>: Kurang tepat karena perencana strategi berorientasi ke depan berfokus pada fungsi perencanaan dinas.</li><li><strong>E</strong>: Kurang tepat karena pelaksana operasional konsisten mencerminkan keandalan dalam eksekusi tugas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 29) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>mengembangkan inovasi baru dan kreativitas • Kementerian Keuangan perlu menjadi manusia</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C3 - Aplikasi", "question": "Perilaku konkret yang ditunjukkan oleh orang-orang pemelajar di lingkungan kerja adalah...", "options": ["Mempelajari regulasi teknis kepegawaian terkini", "Menghadiri agenda sosialisasi program dinas kantor", "Mendorong diri menciptakan proses atau produk baru", "Berbagi data informasi melalui jaringan intranet", "Menyusun kajian komparatif terhadap metode kerja"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Perilaku Orang-Orang Pemelajar</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Orang-orang pemelajar akan <strong>mendorong dan memfasilitasi dirinya untuk berbuat, serta menciptakan cara kerja (proses) atau produk-produk (hasil) baru</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat karena pembelajar sejati tidak berhenti pada penguasaan teori semata, melainkan mengaktualisasikannya dalam bentuk karya nyata dan penyempurnaan alur kerja. Kemampuan mengubah pengetahuan menjadi solusi praktis merupakan bukti kematangan daya cipta aparatur. Oleh sebab itu, penciptaan proses atau hasil baru adalah ciri konkret manusia pemelajar.</p><p>Jawaban tepat: <strong>C. Mendorong diri menciptakan proses atau produk baru</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena mempelajari regulasi terkini merupakan kewajiban umum pembaruan pengetahuan dinas.</li><li><strong>B</strong>: Kurang tepat karena menghadiri sosialisasi program kantor merupakan kepatuhan agenda dinas kepegawaian.</li><li><strong>D</strong>: Kurang tepat karena berbagi informasi melalui intranet merupakan wujud kerja sama pada nilai Sinergi.</li><li><strong>E</strong>: Kurang tepat karena penyusunan kajian komparatif merupakan metode penelitian analitis kebijakan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 29) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>akan mendorong dan memfasilitasi dirinya • kerja (proses) atau produk-produk (hasil)</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Kreativitas atau daya cipta didefinisikan secara konseptual secara konseptual sebagai proses mental yang...", "options": ["Menerapkan sistem komputasi otomatis tanpa campur tangan", "Menghafalkan seluruh prosedur operasional standar kantor", "Menyalin konsep administrasi negara sahabat secara utuh", "Memperdebatkan kebijakan pimpinan dalam forum terbuka", "Memunculkan gagasan baru atau relasi antargagasan lama"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Konseptual Daya Cipta</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Kreativitas atau daya cipta</strong> adalah <strong>proses mental yang melibatkan pemunculan gagasan (concept) baru, atau hubungan baru antara gagasan-gagasan yang sudah ada</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat untuk pengertian ilmiah daya cipta. Kreativitas tidak selalu berwujud penemuan yang benar-benar asing, melainkan dapat berupa perangkaian baru antar-ide yang telah ada sehingga melahirkan solusi segar. Secara psikologis didefinisikan bahwa kreativitas sebagai proses mental memunculkan gagasan baru atau kaitan baru antargagasan lama. Dengan demikian, opsi E adalah rumusan yang tepat.</p><p>Jawaban tepat: <strong>E. Memunculkan gagasan baru atau relasi antargagasan lama</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan otomatisasi sistem perangkat lunak, bukan proses mental daya cipta.</li><li><strong>B</strong>: Merupakan proses kognitif hafalan mekanis yang tidak menghasilkan kebaruan ide.</li><li><strong>C</strong>: Merupakan duplikasi tanpa kreasi yang bertentangan dengan orisinalitas daya cipta.</li><li><strong>D</strong>: Merupakan polemik komunikasi publik yang berada di luar ranah kreasi pemikiran.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 29) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Kreativitas atau daya cipta adalah proses mental • gagasan (concept) baru, atau hubungan</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Dari sudut pandang keilmuan, hasil pemikiran yang berdaya cipta (creative thinking) dinilai memiliki...", "options": ["Kemewahan tampilan dan biaya produksi yang mahal", "Keaslian gagasan serta kepantasan penerapannya", "Kekebalan dari segala bentuk pengujian aturan hukum", "Keseragaman dengan produk lama yang sudah mapan", "Popularitas tinggi di kalangan masyarakat awam"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kriteria Ilmiah Pemikiran Berdaya Cipta</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dari sudut pandang keilmuan, hasil dari pemikiran berdaya cipta (*creative thinking*) <strong>dianggap memiliki keaslian dan kepantasan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat tentang tolok ukur akademik kreativitas. Sebuah inovasi harus memuat orisinalitas (*originality*) sekaligus kepatutan (*appropriateness*) agar dapat diimplementasikan secara bermanfaat bagi organisasi. Hasil pemikiran kreatif secara konseptual dicirikan oleh adanya keaslian dan kepantasan. Maka dari itu, opsi B menyatakan kriteria keilmuan yang benar.</p><p>Jawaban tepat: <strong>B. Keaslian gagasan serta kepantasan penerapannya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan aspek kemewahan materiil yang tidak menentukan mutu substansi ide.</li><li><strong>C</strong>: Merupakan kekebalan hukum keliru yang bertentangan dengan asas kepatuhan aturan.</li><li><strong>D</strong>: Merupakan ketiadaan kebaruan yang berlawanan dengan hakikat daya cipta.</li><li><strong>E</strong>: Merupakan ukuran viralitas publik yang bukan standar validitas ilmiah gagasan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 29) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Dari sudut pandang keilmuan, hasil dari pemikiran berdaya cipta • memiliki keaslian dan kepantasan</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Secara sederhana dalam konsepsi pemahaman sehari-hari, daya cipta dimaknai sebagai...", "options": ["Kemampuan meniru sistem kerja rekan kantor", "Kepatuhan terhadap jam kerja kedinasan resmi", "Kebiasaan menghafal instruksi tertulis pimpinan", "Tindakan nyata membuat sesuatu yang baru", "Keterampilan mengoperasikan peranti lunak komputer"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Konsepsi Sehari-Hari Daya Cipta</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sebagai alternatif, konsepsi sehari-hari dari daya cipta adalah <strong>tindakan membuat sesuatu yang baru</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat untuk makna praktis daya cipta di masyarakat. Terlepas dari kerumitan teori psikologi, kreativitas bermuara pada aksi nyata melahirkan karya atau cara baru yang sebelumnya belum ada. Secara konseptual, daya cipta dalam konteks harian diartikan sebagai tindakan membuat sesuatu yang baru. Oleh sebab itu, opsi D adalah rumusan pengertian yang dimaksud.</p><p>Jawaban tepat: <strong>D. Tindakan nyata membuat sesuatu yang baru</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan imitasi atau peniruan yang bertolak belakang dengan penciptaan hal baru.</li><li><strong>B</strong>: Merupakan disiplin kehadiran kerja yang masuk dalam ranah kepatuhan tata tertib.</li><li><strong>C</strong>: Merupakan kepatuhan administratif yang tidak mencerminkan aksi kreasi produktif.</li><li><strong>E</strong>: Merupakan kemahiran teknis alat yang sekadar media operasional pendukung.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 29) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>sehari-hari dari daya cipta • tindakan membuat sesuatu yang baru</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Dalam pengembangan kompetensi, daya cipta di masa kini sangat dipengaruhi oleh faktor...", "options": ["Diri dari keturunan serta faktor lingkungan", "Besaran tunjangan kinerja bulanan dinas", "Kekayaan materiil warisan keluarga pegawai", "Kedudukan strata sosial pejabat struktural", "Gelar kebangsawanan masa lalu pegawai"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Faktor yang Memengaruhi Daya Cipta</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Daya cipta di masa kini sangat dipengaruhi oleh berbagai faktor, baik <strong>faktor diri dari keturunan dan lingkungan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat tentang faktor pembentuk potensi kreativitas manusia. Kemampuan mencipta merupakan perpaduan antara potensi bawaan (*nature*) dan stimulasi iklim lingkungan sekitar (*nurture*) yang mendukung. Pedoman etika menegaskan bahwa daya cipta dipengaruhi faktor internal keturunan dan faktor eksternal lingkungan. Dengan demikian, opsi A adalah faktor penentu yang benar.</p><p>Jawaban tepat: <strong>A. Diri dari keturunan serta faktor lingkungan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan faktor ekstrinsik keuangan yang bukan pembentuk utama struktur daya cipta.</li><li><strong>C</strong>: Merupakan status harta kekayaan yang tidak berkorelasi dengan potensi intelektual.</li><li><strong>D</strong>: Merupakan posisi jabatan struktural yang berada di luar hakikat biologis psikologis.</li><li><strong>E</strong>: Merupakan status feodal masa lalu yang tidak relevan dengan kompetensi aparatur modern.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 29) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>masa kini sangat dipengaruhi oleh • faktor diri dari keturunan dan</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Nilai-nilai organisasi (organizational values) berfungsi sebagai pedoman bertindak sekaligus untuk...", "options": ["Menata hierarki kepemimpinan dalam birokrasi", "Mengatur alokasi pembagian pagu anggaran seksi", "Menetapkan kriteria promosi jenjang kepangkatan", "Menyusun instrumen evaluasi akuntabilitas satker", "Meningkatkan kohesi dan rasa kesatuan organisasi"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi dan Fungsi Nilai Organisasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Nilai-nilai organisasi adalah pedoman dalam mengambil tindakan dan keputusan, serta berfungsi untuk <strong>meningkatkan kohesi dan rasa kesatuan dalam organisasi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat karena nilai bersama merekatkan seluruh anggota dari berbagai latar belakang ke dalam satu identitas korps yang solid dan harmonis. Keterpaduan rasa kesatuan ini mencegah timbulnya ego sektoral dan memperkuat komitmen pencapaian visi institusi. Dengan demikian, peningkatan kohesi dan persatuan adalah fungsi utama nilai organisasi.</p><p>Jawaban tepat: <strong>E. Meningkatkan kohesi dan rasa kesatuan organisasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena penataan hierarki kepemimpinan merupakan ranah regulasi struktur organisasi.</li><li><strong>B</strong>: Kurang tepat karena pengaturan pagu anggaran seksi merupakan instrumen teknis penganggaran negara.</li><li><strong>C</strong>: Kurang tepat karena kriteria promosi jenjang kepangkatan diatur dalam sistem manajemen talenta ASN.</li><li><strong>D</strong>: Kurang tepat karena evaluasi akuntabilitas satker menggunakan pedoman penilaian sistem kinerja publik.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 29) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>adalah sebuah pedoman, petunjuk, atau • meningkatkan kohesi dan rasa kesatuan dalam organisasi</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Sebagai organisasi modern dan dinamis, arah pergerakan tata kelola Kementerian Keuangan selalu menuju...", "options": ["Standarisasi prosedur pelayanan berbasis digital", "Penguatan struktur hierarki kelembagaan vertikal", "Pengelolaan manajemen yang maju (best practices)", "Kemitraan strategis dengan institusi internasional", "Optimalisasi penerimaan fiskal berbasis potensi riil"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Arah Tata Kelola Manajemen Kementerian Keuangan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kementerian Keuangan adalah organisasi modern yang dinamis yang pergerakannya selalu menuju <strong>sebuah pengelolaan manajemen yang maju (best practices)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat karena Kementerian Keuangan senantiasa melakukan modernisasi tata kelola birokrasi mengadopsi standar-standar keunggulan manajemen kelas dunia. Pengelolaan yang maju menjamin efektivitas tata kelola fiskal negara yang transparan dan akuntabel. Oleh karena itu, pengelolaan manajemen maju (best practices) adalah arah pergerakan kementerian.</p><p>Jawaban tepat: <strong>C. Pengelolaan manajemen yang maju (best practices)</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena standarisasi prosedur digital merupakan salah satu sarana penunjang tata laksana.</li><li><strong>B</strong>: Kurang tepat karena penguatan struktur hierarki vertikal berkaitan dengan perancangan struktur kelembagaan.</li><li><strong>D</strong>: Kurang tepat karena kemitraan internasional merupakan aspek diplomasi kerja sama bidang fiskal.</li><li><strong>E</strong>: Kurang tepat karena optimalisasi penerimaan fiskal merupakan target output pada fungsi penerimaan negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 29) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>dinamis yang pergerakannya selalu menuju • pengelolaan manajemen yang</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Dalam misi penguatan kelembagaan, Kementerian Keuangan menargetkan pembangunan SDM yang...", "options": ["Terampil, cekatan, berwawasan luas, dan adaptif", "Amanah, profesional, berintegritas, dan bertanggung jawab", "Kritis, solutif, memiliki dedikasi tinggi, dan loyal", "Mandiri, inovatif, berdaya saing global, dan tanggap", "Kolaboratif, ramah, beretika luhur, dan disiplin"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Misi Penguatan Sumber Daya Manusia Kemenkeu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Misi kementerian mencakup: membangun dan mengembangkan sumber daya manusia yang <strong>amanah, profesional, berintegritas tinggi, dan bertanggung jawab</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat karena keempat karakter luhur tersebut menjadi pilar utama pembentukan aparatur pengelola keuangan negara yang beretika mulia dan kompeten. Karakter amanah dan integritas menjaga kepercayaan rakyat terhadap tata kelola kas negara. Dengan demikian, amanah, profesional, berintegritas, dan bertanggung jawab adalah rumusan misi SDM.</p><p>Jawaban tepat: <strong>B. Amanah, profesional, berintegritas, dan bertanggung jawab</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena kombinasi kompetensi ini merupakan rumusan umum pengembangan kapasitas kerja.</li><li><strong>C</strong>: Kurang tepat karena kritis dan loyal merupakan nilai sikap umum dalam pembinaan etika pegawai.</li><li><strong>D</strong>: Kurang tepat karena mandiri dan berdaya saing global merupakan visi pengembangan talenta aparatur.</li><li><strong>E</strong>: Kurang tepat karena kolaboratif dan beretika luhur mencerminkan sikap pada nilai Harmonis dan Pelayanan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 29) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Membangun dan mengembangkan sumber daya manusia yang • profesional, berintegritas tinggi, dan bertanggung</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C4 - Analisis", "question": "Karakteristik sistem teknologi informasi keuangan yang dibangun dalam misi kelembagaan kementerian adalah...", "options": ["Mandiri, terdesentralisasi, dan berbasis web lokal", "Fleksibel, adaptif, serta ditopang aplikasi terbuka", "Cepat, responsif, dan berbasis kecerdasan buatan", "Modern, terintegrasi, dan ditopang sarana strategis", "Komprehensif, otomatis, dan berorientasi pengguna"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Misi Pengembangan Teknologi Informasi dan Sarana</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Misi penguatan kementerian mencakup: membangun dan mengembangkan <strong>teknologi informasi keuangan yang modern dan terintegrasi serta sarana dan prasarana strategis lainnya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat karena integrasi sistem teknologi informasi keuangan menjamin kelancaran arus data perbendaharaan negara secara aman, cepat, dan terpercaya. Keterpaduan infrastruktur strategis ini meminimalisasi duplikasi data dan meningkatkan akurasi analisis fiskal. Oleh sebab itu, modern, terintegrasi, dan ditopang sarana strategis adalah karakteristiknya.</p><p>Jawaban tepat: <strong>D. Modern, terintegrasi, dan ditopang sarana strategis</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena desentralisasi web lokal bertentangan dengan prinsip keterpaduan basis data nasional.</li><li><strong>B</strong>: Kurang tepat karena aplikasi terbuka memiliki risiko kerentanan keamanan terhadap kerahasiaan data fiskal.</li><li><strong>C</strong>: Kurang tepat karena kecerdasan buatan merupakan salah satu fitur pengembangan teknologi mutakhir dinas.</li><li><strong>E</strong>: Kurang tepat karena otomatis dan berorientasi pengguna merupakan karakteristik umum antarmuka sistem.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 29) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>teknologi informasi keuangan yang modern • sarana dan prasarana strategis lainnya</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Menteri Keuangan periode 2010–2013 yang mencetuskan ide perumusan Nilai-Nilai Kementerian Keuangan adalah...", "options": ["Bambang Brodjonegoro", "Sri Mulyani Indrawati", "Agus Dermawan Martowardojo", "Boediono Martowardojo", "Mar'ie Muhammad Alamsyah"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Menteri Keuangan Pencetus Nilai Bersama</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Agus Dermawan Mintarto Martowardojo</strong> (lebih populer dengan sebutan <strong>Agus Martowardojo</strong>), Menteri Keuangan periode 2010-2013, mencetuskan ide untuk merumuskan Nilai-Nilai Kementerian Keuangan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat untuk nama Menteri Keuangan pemrakarsa nilai bersama. Kepemimpinan beliau menandai babak konsolidasi budaya kerja di tubuh kementerian demi menyatukan gerak seluruh unit eselon. Secara historis tercatat nama nama Agus Martowardojo sebagai menteri periode 2010–2013 yang melahirkan inisiatif tersebut. Oleh sebab itu, opsi C adalah tokoh yang dimaksud.</p><p>Jawaban tepat: <strong>C. Agus Dermawan Martowardojo</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan Menteri Keuangan periode 2014–2016, bukan pencetus ide perumusan nilai 2011.</li><li><strong>B</strong>: Merupakan Menteri Keuangan yang memimpin reformasi birokrasi 2006 dan penguatan budaya 2022.</li><li><strong>D</strong>: Merupakan penggabungan nama tokoh yang keliru dan tidak sesuai catatan sejarah perumusan nilai.</li><li><strong>E</strong>: Merupakan Menteri Keuangan periode 1993–1998 yang terkenal dengan julukan Mr. Clean.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 30) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Agus Dermawan Mintarto Martowardojo • Menteri Keuangan</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Sebelum menjabat sebagai Menteri Keuangan, Agus Martowardojo memiliki rekam jejak kepemimpinan korporasi di sektor...", "options": ["Perbankan dan institusi korporasi finansial", "Pertambangan minyak bumi dan gas lepas pantai", "Manufaktur industri tekstil dan garmen ekspor", "Transportasi logistik pelayaran antar-pulau", "Pembangunan infrastruktur jalan tol nasional"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Latar Belakang Pengalaman Menteri Pencetus</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Agus Martowardojo memiliki pengalaman segudang mengelola berbagai <strong>institusi korporasi khususnya perbankan</strong> sebelum mencetuskan perumusan nilai organisasi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat tentang latar belakang profesional Menteri Keuangan pencetus nilai. Jam terbang tinggi di industri perbankan yang menuntut kepatuhan integritas dan pelayanan prima menginspirasi beliau membangun fondasi nilai terpadu di Kemenkeu. Sebagaimana dijelaskan di pedoman etika, pengalaman korporasi beliau berfokus di sektor perbankan. Maka dari itu, opsi A adalah rekam jejak yang benar.</p><p>Jawaban tepat: <strong>A. Perbankan dan institusi korporasi finansial</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan industri energi migas yang bukan rekam jejak utama kepemimpinan beliau.</li><li><strong>C</strong>: Merupakan industri manufaktur padat karya yang tidak relevan dengan catatan pedoman kerja.</li><li><strong>D</strong>: Merupakan sektor logistik maritim yang berada di luar riwayat perbankan beliau.</li><li><strong>E</strong>: Merupakan sektor konstruksi fisik jalan tol yang bukan bidang keahlian korporasi beliau.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 30) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>punya segudang pengalaman • perbankan, mencetuskan ide untuk</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Faktor pendorong awal dicetuskannya ide perumusan nilai bersama dalam penataan organisasi adalah...", "options": ["Adanya tuntutan bantuan dana utang dari lembaga donor", "Penurunan drastis realisasi penerimaan perpajakan negara", "Pergantian seluruh jajaran pejabat pimpinan eselon dinas", "Desakan demonstrasi publik di depan kantor kementerian", "Terjadinya variasi nilai di antara unit Eselon I yang ada"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pemicu Awal Perumusan Nilai Kemenkeu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Ide perumusan nilai dicetuskan <strong>setelah mengamati terjadinya variasi nilai dari Eselon-Eselon I yang ada di Kementerian Keuangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat mengenai alasan sosiologis perumusan nilai kementerian. Sebelum 2011, setiap unit eselon eselon I memiliki tata nilai tersendiri sehingga menimbulkan sekat persepsi kerja antarinstansi di bawah satu payung kementerian. Pedoman resmi menyatakan bahwa Menkeu mengamati variasi nilai antarekselon tersebut sebagai titik awal perlunya penyatuan. Dengan demikian, opsi E adalah faktor pemicu awal yang tepat.</p><p>Jawaban tepat: <strong>E. Terjadinya variasi nilai di antara unit Eselon I yang ada</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan faktor pinjaman luar negeri yang tidak melatarbelakangi perumusan nilai.</li><li><strong>B</strong>: Merupakan capaian target fiskal yang bukan alasan langsung penyatuan nilai budaya.</li><li><strong>C</strong>: Merupakan dinamika mutasi kepegawaian biasa yang bukan latar historis KMK 312/2011.</li><li><strong>D</strong>: Merupakan tekanan aksi massa eksternal yang tidak terkait dengan perumusan internal ini.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 30) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>mencetuskan ide untuk merumuskan nilai-nilai • terjadinya variasi nilai dari Eselon-Eselon</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C4 - Analisis", "question": "Kekhawatiran utama Menteri Keuangan yang mendasari pentingnya perumusan nilai bersama adalah...", "options": ["Ketergantungan pembiayaan pada perbankan komersial", "Kesan unit Eselon I berjalan sendiri tanpa menyatu", "Tuntutan kenaikan tunjangan kinerja pegawai di daerah", "Tingginya angka permohonan pensiun dini dari aparatur", "Ketiadaan pedoman teknis penyusunan neraca belanja"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kekhawatiran Utama Menteri Keuangan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Salah satu kekhawatiran Menkeu adalah ia tidak ingin unit Eselon I <strong>berjalan sendiri-sendiri</strong> dan menimbulkan citra <strong>seolah-olah bekerja sendiri-sendiri, tidak menyatu sebagai satu kesatuan organisasi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat terkait pencegahan ego sektoral kelembagaan. Keragaman fungsi seperti pajak, bea cukai, perbendaharaan, dan anggaran berisiko memecah persepsi jika tidak diikat oleh satu pedoman nilai bersama. Pedoman kerja menerangkan kekhawatiran sang Menteri bila unit-unit berjalan sendiri tanpa ikatan keutuhan. Oleh karena itu, opsi B merangkum kekhawatiran utama tersebut.</p><p>Jawaban tepat: <strong>B. Kesan unit Eselon I berjalan sendiri tanpa menyatu</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan kekhawatiran utang pasar modal yang berada di luar konteks budaya kerja.</li><li><strong>C</strong>: Merupakan isu aspirasi remunerasi kepegawaian yang tidak mendasari perumusan nilai.</li><li><strong>D</strong>: Merupakan demografi kepegawaian yang bukan latar belakang integrasi budaya organisasi.</li><li><strong>E</strong>: Merupakan pedoman akuntansi standar yang telah diatur regulasi perbendaharaan baku.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 30) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>tidak ingin setiap unit Eselon I • berjalan sendiri-sendiri • tidak menyatu sebagai satu kesatuan</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Tahun bersejarah diselenggarakannya forum brainstorming penggalian nilai-nilai Kementerian Keuangan adalah...", "options": ["Tahun 2006", "Tahun 2008", "Tahun 2009", "Tahun 2011", "Tahun 2013"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tahun Pelaksanaan Brainstorming Perumusan Nilai</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Pada tahun 2011</strong>, Agus Martowardojo mengumpulkan para pejabat untuk melakukan *brainstorming* penggalian nilai-nilai Kementerian Keuangan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat untuk tahun berlangsungnya musyawarah perumusan nilai. Momentum tahun 2011 menjadi tonggak penetapan KMK 312/KMK.01/2011 yang memformalkan lima nilai kementerian. Sebagaimana dicatat tegas pada pedoman kerja, pertemuan intensif penggalian nilai tersebut terlaksana pada tahun 2011. Dengan demikian, tahun 2011 adalah angka kronologis yang benar.</p><p>Jawaban tepat: <strong>D. Tahun 2011</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan tahun awal peluncuran program reformasi birokrasi di Kementerian Keuangan.</li><li><strong>B</strong>: Merupakan masa penataan kelembagaan lanjutan pasca awal reformasi birokrasi.</li><li><strong>C</strong>: Merupakan tahun transisi menjelang pergantian pimpinan kementerian pada kabinet baru.</li><li><strong>E</strong>: Merupakan tahun penetapan KMK Nomor 127/KMK.01/2013 tentang Program Budaya.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 30) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pada tahun 2011, Agus Martowardojo mengumpulkan • untuk melakukan brainstorming</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Berapakah jumlah keseluruhan pejabat pimpinan yang dikumpulkan dalam forum penggalian nilai tersebut?", "options": ["76 pejabat pimpinan", "65 pejabat pimpinan", "55 pejabat pimpinan", "88 pejabat pimpinan", "99 pejabat pimpinan"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Jumlah Pejabat Perumus Nilai Kemenkeu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Agus Martowardojo mengumpulkan Wakil Menteri Keuangan, seluruh pejabat Eselon I, serta perwakilan pejabat Eselon II dari masing-masing unit, <strong>yang berjumlah 76 pejabat</strong>, untuk melakukan *brainstorming*.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat untuk jumlah peserta musyawarah perumusan nilai. Kehadiran 76 pejabat mencerminkan representasi lengkap pimpinan tertinggi dari seluruh unit eselon kementerian keuangan. Dalam catatan pembahasan, forum brainstorming tersebut dihadiri persis 76 pejabat. Oleh sebab itu, opsi A merupakan data numerik yang tepat.</p><p>Jawaban tepat: <strong>A. 76 pejabat pimpinan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Bukan angka jumlah peserta yang tercantum pada dokumen historis perumusan nilai.</li><li><strong>C</strong>: Bukan jumlah pejabat yang hadir pada rapat penggalian nilai dalam pedoman etika.</li><li><strong>D</strong>: Angka pengecoh numerik yang tidak sesuai dengan sejarah penggalian nilai.</li><li><strong>E</strong>: Angka pengecoh yang melebihi jumlah riil pejabat perumus di kementerian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 30) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>berjumlah 76 pejabat, untuk melakukan • sedang dirumuskan adalah sesuatu yang</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C3 - Aplikasi", "question": "Komposisi pimpinan yang diundang dalam musyawarah penggalian nilai Kementerian Keuangan terdiri atas...", "options": ["Menteri dan staf ahli bidang hubungan internasional saja", "Seluruh pejabat fungsional auditor pemeriksa pajak daerah", "Wamenkeu, seluruh pejabat Eselon I, dan wakil Eselon II", "Kepala kantor pelayanan terdepan di seluruh Indonesia", "Perwakilan pegawai honorer serta calon aparatur sipil negara"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Komposisi Pejabat Perumus Nilai Bersama</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Peserta perumusan terdiri atas <strong>Wakil Menteri Keuangan, seluruh pejabat Eselon I, serta perwakilan pejabat Eselon II dari masing-masing unit</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat mengenai susunan representasi pimpinan perumus nilai. Keterlibatan pimpinan puncak dan perwakilan pimpinan madya menjamin nilai yang digali mewakili aspirasi strategis sekaligus operasional setiap unit kerja. Secara representatif dihadirkan unsur peserta meliputi Wamenkeu, para Eselon I, dan wakil Eselon II. Maka dari itu, opsi C menyajikan komposisi yang benar.</p><p>Jawaban tepat: <strong>C. Wamenkeu, seluruh pejabat Eselon I, dan wakil Eselon II</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan susunan staf ahli yang tidak mencakup pimpinan operasional unit eselon.</li><li><strong>B</strong>: Merupakan pejabat fungsional pemeriksa teknis yang tidak mewakili seluruh unit.</li><li><strong>D</strong>: Merupakan kepala kantor pelayanan vertikal yang belum dilibatkan pada tahap awal ini.</li><li><strong>E</strong>: Merupakan aparatur pelaksana yang saat itu belum masuk forum perumusan tingkat pimpinan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 30) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pejabat Eselon I • perwakilan pejabat Eselon II dari masing-masing unit</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Dalam sejarah perumusan nilai, musyawarah perumusan nilai berlangsung maraton selama sekitar...", "options": ["8 jam kerja normal kantor", "10 jam diselingi istirahat", "12 jam kerja penuh waktu", "15 jam pembahasan teknis", "17 jam kerja tanpa henti"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Durasi Waktu Brainstorming Perumusan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Para pejabat bekerja penuh antusias mulai jam 08.00 pagi sampai sekitar jam 01.00 dini hari (hari berikutnya), yaitu <strong>bekerja selama sekitar 17 jam tanpa henti</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat tentang total waktu pembahasan nilai kementerian. Dedikasi tinggi ditunjukkan melalui kerja intensif mulai pagi hari hingga dini hari berikutnya demi menuntaskan pedoman bersama bagi seluruh generasi pegawai. Dalam catatan sejarah perumusan nilai, para pejabat berdiskusi selama sekitar 17 jam tanpa henti. Oleh sebab itu, opsi E adalah durasi waktu yang tepat.</p><p>Jawaban tepat: <strong>E. 17 jam kerja tanpa henti</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan durasi jam kerja biasa yang tidak mencerminkan kerja maraton malam itu.</li><li><strong>B</strong>: Bukan total waktu pelaksanaan perumusan nilai dalam forum kesepakatan nilai.</li><li><strong>C</strong>: Bukan durasi riil musyawarah dari pukul 08.00 pagi hingga 01.00 dini hari.</li><li><strong>D</strong>: Kurang dari catatan waktu pedoman kerja yang mencantumkan durasi maraton 17 jam.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 30) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>bekerja mulai jam 08.00 pagi • selama sekitar 17 jam tanpa henti</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Dari forum musyawarah maraton tersebut, para pimpinan akhirnya menyepakati perumusan sejumlah...", "options": ["3 nilai dasar kementerian", "5 Nilai Kementerian Keuangan", "7 prinsip integritas fiskal", "9 kode perilaku kepegawaian", "10 kaidah budaya kerja dinas"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Hasil Kesepakatan Jumlah Nilai Kemenkeu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Akhirnya, mereka menyepakati <strong>5 Nilai Kementerian Keuangan</strong> yang kita kenal saat ini (Integritas, Profesionalisme, Sinergi, Pelayanan, Kesempurnaan).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat untuk hasil akhir konsensus perumusan nilai. Lima nilai pokok tersebut merangkum seluruh karakter unggul yang disepakati menjadi jiwa pengabdian pegawai Kementerian Keuangan. Dalam pedoman etika, forum tersebut menyepakati 5 Nilai Kementerian Keuangan. Dengan demikian, opsi B adalah jumlah nilai yang disepakati.</p><p>Jawaban tepat: <strong>B. 5 Nilai Kementerian Keuangan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Bukan jumlah nilai yang disepakati; 3 nilai merujuk pada butir 3S program budaya.</li><li><strong>C</strong>: Merupakan angka variasi yang bukan ketetapan jumlah nilai Kementerian Keuangan.</li><li><strong>D</strong>: Bukan klasifikasi nilai kementerian; 9 merujuk pada butir nilai antikorupsi KPK.</li><li><strong>E</strong>: Bukan jumlah nilai bersama yang ditetapkan dalam musyawarah bersejarah 2011.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 30) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>mereka menyepakati 5 Nilai Kementerian • kita kenal saat ini</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Kesepakatan bersama mengenai Nilai-Nilai Kementerian Keuangan dituangkan secara sah dalam...", "options": ["Peraturan Pemerintah Republik Indonesia Nomor 312", "Undang-Undang Republik Indonesia Nomor 312/2011", "Surat Keputusan Bersama Tingkat Menteri Nomor 312", "Keputusan Menteri Keuangan Nomor 312/KMK.01/2011", "Instruksi Presiden Republik Indonesia Nomor 312"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Regulasi Penetapan Nilai-Nilai Kemenkeu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kesepakatan tersebut dituangkan dalam <strong>Keputusan Menteri Keuangan Nomor 312/KMK.01/2011 tentang Nilai-Nilai Kementerian Keuangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat untuk landasan hukum penetapan nilai bersama kementerian. KMK ini menjadi dokumen historis fundamental yang pertama kali mengkodifikasikan kelima nilai beserta kaidah perilaku utamanya. Secara operasional ditegaskan bahwa penetapan resmi dilakukan melalui KMK Nomor 312/KMK.01/2011. Oleh sebab itu, opsi D adalah nomor regulasi yang tepat.</p><p>Jawaban tepat: <strong>D. Keputusan Menteri Keuangan Nomor 312/KMK.01/2011</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan nomenklatur regulasi tingkat pemerintah pusat yang bukan penetapan menteri.</li><li><strong>B</strong>: Merupakan produk legislasi parlemen bersama presiden, bukan payung hukum internal.</li><li><strong>C</strong>: Merupakan bentuk surat edaran dinas yang berada di bawah tingkat ketetapan menteri.</li><li><strong>E</strong>: Merupakan regulasi presiden yang berada di luar ranah keputusan menteri keuangan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 30) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>dituangkan dalam Keputusan Menteri Keuangan Nomor • Nilai-Nilai Kementerian Keuangan</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Dalam pembinaan budaya kerja, nilai-nilai organisasi tidak akan bermakna sama sekali apabila...", "options": ["Tidak diimplementasikan dalam kehidupan sehari-hari", "Tidak didukung oleh alokasi sarana prasarana kerja", "Tidak dituangkan dalam modul pembelajaran pegawai", "Tidak disosialisasikan secara berkala oleh pimpinan", "Tidak diujikan dalam penilaian kompetensi berkala"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Urgensi Implementasi Nyata Nilai Bersama</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Nilai-nilai yang sudah dirumuskan <strong>tidak akan bermakna sama sekali jika tidak diimplementasikan atau tidak diterapkan dalam kehidupan sehari-hari</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat karena nilai organisasi bukan sekadar pajangan slogan administratif, melainkan penuntun perilaku moral dan operasional nyata di lingkungan dinas. Keberhasilan budaya kerja diukur dari sejauh mana nilai tersebut diamalkan dalam setiap tindakan dan pengambilan keputusan aparatur. Dengan demikian, penerapan harian adalah penentu utama kebermaknaan nilai.</p><p>Jawaban tepat: <strong>A. Tidak diimplementasikan dalam kehidupan sehari-hari</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kurang tepat karena sarana prasarana merupakan pendukung fisik, bukan penentu esensi hidupnya nilai.</li><li><strong>C</strong>: Kurang tepat karena modul pembelajaran merupakan media sosialisasi transfer pengetahuan nilai.</li><li><strong>D</strong>: Kurang tepat karena sosialisasi pimpinan merupakan metode penyebaran pemahaman konsep budaya kerja.</li><li><strong>E</strong>: Kurang tepat karena uji kompetensi merupakan instrumen formal evaluasi pemahaman regulasi dinas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 31) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>tidak akan bermakna sama sekali • atau tidak diterapkan dalam kehidupan sehari-hari</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Dalam diktum kedua KMK Nomor 312/KMK.01/2011, pelaksanaan nilai kementerian diwujudkan secara nyata dalam...", "options": ["Pemberian penghargaan sertifikat piala bergilir", "Pengadaan seragam dinas baru bermotif khusus", "Penataan ruang kerja kantor dengan partisi kaca", "Kaidah-kaidah perilaku utama dari setiap nilai", "Penghitungan jam lembur kerja operasional bulanan"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Perwujudan Nilai dalam KMK 312/2011</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pelaksanaan Nilai-Nilai Kementerian Keuangan <strong>diwujudkan dalam kaidah-kaidah perilaku utama dari setiap nilai yang ditetapkan (keputusan kedua)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat untuk ketentuan diktum kedua keputusan menteri tersebut. Nilai filosofis yang abstrak dijabarkan ke dalam panduan perilaku konkret agar mudah dipahami, diterapkan, dan dinilai penerapannya pada setiap insan pegawai. Dalam prinsip hubungan kerja, keputusan resmi menetapkan bahwa nilai diwujudkan dalam kaidah-kaidah perilaku utama. Oleh sebab itu, opsi D adalah jawaban yang tepat.</p><p>Jawaban tepat: <strong>D. Kaidah-kaidah perilaku utama dari setiap nilai</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan instrumen apresiasi seremonial yang bukan substansi perwujudan nilai.</li><li><strong>B</strong>: Merupakan artefak fisik seragam dinas yang sekadar penunjang tampilan kerja.</li><li><strong>C</strong>: Merupakan tata ruang fisik kantor yang tidak menggambarkan esensi perilaku moral.</li><li><strong>E</strong>: Merupakan urusan administrasi absensi lembur yang berada di luar substansi nilai.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 31) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>diwujudkan dalam kaidah-kaidah perilaku utama • keputusan kedua • kaidah-kaidah perilaku utama dari</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C3 - Aplikasi", "question": "Diktum ketiga KMK Nomor 312/KMK.01/2011 mewajibkan pelaksanaan tugas pokok berlandaskan nilai bagi...", "options": ["Pejabat struktural eselon satu dan eselon dua", "Setiap pimpinan dan seluruh Pegawai Negeri Sipil", "Pejabat fungsional pemeriksa dan auditor dinas", "Aparatur yang bertugas pada seksi pelayanan loket", "Seluruh agen perubahan di tingkat satuan kerja"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kewajiban Pelaksanaan Tugas Berdasar Nilai</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam keputusan ketiga KMK 312/2011 disebutkan bahwa <strong>setiap pimpinan dan seluruh Pegawai Negeri Sipil di lingkungan Kementerian Keuangan wajib melaksanakan tugas pokok dan fungsinya dengan mendasarkan pada nilai-nilai dan kaidah perilaku utama</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat karena kewajiban menjunjung tinggi nilai kementerian mengikat seluruh jajaran pegawai tanpa terkecuali, mulai dari pejabat tertinggi hingga pelaksana. Keterikatan universal ini menjamin terbentuknya budaya integritas yang merata di seluruh lini organisasi kementerian. Oleh karena itu, pimpinan dan seluruh PNS adalah subjek wajib pelaksanaan nilai.</p><p>Jawaban tepat: <strong>B. Setiap pimpinan dan seluruh Pegawai Negeri Sipil</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena kewajiban tidak terbatas pada pejabat eselon struktural saja, melainkan seluruh aparatur.</li><li><strong>C</strong>: Kurang tepat karena pejabat fungsional auditor merupakan salah satu rumpun jabatan di kementerian.</li><li><strong>D</strong>: Kurang tepat karena petugas pelayanan loket hanyalah sebagian dari seluruh aparatur sipil negara kementerian.</li><li><strong>E</strong>: Kurang tepat karena agen perubahan merupakan penggerak budaya, sedangkan kewajiban nilai mengikat semua.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 31) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>setiap pimpinan dan seluruh Pegawai Negeri Sipil • wajib melaksanakan tugas pokok dan</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Pelaksanaan Nilai-Nilai Kementerian Keuangan secara terpusat dikoordinasikan oleh unit eselon I...", "options": ["Inspektorat Jenderal Kementerian Keuangan", "Direktorat Jenderal Perbendaharaan Negara", "Direktorat Jenderal Anggaran Kementerian", "Badan Pendidikan dan Pelatihan Keuangan", "Sekretariat Jenderal Kementerian Keuangan"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Koordinator Pelaksanaan Nilai Kemenkeu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pelaksanaan Nilai-Nilai Kementerian Keuangan ini <strong>dikoordinasi oleh Sekretariat Jenderal Kementerian Keuangan</strong> dan dibantu oleh seluruh pimpinan unit Eselon I.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat untuk unit pengoordinasi utama nilai kementerian. Sebagai poros pembina manajemen kepegawaian dan organisasi, Sekretariat Jenderal bertugas mengawal harmonisasi serta keselarasan program nilai di semua unit. Arahan pimpinan menyebutkan secara tegas bahwa koordinasi pelaksanaan nilai dipegang oleh Sekretariat Jenderal. Maka dari itu, opsi E adalah unit koordinator yang tepat.</p><p>Jawaban tepat: <strong>E. Sekretariat Jenderal Kementerian Keuangan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan aparat pengawas intern pemerintah (APIP) yang bertugas mengaudit kepatuhan.</li><li><strong>B</strong>: Merupakan unit teknis pengelola kas dan perbendaharaan negara di kementerian.</li><li><strong>C</strong>: Merupakan unit teknis perumus alokasi dan kebijakan penganggaran belanja negara.</li><li><strong>D</strong>: Merupakan unit penyedia sarana pendidikan, pelatihan, dan pengembangan kompetensi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 31) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pelaksanaan Nilai-Nilai Kementerian Keuangan ini dikoordinasi oleh • Sekretariat Jenderal Kementerian Keuangan</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C3 - Aplikasi", "question": "Dalam mengoordinasikan pelaksanaan nilai, peran Sekretariat Jenderal dibantu secara aktif oleh...", "options": ["Lembaga swadaya masyarakat pemerhati tata kelola", "Konsultan manajemen independen dari pihak swasta", "Seluruh pimpinan unit Eselon I Kementerian Keuangan", "Organisasi kemahasiswaan perguruan tinggi mitra", "Aparat kepolisian dan kejaksaan di tingkat wilayah"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Peran Pimpinan Unit Eselon I dalam Internalisasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pelaksanaan nilai kementerian dikoordinasi oleh Sekretariat Jenderal dan <strong>dibantu oleh seluruh pimpinan unit Eselon I di Kementerian Keuangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat tentang sinergi struktural internalisasi nilai. Internalisasi tidak akan efektif jika hanya berjalan terpusat tanpa komitmen penuh dari para pimpinan unit eselon teknis di lapangan. Pedoman perilaku menerangkan bahwa koordinasi Sekjen dibantu oleh seluruh pimpinan unit Eselon I. Oleh sebab itu, opsi C adalah pihak pembantu yang digariskan aturan.</p><p>Jawaban tepat: <strong>C. Seluruh pimpinan unit Eselon I Kementerian Keuangan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan pemangku kepentingan masyarakat sipil luar yang bukan pelaksana struktural.</li><li><strong>B</strong>: Merupakan penyedia jasa konsultan eksternal yang berada di luar garis komando dinas.</li><li><strong>D</strong>: Merupakan mitra civitas akademika kampus yang bukan pengelola operasional kementerian.</li><li><strong>E</strong>: Merupakan penegak hukum pidana yang bukan pembantu teknis pembinaan budaya kerja.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 31) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Sekretariat Jenderal Kementerian Keuangan dan • dibantu oleh seluruh pimpinan unit</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Keputusan Menteri Keuangan Nomor 127/KMK.01/2013 secara khusus mengatur tentang...", "options": ["Program Budaya di lingkungan Kementerian Keuangan", "Tata cara pengadaan barang dan jasa pemerintah elektronik", "Pemberian sanksi pemecatan pegawai pelanggar disiplin", "Standar kompetensi jabatan struktural eselon dinas", "Pedoman penyusunan tarif perpajakan daerah terbaru"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Materi Muatan KMK Nomor 127/KMK.01/2013</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Keputusan Menteri Keuangan Nomor 127/KMK.01/2013</strong> mengatur tentang <strong>Program Budaya di lingkungan Kementerian Keuangan tahun 2013</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat untuk substansi KMK 127/2013. Regulasi ini menjadi payung hukum awal penjabaran nilai-nilai bersama ke dalam serangkaian pembiasaan positif harian (seperti 5 Must, 3S, dan disiplin waktu). Keputusan Menteri Keuangan KMK Nomor 127/KMK.01/2013 menetapkan program budaya di kementerian. Dengan demikian, opsi A adalah isi regulasi yang benar.</p><p>Jawaban tepat: <strong>A. Program Budaya di lingkungan Kementerian Keuangan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan regulasi pengadaan barang jasa yang diatur peraturan presiden khusus.</li><li><strong>C</strong>: Merupakan ketentuan penegakan hukum disipliner yang diatur dalam regulasi kepegawaian.</li><li><strong>D</strong>: Merupakan standar kompetensi manajerial yang termuat dalam peraturan kepegawaian lain.</li><li><strong>E</strong>: Merupakan regulasi teknis fiskal daerah yang bukan substansi nilai dan budaya kerja.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 31) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Keputusan Menteri Keuangan Nomor 127/KMK.01/2013 tentang • di lingkungan Kementerian Keuangan tahun</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Instruksi Menteri Keuangan Nomor 346/IMK.01/2017 diterbitkan dengan muatan pokok mengenai...", "options": ["Penerimaan calon pegawai negeri sipil formasi baru", "Pembentukan satgas khusus penanganan unjuk rasa", "Peningkatan tunjangan operasional pejabat pembuat komitmen", "Gerakan Efisiensi sebagai bagian penguatan budaya", "Pemberian amnesti denda keterlambatan pembayaran pajak"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Materi Muatan IMK Nomor 346/IMK.01/2017</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Instruksi Menteri Keuangan Nomor 346/IMK.01/2017</strong> mengatur tentang <strong>Gerakan Efisiensi sebagai bagian implementasi penguatan budaya Kementerian Keuangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat untuk muatan instruksi menteri tahun 2017. Menkeu menginstruksikan agar penguatan nilai tidak berhenti pada tataran retorika, melainkan diwujudkan dalam penghematan anggaran perjalanan dinas, rapat, dan operasional birokrasi. IMK Nomor 346/2017 menetapkan Gerakan Efisiensi dalam rangka penguatan budaya. Oleh sebab itu, opsi D merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>D. Gerakan Efisiensi sebagai bagian penguatan budaya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan pengadaan formasi ASN baru yang berada di bawah panitia seleksi nasional.</li><li><strong>B</strong>: Merupakan pengamanan unjuk rasa yang bukan ranah instruksi penguatan budaya menteri.</li><li><strong>C</strong>: Merupakan kenaikan tunjangan operasional yang bertentangan dengan semangat efisiensi.</li><li><strong>E</strong>: Merupakan kebijakan pengampunan pajak (tax amnesty) yang diatur undang-undang khusus.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 31) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Instruksi Menteri Keuangan Nomor 346/IMK.01/2017 tentang • sebagai bagian implementasi penguatan budaya</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C4 - Analisis", "question": "Dalam Surat Edaran Nomor SE-12/MK.1/2018, penerapan nilai dan kode etik difungsikan secara khusus sebagai...", "options": ["Materi tunggal ujian kenaikan pangkat kedinasan", "Early Warning System deteksi dini pelanggaran", "Instrumen evaluasi beban kerja harian pegawai", "Dasar penghitungan anggaran tunjangan hari raya", "Kriteria kelulusan pelatihan dasar prajabatan PNS"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Fungsi Nilai sebagai Early Warning System</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Berdasarkan <strong>Surat Edaran Nomor SE-12/MK.1/2018</strong>, penerapan nilai-nilai dan kode etik difungsikan sebagai <strong>Early Warning System</strong> guna mendeteksi secara dini potensi pelanggaran.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat. Surat Edaran Menteri Keuangan tersebut secara eksplisit memfungsikan pengawasan nilai dan kode etik sebagai sistem peringatan dini (Early Warning System) di unit kerja. Mekanisme ini dirancang untuk mencegah terjadinya penyimpangan sebelum menjadi pelanggaran disiplin yang lebih berat.</p><p>Jawaban tepat: <strong>B. Early Warning System deteksi dini pelanggaran</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Ujian dinas memiliki instrumen kurikulum evaluasi tersendiri.</li><li><strong>C</strong>: Beban kerja dinilai melalui instrumen analisis beban kerja.</li><li><strong>D</strong>: Tunjangan hari raya dihitung berdasarkan regulasi keuangan negara.</li><li><strong>E</strong>: Kelulusan pelatihan dasar mengacu pada pedoman kurikulum diklat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 31) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Early Warning System • SE-12/MK.1/2018</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Surat Edaran Nomor SE-12/MK.1/2018 terkait Early Warning System diterbitkan secara resmi oleh...", "options": ["Kepala Badan Kebijakan Fiskal Kemenkeu", "Kepala Badan Pendidikan Pelatihan Keuangan", "Direktur Jenderal Bea Cukai Kemenkeu", "Inspektur Jenderal Kementerian Keuangan", "Sekretaris Jenderal Kementerian Keuangan"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pejabat Penerbit Surat Edaran EWS</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Surat Edaran Nomor SE-12/MK.1/2018 diterbitkan oleh <strong>Sekretaris Jenderal Kementerian Keuangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat mengenai otoritas penerbit surat edaran tersebut. Kode penomoran naskah dinas \"SE-12/MK.1/2018\" secara administratif merujuk pada pimpinan Sekretariat Jenderal Kementerian Keuangan (kode MK.1). Surat edaran ini diterbitkan secara resmi oleh Sekretaris Jenderal. Oleh karena itu, opsi E adalah pejabat penerbit yang benar.</p><p>Jawaban tepat: <strong>E. Sekretaris Jenderal Kementerian Keuangan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan pimpinan unit analis fiskal makro yang memiliki kode tata naskah MK.8.</li><li><strong>B</strong>: Merupakan pimpinan lembaga antirasuah luar kementerian dengan yurisdiksi tersendiri.</li><li><strong>C</strong>: Merupakan pimpinan unit eselon I pengawasan kepabeanan dengan kode tata naskah BC.</li><li><strong>D</strong>: Merupakan pimpinan aparat pengawas internal kementerian dengan kode tata naskah IJ.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 31) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Surat Edaran Nomor SE-12/MK.1/2018 dari • Sekretaris Jenderal Kementerian</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Penguatan internalisasi Nilai-Nilai Kementerian Keuangan secara terstruktur dilakukan melalui wahana...", "options": ["Standardisasi kurikulum diklat kepemimpinan aparatur", "Evaluasi berkala terhadap peta proses bisnis kantor", "Penguatan budaya organisasi yang hidup dan dinamis", "Penyusunan kontrak kinerja tahunan berbasis output", "Optimalisasi sistem reward and punishment kepegawaian"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Wahana Penguatan Internalisasi Nilai</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Penguatan internalisasi Nilai-Nilai Kementerian Keuangan secara terstruktur dilakukan <strong>melalui Budaya Organisasi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat karena budaya kerja organisasi menyediakan wahana pembiasaan hal-hal positif serta internalisasi kaidah perilaku utama dalam aktivitas harian aparatur. Melalui budaya kerja yang sehat, nilai-nilai kementerian bertransformasi menjadi norma kerja yang mengakar secara alamiah. Dengan demikian, budaya organisasi adalah wahana penguatan internalisasi.</p><p>Jawaban tepat: <strong>C. Penguatan budaya organisasi yang hidup dan dinamis</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena kurikulum diklat kepemimpinan merupakan sarana pendidikan formal manajerial SDM.</li><li><strong>B</strong>: Kurang tepat karena evaluasi peta proses bisnis berfokus pada penyempurnaan alur tata kelola kerja.</li><li><strong>D</strong>: Kurang tepat karena penyusunan kontrak kinerja tahunan berkaitan dengan pengukuran target tugas individu.</li><li><strong>E</strong>: Kurang tepat karena sistem reward and punishment merupakan instrumen motivasi kedisiplinan pegawai.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 31) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Penguatan Internalisasi Nilai-Nilai Kementerian • melalui Budaya Organisasi</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Peraturan Menteri Keuangan Nomor 190/PMK.01/2018 diterbitkan dengan materi pokok mengatur tentang...", "options": ["Pemberian fasilitas rumah dinas pejabat eselon kementerian", "Kode Etik dan Kode Perilaku Pegawai Negeri Sipil Kemenkeu", "Tata cara pemotongan pajak penghasilan pasal dua puluh satu", "Penyelenggaraan pelatihan teknis kepabeanan dan cukai dinas", "Pedoman penyusunan rancangan undang-undang anggaran belanja"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Materi Muatan PMK Nomor 190/PMK.01/2018</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>PMK Nomor 190/PMK.01/2018</strong> mengatur tentang <strong>Kode Etik dan Kode Perilaku Pegawai Negeri Sipil di lingkungan Kementerian Keuangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat untuk substansi PMK 190/2018. Peraturan ini menjadi instrumen hukum utama yang mengikat seluruh pegawai dalam bersikap, berbicara, bertindak, serta menjaga martabat dan kehormatan korps Kemenkeu. Dalam prinsip dasar, PMK ini memuat kode etik dan kode perilaku PNS. Oleh sebab itu, opsi B merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>B. Kode Etik dan Kode Perilaku Pegawai Negeri Sipil Kemenkeu</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan pengelolaan fasilitas barang milik negara yang diatur ketentuan aset lain.</li><li><strong>C</strong>: Merupakan petunjuk teknis perpajakan PPh 21 yang termuat dalam peraturan perpajakan.</li><li><strong>D</strong>: Merupakan program kediklatan teknis yang diselenggarakan oleh BPPK.</li><li><strong>E</strong>: Merupakan siklus penyusunan nota keuangan APBN yang berada di ranah Ditjen Anggaran.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 32) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Peraturan Menteri Keuangan Republik Indonesia Nomor 190/PMK.01/2018 • Kode Etik dan Kode Perilaku</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Regulasi terkini mengenai Penguatan Budaya di lingkungan Kementerian Keuangan tertuang dalam...", "options": ["Instruksi Presiden Nomor 429 Tahun 2022", "Peraturan Pemerintah Nomor 429 Tahun 2022", "Surat Keputusan Bersama Menkeu Nomor 429 Tahun 2022", "Keputusan Menteri Keuangan Nomor 429/KMK.01/2022", "Peraturan Menteri Keuangan Nomor 429/PMK.01/2022"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Materi Muatan KMK Nomor 429/KMK.01/2022</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Keputusan Menteri Keuangan Nomor 429/KMK.01/2022</strong> mengatur tentang <strong>Penguatan Budaya di lingkungan Kementerian Keuangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat untuk payung hukum modern penguatan budaya kementerian. KMK ini menyelaraskan nilai-nilai kementerian dengan core values BerAKHLAK aparatur sipil negara melalui perumusan sikap dasar dan perilaku efektif. Regulasi pedoman teknis tersebut adalah Keputusan Menteri Keuangan Nomor 429/KMK.01/2022. Dengan demikian, opsi D adalah nomor regulasi yang benar.</p><p>Jawaban tepat: <strong>D. Keputusan Menteri Keuangan Nomor 429/KMK.01/2022</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan bentuk instruksi presiden yang berada di luar hierarki penetapan menteri.</li><li><strong>B</strong>: Merupakan regulasi tingkat pemerintah pusat yang bukan payung hukum internal Kemenkeu.</li><li><strong>C</strong>: Bukan bentuk surat keputusan bersama lintas kementerian dalam pedoman etika.</li><li><strong>E</strong>: Merupakan bentuk peraturan menteri (PMK); ketetapan ini berbentuk keputusan menteri (KMK).</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 32) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Keputusan Menteri Keuangan Nomor 429/KMK.01/2022 tentang • Budaya di lingkungan Kementerian Keuangan</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Petunjuk operasional lapangan pelaksanaan penguatan budaya kementerian diatur secara rinci dalam...", "options": ["Surat Edaran Nomor SE-15/MK.1/2022", "Surat Edaran Nomor SE-01/MK.1/2015", "Surat Edaran Nomor SE-20/MK.1/2019", "Surat Edaran Nomor SE-05/MK.1/2020", "Surat Edaran Nomor SE-10/MK.1/2021"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Materi Muatan SE-15/MK.1/2022</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Surat Edaran Nomor SE-15/MK.1/2022</strong> memuat <strong>Panduan Pelaksanaan Penguatan Budaya di lingkungan Kementerian Keuangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat mengenai naskah dinas panduan operasional budaya. SE ini memberikan pedoman rinci bagi para change agent dan seluruh unit kerja dalam menginternalisasikan sikap dasar serta perilaku efektif di lingkungan kantor. Keputusan Menteri Keuangan secara jelas menetapkan nomor SE-15/MK.1/2022 sebagai panduannya. Oleh karena itu, opsi A adalah nomor surat edaran yang tepat.</p><p>Jawaban tepat: <strong>A. Surat Edaran Nomor SE-15/MK.1/2022</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Bukan nomor surat edaran penguatan budaya kementerian yang tercantum dalam pedoman etika.</li><li><strong>C</strong>: Nomor edaran acak yang tidak memuat panduan penguatan budaya 2022.</li><li><strong>D</strong>: Bukan naskah dinas operasional budaya organisasi tahun 2022 di prinsip dasar.</li><li><strong>E</strong>: Bukan surat edaran panduan pelaksanaan budaya yang ditetapkan Sekretariat Jenderal.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 32) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Surat Edaran Nomor SE-15/MK.1/2022 tentang • Budaya di lingkungan Kementerian Keuangan</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C3 - Aplikasi", "question": "Program Budaya yang diatur dalam KMK Nomor 127/KMK.01/2013 wajib dilaksanakan oleh...", "options": ["Pejabat struktural pemegang wewenang otorisasi anggaran", "Pegawai yang bertugas pada bagian kepegawaian dan umum", "Seluruh Pegawai Negeri Sipil dan Calon Pegawai Negeri Sipil", "Tenaga analis kebijakan dan fungsional perbendaharaan", "Aparatur sipil negara di lingkungan kantor wilayah daerah"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sasaran Program Budaya dalam KMK 127/2013</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>KMK Nomor 127/KMK.01/2013 berisi tentang Program Budaya yang perlu dilaksanakan oleh <strong>seluruh Pegawai Negeri Sipil dan Calon Pegawai Negeri Sipil di lingkungan Kementerian Keuangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat karena pembiasaan etika kerja dan disiplin ditanamkan sejak dini kepada CPNS serta diwajibkan kepada seluruh PNS kementerian tanpa diskriminasi jabatan. Inklusivitas sasaran program budaya memastikan tidak ada satupun insan aparatur yang luput dari pembinaan karakter. Oleh sebab itu, seluruh PNS dan CPNS adalah sasaran wajib pelaksanaan program budaya.</p><p>Jawaban tepat: <strong>C. Seluruh Pegawai Negeri Sipil dan Calon Pegawai Negeri Sipil</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena pemegang otorisasi anggaran merupakan pejabat struktural perbendaharaan tertentu.</li><li><strong>B</strong>: Kurang tepat karena bagian kepegawaian merupakan unit pengelola administrasi SDM satuan kerja.</li><li><strong>D</strong>: Kurang tepat karena analis kebijakan dan fungsional merupakan kelompok jabatan fungsional keahlian.</li><li><strong>E</strong>: Kurang tepat karena kewajiban berlaku menyeluruh di kantor pusat maupun kantor vertikal daerah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 32) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>perlu dilaksanakan oleh seluruh • Calon Pegawai Negeri Sipil di</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C4 - Analisis", "question": "Pertimbangan filosofis dibentuknya program budaya kerja dalam pembiasaan etika kerja pegawai adalah...", "options": ["Penegakan disiplin waktu menjamin keteraturan tugas", "Penerapan tata tertib seragam memelihara citra korps", "Komitmen bersama pimpinan mempercepat perubahan pola kerja", "Pembentukan change agent memperkuat sinergi organisasi", "Kebaikan yang berulang-ulang akan menjadi suatu kebiasaan"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pertimbangan Filosofis Program Budaya</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pemikiran yang dijadikan pertimbangan adalah bahwa <strong>dengan melakukan kebaikan-kebaikan yang berulang, maka kegiatan yang dilakukan para pegawai akan menjadi kebiasaan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat karena pembentukan karakter aparatur tidak dapat terwujud instan, melainkan melalui proses pengulangan perilaku kebajikan secara konsisten setiap hari. Ketika perbuatan baik telah menjadi kebiasaan alamiah, integritas kerja akan otomatis terpelihara tanpa perlu pengawasan ketat. Dengan demikian, kebaikan berulang yang menjadi kebiasaan adalah filosofi dasarnya.</p><p>Jawaban tepat: <strong>E. Kebaikan yang berulang-ulang akan menjadi suatu kebiasaan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena keteraturan tugas merupakan salah satu manfaat teknis penegakan disiplin waktu.</li><li><strong>B</strong>: Kurang tepat karena memelihara citra korps merupakan dampak positif dari ketertiban pakaian seragam.</li><li><strong>C</strong>: Kurang tepat karena komitmen pimpinan merupakan faktor pendorong akselerasi transformasi birokrasi.</li><li><strong>D</strong>: Kurang tepat karena change agent merupakan instrumen penggerak budaya pada satuan kerja kantor.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 32) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>bahwa dengan melakukan kebaikan-kebaikan yang berulang • maka kegiatan yang dilakukan para pegawai akan menjadi kebiasaan</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Tiga komponen program budaya yang diatur secara resmi dalam KMK Nomor 127/KMK.01/2013 meliputi...", "options": ["Disiplin apel pagi, olahraga bersama, dan arisan kantor", "Disiplin waktu, disiplin berpakaian, dan penerapan 5 Must", "Ujian kompetensi, rotasi antar-wilayah, dan kenaikan gaji", "Pengurangan rapat, penghematan listrik, dan daur ulang arsip", "Pemasangan spanduk, pembacaan ikrar, dan senam kesegaran"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tiga Komponen Program Budaya KMK 127/2013</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Program budaya meliputi: (a) <strong>penerapan disiplin dalam waktu</strong>, (b) <strong>penerapan disiplin dalam berpakaian</strong>, dan (c) <strong>penerapan “5 Must”</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat untuk tiga pilar utama program budaya Kemenkeu tahun 2013. Ketiga komponen ini memadukan kepatuhan dasar waktu, kerapian seragam dinas identitas, serta lima pembiasaan harian yang terukur. Ketiga komponen penguatan budaya tersebut adalah disiplin waktu, berpakaian, dan 5 Must. Dengan demikian, opsi B adalah rincian resmi yang benar.</p><p>Jawaban tepat: <strong>B. Disiplin waktu, disiplin berpakaian, dan penerapan 5 Must</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan agenda informal yang bukan muatan tiga pilar resmi KMK 127/2013.</li><li><strong>C</strong>: Merupakan aspek manajemen kepegawaian berkala di luar program pembiasaan budaya.</li><li><strong>D</strong>: Merupakan gerakan efisiensi operasional kantor, bukan tiga pilar program budaya.</li><li><strong>E</strong>: Merupakan kegiatan seremonial yang tidak merangkum ketentuan 5 Must pedoman kerja.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 32) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>budaya yang diatur dalam KMK • Penerapan disiplin dalam waktu • Penerapan disiplin dalam berpakaian • Penerapan “5 Must”</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Butir pertama dalam penerapan program '5 Must' di Kementerian Keuangan adalah...", "options": ["One day one innovation dalam pelayanan publik", "One minute for safety dalam pelaksanaan tugas", "One hour one problem solving di ruang kerja", "One day one information (satu hari satu informasi)", "One team one spirit untuk meraih target kinerja"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Butir Pertama Program 5 Must</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Butir pertama dalam 5 Must adalah <strong>One day one information (satu hari satu informasi)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat untuk butir pembuka program pembiasaan 5 Must. Program ini mendorong transfer pengetahuan dan keterbukaan informasi antarsesama pegawai setiap hari kerja agar wawasan organisasi terus bertambah. Program penguatan mencantumkan butir nomor 1 adalah One day one information. Oleh sebab itu, opsi D adalah butir pertama yang benar.</p><p>Jawaban tepat: <strong>D. One day one information (satu hari satu informasi)</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Bukan butir resmi dalam 5 Must; istilah inovasi harian adalah variasi tidak resmi.</li><li><strong>B</strong>: Merupakan kaidah keselamatan kerja lapangan yang bukan butir 5 Must dalam pedoman etika.</li><li><strong>C</strong>: Bukan butir program pembiasaan yang tertuang dalam regulasi budaya Kemenkeu.</li><li><strong>E</strong>: Merupakan slogan motivasi kerja tim yang berada di luar ketentuan 5 Must.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 32) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>1.One day one information • (satu hari satu informasi)</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Ketentuan mengenai 'Two minutes before schedule' pada butir kedua program 5 Must bermakna...", "options": ["Hadir dua menit sebelum jadwal yang ditentukan", "Membaca risalah rapat dua menit sebelum dimulai", "Menyiapkan perlengkapan tugas dua menit lebih awal", "Mengonfirmasi kehadiran dinas dua menit sebelum apel", "Menyelesaikan disposisi surat dua menit sebelum tenggat"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Butir Kedua Program 5 Must</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Butir kedua program 5 Must adalah <strong>Two minutes before schedule (hadir dua menit sebelum jadwal)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat karena pembiasaan hadir lebih awal melatih kedisiplinan tinggi, kesiapan mental, serta bentuk penghormatan terhadap waktu rekan kerja dan pemangku kepentingan. Ketepatan waktu menjamin setiap agenda rapat maupun pelayanan dinas dapat dimulai persis sesuai jadwal. Oleh karena itu, hadir dua menit sebelum jadwal adalah makna harfiahnya.</p><p>Jawaban tepat: <strong>A. Hadir dua menit sebelum jadwal yang ditentukan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kurang tepat karena membaca risalah rapat merupakan langkah persiapan materi sebelum agenda dimulai.</li><li><strong>C</strong>: Kurang tepat karena menyiapkan perlengkapan tugas merupakan wujud kesiapan operasional kerja.</li><li><strong>D</strong>: Kurang tepat karena konfirmasi kehadiran dinas merupakan bagian dari tertib absensi kepegawaian.</li><li><strong>E</strong>: Kurang tepat karena penyelesaian disposisi surat berkaitan dengan ketepatan waktu tata persuratan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 32) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>2.Two minutes before schedule • (hadir dua menit sebelum jadwal)</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Butir ketiga dalam program '5 Must' mengatur pembiasaan sikap hubungan antarpegawai berupa...", "options": ["Sopan, Santun, dan Sabar dalam menerima keluhan", "Sigap, Solutif, dan Simpatik saat menolong rekan", "3S (Senyum, Salam, Sapa) dan 3 kali salam sehari", "Semangat, Sinergis, dan Sukses dalam bertugas", "Sehat, Sukacita, dan Sederhana dalam keseharian"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Butir Ketiga Program 5 Must</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Butir ketiga dalam 5 Must adalah <strong>3S (Senyum, Salam, Sapa) dan tiga kali salam dalam sehari</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat untuk butir ketiga etika sapaan harian di kantor. Senyum tulus dan tegur sapa menciptakan atmosfer kerja yang bersahabat, meruntuhkan ketegangan, dan meneguhkan saling percaya antarsesama pegawai. Pedoman kerja menerangkan butir nomor 3 adalah 3S (Senyum, Salam, Sapa) serta tiga kali salam dalam sehari. Oleh sebab itu, opsi C adalah rumusan yang benar.</p><p>Jawaban tepat: <strong>C. 3S (Senyum, Salam, Sapa) dan 3 kali salam sehari</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan akronim etika pelayanan umum yang bukan bunyi butir ketiga 5 Must.</li><li><strong>B</strong>: Merupakan sifat pelayanan yang tidak merangkum ketentuan salam tiga kali sehari.</li><li><strong>D</strong>: Merupakan semboyan motivasi kerja yang bukan butir program pembiasaan pedoman kerja.</li><li><strong>E</strong>: Merupakan pola hidup sehat sederhana yang tidak sesuai ketentuan resmi regulasi budaya.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 32) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>3.3S (Senyum, Salam, Sapa) • dan tiga kali salam dalam sehari</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Penerapan konsep tata kelola tempat kerja '5R' pada butir kelima program 5 Must merupakan kepanjangan dari...", "options": ["Responsif, Rasional, Relevan, Rendah hati, dan Ramah", "Rela, Rukun, Responsif, Rapi, dan Rendah hati", "Religius, Rapi, Rela berkorban, Rajin, dan Ringkas", "Rencana, Realisasi, Rekonsiliasi, Respon, dan Rilis", "Ringkas, rapi, resik, rawat, dan rajin di tempat kerja"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Butir Kelima Program 5 Must (5R)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Butir kelima dalam 5 Must adalah <strong>5R (ringkas, rapi, resik, rawat, rajin)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat untuk kepanjangan resmi prinsip 5R di tempat kerja. Konsep manajemen lingkungan kerja yang diadaptasi dari metode *5S* ini melatih pegawai menjaga keteraturan arsip, kebersihan meja, kenyamanan ruangan, dan disiplin perawatan sarana kerja. Program 5R terdiri dari ringkas, rapi, resik, rawat, dan rajin. Dengan demikian, opsi E adalah kepanjangan yang benar.</p><p>Jawaban tepat: <strong>E. Ringkas, rapi, resik, rawat, dan rajin di tempat kerja</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan deretan kata sifat kepribadian yang bukan kepanjangan 5R tempat kerja.</li><li><strong>B</strong>: Merupakan penggabungan kata moralitas yang tidak mewakili kaidah tata letak ruang.</li><li><strong>C</strong>: Merupakan istilah campuran yang tidak sesuai dengan istilah resmi 5R di kementerian.</li><li><strong>D</strong>: Merupakan siklus pembukuan anggaran perbendaharaan, bukan konsep 5R tempat kerja.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 32) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>5.5R • (ringkas, rapi, resik, rawat, rajin)</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Simbol fisik Nilai-Nilai Kementerian Keuangan dipasang pada area yang sering dilewati pegawai seperti...", "options": ["Pintu gerbang kantor, koridor, dinding, dan meja kerja", "Ruang sidang pimpinan, perpustakaan, dan aula utama", "Ruang arsip berkas, pos penjagaan, dan tempat parkir", "Loket pelayanan umum, ruang tunggu, dan serambi depan", "Ruang rapat koordinasi, laboratorium komputer, dan teras"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Titik Pemasangan Simbol Fisik Nilai</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Simbol-simbol Nilai-Nilai Kementerian dipasang di tempat-tempat yang sering dilewati pegawai seperti di <strong>pintu gerbang kantor, koridor kantor, dinding ruang kerja, meja kerja</strong>, dan sebagainya.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat karena penempatan simbol nilai pada titik-titik lintasan harian pegawai berfungsi sebagai pengingat visual yang terus-menerus terhadap komitmen kode etik kementerian. Kekerapan melihat simbol nilai memperkuat proses internalisasi nilai secara bawah sadar dalam bekerja. Dengan demikian, pintu gerbang, koridor, dinding, dan meja kerja adalah lokasi pemasangannya.</p><p>Jawaban tepat: <strong>A. Pintu gerbang kantor, koridor, dinding, dan meja kerja</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kurang tepat karena perpustakaan dan aula utama merupakan area penunjang kegiatan tertentu saja.</li><li><strong>C</strong>: Kurang tepat karena ruang arsip dan pos penjagaan bukan merupakan titik utama perlintasan seluruh pegawai.</li><li><strong>D</strong>: Kurang tepat karena ruang tunggu loket pelayanan lebih ditujukan bagi masyarakat publik pengguna jasa.</li><li><strong>E</strong>: Kurang tepat karena laboratorium komputer dan teras merupakan fasilitas penunjang teknis kantor.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 33) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>simbol-simbol Nilai-Nilai Kementerian yang dipasang • pintu gerbang kantor, koridor kantor,</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C3 - Aplikasi", "question": "Variasi bentuk simbol nilai yang digunakan di lingkungan kantor kementerian meliputi media...", "options": ["Papan pengumuman berkas, brosur cetak, dan majalah dinding", "Spanduk kain, stiker inventaris dinas, dan kartu pegawai", "Running text, baliho, standing tripod, dan sinyal web", "Banner digital interaktif, kalender kerja, dan plakat kayu", "Booklet panduan saku, selebaran dinas, dan papan petunjuk"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Ragam Bentuk Simbol Internalisasi Nilai</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Simbol-simbol nilai dibuat dalam berbagai bentuk seperti <strong>running text, baliho, tulisan-tulisan yang disangga dengan tripod, hingga signal-signal yang muncul di halaman utama web instansi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat karena pemanfaatan beragam media visual modern baik cetak maupun digital memastikan pesan nilai kementerian tersampaikan secara menarik dan dinamis. Kreativitas bentuk simbol menghindarkan pegawai dari kejenuhan visual serta memperkuat daya ingat pesan nilai. Oleh sebab itu, running text, baliho, tripod, dan notifikasi web adalah variasinya.</p><p>Jawaban tepat: <strong>C. Running text, baliho, standing tripod, dan sinyal web</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena majalah dinding dan papan pengumuman merupakan media informasi konvensional kantor.</li><li><strong>B</strong>: Kurang tepat karena stiker inventaris dinas merupakan tanda registrasi barang milik negara satker.</li><li><strong>D</strong>: Kurang tepat karena plakat kayu dan kalender kerja merupakan suvenir kedinasan operasional tahunan.</li><li><strong>E</strong>: Kurang tepat karena booklet panduan saku merupakan dokumen pegangan fisik ketentuan kedinasan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 33) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>berbentuk running text, baliho, tulisan-tulisan • signal-signal yang muncul di halaman utama web instansi</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Dalam penyelenggaraan In House Training (IHT) nilai, narasumber eksternal unit paling sering dihadirkan dari...", "options": ["Badan Pemeriksa Keuangan Republik Indonesia", "Lembaga Administrasi Negara Republik Indonesia", "Badan Kepegawaian Negara Kantor Regional", "Otoritas Jasa Keuangan Bidang Edukasi Konsumen", "Badan Pendidikan dan Pelatihan Keuangan (BPPK)"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Narasumber Utama In House Training (IHT)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Narasumber IHT paling sering berasal dari <strong>Badan Pendidikan dan Pelatihan Keuangan (BPPK)</strong>, karena memang <strong>tugas pokok dan fungsinya memberikan pelatihan-pelatihan semacam itu</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat untuk unit penyedia narasumber pelatihan internal kementerian. BPPK memiliki keahlian kurikulum kediklatan, fasilitator tersertifikasi, dan penguasaan mendalam atas substansi nilai-nilai Kemenkeu. Narasumber pembinaan internalisasi secara resmi dihadirkan dari BPPK. Maka dari itu, opsi E adalah unit eselon I yang dimaksud dalam pembinaan nilai.</p><p>Jawaban tepat: <strong>E. Badan Pendidikan dan Pelatihan Keuangan (BPPK)</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan lembaga pemeriksa eksternal negara di luar struktur Kementerian Keuangan.</li><li><strong>B</strong>: Merupakan lembaga pembina kediklatan aparatur nasional di luar Kemenkeu.</li><li><strong>C</strong>: Merupakan instansi pengelola kepegawaian nasional yang bukan unit internal kementerian.</li><li><strong>D</strong>: Merupakan otoritas independen pengawas sektor jasa keuangan luar kementerian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 33) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>paling sering berasal dari Badan • tugas pokok dan fungsinya memberikan</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Kombinasi agenda internalisasi nilai pada akhirnya diharapkan mampu bermuara pada...", "options": ["Pemberian gelar kehormatan purna tugas kepada pimpinan", "Peningkatan kinerja individu pegawai dan organisasi", "Pengurangan jumlah pegawai fungsional di unit daerah", "Penghapusan seluruh mekanisme pelaporan kinerja tertulis", "Penyatuan seluruh gedung kantor eselon di satu wilayah"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuan Akhir Agenda Internalisasi Nilai</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dengan terinternalisasinya nilai-nilai menjadi budaya organisasi, diharapkan akan <strong>meningkatkan kinerja individu pegawai dan juga kinerja organisasi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat mengenai dampak makro keberhasilan budaya organisasi. Internalisasi nilai bukan sekadar formalitas kegiatan, melainkan pengungkit utama efektivitas kerja, integritas aparatur, dan pencapaian target strategis kelembagaan. Pedoman kerja menyebutkan secara tegas peningkatan kinerja individu dan organisasi sebagai hasil akhirnya. Oleh sebab itu, opsi B adalah tujuan akhir yang tepat.</p><p>Jawaban tepat: <strong>B. Peningkatan kinerja individu pegawai dan organisasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan penghargaan seremonial pensiun yang bukan target pembinaan budaya.</li><li><strong>C</strong>: Merupakan perampingan formasi jabatan yang berada di luar ranah sasaran internalisasi.</li><li><strong>D</strong>: Merupakan pengabaian akuntabilitas laporan yang menyimpang dari tata kelola yang baik.</li><li><strong>E</strong>: Merupakan konsolidasi fisik bangunan kantor yang tidak menentukan budaya kerja batin.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 33) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>nilai-nilai dapat terinternalisasi pada setiap • kinerja individu pegawai dan juga</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Tahun penerbitan Instruksi Menteri Keuangan mengenai Gerakan Efisiensi dalam penguatan budaya adalah...", "options": ["Tahun 2013", "Tahun 2015", "Tahun 2016", "Tahun 2017", "Tahun 2019"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tahun Peluncuran Gerakan Efisiensi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Di tahun 2017</strong>, dilaksanakan penguatan Nilai-Nilai Kementerian Keuangan dengan terbitnya <strong>Instruksi Menteri Keuangan Nomor 346/IMK.01/2017 tentang Gerakan Efisiensi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat untuk tahun penetapan instruksi menteri tersebut. Tahun 2017 menjadi titik penting saat nilai-nilai kementerian dihubungkan langsung dengan penghematan anggaran belanja birokrasi dan akuntabilitas fiskal aparatur. Secara fungsional, instruksi tersebut terbit pada tahun 2017. Dengan demikian, tahun 2017 adalah angka kronologis yang benar.</p><p>Jawaban tepat: <strong>D. Tahun 2017</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan tahun penetapan KMK 127/KMK.01/2013 tentang Program Budaya.</li><li><strong>B</strong>: Bukan tahun terbitnya instruksi gerakan efisiensi menteri keuangan dalam pedoman etika.</li><li><strong>C</strong>: Tahun sebelum penerbitan IMK 346/2017 yang belum mencakup ketentuan ini.</li><li><strong>E</strong>: Bukan tahun penetapan instruksi kebijakan efisiensi belanja di Kementerian Keuangan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 33) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Di tahun 2017, dilaksanakan penguatan • Instruksi Menteri Keuangan Nomor 346/IMK.01/2017</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Gerakan Efisiensi sebagai bagian implementasi penguatan budaya kementerian dituangkan dalam regulasi bernomor...", "options": ["Instruksi Menteri Keuangan Nomor 346/IMK.01/2017", "Keputusan Menteri Keuangan Nomor 346/KMK.01/2017", "Peraturan Menteri Keuangan Nomor 346/PMK.01/2017", "Surat Edaran Sekretaris Jenderal Nomor 346/2017", "Peraturan Direktur Jenderal Perbendaharaan Nomor 346/2017"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Nomor Regulasi Gerakan Efisiensi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Gerakan Efisiensi dituangkan dalam <strong>Instruksi Menteri Keuangan Nomor 346/IMK.01/2017</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat untuk nomenklatur dan nomor regulasi gerakan efisiensi. Bentuk naskah dinas ini adalah Instruksi Menteri Keuangan (IMK) yang memuat perintah langsung kepada jajaran pimpinan dan pengelola anggaran. Pedoman etika mencantumkan secara resmi nomor 346/IMK.01/2017. Oleh karena itu, opsi A adalah nama dokumen yang benar.</p><p>Jawaban tepat: <strong>A. Instruksi Menteri Keuangan Nomor 346/IMK.01/2017</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan bentuk Keputusan Menteri Keuangan (KMK); regulasi ini berbentuk Instruksi (IMK).</li><li><strong>C</strong>: Merupakan bentuk Peraturan Menteri Keuangan (PMK); dokumen ini berupa Instruksi Menteri.</li><li><strong>D</strong>: Merupakan surat edaran eselon satu yang berada di bawah tingkat instruksi menteri.</li><li><strong>E</strong>: Merupakan peraturan teknis direktur jenderal yang berada di bawah level kebijakan menteri.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 33) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Instruksi Menteri Keuangan Nomor 346/IMK.01/2017 • Efisiensi sebagai bagian implementasi penguatan</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Instruksi pertama Menteri Keuangan dalam IMK Nomor 346/IMK.01/2017 memerintahkan pegawai untuk...", "options": ["Menyusun rencana aksi penghematan belanja satuan kerja", "Mengoptimalkan pemanfaatan aset sarana prasarana dinas", "Mendorong perubahan mindset, pola kerja, dan spirit bertugas", "Mengembangkan inovasi digital dalam alur administrasi kerja", "Melakukan evaluasi berkala terhadap efektivitas anggaran unit"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Instruksi Pertama Menteri Keuangan dalam IMK 346/2017</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Instruksi pertama IMK Nomor 346/IMK.01/2017 adalah mengimplementasikan dan menginternalisasikan Nilai-Nilai Kementerian Keuangan dan <strong>mendorong perubahan mindset, pola kerja, dan spirit dalam pelaksanaan tugas</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat karena efisiensi birokrasi sejati harus diawali dari pembaruan pola pikir (mindset) dan semangat kerja aparatur dari pola lama yang boros menuju tata kerja yang produktif dan hemat. Perubahan sikap mental menjadi fondasi utama keberhasilan penghematan anggaran negara. Dengan demikian, pembaruan mindset, pola kerja, dan spirit adalah instruksi pertama.</p><p>Jawaban tepat: <strong>C. Mendorong perubahan mindset, pola kerja, dan spirit bertugas</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena penyusunan rencana aksi penghematan merupakan tahapan perencanaan teknis satker.</li><li><strong>B</strong>: Kurang tepat karena optimalisasi aset sarana prasarana merupakan mandat teknis pengelolaan barang dinas.</li><li><strong>D</strong>: Kurang tepat karena pengembangan inovasi digital tata kelola kerja merupakan wujud nilai Kesempurnaan.</li><li><strong>E</strong>: Kurang tepat karena evaluasi efektivitas anggaran unit merupakan fungsi pengawasan akuntabilitas kas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 33) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Nilai-Nilai Kementerian Keuangan • mendorong perubahan mindset, pola kerja,</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C4 - Analisis", "question": "Inti perintah pada instruksi kedua dan ketiga Menteri Keuangan dalam IMK Nomor 346/IMK.01/2017 adalah...", "options": ["Penambahan kuota pengadaan pegawai dan mobil dinas", "Kenaikan pagu anggaran konsumsi rapat pimpinan unit", "Pemberian dana hibah kepada organisasi kepemudaan", "Pembangunan gedung kantor baru di setiap ibu kota", "Efisiensi pelaksanaan tugas dan efisiensi anggaran birokrasi"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Instruksi Kedua dan Ketiga dalam IMK 346/2017</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Instruksi Kedua: <strong>melaksanakan upaya-upaya efisiensi pelaksanaan tugas</strong>; dan Instruksi Ketiga: <strong>melaksanakan upaya-upaya efisiensi anggaran birokrasi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat mengenai sasaran ganda efisiensi dalam IMK 346/2017. Efisiensi tidak hanya ditujukan pada besaran rupiah anggaran yang dibelanjakan (anggaran birokrasi), tetapi juga pada efektivitas proses dan waktu dalam pelaksanaan tugas negara. Arahan Menkeu menetapkan kedua instruksi tersebut secara berurutan. Oleh sebab itu, opsi E merangkum substansi kedua poin tersebut.</p><p>Jawaban tepat: <strong>E. Efisiensi pelaksanaan tugas dan efisiensi anggaran birokrasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan penambahan belanja sarana mobil yang bertentangan dengan semangat efisiensi.</li><li><strong>B</strong>: Merupakan pemborosan dana konsumsi yang justru menjadi target pengetatan efisiensi.</li><li><strong>C</strong>: Merupakan pos belanja bantuan sosial/hibah yang bukan substansi efisiensi internal.</li><li><strong>D</strong>: Merupakan pembangunan fisik bernilai besar yang dikendalikan secara ketat dalam anggaran.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 33) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>2.Kedua: melaksanakan upaya-upaya efisiensi pelaksanaan tugas • 3.Ketiga: melaksanakan upaya-upaya efisien anggaran birokrasi</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C3 - Aplikasi", "question": "Dalam instruksi keempat IMK Nomor 346/IMK.01/2017, pelaksanaan Gerakan Efisiensi di unit kerja dimotori oleh...", "options": ["Petugas keamanan gedung dinas kementerian", "Pengoptimalan peran change agent agen perubahan", "Auditor investigasi eksternal badan pemeriksa", "Pengurus serikat pekerja tenaga kontrak dinas", "Tim kuasa hukum pembela perkara peradilan"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Peran Change Agent dalam Gerakan Efisiensi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Instruksi Keempat: menyusun dan melaksanakan Gerakan Efisiensi pada unit organisasi masing-masing <strong>dengan mengoptimalkan peran change agent</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat tentang agen penggerak budaya hemat di lingkungan unit kerja. Para agen perubahan (*change agent*) bertugas menjadi teladan, mengampanyekan perilaku efisien, serta mendorong rekan sekerja meninggalkan kebiasaan boros operasional kantor. Optimalisasi peran sangat penting bagi optimalisasi change agent. Dengan demikian, opsi B adalah pihak penggerak yang diinstruksikan.</p><p>Jawaban tepat: <strong>B. Pengoptimalan peran change agent agen perubahan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan petugas ketertiban fisik gedung yang bukan pelopor budaya efisiensi kerja.</li><li><strong>C</strong>: Merupakan pengaudit luar instansi yang bertindak saat pemeriksaan berkala.</li><li><strong>D</strong>: Merupakan wadah tenaga kerja alih daya yang berada di luar peran agen budaya ASN.</li><li><strong>E</strong>: Merupakan advokat hukum litigasi yang menangani sengketa peradilan kantor.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 33) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>4.Keempat: menyusun dan melaksanakan Gerakan • dengan mengoptimalkan peran change agent</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Pihak-pihak di lingkungan kementerian yang menerima instruksi dalam IMK Nomor 346/IMK.01/2017 mencakup...", "options": ["Pimpinan bank komersial dan perusahaan asuransi rekanan", "Kepala dinas pendapatan daerah tingkat provinsi saja", "Gubernur dan bupati di seluruh wilayah Republik Indonesia", "Wamenkeu, Pimpinan Satker, PPK, dan Pejabat Fungsional/staf", "Menteri koordinator bidang perekonomian kabinet kerja"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pihak yang Menerima Instruksi Gerakan Efisiensi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Menteri Keuangan menginstruksikan kepada <strong>Wakil Menteri Keuangan, Pimpinan Satuan Kerja Eselon II/III/IV, Pejabat Pembuat Komitmen, dan Pejabat Fungsional/pegawai</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat untuk jajaran penerima instruksi menteri tersebut. Efisiensi anggaran menuntut keterlibatan seluruh rantai komando pengelolaan kas, mulai dari penentu kebijakan pimpinan satker, pejabat pembuat komitmen (PPK) yang berkontrak, hingga pegawai pelaksana di lapangan. Komitmen integritas melibatkan seluruh pihak tersebut secara lengkap. Oleh sebab itu, opsi D adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>D. Wamenkeu, Pimpinan Satker, PPK, dan Pejabat Fungsional/staf</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan entitas bisnis perbankan komersial luar kementerian.</li><li><strong>B</strong>: Merupakan perangkat daerah pemerintah provinsi di luar hierarki internal Kemenkeu.</li><li><strong>C</strong>: Merupakan kepala daerah otonom yang bukan bawahan instruksi menteri keuangan.</li><li><strong>E</strong>: Merupakan menteri koordinator yang berkedudukan di atas kementerian teknis.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 33) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Menteri Keuangan menginstruksikan kepada wakil • Pimpinan Satuan Kerja Eselon II/III/IV,</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Penguatan nilai kementerian tidak sekadar jargon seremonial, melainkan mengerucut pada karakteristik peran sebagai...", "options": ["Penyelenggara pesta demokrasi pemilihan umum", "Penegak ketertiban keamanan perbatasan laut", "Pengelola keuangan negara dalam berbagai aspeknya", "Penyedia sarana transportasi darat antarkota", "Pengawas stabilitas perdagangan komoditas ekspor"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Hakikat Substansi Gerakan Efisiensi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Penguatan nilai-nilai tidak sekadar sebuah jargon atau yel-yel dalam forum, melainkan <strong>langsung mengerucut pada karakteristik tugas pokok dan fungsi di Kementerian Keuangan, yakni sebagai pengelola keuangan negara dalam berbagai aspeknya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat mengenai esensi peran institusi kementerian. Integritas dan efisiensi pengeluaran kas bersentuhan langsung dengan amanah mengelola uang rakyat demi kemakmuran bangsa. Penguatan nilai-nilai kementerian nilai terfokus pada jati diri instansi sebagai pengelola keuangan negara. Dengan demikian, opsi C adalah rumusan peran yang benar.</p><p>Jawaban tepat: <strong>C. Pengelola keuangan negara dalam berbagai aspeknya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan tugas konstitusional komisi pemilihan umum (KPU) Republik Indonesia.</li><li><strong>B</strong>: Merupakan fungsi aparat penjaga laut dan pantai (KPLP) atau TNI Angkatan Laut.</li><li><strong>D</strong>: Merupakan wewenang teknis Kementerian Perhubungan dalam transportasi publik.</li><li><strong>E</strong>: Merupakan mandat kelembagaan Kementerian Perdagangan dalam urusan niaga.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 34) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>penguatan nilai-nilai tidak sekadar sebuah • mengerucut pada karakterisik tugas pokok</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C3 - Aplikasi", "question": "Pelaksanaan upaya efisiensi tugas di lingkungan kementerian diawasi secara melekat oleh...", "options": ["Inspektorat Jenderal dan Kepala/Petugas UKI", "Badan Intelijen Negara dan kepolisian daerah", "Asosiasi konsultan pengadaan barang nasional", "Panitia pengawas pemilu tingkat kementerian", "Lembaga perlindungan saksi dan korban negara"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengawas Pelaksanaan Upaya Efisiensi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pelaksanaan instruksi efisiensi <strong>akan selalu diawasi oleh Inspektorat Jenderal Kementerian Keuangan dan Kepala/Petugas Unit Kepatuhan Internal (instruksi ketujuh)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat tentang garda pengawasan kepatuhan di Kemenkeu. Pengawasan berjenjang melalui Unit Kepatuhan Internal (lini kedua) dan Inspektorat Jenderal (lini ketiga) menjamin akuntabilitas penghematan anggaran berjalan konsisten di setiap satker. Dalam pedoman etika, kedua pihak inilah yang mengawasi instruksi efisiensi. Maka dari itu, opsi A adalah pengawas yang benar.</p><p>Jawaban tepat: <strong>A. Inspektorat Jenderal dan Kepala/Petugas UKI</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan aparat intelijen dan keamanan pidana luar struktur kementerian.</li><li><strong>C</strong>: Merupakan organisasi profesi pengadaan swasta yang bukan aparat pengawas dinas.</li><li><strong>D</strong>: Merupakan instrumen kepemiluan yang tidak relevan dengan audit efisiensi kantor.</li><li><strong>E</strong>: Merupakan lembaga perlindungan saksi pidana yang berada di luar ranah pengawasan ini.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 34) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pelaksanaan instruksi untuk melakukan upaya-upaya • Inspektorat Jenderal Kementerian Keuangan dan</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Instruksi Menteri Keuangan memberikan penekanan khusus kepada Pejabat Pembuat Komitmen (PPK) agar...", "options": ["Mempercepat proses lelang pengadaan barang secara terpadu", "Menyusun analisis standar biaya masukan satuan kerja", "Melakukan rekonsiliasi data realisasi perbendaharaan kas", "Mengoptimalkan penyerapan belanja operasional kantor", "Mengelola anggaran pada satker secara efektif, efisien, dan beretika"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Instruksi Khusus untuk Pejabat Pembuat Komitmen</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Instruksi ini menekankan kepada para Pejabat Pembuat Komitmen agar <strong>mengelola anggaran pada satuan kerja masing-masing dengan efektif, efisien, dan beretika</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat karena PPK memegang peranan krusial sebagai pengambil keputusan komitmen belanja negara yang wajib mengedepankan integritas moral dan kehati-hatian fiskal. Tata kelola belanja yang efektif dan beretika memastikan setiap rupiah uang rakyat digunakan secara akuntabel bagi kepentingan publik. Oleh sebab itu, pengelolaan anggaran efektif, efisien, dan beretika adalah instruksinya.</p><p>Jawaban tepat: <strong>E. Mengelola anggaran pada satker secara efektif, efisien, dan beretika</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena percepatan proses lelang pengadaan merupakan tugas pejabat pengadaan barang jasa.</li><li><strong>B</strong>: Kurang tepat karena analisis standar biaya masukan disusun oleh unit perencana anggaran kementerian.</li><li><strong>C</strong>: Kurang tepat karena rekonsiliasi realisasi kas merupakan tugas pejabat penatausahaan keuangan kantor.</li><li><strong>D</strong>: Kurang tepat karena optimalisasi penyerapan belanja harus tetap memperhatikan prinsip efisiensi mutu.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 34) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menekankan pada instruksi untuk para Pejabat Pembuat Komitmen agar • anggaran pada satuan kerja masing-masing</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Himbauan pertama yang ditujukan kepada setiap pegawai dalam SE-12/MK.1/2018 adalah...", "options": ["Meningkatkan kompetensi teknis melalui pelatihan berkala", "Memegang teguh Nilai-Nilai Kementerian Keuangan", "Mengoptimalkan koordinasi kerja sama lintas direktorat", "Menjaga standar mutu pelayanan publik secara konsisten", "Menerapkan prinsip efisiensi anggaran dalam pelaksanaan tugas"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Himbauan Pertama bagi Pegawai dalam SE-12/2018</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Himbauan pertama kepada setiap pegawai Kementerian Keuangan dalam SE-12/MK.1/2018 adalah <strong>memegang teguh Nilai-Nilai Kementerian Keuangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat karena memegang teguh nilai kementerian berfungsi sebagai sistem deteksi dini (Early Warning System) dalam membentengi pegawai dari perbuatan menyimpang. Nilai kementerian menjadi kompas moral dasar yang mengarahkan sikap dan integritas aparatur dalam berbagai situasi. Dengan demikian, memegang teguh nilai kementerian adalah butir himbauan pertama.</p><p>Jawaban tepat: <strong>B. Memegang teguh Nilai-Nilai Kementerian Keuangan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena peningkatan kompetensi berkala merupakan pilar peningkatan nilai Profesionalisme.</li><li><strong>C</strong>: Kurang tepat karena optimalisasi koordinasi lintas direktorat merupakan perwujudan dari nilai Sinergi.</li><li><strong>D</strong>: Kurang tepat karena menjaga standar mutu pelayanan merupakan fokus pelaksanaan dari nilai Pelayanan.</li><li><strong>E</strong>: Kurang tepat karena penerapan efisiensi anggaran merupakan fokus instruksi pada Gerakan Efisiensi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 34) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>1. Himbauan kepada setiap pegawai Kementerian Keuangan untuk • Memegang teguh Nilai-Nilai Kementerian Keuangan</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Dalam SE-12/MK.1/2018, pegawai dihimbau senantiasa berpedoman pada etika dalam empat ranah, yaitu...", "options": ["Berbangsa, berkelembagaan, berkarier, dan berkepribadian", "Berorganisasi, berkepemimpinan, beretika, dan bertoleransi", "Bernegara, berintegritas, berakuntabilitas, dan bersinergi", "Bernegara, berpemerintahan, berorganisasi, dan bermasyarakat", "Berpemerintahan, berdemokrasi, berkorporasi, dan berbudaya"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Empat Ranah Etika Pegawai Kemenkeu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai dihimbau berpedoman pada <strong>etika dalam bernegara, dalam penyelenggaraan pemerintahan, dalam berorganisasi, dan dalam bermasyarakat</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat karena keempat ruang lingkup tersebut mencakup seluruh dimensi kehidupan aparatur sipil negara baik sebagai abdi negara, bagian dari birokrasi, insan korps, maupun warga masyarakat. Penjagaan etika di empat ranah ini memastikan integritas aparatur tetap terpelihara secara komprehensif. Oleh karena itu, bernegara, berpemerintahan, berorganisasi, dan bermasyarakat adalah empat ranahnya.</p><p>Jawaban tepat: <strong>D. Bernegara, berpemerintahan, berorganisasi, dan bermasyarakat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena berbangsa dan berkepribadian merupakan pembagian umum konsep etika sosial.</li><li><strong>B</strong>: Kurang tepat karena berkepemimpinan dan bertoleransi merupakan dimensi keterampilan interpersonal.</li><li><strong>C</strong>: Kurang tepat karena berintegritas dan bersinergi merupakan butir nama nilai kementerian, bukan ranah etika.</li><li><strong>E</strong>: Kurang tepat karena berdemokrasi dan berkorporasi merupakan terminologi politik dan tata kelola swasta.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 34) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Berpedoman pada etika dalam bernegara, dalam penyelenggaraan pemerintahan • dalam berorganisasi, dan dalam bermasyarakat</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C3 - Aplikasi", "question": "Salah satu larangan penting dalam himbauan pegawai pada SE-12/MK.1/2018 adalah keharusan menghindari...", "options": ["Perilaku yang mengarah pada ujaran kebencian dan radikalisme", "Sikap eksklusivisme dalam pelaksanaan koordinasi tugas dinas", "Keterlambatan dalam penyampaian laporan pertanggungjawaban kas", "Tindakan mengambil keputusan dinas tanpa telaah komprehensif", "Penggunaan sarana kantor di luar ketentuan standar operasional"], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Larangan Khusus Pegawai dalam SE-12/2018</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai dihimbau untuk <strong>menghindari perilaku-perilaku yang mengarah pada ujaran kebencian dan radikalisme</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat karena ujaran kebencian dan paham radikal merusak persatuan bangsa, mencederai netralitas ASN, serta bertentangan secara mutlak dengan ideologi Pancasila dan UUD 1945. Penjagaan sikap aparatur dari paparan radikalisme menjaga marwah kementerian sebagai pilar NKRI. Dengan demikian, penghindaran ujaran kebencian dan radikalisme adalah larangannya.</p><p>Jawaban tepat: <strong>A. Perilaku yang mengarah pada ujaran kebencian dan radikalisme</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kurang tepat karena sikap eksklusivisme bertentangan dengan prinsip koordinasi pada nilai Sinergi.</li><li><strong>C</strong>: Kurang tepat karena keterlambatan laporan kas berkaitan dengan ketaatan jadwal perbendaharaan dinas.</li><li><strong>D</strong>: Kurang tepat karena keputusan tanpa telaah komprehensif berkaitan dengan kehati-hatian manajerial.</li><li><strong>E</strong>: Kurang tepat karena penggunaan sarana kantor diatur dalam ketentuan tata tertib perlengkapan aset.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 34) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Menghindari perilaku-perilaku yang mengarah pada • ujaran kebencian dan</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C3 - Aplikasi", "question": "Kepada para atasan langsung pegawai, butir pertama himbauan SE-12/MK.1/2018 menginstruksikan untuk...", "options": ["Mengoordinasikan pembagian beban tugas kerja secara proporsional", "Memberikan teladan nyata dalam penyelesaian target kinerja", "Membina, memantau, dan mengawasi bawahan di lingkungan kerjanya", "Menyusun instrumen evaluasi kompetensi fungsional staf kantor", "Menyelenggarakan forum bimbingan teknis berkala bagi pelaksana"], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tanggung Jawab Pembinaan Atasan Langsung</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Himbauan kepada atasan langsung adalah <strong>membina, memantau, dan melakukan pengawasan kepada bawahannya di lingkungan kerjanya dalam rangka mencegah terjadinya pelanggaran</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat karena atasan langsung bertanggung jawab menjaga moralitas dan kedisiplinan staf melalui pengawasan melekat secara berkelanjutan. Pembinaan proaktif mampu mencegah potensi pelanggaran disiplin dan kode etik sebelum menjadi kasus fatal di satker. Oleh karena itu, membina, memantau, dan mengawasi bawahan adalah instruksi utamanya.</p><p>Jawaban tepat: <strong>C. Membina, memantau, dan mengawasi bawahan di lingkungan kerjanya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena pembagian beban tugas proporsional merupakan teknik manajerial pembagian kerja tim.</li><li><strong>B</strong>: Kurang tepat karena keteladanan penyelesaian target kinerja merupakan kepemimpinan berbasis hasil kerja.</li><li><strong>D</strong>: Kurang tepat karena penyusunan instrumen evaluasi kompetensi merupakan wujud tugas manajemen SDM kantor.</li><li><strong>E</strong>: Kurang tepat karena forum bimbingan teknis berkala merupakan kegiatan transfer pengetahuan kedinasan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 34) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>2. Himbauan kepada para atasan langsung pegawai untuk • Membina, memantau, dan melakukan pengawasan</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C4 - Analisis", "question": "Bila terdapat aduan, temuan, atau laporan pelanggaran bawahan, tindakan yang wajib diambil atasan langsung adalah...", "options": ["Meneruskan berkas aduan langsung ke instansi penegak hukum", "Meminta rekomendasi tindakan administratif kepada badan kepegawaian", "Mengoordinasikan telaah hukum dengan bagian advokasi kementerian", "Menyampaikan laporan pendahuluan kepada pimpinan kantor wilayah", "Melakukan pemeriksaan atas dugaan pelanggaran bawahan"], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kewajiban Pemeriksaan Dugaan Pelanggaran Bawahan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Atasan langsung dihimbau untuk <strong>melakukan pemeriksaan atas dugaan pelanggaran yang dilakukan bawahannya jika terdapat aduan, temuan dan laporan pihak terkait</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat karena penanganan dugaan pelanggaran memerlukan langkah klarifikasi dan pemeriksaan objektif dari atasan langsung guna menguji kebenaran materiil laporan. Sikap tegas dan terukur menjamin keadilan bagi pegawai bersangkutan sekaligus menegakkan wibawa regulasi disiplin. Dengan demikian, melakukan pemeriksaan atas dugaan pelanggaran adalah kewajibannya.</p><p>Jawaban tepat: <strong>E. Melakukan pemeriksaan atas dugaan pelanggaran bawahan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena aduan disiplin internal wajib diperiksa berjenjang terlebih dahulu sebelum ke aparat luar.</li><li><strong>B</strong>: Kurang tepat karena rekomendasi badan kepegawaian dimintakan setelah selesainya pemeriksaan internal satker.</li><li><strong>C</strong>: Kurang tepat karena koordinasi advokasi hukum dilakukan bila menyangkut sengketa peradilan tata usaha.</li><li><strong>D</strong>: Kurang tepat karena laporan pendahuluan disampaikan setelah adanya pemeriksaan faktual awal di lapangan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 34) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Melakukan pemeriksaan atas dugaan pelanggaran yang dilakukan bawahannya • jika terdapat aduan, temuan dan laporan pihak terkait</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C3 - Aplikasi", "question": "Terkait temuan aparat pemeriksa, atasan langsung pegawai wajib secara sigap untuk...", "options": ["Melakukan verifikasi ulang terhadap seluruh bukti temuan audit", "Menindaklanjuti hasil pemeriksaan Inspektur Jenderal atau UKI", "Menyusun telaah sanggahan teknis bersama tim auditor internal", "Mengoordinasikan perbaikan prosedur operasional baku satker", "Meminta pendampingan hukum kepada biro hukum kementerian"], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tindak Lanjut Hasil Pemeriksaan Pengawas</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Atasan langsung wajib <strong>menindaklanjuti hasil pemeriksaan yang dilakukan oleh Inspektur Jenderal atau Unit Kepatuhan Internal</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat karena rekomendasi hasil pengawasan aparat internal (Itjen/UKI) bersifat mengikat untuk segera dieksekusi demi perbaikan tata kelola birokrasi dan penegakan akuntabilitas. Tindak lanjut yang cepat mencegah berulangnya penyimpangan serupa di masa depan. Oleh sebab itu, menindaklanjuti hasil pemeriksaan adalah kewajiban atasan langsung.</p><p>Jawaban tepat: <strong>B. Menindaklanjuti hasil pemeriksaan Inspektur Jenderal atau UKI</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena verifikasi bukti temuan merupakan bagian dari proses audit yang telah diselesaikan auditor.</li><li><strong>C</strong>: Kurang tepat karena sanggahan teknis diajukan saat exit meeting audit sebelum laporan resmi diterbitkan.</li><li><strong>D</strong>: Kurang tepat karena perbaikan prosedur operasional merupakan salah satu bentuk aksi tindak lanjut kerja.</li><li><strong>E</strong>: Kurang tepat karena pendampingan biro hukum dilakukan dalam konteks gugatan pihak ketiga peradilan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 34) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Menindaklanjuti hasil pemeriksaan yang dilakukan oleh • Inspektur Jenderal atau</strong></div></div>"}, {"category": "NNK", "sub": "Nilai-Nilai Kemenkeu • Paket 03", "difficulty": "C4 - Analisis", "question": "Dalam SE-12/MK.1/2018, setiap pimpinan unit memegang tanggung jawab penuh terhadap tiga pilar tata kelola, yaitu...", "options": ["Perencanaan strategis, akuntabilitas kinerja, dan reformasi birokrasi", "Pengorganisasian tim, pengawasan melekat, dan evaluasi berkala", "Integritas jabatan, efisiensi belanja, dan standardisasi pelayanan", "Tata kelola, manajemen risiko, dan pengendalian intern unit", "Pembinaan aparatur, penataan kelembagaan, dan keselamatan kerja"], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tanggung Jawab Pimpinan Unit dalam Tata Kelola</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pimpinan unit dihimbau untuk <strong>bertanggung jawab terhadap tata kelola, manajemen risiko, dan pengendalian intern</strong> dalam menjalankan tugas pokok dan fungsinya.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat karena integrasi antara tata kelola yang baik, mitigasi risiko yang matang, serta pengawasan intern yang andal menjadi benteng pertahanan organisasi modern Kementerian Keuangan. Ketiga pilar ini menjamin pencapaian target strategis secara aman dan akuntabel. Dengan demikian, tata kelola, manajemen risiko, dan pengendalian intern adalah tanggung jawab pimpinan unit.</p><p>Jawaban tepat: <strong>D. Tata kelola, manajemen risiko, dan pengendalian intern unit</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena perencanaan strategis dan reformasi merupakan dimensi makro kelembagaan instansi.</li><li><strong>B</strong>: Kurang tepat karena pengorganisasian dan evaluasi merupakan fungsi manajemen operasional kepemimpinan.</li><li><strong>C</strong>: Kurang tepat karena integritas dan efisiensi merupakan prinsip moral dan operasional pelaksanaan tugas.</li><li><strong>E</strong>: Kurang tepat karena pembinaan aparatur dan penataan kelembagaan merupakan fungsi tata pamong kepegawaian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan (Hlm. 34) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>3. Himbauan kepada setiap pimpinan unit untuk • Bertanggung jawab terhadap tata kelola,</strong></div></div>"}];
let current=0;const answers=new Array(questions.length).fill(null);const letters=["A","B","C","D","E"];let seconds=65*60;let submitted=false;


// Anotasi hanya dalam pembahasan. Satu set coretan untuk setiap nomor soal.
const ANNO_KEY='stangolden_kemenkeu_p03_review_annot_v1_user_<?= (int)$USER->id ?>';
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
 try{localStorage.setItem('stg_nk_p03_map_visible',show?'1':'0')}catch(e){}
}
try{if(localStorage.getItem('stg_nk_p03_map_visible')==='0')toggleQuestionMap(false)}catch(e){}
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

  if(modalTitle) modalTitle.textContent="🏆 Lihat Ranking Paket 03";
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
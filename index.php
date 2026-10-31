<?php
// Render runs behind an HTTPS proxy. Configure the session cookie so the
// browser sends the same session back on the login POST request.
if (session_status() !== PHP_SESSION_ACTIVE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string)$_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');

    $sessionDir = sys_get_temp_dir() . '/campuspro_sessions';
    if (!is_dir($sessionDir)) {
        @mkdir($sessionDir, 0700, true);
    }
    if (is_dir($sessionDir) && is_writable($sessionDir)) {
        session_save_path($sessionDir);
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', $isHttps ? '1' : '0');
    ini_set('session.cookie_samesite', 'Lax');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
date_default_timezone_set('Asia/Kolkata');
// Show a useful local diagnostic instead of a silent blank page for unexpected PHP/database errors.
set_exception_handler(function (Throwable $ex) {
    http_response_code(500);
    $message = htmlspecialchars($ex->getMessage(), ENT_QUOTES, 'UTF-8');
    echo '<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>CampusPro error</title><style>body{font:15px Arial;background:#f5f7fc;color:#1c2745;padding:30px}.card{max-width:760px;margin:5vh auto;background:white;padding:28px;border-radius:16px;border:1px solid #e5e9f2}pre{white-space:pre-wrap;overflow-wrap:anywhere;background:#f8f9fd;padding:12px}</style><div class="card"><h1>CampusPro could not finish loading</h1><p>A PHP or database error stopped this page from rendering. The details below are intended for local troubleshooting.</p><pre>'.$message.'</pre><p>Check PHP 8.1+, enable <code>pdo_sqlite</code> and <code>sqlite3</code>, and ensure the folder is writable by PHP.</p></div></html>';
    exit;
});
$base = __DIR__;
$dbFile = $base . '/campus.db';
@mkdir($base.'/uploads/content',0775,true); @mkdir($base.'/uploads/fees',0775,true);
function e($v){ return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
function go($hash=''){ header('Location: index.php'.$hash); exit; }
function flash($msg,$type='success'){ $_SESSION['flash'][]=['msg'=>$msg,'type'=>$type]; }
function getFlash(){ $a=$_SESSION['flash']??[]; unset($_SESSION['flash']); return $a; }
function csrf(){ if(empty($_SESSION['_csrf'])) $_SESSION['_csrf']=bin2hex(random_bytes(24)); return $_SESSION['_csrf']; }
function verifyCsrf(){ if($_SERVER['REQUEST_METHOD']==='POST' && !hash_equals($_SESSION['_csrf']??'',$_POST['_csrf']??'')){ http_response_code(400); exit('Security token missing or expired. Refresh and try again.'); } }
function db(){ global $pdo; return $pdo; }
try { $pdo=new PDO('sqlite:'.$dbFile); $pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION); $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC); $pdo->exec('PRAGMA foreign_keys=ON'); }
catch(Throwable $ex){
    http_response_code(500);
    $hasPdo = extension_loaded('PDO');
    $hasSqlite = extension_loaded('pdo_sqlite');
    $hasSqlite3 = extension_loaded('sqlite3');
    $detail = htmlspecialchars($ex->getMessage(), ENT_QUOTES, 'UTF-8');
    echo '<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>CampusPro setup check</title>';
    echo '<style>body{margin:0;background:#f5f7fc;color:#1c2745;font:15px Arial,sans-serif}.wrap{max-width:760px;margin:7vh auto;padding:0 18px}.card{background:#fff;border:1px solid #e5e9f2;border-radius:16px;padding:28px;box-shadow:0 10px 32px #1d2c530d}h1{margin-top:0}li{margin:10px 0}.pill{display:inline-block;border-radius:8px;padding:7px 10px;background:#fff0f3;color:#b83250;font-weight:bold}.ok{background:#eaf7f0;color:#167653}code{background:#f1f3f9;padding:2px 5px;border-radius:4px}pre{white-space:pre-wrap;overflow-wrap:anywhere;background:#f8f9fd;padding:12px;border-radius:8px}</style><div class="wrap"><div class="card"><h1>CampusPro setup check</h1>';
    echo '<p>The page could not connect to its database. This diagnostic screen replaces a blank page so you can see what needs fixing.</p>';
    echo '<ul><li>PDO: <span class="'.($hasPdo?'pill ok':'pill').'">'.($hasPdo?'Enabled':'Missing').'</span></li><li>PDO SQLite: <span class="'.($hasSqlite?'pill ok':'pill').'">'.($hasSqlite?'Enabled':'Missing').'</span></li><li>SQLite3: <span class="'.($hasSqlite3?'pill ok':'pill').'">'.($hasSqlite3?'Enabled':'Missing').'</span></li></ul>';
    echo '<h2>How to fix on XAMPP</h2><ol><li>Open XAMPP Control Panel and click <b>Config</b> next to Apache, then open <code>php.ini</code>.</li><li>Find <code>;extension=pdo_sqlite</code> and <code>;extension=sqlite3</code>. Remove the leading semicolon from both lines.</li><li>Save the file and restart Apache.</li><li>Open <code>http://localhost/StudentCampusPro/</code> again.</li></ol><p><b>Technical detail:</b></p><pre>'.$detail.'</pre><p>If those extension lines do not exist, check that Apache is using the PHP installation whose <code>php.ini</code> you edited.</p></div></div></html>';
    exit;
}
$pdo->exec("CREATE TABLE IF NOT EXISTS users(id INTEGER PRIMARY KEY AUTOINCREMENT,username TEXT UNIQUE NOT NULL,password_hash TEXT NOT NULL,role TEXT NOT NULL CHECK(role IN ('admin','faculty','student')),full_name TEXT NOT NULL,email TEXT DEFAULT '',student_code TEXT DEFAULT '',department TEXT DEFAULT '',address TEXT DEFAULT '',phone TEXT DEFAULT '',created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)");
$pdo->exec("CREATE TABLE IF NOT EXISTS subjects(id INTEGER PRIMARY KEY AUTOINCREMENT,code TEXT UNIQUE NOT NULL,name TEXT NOT NULL,semester TEXT DEFAULT '',faculty_id INTEGER,FOREIGN KEY(faculty_id) REFERENCES users(id) ON DELETE SET NULL)");
$pdo->exec("CREATE TABLE IF NOT EXISTS attendance(id INTEGER PRIMARY KEY AUTOINCREMENT,student_id INTEGER NOT NULL,subject_id INTEGER NOT NULL,class_date TEXT NOT NULL,status TEXT NOT NULL CHECK(status IN ('Present','Absent','Late')),marked_by INTEGER,UNIQUE(student_id,subject_id,class_date),FOREIGN KEY(student_id) REFERENCES users(id) ON DELETE CASCADE,FOREIGN KEY(subject_id) REFERENCES subjects(id) ON DELETE CASCADE,FOREIGN KEY(marked_by) REFERENCES users(id) ON DELETE SET NULL)");
$pdo->exec("CREATE TABLE IF NOT EXISTS results(id INTEGER PRIMARY KEY AUTOINCREMENT,student_id INTEGER NOT NULL,subject_id INTEGER NOT NULL,exam_name TEXT NOT NULL,marks REAL NOT NULL,max_marks REAL NOT NULL,term TEXT DEFAULT '',updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(student_id) REFERENCES users(id) ON DELETE CASCADE,FOREIGN KEY(subject_id) REFERENCES subjects(id) ON DELETE CASCADE)");
$pdo->exec("CREATE TABLE IF NOT EXISTS fees(id INTEGER PRIMARY KEY AUTOINCREMENT,student_id INTEGER NOT NULL,fee_month TEXT NOT NULL,amount REAL NOT NULL,status TEXT NOT NULL DEFAULT 'Pending' CHECK(status IN ('Pending','Paid','Under Review')),receipt_filename TEXT DEFAULT '',note TEXT DEFAULT '',updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(student_id) REFERENCES users(id) ON DELETE CASCADE)");
$pdo->exec("CREATE TABLE IF NOT EXISTS resources(id INTEGER PRIMARY KEY AUTOINCREMENT,title TEXT NOT NULL,subject_id INTEGER NOT NULL,resource_type TEXT NOT NULL CHECK(resource_type IN ('E-content','Syllabus')),filename TEXT NOT NULL,original_filename TEXT NOT NULL,uploaded_by INTEGER,description TEXT DEFAULT '',created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(subject_id) REFERENCES subjects(id) ON DELETE CASCADE,FOREIGN KEY(uploaded_by) REFERENCES users(id) ON DELETE SET NULL)");
$pdo->exec("CREATE TABLE IF NOT EXISTS messages(id INTEGER PRIMARY KEY AUTOINCREMENT,sender_id INTEGER NOT NULL,recipient_role TEXT NOT NULL DEFAULT 'admin',subject TEXT NOT NULL,body TEXT NOT NULL,status TEXT NOT NULL DEFAULT 'Open' CHECK(status IN ('Open','In Progress','Resolved')),reply TEXT DEFAULT '',created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(sender_id) REFERENCES users(id) ON DELETE CASCADE)");
if(!$pdo->query("SELECT id FROM users WHERE username='admin'")->fetch()) { $st=$pdo->prepare('INSERT INTO users(username,password_hash,role,full_name,email) VALUES(?,?,?,?,?)'); $st->execute(['admin',password_hash('Admin@123',PASSWORD_DEFAULT),'admin','System Administrator','admin@example.edu']); }
if((int)$pdo->query('SELECT COUNT(*) FROM subjects')->fetchColumn()===0){$s=$pdo->prepare('INSERT INTO subjects(code,name,semester) VALUES(?,?,?)');foreach([['CS101','Programming Fundamentals','Semester 1'],['MA102','Applied Mathematics','Semester 1'],['EC103','Digital Electronics','Semester 1']] as $r)$s->execute($r);}
function verifyStoredPassword($password, $stored){
    if (password_verify($password, $stored)) return true;
    // Compatibility with Werkzeug's PBKDF2-SHA256 hashes, if the source site used that method.
    if (preg_match('/^pbkdf2:sha256:(\d+)\$([^$]+)\$([a-f0-9]+)$/i', $stored, $m)) {
        $candidate = hash_pbkdf2('sha256', $password, $m[2], (int)$m[1], strlen($m[3]) / 2, false);
        return hash_equals(strtolower($m[3]), strtolower($candidate));
    }
    return false;
}
function currentUser(){ if(empty($_SESSION['user_id']))return null; $s=db()->prepare('SELECT * FROM users WHERE id=?');$s->execute([$_SESSION['user_id']]);return $s->fetch()?:null; }
function needLogin(){ if(!currentUser())go('?page=login'); }
function needRole(...$roles){ needLogin(); if(!in_array(currentUser()['role'],$roles,true)){http_response_code(403);exit('You do not have permission to view this page.');} }
function rows($sql,$args=[]){$s=db()->prepare($sql);$s->execute($args);return $s->fetchAll();}
function one($sql,$args=[]){$s=db()->prepare($sql);$s->execute($args);return $s->fetch();}
function postv($k,$d=''){return trim((string)($_POST[$k]??$d));}
function safeUpload($field,$folder,$allowed){ if(empty($_FILES[$field]['tmp_name'])||!is_uploaded_file($_FILES[$field]['tmp_name']))return null; $orig=basename($_FILES[$field]['name']);$ext=strtolower(pathinfo($orig,PATHINFO_EXTENSION));if(!in_array($ext,$allowed,true))return false;if($_FILES[$field]['size']>16*1024*1024)return false;$name=bin2hex(random_bytes(12)).'_'.preg_replace('/[^A-Za-z0-9._-]/','_', $orig);if(!move_uploaded_file($_FILES[$field]['tmp_name'],__DIR__.'/uploads/'.$folder.'/'.$name))return false;return ['name'=>$name,'original'=>$orig]; }
verifyCsrf();
$action=$_GET['action']??'';
if($_SERVER['REQUEST_METHOD']==='POST'){
 try {
  if($action==='login'){ $u=postv('username');$p=(string)($_POST['password']??'');$row=one('SELECT * FROM users WHERE username=?',[$u]);if($row&&verifyStoredPassword($p,$row['password_hash'])){session_regenerate_id(true);$_SESSION['user_id']=$row['id'];$_SESSION['_csrf']=bin2hex(random_bytes(24));flash('Welcome back, '.$row['full_name'].'!');go();}flash('Invalid user ID or password. Please try again.','error');go('?page=login'); }
  if($action==='logout'){session_destroy();session_start();flash('You have been signed out.','info');go('?page=login');}
  needLogin(); $user=currentUser();
  switch($action){
   case 'create_user': needRole('admin'); $name=postv('full_name');$role=postv('role');$email=postv('email');$username=postv('new_username');$password=(string)($_POST['new_password']??'');if(!$name||!filter_var($email,FILTER_VALIDATE_EMAIL)||!in_array($role,['student','faculty'],true)||!preg_match('/^[A-Za-z0-9._-]{4,32}$/',$username)||strlen($password)<8)throw new Exception('Enter valid account details. User ID must be 4–32 characters and password at least 8 characters.');$s=$pdo->prepare('INSERT INTO users(username,password_hash,role,full_name,email,student_code,department,address,phone) VALUES(?,?,?,?,?,?,?,?,?)');$s->execute([$username,password_hash($password,PASSWORD_DEFAULT),$role,$name,$email,postv('student_code'),postv('department'),postv('address'),postv('phone')]);flash('Account created for '.$name.'. User ID: '.$username.' · Password: '.$password.' — save it now; it will not be shown again.');go('#accounts');
   case 'create_subject': needRole('admin');$s=$pdo->prepare('INSERT INTO subjects(code,name,semester,faculty_id) VALUES(?,?,?,?)');$s->execute([strtoupper(postv('code')),postv('name'),postv('semester'),postv('faculty_id')?:null]);flash('Subject added to the catalogue.');go('#subjects');
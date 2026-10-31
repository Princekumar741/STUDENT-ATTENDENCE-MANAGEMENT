<?php

// Render runs behind an HTTPS proxy. Configure the session cookie so the

// browser sends the same session back on the login POST request.

if (session_status() !== PHP_SESSION_ACTIVE) {

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')

        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string)$_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');



    $sessionDir = (getenv('CAMPUSPRO_DATA_DIR') ?: sys_get_temp_dir()) . '/campuspro_sessions';

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
$dataDir = getenv('CAMPUSPRO_DATA_DIR') ?: $base;
if (!is_dir($dataDir)) { @mkdir($dataDir, 0775, true); }
$dbFile = rtrim($dataDir, '/\\') . '/campus.db';
@mkdir($dataDir.'/uploads/content',0775,true);
@mkdir($dataDir.'/uploads/fees',0775,true);



function e($v){

    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');

}

function go($hash=''){

    header('Location: index.php'.$hash);

    exit;

}

function flash($msg,$type='success'){

    $_SESSION['flash'][]=['msg'=>$msg,'type'=>$type];

}

function getFlash(){

    $a=$_SESSION['flash']??[];

    unset($_SESSION['flash']);

    return $a;

}

function csrf(){

    if(empty($_SESSION['_csrf'])){

        $_SESSION['_csrf']=bin2hex(random_bytes(24));

    }

    return $_SESSION['_csrf'];

}

function verifyCsrf(){

    if($_SERVER['REQUEST_METHOD']==='POST'){

        $sessionToken = $_SESSION['_csrf'] ?? '';

        $formToken = $_POST['_csrf'] ?? '';



        if(

            !is_string($sessionToken) ||

            !is_string($formToken) ||

            $sessionToken === '' ||

            $formToken === '' ||

            !hash_equals($sessionToken, $formToken)

        ){

            http_response_code(400);

            exit('Security token missing or expired. Refresh and try again.');

        }

    }

}

function db(){

    global $pdo;

    return $pdo;

}



try {

    $pdo=new PDO('sqlite:'.$dbFile);

    $pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);

    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);

    $pdo->exec('PRAGMA foreign_keys=ON');

}

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

    echo '<h2>How to fix on XAMPP</h2><ol><li>Open XAMPP Control Panel and click <b>Config</b> next to Apache, then open <code>php.ini</code>.</li><li>Find <code>;extension=pdo_sqlite</code> and <code>;extension=sqlite3</code>. Remove the leading semicolon from both lines.</li><li>Save the file and restart Apache.</li><li>Open <code>http\://localhost/StudentCampusPro/</code> again.</li></ol><p><b>Technical detail:</b></p><pre>'.$detail.'</pre><p>If those extension lines do not exist, check that Apache is using the PHP installation whose <code>php.ini</code> you edited.</p></div></div></html>';

    exit;

}

$pdo->exec("CREATE TABLE IF NOT EXISTS users(id INTEGER PRIMARY KEY AUTOINCREMENT,username TEXT UNIQUE NOT NULL,password_hash TEXT NOT NULL,role TEXT NOT NULL CHECK(role IN ('admin','faculty','student')),full_name TEXT NOT NULL,email TEXT DEFAULT '',student_code TEXT DEFAULT '',department TEXT DEFAULT '',address TEXT DEFAULT '',phone TEXT DEFAULT '',created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)");

$pdo->exec("CREATE TABLE IF NOT EXISTS subjects(id INTEGER PRIMARY KEY AUTOINCREMENT,code TEXT UNIQUE NOT NULL,name TEXT NOT NULL,semester TEXT DEFAULT '',faculty_id INTEGER,FOREIGN KEY(faculty_id) REFERENCES users(id) ON DELETE SET NULL)");

$pdo->exec("CREATE TABLE IF NOT EXISTS attendance(id INTEGER PRIMARY KEY AUTOINCREMENT,student_id INTEGER NOT NULL,subject_id INTEGER NOT NULL,class_date TEXT NOT NULL,status TEXT NOT NULL CHECK(status IN ('Present','Absent','Late')),marked_by INTEGER,UNIQUE(student_id,subject_id,class_date),FOREIGN KEY(student_id) REFERENCES users(id) ON DELETE CASCADE,FOREIGN KEY(subject_id) REFERENCES subjects(id) ON DELETE CASCADE,FOREIGN KEY(marked_by) REFERENCES users(id) ON DELETE SET NULL)");

$pdo->exec("CREATE TABLE IF NOT EXISTS results(id INTEGER PRIMARY KEY AUTOINCREMENT,student_id INTEGER NOT NULL,subject_id INTEGER NOT NULL,exam_name TEXT NOT NULL,marks REAL NOT NULL,max_marks REAL NOT NULL,term TEXT DEFAULT '',updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(student_id) REFERENCES users(id) ON DELETE CASCADE,FOREIGN KEY(subject_id) REFERENCES subjects(id) ON DELETE CASCADE)");

$pdo->exec("CREATE TABLE IF NOT EXISTS fees(id INTEGER PRIMARY KEY AUTOINCREMENT,student_id INTEGER NOT NULL,fee_month TEXT NOT NULL,amount REAL NOT NULL,status TEXT NOT NULL DEFAULT 'Pending' CHECK(status IN ('Pending','Paid','Under Review')),receipt_filename TEXT DEFAULT '',note TEXT DEFAULT '',updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(student_id) REFERENCES users(id) ON DELETE CASCADE)");

$pdo->exec("CREATE TABLE IF NOT EXISTS resources(id INTEGER PRIMARY KEY AUTOINCREMENT,title TEXT NOT NULL,subject_id INTEGER NOT NULL,resource_type TEXT NOT NULL CHECK(resource_type IN ('E-content','Syllabus')),filename TEXT NOT NULL,original_filename TEXT NOT NULL,uploaded_by INTEGER,description TEXT DEFAULT '',created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(subject_id) REFERENCES subjects(id) ON DELETE CASCADE,FOREIGN KEY(uploaded_by) REFERENCES users(id) ON DELETE SET NULL)");

$pdo->exec("CREATE TABLE IF NOT EXISTS messages(id INTEGER PRIMARY KEY AUTOINCREMENT,sender_id INTEGER NOT NULL,recipient_role TEXT NOT NULL DEFAULT 'admin',subject TEXT NOT NULL,body TEXT NOT NULL,status TEXT NOT NULL DEFAULT 'Open' CHECK(status IN ('Open','In Progress','Resolved')),reply TEXT DEFAULT '',created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(sender_id) REFERENCES users(id) ON DELETE CASCADE)");



if(!$pdo->query("SELECT id FROM users WHERE username='admin'")->fetch()) {

    $st=$pdo->prepare('INSERT INTO users(username,password_hash,role,full_name,email) VALUES(?,?,?,?,?)');

    $st->execute(['admin',password_hash('Admin@123',PASSWORD_DEFAULT),'admin','System Administrator','admin@example.edu']);

}

if((int)$pdo->query('SELECT COUNT(*) FROM subjects')->fetchColumn()===0){

    $s=$pdo->prepare('INSERT INTO subjects(code,name,semester) VALUES(?,?,?)');

    foreach([['CS101','Programming Fundamentals','Semester 1'],['MA102','Applied Mathematics','Semester 1'],['EC103','Digital Electronics','Semester 1']] as $r)$s->execute($r);

}



function verifyStoredPassword($password, $stored){

    if (password_verify($password, $stored)) return true;

    if (preg_match('/^pbkdf2:sha256:(\d+)\$([^$]+)\$([a-f0-9]+)$/i', $stored, $m)) {

        $candidate = hash_pbkdf2('sha256', $password, $m[2], (int)$m[1], strlen($m[3]) / 2, false);

        return hash_equals(strtolower($m[3]), strtolower($candidate));

    }

    return false;

}

function currentUser(){

    if(empty($_SESSION['user_id']))return null;

    $s=db()->prepare('SELECT * FROM users WHERE id=?');

    $s->execute([$_SESSION['user_id']]);

    return $s->fetch()?:null;

}

function needLogin(){

    if(!currentUser())go('?page=login');

}

function needRole(...$roles){

    needLogin();

    if(!in_array(currentUser()['role'],$roles,true)){

        http_response_code(403);

        exit('You do not have permission to view this page.');

    }

}

function rows($sql,$args=[]){

    $s=db()->prepare($sql);

    $s->execute($args);

    return $s->fetchAll();

}

function one($sql,$args=[]){

    $s=db()->prepare($sql);

    $s->execute($args);

    return $s->fetch();

}

function postv($k,$d=''){

    return trim((string)($_POST[$k]??$d));

}

function safeUpload($field,$folder,$allowed){

    global $dataDir;

    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) return null;
    if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK) return false;
    if (empty($_FILES[$field]['tmp_name']) || !is_uploaded_file($_FILES[$field]['tmp_name'])) return false;

    $orig=basename($_FILES[$field]['name']);

    $ext=strtolower(pathinfo($orig,PATHINFO_EXTENSION));

    if(!in_array($ext,$allowed,true))return false;

    if($_FILES[$field]['size']>16*1024*1024)return false;

    $name=bin2hex(random_bytes(12)).'_'.preg_replace('/[^A-Za-z0-9._-]/','_', $orig);

    $destinationDir = $dataDir.'/uploads/'.$folder;
    if (!is_dir($destinationDir) && !mkdir($destinationDir, 0775, true) && !is_dir($destinationDir)) return false;

    if(!move_uploaded_file($_FILES[$field]['tmp_name'],$destinationDir.'/'.$name))return false;

    return ['name'=>$name,'original'=>$orig];

}



verifyCsrf();

$action=$_GET['action']??'';



if($_SERVER['REQUEST_METHOD']==='POST'){

 try {

  if($action==='login'){

    $u=postv('username');

    $p=(string)($_POST['password']??'');

    $row=one('SELECT * FROM users WHERE username=?',[$u]);

    if($row&&verifyStoredPassword($p,$row['password_hash'])){

        session_regenerate_id(true);

        $_SESSION['user_id']=$row['id'];

        $_SESSION['_csrf']=bin2hex(random_bytes(24));

        flash('Welcome back, '.$row['full_name'].'!');

        go();

    }

    flash('Invalid user ID or password. Please try again.','error');

    go('?page=login');

  }

  if($action==='logout'){

    session_destroy();

    session_start();

    flash('You have been signed out.','info');

    go('?page=login');

  }



  needLogin();

  $user=currentUser();



  switch($action){

   case 'create_user':

    needRole('admin');

    $name=postv('full_name');

    $role=postv('role');

    $email=postv('email');

    $username=postv('new_username');

    $password=(string)($_POST['new_password']??'');

    if(!$name||!filter_var($email,FILTER_VALIDATE_EMAIL)||!in_array($role,['student','faculty'],true)||!preg_match('/^[A-Za-z0-9._-]{4,32}$/',$username)||strlen($password)<8)

        throw new Exception('Enter valid account details. User ID must be 4–32 characters and password at least 8 characters.');

    $s=$pdo->prepare('INSERT INTO users(username,password_hash,role,full_name,email,student_code,department,address,phone) VALUES(?,?,?,?,?,?,?,?,?)');

    $s->execute([$username,password_hash($password,PASSWORD_DEFAULT),$role,$name,$email,postv('student_code'),postv('department'),postv('address'),postv('phone')]);

    flash('Account created for '.$name.'. User ID: '.$username.' · Password: '.$password.' — save it now; it will not be shown again.');

    go('#accounts');



   case 'create_subject':

    needRole('admin');

    $s=$pdo->prepare('INSERT INTO subjects(code,name,semester,faculty_id) VALUES(?,?,?,?)');

    $s->execute([strtoupper(postv('code')),postv('name'),postv('semester'),postv('faculty_id')?:null]);

    flash('Subject added to the catalogue.');

    go('#subjects');



   case 'attendance':

    needRole('faculty');

    $s=$pdo->prepare('INSERT INTO attendance(student_id,subject_id,class_date,status,marked_by) VALUES(?,?,?,?,?) ON CONFLICT(student_id,subject_id,class_date) DO UPDATE SET status=excluded.status,marked_by=excluded.marked_by');

    $s->execute([(int)postv('student_id'),(int)postv('subject_id'),postv('class_date'),postv('status'),$user['id']]);

    flash('Attendance saved successfully.');

    go('#attendance');



   case 'result':

    needRole('faculty');

    $marks=(float)postv('marks');

    $max=(float)postv('max_marks');

    if($marks<0||$max<=0||$marks>$max)throw new Exception('Marks must be between 0 and maximum marks.');

    $s=$pdo->prepare('INSERT INTO results(student_id,subject_id,exam_name,marks,max_marks,term) VALUES(?,?,?,?,?,?)');

    $s->execute([(int)postv('student_id'),(int)postv('subject_id'),postv('exam_name'),$marks,$max,postv('term')]);

    flash('Student result added.');

    go('#results');



   case 'fee':

    needRole('admin');

    $s=$pdo->prepare("INSERT INTO fees(student_id,fee_month,amount,status,note) VALUES(?,?,?,'Pending',?)");

    $s->execute([(int)postv('student_id'),postv('fee_month'),(float)postv('amount'),postv('note')]);

    flash('Fee due added.');

    go('#fees');



   case 'fee_status':

    needRole('admin');

    $st=postv('status');

    if(!in_array($st,['Pending','Paid','Under Review'],true))throw new Exception('Invalid fee status.');

    $s=$pdo->prepare('UPDATE fees SET status=?,updated_at=CURRENT_TIMESTAMP WHERE id=?');

    $s->execute([$st,(int)($_GET['id']??0)]);

    flash('Fee status updated.');

    go('#fees');



   case 'fee_receipt':

    needRole('student');

    $fid=(int)postv('fee_id');

    $fee=one('SELECT * FROM fees WHERE id=? AND student_id=?',[$fid,$user['id']]);

    $up=safeUpload('receipt','fees',['pdf','png','jpg','jpeg']);

    if(!$fee||!$up)throw new Exception('Choose a valid PDF, PNG or JPG receipt for your own fee record.');

    $s=$pdo->prepare("UPDATE fees SET receipt_filename=?,status='Under Review',updated_at=CURRENT_TIMESTAMP WHERE id=?");

    $s->execute([$up['name'],$fid]);

    flash('Receipt uploaded. Admin can review the payment.');

    go('#fees');



   case 'resource':

    needRole('admin','faculty');

    $up=safeUpload('file','content',['pdf','doc','docx','ppt','pptx','txt']);

    if(!$up)throw new Exception('Allowed files: PDF, DOC/DOCX, PPT/PPTX or TXT (maximum 16 MB).');

    $s=$pdo->prepare('INSERT INTO resources(title,subject_id,resource_type,filename,original_filename,uploaded_by,description) VALUES(?,?,?,?,?,?,?)');

    $s->execute([postv('title'),(int)postv('subject_id'),postv('resource_type'),$up['name'],$up['original'],$user['id'],postv('description')]);

    flash('Resource uploaded successfully.');

    go('#resources');



   case 'message':

    needRole('student');

    $s=$pdo->prepare('INSERT INTO messages(sender_id,subject,body) VALUES(?,?,?)');

    $s->execute([$user['id'],postv('subject'),postv('body')]);

    flash('Message sent to Admin.');

    go('#messages');



   case 'message_update':

    needRole('admin');

    $status=postv('status');

    if(!in_array($status,['Open','In Progress','Resolved'],true))throw new Exception('Invalid message status.');

    $s=$pdo->prepare('UPDATE messages SET status=?,reply=? WHERE id=?');

    $s->execute([$status,postv('reply'),(int)($_GET['id']??0)]);

    flash('Message updated.');

    go('#messages');

  }

 } catch(Throwable $ex){

    flash($ex->getMessage(),'error');

    go();

 }

}



// Page rendering

$user = currentUser();

$page = $_GET['page'] ?? ($user ? 'dashboard' : 'login');



function field($name, $label, $type='text', $required=true) {

    echo '<label>'.e($label).'</label>';

    echo '<input type="'.e($type).'" name="'.e($name).'" '.($required?'required':'').'>';

}



function hiddenCsrf() {

    echo '<input type="hidden" name="_csrf" value="'.e(csrf()).'">';

}



function formStart($action, $extra='') {

    echo '<form method="post" action="index.php?action='.e($action).'" '.$extra.'>';

    hiddenCsrf();

}



function formEnd($label='Submit') {

    echo '<button type="submit">'.e($label).'</button></form>';

}



function sectionTitle($id,$title) {

    echo '<section id="'.e($id).'" class="panel"><h2>'.e($title).'</h2>';

}



function sectionEnd() {

    echo '</section>';

}



$students=rows("SELECT * FROM users WHERE role='student' ORDER BY full_name");

$faculty=rows("SELECT * FROM users WHERE role='faculty' ORDER BY full_name");

$subjects=rows("SELECT s.*,u.full_name AS faculty_name FROM subjects s LEFT JOIN users u ON u.id=s.faculty_id ORDER BY s.name");

$messages=$user ? ($user['role']==='admin' ? rows("SELECT m.*,u.full_name FROM messages m JOIN users u ON u.id=m.sender_id ORDER BY m.id DESC") : rows("SELECT * FROM messages WHERE sender_id=? ORDER BY id DESC",[$user['id']])) : [];

$resources=$user ? rows("SELECT r.*,s.name AS subject_name FROM resources r JOIN subjects s ON s.id=r.subject_id ORDER BY r.id DESC") : [];

$flashMessages=getFlash();

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width,initial-scale=1">

<title>CampusPro - Student Attendance Management</title>

<style>

*{box-sizing:border-box}

body{margin:0;font-family:Arial,sans-serif;background:#f3f5fb;color:#202943}

a{color:#3558c9;text-decoration:none}

header{background:#172554;color:white;padding:18px 24px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px}

header h1{font-size:22px;margin:0}

header a{color:white}

.container{max-width:1200px;margin:24px auto;padding:0 16px}

.layout{display:grid;grid-template-columns:220px minmax(0,1fr);gap:20px}

nav,.panel,.login-card{background:white;border:1px solid #e3e7f1;border-radius:12px;padding:18px;box-shadow:0 4px 18px #1e2d5010}

nav a{display:block;padding:10px;border-radius:7px;color:#24345c}

nav a:hover{background:#edf1ff}

.panel{margin-bottom:20px;overflow-x:auto}

h2{font-size:19px;margin:0 0 16px}

h3{font-size:16px}

label{display:block;font-size:13px;font-weight:bold;margin:12px 0 6px}

input,select,textarea{width:100%;padding:11px;border:1px solid #ccd3e1;border-radius:7px;font:inherit;background:white}

textarea{min-height:90px;resize:vertical}

button,.btn{border:0;background:#3558c9;color:white;padding:10px 16px;border-radius:7px;cursor:pointer;font-weight:bold;display:inline-block;margin-top:12px}

button:hover,.btn:hover{background:#243f9e}

form{margin-bottom:16px}

table{width:100%;border-collapse:collapse;min-width:600px}

th,td{text-align:left;padding:11px;border-bottom:1px solid #e8ebf2;font-size:14px}

th{background:#f6f7fb}

.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:14px}

.stat{background:#eef2ff;padding:18px;border-radius:10px}

.stat strong{display:block;font-size:26px;color:#263e9a;margin-top:8px}

.notice{padding:12px 14px;background:#eaf7ef;color:#17643b;border-radius:8px;margin-bottom:12px}

.error{background:#fff0f0;color:#a32232}

.info{background:#edf3ff;color:#24428d}

.login-wrap{max-width:430px;margin:8vh auto;padding:16px}

.login-card h2{text-align:center}

.muted{color:#69748c;font-size:13px}

.badge{display:inline-block;padding:5px 8px;border-radius:20px;background:#edf1ff;font-size:12px}

footer{text-align:center;color:#7c8498;padding:25px}

@media(max-width:750px){.layout{grid-template-columns:1fr}header{padding:16px}.container{margin:14px auto}}

</style>

</head>

<body>



<header>

    <h1>CampusPro</h1>

    <?php if($user): ?>

        <div>

            Welcome, <b><?=e($user['full_name'])?></b>

            &nbsp; | &nbsp;

            <span class="badge"><?=e(strtoupper($user['role']))?></span>

        </div>

    <?php endif; ?>

</header>



<div class="container">



<?php foreach($flashMessages as $f): ?>

    <div class="notice <?=($f['type']==='error'?'error':($f['type']==='info'?'info':''))?>">

        <?=e($f['msg'])?>

    </div>

<?php endforeach; ?>



<?php if(!$user): ?>



<div class="login-wrap">

<div class="login-card">

    <h2>Student Attendance Management</h2>

    <p class="muted" style="text-align:center">Login to your CampusPro account</p>



    <?php formStart('login'); ?>

        <?php field('username','User ID'); ?>

        <?php field('password','Password','password'); ?>

        <button type="submit" style="width:100%">Sign In</button>

    </form>



    <p class="muted">First-time administrator: User ID <b>admin</b>. Change the default password after logging in.</p>

</div>

</div>



<?php else: ?>



<div class="layout">



<nav>

    <h3>Navigation</h3>

    <a href="index.php">Dashboard</a>



    <?php if($user['role']==='admin'): ?>

        <a href="#accounts">Create Accounts</a>

        <a href="#subjects">Subjects</a>

        <a href="#fees">Fees</a>

        <a href="#messages">Helpdesk</a>

    <?php elseif($user['role']==='faculty'): ?>

        <a href="#attendance">Attendance</a>

        <a href="#results">Results</a>

        <a href="#resources">E-content</a>

    <?php else: ?>

        <a href="#attendance">My Attendance</a>

        <a href="#results">My Results</a>

        <a href="#fees">My Fees</a>

        <a href="#resources">Study Resources</a>

        <a href="#messages">Helpdesk</a>

    <?php endif; ?>



    <form method="post" action="index.php?action=logout">

        <?php hiddenCsrf(); ?>

        <button type="submit">Logout</button>

    </form>

</nav>



<main>



<section class="panel">

    <h2>Dashboard</h2>

    <p>Welcome to CampusPro Student Attendance Management System.</p>



    <?php if($user['role']==='admin'): ?>

    <div class="grid">

        <div class="stat">Total Students<strong><?=count($students)?></strong></div>

        <div class="stat">Total Faculty<strong><?=count($faculty)?></strong></div>

        <div class="stat">Total Subjects<strong><?=count($subjects)?></strong></div>

        <div class="stat">Helpdesk Messages<strong><?=count($messages)?></strong></div>

    </div>

    <?php elseif($user['role']==='faculty'): ?>

    <div class="grid">

        <div class="stat">Students<strong><?=count($students)?></strong></div>

        <div class="stat">Subjects<strong><?=count($subjects)?></strong></div>

    </div>

    <?php else: ?>

    <div class="grid">

        <div class="stat">My Student ID<strong><?=e($user['student_code'] ?: $user['username'])?></strong></div>

        <div class="stat">Department<strong><?=e($user['department'] ?: 'Not assigned')?></strong></div>

    </div>

    <?php endif; ?>

</section>



<?php if($user['role']==='admin'): ?>



<?php

sectionTitle('accounts','Create Student / Faculty Account');

?>

<?php formStart('create_user'); ?>

<div class="grid">

    <div><?php field('full_name','Full Name'); ?></div>

    <div><?php field('email','Email','email'); ?></div>

    <div><?php field('new_username','User ID'); ?></div>

    <div><?php field('new_password','Temporary Password','password'); ?></div>

    <div>

        <label>Role</label>

        <select name="role" required>

            <option value="student">Student</option>

            <option value="faculty">Faculty</option>

        </select>

    </div>

    <div><?php field('student_code','Roll Number', 'text',false); ?></div>

    <div><?php field('department','Department','text',false); ?></div>

    <div><?php field('phone','Phone','tel',false); ?></div>

</div>

<label>Address</label>

<textarea name="address"></textarea>

<?php formEnd('Create Account'); ?>

<?php sectionEnd(); ?>



<?php sectionTitle('subjects','Manage Subjects'); ?>

<?php formStart('create_subject'); ?>

<div class="grid">

    <div><?php field('code','Subject Code'); ?></div>

    <div><?php field('name','Subject Name'); ?></div>

    <div><?php field('semester','Semester'); ?></div>

    <div>

        <label>Faculty</label>

        <select name="faculty_id">

            <option value="">Not assigned</option>

            <?php foreach($faculty as $f): ?>

                <option value="<?=$f['id']?>"><?=e($f['full_name'])?></option>

            <?php endforeach; ?>

        </select>

    </div>

</div>

<?php formEnd('Add Subject'); ?>

<table><tr><th>Code</th><th>Subject</th><th>Semester</th><th>Faculty</th></tr>

<?php foreach($subjects as $s): ?>

<tr><td><?=e($s['code'])?></td><td><?=e($s['name'])?></td><td><?=e($s['semester'])?></td><td><?=e($s['faculty_name']??'Not assigned')?></td></tr>

<?php endforeach; ?>

</table>

<?php sectionEnd(); ?>



<?php sectionTitle('fees','Add Student Fee'); ?>

<?php formStart('fee'); ?>

<div class="grid">

    <div><label>Student</label><select name="student_id" required>

        <?php foreach($students as $s): ?>

            <option value="<?=$s['id']?>"><?=e($s['full_name'])?> (<?=e($s['username'])?>)</option>

        <?php endforeach; ?>

    </select></div>

    <div><?php field('fee_month','Fee Month','text'); ?></div>

    <div><?php field('amount','Amount','number'); ?></div>

    <div><?php field('note','Note','text',false); ?></div>

</div>

<?php formEnd('Add Fee'); ?>



<table><tr><th>Student</th><th>Month</th><th>Amount</th><th>Status</th><th>Update</th></tr>

<?php foreach(rows("SELECT f.*,u.full_name FROM fees f JOIN users u ON u.id=f.student_id ORDER BY f.id DESC") as $f): ?>

<tr>

<td><?=e($f['full_name'])?></td><td><?=e($f['fee_month'])?></td><td><?=e($f['amount'])?></td><td><?=e($f['status'])?></td>

<td>

<form method="post" action="index.php?action=fee_status&id=<?=$f['id']?>">

<?php hiddenCsrf(); ?>

<select name="status">

<?php foreach(['Pending','Paid','Under Review'] as $st): ?>

<option <?=$f['status']===$st?'selected':''?>><?=$st?></option>

<?php endforeach; ?>

</select>

<button>Save</button>

</form>

</td></tr>

<?php endforeach; ?>

</table>

<?php sectionEnd(); ?>



<?php endif; ?>



<?php if($user['role']==='faculty'): ?>



<?php sectionTitle('attendance','Mark Student Attendance'); ?>

<?php formStart('attendance'); ?>

<div class="grid">

    <div><label>Student</label><select name="student_id" required>

        <?php foreach($students as $s): ?>

        <option value="<?=$s['id']?>"><?=e($s['full_name'])?></option>

        <?php endforeach; ?>

    </select></div>

    <div><label>Subject</label><select name="subject_id" required>

        <?php foreach($subjects as $s): ?>

        <option value="<?=$s['id']?>"><?=e($s['name'])?></option>

        <?php endforeach; ?>

    </select></div>

    <div><?php field('class_date','Date','date'); ?></div>

    <div><label>Status</label><select name="status"><option>Present</option><option>Absent</option><option>Late</option></select></div>

</div>

<?php formEnd('Save Attendance'); ?>

<?php sectionEnd(); ?>



<?php sectionTitle('results','Add Student Result'); ?>

<?php formStart('result'); ?>

<div class="grid">

    <div><label>Student</label><select name="student_id">

        <?php foreach($students as $s): ?><option value="<?=$s['id']?>"><?=e($s['full_name'])?></option><?php endforeach; ?>

    </select></div>

    <div><label>Subject</label><select name="subject_id">

        <?php foreach($subjects as $s): ?><option value="<?=$s['id']?>"><?=e($s['name'])?></option><?php endforeach; ?>

    </select></div>

    <div><?php field('exam_name','Exam Name'); ?></div>

    <div><?php field('term','Term','text',false); ?></div>

    <div><?php field('marks','Marks Obtained','number'); ?></div>

    <div><?php field('max_marks','Maximum Marks','number'); ?></div>

</div>

<?php formEnd('Save Result'); ?>

<?php sectionEnd(); ?>



<?php endif; ?>



<?php if($user['role']==='admin'||$user['role']==='faculty'): ?>

<?php sectionTitle('resources','Upload E-content / Syllabus'); ?>

<?php formStart('resource','enctype="multipart/form-data"'); ?>

<div class="grid">

    <div><?php field('title','Resource Title'); ?></div>

    <div><label>Subject</label><select name="subject_id" required>

        <?php foreach($subjects as $s): ?><option value="<?=$s['id']?>"><?=e($s['name'])?></option><?php endforeach; ?>

    </select></div>

    <div><label>Type</label><select name="resource_type"><option>E-content</option><option>Syllabus</option></select></div>

    <div><label>File</label><input type="file" name="file" required></div>

</div>

<label>Description</label><textarea name="description"></textarea>

<?php formEnd('Upload Resource'); ?>

<?php sectionEnd(); ?>

<?php endif; ?>



<?php if($user['role']==='student'): ?>



<?php sectionTitle('attendance','My Attendance'); ?>

<?php

$myAttendance=rows("SELECT a.*,s.name AS subject_name FROM attendance a JOIN subjects s ON s.id=a.subject_id WHERE a.student_id=? ORDER BY a.class_date DESC",[$user['id']]);

?>

<table><tr><th>Date</th><th>Subject</th><th>Status</th></tr>

<?php foreach($myAttendance as $a): ?>

<tr><td><?=e($a['class_date'])?></td><td><?=e($a['subject_name'])?></td><td><?=e($a['status'])?></td></tr>

<?php endforeach; ?>

</table>

<?php if(!$myAttendance): ?><p class="muted">No attendance records found.</p><?php endif; ?>

<?php sectionEnd(); ?>



<?php sectionTitle('results','My Results'); ?>

<table><tr><th>Subject</th><th>Exam</th><th>Marks</th><th>Maximum</th><th>Term</th></tr>

<?php foreach(rows("SELECT r.*,s.name AS subject_name FROM results r JOIN subjects s ON s.id=r.subject_id WHERE r.student_id=? ORDER BY r.id DESC",[$user['id']]) as $r): ?>

<tr><td><?=e($r['subject_name'])?></td><td><?=e($r['exam_name'])?></td><td><?=e($r['marks'])?></td><td><?=e($r['max_marks'])?></td><td><?=e($r['term'])?></td></tr>

<?php endforeach; ?>

</table>

<?php sectionEnd(); ?>



<?php sectionTitle('fees','My Fees'); ?>

<table><tr><th>Month</th><th>Amount</th><th>Status</th><th>Receipt</th></tr>

<?php foreach(rows("SELECT * FROM fees WHERE student_id=? ORDER BY id DESC",[$user['id']]) as $f): ?>

<tr>

<td><?=e($f['fee_month'])?></td><td><?=e($f['amount'])?></td><td><?=e($f['status'])?></td>

<td>

<?php if(!$f['receipt_filename']): ?>

<form method="post" enctype="multipart/form-data" action="index.php?action=fee_receipt">

<?php hiddenCsrf(); ?>

<input type="hidden" name="fee_id" value="<?=$f['id']?>">

<input type="file" name="receipt" accept=".pdf,.png,.jpg,.jpeg" required>

<button>Upload Receipt</button>

</form>

<?php else: ?>Uploaded<?php endif; ?>

</td>

</tr>

<?php endforeach; ?>

</table>

<?php sectionEnd(); ?>



<?php sectionTitle('messages','Helpdesk / Complaints'); ?>

<?php formStart('message'); ?>

<?php field('subject','Subject'); ?>

<label>Message</label><textarea name="body" required></textarea>

<?php formEnd('Send Message'); ?>

<?php sectionEnd(); ?>



<?php endif; ?>



<?php if($user['role']==='admin'): ?>

<?php sectionTitle('messages','Helpdesk Messages'); ?>

<?php foreach($messages as $m): ?>

<div class="panel">

    <h3><?=e($m['subject'])?></h3>

    <p><?=e($m['body'])?></p>

    <p class="muted">From: <?=e($m['full_name'])?> | <?=e($m['created_at'])?></p>

    <?php formStart('message_update',''); ?>

    <label>Status</label>

    <select name="status">

        <?php foreach(['Open','In Progress','Resolved'] as $st): ?>

        <option <?=$m['status']===$st?'selected':''?>><?=$st?></option>

        <?php endforeach; ?>

    </select>

    <label>Reply</label>

    <textarea name="reply"><?=e($m['reply'])?></textarea>

    <button type="submit" formaction="index.php?action=message_update&id=<?=$m['id']?>">Update</button>

    </form>

</div>

<?php endforeach; ?>

<?php sectionEnd(); ?>

<?php endif; ?>



<?php if($user): ?>

<?php sectionTitle('resources','Available Study Resources'); ?>

<table><tr><th>Title</th><th>Subject</th><th>Type</th><th>Uploaded</th></tr>

<?php foreach($resources as $r): ?>

<tr>

<td><?=e($r['title'])?></td>

<td><?=e($r['subject_name'])?></td>

<td><?=e($r['resource_type'])?></td>

<td><?=e($r['created_at'])?></td>

</tr>

<?php endforeach; ?>

</table>

<?php sectionEnd(); ?>

<?php endif; ?>



</main>

</div>

<?php endif; ?>

</div>



<footer>CampusPro · Student Attendance Management System</footer>

</body>

</html>

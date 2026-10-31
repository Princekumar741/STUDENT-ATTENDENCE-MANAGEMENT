<?php
header('Content-Type: text/html; charset=utf-8');
function statusRow($label, $ok, $detail='') {
    echo '<tr><td>'.htmlspecialchars($label).'</td><td style="color:'.($ok?'#167653':'#b83250').'">'.($ok?'Enabled':'Missing').'</td><td>'.htmlspecialchars($detail).'</td></tr>';
}
$ini = php_ini_loaded_file() ?: 'No php.ini loaded';
?>
<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>CampusPro PHP Diagnostics</title>
<style>body{font:15px Arial;background:#f5f7fc;color:#1c2745;margin:0;padding:24px}.card{max-width:900px;margin:auto;background:white;border:1px solid #e5e9f2;border-radius:16px;padding:24px}table{width:100%;border-collapse:collapse}td,th{text-align:left;padding:12px;border-bottom:1px solid #e8ebf3}code{background:#f1f3f9;padding:3px 5px;border-radius:4px}</style>
<div class="card"><h1>CampusPro PHP diagnostics</h1><p>Use this page if the portal does not load.</p><p><b>PHP version:</b> <?=htmlspecialchars(PHP_VERSION)?></p><p><b>Loaded php.ini:</b> <code><?=htmlspecialchars($ini)?></code></p><table><thead><tr><th>Check</th><th>Status</th><th>Details</th></tr></thead><tbody>
<?php statusRow('PDO', extension_loaded('PDO')); statusRow('PDO SQLite', extension_loaded('pdo_sqlite')); statusRow('SQLite3', extension_loaded('sqlite3')); statusRow('Uploads directory writable', is_writable(__DIR__.'/uploads') || is_writable(__DIR__)); ?>
</tbody></table><h2>Fix common setup issue</h2><ol><li>Open the <code>php.ini</code> path shown above.</li><li>Enable <code>extension=pdo_sqlite</code> and <code>extension=sqlite3</code> (remove leading semicolons).</li><li>Restart Apache from XAMPP.</li><li>Return to <a href="index.php">CampusPro</a>.</li></ol></div></html>

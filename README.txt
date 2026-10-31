CAMPUSPRO — PHP VERSION
=======================
This is the PHP conversion of the CampusPro student/faculty/admin portal. The layout and CSS are retained from the supplied website; the backend has been converted from Python Flask to native PHP.

FEATURES
--------
- One shared login screen; the account's database role determines the dashboard.
- Admin manually sets the User ID and password for Student and Faculty accounts.
- Unique User IDs, password hashing, CSRF tokens and role-protected actions.
- Subject catalogue and faculty assignment.
- Faculty attendance entry and result publishing.
- Student attendance/results views.
- Admin fee management; students upload payment receipts.
- E-content and syllabus uploads/downloads.
- Student complaint/helpdesk submission; Admin updates status and replies.
- Same existing stylesheet and CampusPro visual design.

REQUIREMENTS
------------
- PHP 8.1+ recommended.
- PDO SQLite extension enabled (pdo_sqlite and sqlite3).
- For XAMPP, make sure extension=pdo_sqlite and extension=sqlite3 are enabled in php.ini, then restart Apache.

WINDOWS / XAMPP SETUP
---------------------
1. Extract this folder into C:\\xampp\\htdocs\\StudentCampusPro (or your PHP server's document root).
2. Check that pdo_sqlite and sqlite3 are enabled in php.ini, then restart Apache.
3. Open http://localhost/StudentCampusPro/ in your browser. If it fails, open http://localhost/StudentCampusPro/diagnostics.php.
4. First login for a fresh database:
   User ID: admin
   Password: Admin@123
5. Immediately change/remove demo credentials before exposing the website publicly.

ALTERNATIVE PHP BUILT-IN SERVER
-------------------------------
Open a terminal in this folder and run:
  php -S 127.0.0.1:8000
Then visit http://127.0.0.1:8000/ . The PHP build must have PDO SQLite enabled.

DATABASE / MIGRATION NOTES
--------------------------
The PHP version uses campus.db, the same SQLite filename/schema family as the supplied Flask version, so a compatible existing campus.db can be placed beside index.php to retain records. Existing password hashes created by Werkzeug may depend on the hashing algorithm used by that installation; PBKDF2-SHA256 hashes are supported, but Werkzeug scrypt hashes may need a password reset/migration before those users can sign in. A fresh database seeds admin / Admin@123.

Uploads are saved under uploads/content and uploads/fees. Back up campus.db and uploads regularly.

SECURITY
--------
If the portal previously showed a blank page, visit diagnostics.php to see PHP extension and folder-permission status. Unexpected PHP errors now display a troubleshooting page instead of failing silently.\n\nThis is a starter project, not production-hardened. Use HTTPS, change demo credentials, restrict upload access, configure backups, and review access controls before storing real student records.

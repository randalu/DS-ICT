<?php
declare(strict_types=1);
// Local synthetic setup only. Copies table structures, never operational records.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
putenv('DB_NAME=ict_quotation_rehearsal_20261023'); putenv('APP_URL=http://127.0.0.1:8153');
require_once __DIR__ . '/../../includes/customer_auth.php';
require_once __DIR__ . '/../../includes/quotation_booking.php';
if (!in_array(DB_HOST, ['127.0.0.1','localhost','::1'], true) || DB_NAME !== 'ict_quotation_rehearsal_20261023') { throw new RuntimeException('Only the named loopback synthetic schema is permitted.'); }
$server = new PDO('mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';charset=utf8mb4', DB_USERNAME, DB_PASSWORD, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
if (($argv[1] ?? '') === 'companion-tests') {
    foreach (['ict_admin_response_quotation_20261023', 'ict_r2_trust_20261020_a12345'] as $companion) {
        $exists=$server->prepare('SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME=?');$exists->execute([$companion]);if($exists->fetch()){throw new RuntimeException('Companion schema exists. Inspect before reuse.');}
        $server->exec('CREATE DATABASE `'.$companion.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        foreach($server->query('SHOW TABLES FROM ict_quotation_rehearsal_20261023')->fetchAll(PDO::FETCH_COLUMN) as $table){if(!preg_match('/^[a-zA-Z0-9_]+$/',$table)){throw new RuntimeException('Unexpected table name.');}$server->exec('CREATE TABLE `'.$companion.'`.`'.$table.'` LIKE ict_quotation_rehearsal_20261023.`'.$table.'`');}
    }
    echo "Empty companion schemas created for credential-session and public-link checks.\n";exit;
}
if (($argv[1] ?? '') === 'failure-trigger') {
    db()->exec("CREATE TRIGGER quotation_fixture_failure BEFORE INSERT ON quotation_bookings FOR EACH ROW BEGIN IF @ict_quote_fail_conversion=1 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Synthetic late conversion failure'; END IF; END");
    echo "Synthetic conversion failure trigger installed.\n"; exit;
}
if (($argv[1] ?? '') !== 'setup') { throw new RuntimeException('Use setup; no operational database or destructive mode is supported.'); }
if ($server->query("SHOW DATABASES LIKE 'ict_quotation_rehearsal_20261023'")->fetch()) { throw new RuntimeException('Synthetic schema already exists. Inspect before reusing.'); }
$server->exec('CREATE DATABASE ict_quotation_rehearsal_20261023 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$tables = $server->query('SHOW TABLES FROM ict')->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $table) { if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) { throw new RuntimeException('Unexpected table name.'); } $server->exec('CREATE TABLE ict_quotation_rehearsal_20261023.`' . $table . '` LIKE ict.`' . $table . '`'); }
ensure_customer_accounts_schema(); ensure_site_settings_schema();
db()->exec((string) file_get_contents(__DIR__ . '/../../database/migrations/2026-10-23-quotation-builder.sql'));
db()->exec("CREATE TRIGGER quotation_fixture_failure BEFORE INSERT ON quotation_bookings FOR EACH ROW BEGIN IF @ict_quote_fail_conversion=1 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Synthetic late conversion failure'; END IF; END");
execute_query('INSERT INTO admins (id,name,email,username,password_hash,is_active) VALUES (101,:name,:email,:username,:hash,1)', ['name'=>'Quotation Fixture','email'=>'quote@example.invalid','username'=>'quotation_fixture','hash'=>password_hash(bin2hex(random_bytes(24)), PASSWORD_DEFAULT)]);
foreach (ict_policy_documents() as $doc) { execute_query('INSERT INTO static_pages (title,slug,content,meta_title,meta_description,is_published) VALUES (:title,:slug,:content,:meta,:description,1)', ['title'=>$doc['title'],'slug'=>$doc['slug'],'content'=>$doc['content'],'meta'=>$doc['meta_title'],'description'=>$doc['meta_description']]); }
execute_query('INSERT INTO destinations (name,slug,hero_image,is_published) VALUES (:name,:slug,:image,1)', ['name'=>'Nuwara Eliya','slug'=>'quotation-fixture-nuwara-eliya','image'=>'assets/images/country-market/nuwara-eliya-tea.jpg']);
execute_query('INSERT INTO experiences (title,slug,excerpt,hero_image,is_published) VALUES (:name,:slug,:excerpt,:image,1)', ['name'=>'Scenic train journey','slug'=>'quotation-fixture-train','excerpt'=>'A scenic rail journey through Sri Lanka’s hill country.','image'=>'assets/images/country-market/nine-arch-bridge.jpg']);
execute_query('INSERT INTO media_assets (file_path,file_name,mime_type,file_size,alt_text) VALUES (:path,:name,:type,1,:alt)', ['path'=>'assets/images/country-market/nuwara-eliya-tea.jpg','name'=>'Hill country tea fields','type'=>'image/jpeg','alt'=>'Tea fields in the Nuwara Eliya hill country']);
echo count($tables) . " table structures copied; synthetic admin, policies and travel content seeded. No operational rows copied.\n";

<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { exit("CLI only\n"); }
$email=$argv[1]??''; $password=$argv[2]??''; $name=$argv[3]??'Administrator';
if($email===''||$password===''){exit("Usage: php create_admin.php admin@example.com StrongPassword "Admin Name"\n");}
$host=getenv('DB_HOST')?:'127.0.0.1';$port=getenv('DB_PORT')?:'3306';$db=getenv('DB_DATABASE')?:'wifi_manager';$user=getenv('DB_USERNAME')?:'root';$pass=getenv('DB_PASSWORD')?:'';
$pdo=new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4",$user,$pass,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$stmt=$pdo->prepare('INSERT INTO admins(name,email,password_hash) VALUES(?,?,?) ON DUPLICATE KEY UPDATE name=VALUES(name),password_hash=VALUES(password_hash)');
$stmt->execute([$name,$email,password_hash($password,PASSWORD_DEFAULT)]);
echo "Admin created/updated: $email\n";

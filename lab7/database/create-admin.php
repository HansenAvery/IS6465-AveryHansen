<?php
// Run once from the project root: php database/create-admin.php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../repositories/userPDO.php';
function promptLine(string $label): string { fwrite(STDOUT,$label); return trim((string)fgets(STDIN)); }
$name=promptLine('Administrator full name: ');
$email=strtolower(promptLine('Administrator email: '));
fwrite(STDOUT,'Administrator password (10+ characters): ');
$hidden=function_exists('shell_exec') && PHP_OS_FAMILY!=='Windows';
if($hidden) @shell_exec('stty -echo');
$password=trim((string)fgets(STDIN));
if($hidden) @shell_exec('stty echo');
fwrite(STDOUT,PHP_EOL);
if($name==='' || !filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($password)<10 || strlen($password)>200) { fwrite(STDERR,"Invalid name, email, or password length.\n"); exit(1); }
$pdo=openDatabase();
if(findUserByEmail($pdo,$email)){fwrite(STDERR,"That email already has an account.\n");exit(1);}
insertUser($pdo,$name,$email,password_hash($password,PASSWORD_DEFAULT),'Administrator');
fwrite(STDOUT,"Administrator account created.\n");

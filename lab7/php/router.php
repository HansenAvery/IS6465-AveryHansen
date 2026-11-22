<?php
$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH)?:'/';
if($path==='/')$path='/index.php';
if(preg_match('~^/(?:database|config|includes|repositories|services|tests|auth)(?:/|$)~i',$path)||preg_match('~\.(?:db|sqlite|sql|md|txt)$~i',$path)){http_response_code(404);exit('Not found');}
$base=realpath(dirname(__DIR__));$full=realpath($base.$path);
if($full===false||strpos($full,$base.DIRECTORY_SEPARATOR)!==0||!is_file($full)){http_response_code(404);exit('Not found');}
if(strpos($path,'/php/')!==0 && $path!=='/index.php' && strpos($path,'/css/')!==0){http_response_code(404);exit('Not found');}
return false;

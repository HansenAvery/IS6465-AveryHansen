<?php
require_once dirname(__DIR__).'/services/userService.php';
require_once dirname(__DIR__).'/services/requestService.php';
$pdo=new PDO('sqlite::memory:');$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);$pdo->exec('PRAGMA foreign_keys=ON');
$pdo->exec(file_get_contents(dirname(__DIR__).'/database/setup-database.sql'));
$passed=0;$failed=0;
function checkResult(bool $ok,string $label):void{global $passed,$failed;if($ok){$passed++;echo "PASS: $label
";}else{$failed++;echo "FAIL: $label
";}}
$base=['full_name'=>'Taylor Student','email'=>'taylor@example.edu','password'=>'CorrectHorseBattery1','confirm_password'=>'CorrectHorseBattery1','role'=>'Student'];
$id=registerAccount($pdo,$base);$u=findUserById($pdo,$id);
checkResult($u&&$u['status']==='Active'&&$u['role']==='Student','UM-01 creates active Student account');
checkResult(password_verify($base['password'],$u['password_hash']),'UM-01 stores a password hash');
checkResult(findStudentIdForUser($pdo,$id)!==null,'UM-01 links Student account to students record');
try{registerAccount($pdo,$base);checkResult(false,'UM-01 rejects duplicate email');}catch(InvalidArgumentException $e){checkResult(true,'UM-01 rejects duplicate email');}
$tutor=$base;$tutor['email']='tutor@example.edu';$tutor['role']='Tutor';$tid=registerAccount($pdo,$tutor);checkResult(findStudentIdForUser($pdo,$tid)===null,'UM-01 Tutor does not receive student request record');
$adminData=$base;$adminData['email']='admin@example.edu';$adminData['role']='Student';$aid=registerAccount($pdo,$adminData);$admin=findUserById($pdo,$aid);changeUserRole($pdo,$admin,$aid,'Administrator');$admin=findUserById($pdo,$aid);
checkResult($admin['role']==='Administrator','UM-06 administrator role change persists');
$profile=['full_name'=>'Taylor Updated','email'=>'taylor.updated@example.edu'];updateProfile($pdo,$u,$profile);$u=findUserById($pdo,$id);
checkResult($u['full_name']==='Taylor Updated'&&$u['email']==='taylor.updated@example.edu','UM-04 updates own name and email');
try{changePassword($pdo,$u,['current_password'=>'wrong','new_password'=>'AnotherCorrectPass2','confirm_password'=>'AnotherCorrectPass2']);checkResult(false,'UM-05 rejects incorrect current password');}catch(InvalidArgumentException $e){checkResult(true,'UM-05 rejects incorrect current password');}
changePassword($pdo,$u,['current_password'=>$base['password'],'new_password'=>'AnotherCorrectPass2','confirm_password'=>'AnotherCorrectPass2']);$u=findUserById($pdo,$id);
checkResult(password_verify('AnotherCorrectPass2',$u['password_hash']),'UM-05 updates password hash');
changeUserStatus($pdo,$admin,$tid,'Inactive');checkResult(findUserById($pdo,$tid)['status']==='Inactive','UM-07 deactivates account');
try{changeUserStatus($pdo,$admin,$aid,'Inactive');checkResult(false,'UM-07 prevents last active administrator deactivation');}catch(InvalidArgumentException $e){checkResult(true,'UM-07 prevents last active administrator deactivation');}
$msg=deleteUserSafely($pdo,$admin,$tid);checkResult(strpos($msg,'blocked')!==false&&findUserById($pdo,$tid)!==false,'UM-08 blocks deletion while retention policy is unresolved');
checkResult(authenticateUser($pdo,$profile['email'],'AnotherCorrectPass2')!==false,'UM-09 authenticates valid active account');
checkResult(authenticateUser($pdo,'missing@example.edu','anything')===false,'UM-09 rejects unknown account generically');
checkResult(authenticateUser($pdo,'tutor@example.edu','CorrectHorseBattery1')===false,'UM-07 inactive account cannot authenticate');
$rows=searchUsers($pdo,'Taylor','Student','Active');checkResult(count($rows)===1,'UM-03 administrator search and filters return match');
$reqInput=['course'=>'IS 6465','reason'=>'Please help me understand PHP sessions.','topics'=>['Exam review'],'preferreddate'=>(new DateTimeImmutable('+2 days'))->format('Y-m-d'),'preferredtime'=>'Morning'];
$requestId=submitRequestForUser($pdo,$u,$reqInput);$mine=listRequestsForUser($pdo,$u);checkResult(count($mine)===1&&$mine[0]['request_id']===$requestId,'Student workflow lists only own request');
checkResult(findRequestForUser($pdo,$requestId,$admin)!==false,'Administrator can view all requests');
checkResult(!findRequestForUser($pdo,$requestId,$tutor),'Tutor cannot view tutoring request workflow');
checkResult(findRequestForUser($pdo,$requestId,$u)!==false,'UM request ownership allows own request');
// UM-02 is also covered by the profile repository lookup; session/page checks require HTTP testing.
checkResult(findUserById($pdo,$id)['user_id']===$id,'UM-02 retrieves own account record by authenticated ID');
echo "Results: $passed passed, $failed failed
";exit($failed>0?1:0);

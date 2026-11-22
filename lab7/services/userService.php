<?php
require_once __DIR__ . '/../repositories/userPDO.php';
function validateAccountInput(array $in): array {
 $name=trim((string)($in['full_name']??'')); $email=strtolower(trim((string)($in['email']??''))); $pw=(string)($in['password']??''); $confirm=(string)($in['confirm_password']??''); $role=(string)($in['role']??''); $e=[];
 if($name===''||strlen($name)>120)$e[]='Enter a name of 1 to 120 characters.';
 if(!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($email)>254)$e[]='Enter a valid email address.';
 if(strlen($pw)<10||strlen($pw)>200)$e[]='Password must be 10 to 200 characters.';
 if($pw!==$confirm)$e[]='Passwords do not match.';
 if(!in_array($role,['Student','Tutor'],true))$e[]='Choose Student or Tutor.';
 return [$e,$name,$email,$pw,$role];
}
function registerAccount(PDO $pdo,array $in): int {
 [$e,$name,$email,$pw,$role]=validateAccountInput($in); if($e)throw new InvalidArgumentException(implode(' ',$e));
 if(findUserByEmail($pdo,$email))throw new InvalidArgumentException('An account with that email already exists.');
 $pdo->beginTransaction(); try { $id=insertUser($pdo,$name,$email,password_hash($pw,PASSWORD_DEFAULT),$role); if($role==='Student')linkStudentAccount($pdo,$id,$name,$email); $pdo->commit(); return $id; } catch(Throwable $x){if($pdo->inTransaction())$pdo->rollBack();throw $x;}
}
function updateProfile(PDO $pdo,array $user,array $in): void {
 $name=trim((string)($in['full_name']??''));$email=strtolower(trim((string)($in['email']??'')));
 if($name===''||strlen($name)>120||!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($email)>254)throw new InvalidArgumentException('Enter a valid name and email address.');
 $existing=findUserByEmail($pdo,$email);if($existing&&(int)$existing['user_id']!==(int)$user['user_id'])throw new InvalidArgumentException('That email address is already in use.');
 $pdo->beginTransaction();try{updateOwnUser($pdo,(int)$user['user_id'],$name,$email);if($user['role']==='Student')linkStudentAccount($pdo,(int)$user['user_id'],$name,$email);$pdo->commit();}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
}
function changePassword(PDO $pdo,array $user,array $in): void {
 $current=(string)($in['current_password']??'');$new=(string)($in['new_password']??'');$confirm=(string)($in['confirm_password']??'');
 if(!password_verify($current,$user['password_hash']))throw new InvalidArgumentException('Current password is incorrect.');
 if(strlen($new)<10||strlen($new)>200)throw new InvalidArgumentException('New password must be 10 to 200 characters.');
 if($new!==$confirm)throw new InvalidArgumentException('New passwords do not match.');
 updateUserPassword($pdo,(int)$user['user_id'],password_hash($new,PASSWORD_DEFAULT));
}
function authenticateUser(PDO $pdo,string $email,string $password) {
 $u=findUserByEmail($pdo,strtolower(trim($email)));
 if(!$u||$u['status']!=='Active'||!password_verify($password,$u['password_hash']))return false;
 if(password_needs_rehash($u['password_hash'],PASSWORD_DEFAULT)){updateUserPassword($pdo,(int)$u['user_id'],password_hash($password,PASSWORD_DEFAULT));}
 return $u;
}
function changeUserRole(PDO $pdo,array $admin,int $targetId,string $role): void {
 if(!in_array($role,['Student','Tutor','Administrator'],true))throw new InvalidArgumentException('Invalid role.');
 $target=findUserById($pdo,$targetId);if(!$target)throw new InvalidArgumentException('User not found.');
 if($targetId===(int)$admin['user_id']&&$role!=='Administrator')throw new InvalidArgumentException('You cannot remove your own administrator role.');
 if($target['role']==='Administrator'&&$role!=='Administrator'&&$target['status']==='Active'&&countOtherActiveAdmins($pdo,$targetId)===0)throw new InvalidArgumentException('The last active administrator cannot be demoted.');
 updateUserRole($pdo,$targetId,$role);
}
function changeUserStatus(PDO $pdo,array $admin,int $targetId,string $status): void {
 if(!in_array($status,['Active','Inactive'],true))throw new InvalidArgumentException('Invalid status.');
 $target=findUserById($pdo,$targetId);if(!$target)throw new InvalidArgumentException('User not found.');
 if($targetId===(int)$admin['user_id']&&$status==='Inactive')throw new InvalidArgumentException('You cannot deactivate your own account.');
 if($target['role']==='Administrator'&&$target['status']==='Active'&&$status==='Inactive'&&countOtherActiveAdmins($pdo,$targetId)===0)throw new InvalidArgumentException('The last active administrator cannot be deactivated.');
 updateUserStatus($pdo,$targetId,$status);
}
function deleteUserSafely(PDO $pdo,array $admin,int $targetId): string {
 if($targetId===(int)$admin['user_id'])throw new InvalidArgumentException('You cannot delete your own account.');
 $target=findUserById($pdo,$targetId);if(!$target)throw new InvalidArgumentException('User not found.');
 if($target['role']==='Administrator'&&$target['status']==='Active'&&countOtherActiveAdmins($pdo,$targetId)===0)throw new InvalidArgumentException('The last active administrator cannot be deleted.');
 if(userHasTutoringHistory($pdo,$targetId)) return 'Deletion is blocked because this account has related tutoring requests. Records were retained.';
 // Permanent deletion policy and retention period are unresolved in the approved design.
 // Fail closed; never remove an account until that policy is approved.
 return 'Deletion is blocked: the approved retention and permanent-deletion policy has not been defined. No data was changed.';
}

<?php
function findUserById(PDO $pdo, int $id) { $s=$pdo->prepare('SELECT user_id,full_name,email,password_hash,role,status,created_at FROM users WHERE user_id=?'); $s->execute([$id]); return $s->fetch(); }
function findUserByEmail(PDO $pdo, string $email) { $s=$pdo->prepare('SELECT * FROM users WHERE email=? COLLATE NOCASE'); $s->execute([$email]); return $s->fetch(); }
function insertUser(PDO $pdo, string $name, string $email, string $hash, string $role): int { $s=$pdo->prepare("INSERT INTO users(full_name,email,password_hash,role,status) VALUES(?,?,?,?,'Active')"); $s->execute([$name,$email,$hash,$role]); return (int)$pdo->lastInsertId(); }
function updateOwnUser(PDO $pdo, int $id, string $name, string $email): bool { $s=$pdo->prepare('UPDATE users SET full_name=?,email=? WHERE user_id=?'); return $s->execute([$name,$email,$id]); }
function updateUserPassword(PDO $pdo, int $id, string $hash): void { $s=$pdo->prepare('UPDATE users SET password_hash=? WHERE user_id=?'); $s->execute([$hash,$id]); }
function searchUsers(PDO $pdo, string $term='', string $role='', string $status=''): array {
 $sql='SELECT user_id,full_name,email,role,status,created_at FROM users WHERE 1=1'; $a=[];
 if($term!==''){ $sql.=' AND (full_name LIKE ? OR email LIKE ?)'; $a[]='%'.$term.'%'; $a[]='%'.$term.'%'; }
 if($role!==''){ $sql.=' AND role=?'; $a[]=$role; } if($status!==''){ $sql.=' AND status=?'; $a[]=$status; }
 $sql.=' ORDER BY full_name COLLATE NOCASE'; $s=$pdo->prepare($sql); $s->execute($a); return $s->fetchAll();
}
function updateUserRole(PDO $pdo,int $id,string $role): void { $s=$pdo->prepare('UPDATE users SET role=? WHERE user_id=?'); $s->execute([$role,$id]); }
function updateUserStatus(PDO $pdo,int $id,string $status): void { $s=$pdo->prepare('UPDATE users SET status=? WHERE user_id=?'); $s->execute([$status,$id]); }
function countOtherActiveAdmins(PDO $pdo,int $excludeId): int { $s=$pdo->prepare("SELECT COUNT(*) FROM users WHERE role='Administrator' AND status='Active' AND user_id<>?"); $s->execute([$excludeId]); return (int)$s->fetchColumn(); }
function userHasTutoringHistory(PDO $pdo,int $id): bool { $s=$pdo->prepare('SELECT COUNT(*) FROM students s JOIN tutoring_requests r ON r.student_id=s.student_id WHERE s.user_id=?'); $s->execute([$id]); return (int)$s->fetchColumn()>0; }
function linkStudentAccount(PDO $pdo,int $userId,string $name,string $email): void {
 $byUser=$pdo->prepare('SELECT student_id FROM students WHERE user_id=?');$byUser->execute([$userId]);$linked=$byUser->fetch();
 $byEmail=$pdo->prepare('SELECT student_id,user_id FROM students WHERE student_email=? COLLATE NOCASE');$byEmail->execute([$email]);$matching=$byEmail->fetch();
 if($matching && $matching['user_id'] !== null && (int)$matching['user_id'] !== $userId) throw new RuntimeException('That student email is already linked to another account.');
 if($linked){
   if($matching && (int)$matching['student_id'] !== (int)$linked['student_id']) throw new RuntimeException('That email is already used by another student record.');
   $u=$pdo->prepare('UPDATE students SET student_name=?,student_email=? WHERE student_id=?');$u->execute([$name,$email,$linked['student_id']]);
 } elseif($matching){
   $u=$pdo->prepare('UPDATE students SET student_name=?,user_id=? WHERE student_id=? AND user_id IS NULL');$u->execute([$name,$userId,$matching['student_id']]);
 } else {
   $i=$pdo->prepare('INSERT INTO students(student_name,student_email,user_id) VALUES(?,?,?)');$i->execute([$name,$email,$userId]);
 }
}
function findStudentIdForUser(PDO $pdo,int $userId): ?int { $s=$pdo->prepare('SELECT student_id FROM students WHERE user_id=?');$s->execute([$userId]);$v=$s->fetchColumn();return $v===false?null:(int)$v; }

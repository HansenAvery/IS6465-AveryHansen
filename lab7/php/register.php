<?php
require_once __DIR__.'/../config/db.php'; require_once __DIR__.'/../services/userService.php'; require_once __DIR__.'/../includes/auth.php';
$pdo=openDatabase(); startSecureSession(); $error='';
if($_SERVER['REQUEST_METHOD']==='POST'){verifyCsrfToken($_POST);try{registerAccount($pdo,$_POST);header('Location: /php/login.php?registered=1',true,303);exit;}catch(InvalidArgumentException|RuntimeException $e){$error=$e->getMessage();}catch(Throwable $e){error_log($e->getMessage());$error='Unable to create account.';}}
require __DIR__.'/../includes/header.php';require __DIR__.'/../includes/nav.php'; ?>
<main class="container py-4"><h1>Create account</h1><p>Students and tutors may register. Administrator access is assigned separately.</p><?php if($error):?><div class="alert alert-danger"><?=htmlspecialchars($error,ENT_QUOTES,'UTF-8')?></div><?php endif;?>
<form method="post" class="col-md-6"><input type="hidden" name="csrf_token" value="<?=htmlspecialchars(csrfToken(),ENT_QUOTES,'UTF-8')?>">
<label class="form-label">Full name<input class="form-control" name="full_name" maxlength="120" required></label>
<label class="form-label">Email<input class="form-control" type="email" name="email" maxlength="254" required></label>
<label class="form-label">Password (10–200 characters)<input class="form-control" type="password" name="password" minlength="10" maxlength="200" required></label>
<label class="form-label">Confirm password<input class="form-control" type="password" name="confirm_password" required></label>
<label class="form-label">Account type<select class="form-select" name="role" required><option value="Student">Student</option><option value="Tutor">Tutor</option></select></label>
<button class="btn btn-primary mt-3">Create Account</button> <a href="/php/login.php">Log in</a></form></main><?php require __DIR__.'/../includes/footer.php'; ?>

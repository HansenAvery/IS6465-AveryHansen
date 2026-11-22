<?php
require_once __DIR__.'/auth.php';
startSecureSession();
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container">
    <a class="navbar-brand" href="../index.php">Peer Tutoring Hub</a>
    <div class="navbar-nav">
<?php if(!empty($_SESSION['user_id'])): ?>
<?php if(($_SESSION['role']??'')==='Student'): ?>
      <a class="nav-link" href="/php/requests.php">Requests</a>
      <a class="nav-link" href="/php/request-form.php">Request a Session</a>
<?php elseif(($_SESSION['role']??'')==='Administrator'): ?>
      <a class="nav-link" href="/php/requests.php">Requests</a>
      <a class="nav-link" href="/php/admin-users.php">Manage Users</a>
<?php endif; ?>
      <a class="nav-link" href="/php/profile-view.php">My Profile</a>
      <form class="d-inline" method="post" action="/php/logout.php">
        <input type="hidden" name="csrf_token" value="<?=htmlspecialchars(csrfToken(),ENT_QUOTES,'UTF-8')?>">
        <button type="submit" class="nav-link btn btn-link text-white">Log Out</button>
      </form>
<?php else: ?>
      <a class="nav-link" href="/php/login.php">Log In</a>
      <a class="nav-link" href="/php/register.php">Register</a>
<?php endif; ?>
    </div>
  </div>
</nav>

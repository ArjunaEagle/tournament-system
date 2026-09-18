<?php
$pageTitle='Admin Login';require_once dirname(__DIR__).'/includes/header.php';
if(!empty($_SESSION['user'])){header('Location: '.BASE_URL.'/admin/');exit;}
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  try{
    if(!hash_equals($_SESSION['csrf_token']??'',$_POST['csrf_token']??''))throw new RuntimeException('Session expired. Please try again.');
    $stmt=$pdo->prepare('SELECT * FROM users WHERE username=? LIMIT 1');$stmt->execute([trim($_POST['username']??'')]);$user=$stmt->fetch();
    $password=$_POST['password']??'';$valid=$user&&password_verify($password,$user['password']);
    if($user&&str_starts_with($user['password'],'sha256$')){$valid=hash_equals(substr($user['password'],7),hash('sha256',$password));if($valid){$newHash=password_hash($password,PASSWORD_DEFAULT);$pdo->prepare('UPDATE users SET password=? WHERE id=?')->execute([$newHash,$user['id']]);}}
    if(!$valid)throw new RuntimeException('Username atau password salah.');
    session_regenerate_id(true);$_SESSION['user']=['id'=>(int)$user['id'],'username'=>$user['username'],'name'=>$user['name'],'role'=>$user['role']];
    audit_log($pdo,'login','user',(int)$user['id'],'User logged in');header('Location: '.BASE_URL.'/admin/');exit;
  }catch(Throwable $e){$error=$e->getMessage();}
}
?>
<main style="min-height:100vh;display:grid;place-items:center;padding:20px"><section class="card" style="width:min(440px,100%);padding:32px"><a class="brand" href="<?=BASE_URL?>/"><span class="brand-mark"><i class="fa-solid fa-trophy"></i></span><span>ARENA<span>FLOW</span></span></a><div style="margin:32px 0 24px"><div class="eyebrow">Secure operations</div><h1 style="margin:6px 0">Admin login</h1><p style="color:var(--text-secondary)">Sign in to manage matches and live scoring.</p></div><?php if($error):?><p style="color:#fca5a5"><?=e($error)?></p><?php endif;?><form method="post"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><div class="form-group" style="margin-bottom:16px"><label>Username</label><input name="username" required autocomplete="username"></div><div class="form-group" style="margin-bottom:22px"><label>Password</label><input type="password" name="password" required autocomplete="current-password"></div><button class="btn btn-primary" style="width:100%" type="submit"><i class="fa-solid fa-arrow-right-to-bracket"></i> Login</button></form><p style="text-align:center;color:var(--text-secondary);font-size:.78rem;margin-top:20px">Demo: admin / admin123</p></section></main>
<?php require dirname(__DIR__).'/includes/footer.php';?>


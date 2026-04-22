<?php
session_start();
include "config.php";

if($_POST){
    $u=$_POST['user'];
    $p=md5($_POST['pass']);

    $res=$conn->query("SELECT * FROM users WHERE username='$u' AND password='$p'");
    if($res->num_rows){
        $_SESSION['login']=1;
        header("Location: dashboard.php");
    }
}
?>

<form method="post">
<input name="user" placeholder="user">
<input name="pass" type="password">
<button>Login</button>
</form>
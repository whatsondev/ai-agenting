<?php
session_start();
include "config.php";

if($_POST){
    $stmt=$conn->prepare("INSERT INTO training_data(keyword,response) VALUES(?,?)");
    $stmt->bind_param("ss",$_POST['k'],$_POST['r']);
    $stmt->execute();
}
?>

<form method="post">
Keyword: <input name="k"><br>
Response:<br>
<textarea name="r"></textarea><br>
<button>Add</button>
</form>
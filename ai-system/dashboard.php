<?php
session_start();
if(!$_SESSION['login']) exit;

include "config.php";

$res=$conn->query("SELECT * FROM comments ORDER BY id DESC");
?>

<h2>AI Comment System</h2>

<a href="train.php">Train AI</a>

<table border="1">
<tr>
<th>User</th>
<th>Comment</th>
<th>Reply</th>
<th>Status</th>
</tr>

<?php while($row=$res->fetch_assoc()): ?>
<tr>
<td><?= $row['user_name'] ?></td>
<td><?= $row['comment'] ?></td>
<td><?= $row['reply'] ?></td>
<td><?= $row['status'] ?></td>
</tr>
<?php endwhile; ?>

</table>

<?php
session_start();
$_SESSION = [];
session_destroy();
header('Location: /lazio_site/index.php');
exit;

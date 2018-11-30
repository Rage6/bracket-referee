<?php
// For local host with MAMP...
// $pdo = new PDO('mysql:host=localhost;port=8888;dbname=Bracket_Referee','Nick','Ike');
// For ClearDB with Heroku...
$pdo = new PDO('mysql:host=us-cdbr-iron-east-01.cleardb.net;port=3306;dbname=heroku_09cdd5d6c600012','bff92f85b9436f','d227bd4fb490ae1');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
?>

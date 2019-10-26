<?php

$currentHost = $_SERVER['HTTP_HOST'];

if ($currentHost == 'localhost:8888') {
  $pdo = new PDO('mysql:host=localhost;port=8888;dbname=Bracket_Referee','Nick','Ike');
} else {
  $pdo = new PDO('mysql:host=us-cdbr-iron-east-01.cleardb.net;port=3306;dbname=heroku_09cdd5d6c600012','bff92f85b9436f','*passwrd_goes_here*');
  $apiKey = '*api_key_goes_here*';
};
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

?>

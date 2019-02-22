<?php
  session_start();
  require_once("pdo.php");

  header('Content-Type: application/json; CHARSET=utf-8');

  $allTournStmt = $pdo->prepare('SELECT tourn_id,tourn_name FROM Tournaments');
  $allTournStmt->execute(array());
  $allTourn = [];
  while ($singleTourn = $allTournStmt->fetch(PDO::FETCH_ASSOC)) {
    $allTourn[] = $singleTourn;
  };

  // print_r($teamList);
  echo(json_encode($allTourn,JSON_PRETTY_PRINT));
?>

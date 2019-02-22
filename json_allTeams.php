<?php
  session_start();
  require_once("pdo.php");

  header('Content-Type: application/json; CHARSET=utf-8');

  $allTeamStmt = $pdo->prepare('SELECT team_id,team_name FROM Teams');
  $allTeamStmt->execute(array());
  $allTeams = [];
  while ($singleTeam = $allTeamStmt->fetch(PDO::FETCH_ASSOC)) {
    $allTeams[] = $singleTeam;
  };

  // print_r($teamList);
  echo(json_encode($allTeams,JSON_PRETTY_PRINT));
?>

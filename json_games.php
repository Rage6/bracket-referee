<?php
  session_start();
  require_once("pdo.php");

  header('Content-Type: application/json; CHARSET=utf-8');

  if (!isset($_GET['group_id'])) {
    die("<h3><u>ERROR:</u> Your group's game roster cannot be viewed unless your group's id number is included</h3>");
  };

  $teamStmt = $pdo->prepare('SELECT Games.game_id,Games.next_game,Games.team_a,Games.team_b,Games.get_wildcard,Games.is_wildcard FROM Games JOIN Groups WHERE Games.tourn_id=Groups.fk_tourn_id AND Groups.group_id=:gid');
  $teamStmt->execute(array(
    ':gid'=>htmlentities($_GET['group_id'])
  ));
  $teamList = [];
  while ($oneTeam = $teamStmt->fetch(PDO::FETCH_ASSOC)) {
    $teamList[] = $oneTeam;
  };
  // print_r($teamList);
  echo(json_encode($teamList,JSON_PRETTY_PRINT));
?>

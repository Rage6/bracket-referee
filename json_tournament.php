<?php
  session_start();
  require_once("pdo.php");

  header('Content-Type: application/json; CHARSET=utf-8');

  if (!isset($_GET['group_id'])) {
    die("<h3><u>ERROR:</u> Your tournament's team roster cannot be viewed unless your group's id number is included</h3>");
  };

  $teamStmt = $pdo->prepare('SELECT Teams.team_id,Teams.team_name,Games.game_id,Games.next_game,Games.team_a,Games.team_b FROM Teams JOIN Games JOIN Groups WHERE (Teams.team_id=Games.team_a OR Teams.team_id=Games.team_b) AND Games.tourn_id=Groups.fk_tourn_id AND (Games.first_round=1 OR Games.is_wildcard=1) AND Groups.group_id=:gid');
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

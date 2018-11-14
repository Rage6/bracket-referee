<?php
  session_start();
  require_once("pdo.php");
  // require_once("json_tournament.php?group_id=1");

  // Prevents entering this page w/o logging in
  if (!isset($_SESSION['player_id'])) {
    $_SESSION['message'] = "<b style='color:red'>You must log in or create an account to join a group.</b>";
    header('Location: index.php');
    return false;
  };

  // To get this player's name for the title
  $usrNmeStmt = $pdo->prepare('SELECT userName FROM Players WHERE player_id=:pid');
  $usrNmeStmt->execute(array(
    ':pid'=>$_SESSION['player_id']
  ));
  $usrNmeArray = $usrNmeStmt->fetch(PDO::FETCH_ASSOC);

  // Returns the user to the group that this bracket is in
  if (isset($_POST['returnGroup'])) {
    header('Location: group.php?group_id='.$_GET['group_id']);
    return true;
  };

?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title>Review | Bracket Referee</title>
  </head>
  <body>
    <h1>Bracket Review</h1>
    <h2>Player: <?php echo($usrNmeArray['userName']) ?></h2>
    <?php
      $pickListStmt = $pdo->prepare('SELECT pick_id,player_pick,layer,level_name,points FROM Picks JOIN Games JOIN Levels WHERE Picks.bracket_id=:bid AND Picks.game_id=Games.game_id AND Games.level_id=Levels.level_id ORDER BY Levels.layer ASC');
      $pickListStmt->execute(array(
        ':bid'=>htmlentities($_GET['bracket_id'])
      ));
      $pickArray = array();
      while ($onePush = $pickListStmt->fetch(PDO::FETCH_ASSOC)) {
        $pickArray[] = $onePush;
      };
      $totalScore = 0;
      $lastLayer = null;
      foreach ($pickArray as $layer) {
        if ($layer['layer'] != $lastLayer) {
          echo("<table border=1>
            <tr>
              <th>".$layer['level_name']."</th>
              <th>Points Earned</th>
            </tr>");
          foreach ($pickArray as $pick) {
            if ($pick['layer'] == $layer['layer']) {
              $pickNameStmt = $pdo->prepare('SELECT team_name FROM Teams WHERE team_id=:pid');
              $pickNameStmt->execute(array(
                ':pid'=>$pick['player_pick']
              ));
              $pickName = $pickNameStmt->fetch(PDO::FETCH_ASSOC);
              $checkPtsStmt = $pdo->prepare('SELECT winner_id,player_pick FROM Games JOIN Picks WHERE Picks.pick_id=:pid2 AND Picks.bracket_id=:bid AND Games.game_id=Picks.game_id');
              $checkPtsStmt->execute(array(
                ':bid'=>htmlentities($_GET['bracket_id']),
                ':pid2'=>$pick['pick_id']
              ));
              $checkResult = $checkPtsStmt->fetch(PDO::FETCH_ASSOC);
              $winnerId = $checkResult['winner_id'];
              $playerPick = $checkResult['player_pick'];
              $pointsEarned = 0;
              if ($winnerId == $playerPick) {
                $pointsEarned = $layer['points'];
                $totalScore += $layer['points'];
              };
              echo("<tr>
                <td>".$pickName['team_name']."</td>
                <td>".$pointsEarned."</td>
              </tr>");
            };
          };
          $lastLayer = $layer['layer'];
        };
        echo("</table></br>");
      };
    ?>
    <h3>
      <u>Total Score:</u> <?php echo($totalScore) ?>
    </h3>
    <form method="POST">
      <input type="submit" name="returnGroup" value="<-- BACK" />
    </form>
  </body>
</html>

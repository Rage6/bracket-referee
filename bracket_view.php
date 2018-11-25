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

  // Prevents someone from manually switching players after logging in
  $findToken = $pdo->prepare('SELECT token FROM Players WHERE player_id=:pid');
  $findToken->execute(array(
    ':pid'=>$_SESSION['player_id']
  ));
  $playerToken = $findToken->fetch(PDO::FETCH_ASSOC);
  if ($_SESSION['token'] != $playerToken['token']) {
    $_SESSION['message'] = "<b style='color:red'>Your current token does not coincide with your account's token. Reassign a new token by logging back in.</b>";
    unset($_SESSION['player_id']);
    unset($_SESSION['token']);
    header('Location: index.php');
    return false;
  };

  // To find this bracket's player idea
  $brkPlyStmt = $pdo->prepare('SELECT player_id FROM Brackets WHERE :bid=bracket_id');
  $brkPlyStmt->execute(array(
    ':bid'=>htmlentities($_GET['bracket_id'])
  ));
  $brkPlyId = $brkPlyStmt->fetch(PDO::FETCH_ASSOC);

  // To get this player's name for the title
  $usrNmeStmt = $pdo->prepare('SELECT userName FROM Players WHERE player_id=:pid');
  $usrNmeStmt->execute(array(
    ':pid'=>$brkPlyId['player_id']
  ));
  $usrNmeArray = $usrNmeStmt->fetch(PDO::FETCH_ASSOC);

  // To delete this bracket
  if (isset($_POST['deleteBracket'])) {
    $deleteStmt = $pdo->prepare('DELETE FROM Brackets WHERE bracket_id=:bid');
    $deleteStmt->execute(array(
      ':bid'=>htmlentities($_GET['bracket_id'])
    ));
    $_SESSION['message'] = "<b style='color:green'>Bracket successfully deleted</b>";
    header('Location: group.php?group_id='.$_GET['group_id']);
  };

  // Returns the user to the group that this bracket is in
  if (isset($_POST['returnGroup'])) {
    header('Location: group.php?group_id='.$_GET['group_id']);
    return true;
  };

  // echo("Session:</br>");
  // print_r($_SESSION);
  // echo("</br>");
  // echo("Post:</br>");
  // print_r($_POST);
  // echo("</br>");
  // echo("Get:</br>");
  // print_r($_GET);
  // echo("</br>");

?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title>Review | Bracket Referee</title>
    <script
    src="https://code.jquery.com/jquery-3.3.1.min.js"
    integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="
    crossorigin="anonymous"></script>
    <script src="main.js"></script>
  </head>
  <body>
    <h1>Bracket Review</h1>
    <h2>Player: <?php echo($usrNmeArray['userName']) ?></h2>
    <?php
      if ($brkPlyId['player_id'] == $_SESSION['player_id']) {
        echo("<p id='showDelBox'><u>Delete Bracket?</u></p>");
      };
    ?>
    <div id="delBox">
      <p>
        <i>Are you sure?</i> This bracket and its results will be permanently deleted.
      </p>
      <form method='POST'>
        <input type='submit' name='deleteBracket' value='DELETE'/>
        <span id="hideDelBox">CANCEL</span>
      </form>
      </br>
    </div>
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
          echo("</table></br>");
          $lastLayer = $layer['layer'];
        };
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

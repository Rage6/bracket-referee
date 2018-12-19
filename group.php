<?php
  session_start();
  require_once("pdo.php");

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

  // Sends the user back to the Player file
  if (isset($_POST['returnPlayer'])) {
    header('Location: player.php');
    return true;
  };

  // Recalls the current group's name
  $grpNameStmt = $pdo->prepare('SELECT group_name,admin_id FROM Groups WHERE group_id=:gid');
  $grpNameStmt->execute(array(
    ':gid'=>htmlentities($_GET['group_id'])
  ));
  $grpNameResult = $grpNameStmt->fetch(PDO::FETCH_ASSOC);

  // Recalls the administrator's username
  $grpUserResult = $grpNameResult['admin_id'];
  $adminStmt = $pdo->prepare('SELECT userName FROM Players WHERE player_id=:aid');
  $adminStmt->execute(array(
    ':aid'=>$grpUserResult
  ));
  $adminResult = $adminStmt->fetch(PDO::FETCH_ASSOC);

  // Recalls all of the players in this group
  $grpAllStmt = $pdo->prepare('SELECT userName,Groups_Players.player_id FROM Players JOIN Groups_Players WHERE Players.player_id=Groups_Players.player_id AND Groups_Players.group_id=:gid');
  // $grpAllStmt = $pdo->prepare('SELECT userName,Groups_Players.player_id FROM Players JOIN Groups_Players WHERE Players.player_id=Groups_Players.player_id AND Groups_Players.group_id=:gid');
  $grpAllStmt->execute(array(
    ':gid'=>htmlentities($_GET['group_id'])
  ));

  // Recalls the tournament's info for this group
  $tournStmt = $pdo->prepare('SELECT tourn_id,tourn_name,level_total,start_date,bracket_id FROM Groups JOIN Tournaments JOIN Brackets WHERE Groups.group_id=:gid AND Groups.fk_tourn_id=Tournaments.tourn_id');
  $tournStmt->execute(array(
    ':gid'=>htmlentities($_GET['group_id'])
  ));
  $tournArray = $tournStmt->fetch(PDO::FETCH_ASSOC);

  // Create a bracket with the current group by going to 'bracket_make.php'
  if (isset($_POST['make_bracket'])) {
    $isMember = false;
    while ($onePlayer = $grpAllStmt->fetch(PDO::FETCH_ASSOC)) {
      $onePlayerId = (int)$onePlayer['player_id'];
      if ($onePlayerId == $_SESSION['player_id']) {
        $isMember = true;
      };
    };
    if ($isMember == true) {
      header('Location: bracket_make.php?group_id='.$_GET['group_id']);
      return true;
    } else {
      $_SESSION['message'] = "<b style='color:red'>You must join this group before making your bracket</b>";
      header('Location: group.php?group_id='.$_GET['group_id']);
      return false;
    };
  };

  // Checks to see if the current player is already in this group
  $canJoinStmt = $pdo->prepare('SELECT COUNT(main_id) FROM Groups_Players WHERE group_id=:gid AND player_id=:pid');
  $canJoinStmt->execute(array(
    ':gid'=>htmlentities($_GET['group_id']),
    ':pid'=>$_SESSION['player_id']
  ));
  $canJoinResult = $canJoinStmt->fetch(PDO::FETCH_ASSOC);

  // Adds the current player to this group
  if (isset($_POST['joinGroup'])) {
    $newJoinStmt = $pdo->prepare('INSERT INTO Groups_Players(group_id,player_id) VALUES (:gid,:pid)');
    $newJoinStmt->execute(array(
      ':gid'=>htmlentities($_GET['group_id']),
      ':pid'=>$_SESSION['player_id']
    ));
    $_SESSION['message'] = "<b style='color:green'>New player added</b>";
    header('Location: player.php');
    return true;
  };

  // Remove current player from this group
  if (isset($_POST['leaveGroup'])) {
    $leaveGrpStmt = $pdo->prepare('DELETE FROM Groups_Players WHERE group_id=:gid AND player_id=:pid');
    $leaveGrpStmt->execute(array(
      ':gid'=>htmlentities($_GET['group_id']),
      ':pid'=>$_SESSION['player_id']
    ));
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
    <title><?php echo($grpNameResult['group_name']) ?> | Bracket Referee</title>
    <link rel="stylesheet" type="text/css" href="style/output.css"/>
    <script
    src="https://code.jquery.com/jquery-3.3.1.min.js"
    integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="
    crossorigin="anonymous"></script>
    <script src="main.js"></script>
  </head>
  <body>
    <div id="groupPage">
      <div id="introTitle">Welcome to</div>
      <div id="groupTitle"><?php echo($grpNameResult['group_name']) ?></div>
      <div class="allTitles">Tournament:</div>
      <table id="tournTable">
        <tr>
          <td>Name</td>
          <td><?php echo($tournArray['tourn_name']) ?></td>
        </tr>
        <tr>
          <td>Rounds</td>
          <td><?php echo($tournArray['level_total']) ?></td>
        </tr>
        <tr>
          <td>Start Date</td>
          <td><?php echo($tournArray['start_date']) ?></td>
        </tr>
        <tr>
          <td>Director</td>
          <td><?php echo($adminResult['userName']) ?></td>
        </tr>
      </table>
      <?php
        if ($grpNameResult['admin_id'] == $_SESSION['player_id']) {
          $urlPrefix = "http://localhost:8888/bracket-referee/group_edit.php?group_id=";
          // $urlPrefix = "https://bracket-referee.herokuapp.com/bracket-referee/group_edit.php?group_id=";
          $urlId = $_GET['group_id'];
          echo(" <u><a href='".$urlPrefix.$urlId."'>(EDIT)</a></u>");
        };
      ?>
      <?php
        if ($canJoinResult['COUNT(main_id)'] > 0) {
          echo("
          <div class='allTitles'>Current Players:</div>
          <table border='1px solid black'>
            <tr>
              <th>Username</th>
              <th>Bracket?</th>
              <th>Score</th>
            </tr>");
            $hasBracket = false;
            while ($playerRow = $grpAllStmt->fetch(PDO::FETCH_ASSOC)) {
              // Detects if the user has a bracket
              $bracketStmt = $pdo->prepare('SELECT bracket_id,total_score FROM Brackets WHERE player_id=:pid AND group_id=:gid');
              $bracketStmt->execute(array(
                ':pid'=>$playerRow['player_id'],
                ':gid'=>htmlentities($_GET['group_id'])
              ));
              $bracketArray = $bracketStmt->fetch(PDO::FETCH_ASSOC);
              if (is_array($bracketArray)==false || count($bracketArray) <= 0) {
                $bracketStatus = "NO";
                $bracketTotal = "---";
              } else {
                $bracketID = $bracketArray['bracket_id'];
                $bracketStatus = "<a href=bracket_view.php?group_id=".$_GET['group_id']."&bracket_id=".$bracketID.">YES</a>";
                $bracketTotal = 0;
                if ($playerRow['player_id'] == $_SESSION['player_id']) {
                  $hasBracket = true;
                };
              };
              // Detects the user's score IF they have a bracket
              if ($bracketStatus != "NO") {
                $findPointsStmt = $pdo->prepare('SELECT player_pick,winner_id,points FROM Picks JOIN Games JOIN Levels WHERE Picks.bracket_id=:bid AND Picks.game_id=Games.game_id AND Levels.level_id=Games.level_id');
                $findPointsStmt->execute(array(
                  ':bid'=>$bracketID
                ));
                while ($onePick = $findPointsStmt->fetch(PDO::FETCH_ASSOC)) {
                  if ($onePick['player_pick'] == $onePick['winner_id']) {
                    $bracketTotal += $onePick['points'];
                  };
                };
              };
              echo("
              <tr>
                <td>".$playerRow['userName']."</td>
                <td>".$bracketStatus."</td>
                <td>".$bracketTotal."</td>
              </tr>");
            };
            echo("</table>");
          };
        ?>
      <?php
        if ($canJoinResult['COUNT(main_id)'] > 0) {
          if ($hasBracket == false) {
            echo("<div id='bracketButton'><form method='POST'>
              <input type='submit' name='make_bracket' value='CREATE YOUR BRACKET'/>
            </form></div>");
          };
        };
      ?>
      <div class="allTitles">Tournament Results</div>
      <?php
        $gameListStmt = $pdo->prepare('SELECT game_id,team_a,team_b,winner_id,layer,level_name,get_wildcard FROM Groups JOIN Games JOIN Levels WHERE Groups.group_id=:gid AND Groups.fk_tourn_id=Games.tourn_id AND Games.level_id=Levels.level_id ORDER BY layer ASC');
        $gameListStmt->execute(array(
          ':gid'=>htmlentities($_GET['group_id'])
        ));
        // $currentLayer = "-1";
        $currentLayer = null;
        while ($oneGame = $gameListStmt->fetch(PDO::FETCH_ASSOC)) {
          $newLayer = $oneGame['layer'];
          if ($currentLayer != $newLayer) {
            $roundTitle = $oneGame['level_name'];
            // if ($currentLayer != "0") {
            //   echo("</table>");
            // };
            if ($currentLayer == null) {
              echo("<div class='allRounds'><div>".$roundTitle."</div>");
            } else {
              echo("</div><div class='allRounds'><div>".$roundTitle."</div>");
            };
            $currentLayer = $newLayer;
          };
          $team_a = $oneGame['team_a'];
          $getTeamA = $pdo->prepare('SELECT team_name FROM Teams WHERE :aid=team_id');
          $getTeamA->execute(array(
            ':aid'=>$team_a
          ));
          if ($oneGame['get_wildcard'] == 0) {
            $team_b = $oneGame['team_b'];
            $getTeamB = $pdo->prepare('SELECT team_name FROM Teams WHERE :bid=team_id');
            $getTeamB->execute(array(
              ':bid'=>$team_b
            ));
          } else {
            $nextGameId = $oneGame['game_id'];
            $getWildWinId = $pdo->prepare('SELECT winner_id FROM Games WHERE is_wildcard=1 AND next_game=:ngid');
            $getWildWinId->execute(array(
              ':ngid'=>$nextGameId
            ));
            $team_b = $getWildWinId->fetch(PDO::FETCH_ASSOC)['winner_id'];
            $getTeamB = $pdo->prepare('SELECT team_name FROM Teams WHERE :bid=team_id');
            $getTeamB->execute(array(
              ':bid'=>$team_b
            ));
          };
          $winnerTeam = $oneGame['winner_id'];
          $a_name = $getTeamA->fetch(PDO::FETCH_ASSOC);
          $b_name = $getTeamB->fetch(PDO::FETCH_ASSOC);
          if ($team_a == $winnerTeam) {
            $a_name = "<div><span style='color:white;background-color:green'>".$a_name['team_name']."</span>";
            $b_name = "<span>".$b_name['team_name']."</span></div>";
          } elseif ($team_b == $winnerTeam) {
            $a_name = "<div><span>".$a_name['team_name']."</span>";
            $b_name = "<span style='color:white;background-color:green'>".$b_name['team_name']."</span></div>";
          } else {
            $a_name = "<div><span>".$a_name['team_name']."</span>";
            $b_name = "<span>".$b_name['team_name']."</span></div>";
          };
          echo($a_name);
          echo($b_name);
        };
        echo("</div>")
      ?>
      <?php
        if (isset($_SESSION['message'])) {
          echo($_SESSION['message']);
          unset($_SESSION['message']);
        };
      ?>
      <form method="POST">
        <input type="submit" name="returnPlayer" value="<-- BACK " />
        <?php
          if ($canJoinResult['COUNT(main_id)'] == 0) {
            echo("<input type='submit' name='joinGroup' value=' JOIN -->'>");
          };
          if ($canJoinResult['COUNT(main_id)'] > 0 && $grpNameResult['admin_id'] != $_SESSION['player_id']) {
            echo("<h3 id='leaveGrpButton'>Leave this group?</h3>");
            echo("<div id='leaveGrpBox'>
              <p>Are you sure? Your <u>bracket</u> and <u>results</u> will be <b>permanently deleted</b>.</p>
              <input type='submit' name='leaveGroup' value='[X] LEAVE '>
              <span id='cancelLeave'> CANCEL </span>
              </div>");
          };
        ?>
      </form>
    </div>
  </body>
</html>

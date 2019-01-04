<?php
  session_start();
  require_once("pdo.php");

  $ifInviteStmt = $pdo->prepare('SELECT group_name,link_key,private FROM Groups WHERE group_id=:gp');
  $ifInviteStmt->execute(array(
    ':gp'=>htmlentities($_GET['group_id'])
  ));
  $ifInvite = $ifInviteStmt->fetch(PDO::FETCH_ASSOC);

  // finds current host
  $currentHost = $_SERVER['HTTP_HOST'];

  // Prevents entering this page w/o logging in
  if (!isset($_SESSION['player_id'])) {
    if (isset($_GET['invite'])) {
      if ($ifInvite['private'] == 1) {
        // Example (Private, "The First One"): http://localhost:8888/bracket-referee/group.php?group_id=1&invite=true&link_key=11111111111111111111
        if ($_GET['link_key'] == $ifInvite['link_key']) {
          header('Location: group_invite.php?group_id='.$_GET['group_id']."&invite=".$_GET['invite']."&link_key=".$_GET['link_key']);
          return true;
        } else {
          $_SESSION['message'] = "<b style='color:red'>Your invitation link was incorrect. Contact the group creator in order to get the correct link.</b>";
          header('Location: index.php');
          return false;
        };
      } else {
        // Example (Public): http://localhost:8888/bracket-referee/group.php?group_id=2&invite=true
        header('Location: group_invite.php?group_id='.$_GET['group_id']);
        return false;
      };
    } else {
      $_SESSION['message'] = "<b style='color:red'>You must log in or create an account to join a group.</b>";
      header('Location: index.php');
      return false;
    };
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
  $grpAllStmt->execute(array(
    ':gid'=>htmlentities($_GET['group_id'])
  ));
  $grpAllArray = [];
  while ($isPlayer = $grpAllStmt->fetch(PDO::FETCH_ASSOC)) {
    $grpAllArray[] = [$isPlayer['userName'],$isPlayer['player_id']];
  };

  // This determines if a) the group is 'private' and b) if the current player is already joined. If not joined, it will confirm that they are invited and with the correct link_key
  if ($ifInvite['private'] == 1) {
    $isMember = false;
    for ($checkNum = 0; $checkNum < sizeof($grpAllArray); $checkNum++) {
      $checkPlayerId = $grpAllArray[$checkNum][1];
      if ($checkPlayerId == $_SESSION['player_id']) {
        $isMember = true;
      };
    };
    if ($isMember == false) {
      if ($_GET['invite'] == true) {
        if ($ifInvite['link_key'] != $_GET['link_key']) {
          $_SESSION['message'] = "<b style='color:red'>Your invite key was incorrect</b>";
          header('Location: player.php');
          return false;
        };
      } else {
        $_SESSION['message'] = "<b style='color:red'>This private group requiring an invite link</b>";
        header('Location: player.php');
        return false;
      };
    };
  };

  // Recalls the tournament's info for this group
  $tournStmt = $pdo->prepare('SELECT tourn_id,tourn_name,level_total,start_date FROM Groups JOIN Tournaments WHERE Groups.group_id=:gid AND Groups.fk_tourn_id=Tournaments.tourn_id');
  $tournStmt->execute(array(
    ':gid'=>htmlentities($_GET['group_id'])
  ));
  $tournArray = $tournStmt->fetch(PDO::FETCH_ASSOC);

  // Create a bracket with the current group by going to 'bracket_make.php'
  if (isset($_POST['make_bracket'])) {
    $isMember = false;
    for ($playerNum = 0; $playerNum < sizeof($grpAllArray); $playerNum++) {
      $onePlayerId = $grpAllArray[$playerNum][1];
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
  // [UPDATE] Here, also add in code that deletes the player's bracket and picks (if any)
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
    <link href="https://fonts.googleapis.com/css?family=Bevan|Catamaran|Special+Elite|Staatliches" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="style/output.css"/>
    <script
    src="https://code.jquery.com/jquery-3.3.1.min.js"
    integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="
    crossorigin="anonymous"></script>
    <script src="main.js"></script>
  </head>
  <body>
    <div id="groupPage">
      <form method="POST">
        <input type="submit" name="returnPlayer" value="<<  BACK " />
        <?php
          if ((int)$canJoinResult['COUNT(main_id)'] == 0) {
            echo("<input id='joinBttn' type='submit' name='joinGroup' value='JOIN  >>'>");
          };
        ?>
      </form>
      <div class="allTitles">Group:</div>
      <div id="groupTitle"><?php echo($grpNameResult['group_name']) ?></div>
      <?php
        if (isset($_SESSION['message'])) {
          echo("<div id='message'>".$_SESSION['message']."</div>");
          unset($_SESSION['message']);
        };
      ?>
      <div id="tournTableTitle" class="allTitles">Tournament:</div>
      <table id="tournTable">
        <tr>
          <th>Name: </td>
          <td><?php echo($tournArray['tourn_name']) ?></td>
        </tr>
        <tr>
          <th>Rounds: </td>
          <td><?php echo($tournArray['level_total']) ?></td>
        </tr>
        <tr>
          <th>Start Date: </td>
          <td><?php echo($tournArray['start_date']) ?></td>
        </tr>
        <tr>
          <th>Director: </td>
          <td>
            <?php echo($adminResult['userName']) ?>
            <?php
              if ($grpNameResult['admin_id'] == $_SESSION['player_id']) {
                if ($currentHost == 'localhost:8888') {
                  $urlPrefix = "http://localhost:8888/bracket-referee/group_edit.php?group_id=";
                } else {
                  $urlPrefix = "https://bracket-referee.herokuapp.com/group_edit.php?group_id=";
                };
                $urlId = $_GET['group_id'];
                echo(" <a style='text-decoration:none' href='".$urlPrefix.$urlId."'>(EDIT)</a>");
              };
            ?>
          </td>
        </tr>
      </table>
      <?php
        if ((int)$canJoinResult['COUNT(main_id)'] > 0) {
          echo("
          <div class='allTitles'>Current Players:</div>
          <table id='playerTable'>
            <tr>
              <th>Username</th>
              <th>Bracket?</th>
              <th>Score</th>
            </tr>");
            $hasBracket = false;
            for ($rowNum = 0; $rowNum < sizeof($grpAllArray); $rowNum++) {
              $playerRow = $grpAllArray[$rowNum];
              // Detects if the user has a bracket
              $bracketStmt = $pdo->prepare('SELECT bracket_id,total_score FROM Brackets WHERE player_id=:pid AND group_id=:gid');
              $bracketStmt->execute(array(
                ':pid'=>$playerRow[1],
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
                if ($playerRow[1] == $_SESSION['player_id']) {
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
                <td>".$playerRow[0]."</td>
                <td>".$bracketStatus."</td>
                <td>".$bracketTotal."</td>
              </tr>");
            };
            echo("</table>");
          };
        ?>
      <?php
        if ((int)$canJoinResult['COUNT(main_id)'] > 0) {
          if ($hasBracket == false) {
            echo("<div id='bracketButton'><form method='POST'>
              <input type='submit' name='make_bracket' value='CREATE YOUR BRACKET'/>
            </form></div>");
          };
        };
      ?>
      <div class="allTitles">Tournament Results</div>
      <div id="groupScrollBox">
        <div id="scrollLeft"> << PREV</div>
        <div id="scrollRight"> NEXT >> </div>
      </div>
      <?php
        $gameListStmt = $pdo->prepare('SELECT game_id,team_a,team_b,winner_id,layer,level_name,get_wildcard FROM Groups JOIN Games JOIN Levels WHERE Groups.group_id=:gid AND Groups.fk_tourn_id=Games.tourn_id AND Games.level_id=Levels.level_id ORDER BY layer ASC');
        $gameListStmt->execute(array(
          ':gid'=>htmlentities($_GET['group_id'])
        ));
        $currentLayer = null;
        while ($oneGame = $gameListStmt->fetch(PDO::FETCH_ASSOC)) {
          $newLayer = $oneGame['layer'];
          if ($currentLayer != $newLayer) {
            $roundTitle = $oneGame['level_name'];
            $roundNum = 0;
            if ($currentLayer == null) {
              echo("<div id='layer_".$newLayer."' class='allRounds' data-check='true'><div class='rowTitle'>".$roundTitle."</div>");
            } else {
              echo("</div><div id='layer_".$newLayer."' class='allRounds' data-round='".$newLayer."'><div class='rowTitle'>".$roundTitle."</div>");
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
            $a_name = "<div class='allRows'><div style='color:white;background-color:green'>".$a_name['team_name']."</div>";
            $b_name = "<div>".$b_name['team_name']."</div></div>";
          } elseif ($team_b == $winnerTeam) {
            $a_name = "<div class='allRows'><div>".$a_name['team_name']."</div>";
            $b_name = "<div style='color:white;background-color:green'>".$b_name['team_name']."</div></div>";
          } else {
            $a_name = "<div class='allRows'><div>".$a_name['team_name']."</div>";
            $b_name = "<div>".$b_name['team_name']."</div></div>";
          };
          echo($a_name);
          echo($b_name);
        };
        echo("</div>")
      ?>
      <?php
        if ((int)$canJoinResult['COUNT(main_id)'] > 0 && $grpNameResult['admin_id'] != $_SESSION['player_id']) {
          echo("<div id='leaveGrpButton'>Unjoin this group?</div>");
          echo("
            <div id='leaveGrpBox'>
              <p>Are you sure? Your <u>bracket</u> and <u>results</u> will be <b>permanently deleted</b>.</p>
              <div>
                <form method='POST'>
                  <input type='submit' name='leaveGroup' value='[X] UNJOIN '>
                  <span id='cancelLeave'><u>CANCEL</u></span>
                </form>
              </div>
            </div>");
        };
      ?>
    </div>
  </body>
</html>

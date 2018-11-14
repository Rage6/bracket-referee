<?php
  session_start();
  require_once("pdo.php");

  // Prevents entering this page w/o logging in
  if (!isset($_SESSION['player_id'])) {
    $_SESSION['message'] = "<b style='color:red'>You must log in or create an account to join a group.</b>";
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
    <script
    src="https://code.jquery.com/jquery-3.3.1.min.js"
    integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="
    crossorigin="anonymous"></script>
    <script src="main.js"></script>
  </head>
  <body>
    <h1>Group: <?php echo($grpNameResult['group_name']) ?></h1>
    <span>Director: <?php echo($adminResult['userName']) ?></span>
    <?php
      if ($grpNameResult['admin_id'] == $_SESSION['player_id']) {
        $urlPrefix = "http://localhost:8888/bracket-referee/group_edit.php?group_id=";
        // $urlPrefix = "https://bracket-referee.herokuapp.com/bracket-referee/group_edit.php?group_id=";
        $urlId = $_GET['group_id'];
        echo(" <span><u><a href='".$urlPrefix.$urlId."'>(EDIT)</a></u></span>");
      };
    ?>
    <h2>Players:</h2>
    <table border="1px solid black">
      <tr>
        <th>Username</th>
        <th>Bracket?</th>
        <th>Score</th>
      </tr>
      <?php
        while ($playerRow = $grpAllStmt->fetch(PDO::FETCH_ASSOC)) {
          // Detects if the user has a bracket
          $bracketStmt = $pdo->prepare('SELECT bracket_id,total_score FROM Brackets WHERE player_id=:pid AND group_id=:gid');
          $bracketStmt->execute(array(
            ':pid'=>$_SESSION['player_id'],
            ':gid'=>htmlentities($_GET['group_id'])
          ));
          $bracketArray = $bracketStmt->fetch(PDO::FETCH_ASSOC);
          if (is_array($bracketArray)==false || count($bracketArray) <= 0) {
            $bracketStatus = "NO";
            $bracketTotal = "---";
          } else {
            $bracketID = $bracketArray['bracket_id'];
            $bracketStatus = "<a href=bracket_view.php?group_id=".$_GET['group_id']."&bracket_id=".$bracketID.">YES</a>";
            $bracketTotal = $bracketArray['total_score'];
          };
          // Detects the user's score IF they have a bracket
          if ($bracketStatus != "NO") {
            // echo("bracket_id: ".$bracketID);
            // var_dump((int)$bracketID);
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
      ?>
    </table>
    <h2>Tournament:</h2>
    <table>
      <tr>
        <td>Name</td>
        <td><?php echo($tournArray['tourn_name']) ?></td>
      </tr>
      <tr>
        <td># of Rounds</td>
        <td><?php echo($tournArray['level_total']) ?></td>
      </tr>
      <tr>
        <td>Start Date</td>
        <td><?php echo($tournArray['start_date']) ?></td>
      </tr>
    </table>
    </br>
    <form method='POST'>
      <input type='submit' name='make_bracket' value='CREATE YOUR BRACKET'/>
    </form>
    </br>
    <h3>Tournament Results</h3>
    <?php
      $gameListStmt = $pdo->prepare('SELECT team_a,team_b,winner_id,layer,level_name FROM Groups JOIN Games JOIN Levels WHERE Groups.group_id=:gid AND Groups.fk_tourn_id=Games.tourn_id AND Games.level_id=Levels.level_id ORDER BY layer ASC');
      $gameListStmt->execute(array(
        ':gid'=>htmlentities($_GET['group_id'])
      ));
      $currentLayer = "0";
      while ($oneGame = $gameListStmt->fetch(PDO::FETCH_ASSOC)) {
        $newLayer = $oneGame['layer'];
        if ($currentLayer != $newLayer) {
          $roundTitle = $oneGame['level_name'];
          if ($currentLayer != "0") {
            echo("</table></br>");
          };
          echo("<table border=1><tr><th colspan='2'>".$roundTitle."</th></tr>");
          $currentLayer = $newLayer;
        };
        $team_a = $oneGame['team_a'];
        $getTeamA = $pdo->prepare('SELECT team_name FROM Teams WHERE :aid=team_id');
        $getTeamA->execute(array(
          ':aid'=>$team_a
        ));
        $team_b = $oneGame['team_b'];
        $getTeamB = $pdo->prepare('SELECT team_name FROM Teams WHERE :bid=team_id');
        $getTeamB->execute(array(
          ':bid'=>$team_b
        ));
        $winnerTeam = $oneGame['winner_id'];
        $a_name = $getTeamA->fetch(PDO::FETCH_ASSOC);
        $b_name = $getTeamB->fetch(PDO::FETCH_ASSOC);
        if ($team_a == $winnerTeam) {
          $a_name = "<tr><td style='color:white;background-color:green'>".$a_name['team_name']."</td>";
          $b_name = "<td>".$b_name['team_name']."</td></tr>";
        } elseif ($team_b == $winnerTeam) {
          $a_name = "<tr><td>".$a_name['team_name']."</td>";
          $b_name = "<td style='color:white;background-color:green'>".$b_name['team_name']."</td></tr>";
        } else {
          $a_name = "<tr><td>".$a_name['team_name']."</td></tr>";
          $b_name = "<tr><tr>".$b_name['team_name']."</td></tr>";
        };
        echo($a_name);
        echo($b_name);
        // print_r($a_name.$b_name).echo;
      };
      echo("</table></br>")
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
  </body>
</html>

<?php
  session_start();
  require_once("pdo.php");

  // Sets shows selection, first game, message, and comment dates in US/Eastern automatically
  date_default_timezone_set('US/Eastern');

  $ifInviteStmt = $pdo->prepare('SELECT group_name,link_key,private,admin_id FROM Groups WHERE group_id=:gp');
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
        if ($_GET['link_key'] == $ifInvite['link_key']) {
          header('Location: group_invite.php?group_id='.$_GET['group_id']."&invite=".$_GET['invite']."&link_key=".$_GET['link_key']);
          return true;
        } else {
          $_SESSION['message'] = "<b style='color:red'>Your invitation link was incorrect. Contact the group creator in order to get the correct link.</b>";
          header('Location: index.php');
          return false;
        };
      } else {
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
  $tournStmt = $pdo->prepare('SELECT tourn_id,tourn_name,level_total,start_date,selection_date,active FROM Groups JOIN Tournaments WHERE Groups.group_id=:gid AND Groups.fk_tourn_id=Tournaments.tourn_id');
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
    header('Location: group.php?group_id='.$_GET['group_id']);
    return true;
  };

  // Remove current player from this group and delete their bracket
  if (isset($_POST['leaveGroup'])) {
    $ifBrckIdStmt = $pdo->prepare('SELECT bracket_id FROM Brackets WHERE player_id=:bp AND group_id=:bg');
    $ifBrckIdStmt->execute(array(
      ':bp'=>$_SESSION['player_id'],
      ':bg'=>htmlentities($_GET['group_id'])
    ));
    $oneBrkId = $ifBrckIdStmt->fetch(PDO::FETCH_ASSOC);
    if ($oneBrkId != false) {
      $delPickStmt = $pdo->prepare('DELETE FROM Picks WHERE bracket_id=:pk');
      $delPickStmt->execute(array(
        ':pk'=>$oneBrkId['bracket_id']
      ));
      $delBrkStmt = $pdo->prepare('DELETE FROM Brackets WHERE bracket_id=:bkid');
      $delBrkStmt->execute(array(
        ':bkid'=>$oneBrkId['bracket_id']
      ));
    };
    $leaveGrpStmt = $pdo->prepare('DELETE FROM Groups_Players WHERE group_id=:gid AND player_id=:pid');
    $leaveGrpStmt->execute(array(
      ':gid'=>htmlentities($_GET['group_id']),
      ':pid'=>$_SESSION['player_id']
    ));
    header('Location: group.php?group_id='.$_GET['group_id']);
    return true;
  };

  // Posts a 'parent' message on the message board
  if (isset($_POST['parentMessage'])) {
    if (strlen($_POST['message']) > 0) {
      if (strlen($_POST['message']) < 301) {
        if ($_GET['group_id'] == $_POST['groupId']) {
          $parentPostStmt = $pdo->prepare("INSERT INTO Messages(message,post_time,player_id,group_id) VALUES(:msg,:pt,:pli,:gri)");
          $parentPostStmt->execute(array(
            ':msg'=>htmlentities($_POST['message']),
            ':pt'=>time(),
            ':pli'=>$_SESSION['player_id'],
            ':gri'=>htmlentities($_POST['groupId'])
          ));
          $_SESSION['message'] = "<div style='color:green'>Message Successful</div>";
          header('Location: group.php?group_id='.$_GET['group_id']);
          return true;
        } else {
          $_SESSION['message'] = "<div style='color:red'>Invalid GET request</div>";
          header('Location: player.php');
          return false;
        };
      } else {
        $_SESSION['message'] = "<div style='color:red'>Exceeded maximum 300 characters</div>";
        header('Location: group.php?group_id='.$_GET['group_id']);
        return false;
      };
    } else {
      $_SESSION['message'] = "<div style='color:red'>No information was included in this message</div>";
      header('Location: group.php?group_id='.$_GET['group_id']);
      return false;
    };
  };

  // Posts a 'child' comment on its 'parent' message
  if (isset($_POST['childMessage'])) {
    if (strlen($_POST['message']) > 0) {
      if (strlen($_POST['message']) < 301) {
        if ($_GET['group_id'] == $_POST['groupId']) {
          $childPostStmt = $pdo->prepare("INSERT INTO Messages(message,post_time,parent_id,player_id,group_id) VALUES(:msg,:pt,:prt,:pli,:gri)");
          $childPostStmt->execute(array(
            ':msg'=>htmlentities($_POST['message']),
            ':pt'=>time(),
            ':prt'=>htmlentities($_POST['parentId']),
            ':pli'=>$_SESSION['player_id'],
            ':gri'=>htmlentities($_POST['groupId'])
          ));
          $_SESSION['message'] = "<div style='color:green'>Comment Successful</div>";
          header('Location: group.php?group_id='.$_GET['group_id']);
          return true;
        } else {
          $_SESSION['message'] = "<div style='color:red'>Invalid GET request</div>";
          header('Location: player.php');
          return false;
        };
      } else {
        $_SESSION['message'] = "<div style='color:red'>Exceeded maximum 300 characters</div>";
        header('Location: group.php?group_id='.$_GET['group_id']);
        return false;
      };
    } else {
      $_SESSION['message'] = "<div style='color:red'>No information was included in this message</div>";
      header('Location: group.php?group_id='.$_GET['group_id']);
      return false;
    };
  };

  // Edit a parent message
  if (isset($_POST['changeMsg'])) {
    if (strlen($_POST['editText']) > 0) {
      $changeMsgStmt = $pdo->prepare("UPDATE Messages SET message=:mes WHERE message_id=:mgi");
      $changeMsgStmt->execute(array(
        ':mes'=>htmlentities($_POST['editText']),
        ':mgi'=>htmlentities($_POST['msgId'])
      ));
      $_SESSION['message'] = "<div style='color:green'>Message Updated</div>";
      header('Location: group.php?group_id='.$_GET['group_id']);
      return true;
    } else {
      $_SESSION['message'] = "<div style='color:red'>Messages cannot be empty</div>";
      header('Location: group.php?group_id='.$_GET['group_id']);
      return false;
    };
  };

  // Delete a parent message and all child messages
  if (isset($_POST['deleteMsg'])) {
    $deleteMsgStmt = $pdo->prepare("DELETE FROM Messages WHERE message_id=:mid OR parent_id=:mid");
    $deleteMsgStmt->execute(array(
      ':mid'=>htmlentities($_POST['msgId'])
    ));
    $_SESSION['message'] = "<div style='color:green'>Message Deleted</div>";
    header('Location: group.php?group_id='.$_GET['group_id']);
    return true;
  };

  // Checks to see if it past the deadline
  $currentTimestamp = time();
  $pastDeadline = false;
  if ($tournArray['start_date'] < $currentTimestamp) {
    $pastDeadline = true;
  };

  // Any message older than 30 days is automatically deleted
  $expirationDate = time() - 2592000;
  $checkDatesStmt = $pdo->prepare("DELETE FROM Messages WHERE :ex > post_time");
  $checkDatesStmt->execute(array(
    ':ex'=>$expirationDate
  ));

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
    <link rel="icon" type="image/x-icon" href="style/img/index/bracket_favicon.ico"/>
    <script
    src="https://code.jquery.com/jquery-3.3.1.min.js"
    integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="
    crossorigin="anonymous"></script>
    <script src="main.js"></script>
  </head>
  <body>
    <div id="groupPage">
      <form method="POST">
        <input type="submit" name="returnPlayer" value="<<  BACK " class="backBttn" />
        <?php
          if ((int)$canJoinResult['COUNT(main_id)'] == 0) {
            echo("<input id='joinBttn' type='submit' name='joinGroup' value='JOIN  >>'>");
          };
        ?>
      </form>
      <div id="groupTopRow">
      <div id="titleBox">
        <div id="titleTab" class="allTitles">Group:</div>
        <div id="groupTitle"><?php echo($grpNameResult['group_name']) ?></div>
        <?php
          if (isset($_SESSION['message'])) {
            echo("<div id='message'>".$_SESSION['message']."</div>");
            unset($_SESSION['message']);
          };
        ?>
      </div>
      <div id="tournBox">
        <div id="tournTableTitle" class="allSubtitles allTitles">Tournament:</div>
        <table id="tournTable" class="allTables" cellpadding="10">
          <tr>
            <td class="rowTitle">Name</td>
            <td class="rowValue"><?php echo($tournArray['tourn_name']) ?></td>
          </tr>
          <tr>
            <td class="rowTitle">Rounds</td>
            <td class="rowValue"><?php echo($tournArray['level_total']) ?></td>
          </tr>
          <tr>
            <td class="rowTitle">Teams Chosen</td>
            <td class="rowValue">
              <?php
                echo(date('m/d/Y', $tournArray['selection_date']))
              ?>
            </td>
          </tr>
          <tr>
            <td class="rowTitle">First Game</td>
            <td class="rowValue">
              <?php
                echo(date('m/d/Y g:ia', $tournArray['start_date']))
              ?> EST
            </td>
          </tr>
          <tr>
            <td class="rowTitle">Director</td>
            <td class="rowValue" style="overflow-x:scroll">
              <?php echo($adminResult['userName']) ?>
            </td>
          </tr>
          <?php
            if ($grpNameResult['admin_id'] == $_SESSION['player_id']) {
              if ($currentHost == 'localhost:8888') {
                $urlPrefix = "http://localhost:8888/bracket-referee/group_edit.php?group_id=";
              } else {
                $urlPrefix = "https://bracket-referee.herokuapp.com/group_edit.php?group_id=";
              };
              $urlId = $_GET['group_id'];
              echo("
              <tr>
                <td id='grpEditBttn' colspan='2'>
                  <a href='".$urlPrefix.$urlId."'>
                    <div>
                      Change Your Group?
                    </div>
                  </a>
                </td>
              </tr>");
            };
          ?>
        </table>
      </div>
    </div>

      <?php
        if ((int)$canJoinResult['COUNT(main_id)'] > 0) {
          echo("
          <div id='currentTitle' class='allSubtitles allTitles'>Players:</div>
          <div id='scrollPlayers'>
            <table id='playerTable' class='allTables'>
              <tr id='playersTopRow'>
                <th class='userHead'>Username</th>
                <th>Bracket?</th>
                <th>Score</th>
              </tr>");
            $hasBracket = false;
            $rowArray = [];
            for ($rowNum = 0; $rowNum < sizeof($grpAllArray); $rowNum++) {
              $playerRow = $grpAllArray[$rowNum];
              // Detects if the user has a bracket
              $bracketStmt = $pdo->prepare('SELECT bracket_id,total_score,player_id FROM Brackets WHERE player_id=:pid AND group_id=:gid');
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
                if ($bracketArray['player_id'] == $_SESSION['player_id'] || $pastDeadline == true) {
                  $bracketStatus = "<a href=bracket_view.php?group_id=".$_GET['group_id']."&bracket_id=".$bracketID.">YES</a>";
                } else {
                  $bracketStatus = "YES";
                };
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
              $rowArray[] = [
                "playerName" => $playerRow[0],
                "bracketStatus" => $bracketStatus,
                "bracketTotal" => $bracketTotal
              ];
            };
            // Now the players are ordered based on their scores...
            $sortedArray = [];
            foreach($rowArray as $key => $keyRow) {
              $sortedArray[$key] = $keyRow['bracketTotal'];
            };
            array_multisort($sortedArray,SORT_DESC,$rowArray);
            // ...and the final results are displayed
            for ($listNum = 0; $listNum < sizeof($rowArray); $listNum++) {
              echo("
              <tr>
                <td class='userCol'>".$rowArray[$listNum]['playerName']."</td>
                <td>".$rowArray[$listNum]['bracketStatus']."</td>
                <td>".$rowArray[$listNum]['bracketTotal']."</td>
              </tr>");
            };
            echo("</table></div>");
          } else {
            echo("
            <div id='currentTitle' class='allSubtitles allTitles'>Players:</div>
            <div id='scrollPlayers'>
              <table id='playerTable' class='allTables'>
                <tr id='playersTopRow'>
                  <th>Username</th>
                  <th>Bracket?</th>
                  <th>Score</th>
                </tr>
                <tr id='hideMemberList'>
                  <td colspan='3'>Becoming a member shows you a list of:
                    <ul>
                      <li>fellow member usernames</li>
                      <li>which ones have entered a bracket</li>
                      <li>each member's current score</li>
                    </ul>
                  </td>
                </tr>
              </table>
            </div>
            ");
          };
        ?>
      <?php
        if ($pastDeadline == false) {
          if ((int)$canJoinResult['COUNT(main_id)'] > 0) {
            if ($hasBracket == false) {
              echo("
              <div id='bracketButton'>
                <form method='POST'>
                  <input type='submit' name='make_bracket' value='CREATE YOUR BRACKET'/>
                </form>
              </div>");
            };
          };
        };
      ?>
      <?php
      // Here is where the 'Invite Link' is displayed (or not)
      if ($currentHost == 'localhost:8888') {
        $inviteLinkHead = $currentHost."/bracket-referee/group.php?group_id=".$_GET['group_id']."&invite=true";
      } else {
        $inviteLinkHead = $currentHost."/group.php?group_id=".$_GET['group_id']."&invite=true";
      };

      if ((int)$canJoinResult['COUNT(main_id)'] > 0) {
        if ($ifInvite['private'] == 1) {
          if ($ifInvite['admin_id'] == $_SESSION['player_id']) {
            echo(
              "<div id='inviteBox'>
                <div id='inviteTitle'>INVITE A PLAYER</div>
                <div class='inviteIntro'>Send the below link to your friends so that they can quickly join the arena!</div>
                <div class='inviteCopyLine'>
                  <div id='clickLink' class='inviteBttn'>COPY</div>
                  <div id='copyLink' class='inviteScroll'>".$inviteLinkHead."&link_key=".$ifInvite['link_key']."</div>
                </div>
                <div class='inviteIntro' style='font-size:1.5rem'>
                  <u>NOTE</u>: As the director of a private group, ONLY YOU are shown the above 'invitation link'. <i>However</i>, you DO NOT have absolute control over group memberships since the link that you email to others can be shared by the recipients too.
                </div>
              </div>");
          };
        } else {
          echo(
            "<div id='inviteBox'>
              <div id='inviteTitle'>INVITE A PLAYER</div>
              <div class='inviteIntro'>Send the below link to your friends so that they can quickly join the arena!</div>
              <div class='inviteCopyLine'>
                <div id='clickLink' class='inviteBttn'>COPY</div>
                <div id='copyLink' class='inviteScroll'>".$inviteLinkHead."</div>
              </div>
            </div>");
        };
      };
      ?>

      <div class="resultsAndMsg">
        <div class="resultsOnly">
          <div id="resultListTitle" class="allSubtitles allTitles">Game Results</div>
          <?php
            $gameListStmt = $pdo->prepare('SELECT game_id,team_a,team_b,winner_id,layer,level_name,get_wildcard FROM Groups JOIN Games JOIN Levels WHERE Groups.group_id=:gid AND Groups.fk_tourn_id=Games.tourn_id AND Games.level_id=Levels.level_id ORDER BY layer ASC');
            $gameListStmt->execute(array(
              ':gid'=>htmlentities($_GET['group_id'])
            ));
            $currentLayer = null;
            $rowColor = "lightgrey";
            while ($oneGame = $gameListStmt->fetch(PDO::FETCH_ASSOC)) {
              $newLayer = $oneGame['layer'];
              if ($currentLayer != $newLayer) {
                $roundTitle = $oneGame['level_name'];
                $roundNum = 0;
                if ($currentLayer == null) {
                  echo("<div id='layer_".$newLayer."' class='allRounds' data-check='true'><div class='rowTitle'><u>".$roundTitle."</u></div>");
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
              if ($rowColor == 'lightgrey') {
                $rowColor = "white";
              } else {
                $rowColor = "lightgrey";
              };
              if ($team_a == $winnerTeam && $winnerTeam != 0) {
                $a_name = "<div style='background-color:".$rowColor."' class='allRows'><div style='color:white;background-color:green'>".$a_name['team_name']."</div>";
                $b_name = "<div>".$b_name['team_name']."</div></div>";
              } elseif ($team_b == $winnerTeam && $winnerTeam != 0) {
                $a_name = "<div style='background-color:".$rowColor."' class='allRows'><div>".$a_name['team_name']."</div>";
                $b_name = "<div style='color:white;background-color:green'> ".$b_name['team_name']."</div></div>";
              } else {
                $a_name = "<div style='background-color:".$rowColor."' class='allRows'><div>".$a_name['team_name']."</div>";
                $b_name = "<div>".$b_name['team_name']."</div></div>";
              };
              echo($a_name);
              echo($b_name);
            };
            echo("</div>")
          ?>
          <div id="groupScrollBox">
            <div id="scrollLeft"> << PREV</div>
            <div id="scrollRight"> NEXT >> </div>
          </div>
        </div>
        <div class="messagesOnly">
          <div id="messageBoxTitle" class="allSubtitles allTitles">
            Messages
          </div>
          <div id="messageBoxContent" class="allRounds">
            <?php
              if ((int)$canJoinResult['COUNT(main_id)'] > 0) {
                $initGetReq = $_GET['group_id'];
                $msgListStmt = $pdo->prepare("SELECT message_id,message,parent_id,post_time,userName,Players.player_id FROM Messages JOIN Players WHERE Players.player_id=Messages.player_id AND group_id=:gi AND parent_id IS NULL ORDER BY post_time DESC");
                $msgListStmt->execute(array(
                  ':gi'=>htmlentities($_GET['group_id'])
                ));
                echo("
                  <div>
                    <form method='POST'>
                      <div>
                        <input type='hidden' value='".$_SESSION['player_id']."' name='playerId' />
                      </div>
                      <div>
                        <input type='hidden' value=".$initGetReq." name='groupId' />
                      </div>
                      <textarea class='inputMsgText' placeholder='Enter post here' name='message'></textarea>
                      <div>
                        <input type='submit' value='ENTER' name='parentMessage' />
                      </div>
                    </form>
                  </div>
                ");
                echo("<div class='scrollMsg'>");
                while ($oneMsg = $msgListStmt->fetch(PDO::FETCH_ASSOC)) {
                  $defaultTimezone = 'EST';
                  $postDate = new DateTime("now", new DateTimeZone($defaultTimezone));
                  $postDate->setTimestamp($oneMsg['post_time']);
                  $countingStmt = $pdo->prepare("SELECT COUNT(message_id) FROM Messages WHERE parent_id=:mei");
                  $countingStmt->execute(array(
                    ':mei'=>htmlentities($oneMsg['message_id'])
                  ));
                  $commentNum = $countingStmt->fetch(PDO::FETCH_ASSOC)['COUNT(message_id)'];
                  if ($_SESSION['player_id'] == $oneMsg['player_id']) {
                    echo("
                      <div class='oneMsgBox' id='oneMsgBox_".$oneMsg['message_id']."'>
                        <div class='oneMsgContent'>
                          <div class='oneMsgName'>
                            <div><i>".$oneMsg['userName']."</i></div>
                            <button class='msgEditBttn' data-edit='false' data-num=".$oneMsg['message_id'].">EDIT</button>
                          </div>
                          <div class='oneMsgText'>".$oneMsg['message']."</div>
                          <div class='oneMsgTime'>".$postDate->format('Y-m-d g:ia e')."</div>
                        </div>
                        <div class='commentBttn' data-comments='".$oneMsg['message_id']."'>
                          Comments (".$commentNum.")
                        </div>
                      </div>
                      <div class='oneMsgBox oneMsgEditBox' id='oneMsgEditBox_".$oneMsg['message_id']."'>
                        <div class='oneMsgContent'>
                          <div class='oneMsgName'>
                            <div><i>".$oneMsg['userName']."</i></div>
                            <div class='msgEditBttn' data-edit='true' data-num=".$oneMsg['message_id'].">X</div>
                          </div>
                          <form method='POST'>
                            <input type='hidden' name='msgId' value='".$oneMsg['message_id']."' />
                            <textarea class='oneMsgText' name='editText'>".$oneMsg['message']."</textarea>
                            <input type='submit' name='changeMsg' value='CHANGE' class='centerBttns' style='background-color:blue;color:white' />
                            <div class='centerBttns' style='margin-top:30px;margin-bottom:30px'> -- OR -- </div>
                            <input type='submit' name='deleteMsg' value='DELETE' class='centerBttns' style='background-color:red;color:white' />
                          </form>
                        </div>
                      </div>");
                  } else {
                    echo("
                      <div class='oneMsgBox'>
                        <div class='oneMsgContent'>
                          <div class='oneMsgName'>
                            <div><i>".$oneMsg['userName']."</i></div>
                            <div></div>
                          </div>
                          <div class='oneMsgText'>".$oneMsg['message']."</div>
                          <div class='oneMsgTime'>".$postDate->format('Y-m-d g:ia e')."</div>
                        </div>
                        <div class='commentBttn' data-comments='".$oneMsg['message_id']."'>Comments (".$commentNum.")</div>
                      </div>");
                  };
                  // This is where to put the comments
                  if ($commentNum > 0) {
                    echo("<div class='commentGroup' id='comment_".$oneMsg['message_id']."'>");
                      $getCommentsStmt = $pdo->prepare("SELECT * FROM Messages JOIN Players WHERE Messages.player_id=Players.player_id AND parent_id=:pri ORDER BY post_time ASC");
                      $getCommentsStmt->execute(array(
                        ':pri'=>$oneMsg['message_id']
                      ));
                      while ($oneComment = $getCommentsStmt->fetch(PDO::FETCH_ASSOC)) {
                        $commentDate = new DateTime("now", new DateTimeZone($defaultTimezone));
                        $commentDate->setTimestamp($oneComment['post_time']);
                        if ($_SESSION['player_id'] == $oneComment['player_id']) {
                          echo("
                            <div class='oneCommentBox' id='oneMsgBox_".$oneComment['message_id']."'>
                              <div class='oneCommentContent'>
                                <div class='oneCommentName'>
                                  <div><i>".$oneComment['userName']."</i></div>
                                  <button class='commentEditBttn' data-edit='false' data-num=".$oneComment['message_id'].">EDIT</button>
                                </div>
                                <div class='oneCommentText'>".$oneComment['message']."</div>
                                <div class='oneCommentTime'>".$commentDate->format('Y-m-d g:ia e')."</div>
                              </div>
                            </div>
                            <div class='oneCommentBox oneCommentEditBox' id='oneMsgEditBox_".$oneComment['message_id']."'>
                              <div class='oneCommentContent'>
                                <div class='oneCommentName'>
                                  <div><i>".$oneComment['userName']."</i></div>
                                  <button class='commentEditBttn' data-edit='true' data-num=".$oneComment['message_id'].">X</button>
                                </div>
                                <form method='POST'>
                                  <input type='hidden' name='msgId' value='".$oneComment['message_id']."' />
                                  <textarea class='oneMsgText' name='editText'>".$oneComment['message']."</textarea>
                                  <input type='submit' name='changeMsg' value='CHANGE' class='centerBttns centerCommentBttns' style='background-color:blue;color:white' />
                                  <div class='centerBttns centerCommentBttns' style='margin-top:30px;margin-bottom:30px'> -- OR -- </div>
                                  <input type='submit' name='deleteMsg' value='DELETE' class='centerBttns centerCommentBttns' style='background-color:red;color:white' />
                                </form>
                              </div>
                            </div>
                          ");
                        } else {
                          echo("
                            <div class='oneCommentBox'>
                              <div class='oneCommentContent'>
                                <div class='oneCommentName'>
                                  <div><i>".$oneComment['userName']."</i></div>
                                  <div></div>
                                </div>
                                <div class='oneCommentText'>".$oneComment['message']."</div>
                                <div class='oneCommentTime'>".$commentDate->format('Y-m-d g:ia e')."</div>
                              </div>
                            </div>
                          ");
                        };
                      };

                      echo("
                        <div class='insertCommentBox'>
                          <form method='POST'>
                            <div>
                              <input type='hidden' value='".$_SESSION['player_id']."' name='playerId' />
                            </div>
                            <div>
                              <input type='hidden' value='".$oneMsg['message_id']."' name='parentId' />
                            </div>
                            <div>
                              <input type='hidden' value=".$initGetReq." name='groupId' />
                            </div>
                            <textarea class='inputCommentText' placeholder='Enter comment here' name='message'></textarea>
                            <div>
                              <input type='submit' value='ENTER' name='childMessage' />
                            </div>
                          </form>
                        </div>
                      ");
                    echo("</div>");
                  } else {
                    echo("
                      <div class='commentGroup' id='comment_".$oneMsg['message_id']."'>
                      <div class='insertCommentBox'>
                        <form method='POST'>
                          <div>
                            <input type='hidden' value='".$_SESSION['player_id']."' name='playerId' />
                          </div>
                          <div>
                            <input type='hidden' value='".$oneMsg['message_id']."' name='parentId' />
                          </div>
                          <div>
                            <input type='hidden' value=".$initGetReq." name='groupId' />
                          </div>
                          <textarea class='inputCommentText' placeholder='Enter comment here' name='message'></textarea>
                          <div>
                            <input type='submit' value='ENTER' name='childMessage' />
                          </div>
                        </form>
                      </div>
                      </div>
                    ");
                  };
                };
                echo("</div>");
              } else {
                echo("
                  <div>
                    <div class='emptyMsgBrd'>
                      Join our group to read or comment our message board! Click 'JOIN' at the top of the page to become a member.
                    </div>
                  </div>
                ");
              };
            ?>
          </div>
        </div>
      </div>

      <?php
        if ((int)$canJoinResult['COUNT(main_id)'] > 0 && $grpNameResult['admin_id'] != $_SESSION['player_id']) {
          echo("<div id='leaveGrpButton'>Leave this group?</div>");
          echo("
            <div id='leaveGrpBox'>
              <p>Are you sure? Your <u>bracket</u> and <u>results</u> will be <b>permanently deleted</b>.</p>
              <div>
                <form method='POST'>
                  <input type='submit' name='leaveGroup' value=' LEAVE '>
                  <span id='cancelLeave'><u>CANCEL</u></span>
                </form>
              </div>
            </div>");
        };
      ?>
  </body>
</html>

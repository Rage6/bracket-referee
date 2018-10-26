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
  $grpAllStmt = $pdo->prepare('SELECT userName FROM Players JOIN Groups_Players WHERE Players.player_id=Groups_Players.player_id AND Groups_Players.group_id=:gid');
  $grpAllStmt->execute(array(
    ':gid'=>htmlentities($_GET['group_id'])
  ));

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
        // $urlPrefix = "http://localhost:8888/bracket-referee/group_edit.php?group_id=";
        $urlPrefix = "https://bracket-referee.herokuapp.com/bracket-referee/group_edit.php?group_id=";
        $urlId = $_GET['group_id'];
        echo(" <span><u><a href='".$urlPrefix.$urlId."'>(EDIT)</a></u></span>");
      };
    ?>
    <h2>Players:</h2>
    <table>
      <tr>
        <th>Username</th>
      </tr>
      <?php
        while ($playerRow = $grpAllStmt->fetch(PDO::FETCH_ASSOC)) {
          echo("<tr><td>".$playerRow['userName']."</td></tr>");
        };
      ?>
    </table>
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

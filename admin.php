<?php
  session_start();
  require_once("pdo.php");

  // Prevents entering this page w/o logging in
  if (!isset($_SESSION['player_id'])) {
    $_SESSION['message'] = "<b style='color:red'>You must log in or create an account to enter the Administrative Center.</b>";
    header('Location: index.php');
    return false;
  };

  // Prevents hacker from manually switching players after logging in
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

  // Only allows the administrator onto this page
  if ($_SESSION['player_id'] != 0) {
    $_SESSION['message'] = "<b style='color:red'>You are not authorized to enter the Administrative Center</b>";
    header('Location: player.php');
    return false;
  };



?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title>Bracket Referee | Administrator</title>
    <!-- <link href="https://fonts.googleapis.com/css?family=Bevan|Catamaran|Special+Elite|Staatliches" rel="stylesheet"> -->
    <link rel="stylesheet" type="text/css" href="style/output.css"/>
    <link rel="icon" type="image/x-icon" href="style/img/index/bracket_favicon.ico"/>
    <link rel="icon" type="image/x-icon" href="style/img/index/bracket_favicon.ico"/>
    <!-- <script
    src="https://code.jquery.com/jquery-3.3.1.min.js"
    integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="
    crossorigin="anonymous"></script> -->
    <script src="main.js"></script>
  </head>
  <body>
    <div id="adminPage">
      <div id="adminTitle">
        ADMINISTRATIVE CENTER
      </div>
      <div>
        <div class="adminSubtitle">
          Find The Desired Tournament
        </div>
        <div id="searchBox">
          <form type="POST">
            <input id="text" type="text" name="nameTyped" placeholder="Enter approximate name" />
            <input id="submit" type="submit" name="findTourn" value="SEARCH" />
          </form>
          <table id='tournResults'>
            <?php
              if (!isset($_POST['findTourn'])) {
                echo("<td style='color: lightgrey'>Results go here... </td>"
                );
              };
            ?>
          </table>
        </div>
      </div>
      <div>
        <div class="adminSubtitle">
          Wildcard Round
        </div>
        <div id="wildcardBox" class="adminGameBox">

        </div>
      </div>
      <div>
        <div class="adminSubtitle">
          Regular Rounds
        </div>
        <div id="regularBox" class="adminGameBox">

        </div>
      </div>
      <div>
        <div class="adminSubtitle">
          Third-Place Round
        </div>
        <div id="thirdPlaceBox" class="adminGameBox">

        </div>
      </div>
    </div>
  </body>
  <script>
    // All of the teams w/ their id's
    $(document).ready(()=>{
      $.getJSON('json_allTeams.php',(teamList));
    });
    // All of the tournaments w/ their id's
    $(document).ready(()=>{
      $.getJSON('json_allTourn.php',(tournList));
    });
  </script>
</html>

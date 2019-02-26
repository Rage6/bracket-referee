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

  // // Searches for the desired tournament
  if (isset($_POST['findTourn'])) {
    if (strlen($_POST['nameString']) > 0) {
      $findTourn = $pdo->prepare('SELECT * FROM Tournaments WHERE tourn_name LIKE "%":nm"%" ORDER BY tourn_name ASC');
      $findTourn->execute(array(
        ':nm'=>htmlentities($_POST['nameString'])
      ));
      $tournResults = [];
      while ($oneTourn = $findTourn->fetch(PDO::FETCH_ASSOC)) {
        $tournResults[] =
        "<tr>
          <td>".$oneTourn['tourn_name']."</td>
          <td>".$oneTourn['tourn_id']."</td>
        </tr>";
      };
      if (count($tournResults) <= 0) {
        $tournResults[] = "<tr><td><i>No such tournaments found</i></td></tr>";
      };
      $_SESSION['tournFound'] = $tournResults;
      header('Location: admin.php');
      return true;
    } else {
      $_SESSION['message'] = "<b style='color:red'>You must enter a name to get the results</b>";
      header('Location: admin.php');
      return false;
    };
  } else {
    $tournResults[] = "<tr><td><i>Waiting for search...</i></td></tr>";
  };

  // To select the tournament, submit its ID number here
  if (isset($_POST['pickTourn'])) {
    if (strlen($_POST['enterID']) > 0) {
      $isPresent = false;
      $compareID = $pdo->prepare('SELECT COUNT(tourn_id) FROM Tournaments WHERE tourn_id=:tid');
      $compareID->execute(array(
        ':tid'=>htmlentities($_POST['enterID'])
      ));
      $idCount = $compareID->fetch(PDO::FETCH_ASSOC);
      if ($idCount['COUNT(tourn_id)'] == "1") {
        $_SESSION['tournId'] = htmlentities((int)$_POST['enterID']);
        $tournDataStmt = $pdo->prepare('SELECT tourn_name,level_total,wildcard,third_place FROM Tournaments WHERE tourn_id=:td');
        $tournDataStmt->execute(array(
          ':td'=>htmlentities($_SESSION['tournId'])
        ));
        $tournData = $tournDataStmt->fetch(PDO::FETCH_ASSOC);
        $_SESSION['tournData'] = $tournData;
        $_SESSION['message'] = "<b style='color:green'>Tournament selected</b>";
        header('Location: admin.php');
        return true;
      } else {
        $_SESSION['message'] = "<b style='color:red'>That ID number was not in the database</b>";
        header('Location: admin.php');
        return false;
      };
    } else {
      $_SESSION['message'] = "<b style='color:red'>An ID number must be entered</b>";
      header('Location: admin.php');
      return false;
    };
  };

  // To "unselect" the current tournament
  if (isset($_POST['exitTourn'])) {
    unset($_SESSION['tournId']);
    unset($_SESSION['tournData']);
    $_SESSION['message'] = "<b style='color:green'>Tournament exit successful</b>";
    header('Location: admin.php');
    return true;
  };

  echo("Session:</br>");
  print_r($_SESSION);
  echo("</br>");
  echo("Post:</br>");
  print_r($_POST);
  echo("</br>");
  echo("Get:</br>");
  print_r($_GET);
  echo("</br>");
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title>Bracket Referee | Administrator</title>
    <link href="https://fonts.googleapis.com/css?family=Bevan|Catamaran|Special+Elite|Staatliches" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="style/output.css"/>
    <link rel="icon" type="image/x-icon" href="style/img/index/bracket_favicon.ico"/>
    <link rel="icon" type="image/x-icon" href="style/img/index/bracket_favicon.ico"/>
    <script
    src="https://code.jquery.com/jquery-3.3.1.min.js"
    integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="
    crossorigin="anonymous"></script>
    <script src="main.js"></script>
  </head>
  <body>
    <div id="adminPage">
      <div id="adminTitle">
        ADMINISTRATIVE CENTER
      </div>
      <?php
        if (isset($_SESSION['message'])) {
          echo("<div style='text-align:center;border:none'>".$_SESSION['message']."</div>");
          unset($_SESSION['message']);
        };
      ?>
      <div>
        <div class="adminSubtitle">
          Find A Tournament
        </div>
        <div id="searchBox">
          <form method="POST">
            <input id="text" type="text" name="nameString" placeholder="Enter approximate name" />
            <input id="submit" type="submit" name="findTourn" value="SEARCH" />
            <table id='tournResults'>
              <?php
                if (isset($_SESSION['tournFound'])) {
                  $tournResults = $_SESSION['tournFound'];
                };
                for ($tournNum = 0; $tournNum < count($tournResults); $tournNum++) {
                  echo($tournResults[$tournNum]);
                };
                unset($_SESSION['tournFound']);
              ?>
            </table>
          </form>
        </div>
      </div>
      <div>
        <div class="adminSubtitle">
          Enter An ID Number
        </div>
        <div id="selectBox">
          <form method="POST">
            <input type="text" name="enterID" placeholder="ID # here" />
            <input type="submit" name="pickTourn" value="SUBMIT"/>
          </form>
        </div>
        <?php
          if (isset($_SESSION['tournId'])) {
            echo("
            <div id='unselectBox'>
              <form method='POST'>
                <input type='submit' name='exitTourn' value='LEAVE TOURNAMENT' />
              </form>
            </div>");
          };
        ?>
      </div>
      <?php
        if (isset($_SESSION['tournName'])) {
          echo("
            <div>
              <b>".$_SESSION['tournName']."</b>
            </div>"
          );
        };
      ?>
      <?php
        if (isset($_SESSION['tournData'])) {
          echo("
          <div style='text-align:center;background-color:green;color:white'>
            <div>You are working on:</div>
            <div><b>".$_SESSION['tournData']['tourn_name']."</b></div>
          </div>");
        };
      ?>
      <div>
        <div class="adminSubtitle">
          Wildcard Round
        </div>
        <div id="wildcardBox" class="adminGameBox">
          <?php
            if (isset($_SESSION['tournData']) && $_SESSION['tournData']['wildcard'] == "1") {
              $findWildStmt = $pdo->prepare('SELECT * FROM Games WHERE tourn_id=:trn AND is_wildcard=1');
              $findWildStmt->execute(array(
                ':trn'=>htmlentities((int)$_SESSION['tournId'])
              ));
              while ($oneID = $findWildStmt->fetch(PDO::FETCH_ASSOC)) {
                echo(
                  "<div>".$oneID['game_id']."</div>
                  <div style='display:flex;justify-content:space-around'>
                    <div>".(int)$oneID['team_a']."</div>
                    <div>".(int)$oneID['team_b']."</div>
                  </div>"
                );
                echo("</br>");
              };
            } else {
              echo("N/A");
            };
          ?>
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
          <?php
            if (isset($_SESSION['tournData']) && $_SESSION['tournData']['third_place'] == "1") {
              echo("Third Place game goes here");
            } else {
              echo("N/A");
            };
          ?>
        </div>
      </div>
    </div>
  </body>
  <script>
    // // All of the teams w/ their id's
    // $(document).ready(()=>{
    //   $.getJSON('json_allTeams.php',(teamList));
    // });
    // // All of the tournaments w/ their id's
    // $(document).ready(()=>{
    //   $.getJSON('json_allTourn.php',(tournList));
    // });
  </script>
</html>

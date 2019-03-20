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

  // Searches for the desired tournament
  if (isset($_POST['findTourn'])) {
    if (strlen($_POST['nameString']) > 0) {
      $findTourn = $pdo->prepare('SELECT * FROM Tournaments WHERE tourn_name LIKE "%":nm"%" OR tourn_id=:nm ORDER BY tourn_name ASC');
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

  // Switches a tournament active or inactive
  // Note: This POST doesn't end on its own. Instead, it has to run into the 'pickTourn' POST below it in order to update the $_SESSION['tournData'] array
  if (isset($_POST['makeActive']) || isset($_POST['makeInactive'])) {
    if (isset($_POST['makeActive'])) {
      $makeActiveStmt = $pdo->prepare('UPDATE Tournaments SET active=1 WHERE tourn_id=:to');
      $makeActiveStmt->execute(array(
        ':to'=>htmlentities($_SESSION['tournId'])
      ));
      $_POST['pickTourn'] = "SUBMIT";
      $_POST['enterID'] = $_SESSION['tournId'];
    } else {
      $makeInactiveStmt = $pdo->prepare('UPDATE Tournaments SET active=0 WHERE tourn_id=:to');
      $makeInactiveStmt->execute(array(
        ':to'=>htmlentities($_SESSION['tournId'])
      ));
      $_POST['pickTourn'] = "SUBMIT";
      $_POST['enterID'] = $_SESSION['tournId'];
    };
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
        $tournDataStmt = $pdo->prepare('SELECT tourn_name,level_total,wildcard,third_place, active FROM Tournaments WHERE tourn_id=:td');
        $tournDataStmt->execute(array(
          ':td'=>htmlentities($_SESSION['tournId'])
        ));
        $tournData = $tournDataStmt->fetch(PDO::FETCH_ASSOC);
        $_SESSION['tournData'] = $tournData;
        $_SESSION['message'] = "<b style='color:green'>Tournament Setup Complete</b>";
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

  // To search for a team_id by team_name (or vice versa)
  if (isset($_POST['teamSearch'])) {
    $teamList = [];
    if (strlen($_POST['teamInput']) > 0) {
      $findTeamStmt = $pdo->prepare('SELECT * FROM Teams WHERE team_name LIKE "%":tn"%" OR team_id=:tn');
      $findTeamStmt->execute(array(
        ':tn'=>htmlentities($_POST['teamInput'])
      ));
      while ($oneTeam = $findTeamStmt->fetch(PDO::FETCH_ASSOC)) {
        $teamList[] = $oneTeam;
      };
      $_SESSION['teamList'] = $teamList;
      if (count($teamList) > 0) {
        $_SESSION['message'] = "<b style='color:green'>".count($teamList)." team(s) found</b>";
      } else {
        $_SESSION['message'] = "<b style='color:green'>No teams with that name or ID</b>";
      };
      header('Location: admin.php');
      return true;
    } else {
      $_SESSION['message'] = "<b style='color:red'>Text must be entered</b>";
      header('Location: admin.php');
      return false;
    };
  };

  // To make a new team
  if (isset($_POST['teamMake'])) {
    if (strlen($_POST['teamName']) > 0) {
      $makeNewTeam = $pdo->prepare('INSERT INTO Teams(team_name) VALUES (:nw)');
      $makeNewTeam->execute(array(
        ':nw'=>htmlentities($_POST['teamName'])
      ));
      $_SESSION['message'] = "<b style='color:green'>'".htmlentities($_POST['teamName'])."' was added to the database</b>";
      header('Location: admin.php');
      return true;
    } else {
      $_SESSION['message'] = "<b style='color:red'>A name must be included</b>";
      header('Location: admin.php');
      return false;
    };
  };

  // To change the teams and/or winners of a tournament's games
  if (isset($_POST['changeGames'])) {
    $_SESSION['changeInput'] = $_POST;
    $countChanges = ((count($_SESSION['changeInput']) - 1) / 6);
    $gameNum = 0;
    // Wildcard games
    if ($_SESSION['tournData']['wildcard'] == "1") {
      for ($oneWild = $gameNum; $oneWild < $countChanges; $oneWild++) {
        if ($_SESSION['changeInput']['isWild_'.$oneWild] == "1") {
          $gameId = htmlentities($_SESSION['changeInput']['gameId_'.$oneWild]);
          $teamA = htmlentities($_SESSION['changeInput']['teamA_'.$oneWild]);
          $teamB = htmlentities($_SESSION['changeInput']['teamB_'.$oneWild]);
          $winner = htmlentities($_SESSION['changeInput']['gameWin_'.$oneWild]);
          $upGameData = $pdo->prepare('UPDATE Games SET team_a=:ta,team_b=:tb,winner_id=:wn WHERE game_id=:gid');
          $upGameData->execute(array(
            ':ta'=>(int)$teamA,
            ':tb'=>(int)$teamB,
            ':wn'=>(int)$winner,
            ':gid'=>(int)$gameId
          ));
        };
      };
      $gameNum = 0;
    };
    // Regular games
    for ($oneRegular = $gameNum; $oneRegular < $countChanges; $oneRegular++) {
      if ($_SESSION['changeInput']['isWild_'.$oneRegular] == "0" && $_SESSION['changeInput']['isThird_'.$oneRegular] == "0") {
        $gameId = htmlentities($_SESSION['changeInput']['gameId_'.$oneRegular]);
        $teamA = htmlentities($_SESSION['changeInput']['teamA_'.$oneRegular]);
        $teamB = htmlentities($_SESSION['changeInput']['teamB_'.$oneRegular]);
        $winner = htmlentities($_SESSION['changeInput']['gameWin_'.$oneRegular]);
        $upGameData = $pdo->prepare('UPDATE Games SET team_a=:ta,team_b=:tb,winner_id=:wn WHERE game_id=:gid');
        $upGameData->execute(array(
          ':ta'=>(int)$teamA,
          ':tb'=>(int)$teamB,
          ':wn'=>(int)$winner,
          ':gid'=>(int)$gameId
        ));
      };
    };
    $gameNum = 0;
    // Third-place game
    if ($_SESSION['tournData']['third_place'] == "1") {
      for ($oneThird = $gameNum; $oneThird < $countChanges; $oneThird++) {
        if ($_SESSION['changeInput']['isThird_'.$oneThird] == "1") {
          $gameId = htmlentities($_SESSION['changeInput']['gameId_'.$oneThird]);
          $teamA = htmlentities($_SESSION['changeInput']['teamA_'.$oneThird]);
          $teamB = htmlentities($_SESSION['changeInput']['teamB_'.$oneThird]);
          $winner = htmlentities($_SESSION['changeInput']['gameWin_'.$oneThird]);
          $upGameDataStmt = $pdo->prepare('UPDATE Games SET team_a=:ta,team_b=:tb,winner_id=:wn WHERE game_id=:gid');
          $upGameDataStmt->execute(array(
            ':ta'=>(int)$teamA,
            ':tb'=>(int)$teamB,
            ':wn'=>(int)$winner,
            ':gid'=>(int)$gameId
          ));
        };
      };
      $gameNum = 0;
    };
    // This resets any teams,winners with the ID 0 as NULL
    $clearZero = $pdo->prepare('UPDATE Games SET team_a=NULL,team_b=NULL,winner_id=NULL WHERE team_a=0 AND team_b=0');
    $clearZero->execute(array());
    unset($_SESSION['changeInput']);
    $_SESSION['message'] = "<b style='color:green'>Update successful</b>";
    header('Location: admin.php');
    return true;
  };

  // echo("<pre>");
  // echo("SESSION:");
  // print_r($_SESSION);
  // echo("POST:");
  // print_r($_POST);
  // echo("GET:");
  // print_r($_GET);
  // echo("</pre>");

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
          Enter Tournament ID
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
        if (isset($_SESSION['tournData']) && $_SESSION['tournData']['active'] == 1) {
          $firstInput = "style='background-color:red;color:white'";
          $secondInput = "style='background-color:green;color:white'";
        } else {
          $firstInput = "style='background-color:green;color:white'";
          $secondInput = "style='background-color:red;color:white'";
        };
        if (isset($_SESSION['tournData'])) {
          echo("
          <div id='tournTitleBox'>
            <div>You are working on:</div>
            <div style='margin-bottom:80px'><b>".$_SESSION['tournData']['tourn_name']."</b></div>
            <div>
              <form method='POST'>
                <input id='activeBttn' ".$firstInput." type='submit' name='makeActive' value='ACTIVE' />
                <input id='inactiveBttn' ".$secondInput." type='submit' name='makeInactive' value='INACTIVE' />
              </form>
            </div>
          </div>");
        };
      ?>
      <?php
        if (isset($_SESSION['teamList']) && count($_SESSION['teamList']) > 0) {
          echo("<table id='teamListTable'>");
          for ($teamNum = 0; $teamNum < count($_SESSION['teamList']); $teamNum++) {
            echo("<tr>
                    <td>".$_SESSION['teamList'][$teamNum]['team_name']."</td>
                    <td>".$_SESSION['teamList'][$teamNum]['team_id']."</td>
                  </tr>");
          };
          echo("</table>");
          unset($_SESSION['teamList']);
        };
      ?>
      <form method='POST'>
        <?php
          $gameNum = 0;
          $currentColor = "lightgrey";
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
                    "<div style='margin-bottom:0;padding:40px 0;background-color:".$currentColor."'>
                      <div style='display:flex;justify-content:space-around'>
                        <div>Game #".$oneID['game_id']."</div>
                        <div style='background-color:orange'>Next Game: ".$oneID['next_game']."</div>
                      </div>
                      <table>
                        <tr style='border:none'>
                          <td>
                            TEAM A
                          </td>
                          <td>
                            TEAM B
                          </td>
                          <td>
                            WINNER
                          </td>
                        </tr>
                        <tr>
                          <input type='hidden' name='gameId_".$gameNum."' value=".(int)$oneID['game_id']." />
                          <input type='hidden' name='isWild_".$gameNum."' value='1' />
                          <input type='hidden' name='isThird_".$gameNum."' value='0' />
                          <td>
                            <input type='text' name='teamA_".$gameNum."' value=".(int)$oneID['team_a']." />
                          </td>
                          <td>
                            <input type='text' name='teamB_".$gameNum."' value=".(int)$oneID['team_b']." />
                          </td>
                          <td>
                            <input type='text' name='gameWin_".$gameNum."' value=".(int)$oneID['winner_id']."
                          </td>
                        </tr>
                      </table>
                    </div>"
                  );
                  if ($currentColor == "white") {
                    $currentColor = "lightgrey";
                  } else {
                    $currentColor = "white";
                  };
                  $gameNum++;
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
            <?php
            if (isset($_SESSION['tournId'])) {
              $findRegStmt = $pdo->prepare('SELECT * FROM Games JOIN Levels WHERE Games.level_id=Levels.level_id AND Games.tourn_id=:trn AND Games.is_third<>1 AND Games.is_wildcard<>1 ORDER BY Levels.layer ASC');
              $findRegStmt->execute(array(
                ':trn'=>htmlentities((int)$_SESSION['tournId'])
              ));
              $levelName = null;
              while ($oneID = $findRegStmt->fetch(PDO::FETCH_ASSOC)) {
                if ($levelName == null || $levelName != $oneID['level_name']) {
                  echo("<div style='background-color:blue;color:white'>".$oneID['level_name']."</div>");
                };
                echo(
                  "<div style='margin-bottom:0;padding:40px 0;background-color:".$currentColor.";font-size:3rem'>
                    <div style='display:flex;justify-content:space-around'>
                      <div>Game #".$oneID['game_id']."</div>
                      <div style='background-color:orange'>Next Game: ".$oneID['next_game']."</div>
                    </div>
                    <table>
                      <tr style='border:none'>
                        <td>
                          TEAM A
                        </td>
                        <td>
                          TEAM B
                        </td>
                        <td>
                          WINNER
                        </td>
                      </tr>
                      <tr>
                        <input type='hidden' name='gameId_".$gameNum."' value=".(int)$oneID['game_id']." />
                        <input type='hidden' name='isWild_".$gameNum."' value='0' />
                        <input type='hidden' name='isThird_".$gameNum."' value='0' />
                        <td>
                          <input type='text' name='teamA_".$gameNum."' value=".(int)$oneID['team_a']." />
                        </td>
                        <td>
                          <input type='text' name='teamB_".$gameNum."' value=".(int)$oneID['team_b']." />
                        </td>
                        <td>
                          <input type='text' name='gameWin_".$gameNum."' value=".(int)$oneID['winner_id']."
                        </td>
                      </tr>
                    </table>
                  </div>"
                );
                if ($currentColor == "white") {
                  $currentColor = "lightgrey";
                } else {
                  $currentColor = "white";
                };
                $gameNum++;
                $levelName = $oneID['level_name'];
              };
            } else {
              echo("N/A");
            };
            ?>
          </div>
        </div>
        <div>
          <div class="adminSubtitle">
            Third-Place Round
          </div>
          <div id="thirdPlaceBox" class="adminGameBox">
            <?php
              if (isset($_SESSION['tournData']) && $_SESSION['tournData']['third_place'] == "1") {
                $findThirdStmt = $pdo->prepare('SELECT * FROM Games WHERE tourn_id=:trn AND is_third=1');
                $findThirdStmt->execute(array(
                  ':trn'=>htmlentities((int)$_SESSION['tournId'])
                ));
                while ($oneID = $findThirdStmt->fetch(PDO::FETCH_ASSOC)) {
                  echo(
                    "<div>Game #".$oneID['game_id']."</div>
                    <table>
                      <tr style='border:none'>
                        <td>
                          TEAM A
                        </td>
                        <td>
                          TEAM B
                        </td>
                        <td>
                          WINNER
                        </td>
                      </tr>
                      <tr>
                        <input type='hidden' name='gameId_".$gameNum."' value=".(int)$oneID['game_id']." />
                        <input type='hidden' name='isWild_".$gameNum."' value='0' />
                        <input type='hidden' name='isThird_".$gameNum."' value='1' />
                        <td>
                          <input type='text' name='teamA_".$gameNum."' value=".(int)$oneID['team_a']." />
                        </td>
                        <td>
                          <input type='text' name='teamB_".$gameNum."' value=".(int)$oneID['team_b']." />
                        </td>
                        <td>
                          <input type='text' name='gameWin_".$gameNum."' value=".(int)$oneID['winner_id']."
                        </td>
                      </tr>
                    </table>"
                  );
                  echo("</br>");
                  $gameNum++;
                };
              } else {
                echo("N/A");
              };
            ?>
          </div>
        </div>
        <?php
        if (isset($_SESSION['tournId'])) {
          echo("<input id='submitUpdate' type='submit' name='changeGames' value='SUBMIT' />");
        };
        ?>
      </form>

      <div id='aboutTeam'>
        <div>
          <div class='adminSubtitle teamBttn' id='findTeamBttn'>Find Team</div>
          <div class='adminSubtitle teamBttn' id='makeTeamBttn'>Make Team</div>
        </div>
        <div id='findTeamBox' class='teamBox'>
          <form method='POST'>
            <input type='text' name='teamInput' placeholder='Enter existing name or ID' /></br>
            <input type='submit' name='teamSearch' values='ENTER' />
          </form>
        </div>
        <div id='makeTeamBox' class='teamBox'>
          <form method='POST'>
            <input type='text' name='teamName' placeholder='Submit new team name' /></br>
            <input type='submit' name='teamMake' values='ENTER' />
          </form>
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

<?php
  session_start();
  require_once("pdo.php");

  // Finds the page's current host
  $currentHost = $_SERVER['HTTP_HOST'];

  // Uses the $_SESSION['player_id'] from logging in to find the rest of the player's data
  $recall = $pdo->prepare('SELECT email,userName,firstName,lastName FROM Players WHERE (player_id=:id)');
  $recall->execute(array(
    ':id'=>$_SESSION['player_id']
  ));
  $playerData = $recall->fetch(PDO::FETCH_ASSOC);

  // This is the prefix for all of the href that take a user to a givien group in the search list
  if ($currentHost == 'localhost:8888') {
    $groupLink = "http://localhost:8888/bracket-referee/group.php?group_id=";
  } else {
    $groupLink = "https://bracket-referee.herokuapp.com/group.php?group_id=";
  };

  // Prevents entering this page w/o logging in
  if (!isset($_SESSION['player_id'])) {
    $_SESSION['message'] = "<b style='color:red'>You must log in or create an account to view your profile.</b>";
    unset($_SESSION['player_id']);
    unset($_SESSION['token']);
    unset($_SESSION['playerToken']);
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

  // Finds a list of random, public groups to choose from
  // First, it learns all of the player's current groups to take them off of the list...
  $joinGrpIdList = [];
  $allCurrentGrps = $pdo->prepare('SELECT Groups.group_id FROM Groups JOIN Groups_Players WHERE Groups.group_id=Groups_Players.group_id AND player_id=:cid');
  $allCurrentGrps->execute(array(
    ':cid'=>$_SESSION['player_id']
  ));
  while ($oneCurGrp = $allCurrentGrps->fetch(PDO::FETCH_ASSOC)) {
    $joinGrpIdList[] = (int)$oneCurGrp['group_id'];
  };
  // Second, it randomly selects up to 20 groups...
  $randomGrpsStmt = $pdo->prepare('SELECT DISTINCT group_name,Groups.group_id FROM Groups JOIN Groups_Players WHERE Groups.group_id=Groups_Players.group_id AND Groups_Players.player_id<>:pid AND private=0 ORDER BY RAND() LIMIT 10');
  $randomGrpsStmt->execute(array(
    ':pid'=>$_SESSION['player_id']
  ));
  // Third, adds any selected to the list that are not already on the player's current list
  $randomList = [];
  while ($oneRandom = $randomGrpsStmt->fetch(PDO::FETCH_ASSOC)) {
    $duplicate = false;
    for ($listNum = 0; $listNum < count($joinGrpIdList); $listNum++) {
      if ($oneRandom['group_id'] == $joinGrpIdList[$listNum]) {
        $duplicate = true;
      };
    };
    if ($duplicate != true) {
      $randomList[] = [$oneRandom['group_name'],$oneRandom['group_id']];
    };
  };

  // Allows user to log out
  if (isset($_POST['logout'])) {
    $_SESSION['message'] = "<b style='color:green'>Log out successful</b>";
    unset($_SESSION['player_id']);
    unset($_SESSION['token']);
    unset($_SESSION['playerToken']);
    header('Location: index.php');
    return true;
  };

  // User can search for a group by their names
  if (isset($_POST['findGroup'])) {
    if (strlen($_POST['name']) > 0) {

      $findStmt = $pdo->prepare('SELECT group_name FROM Groups');
      $findList = $findStmt->execute();
      $_SESSION['search'] = htmlentities($_POST['name']);
      header('Location: player.php?inSearch=true');
      return true;
    } else {
      $_SESSION['message'] = "<b style='color:red'>Empty value</b>";
      header('Location: player.php');
      return false;
    }
  };

  // Start a new Group
  if (isset($_POST['new_group'])) {
    if (strlen($_POST['group_name']) > 0 && $_POST['tourn_id'] != 'null') {
      $linkKey = bin2hex(random_bytes(10));
      $groupStmt = $pdo->prepare('INSERT INTO Groups(admin_id,group_name,fk_tourn_id,private,link_key) VALUES (:pid,:gnm,:tid,:inv,:lky)');
      $groupStmt->execute(array(
        ':pid'=>$_SESSION['player_id'],
        ':gnm'=>htmlentities($_POST['group_name']),
        ':tid'=>htmlentities($_POST['tourn_id']),
        ':inv'=>intval($_POST['invitation']),
        ':lky'=>$linkKey
      ));
      $getIdStmt = $pdo->query("SELECT LAST_INSERT_ID()");
      $groupId = $getIdStmt->fetchColumn();
      $grpPlyStmt = $pdo->prepare('INSERT INTO Groups_Players(group_id,player_id) VALUES (:gr,:pl)');
      $grpPlyStmt->execute(array(
        ':gr'=>$groupId,
        ':pl'=>$_SESSION['player_id']
      ));
      $_SESSION['message'] = "<b style='color:green'>New group created!</b>";
      header('Location: group.php?group_id='.$groupId);
      return true;
    } else {
      $_SESSION['message'] = "<b style='color:red'>Group names and tournaments are required</b>";
      header('Location: player.php');
      return false;
    };
  };

  // Redirects to player_edit.php
  if (isset($_POST['edit'])) {
    unset($_SESSION['message']);
    header('Location: player_edit.php');
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
    <title><?php echo($playerData['userName']) ?> | Bracket Referee</title>
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
    <div id="playerPage">
      <div id="hqTitle">Bracket HQ</div>
      <?php
      if (isset($_SESSION['message'])) {
        echo("<div id='message'>".$_SESSION['message']."</div>");
        unset($_SESSION['message']);
      };
      ?>
      <div id="topRow">
        <div id="profIdBox" class="allBox">
          <div class="allInnerBox">
            <div class="allBoxTitle">
              Profile
            </div>
            <table id="profIdTable">
              <tr>
                <td class="profTitle">Name</th>
                <td class="profInput"><?php echo($playerData['firstName']." ".$playerData['lastName']) ?></td>
              </tr>
              <tr>
                <td class="profTitle">Username</th>
                <td class="profInput"><?php echo($playerData['userName']) ?></td>
              </tr>
              <tr>
                <td class="profTitle">Email</th>
                <td class="profInput"><?php echo($playerData['email']) ?></td>
              </tr>
            </table>
            <form id="profIdBtns" method='POST'>
              <input id="plrEdit" type='submit' name='edit' value='EDIT'/>
              <input id="plrLogOut" type='submit' name='logout' value='LOGOUT'/>
            </form>
          </div>
        </div>
        <div id="grpListBox" class="allBox">
          <div class="allInnerBox">
            <div class="allBoxTitle">
              Current Groups
            </div>
            <div id="completeList">
              <?php
                $groupList = $pdo->prepare('SELECT Groups.group_id,Groups.group_name FROM Groups JOIN Groups_Players WHERE Groups_Players.player_id=:id AND Groups.group_id=Groups_Players.group_id');
                $groupList->execute(array(
                  ':id'=>$_SESSION['player_id']
                ));
                $numCurrGrp = 0;
                while ($oneGrp = $groupList->fetch(PDO::FETCH_ASSOC)) {
                  $numCurrGrp++;
                };
                if ($numCurrGrp > 0) {
                  $groupList->execute(array(
                    ':id'=>$_SESSION['player_id']
                  ));
                  while ($row = $groupList->fetch(PDO::FETCH_ASSOC)) {
                    echo("<a href=".$groupLink.$row['group_id'].">
                            <div class='listContent'>
                              <div>".$row['group_name']."</div>
                              <div class='rightArrow'></div>
                            </div>
                          </a>");
                  };
                } else {
                  echo("
                  <div id='emptyCurrentList'>
                    Join a group below by...
                    <ul>
                      <li>searching for a group by name</li>
                      <li>creating your own group</li>
                      <li>browsing through the available public groups</li>
                    </ul>
                  </div>");
                };
              ?>
            </div>
          </div>
        </div>
      </div>
      <div id="groupBox" class="allBox">
        <div class="allInnerBox" id="optionInnerBox">
          <div class="allBoxTitle" id="grpBoxTitle">Join A Group</div>
          <div id="allNewOpts">
            <div class="newGrpOption">
              <div id="findGroup" class="allOptTitle">
                <span>Search For A Group</span>
                <span id="findGrpV">
                  <img id="findGrpVimg" src="style/img/player/down_arrow.jpg">
                </span>
              </div>
              <div id="findGroupBox">
                <form method="POST">
                  <input type="text" name="name"/>
                  <input id="searchBtn" type="submit" name="findGroup" value="SEARCH" />
                </form>
                <?php
                  $nameList = null;
                  if (isset($_SESSION['search'])) {
                    $findList = $pdo->prepare('SELECT group_name,group_id,admin_id FROM Groups WHERE private=0 AND group_name LIKE :nm');
                    $findList->execute(array(
                      ':nm'=>"%".$_SESSION['search']."%"
                    ));
                    if ($currentHost == 'localhost:8888') {
                      $url = "http://localhost:8888/bracket-referee/group.php?group_id=";
                    } else {
                      $url = "https://bracket-referee.herokuapp.com/group.php?group_id=";
                    };
                    while ($row = $findList->fetch(PDO::FETCH_ASSOC)) {
                      $nameList[] = $row['group_name'];
                      $startList[] = "<a href='".$url.$row['group_id']."'>";
                      $stopList[] = "</a>";
                    };
                    unset($_SESSION['search']);
                  };
                ?>
              </div>
              <?php
                if ($nameList != null) {
                  echo("<div id='searchResults'><div id='resultList'>");
                  $resultColor = "white";
                  for ($i = 0; $i < count($nameList); $i++) {
                    echo($startList[$i]."<p style='background-color:".$resultColor."'>".$nameList[$i]."</p>".$stopList[$i]);
                    if ($resultColor == "white") {
                      $resultColor = "lightgrey";
                    } else {
                      $resultColor = "white";
                    };
                  };
                  echo("</div>");
                  echo("<p>Groups found: ".$i."</p></div>");
                };
              ?>
            </div>
            <div class="newGrpOption">
              <div id="showAddBox" class="allOptTitle">
                <span>Create A New Group</span>
                <span id="showAddV">
                  <img id="showAddVimg" src="style/img/player/down_arrow.jpg">
                </span>
              </div>
              <div id="addGroupBox">
                <form method="POST">
                  <table>
                    <tr>
                      <td>Name:</td>
                      <td><input type='text' name='group_name'></td>
                    </tr>
                    <tr>
                      <td>Sport Type:</td>
                      <td>
                        <select name="tourn_id">
                          <option value='null'>Choose from...</option>
                          <?php
                            $tournStmt = $pdo->prepare('SELECT tourn_id,tourn_name FROM Tournaments WHERE active=1');
                            $tournStmt->execute();
                            while ($tournRow = $tournStmt->fetch(PDO::FETCH_ASSOC)) {
                              echo("<option value='".$tournRow['tourn_id']."'>".$tournRow['tourn_name']."</option>");
                            };
                          ?>
                        </select>
                      </td>
                    </tr>
                    <tr>
                      <td>Invite Level</td>
                      <td>
                        <select name="invitation">
                          <option value="1">PRIVATE</option>
                          <option value="0" selected>PUBLIC</option>
                        </select>
                      </td>
                    </tr>
                  </table>
                  <div>
                    <input id="submitNewGrp" type="submit" name="new_group" value="START">
                    <span id="cancelGroup"><u>CANCEL</u></span>
                  </div>
                </form>
              </div>
            </div>
            <div class="newGrpOption">
              <div class="allOptTitle">
                Available Groups
              </div>
              <div id="resultBox">
                <?php
                if ($currentHost == 'localhost:8888') {
                  $randomURL = "http://localhost:8888/bracket-referee/group.php?group_id=";
                } else {
                  $randomURL = "https://bracket-referee.herokuapp.com/group.php?group_id=";
                };
                $rowColor = "lightgrey";
                for ($randNum = 0; $randNum < count($randomList); $randNum++) {
                  $randomGrpId = $randomList[$randNum][1];
                  $randomGrpName = $randomList[$randNum][0];
                  if ($rowColor == "white") {
                    $rowColor = "lightgrey";
                  } else {
                    $rowColor = "white";
                  };
                  echo("<a href=".$randomURL.$randomGrpId."><p style='background-color:".$rowColor."'>".$randomGrpName."</p></a>");
                };
                ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </body>
</html>

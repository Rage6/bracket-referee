<?php
  session_start();
  require_once("pdo.php");

  // Uses the $_SESSION['player_id'] from logging in to find the rest of the player's data
  $recall = $pdo->prepare('SELECT email,userName,firstName,lastName FROM Players WHERE (player_id=:id)');
  $recall->execute(array(
    ':id'=>$_SESSION['player_id']
  ));
  $playerData = $recall->fetch(PDO::FETCH_ASSOC);

  // This is the prefix for all of the href that take a user to a givien group in the search list
  // Local host
  $groupLink = "http://localhost:8888/bracket-referee/group.php?group_id=";
  // ClearDB host
  // $groupLink = "https://bracket-referee.herokuapp.com/group.php?group_id=";

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
      header('Location: player.php');
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
      $groupStmt = $pdo->prepare('INSERT INTO Groups(admin_id,group_name,fk_tourn_id) VALUES (:pid,:gnm,:tid)');
      $groupStmt->execute(array(
        ':pid'=>$_SESSION['player_id'],
        ':gnm'=>htmlentities($_POST['group_name']),
        ':tid'=>htmlentities($_POST['tourn_id'])
      ));
      $getIdStmt = $pdo->query("SELECT LAST_INSERT_ID()");
      $groupId = $getIdStmt->fetchColumn();
      $grpPlyStmt = $pdo->prepare('INSERT INTO Groups_Players(group_id,player_id) VALUES (:gr,:pl)');
      $grpPlyStmt->execute(array(
        ':gr'=>$groupId,
        ':pl'=>$_SESSION['player_id']
      ));
      $_SESSION['message'] = "<b style='color:green'>New group created!</b>";
      header('Location: player.php');
      return true;
    } else {
      $_SESSION['message'] = "<b style='color:red'>Group names and tournaments are required</b>";
      header('Location: player.php');
      return false;
    };
  };

  // Delete user's account
  if (isset($_POST['deleteAcct'])) {
    $deleteStmt = $pdo->prepare('DELETE FROM Players WHERE player_id=:id');
    $deleteStmt->execute(array(
      ':id'=>$_SESSION['player_id']
    ));
    $_SESSION['message'] = "<b style='color:green'>Account deleted</b>";
    header('Location: index.php');
    return true;
  }

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
    <link rel="stylesheet" type="text/css" href="style/output.css"/>
    <script
    src="https://code.jquery.com/jquery-3.3.1.min.js"
    integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="
    crossorigin="anonymous"></script>
    <script src="main.js"></script>
  </head>
  <body>
    <div id="playerPage">
      <h1 id="hqTitle">Bracket HQ</h1>
      <?php
      if (isset($_SESSION['message'])) {
        echo($_SESSION['message']);
        unset($_SESSION['message']);
      };
      ?>
      <table id="profileId">
        <tr>
          <td class="profTitle">Name</th>
          <td><?php echo($playerData['firstName']) ?> <?php echo($playerData['lastName']) ?></td>
        </tr>
        <tr>
          <td class="profTitle">Username</th>
          <td><?php echo($playerData['userName']) ?></td>
        </tr>
        <tr>
          <td class="profTitle">Email</th>
          <td><?php echo($playerData['email']) ?></td>
        </tr>
      </table>
      <form method='POST'>
        <input type='submit' name='logout' value='LOGOUT'/>
        <input type='submit' name='edit' value='EDIT'/>
      </form>
      <div>
        <h3>Group List</h3>
        <table>
          <tr>
            <th>
              Name
            </th>
          </tr>
          <?php
            $groupList = $pdo->prepare('SELECT Groups.group_id,Groups.group_name FROM Groups JOIN Groups_Players ON Groups.group_id=Groups_Players.group_id AND Groups_Players.player_id=:id');
            $groupList->execute(array(
              ':id'=>$_SESSION['player_id']
            ));
            while ($row = $groupList->fetch(PDO::FETCH_ASSOC)) {
              echo("<tr><td><a href=".$groupLink.$row['group_id'].">".$row['group_name']."</td></tr>");
            };
          ?>
        </table>
      </div>
      <div id="groupBox">
        <h3 id="findGroup">Find An Existing Group?</h3>
        <div id="findGroupBox">
          <form method="POST">
            <input type="text" name="name"/>
            <input type="submit" name="findGroup" value="SEARCH" />
          </form>
          <?php
            $nameList = null;
            if (isset($_SESSION['search'])) {
              $findList = $pdo->prepare('SELECT group_name,group_id,admin_id FROM Groups WHERE group_name LIKE :nm');
              $findList->execute(array(
                ':nm'=>"%".$_SESSION['search']."%"
              ));
              $url = "http://localhost:8888/bracket-referee/group.php?group_id=";
              // $url = "https://bracket-referee.herokuapp.com/group.php?group_id=";
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
            echo("<table style='border:1px solid black'>");
            for ($i = 0; $i < count($nameList); $i++) {
              echo("<tr>");
              echo("<td>".$startList[$i].$nameList[$i].$stopList[$i]."</td>");
              echo("</tr>");
            };
            echo("</table>");
            echo("Total Found: ".$i);
          };
        ?>
        <h3 id="showAddBox">Create A New Group?</h3>
        <div id="addGroupBox">
          <form method="POST">
            <table>
              <tr>
                <td>Group name:</td>
                <td><input type='text' name='group_name'></td>
              </tr>
              <tr>
                <td>Tournament:</td>
                <td>
                  <select name="tourn_id">
                    <option value='null'>Choose from...</option>
                    <?php
                      $tournStmt = $pdo->prepare('SELECT tourn_id,tourn_name FROM Tournaments');
                      $tournStmt->execute();
                      while ($tournRow = $tournStmt->fetch(PDO::FETCH_ASSOC)) {
                        echo("<option value='".$tournRow['tourn_id']."'>".$tournRow['tourn_name']."</option>");
                      };
                    ?>
                  </select>
                </td>
              </tr>
            </table>
            <input type="submit" name="new_group" value="START">
          </form>
          <span id="cancelGroup">CANCEL</span>
        </div>
      </div>
      <h3 id="showDeleteBox">Delete your account?</h3>
      <div id="deleteBox">
        <b>Are you sure that you want to delete your account?
        <form method="POST">
          <input type="submit" name="deleteAcct" value="YES, delete my account"/>
        </form>
        <span id="cancelDelete">NO, keep my account</span>
      </div>
    </div>
  </body>
</html>

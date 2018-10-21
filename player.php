<?php
  session_start();
  require_once("pdo.php");

  // Uses the $_SESSION['player_id'] from logging in to find the rest of the player's data
  $recall = $pdo->prepare('SELECT email,userName,firstName,lastName FROM Players WHERE (player_id=:id)');
  $recall->execute(array(
    ':id'=>$_SESSION['player_id']
  ));
  $playerData = $recall->fetch(PDO::FETCH_ASSOC);

  // Prevents entering this page w/o logging in
  if (!isset($_SESSION['player_id'])) {
    $_SESSION['message'] = "<b style='color:red'>You must log in or create an account to view your profile.</b>";
    unset($_SESSION['player_id']);
    header('Location: index.php');
    return false;
  };

  // Allows user to log out
  if (isset($_POST['logout'])) {
    $_SESSION['message'] = "<b style='color:green'>Log out successful</b>";
    unset($_SESSION['player_id']);
    header('Location: index.php');
    return true;
  };

  // Start a new Group
  if (isset($_POST['new_group'])) {
    if (strlen($_POST['group_name']) > 0) {
      $groupStmt = $pdo->prepare('INSERT INTO Groups(admin_id,group_name) VALUES (:pid,:gnm)');
      $groupStmt->execute(array(
        ':pid'=>$_SESSION['player_id'],
        ':gnm'=>htmlentities($_POST['group_name'])
      ));
      $_SESSION['message'] = "<b style='color:green'>New group created!</b>";
      header('Location: player.php');
      return true;
    } else {
      $_SESSION['message'] = "<b style='color:red'>Group name is required</b>";
      header('Location: player.php');
      return true;
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
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title><?php echo($playerData['userName']) ?> | Bracket Referee</title>
    <script
    src="https://code.jquery.com/jquery-3.3.1.min.js"
    integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="
    crossorigin="anonymous"></script>
    <script src="main.js"></script>
  </head>
  <body>
    <h1>Bracket HQ</h1>
    <?php
    if (isset($_SESSION['message'])) {
      echo($_SESSION['message']);
      unset($_SESSION['message']);
    };
    ?>
    <table>
      <tr>
        <th>Name</th>
        <td><?php echo($playerData['firstName']) ?> <?php echo($playerData['lastName']) ?></td>
      </tr>
      <tr>
        <th>Username</th>
        <td><?php echo($playerData['userName']) ?></td>
      </tr>
      <tr>
        <th>Email</th>
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
          $groupList = $pdo->prepare('SELECT Groups.group_name FROM Groups JOIN Groups_Players ON Groups.group_id=Groups_Players.group_id AND Groups_Players.player_id=:id');
          $groupList->execute(array(
            ':id'=>$_SESSION['player_id']
          ));
          while ($row = $groupList->fetch(PDO::FETCH_ASSOC)) {
            echo("<tr><td>".$row['group_name']."</td></tr>");
          };
        ?>
      </table>
    </div>
    <div id="groupBox">
      <h3 id="showGroupBox">Referee A New Group?</h3>
      <form method="POST">
        <table>
          <tr>
            <td>Group name:</td>
            <td><input type='text' name='group_name'></td>
          </tr>
        </table>
        <input type="submit" name="new_group" value="START">
      </form>
      <button id="cancelGroup">CANCEL</button>
    </div>
    <h3 id="showDeleteBox">Delete your account?</h3>
    <div id="deleteBox">
      <b>Are you sure that you want to delete your account?
      <form method="POST">
        <input type="submit" name="deleteAcct" value="YES, delete my account"/>
      </form>
      <button id="cancelDelete">NO, keep my account</button>
    </div>
  </body>
</html>

<?php
  session_start();
  require_once("pdo.php");

  // Confirms that the user is logged in
  if (!isset($_SESSION['player_id'])) {
    $_SESSION['message'] = "<b style='color:red'>You must log in or create an account to edit a group.</b>";
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

  // Makes sure the administrator for this group is the current user
  $checkStmt = $pdo->prepare('SELECT admin_id,group_name FROM Groups WHERE group_id=:gid');
  $checkStmt->execute(array(
    ':gid'=>$_GET['group_id']
  ));
  $adminId = $checkStmt->fetch(PDO::FETCH_ASSOC);
  if ($adminId['admin_id'] != $_SESSION['player_id']) {
    $urlId = $_GET['group_id'];
    $_SESSION['message'] = "<b style='color:red'>Only the administrator can edit this group.</b>";
    header('Location: group.php?group_id='.$urlId);
    return false;
  };

  // The current group_id
  $urlId = htmlentities($_GET['group_id']);

  if (isset($_POST['submitEdit'])) {
    // $urlId = htmlentities($_GET['group_id']);
    if ($adminId['group_name'] != $_POST['new_name']) {
      if (strlen($_POST['new_name'])) {
        $editStmt = $pdo->prepare('UPDATE Groups SET group_name=:nw WHERE group_id=:id');
        $editStmt->execute(array(
          ':id'=>$urlId,
          ':nw'=>htmlentities($_POST['new_name'])
        ));
        $_SESSION['message'] = "<b style='color:green'>Change completed</b>";
        header('Location: group.php?group_id='.$urlId);
        return true;
      };
    } else {
      header('Location: group_edit.php?group_id='.$urlId);
    }
  };

  // Delete this group and it's linking table in the Groups_Players.php file
  if (isset($_POST['submitDelete'])) {
    $deleteStmt = $pdo->prepare('DELETE FROM Groups WHERE group_id=:id');
    $deleteStmt->execute(array(
      ':id'=>$urlId
    ));
    $_SESSION['message'] = "<b style='color:blue'>Group deleted</b>";
    header('Location: player.php');
    return true;
  };

  // Allows user to return to group.php
  if (isset($_POST['cancelEdit'])) {
    $urlId = $_GET['group_id'];
    header('Location: group.php?group_id='.$urlId);
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
    <title>Change Group | Bracket Referee</title>
    <link rel="stylesheet" type="text/css" href="style/output.css"/>
    <script
    src="https://code.jquery.com/jquery-3.3.1.min.js"
    integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="
    crossorigin="anonymous"></script>
    <script src="main.js"></script>
  </head>
  <body>
    <div id="groupEditPage">
      <div id="mainEditTitle">Want to change this group?</div>
      <p>As the director, you can change this group by simply inserting a new values in the below boxes. Enter your changes by clicking on 'ENTER', or ignore your changes and return to the previous page by clicking 'CANCEL'.</p>
      <form method="POST">
        <div id="changeBox">
          <table>
            <tr>
              <th>Group Name</th>
              <td>
                <input type="text" name="new_name" value="<?php echo($adminId['group_name']) ?>"/>
              </td>
            </tr>
          </table>
          <div>
            <input id="submitEnter" type="submit" name="submitEdit" value="ENTER" />
            <input type="submit" name="cancelEdit" value="CANCEL" />
          </div>
        </div>
        <div id="delGrpButton">Delete this Group?</div>
        <div id="delGrpBox">
          <div id="warningGrp">WARNING</div>
          <p>
            Are you sure you want to delete this group? All of the results and brackets will be permanently deleted!
          </p>
          <div id="warningRow">
            <input type="submit" name="submitDelete" value="DELETE" />
            <span id="cancelDelGrp">CANCEL</span>
          </div>
        </div>
      </form>
      <?php
        if (isset($_SESSION['message'])) {
          echo($_SESSION['message']);
          unset($_SESSION['message']);
        };
      ?>
    </div>
  </body>
</html>

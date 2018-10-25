<?php
  session_start();
  require_once("pdo.php");

  // Confirms that the user is logged in
  if (!isset($_SESSION['player_id'])) {
    $_SESSION['message'] = "<b style='color:red'>You must log in or create an account to edit a group.</b>";
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
    <script
    src="https://code.jquery.com/jquery-3.3.1.min.js"
    integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="
    crossorigin="anonymous"></script>
    <script src="main.js"></script>
  </head>
  <body>
    <h1>Change Your Group</h1>
    <p>To change any of your group's values, simply put in the new values into the below boxes and click 'ENTER'</p>
    <form method="POST">
      <table style='border:solid black 1px'>
        <tr>
          <td><b>Group Name</b><td>
          <td><input type="text" name="new_name" value="<?php echo($adminId['group_name']) ?>"/></td>
        </tr>
      </table>
      <span>
        <input type="submit" name="submitEdit" value="ENTER" />
        <input type="submit" name="cancelEdit" value="CANCEL" />
      </span>
      <h3 id="delGrpButton">Delete this Group?</h3>
      <div id="delGrpBox" style="border: 1px solid red">
        <b style='color:red'>WARNING</b></br>
        <p>
          Are you sure you want to delete this group? All of the results and brackets will be permanently deleted!
        </p>
        <input type="submit" name="submitDelete" value="DELETE" />
        <button id="cancelDelGrp">CANCEL</button>
      </div>
    </form>
    <?php
      if (isset($_SESSION['message'])) {
        echo($_SESSION['message']);
        unset($_SESSION['message']);
      };
    ?>
  </body>
</html>

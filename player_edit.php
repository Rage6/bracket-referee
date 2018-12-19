<?php
  session_start();
  require_once("pdo.php");

  // Prevents entering this page w/o logging in
  if (!isset($_SESSION['player_id'])) {
    $_SESSION['message'] = "<b style='color:red'>You must log in or create an account to view your profile.</b>";
    header('Location: index.php');
  };

  // Uses the $_SESSION['player_id'] from logging in to find the rest of the player's data
  $recall = $pdo->prepare('SELECT email,userName,firstName,lastName,token FROM Players WHERE (player_id=:id)');
  $recall->execute(array(
    ':id'=>$_SESSION['player_id']
  ));
  $playerData = $recall->fetch(PDO::FETCH_ASSOC);

  // Prevents different users from entering this page with a different player_id
  if ($_SESSION['token'] != $playerData['token']) {
    $_SESSION['message'] = "<b style='color:red'>Your current token does not coincide with your account's token. Reassign a new token by logging back in.</b>";
    unset($_SESSION['player_id']);
    unset($_SESSION['token']);
    header('Location: index.php');
    return false;
  };

  // Returns the user to the player.php file
  if (isset($_POST['cancel'])) {
    $_SESSION['message'] = "<b style='color:blue'>Changes canceled</b>";
    header('Location: player.php');
    return true;
  };

  // Submits the edited values
  if (isset($_POST['submit'])) {
    if (strlen($_POST['editFirst']) > 0 && strlen($_POST['editLast']) > 0 && strlen($_POST['editUser']) > 0 && strlen($_POST['editEmail']) > 0) {
      if (filter_var($_POST['editEmail'],FILTER_VALIDATE_EMAIL)) {
        $updateStmt = $pdo->prepare('UPDATE Players SET email=:em, userName=:un, firstName=:fn, lastName=:ls WHERE player_id=:id');
        $updateStmt->execute(array(
          ':em'=>htmlentities($_POST['editEmail']),
          ':un'=>htmlentities($_POST['editUser']),
          ':fn'=>htmlentities($_POST['editFirst']),
          ':ls'=>htmlentities($_POST['editLast']),
          ':id'=>$_SESSION['player_id']
        ));
        $_SESSION['message'] = "<b style='color:green'>Update successful</b>";
        header('Location: player.php');
        return true;
      } else {
        $_SESSION['message'] = "<b style='color:red'>Invalid email address</b>";
        header('Location: player_edit.php');
        return false;
      }
    } else {
      $_SESSION['message'] = "<b style='color:red'>All boxes must be filled</b>";
      header('Location: player_edit.php');
      return false;
    }
  }

  // Submit new password
  if (isset($_POST['makePassword'])) {
    $findPw = $pdo->prepare('SELECT pswd FROM Players WHERE player_id=:id');
    $findPw->execute(array(
      ':id'=>$_SESSION['player_id']
    ));
    $currentPw = $findPw->fetch(PDO::FETCH_ASSOC);
    if (strlen($_POST['oldPw']) > 0 && strlen($_POST['newPw']) > 0 && strlen($_POST['confPw']) > 0) {
      if (password_verify($_POST['oldPw'],$currentPw['pswd'])) {
        $newPw = password_hash($_POST['newPw'],PASSWORD_DEFAULT);
        if ($_POST['oldPw'] != $_POST['newPw']) {
          if ($_POST['newPw'] === $_POST['confPw']) {
            $newPsStmt = $pdo->prepare('UPDATE Players SET pswd=:pw WHERE player_id=:id');
            $newPsStmt->execute(array(
              ':pw'=>$newPw,
              ':id'=>$_SESSION['player_id']
            ));
            $_SESSION['message'] = "<b style='color:blue'>Password change successful</b>";
            header('Location: player.php');
            return true;
          } else {
            $_SESSION['message'] = "<b style='color:red'>New passwords were not equal</b>";
            header('Location: player_edit.php');
            return false;
          };
        } else {
          $_SESSION['message'] = "<b style='color:red'>New password must be differnt than current password</b>";
          header('Location: player_edit.php');
          return false;
        };
      } else {
        $_SESSION['message'] = "<b style='color:red'>Current password' was incorrect</b>";
        header('Location: player_edit.php');
        return false;
      };
    } else {
      $_SESSION['message'] = "<b style='color:red'>Both old and new password must be filled</b>";
      header('Location: player_edit.php');
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
    <div id="plyrEditPage">
      <div id="editTitle">Edit Your Profile</div>
      <?php
      if (isset($_SESSION['message']) && !isset($_POST['cancel'])) {
        echo($_SESSION['message']);
        unset($_SESSION['message']);
      };
      ?>
      <form method="POST">
        <table>
          <tr>
            <th>First Name</th>
            <td>
              <input type="text" name="editFirst" value="<?php echo($playerData['firstName']) ?>" />
            </td>
          </tr>
          <tr>
            <th>Last Name</th>
            <td>
              <input type="text" name="editLast" value="<?php echo($playerData['lastName']) ?>" />
            </td>
          </tr>
          <tr>
            <th>Username</th>
            <td>
              <input type="text" name="editUser" value="<?php echo($playerData['userName']) ?>" />
            </td>
          </tr>
          <tr>
            <th>Email</th>
            <td>
              <input type="text" name="editEmail" value="<?php echo($playerData['email']) ?>" />
            </td>
          </tr>
        </table>
        <div id="plyrEditBttns">
          <input class="onePlyrEditBttn" type='submit' name='submit' value='SUBMIT'/>
          <input class="onePlyrEditBttn" type='submit' name='cancel' value='CANCEL'/>
        </div>
      </form>
      <div id="delAndPsBox">
        <div id="change-Pw-Btn" class="pwAndDeleteBttns">Change Password</div>
        <div id="showDeleteBox" class="pwAndDeleteBttns">Delete your account?</div>
      </div>
      <div id="changePs">
        <form method="POST">
          <table>
            <tr>
              <td>Current Password</td>
              <td><input type='text' name='oldPw'></td>
            </tr>
            <tr>
              <td>New Password</td>
              <td><input type='text' name='newPw'></td>
            </tr>
            <tr>
              <td>Confirm New Password</td>
              <td><input type='text' name='confPw'></td>
            </tr>
          </table>
          <div>
            <input type="submit" name="makePassword" value="CHANGE">
            <span id="change-Pw-cancel"><u>CANCEL</u></span>
          </div>
        </form>
      </div>
      <div id="deleteBox">
        <div id="warning">
          Are you sure that you want to delete your account? All of your information will be lost.
        </div>
        <div>
          <form method="POST">
            <input type="submit" name="deleteAcct" value="YES, delete my account"/>
          </form>
          <span id="cancelDelete">NO, keep my account</span>
        </div>
      </div>
    </div>
  </body>
</html>

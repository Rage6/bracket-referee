<?php
  session_start();
  require_once("pdo.php");

  // For logging into an existing account
  if (isset($_POST['confirmOld'])) {
    if (strlen($_POST['userEmail']) > 0 && strlen($_POST['password']) > 0) {
      $stmt = $pdo->prepare("SELECT player_id,userName,firstName,lastName,email,pswd FROM Players WHERE (userName=:ue) OR (email=:ue)");
      $stmt->execute(array(
        ':ue'=>htmlentities($_POST['userEmail']),
        ':em'=>htmlentities($_POST['email'])
      ));
      $list = $stmt->fetch(PDO::FETCH_ASSOC);
      if (count($list['player_id']) < 1) {
        $_SESSION['message'] = "<b style='color:red'>Your email or password was invalid</b>";
        header('Location: index.php');
        return false;
      } elseif (count($list['player_id']) > 1) {
        $_SESSION['message'] = "<b style='color:red'>An error has occured. Notify the administrator at nicholas.vogt2017@gmail.com with any details</b>";
        header('Location: index.php');
        return false;
      } else {
        if (password_verify($_POST['password'],$list['pswd'])) {
          $_SESSION['message'] = "<b style='color:green'>Welcome, ".$list['userName']."!</b>";
          $_SESSION['player_id'] = $list['player_id'];
          header('Location: player.php');
          return true;
        } else {
          $_SESSION['message'] = "<b style='color:red'>Your email or password was invalid</b>";
          header('Location: index.php');
          return false;
        };
      };
    } else {
      $_SESSION['message'] = "<b style='color:red;'>All values must be entered</b>";
      header('Location: index.php');
      return false;
    };
  };

  // For creating a new account
  if (isset($_POST['makeNew'])) {
    if (strlen($_POST['newFirst']) > 0 && strlen($_POST['newLast']) > 0 && strlen($_POST['newEmail']) > 0 && strlen($_POST['newUser']) > 0 && strlen($_POST['newPass']) > 0) {
      if (filter_var($_POST['newEmail'],FILTER_VALIDATE_EMAIL)) {
        if ($_POST['newPass'] === $_POST['newConf']) {
          if (strlen($_POST['newPass']) >= 8 && strlen($_POST['newPass']) <= 25) {
            $hash = password_hash($_POST['newPass'],PASSWORD_DEFAULT);
            $addStmt = $pdo->prepare('INSERT INTO Players(email,userName,firstName,lastName,pswd) VALUES (:em,:un,:ft,:lt,:ps)');
            $addStmt->execute(array(
              ':em'=>htmlentities($_POST['newEmail']),
              ':un'=>htmlentities($_POST['newUser']),
              ':ft'=>htmlentities($_POST['newFirst']),
              ':lt'=>htmlentities($_POST['newLast']),
              ':ps'=>htmlentities($hash)
            ));
            $findID = $pdo->prepare('SELECT player_id FROM Players WHERE pswd=:ps');
            $findID->execute(array(
              ':ps'=>htmlentities($hash)
            ));
            $newID = $findID->fetch(PDO::FETCH_ASSOC);
            $_SESSION['player_id'] = $newID['player_id'];
            $_SESSION['message'] = "<b style='color:green'>Welcome, ".$_POST['newUser']."!</b>";
            header('Location: player.php');
            return true;
          } else {
            $_SESSION['message'] = "<b style='color:red'>Password must be greater than 7 and less than 26 characters</b>";
            header('Location: index.php');
            return false;
          };
        } else {
          $_SESSION['message'] = "<b style='color:red'>The password and confirming password must be identical</b>";
          header('Location: index.php');
          return false;
        };
      } else {
        $_SESSION['message'] = "<b style='color:red'>Invalid email address</b>";
        header('Location: index.php');
        return false;
      };
    } else {
      $_SESSION['message'] = "<b style='color:red'>All values must be entered</b>";
      header('Location: index.php');
      return false;
    }
  };
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title>
      Welcome | The Bracket Referee
    </title>
    <script
    src="https://code.jquery.com/jquery-3.3.1.min.js"
    integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="
    crossorigin="anonymous"></script>
    <script src="main.js"></script>
  </head>
  <body>
    <h1>
      The Bracket Referee
    </h1>
    <h2>
      Welcome! The Bracket Referee is a free center where you and your friend's can make private brackets and compete against one another!
    </h2>
    <p>If you want to step on the court, simply...</p>
    <div id="logBox">
      <div id="logButton"><b>ACCOUNT LOGIN</b></div>
      <div id="logForm">
        <form method="POST">
          <table>
            <tr>
              <td>Email or Username </td>
              <td><input type="text" name="userEmail"></td>
            </tr>
            <tr>
              <td>Password </td>
              <td><input type="text" name="password"></td>
            </tr>
          </table>
          <input type="submit" name="confirmOld" value="ENTER">
        </form>
      </div>
    </div>
    </br>
    <div id="signBox">
      <div id="signButton"><b>CREATE ACCOUNT</b></div>
      <div id="signForm">
        <form method='POST'>
          <table>
            <tr>
              <td>Username</td>
              <td><input type='text' name='newUser'/></td>
            </tr>
            <tr>
              <td>First Name</td>
              <td><input text='text' name='newFirst'/></td>
            </tr>
            <tr>
              <td>Last Name</td>
              <td><input type='text' name='newLast'/></td>
            </tr>
            <tr>
              <td>Email</td>
              <td><input type='text' name='newEmail'/></td>
            </tr>
            <tr>
              <td>Password</td>
              <td><input type='text' name='newPass' placeholder='8 - 25 characters'/s></td>
            </tr>
            <tr>
              <td>Confirm Password</td>
              <td><input type='text' name='newConf' placeholder='8 - 25 characters'/></td>
            </tr>
          </table>
          <input type='submit' name='makeNew' value='ENTER'/>
        </form>
      </div>
    </div>
    <?php
      if (isset($_SESSION['message'])) {
        echo($_SESSION['message']);
        unset($_SESSION['message']);
      };
    ?>
  </body>
</html>
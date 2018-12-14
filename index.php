<?php
  session_start();
  require_once("pdo.php");

  // For logging into an existing account
  if (isset($_POST['confirmOld'])) {
    if (strlen($_POST['userEmail']) > 0 && strlen($_POST['password']) > 0) {
      $stmt = $pdo->prepare("SELECT player_id,userName,firstName,lastName,email,pswd,token FROM Players WHERE (userName=:ue) OR (email=:ue)");
      $stmt->execute(array(
        ':ue'=>htmlentities($_POST['userEmail'])
        // ':em'=>htmlentities($_POST['email'])
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
          $_SESSION['message'] = "<b style='color:green'>Welcome, ".$list['userName']."!</b> ";
          $token = bin2hex(random_bytes(21));
          $new_token = $pdo->prepare('UPDATE Players SET token=:tk WHERE player_id=:pid');
          $new_token->execute(array(
            ':tk'=>$token,
            ':pid'=>$list['player_id']
          ));
          $_SESSION['token'] = $token;
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
            // This compares the email, username, and password to the ones that already exist
            $compareExst = $pdo->prepare('SELECT player_id FROM Players WHERE email=:em OR userName =:un OR pswd=:pw');
            $compareExst->execute(array(
              ':em'=>htmlentities($_POST['newEmail']),
              ':un'=>htmlentities($_POST['newUser']),
              ':pw'=>htmlentities($hash)
            ));
            $exstList = $compareExst->fetch(PDO::FETCH_ASSOC);
            if (count($exstList['player_id']) == 0) {
              $token = bin2hex(random_bytes(21));
              $addStmt = $pdo->prepare('INSERT INTO Players(email,userName,firstName,lastName,pswd,token) VALUES (:em,:un,:ft,:lt,:ps,:tk)');
              $addStmt->execute(array(
                ':em'=>htmlentities($_POST['newEmail']),
                ':un'=>htmlentities($_POST['newUser']),
                ':ft'=>htmlentities($_POST['newFirst']),
                ':lt'=>htmlentities($_POST['newLast']),
                ':ps'=>htmlentities($hash),
                ':tk'=>$token
              ));
              $findID = $pdo->prepare('SELECT player_id FROM Players WHERE pswd=:ps');
              $findID->execute(array(
                ':ps'=>htmlentities($hash)
              ));
              $newID = $findID->fetch(PDO::FETCH_ASSOC);
              $_SESSION['player_id'] = $newID['player_id'];
              $_SESSION['token'] = $token;
              $_SESSION['message'] = "<b style='color:green'>Welcome, ".$_POST['newUser']."!</b>";
              header('Location: player.php');
              return true;
            } else {
              $_SESSION['message'] = "<b style='color:red'>Email address, username, and/or password already in use. Please try a different value</b>";
              header('Location: index.php');
              return false;
            };
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
    <title>
      Welcome | Bracket Referee
    </title>
    <link rel="stylesheet" type="text/css" href="style/output.css"/>
    <script
    src="https://code.jquery.com/jquery-3.3.1.min.js"
    integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="
    crossorigin="anonymous"></script>
    <script src="main.js"></script>
  </head>
  <body>
    <div id="indexTitle">
      <p>
        Bracket
      </p>
      <p>
        Referee
      </p>
    </div>
    <div id="bothAcctButtons">
      <div id="logButton">
        <b>LOGIN</b>
      </div>
      <div id="signButton">
        <b>CREATE</b>
      </div>
    </div>
    <?php
      if (isset($_SESSION['message'])) {
        echo("<div style='margin-bottom:20px;text-align:center'>".$_SESSION['message']."</div>");
        unset($_SESSION['message']);
      };
    ?>
    <div class="acctForms" id="logForm">
      <form method="POST">
        <table>
          <tr>
            <td>Email or Username </td>
            <td><input type="text" name="userEmail"></td>
          </tr>
          <tr>
            <td>Password </td>
            <td><input type="password" name="password"></td>
          </tr>
        </table>
        <input type="submit" name="confirmOld" value="ENTER">
      </form>
    </div>
    <div class="acctForms" id="signForm">
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
            <td><input type='password' name='newPass' placeholder='8 - 25 characters'/s></td>
          </tr>
          <tr>
            <td>Confirm Password</td>
            <td><input type='password' name='newConf' placeholder='8 - 25 characters'/></td>
          </tr>
        </table>
        <input type='submit' name='makeNew' value='ENTER'/>
      </form>
    </div>
    <div id="indexMain">
      <div id="motto">
        <p>Public Tournaments.</p>
        <p>Personal Groups.</p>
        <p>Precious Glory.</p>
      </div>
      <div id="indexIntro">
        <b>Welcom to Bracket Referee!</b>
        <p>
          Here is where friends, family, and fellow competitors can put their brackets to the test. The process is simple, completely free, and open to anyone.
        </p>
        <b><i>What is Bracket Referee for?</i></b>
        <p>
          sdsdfsdfsdf
        </p>
        <b><i>How does it work?</i></b>
        <p>
          First, press the "LOGIN" button and enter your email address/username and password. If you haven't already created an account, do so by clicking on "CREATE" above and fill out the basic information. Doing either will allow you into your profile, <u>Bracket HQ</u>.
        </p>
        <p>
          This page is the center of all of your information and possibilities. From here, you can:
        </p>
        <ul>
          <li>See the groups that you compete within</li>
          <li>Search for a specific group</li>
          <li>Create a new group</li>
          <li>Edit or delete your account</li>
        </ul>
        <p>
          Upon entering a new group, you can officially join it by the single click. A joined member can then enter their own bracket and see their fellow member's usernames, scores, and brackets.
        </p>
        <b><i>Who Made This?</i></b>
        <p style="margin-bottom:0px;padding-bottom:100px">
          The creator and director of "Bracket Referee" is myself, Nicholas Vogt, as a project to exercise and expand my skills as a website developer. To see my porfolio, my professional history, or the actual code for this website, please click below.
        </p>
      </div>
    </div>
    <footer>
      Portfolio</br>
      LinkedIn</br>
      GitHub</br>
    </footer>
  </body>
</html>

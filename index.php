<?php
  session_start();
  require_once("pdo.php");

  // Activates SendGrid stuff when NOT on the local host
  $currentHost = $_SERVER['HTTP_HOST'];
  if ($currentHost != 'localhost:8888') {
    require 'vendor/autoload.php';
  };

  // Redirects someone to their player.php if they are still logged in
  if (isset($_SESSION['player_id']) && isset($_SESSION['token'])) {
    $checkTokenStmt = $pdo->prepare('SELECT token,userName FROM Players WHERE player_id=:ply');
    $checkTokenStmt->execute(array(
      'ply'=>htmlentities($_SESSION['player_id'])
    ));
    $checkPlayer = $checkTokenStmt->fetch(PDO::FETCH_ASSOC);
    if ($_SESSION['token'] == $checkPlayer['token']) {
      $_SESSION['message'] = "<b style='color:green'>Welcome back, ".$checkPlayer['userName']."!</b> ";
      header('Location: player.php');
      return true;
    } else {
      $_SESSION['message'] = "<b style='color:red'>Your account's token does not equal your current token. Log back in to refresh your token.</b>";
      unset($_SESSION['player_id']);
      unset($_SESSION['token']);
      header('Location: index.php');
      return false;
    };
    $_SESSION['message'] = "<b style='color:green'>Welcome back, ".$checkPlayer['userName']."!</b> ";
    header('Location: player.php');
    return true;
  };

  // For logging into an existing account
  if (isset($_POST['confirmOld'])) {
    if (strlen($_POST['userEmail']) > 0 && strlen($_POST['password']) > 0) {
      $stmt = $pdo->prepare("SELECT player_id,userName,firstName,lastName,email,pswd,token,tries FROM Players WHERE (userName=:ue) OR (email=:ue)");
      $stmt->execute(array(
        ':ue'=>htmlentities($_POST['userEmail'])
      ));
      $list = $stmt->fetch(PDO::FETCH_ASSOC);
      if (count($list['player_id']) < 1) {
        $_SESSION['message'] = "<b style='color:red'>Your email or username was invalid</b>";
        header('Location: index.php');
        return false;
      } elseif (count($list['player_id']) > 1) {
        $_SESSION['message'] = "<b style='color:red'>An error has occured. Notify the administrator at nicholas.vogt2017@gmail.com with any details</b>";
        header('Location: index.php');
        return false;
      } else {
        if (password_verify($_POST['password'],$list['pswd'])) {
          if ($list['tries'] > 0) {
            $_SESSION['message'] = "<b style='color:green'>Welcome, ".$list['userName']."!</b> ";
            $token = bin2hex(random_bytes(21));
            $new_token = $pdo->prepare('UPDATE Players SET token=:tk, tries=5 WHERE player_id=:pid');
            $new_token->execute(array(
              ':tk'=>$token,
              ':pid'=>$list['player_id']
            ));
            $_SESSION['token'] = $token;
            $_SESSION['player_id'] = $list['player_id'];
            header('Location: player.php');
            return true;
          } else {
            $_SESSION['message'] = "<b style='color:red'>Your account was locked due to 5 failed login attempts. Click on the 'LOGIN' button and the 'Forgot Your Password?' option in order to make a new password and unlock your account</b> ";
            header('Location: index.php');
            return false;
          };
        } else {
          if ($list['tries'] > 0) {
            $oneLess = $list['tries'] - 1;
            $triesStmt = $pdo->prepare("UPDATE Players SET tries=tries-1 WHERE player_id=:lp");
            $triesStmt->execute(array(
              ':lp'=> $list['player_id']
            ));
            $_SESSION['message'] = "<b style='color:red'>Your password was invalid. You have ".$oneLess." attempt(s) left.</b>";
            header('Location: index.php');
            return false;
          } else {
            $_SESSION['message'] = "<b style='color:red'>Your account was locked due to 5 failed login attempts. Click on the 'LOGIN' button and the 'Forgot Your Password?' option in order to make a new password and unlock your account</b> ";
            header('Location: index.php');
            return false;
          };
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
            $countSpace = substr_count($_POST['newUser']," ") + substr_count($_POST['newPass']," ");
            if ($countSpace == 0) {
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
              $_SESSION['message'] = "<b style='color:red'>Usernames and passwords cannot contain 'spaces'</b>";
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

  // So users can reset a forgotten password
  if (isset($_POST['reset'])) {
    if (strlen($_POST['resetEmail']) > 0) {
      if (filter_var($_POST['resetEmail'],FILTER_VALIDATE_EMAIL)) {
        $countEmailStmt = $pdo->prepare('SELECT COUNT(email) FROM Players WHERE email=:rem');
        $countEmailStmt->execute(array(
          ':rem'=>htmlentities($_POST['resetEmail'])
        ));
        $countEmail = (int)$countEmailStmt->fetch(PDO::FETCH_ASSOC)['COUNT(email)'];
        if ($countEmail == 1) {
            // A new password and hash are made...
            $newPassword = bin2hex(random_bytes(5));
            $newHash = password_hash($newPassword,PASSWORD_DEFAULT);
            // ... but keeps the old password's hash (in case the email doesn't go through)...
            $oldDataStmt = $pdo->prepare('SELECT email,firstName,lastName,pswd FROM Players WHERE email=:gem');
            $oldDataStmt->execute(array(
              ':gem'=>htmlentities($_POST['resetEmail'])
            ));
            $oldData = $oldDataStmt->fetch(PDO::FETCH_ASSOC);
            $oldHash = $oldData['pswd'];
            $firstName = $oldData['firstName'];
            $lastName = $oldData['lastName'];
            // .. so that the old hash can be changed to the new hash...
            $changePasswordStmt = $pdo->prepare('UPDATE Players SET pswd=:npw WHERE email=:fem');
            $changePasswordStmt->execute(array(
              ':npw'=>$newHash,
              ':fem'=>htmlentities($_POST['resetEmail'])
            ));
            // .. and an email with the new password is written.
            // If the account was locked due to 5 login failures, this will unlock it.
            $unlockAcct = $pdo->prepare("UPDATE Players SET tries=5 WHERE email=:pe");
            $unlockAcct->execute(array(
              ':pe'=>htmlentities($_POST['resetEmail'])
            ));
            // If the host is NOT a local host, it will email the new password.
            if ($currentHost != 'localhost:8888') {
              putenv("SENDGRID_API_KEY=".$apiKey);
              $email = new \SendGrid\Mail\Mail();
              $email->setFrom("nicholas.vogt2017@gmail.com", "Nicholas Vogt");
              $email->setSubject("Password Reset | Bracket Referee");
              $email->addTo(htmlentities($_POST['resetEmail']), $firstName." ".$lastName);
              $email->addContent(
                  "text/html", "
                    <h1 style='text-align:center'><u>BRACKET REFEREE</u></h1>
                    <h2 style='text-align:center'>Your password has been reset</h2>
                    <p>".$firstName." ".$lastName.",</p>
                    <p>Your password for <a href='https://bracket-referee.herokuapp.com/index.php'>'Bracket Referee'</a> was recently reset. Your new password is:</p>
                    <b style='color:green;text-align:center'>".$newPassword."</b>"
              );
              $sendgrid = new \SendGrid(getenv('SENDGRID_API_KEY'));
              // If the email is successful...
              try {
                  $response = $sendgrid->send($email);
                  $_SESSION['message'] = "<b style='color:green'>RESET SUCCESSFUL</br>Your new password was sent to your email account.</b>";
                  header('Location: index.php');
                  return true;
              // ... and this happens if the email fails (and the old hash is returned to the account)...
              } catch (Exception $e) {
                  $returnPasswordStmt = $pdo->prepare('UPDATE Players SET pswd=:opw WHERE email=:oem');
                  $changePasswordStmt->execute(array(
                    ':opw'=>$oldHash,
                    ':fem'=>htmlentities($_POST['resetEmail'])
                  ));
                  // echo 'Caught exception: '. $e->getMessage() ."\n";
                  $_SESSION['message'] = "<b style='color:red'>Sorry, there has been an error that prevented us from sending you a new password. Email me at nicholas.vogt2017@gmail.com with a description of your issue.</b>";
                  header('Location: index.php');
                  return false;
              };
            // If the host is a local host, then it shows the new password on index.php
            } else {
              $_SESSION['message'] = "<b style='color:blue'>New Password: ".$newPassword."</b>";
              header('Location: index.php');
              return false;
            };
          header('Location: index.php');
          return true;
        } else {
          $_SESSION['message'] = "<b style='color:red'>No email account could be found with that email address</b>";
          header('Location: index.php');
          return false;
        };
      } else {
        $_SESSION['message'] = "<b style='color:red'>Only a valid email syntax is accepted (ex. myName@email.com)</b>";
        header('Location: index.php');
        return false;
      };
    } else {
      $_SESSION['message'] = "<b style='color:red'>An email must be entered</b>";
      header('Location: index.php');
      return false;
    };
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
    <meta property="og:title" content="Bracket Referee" />
    <meta property="og:image" content="style/img/ball_ref_meta.jpg" />
    <meta property="og:description" content="Bring your brackets to the arena and compete within all of the popular upcoming tournaments." />
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
    <div id="indexWords">
      <div>
        Bracket
      </div>
      <div>
        Referee
      </div>
    </div>
    <div id="bothAcctButtons">
      <div id="logButton">
        <span>LOGIN</span>
      </div>
      <div id="signButton">
        <span>CREATE</span>
      </div>
    </div>
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
        <input class="enterBttn" type="submit" name="confirmOld" value="ENTER">
      </form>
      <div id="forgotBttn">Forgot you password?</div>
      <div id="forgotBox">
        <div>
          Get a new password by entering your current email address below and clicking 'RESET'. You should recieve an email from Bracket Referee shortly after.
        </div>
        <form method="POST">
          <input type="text" name="resetEmail"/></br>
          <input type="submit" name="reset" value="RESET"/>
        </form>
      </div>
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
        <input class="enterBttn" type='submit' name='makeNew' value='ENTER'/>
      </form>
    </div>
    <?php
      if (isset($_SESSION['message'])) {
        echo("<div id='message'>".$_SESSION['message']."</div>");
        unset($_SESSION['message']);
      };
    ?>
    <div id="indexMain">
      <div id="motto">
        <p>Public Tournaments.</p>
        <p>Personal Groups.</p>
        <p>Precious Glory.</p>
      </div>
      <div id="indexIntro">
        <div class="introParagraph">
          <b>Welcome to Bracket Referee!</b>
          <p>
            Here is where friends, family, and fellow competitors can put their brackets to the test. The process is simple, completely free, and open to anyone.
          </p>
        </div>
        <div class="imageBorder" id="imgBorderOne"></div>
        <div class="introParagraph">
          <b>
            <i>What is Bracket Referee used for?</i>
          </b>
          <p>
            The high-stakes tournaments that happen throughout the year can get anyone excited, especially if they feel like a competetitor instead of just another spectator! <u>Bracket Referee</u> allows you to:
          </p>
          <div>
            <ul>
              <li>Get excited about any of the upcoming tournaments!</li>
              <li>Compete within as many groups as you want</li>
              <li>Make a group of your own! Groups can be made open to the public, or can enter by private invitation only</li>
              <li>Easily fill out your picks and submit a bracket with a few simple clicks</li>
              <li>Watch how your scores measure up within your group</li>
              <li><b style="color:red;font-size:2rem">NEW!</b> Leave messages and comments within your group pages!</li>
            </ul>
          </div>
          <p>
            <u>Bracket Referee</u> can be used for any <span style="color:green"><u>single-elimination</u></span> tournament, with or without <span style="color:green"><u>wildcard</u></span> and <span style="color:green"><u>third-place</u></span> games.
          </p>
        </div>
        <div class="imageBorder" id="imgBorderTwo"></div>
        <div class="introParagraph">
          <b>
            <i>How do I start?</i>
          </b>
          <p>
            First, click on the <span style="border-radius:15px;padding:0 10px;background-color:green;color:white">CREATE</span> button above and fill out the basic information. If you have already created an account, press the <span style="border-radius:15px;padding:0 10px;background-color:blue;color:white">LOGIN</span> button and enter your username (or email address) and password. This will then send you to your profile, <u>Bracket HQ</u>. From here, you can:
          </p>
          <div>
            <ul>
              <li>See the groups that you are a member of</li>
              <li>Search for a specific group</li>
              <li>Create a new group</li>
              <li>See a list of groups with open memberships</li>
              <li>Edit or delete your account</li>
            </ul>
          </div>
          <p>
            Upon joining a group, you can submit a bracket and (after the tournament begins) see your fellow members' usernames, scores, and brackets. You can also post messages or comments on your group's 'Message' board.
          </p>
        </div>
        <div class="imageBorder" id="imgBorderThree"></div>
        <div class="introParagraph">
          <b>
            <i>Who Made This?</i>
          </b>
          <p style="margin-bottom:0px;padding-bottom:100px">
            The creator and director of <u>Bracket Referee</u> is Nicholas Vogt, a web developer in Ohio. This was a project meant for exercising and expanding upon his skills within two coding languages: PHP and Javascript. To see his porfolio, his professional history, or the actual code for this website, click one of the buttons below.
          </p>
        </div>
      </div>
    </div>
    <footer>
      <a href="https://portfolio-vogt.herokuapp.com"><img class="footerLogo" src="style/img/index/nick_vogt.jpg"></a>
      <a href="https://www.linkedin.com/in/nicholasvogt2017/"><img class="footerLogo" src="style/img/index/linkedin.jpg"></a>
      <a href="https://github.com/Rage6/bracket-referee"><img style="background-color:white;" class="footerLogo" src="style/img/index/gitHubLogo.png"></a>
    </footer>
  </body>
</html>

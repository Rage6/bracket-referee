<?php
  session_start();
  require_once("pdo.php");

  // Prevents entering this page w/o logging in
  if (!isset($_SESSION['player_id'])) {
    $_SESSION['message'] = "<b style='color:red'>You must log in or create an account to view your profile.</b>";
    unset($_SESSION['player_id']);
    unset($_SESSION['email']);
    unset($_SESSION['firstName']);
    unset($_SESSION['lastName']);
    unset($_SESSION['userName']);
    header('Location: index.php');
  };

  // Allows user to log out
  if (isset($_POST['logout'])) {
    $_SESSION['message'] = "<b style='color:green'>Log out successful</b>";
    unset($_SESSION['player_id']);
    unset($_SESSION['email']);
    unset($_SESSION['firstName']);
    unset($_SESSION['lastName']);
    unset($_SESSION['userName']);
    header('Location: index.php');
  };
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title><?php echo($_SESSION['userName']) ?> | Bracket Referee</title>
  </head>
  <body>
    <h1>Bracket HQ</h1>
    <?php
    if (isset($_SESSION['message'])) {
      echo($_SESSION['message']);
      // unset($_SESSION['message']);
    };
    ?>
    <table>
      <tr>
        <th>Name</th>
        <td><?php echo($_SESSION['firstName']) ?> <?php echo($_SESSION['lastName']) ?></td>
      </tr>
      <tr>
        <th>Username</th>
        <td><?php echo($_SESSION['userName']) ?></td>
      </tr>
      <tr>
        <th>Email</th>
        <td><?php echo($_SESSION['email']) ?></td>
      </tr>
    </table>
    <form method='POST'>
      <input type='submit' name='logout' value='LOGOUT'/>
      <input type='submit' name='edit' value='EDIT'/>
      <input type='submit' name='delete' value='DELETE'/>
    </form>
  </body>
</html>

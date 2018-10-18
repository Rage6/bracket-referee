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
  };

  // Allows user to log out
  if (isset($_POST['logout'])) {
    $_SESSION['message'] = "<b style='color:green'>Log out successful</b>";
    unset($_SESSION['player_id']);
    header('Location: index.php');
  };

  // Redirects to player_edit.php
  if (isset($_POST['edit'])) {
    unset($_SESSION['message']);
    header('Location: player_edit.php');
  };
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title><?php echo($playerData['userName']) ?> | Bracket Referee</title>
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
      <input type='submit' name='delete' value='DELETE'/>
    </form>
  </body>
</html>

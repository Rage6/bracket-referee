<?php
  session_start();
  require_once("pdo.php");

  // Prevents entering this page w/o logging in
  if (!isset($_SESSION['player_id'])) {
    $_SESSION['message'] = "<b style='color:red'>You must log in or create an account to view your profile.</b>";
    header('Location: index.php');
  };

  // Uses the $_SESSION['player_id'] from logging in to find the rest of the player's data
  $recall = $pdo->prepare('SELECT email,userName,firstName,lastName FROM Players WHERE (player_id=:id)');
  $recall->execute(array(
    ':id'=>$_SESSION['player_id']
  ));
  $playerData = $recall->fetch(PDO::FETCH_ASSOC);

  // Returns the user to the view.php file
  if (isset($_POST['cancel'])) {
    $_SESSION['message'] = "<b style='color:blue'>Changes canceled</b>";
    header('Location: view.php');
  };

  // Submits the SDO_DAS_ChangeSummary
  if (isset($_POST['submit'])) {
    if (strlen($_POST['editFirst']) > 0 && strlen($_POST['editLast']) > 0 && strlen($_POST['editUser']) > 0 && strlen($_POST['editEmail']) > 0) {
      if (filter_var($_POST['editEmail'],FILTER_VALIDATE_EMAIL)) {
        $_SESSION['message'] = "<b style='color:green'>Update successful</b>";
        header('Location: player_edit.php');
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
      <input type='submit' name='submit' value='SUBMIT'/>
      <input type='submit' name='cancel' value='CANCEL'/>
    </form>
  </body>
</html>

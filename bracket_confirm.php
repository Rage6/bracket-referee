<?php

  session_start();
  require_once("pdo.php");

  // Prevents entering this page w/o logging in
  if (!isset($_SESSION['player_id'])) {
    $_SESSION['message'] = "<b style='color:red'>You must log in or create an account to join a group.</b>";
    header('Location: index.php');
    return false;
  };

  if ($_SESSION['player_id'] != $_GET['player_id']) {
    $_SESSION['message'] = "<b style='color:red'>The ID that you inserted is not the same as your profile's ID.</b>";
    unset($_SESSION['player_id']);
    header('Location: index.php');
    return false;
  };

  echo("This worked");

?>

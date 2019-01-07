<?php

  session_start();
  require_once("pdo.php");

  // Prevents entering this page w/o logging in
  if (!isset($_SESSION['player_id'])) {
    $_SESSION['message'] = "<b style='color:red'>You must log in or create an account to join a group.</b>";
    header('Location: index.php');
    return false;
  } else {
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
    } else {
      // Prevents user from manually inserting a bracket with any player_id except with their own. If they try, they are logged out of their current user and redirected to index.php
      if ($_SESSION['player_id'] != $_GET['player_id']) {
        $_SESSION['message'] = "<b style='color:red'>The ID that you inserted is not the same as your profile's ID.</b>";
        unset($_SESSION['player_id']);
        header('Location: index.php');
        return false;
      } else {
        // Prevents a player from submitting a bracket if they have already submitted one
        $bracketListStmt = $pdo->prepare('SELECT COUNT(bracket_id) FROM Brackets WHERE player_id=:plid AND group_id=:grid');
        $bracketListStmt->execute(array(
          ':plid'=>$_SESSION['player_id'],
          ':grid'=>htmlentities($_GET['group_id'])
        ));
        $bracketNum = $bracketListStmt->fetch(PDO::FETCH_ASSOC)['COUNT(bracket_id)'];
        if ((int)$bracketNum > 0) {
          $_SESSION['message'] = "<b style='color:red'>No more than one bracket per group</b>";
          header('Location: group.php?group_id='.$_GET['group_id']);
          return false;
        } else {
          // Now to validate the GET values...
          $getLen = $_GET['gameTotal'];
          // This for loop confirms that winners were picked for each of the games
          for ($i = 0; $i < $getLen; $i++) {
            $gameId = $_GET['gameId'.$i];
            $pickId = $_GET['pickId'.$i];
            if (!is_numeric($gameId) || !is_numeric($pickId)) {
              $_SESSION['message'] = '<b style="color:red">All games must have a selected winner before a bracket can be submitted. gameId: '.$gameId.', pickId: '.$pickId.'</b>';
              header('Location: bracket_make.php?group_id='.$_GET['group_id']);
              return false;
            };
          };
          // If it all passes, then a new bracket is made first...
          $bracketStmt = $pdo->prepare('INSERT INTO Brackets(total_score,player_id,group_id) VALUES(0,:pid,:gid)');
          $bracketStmt->execute(array(
            ':pid'=>$_SESSION['player_id'],
            ':gid'=>$_GET['group_id']
          ));
          $bracketId = $pdo->lastInsertId();
          for ($j = 0; $j < $getLen; $j++) {
            $oneGame = $_GET['gameId'.$j];
            $onePick = $_GET['pickId'.$j];
            $pickStmt = $pdo->prepare('INSERT INTO Picks(player_pick,bracket_id,game_id) VALUES(:ppk,:bid,:gid)');
            $pickStmt->execute(array(
              ':ppk'=>$onePick,
              ':bid'=>$bracketId,
              ':gid'=>$oneGame
            ));
          };
          $_SESSION['message'] = "<b style='color:green'>New bracket added</b>";
          header('Location: group.php?group_id='.$_GET['group_id']);
        };
      };
    };
  };

?>

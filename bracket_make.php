<?php
  session_start();
  require_once("pdo.php");
  // require_once("json_tournament.php?group_id=1");

  // Prevents entering this page w/o logging in
  if (!isset($_SESSION['player_id'])) {
    $_SESSION['message'] = "<b style='color:red'>You must log in or create an account to join a group.</b>";
    header('Location: index.php');
    return false;
  };

  // Sends the user back to the Player file
  if (isset($_POST['returnPlayer'])) {
    header('Location: player.php');
    return true;
  };

  // Discontinue this bracket and return to group.php
  if (isset($_POST['cancelBracket'])) {
    header('Location: group.php?group_id='.$_GET['group_id']);
    return true;
  };

  // Gets the tournament for this group
  $grpStmt = $pdo->prepare('SELECT fk_tourn_id FROM Groups WHERE group_id=:gid');
  $grpStmt->execute(array(
    ':gid'=>htmlentities($_GET['group_id'])
  ));
  $grpArray = $grpStmt->fetch(PDO::FETCH_ASSOC);
  $tournId = $grpArray['fk_tourn_id'];

  // Gets the number of levels for this tournament
  $tournStmt = $pdo->prepare('SELECT level_total FROM Tournaments WHERE tourn_id=:tid');
  $tournStmt->execute(array(
    ':tid'=>$tournId
  ));
  $tournArray = $tournStmt->fetch(PDO::FETCH_ASSOC);
  $tournLevel = $tournArray['level_total'];

  // Checks and submits the new bracket
  if (isset($_POST['enterBracket'])) {
    $_SESSION['message'] = "<b style='color:green'>Bracket entered</b>";
    header('Location: group.php?group_id='.$_GET['group_id']);
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
    <title>Make A Bracket | Bracket Referee</title>
    <script
    src="https://code.jquery.com/jquery-3.3.1.min.js"
    integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="
    crossorigin="anonymous"></script>
    <script src="main.js"></script>
  </head>
  <body>
    <h1>Make Your Bracket</h1>
    <?php
      $levelStmt = $pdo->prepare('SELECT level_id,layer,level_name FROM Levels WHERE tourn_id=:tid');
      $levelStmt->execute(array(
        ':tid'=>$tournId
      ));
      while ($oneLevel = $levelStmt->fetch(PDO::FETCH_ASSOC)) {
        echo("<div id='layer_".$oneLevel['layer']."'>
                <h3 id='layerTitle_".$oneLevel['layer']."'>
                  <u>".$oneLevel['level_name']."</u>
                </h3>");
                // $currentLevel = $oneLevel['level_id'];
                // $gameStmt = $pdo->prepare('SELECT game_id,team_a,team_b,winner_id FROM Games WHERE level_id=:lid AND first_round=1');
                // $gameStmt->execute(array(
                //   ':lid'=>$currentLevel
                // ));
                // while ($oneGame = $gameStmt->fetch(PDO::FETCH_ASSOC)) {
                //   // var_dump($oneGame);)
                //   $aTeamStmt = $pdo->prepare('SELECT team_name FROM Teams WHERE team_id=:tid');
                //   $aTeamStmt->execute(array(
                //     ':tid'=>$oneGame['team_a']
                //   ));
                //   $aTeamName = $aTeamStmt->fetch(PDO::FETCH_ASSOC)['team_name'];
                //   $bTeamStmt = $pdo->prepare('SELECT team_name FROM Teams WHERE team_id=:tid');
                //   $bTeamStmt->execute(array(
                //     ':tid'=>$oneGame['team_b']
                //   ));
                //   $bTeamName = $bTeamStmt->fetch(PDO::FETCH_ASSOC)['team_name'];
                //   echo("<span id='game_".$oneGame['game_id']."'>
                //     <span id='team_".$oneGame['team_a']."'>".$aTeamName."</span> vs.
                //     <span id='team_".$oneGame['team_b']."'>".$bTeamName." --> </span>
                //   </span></br>");
                // };
        echo("</div>");
      };
    ?>
    <form method="POST">
      <input type="submit" name="enterBracket" value="SUBMIT" />
      <span id="leaveBrktButton"> CANCEL </span></br>
      <div style="padding: 10px;border: 1px solid black;display: inline-block" id="leaveBrktBox">
        <p>Are you sure? Your progress on the bracket will be deleted.</p>
        <input type="submit" name="cancelBracket" value="YES, trash this bracket" />
        <div id="hideBrktBttn">NO, keep working on this bracket</div>
      </div>
    </form>
  </body>
  <script>
    var groupId = <?php echo($_GET['group_id']) ?>;
    $(document).ready(()=>{
      var url = 'json_tournament.php?group_id=' + groupId;
      $.getJSON(url,(data)=>{
        console.log(data);
        var layerCount = 2;
        var tableId = 3;
        var pickNum = 0;
        for (var c = 0; c < layerCount; c++) {
          $("<table border='1px solid black' id='table_" + tableId + "'></table>").insertAfter("#layerTitle_" + tableId);
          if (c == 0) {
            console.log(tableId);
            for (var a = 0; a < data.length; a++) {
              var teamA = data[a];
              for (var b = a + 1; b < data.length; b++) {
                var teamB = data[b];
                if (teamA['game_id'] == teamB['game_id']) {
                  var pickIdA = "pickId_" + pickNum + "_" + teamA['team_id'];
                  var pickIdB = "pickId_" + pickNum + "_" + teamB['team_id'];
                  $("#table_" + tableId).append(
                    "<tr>\
                      <td id='" + pickIdA + "' data-team-id='"+teamA['team_id']+"'>"+teamA['team_name']+"</td>\
                      <td> VS </td>\
                      <td id='" + pickIdB + "' data-team-id='"+teamB['team_id']+"'>"+teamB['team_name']+"</td>\
                    </tr>");
                    // console.log(pickIdA);
                    // console.log(pickIdB);
                  $("#"+pickIdA).click((pickIdA)=>{
                    console.log("It worked for A!");
                    console.log(pickIdA.target.id);
                    $("#"+pickIdA).change((pickIdB)=>{
                      console.log(pickIdB.target.id);
                      // $("#"+pickIdA.target.id).data('winner','true');
                      // $("#"+pickIdB.target.id).data('winner','false');
                    });
                  });
                  $("#"+pickIdB).click((pickIdB)=>{
                    console.log("It worked for B!");
                    console.log(pickIdB.target.id);
                    $("#"+pickIdB.target.id).data('winner','true');
                    $("#"+pickIdA.target.id).data('winner','false');
                  });
                  pickNum++;
                };
              };
            };
          } else {
            console.log(tableId);
            $("<table border='1px solid black' id='table_" + tableId + "'></table>").insertAfter("#layerTitle_" + tableId);
            pickNum++;
          };
          tableId++;
          pickNum++;
        };
        $("h1").click(()=>{
          console.log("Minnesota: " + $("#pickId_0_5").data('winner'));
          console.log("OSU: " + $("#pickId_0_6").data('winner'));
          console.log("Michigan: " + $("#pickId_1_7").data('winner'));
          console.log("Notre Dame: " + $("#pickId_1_8").data('winner'));
        });
      })
    });
  </script>
</html>

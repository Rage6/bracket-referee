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
    </br>
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
        // console.log(data);
        var firstTable = 3;
        var lastTable = 4;
        var pickNum = 0;
        var totalGames = null;
        // This accounts for tournaments where two teams have don't play in the first round
        if ((data.length / 2) % 2 == 0) {
          totalGames = data.length / 2;
        } else {
          totalGames = (data.length / 2) + 1;
        }
        for (var c = firstTable; c <= lastTable; c++) {
          var tableId = c;
          $("<table border='1px solid black' id='table_" + tableId + "'></table>").insertAfter("#layerTitle_" + tableId);
          if (c == firstTable) {
            // console.log(tableId);
            var bothTeamIds = [];
            var gameNum = 0;
            for (var a = 0; a < data.length; a++) {
              var teamA = data[a];
              for (var b = a + 1; b < data.length; b++) {
                var teamB = data[b];
                if (teamA['game_id'] == teamB['game_id']) {
                  // var pickIdA = "pickId_"+pickNum+"_top";
                  // var pickIdB = "pickId_"+pickNum+"_bottom";
                  var pickIdA = "pickId_"+tableId+"_"+gameNum+"_top";
                  var pickIdB = "pickId_"+tableId+"_"+gameNum+"_bottom";
                  bothTeamIds.push([["#"+pickIdA],["#"+pickIdB]]);
                  $("#table_" + tableId).append(
                    "<tr>\
                      <td \
                        id='" + pickIdA + "'\
                        data-team_id='"+teamA['team_id']+"'\
                        data-team_name='"+teamA['team_name']+"'\
                        data-layer='"+tableId+"'\
                        data-game='" + gameNum + "'\
                        data-pick='"+pickNum+"'\
                        data-winner='null'>"+teamA['team_name']+"</td>\
                      <td> VS </td>\
                      <td id='" + pickIdB + "'\
                        data-team_id='"+teamB['team_id']+"'\
                        data-team_name='"+teamB['team_name']+"'\
                        data-layer='"+tableId+"'\
                        data-game='" + gameNum + "' data-pick='"+pickNum+"'\
                        data-winner='winner'>"+teamB['team_name']+"</td>\
                    </tr>");
                  $("#"+pickIdA).click((pickIdA)=>{
                    // console.log("It worked for A!");
                    var pickIdB = null;
                    for (var bothNum = 0; bothNum < bothTeamIds.length; bothNum++) {
                      if (bothTeamIds[bothNum][0][0] == "#"+pickIdA.target.id) {
                        // console.log(bothTeamIds[bothNum][0][0]);
                        pickIdB = bothTeamIds[bothNum][1][0];
                      };
                    };
                    // console.log(pickIdB);
                    $("#"+pickIdA.target.id).data('winner','true').css('background-color','green').css('color','white');
                    $(pickIdB).data('winner','false').css('background-color','white').css('color','black');
                  });
                  $("#"+pickIdB).click((pickIdB)=>{
                    // console.log("It worked for B!");
                    var pickIdA = null;
                    for (var bothNum = 0; bothNum < bothTeamIds.length; bothNum++) {
                      if (bothTeamIds[bothNum][1][0] == "#"+pickIdB.target.id) {
                        // console.log(bothTeamIds[bothNum][1][0]);
                        pickIdA = bothTeamIds[bothNum][0][0];
                      };
                    };
                    // console.log(pickIdA);
                    $("#"+pickIdB.target.id).data('winner','true').css('background-color','green').css('color','white');
                    $(pickIdA).data('winner','false').css('background-color','white').css('color','black');
                  });
                  pickNum++;
                  gameNum++;
                };
              };
            };
          } else {
            var totalGames = totalGames / 2;
            for (var gameNum = 0; gameNum < totalGames; gameNum++) {
              $("#table_"+tableId).append("\
              <tr>\
                <td id='pickId_"+tableId+"_"+gameNum+"_top'\
                  data-team_id='null'\
                  data-team_name='waiting on A...'\
                  data-layer='"+tableId+"'\
                  data-game='"+gameNum+"'\
                  data-pick='"+pickNum+"'\
                  data-winner='null'></td>\
                <td>VS</td>\
                <td id='pickId_"+tableId+"_"+gameNum+"_bottom'\
                  data-team_id='null'\
                  data-team_name='waiting on B...'\
                  data-layer='"+tableId+"'\
                  data-game='"+gameNum+"'\
                  data-pick='"+pickNum+"'\
                  data-winner='null'></td>\
              </tr>");
              $("#pickId_"+tableId+"_"+gameNum+"_top").text($("#pickId_"+tableId+"_"+gameNum+"_top").data('team_name'));
              $("#pickId_"+tableId+"_"+gameNum+"_bottom").text($("#pickId_"+tableId+"_"+gameNum+"_bottom").data('team_name'));
            };
            pickNum++;
          };
          // tableId++;
          // pickNum++;
        };
        console.log(bothTeamIds);
        $("h1").click(()=>{
          console.log("Minnesota: " + $("#pickId_3_0_top").data('winner'));
          console.log("OSU: " + $("#pickId_3_0_bottom").data('winner'));
          console.log("Michigan: " + $("#pickId_3_1_top").data('winner'));
          console.log("Notre Dame: " + $("#pickId_3_1_bottom").data('winner'));
        });
      })
    });
  </script>
</html>

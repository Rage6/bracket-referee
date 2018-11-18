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
                </h3>
              </div>");
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
      var gameUrl = 'json_games.php?group_id=' + groupId;
      gameIdList = [];
      $.getJSON(gameUrl,(gameData)=>{
        console.log(gameData)
        // this makes an array of pairs with [game_id, next_game_id] so that the next_game_id values can be assigned to an element right after it recieves its new game_id
        for (var i = 0; i < gameData.length; i++) {
          var oneList = [gameData[i]['game_id'],gameData[i]['next_game']];
          gameIdList.push(oneList);
        };
        // console.log(gameIdList);
      });
      var url = 'json_tournament.php?group_id=' + groupId;
      $.getJSON(url,(data)=>{
        // console.log(data);
        var firstTable = 2;
        var lastTable = 4;
        var pickNum = 0;
        var totalGames = null;
        bothTeamIds = [];
        // Below is for tournaments in which two teams do not have to play in the first round
        if ((data.length / 2) % 2 == 0) {
          totalGames = data.length / 2;
        } else {
          totalGames = (data.length / 2) + 1;
        };

        for (var c = firstTable; c <= lastTable; c++) {
          var tableId = c;
          $("<table border='1px solid black' id='table_" + tableId + "'></table>").insertAfter("#layerTitle_" + tableId);
          const findNextGame = (currentNum)=>{
            if (currentNum == 0) {
              return [0,"top"];
            } else if (currentNum == 1) {
              return [0,"bottom"];
            } else if (currentNum % 2 == 0) {
              return [currentNum / 2,"top"];
            } else {
              return [(currentNum - 1) / 2,"bottom"];
            };
          };
          // This is how the first round is set up and clicked on...
          if (c == firstTable) {
            // console.log(tableId);
            // var bothTeamIds = [];
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
                        data-game_id='" + teamA['game_id'] + "'\
                        data-next_game_id='" + teamA['next_game'] + "'\
                        data-pick='"+pickNum+"'\
                        data-winner='null'>"+teamA['team_name']+"</td>\
                      <td> VS </td>\
                      <td id='" + pickIdB + "'\
                        data-team_id='"+teamB['team_id']+"'\
                        data-team_name='"+teamB['team_name']+"'\
                        data-layer='"+tableId+"'\
                        data-game='" + gameNum + "'\
                        data-game_id='" + teamB['game_id'] + "'\
                        data-next_game_id='" + teamB['next_game'] + "'\
                        data-pick='"+pickNum+"'\
                        data-winner='null'>"+teamB['team_name']+"</td>\
                    </tr>");
                  // When clicking on the A team in the first round...
                  $("#"+pickIdA).click((pickIdA)=>{
                    var nextLayer = $("#"+pickIdA.target.id).data('layer') + 1;
                    var nextGame = findNextGame($("#"+pickIdA.target.id).data('game'));
                    var nextElement = "#pickId_"+nextLayer+"_"+nextGame[0]+"_"+nextGame[1];
                    console.log(nextElement);
                    var pickIdB = null;
                    for (var bothNum = 0; bothNum < bothTeamIds.length; bothNum++) {
                      if (bothTeamIds[bothNum][0][0] == "#"+pickIdA.target.id) {
                        pickIdB = bothTeamIds[bothNum][1][0];
                      };
                    };
                    var newId = $("#"+pickIdA.target.id).data('team_id');
                    console.log("newId: "+newId);
                    var newName = $("#"+pickIdA.target.id).data('team_name');
                    console.log("newName: "+newName);
                    $(nextElement)
                      .attr('data-team_id',newId)
                      .attr('data-team_name',newName)
                      .text($(nextElement).attr('data-team_name'));
                    $("#"+pickIdA.target.id)
                      .attr('data-winner','true')
                      .css('background-color','green')
                      .css('color','white');
                    $(pickIdB)
                      .attr('data-winner','false')
                      .css('background-color','white')
                      .css('color','black');
                  });
                  // ... and when clicking on the B team in the first round.
                  $("#"+pickIdB).click((pickIdB)=>{
                    var nextLayer = $("#"+pickIdB.target.id).data('layer') + 1;
                    var nextGame = findNextGame($("#"+pickIdB.target.id).data('game'));
                    var nextElement = "#pickId_"+nextLayer+"_"+nextGame[0]+"_"+nextGame[1];
                    var pickIdA = null;
                    for (var bothNum = 0; bothNum < bothTeamIds.length; bothNum++) {
                      if (bothTeamIds[bothNum][1][0] == "#"+pickIdB.target.id) {
                        pickIdA = bothTeamIds[bothNum][0][0];
                      };
                    };
                    var newId = $("#"+pickIdB.target.id).data('team_id');
                    var newName = $("#"+pickIdB.target.id).data('team_name');
                    $(nextElement)
                      .attr('data-team_id',newId)
                      .attr('data-team_name',newName)
                      .text($(nextElement).attr('data-team_name'));
                    $("#"+pickIdB.target.id)
                      .attr('data-winner','true')
                      .css('background-color','green')
                      .css('color','white');
                    $(pickIdA)
                      .attr('data-winner','false')
                      .css('background-color','white')
                      .css('color','black');
                  });
                  pickNum++;
                  gameNum++;
                };
              };
            };
          // ... and this is where it all happens in the following rounds.
          } else {
            var totalGames = totalGames / 2;
            for (var gameNum = 0; gameNum < totalGames; gameNum++) {
              // var check = $('*[data-game_id="9"]').data('team_name');
              // NOTICE: the next_game_id is found on the past game element with the smallest gameNum. The other element could be used instead, though, since both of them would produce the same next_game_id.
              var pastGameA = null;
              if (gameNum == 0) {
                pastGameA = 0;
              } else {
                pastGameA = gameNum * 2;
              };
              var pastTable = tableId - 1;
              var pastElementA = "#pickId_" + pastTable + "_" + pastGameA + "_top";
              // finds the upcoming, new game's id number (based on the previous element's next_game_id)
              var currentGameIdA = $(pastElementA).attr('data-next_game_id');
              var nextGameId = null;
              for (var j = 0; j < gameIdList.length; j++) {
                var curJ = gameIdList[j][0];
                var nexJ = gameIdList[j][1];
                if (curJ == currentGameIdA) {
                  nextGameId = nexJ;
                };
              };
              // console.log("returns next_game_id: "+nextGameId);
              $("#table_"+tableId).append("\
              <tr>\
                <td id='pickId_"+tableId+"_"+gameNum+"_top'\
                  data-team_id='null'\
                  data-team_name='waiting on A...'\
                  data-layer='"+tableId+"'\
                  data-game='"+gameNum+"'\
                  data-game_id='"+currentGameIdA+"'\
                  data-next_game_id='"+nextGameId+"'\
                  data-pick='"+pickNum+"'\
                  data-winner='null'></td>\
                <td>VS</td>\
                <td id='pickId_"+tableId+"_"+gameNum+"_bottom'\
                  data-team_id='null'\
                  data-team_name='waiting on B...'\
                  data-layer='"+tableId+"'\
                  data-game='"+gameNum+"'\
                  data-game_id='"+currentGameIdA+"'\
                  data-next_game_id='"+nextGameId+"'\
                  data-pick='"+pickNum+"'\
                  data-winner='null'></td>\
              </tr>");
              $("#pickId_"+tableId+"_"+gameNum+"_top")
                .text($("#pickId_"+tableId+"_"+gameNum+"_top").data('team_name'));
              $("#pickId_"+tableId+"_"+gameNum+"_bottom")
                .text($("#pickId_"+tableId+"_"+gameNum+"_bottom").data('team_name'));
              var pickIdA = "pickId_"+tableId+"_"+gameNum+"_top";
              var pickIdB = "pickId_"+tableId+"_"+gameNum+"_bottom";
              bothTeamIds.push([["#"+pickIdA],["#"+pickIdB]]);
              $("#"+pickIdA).click((pickIdA)=>{
                var nextLayer = $("#"+pickIdA.target.id).data('layer') + 1;
                var nextGame = findNextGame($("#"+pickIdA.target.id).data('game'));
                var nextElement = "#pickId_"+nextLayer+"_"+nextGame[0]+"_"+nextGame[1];
                var pickIdB = null;
                for (var bothNum = 0; bothNum < bothTeamIds.length; bothNum++) {
                  if ("#"+pickIdA.target.id == bothTeamIds[bothNum][0][0]) {
                    pickIdB = bothTeamIds[bothNum][1][0];
                  };
                };
                var newId = $("#"+pickIdA.target.id).data('team_id');
                var newName = $("#"+pickIdA.target.id).data('team_name');
                $(nextElement)
                  .attr('data-team_id',newId)
                  .attr('data-team_name',newName)
                  .text($("#"+pickIdA.target.id).attr('data-team_name'));
                $("#"+pickIdA.target.id)
                  .attr('data-winner','true')
                  .css('background-color','green')
                  .css('color','white');
                // console.log("#"+pickIdB);
                $(pickIdB)
                  .attr('data-winner','false')
                  .css('background-color','white')
                  .css('color','black');
              });
              $("#"+pickIdB).click((pickIdB)=>{
                var nextLayer = $("#"+pickIdB.target.id).data('layer') + 1;
                var nextGame = findNextGame($("#"+pickIdB.target.id).data('game'));
                var nextElement = "#pickId_"+nextLayer+"_"+nextGame[0]+"_"+nextGame[1];
                var pickIdA = null;
                for (var bothNum = 0; bothNum < bothTeamIds.length; bothNum++) {
                  if ("#"+pickIdB.target.id == bothTeamIds[bothNum][1][0]) {
                    pickIdA = bothTeamIds[bothNum][0][0];
                  };
                };
                var newId = $("#"+pickIdB.target.id).data('team_id');
                var newName = $("#"+pickIdB.target.id).data('team_name');
                $(nextElement)
                  .attr('data-team_id',newId)
                  .attr('data-team_name',newName)
                  .text($("#"+pickIdB.target.id).attr('data-team_name'));
                $("#"+pickIdB.target.id)
                  .attr('data-winner','true')
                  .css('background-color','green')
                  .css('color','white');
                $(pickIdA)
                  .attr('data-winner','false')
                  .css('background-color','white')
                  .css('color','black');
              });
            };
            pickNum++;
          };
        };
        console.log(bothTeamIds);
      })
    });
  </script>
</html>

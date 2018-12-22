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

  // Prevents hacker from manually switching players after logging in
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
  };

  // Prevents non-group members from getting into bracket_make.php manually
  $findGrpPlyr = $pdo->prepare('SELECT main_id FROM Groups_Players WHERE player_id=:pid AND group_id=:gid');
  $findGrpPlyr->execute(array(
    ':pid'=>$_SESSION['player_id'],
    ':gid'=>$_GET['group_id']
  ));
  $grpPlyrId = $findGrpPlyr->fetch(PDO::FETCH_ASSOC);
  if ($grpPlyrId == false) {
    $_SESSION['message'] = "<b style='color:red'>You can only submit a bracket AFTER you've joined this group.</b>";
    header('Location: group.php?group_id='.$_GET['group_id']);
    return false;
  };

  // Prevent members from putting in more than one bracket


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
  $tournStmt = $pdo->prepare('SELECT level_total,wildcard FROM Tournaments WHERE tourn_id=:tid');
  $tournStmt->execute(array(
    ':tid'=>$tournId
  ));
  $tournArray = $tournStmt->fetch(PDO::FETCH_ASSOC);
  $tournLevel = $tournArray['level_total'];
  $tournWildcard = $tournArray['wildcard'];

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
    <link rel="stylesheet" type="text/css" href="style/output.css"/>
    <script
    src="https://code.jquery.com/jquery-3.3.1.min.js"
    integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="
    crossorigin="anonymous"></script>
    <script src="main.js"></script>
  </head>
  <body>
    <div id="makePage">
      <div id="makeTitle">Make Your Bracket</div>
      <?php
        if (isset($_SESSION['message'])) {
          echo($_SESSION['message']);
          unset($_SESSION['message']);
        };
        $wildStmt = $pdo->prepare('SELECT level_id,layer,level_name,is_wildcard FROM Levels WHERE tourn_id=:tid');
        $wildStmt->execute(array(
          ':tid'=>$tournId
        ));
        while ($oneWild = $wildStmt->fetch(PDO::FETCH_ASSOC)) {
          if ($oneWild['is_wildcard'] == 1) {
            echo("<div id='layer_wild' class='gameList'>
                    <div class='roundTitle'>
                      <u>".$oneWild['level_name']."</u>
                    </div>
                  </div>");
          };
        };
        $levelStmt = $pdo->prepare('SELECT level_id,layer,level_name,is_wildcard FROM Levels WHERE tourn_id=:tid');
        $levelStmt->execute(array(
          ':tid'=>$tournId
        ));
        while ($oneLevel = $levelStmt->fetch(PDO::FETCH_ASSOC)) {
          if ($oneLevel['is_wildcard'] != 1) {
            echo("<div id='layer_".$oneLevel['layer']."' class='gameList'>
                    <div id='layerTitle_".$oneLevel['layer']."' class='roundTitle'>
                      <u>".$oneLevel['level_name']."</u>
                    </div>
                  </div>");
          };
        };
      ?>
      <form method="POST">
        <div id="makeSubmitBox">
          <div id="submitBracket">
            SUBMIT
          </div>
          <input type='submit' name='cancelBracket' value='CANCEL'/>
        </div>
      </form>
    </div>
  </body>
  <script>
    var groupId = <?php echo($_GET['group_id']) ?>;
    $(document).ready(()=>{

      var gameUrl = 'json_games.php?group_id=' + groupId;
      var gameIdList = [];
      var wildcardList = [];
      $.getJSON(gameUrl,(gameData)=>{
        // this makes an array of pairs with [game_id, next_game_id] so that the next_game_id values can be assigned to an element right after it recieves its new game_id
        for (var i = 0; i < gameData.length; i++) {
          var oneList = [gameData[i]['game_id'],gameData[i]['next_game'],gameData[i]['get_wildcard'],gameData[i]['team_a'],gameData[i]['team_b'],gameData[i]['is_wildcard']];
          gameIdList.push(oneList);
          if (oneList[5] == "1") {
            wildcardList.push([oneList[0],oneList[1],oneList[3],oneList[4],oneList[5]]);
          };
        };
        $("#submitBracket").click(()=>{
          var pickList = [];
          for (var k = 0; k < gameIdList.length; k++) {
            var oneGameId = gameIdList[k][0];
            // console.log(oneGameId);
            var onePick = $('[data-game_id='+oneGameId+'][data-winner="true"]').attr('data-team_id');
            var oneObject = {
              gameId: parseInt(oneGameId),
              pickId: parseInt(onePick)
            };
            pickList.push(oneObject);
          };
          var urlHead = "bracket_confirm.php?group_id=" + <?php echo($_GET['group_id']) ?> + "&gameTotal="+pickList.length + "&player_id=" + <?php echo($_SESSION['player_id']); ?>;
          for (var m = 0; m < pickList.length; m++) {
            var urlTag = "gameId"+m+"="+pickList[m]['gameId']+"&pickId"+m+"="+pickList[m]['pickId'];
            urlHead += "&" + urlTag;
          };
          // console.log(urlHead);
          window.location = urlHead;
        });
      });
      // console.log(gameIdList);
      // console.log(wildcardList);

      var url = 'json_tournament.php?group_id=' + groupId;
      $.getJSON(url,(data)=>{
        var firstTable = 1;
        var lastTable = <?php echo($tournLevel) ?> ;
        var pickNum = 0;
        var totalGames = null;
        var bothTeamIds = [];

        // Below is because some tournaments start with two teams not playing in the first round
        if (((data.length - wildcardList.length) / 2) % 2 == 0) {
          totalGames = (data.length - wildcardList.length) / 2;
        } else {
          totalGames = ((data.length - wildcardList.length) / 2) + 1;
        };

        // This will alternate the background colors for each game to help users read it easier
        var currentColor = "white";
        const alternateColors = (whichColor) => {
          if (whichColor == "white") {
            currentColor = "lightgrey";
          } else {
            currentColor = "white";
          };
        };

        // If there are wildcards, this installs them before the first regular round
        if (wildcardList.length > 0) {
          // $("#layer_wild").append("<table border='1px solid black' id='table_wild'></table>");
          $("#layer_wild").append("<div id='table_wild' class='allRoundLists'></div>");
          for (var d = 0; d < wildcardList.length; d++) {
            var wild_team_a = wildcardList[d][2];
            var wild_team_b = wildcardList[d][3];
            var wild_game_id = wildcardList[d][0];
            var wild_next_game = wildcardList[d][1];
            var wild_name_a = "none A";
            var wild_name_b = "none B";
            for (var getName = 0; getName < data.length; getName++) {
              if (data[getName]['game_id'] == wild_game_id && data[getName]['team_a'] == data[getName]['team_id']) {
                wild_name_a = data[getName]['team_name'];
              } else if (data[getName]['game_id'] == wild_game_id && data[getName]['team_b'] == data[getName]['team_id']) {
                wild_name_b = data[getName]['team_name'];
              };
            };
            alternateColors(currentColor);
            $("#table_wild").append(
              "<div style='background-color:"+currentColor+"'>\
                <div\
                  id='pickId_wild_"+d+"_top'\
                  class='allTeams'\
                  data-team_id="+wild_team_a+"\
                  data-team_name="+wild_name_a+"\
                  data-game="+d+"\
                  data-game_id="+wild_game_id+"\
                  data-next_game="+wild_next_game+">"+wild_name_a+"</div>\
                <div class='vs'>VS</div>\
                <div\
                  id='pickId_wild_"+d+"_bottom'\
                  class='allTeams'\
                  data-team_id="+wild_team_b+"\
                  data-team_name="+wild_name_b+"\
                  data-game="+d+"\
                  data-game_id="+wild_game_id+"\
                  data-next_game="+wild_next_game+">"+wild_name_b+"</div>\
              </div>");
          };
        };
        // Now the regular games begin...
        for (var c = firstTable; c <= lastTable; c++) {
          var tableId = c;
          $("<div id='table_" + tableId + "' class='allRoundLists'></div>").insertAfter("#layerTitle_" + tableId);
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
            var gameNum = 0;
            for (var a = 0; a < data.length; a++) {
              var teamA = data[a];
              for (var b = a + 1; b < data.length; b++) {
                var teamB = data[b];
                if (teamA['game_id'] == teamB['game_id'] || (teamA['get_wildcard']=="1" && b + 1 == data.length)) {
                  // This prevent wildcard games from appearing in the first round
                  var isWildcard = false;
                  for (var g = 0; g < wildcardList.length; g++) {
                    if (wildcardList[g][0] == teamA['game_id']) {
                      isWildcard = true;
                    };
                  };
                  //
                  if (isWildcard == false) {
                    var pickIdA = "pickId_"+tableId+"_"+gameNum+"_top";
                    var pickIdB = "pickId_"+tableId+"_"+gameNum+"_bottom";
                    bothTeamIds.push([["#"+pickIdA],["#"+pickIdB]]);
                    // This fills in the blank spots that happen when a wildcard team hasn't been selected yet for certain Round 1 games
                    if (teamA['get_wildcard'] == "1") {
                      var bTeamData = {
                        id: "null",
                        name: "---",
                        gameId: teamA['game_id'],
                        nextGame: teamA['next_game']
                      };
                    } else {
                      var bTeamData = {
                        id: teamB['team_id'],
                        name: teamB['team_name'],
                        gameId: teamB['game_id'],
                        nextGame: teamB['next_game']
                      };
                    };
                    //
                    alternateColors(currentColor);
                    $("#table_" + tableId).append(
                      "<div style='background-color:"+currentColor+"'>\
                        <div \
                          id='" + pickIdA + "'\
                          class='allTeams'\
                          data-team_id="+teamA['team_id']+"\
                          data-team_name='"+teamA['team_name']+"'\
                          data-layer='"+tableId+"'\
                          data-game='" + gameNum + "'\
                          data-game_id='" + teamA['game_id'] + "'\
                          data-next_game_id='" + teamA['next_game'] + "'\
                          data-pick='"+pickNum+"'\
                          data-winner='null'>"+teamA['team_name']+"</div>\
                        <div> VS </div>\
                        <div\
                          id='" + pickIdB + "'\
                          class='allTeams'\
                          data-team_id="+bTeamData.id+"\
                          data-team_name='"+bTeamData.name+"'\
                          data-layer='"+tableId+"'\
                          data-game='" + gameNum + "'\
                          data-game_id='" + bTeamData.gameId + "'\
                          data-next_game_id='" + bTeamData.nextGame + "'\
                          data-pick='"+pickNum+"'\
                          data-winner='null'>"+bTeamData.name+"</div>\
                      </div>");
                    // When clicking on the A team in the first round...
                    $("#"+pickIdA).click((pickIdA)=>{
                      var nextLayer = parseInt($("#"+pickIdA.target.id).attr('data-layer')) + 1;
                      var nextGame = findNextGame($("#"+pickIdA.target.id).attr('data-game'));
                      var nextElement = "#pickId_"+nextLayer+"_"+nextGame[0]+"_"+nextGame[1];
                      console.log(nextElement);
                      var pickIdB = null;
                      for (var bothNum = 0; bothNum < bothTeamIds.length; bothNum++) {
                        if (bothTeamIds[bothNum][0][0] == "#"+pickIdA.target.id) {
                          pickIdB = bothTeamIds[bothNum][1][0];
                        };
                      };
                      var newId = $("#"+pickIdA.target.id).attr('data-team_id');
                      console.log("newId: "+newId);
                      var newName = $("#"+pickIdA.target.id).attr('data-team_name');
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
                      var nextLayer = parseInt($("#"+pickIdB.target.id).attr('data-layer')) + 1;
                      var nextGame = findNextGame($("#"+pickIdB.target.id).attr('data-game'));
                      var nextElement = "#pickId_"+nextLayer+"_"+nextGame[0]+"_"+nextGame[1];
                      console.log(nextElement);
                      var pickIdA = null;
                      for (var bothNum = 0; bothNum < bothTeamIds.length; bothNum++) {
                        if (bothTeamIds[bothNum][1][0] == "#"+pickIdB.target.id) {
                          pickIdA = bothTeamIds[bothNum][0][0];
                        };
                      };
                      var newId = $("#"+pickIdB.target.id).attr('data-team_id');
                      var newName = $("#"+pickIdB.target.id).attr('data-team_name');
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
                if (b + 1 == data.length) {
                  // console.log(data[a]['team_name']);
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
              var pastElement = "#pickId_" + pastTable + "_" + pastGameA + "_top";
              // finds the upcoming, new game's id number (based on the previous element's next_game_id)
              var currentGameId = $(pastElement).attr('data-next_game_id');
              var nextGameId = null;
              for (var j = 0; j < gameIdList.length; j++) {
                var curJ = gameIdList[j][0];
                var nexJ = gameIdList[j][1];
                if (curJ == currentGameId) {
                  nextGameId = nexJ;
                };
              };
              // console.log("returns next_game_id: "+nextGameId);
              alternateColors(currentColor);
              $("#table_"+tableId).append("\
              <div style='background-color:"+currentColor+"'>\
                <div \
                  id='pickId_"+tableId+"_"+gameNum+"_top'\
                  class='allTeams'\
                  data-team_id='null'\
                  data-team_name='---'\
                  data-layer='"+tableId+"'\
                  data-game='"+gameNum+"'\
                  data-game_id='"+currentGameId+"'\
                  data-next_game_id='"+nextGameId+"'\
                  data-pick='"+pickNum+"'\
                  data-winner='null'></div>\
                <div>VS</div>\
                <div \
                  id='pickId_"+tableId+"_"+gameNum+"_bottom'\
                  class='allTeams'\
                  data-team_id='null'\
                  data-team_name='---'\
                  data-layer='"+tableId+"'\
                  data-game='"+gameNum+"'\
                  data-game_id='"+currentGameId+"'\
                  data-next_game_id='"+nextGameId+"'\
                  data-pick='"+pickNum+"'\
                  data-winner='null'></div>\
              </div>");
              $("#pickId_"+tableId+"_"+gameNum+"_top")
                .text($("#pickId_"+tableId+"_"+gameNum+"_top")
                // .data('team_name'));
                .attr('data-team_name'));
              $("#pickId_"+tableId+"_"+gameNum+"_bottom")
                .text($("#pickId_"+tableId+"_"+gameNum+"_bottom")
                // .data('team_name'));
                .attr('data-team_name'));
              var pickIdA = "pickId_"+tableId+"_"+gameNum+"_top";
              var pickIdB = "pickId_"+tableId+"_"+gameNum+"_bottom";
              bothTeamIds.push([["#"+pickIdA],["#"+pickIdB]]);
              $("#"+pickIdA).click((pickIdA)=>{
                if ($("#"+pickIdA.target.id).attr('data-team_id') != "null") {
                  var nextLayer = parseInt($("#"+pickIdA.target.id).attr('data-layer')) + 1;
                  console.log(nextElement);
                  var nextGame = findNextGame($("#"+pickIdA.target.id).attr('data-game'));
                  var nextElement = "#pickId_"+nextLayer+"_"+nextGame[0]+"_"+nextGame[1];
                  var pickIdB = null;
                  for (var bothNum = 0; bothNum < bothTeamIds.length; bothNum++) {
                    if ("#"+pickIdA.target.id == bothTeamIds[bothNum][0][0]) {
                      pickIdB = bothTeamIds[bothNum][1][0];
                    };
                  };
                  var newId = $("#"+pickIdA.target.id).attr('data-team_id');
                  var newName = $("#"+pickIdA.target.id).attr('data-team_name');
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
                };
              });
              $("#"+pickIdB).click((pickIdB)=>{
                if ($("#"+pickIdB.target.id).attr('data-team_id') != "null") {
                  var nextLayer = parseInt($("#"+pickIdB.target.id).attr('data-layer')) + 1;
                  console.log(nextElement);
                  var nextGame = findNextGame($("#"+pickIdB.target.id).attr('data-game'));
                  var nextElement = "#pickId_"+nextLayer+"_"+nextGame[0]+"_"+nextGame[1];
                  var pickIdA = null;
                  for (var bothNum = 0; bothNum < bothTeamIds.length; bothNum++) {
                    if ("#"+pickIdB.target.id == bothTeamIds[bothNum][1][0]) {
                      pickIdA = bothTeamIds[bothNum][0][0];
                    };
                  };
                  var newId = $("#"+pickIdB.target.id).attr('data-team_id');
                  var newName = $("#"+pickIdB.target.id).attr('data-team_name');
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
                };
              });
            };
            pickNum++;
          };
        };

        // This is where the wildcard buttons starts...");
        if (wildcardList.length > 0) {
          for (var e = 0; e < wildcardList.length; e++) {
            var pickWildA = "#pickId_wild_"+e+"_top";
            var pickWildB = "#pickId_wild_"+e+"_bottom";
            // var afterWild = $("[data-game_id="+wildcardList[e][1]+"][data-layer=1][data-team_id='null']");
            // var idAfterWild = "#" + $(afterWild).attr('id');
            $(pickWildA).click((event)=>{
              console.log("This is A...");
              pickWildA = "#" + event.target.id;
              var thisGame = $(pickWildA).attr('data-game');
              pickWildB = "#pickId_wild_"+thisGame+"_bottom";
              if ($($("[data-game_id="+wildcardList[thisGame][1]+"][data-layer=1]")[1]).attr("data-team_id") == "null") {
                console.log("if");
                var afterWild = $("[data-game_id="+wildcardList[thisGame][1]+"][data-layer=1]")[1];
              } else {
                console.log("else");
                var currentId = $($("[data-game_id="+wildcardList[thisGame][1]+"][data-layer=1]")[1]).attr("data-team_id");
                var afterWild = $("[data-game_id="+wildcardList[thisGame][1]+"][data-layer=1][data-team_id='"+currentId+"']");
              };
              var idAfterWild = "#" + $(afterWild).attr('id');
              console.log(idAfterWild);
              $(idAfterWild)
                .attr('data-team_id',$(pickWildA).attr('data-team_id'))
                .attr('data-team_name',$(pickWildA).attr('data-team_name'))
                .text($(pickWildA).text());
              $(pickWildA)
                .attr('data-winner','true')
                .css('background-color','green')
                .css('color','white');
              $(pickWildB)
                .attr('data-winner','false')
                .css('background-color','white')
                .css('color','black');
            });
            $(pickWildB).click((event)=>{
              console.log("This is B...");
              pickWildB = "#" + event.target.id;
              var thisGame = $(pickWildB).attr('data-game');
              pickWildA = "#pickId_wild_"+thisGame+"_top";

              if ($($("[data-game_id="+wildcardList[thisGame][1]+"][data-layer=1]")[1]).attr("data-team_id") == "null") {
                console.log("if");
                var afterWild = $("[data-game_id="+wildcardList[thisGame][1]+"][data-layer=1]")[1];
              } else {
                console.log("else");
                var currentId = $($("[data-game_id="+wildcardList[thisGame][1]+"][data-layer=1]")[1]).attr("data-team_id");
                var afterWild = $("[data-game_id="+wildcardList[thisGame][1]+"][data-layer=1][data-team_id='"+currentId+"']");
              };

              var idAfterWild = "#" + $(afterWild).attr('id');
              console.log(idAfterWild);
              $(idAfterWild)
                .attr('data-team_id',$(pickWildB).attr('data-team_id'))
                .attr('data-team_name',$(pickWildB).attr('data-team_name'))
                .text($(pickWildB).text());
              $(pickWildB)
                .attr('data-winner','true')
                .css('background-color','green')
                .css('color','white');
              $(pickWildA)
                .attr('data-winner','false')
                .css('background-color','white')
                .css('color','black');
            });
          };
        // end of button

        // console.log("testing bothTeamIds");
        // console.log(bothTeamIds);
        };

      });
    });
  </script>
</html>


<!-- The below is an example of the URL when the bracket is submitted to the 2017 March Madness:

bracket_confirm.php?
group_id=1
&gameTotal=67
&player_id=12
&gameId0=1&pickId0=21
&gameId1=2&pickId1=65
&gameId2=3&pickId2=65
&gameId3=19&pickId3=21
&gameId4=20&pickId4=30
&gameId5=21&pickId5=65
&gameId6=22&pickId6=3
&gameId7=23&pickId7=21
&gameId8=24&pickId8=28
&gameId9=25&pickId9=30
&gameId10=26&pickId10=32
&gameId11=27&pickId11=65
&gameId12=28&pickId12=77
&gameId13=29&pickId13=3
&gameId14=30&pickId14=59
&gameId15=31&pickId15=21
&gameId16=32&pickId16=27
&gameId17=33&pickId17=28
&gameId18=34&pickId18=7
&gameId19=35&pickId19=30
&gameId20=36&pickId20=31
&gameId21=37&pickId21=32
&gameId22=38&pickId22=44
&gameId23=39&pickId23=65
&gameId24=40&pickId24=66
&gameId25=41&pickId25=77
&gameId26=42&pickId26=2
&gameId27=43&pickId27=3
&gameId28=44&pickId28=8
&gameId29=45&pickId29=59
&gameId30=46&pickId30=72
&gameId31=47&pickId31=65
&gameId32=48&pickId32=61
&gameId33=49&pickId33=66
&gameId34=50&pickId34=58
&gameId35=51&pickId35=77
&gameId36=52&pickId36=62
&gameId37=53&pickId37=2
&gameId38=54&pickId38=68
&gameId39=55&pickId39=3
&gameId40=56&pickId40=69
&gameId41=57&pickId41=8
&gameId42=58&pickId42=63
&gameId43=59&pickId43=59
&gameId44=60&pickId44=71
&gameId45=61&pickId45=72
&gameId46=62&pickId46=64
&gameId47=63&pickId47=21
&gameId48=64&pickId48=26
&gameId49=65&pickId49=27
&gameId50=66&pickId50=23
&gameId51=67&pickId51=28
&gameId52=68&pickId52=1
&gameId53=69&pickId53=7
&gameId54=70&pickId54=29
&gameId55=71&pickId55=30
&gameId56=72&pickId56=4
&gameId57=73&pickId57=31
&gameId58=74&pickId58=24
&gameId59=75&pickId59=32
&gameId60=76&pickId60=25
&gameId61=77&pickId61=44
&gameId62=78&pickId62=22
&gameId63=80&pickId63=73
&gameId64=81&pickId64=107
&gameId65=82&pickId65=103
&gameId66=83&pickId66=11 -->

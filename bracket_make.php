<?php
  session_start();
  require_once("pdo.php");

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
  $tournStmt = $pdo->prepare('SELECT level_total,wildcard,third_place,start_date,selection_date FROM Tournaments WHERE tourn_id=:tid');
  $tournStmt->execute(array(
    ':tid'=>$tournId
  ));
  $tournArray = $tournStmt->fetch(PDO::FETCH_ASSOC);
  $tournLevel = $tournArray['level_total'];
  $tournWildcard = $tournArray['wildcard'];
  $tournThird = $tournArray['third_place'];
  $tournStart = $tournArray['start_date'];
  $tournSelect = $tournArray['selection_date'];

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
    <link href="https://fonts.googleapis.com/css?family=Bevan|Catamaran|Special+Elite|Staatliches" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="style/output.css"/>
    <link rel="icon" type="image/x-icon" href="style/img/index/bracket_favicon.ico"/>
    <link rel="icon" type="image/x-icon" href="style/img/index/bracket_favicon.ico"/>
    <script
    src="https://code.jquery.com/jquery-3.3.1.min.js"
    integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="
    crossorigin="anonymous"></script>
    <script src="main.js"></script>
  </head>
  <body>
    <div id="makePage">
      <div id="makeTitleBkgd">
        <div id="makeTitle">
          Make Your Bracket
        </div>
      </div>
      <div id="allGameLists">
      <?php
        if (isset($_SESSION['message'])) {
          echo($_SESSION['message']);
          unset($_SESSION['message']);
        };
      ?>
      <div id="bracketRange">
        Brackets can be filled out between selections (<?php echo($tournSelect)?>) and the first game (<?php echo(substr($tournStart,0,10)) ?>).
      </div>
      <?php
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
                    <div class='waiting'>
                      <img src='style/img/loading.gif'/>
                    </div>
                  </div>");
          };
        };
        $levelStmt = $pdo->prepare('SELECT level_id,layer,level_name,is_wildcard,is_third FROM Levels WHERE tourn_id=:tid');
        $levelStmt->execute(array(
          ':tid'=>$tournId
        ));
        while ($oneLevel = $levelStmt->fetch(PDO::FETCH_ASSOC)) {
          if ($oneLevel['is_wildcard'] != 1) {
            if ($oneLevel['is_third'] != 1) {
            echo("<div id='layer_".$oneLevel['layer']."' class='gameList'>
                    <div id='layerTitle_".$oneLevel['layer']."' class='roundTitle'>
                      <u>".$oneLevel['level_name']."</u>
                    </div>
                    <div class='waiting'>
                      <img src='style/img/loading.gif'/>
                    </div>
                  </div>");
            } else {
              echo("<div id='layer_".$oneLevel['layer']."' class='gameList thirdPlace'>
                      <div id='layerTitle_".$oneLevel['layer']."' class='roundTitle'>
                        <u>".$oneLevel['level_name']."</u>
                      </div>
                      <div class='waiting'>
                        <img src='style/img/loading.gif'/>
                      </div>
                    </div>");
            };
          };
        };
      ?>
      </div>
      <form method="POST">
        <div id="makeSubmitBox">
          <div id="submitBracket">
            SUBMIT
          </div>
          <input id='cancelBracket' type='submit' name='cancelBracket' value='CANCEL'/>
        </div>
      </form>
    </div>
  </body>
  <script>
    var groupId = <?php echo($_GET['group_id']) ?>;
    $(document).ready(()=>{
      $(".waiting").hide();
      var gameUrl = 'json_games.php?group_id=' + groupId;
      var gameIdList = [];
      var wildcardList = [];
      $.getJSON(gameUrl,(gameData)=>{
        // this makes an array of pairs with [game_id, next_game_id] so that the next_game_id values can be assigned to an element right after it recieves its new game_id
        for (var i = 0; i < gameData.length; i++) {
          var oneList =
          [
            gameData[i]['game_id'],
            gameData[i]['next_game'],
            gameData[i]['get_wildcard'],
            gameData[i]['team_a'],
            gameData[i]['team_b'],
            gameData[i]['is_wildcard'],
            gameData[i]['third_id'],
            gameData[i]['third_player'],
            gameData[i]['is_third']
          ];
          gameIdList.push(oneList);
          if (oneList[5] == "1") {
            wildcardList.push([oneList[0],oneList[1],oneList[3],oneList[4],oneList[5]]);
          };
        };
        $("#submitBracket").click(()=>{
          var pickList = [];
          for (var k = 0; k < gameIdList.length; k++) {
            var oneGameId = gameIdList[k][0];
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

      // The json_games.php data has to be included in this ONLY because, if a first-round game is waiting on TWO games from the wildcard round, its lack of team_a and team_b will make json_tournament.php ignore it. The code before json_tournament FORCES it to accept any empty games too.
      $.getJSON(gameUrl,(getEmptyGame)=>{
        var emptyGameList = [];
        for (var emptyNum = 0; emptyNum < getEmptyGame.length; emptyNum++) {
          if (getEmptyGame[emptyNum]['team_a'] == null && getEmptyGame[emptyNum]['team_b'] == null && getEmptyGame[emptyNum]['get_wildcard'] == "1") {
            var emptyObject = {
              team_id: "null",
              team_name: "---",
              game_id: getEmptyGame[emptyNum]['game_id'],
              next_game: getEmptyGame[emptyNum]['next_game'],
              team_a: null,
              team_b: null,
              get_wildcard: getEmptyGame[emptyNum]['get_wildcard'],
              is_wildcard: getEmptyGame[emptyNum]['is_wildcard']
            };
            emptyGameList.push(emptyObject);
          };
        };

        // Now the json_tournament.php gets each team in each data comes into play
        var url = 'json_tournament.php?group_id=' + groupId;
        $.getJSON(url,(data)=>{
          if (data.length > 0) {
            var firstTable = 1;
            var lastTable = <?php echo($tournLevel) ?> ;
            var pickNum = 0;
            var totalGames = null;
            var bothTeamIds = [];
            // This will add another level if a "Third Place" game will take place
            if (<?php echo($tournThird) ?> == "1") {
              lastTable++;
            };

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
              $("#layer_wild").append("<div id='table_wild' class='allRoundLists'></div>");
              for (var d = 0; d < wildcardList.length; d++) {
                var wild_team_a = wildcardList[d][2];
                var wild_team_b = wildcardList[d][3];
                var wild_game_id = wildcardList[d][0];
                var wild_next_game = wildcardList[d][1];
                var wild_name_a = "---";
                var wild_name_b = "---";
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
                      data-team_name='"+wild_name_a+"'\
                      data-game="+d+"\
                      data-game_id="+wild_game_id+"\
                      data-next_game="+wild_next_game+">"+wild_name_a+"</div>\
                    <div class='vs'>VS</div>\
                    <div\
                      id='pickId_wild_"+d+"_bottom'\
                      class='allTeams'\
                      data-team_id="+wild_team_b+"\
                      data-team_name='"+wild_name_b+"'\
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
                    // --
                    if (teamA['game_id'] == teamB['game_id'] || (teamA['get_wildcard']=="1" && b + 1 == data.length)) {
                      //-- This prevent wildcard games from appearing in the first round
                      var isWildcard = false;
                      for (var g = 0; g < wildcardList.length; g++) {
                        if (wildcardList[g][0] == teamA['game_id']) {
                          isWildcard = true;
                        };
                      };
                      //--
                      if (isWildcard == false) {
                        var pickIdA = "pickId_"+tableId+"_"+gameNum+"_top";
                        var pickIdB = "pickId_"+tableId+"_"+gameNum+"_bottom";
                        bothTeamIds.push([["#"+pickIdA],["#"+pickIdB]]);
                        //-- This fills in the blank spots that happen when a wildcard team hasn't been selected yet for certain Round 1 games
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
                        //--
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
                    // In case the final game also needs to get a wildcard, this is necessary...
                    if (a + 2 == data.length && b + 1 == data.length && teamB['get_wildcard'] == "1") {
                      console.log("the last game needs a wildcard");
                      var pickIdA = "pickId_"+tableId+"_"+gameNum+"_top";
                      var pickIdB = "pickId_"+tableId+"_"+gameNum+"_bottom";
                      bothTeamIds.push([["#"+pickIdA],["#"+pickIdB]]);
                      var bTeamData = {
                        id: "null",
                        name: "---",
                        gameId: teamA['game_id'],
                        nextGame: teamA['next_game']
                      };
                      alternateColors(currentColor);
                      $("#table_" + tableId).append(
                        "<div style='background-color:"+currentColor+"'>\
                          <div \
                            id='" + pickIdA + "'\
                            class='allTeams'\
                            data-team_id="+teamB['team_id']+"\
                            data-team_name='"+teamB['team_name']+"'\
                            data-layer='"+tableId+"'\
                            data-game='" + gameNum + "'\
                            data-game_id='" + teamB['game_id'] + "'\
                            data-next_game_id='" + teamB['next_game'] + "'\
                            data-pick='"+pickNum+"'\
                            data-winner='null'>"+teamB['team_name']+"</div>\
                          <div> VS </div>\
                          <div\
                            id='" + pickIdB + "'\
                            class='allTeams'\
                            data-team_id="+bTeamData.id+"\
                            data-team_name='"+bTeamData.name+"'\
                            data-layer='"+tableId+"'\
                            data-game='" + gameNum + "'\
                            data-game_id='" + teamB['game_id'] + "'\
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
                };
                // To add on an empty row if TWO wildcards are going to enter it
                // var startEmpty = data.length;
                // objectNum = 0;
                // for (var n = startEmpty; n < startEmpty + emptyGameList.length; n++) {
                //   alternateColors(currentColor);
                //   var thisObject = emptyGameList[objectNum];
                //   $("#table_" + tableId).append(
                //     "<div style='background-color:"+currentColor+"'>\
                //       <div \
                //         id='pickId_1_"+gameNum+"_top'\
                //         class='allTeams'\
                //         data-team_id='null'\
                //         data-team_name='---'\
                //         data-layer='1'\
                //         data-game='" + gameNum + "'\
                //         data-game_id='" + thisObject['game_id'] + "'\
                //         data-next_game_id='" + thisObject['next_game'] + "'\
                //         data-pick='"+pickNum+"'\
                //         data-winner='null'>\
                //           ---\
                //       </div>\
                //       <div> VS </div>\
                //       <div\
                //         id='pickId_1_"+gameNum+"_bottom'\
                //         class='allTeams'\
                //         data-team_id='null'\
                //         data-team_name='---'\
                //         data-layer='1'\
                //         data-game='" + gameNum + "'\
                //         data-game_id='" + thisObject['game_id'] + "'\
                //         data-next_game_id='" + thisObject['next_game'] + "'\
                //         data-pick='"+pickNum+"'\
                //         data-winner='null'>\
                //           ---\
                //       </div>\
                //     </div>");
                //   objectNum++;
                //   pickNum++;
                //   gameNum++;
                // };
              // ... and this is where it all happens in the following rounds.
              } else {
                var totalGames = totalGames / 2;
                if (totalGames < 1) {
                  totalGames = 1;
                };
                for (var gameNum = 0; gameNum < totalGames; gameNum++) {
                  // NOTICE: the next_game_id is found on the past game element with the smallest gameNum. The other element could be used instead, though, since both of them would produce the same next_game_id.
                  var pastGameA = null;
                  if (gameNum == 0) {
                    pastGameA = 0;
                  } else {
                    pastGameA = gameNum * 2;
                  };
                  var pastTable = tableId - 1;
                  // --- In case there is a "Third Place" game...
                  if (<?php echo($tournThird) ?> == "1" && tableId == lastTable) {
                    pastTable = tableId - 2;
                  };
                  // ---
                  var pastElement = "#pickId_" + pastTable + "_" + pastGameA + "_top";

                  // Fills the game's id IF it is the 'third place' game
                  var thirdGameId = null;
                  for (var p = 0; p < gameIdList.length; p++) {
                    // if (gameIdList[p][6] != "0") {
                    if (gameIdList[p][6] != null) {
                      thirdGameId = gameIdList[p][6];
                    };
                  };

                  // finds the upcoming, new game's id number (based on the previous element's next_game_id) and tags it at a 'third game' or not
                  if (<?php echo($tournThird) ?> == "1" && tableId == lastTable) {
                    var currentGameId = thirdGameId;
                    var confirmThird = "1";
                  } else {
                    var currentGameId = $(pastElement).attr('data-next_game_id');
                    var confirmThird = "0";
                  };
                  console.log(currentGameId);

                  var nextGameId = null;
                  for (var j = 0; j < gameIdList.length; j++) {
                    var curJ = gameIdList[j][0];
                    var nexJ = gameIdList[j][1];
                    if (curJ == currentGameId) {
                      nextGameId = nexJ;
                    };
                  };

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
                      data-third_id='"+thirdGameId+"'\
                      data-pick='"+pickNum+"'\
                      data-winner='null'\
                      data-is_third='"+confirmThird+"'></div>\
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
                      data-third_id='"+thirdGameId+"'\
                      data-pick='"+pickNum+"'\
                      data-winner='null'>\
                      data-is_third='"+confirmThird+"'></div>\
                  </div>");
                  $("#pickId_"+tableId+"_"+gameNum+"_top")
                    .text($("#pickId_"+tableId+"_"+gameNum+"_top")
                    .attr('data-team_name'));
                  $("#pickId_"+tableId+"_"+gameNum+"_bottom")
                    .text($("#pickId_"+tableId+"_"+gameNum+"_bottom")
                    .attr('data-team_name'));
                  var pickIdA = "pickId_"+tableId+"_"+gameNum+"_top";
                  var pickIdB = "pickId_"+tableId+"_"+gameNum+"_bottom";
                  bothTeamIds.push([["#"+pickIdA],["#"+pickIdB]]);
                  $("#"+pickIdA).click((pickIdA)=>{
                    if ($("#"+pickIdA.target.id).attr('data-team_id') != "null") {
                      var nextLayer = parseInt($("#"+pickIdA.target.id).attr('data-layer')) + 1;
                      var nextGame = findNextGame($("#"+pickIdA.target.id).attr('data-game'));
                      var nextElement = "#pickId_"+nextLayer+"_"+nextGame[0]+"_"+nextGame[1];
                      console.log("nextElement: "+nextElement);
                      var pickIdB = null;
                      for (var bothNum = 0; bothNum < bothTeamIds.length; bothNum++) {
                        if ("#"+pickIdA.target.id == bothTeamIds[bothNum][0][0]) {
                          pickIdB = bothTeamIds[bothNum][1][0];
                        };
                      };
                      var newId = $("#"+pickIdA.target.id).attr('data-team_id');
                      var newName = $("#"+pickIdA.target.id).attr('data-team_name');
                      if (<?php echo($tournThird) ?> == "1" && nextLayer == lastTable) {
                        console.log("blocks changing the 'third place' game");
                      } else {
                        $(nextElement)
                          .attr('data-team_id',newId)
                          .attr('data-team_name',newName)
                          .text($("#"+pickIdA.target.id).attr('data-team_name'));
                      };
                      $("#"+pickIdA.target.id)
                        .attr('data-winner','true')
                        .css('background-color','green')
                        .css('color','white');
                      $(pickIdB)
                        .attr('data-winner','false')
                        .css('background-color','white')
                        .css('color','black');
                      // -- Everything between this is for tournaments with a 'third place' game
                      var clickedId = $("#"+pickIdA.target.id);
                      if (<?php echo($tournThird) ?> == "1") {
                        var thirdPlyIdA = $(pickIdB).attr("data-team_id");
                        var thirdPlyNameA = $(pickIdB).attr("data-team_name");
                        if (clickedId[0].id == "pickId_"+(lastTable-2)+"_0_top") {
                          $("#pickId_"+lastTable+"_0_top")
                            .attr("data-team_id",thirdPlyIdA)
                            .attr("data-team_name",thirdPlyNameA)
                            .text(thirdPlyNameA)
                        } else if (clickedId[0].id == "pickId_"+(lastTable-2)+"_1_top") {
                          $("#pickId_"+lastTable+"_0_bottom")
                            .attr("data-team_id",thirdPlyIdA)
                            .attr("data-team_name",thirdPlyNameA)
                            .text(thirdPlyNameA)
                        };
                      };
                      // --
                    };
                  });
                  $("#"+pickIdB).click((pickIdB)=>{
                    if ($("#"+pickIdB.target.id).attr('data-team_id') != "null") {
                      var nextLayer = parseInt($("#"+pickIdB.target.id).attr('data-layer')) + 1;
                      var nextGame = findNextGame($("#"+pickIdB.target.id).attr('data-game'));
                      var nextElement = "#pickId_"+nextLayer+"_"+nextGame[0]+"_"+nextGame[1];
                      console.log(nextElement);
                      var pickIdA = null;
                      for (var bothNum = 0; bothNum < bothTeamIds.length; bothNum++) {
                        if ("#"+pickIdB.target.id == bothTeamIds[bothNum][1][0]) {
                          pickIdA = bothTeamIds[bothNum][0][0];
                        };
                      };
                      var newId = $("#"+pickIdB.target.id).attr('data-team_id');
                      var newName = $("#"+pickIdB.target.id).attr('data-team_name');
                      if (<?php echo($tournThird) ?> == "1" && nextLayer == lastTable) {
                        console.log("blocks changing the 'third place' game");
                      } else {
                        $(nextElement)
                          .attr('data-team_id',newId)
                          .attr('data-team_name',newName)
                          .text($("#"+pickIdB.target.id).attr('data-team_name'));
                      };
                      $("#"+pickIdB.target.id)
                        .attr('data-winner','true')
                        .css('background-color','green')
                        .css('color','white');
                      $(pickIdA)
                        .attr('data-winner','false')
                        .css('background-color','white')
                        .css('color','black');
                      // -- Everything between this is for tournaments with a 'third place' game
                      var clickedId = $("#"+pickIdB.target.id);
                      if (<?php echo($tournThird) ?> == "1") {
                        var thirdPlyIdB = $(pickIdA).attr("data-team_id");
                        var thirdPlyNameB = $(pickIdA).attr("data-team_name");
                        if (clickedId[0].id == "pickId_"+(lastTable-2)+"_0_bottom") {
                          $("#pickId_"+lastTable+"_0_top")
                            .attr("data-team_id",thirdPlyIdB)
                            .attr("data-team_name",thirdPlyNameB)
                            .text(thirdPlyNameB)
                        } else if (clickedId[0].id == "pickId_"+(lastTable-2)+"_1_bottom") {
                          $("#pickId_"+lastTable+"_0_bottom")
                            .attr("data-team_id",thirdPlyIdB)
                            .attr("data-team_name",thirdPlyNameB)
                            .text(thirdPlyNameB)
                        };
                      };
                      // --
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
            };

          } else {
            $("#bracketRange").css('display','block');
            $('.roundTitle').after(
              '<div class="noTeams">\
                TBA\
              </div>'
            );
          };
        });
      });

    });
  </script>
</html>

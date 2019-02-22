<?php
  session_start();
  require_once("pdo.php");

?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title>Bracket Referee | Administrator</title>
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
    <p>TESTING<p>
  </body>
  <script>
    // All of the teams w/ their id's
    $(document).ready(()=>{
      $.getJSON('json_allTeams.php',(teamList));
    });
    // All of the tournaments w/ their id's
    $(document).ready(()=>{
      $.getJSON('json_allTeams.php',(teamList));
    });
  </script>
</html>

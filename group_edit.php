<?php
  session_start();
  require_once("pdo.php");

  // Finds the page's current host
  $currentHost = $_SERVER['HTTP_HOST'];

  // Confirms that the user is logged in
  if (!isset($_SESSION['player_id'])) {
    $_SESSION['message'] = "<b style='color:red'>You must log in or create an account to edit a group.</b>";
    header('Location: index.php');
    return false;
  };

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
  };

  // Makes sure the administrator for this group is the current user
  $checkStmt = $pdo->prepare('SELECT admin_id,group_name FROM Groups WHERE group_id=:gid');
  $checkStmt->execute(array(
    ':gid'=>$_GET['group_id']
  ));
  $adminId = $checkStmt->fetch(PDO::FETCH_ASSOC);
  if ($adminId['admin_id'] != $_SESSION['player_id']) {
    $urlId = $_GET['group_id'];
    $_SESSION['message'] = "<b style='color:red'>Only the administrator can edit this group.</b>";
    header('Location: group.php?group_id='.$urlId);
    return false;
  };

  // The current group_id
  $urlId = htmlentities($_GET['group_id']);

  // Puts together the 'invite link' to this group
  $inviteStatus = $pdo->prepare('SELECT link_key,private FROM Groups WHERE group_id=:gid');
  $inviteStatus->execute(array(
    ':gid'=>$urlId
  ));
  $linkKey = $inviteStatus->fetch(PDO::FETCH_ASSOC)['link_key'];

  // Chooses the inviteLinks based on whether on the local host or not
  if ($currentHost == 'localhost:8888') {
    $privateLink = "http://localhost:8888/bracket-referee/group.php?group_id=".$urlId."&invite=true&link_key=".$linkKey;
    $publicLink = "http://localhost:8888/bracket-referee/group.php?group_id=".$urlId."&invite=true";
  } else {
    $privateLink = "https://bracket-referee.herokuapp.com/group.php?group_id=".$urlId."&invite=true&link_key=".$linkKey;
    $publicLink = "https://bracket-referee.herokuapp.com/group.php?group_id=".$urlId."&invite=true";
  };

  $inviteStatus->execute(array(
    ':gid'=>$urlId
  ));
  $private = $inviteStatus->fetch(PDO::FETCH_ASSOC)['private'];
  if ($private == 1) {
    $inviteLink = $privateLink;
  } else {
    $inviteLink = $publicLink;
  };

  if (isset($_POST['submitEdit'])) {
    if ($adminId['group_name'] == $_POST['new_name'] && $private == $_POST['is_private']) {
      header('Location: group_edit.php?group_id='.$urlId);
    } else {
      if (strlen($_POST['new_name']) > 0) {
        $privateInt = intval($_POST['is_private']);
        $editStmt = $pdo->prepare('UPDATE Groups SET group_name=:nw, private=:pv WHERE group_id=:id');
        $editStmt->execute(array(
          ':id'=>$urlId,
          ':nw'=>htmlentities($_POST['new_name']),
          ':pv'=>$privateInt
        ));
        $_SESSION['message'] = "<b style='color:green'>Change completed</b>";
        header('Location: group.php?group_id='.$urlId);
        return true;
      } else {
        $_SESSION['message'] = "<b style='color:red'>Group must have a name</b>";
        header('Location: group_edit.php?group_id='.$urlId);
        return true;
      };
    };
  };

  // Delete this group and it's linking table in the Groups_Players.php file
  if (isset($_POST['submitDelete'])) {

    // This deletes the brackets and picks
    $findBrackets = $pdo->prepare('SELECT bracket_id FROM Brackets WHERE group_id=:gid');
    $findBrackets->execute(array(
      ':gid'=>$urlId
    ));
    while ($oneBracket = $findBrackets->fetch(PDO::FETCH_ASSOC)) {
      $findPicks = $pdo->prepare('SELECT pick_id FROM Picks WHERE bracket_id=:bid');
      $findPicks->execute(array(
        ':bid'=>$oneBracket['bracket_id']
      ));
      while ($onePick = $findPicks->fetch(PDO::FETCH_ASSOC)) {
        $delPick = $pdo->prepare('DELETE FROM Picks WHERE pick_id=:pid');
        $delPick->execute(array(
          ':pid'=>$onePick['pick_id']
        ));
      };
      $delBracket = $pdo->prepare('DELETE FROM Brackets WHERE bracket_id=:bdid');
      $delBracket->execute(array(
        ':bdid'=>$oneBracket['bracket_id']
      ));
    };

    // This deletes the links between the players and groups in the Groups_Players table
    $findLinks = $pdo->prepare('SELECT main_id FROM Groups_Players WHERE group_id=:grid');
    $findLinks->execute(array(
      ':grid'=>$urlId
    ));
    while ($oneLink = $findLinks->fetch(PDO::FETCH_ASSOC)) {
      $delLink = $pdo->prepare('DELETE FROM Groups_Players WHERE main_id=:mid');
      $delLink->execute(array(
        'mid'=>$oneLink['main_id']
      ));
    };

    // This is where the actual groups data is deleted in the Groups table
    $deleteStmt = $pdo->prepare('DELETE FROM Groups WHERE group_id=:id');
    $deleteStmt->execute(array(
      ':id'=>$urlId
    ));

    $_SESSION['message'] = "<b style='color:blue'>Group deleted</b>";
    header('Location: player.php');
    return true;
  };

  // Allows user to return to group.php
  if (isset($_POST['cancelEdit'])) {
    $urlId = $_GET['group_id'];
    header('Location: group.php?group_id='.$urlId);
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
    <title>Change Group | Bracket Referee</title>
    <link rel="stylesheet" type="text/css" href="style/output.css"/>
    <link rel="icon" type="image/x-icon" href="style/img/index/bracket_favicon.ico"/>
    <script
    src="https://code.jquery.com/jquery-3.3.1.min.js"
    integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="
    crossorigin="anonymous"></script>
    <script src="main.js"></script>
  </head>
  <body>
    <div id="groupEditPage">
      <div id="mainEditTitle">Change Your Group?</div>
      <?php
        if (isset($_SESSION['message'])) {
          echo($_SESSION['message']);
          unset($_SESSION['message']);
        };
      ?>
      <p>
        As the director, you can change this group by simply inserting a new values in the below boxes. Then scroll to the bottom, where you can...
      </p>
      <ul>
        <li>
          complete your changes by clicking on 'ENTER'
        </li>
        <li>
          ignore your changes and return to the previous page by clicking 'CANCEL'
        </li>
      </ul>
      <form method="POST">
        <div id="changeBox">
          <table>
            <tr>
              <th>Group Name</th>
              <td>
                <input type="text" name="new_name" value="<?php echo($adminId['group_name']) ?>"/>
              </td>
            </tr>
            <tr>
              <th>Invite Status</th>
              <td>
                <?php
                  if ($private == 1) {
                    $currStatus = "PRIVATE";
                    $currValue = 1;
                    $otherStatus = "PUBLIC";
                    $otherValue = 0;
                  } else {
                    $currStatus = "PUBLIC";
                    $currValue = 0;
                    $otherStatus = "PRIVATE";
                    $otherValue = 1;
                  };
                ?>
                <select name="is_private">
                  <option value=<?php echo($currValue) ?>><?php echo($currStatus) ?></option>
                  <option value=<?php echo($otherValue) ?>><?php echo($otherStatus) ?></option>
                </select>
              </td>
            </tr>
          </table>
          <div id="changeBttnRow">
            <input id="submitEnter" type="submit" name="submitEdit" value="ENTER" />
            <input type="submit" name="cancelEdit" value="CANCEL" />
          </div>
          <table>
            <tr>
              <th>
                Invite Link
              </th>
            </tr>
            <tr>
              <td id="inviteLinkBox">
                <?php echo($inviteLink) ?>
              </td>
            </tr>
          </table>
          <div id="inviteTitle">Group Q & A</div>
          <div id="inviteBox">
            <div class="oneQA">
              <div class="inviteQ">
                What does the Invite Link do?
              </div>
              <div class="inviteA">
                You can use the 'Invite Link' by sending it to others as a quick, easy way for them to join your group.
              </div>
            </div>
            <div class="oneQA">
              <div class="inviteQ">
                What does the 'Invite Status' mean?
              </div>
              <div class="inviteA">
                'Invite Status' indicates how it is difficult for a random player to join your group.
              </div>
            </div>
            <div class="oneQA" style="width:98%">
              <div class="inviteQ">
                Invite Status: 'PRIVATE' vs. 'PUBLIC'
              </div>
              <div class="inviteA">
                <u>PRIVATE:</u>
                <ul>
                  <li>The <i>Invite Link</i> will include a unique password, making it difficult for a player to guess themselves into the group</li>
                  <li>The group <u>CANNOT</u> be found with the <i>Search For A Group</i> button on the "BRACKET HQ" page</li>
                  <li>The group will <u>NOT</u> be display on the <i>Available Groups</i> board on the "BRACKET HQ" page</li>
                </ul>
                <u>PUBLIC:</u>
                <ul>
                  <li>'Invite Link' uses a simpler link that is more easily used</li>
                  <li>The group <u>CAN</u> be found with the <i>Search For A Group</i> button on the "BRACKET HQ" page</li>
                  <li>The group <u>WILL</u> be display on the <i>Available Groups</i> board on the "BRACKET HQ" page</li></li>
                </ul>
              </div>
            </div>
            <div class="oneQA">
              <div class="inviteQ">
                Can I block or remove specific players from my group?
              </div>
              <div class="inviteA">
                No. To prevent claims of cheating or bias, a group director CANNOT remove/change any player or their bracket.
              </div>
            </div>
            <div class="oneQA">
              <div class="inviteQ">
                Who can share the Invite Link?
              </div>
              <div class="inviteA">
                Under the 'PRIVATE' setting, the director is the only player that is shown the Invite Link. With the 'PUBLIC' setting, every member can see it.
                </br>
                <span style='font-weight:bold'>IMPORTANT:</span> The link CAN be share by anyone recieves it, even if your group is PRIVATE. If unexpected members join your private group, it is because your link has probably been forwarded to other by one of your initial recipients.
              </div>
            </div>
          </div>
        </div>
        <div id="delGrpButton">Delete this Group?</div>
        <div id="delGrpBox">
          <div id="warningGrp">WARNING</div>
          <p>
            Are you sure you want to delete this group? All of the results and brackets will be permanently deleted!
          </p>
          <div id="warningRow">
            <input type="submit" name="submitDelete" value="DELETE" />
            <span id="cancelDelGrp">CANCEL</span>
          </div>
        </div>
      </form>

    </div>
  </body>
</html>

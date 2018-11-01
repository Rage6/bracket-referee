$(()=>{
  console.log("new test successful");

  $('#logForm').hide();
  $('#signForm').hide();
  // Toggles the "Login" box
  $('#logButton').click(()=>{
    $('#logForm').toggle();
  });
  // Toggles the "Login" box
  $('#signButton').click(()=>{
    $('#signForm').toggle();
  });

  // Toggle the box for changing the password with title
  $('#changePs').hide();
  $('#change-Pw-Btn').click(()=>{
    $('#changePs').toggle();
  });
  // Close the box for changing the password with 'CANCEL' button
  $('#change-Pw-cancel').click(()=>{
    $('#changePs').hide();
  });

  // Toggle the box for adding a new group
  $("#addGroupBox").hide();
  $('#showAddBox').click(()=>{
    $('#deleteBox').hide();
    $("#addGroupBox").toggle();
  });
  $('#cancelGroup').click(()=>{
    $("#addGroupBox").hide();
  });

  // Toggle the box for deleting account in player.php
  $('#deleteBox').hide();
  $('#showDeleteBox').click(()=>{
    $("#addGroupBox").hide();
    $('#deleteBox').toggle();
  });
  // Close the account deletion account
  $('#cancelDelete').click(()=>{
    $('#deleteBox').hide();
  });

  // Toggle the box for deleting groups in player_edit.php
  $("#delGrpBox").hide();
  $("#delGrpButton").click(()=>{
    $("#delGrpBox").toggle();
  });
  // Cancel this and close that box
  $("#cancelDelGrp").click(()=>{
    $("#delGrpBox").hide();
  });

  // Toggles the box for leaving a group
  $("#leaveGrpBox").hide();
  $("#leaveGrpButton").click(()=>{
    $("#leaveGrpBox").toggle();
  });
  // Cancel this and close that box
  $("#cancelLeave").click(()=>{
    $("#leaveGrpBox").hide();
  });

  // Toggle the box to cancel a new bracket and return to the group page
  $("#leaveBracketBox").hide();
  $("#leaveBracketButton").click(()=>{
    $("#leaveBracketBox").toggle();
  });
  // Remove the box and go back to the bracket page


})

$(()=>{
  console.log("main.js is active again...");

  $('#logForm').hide();
  $('#signForm').hide();
  // Toggles the "Login" box
  $('#logButton').click(()=>{
    $('#logForm').toggle();
    $('#signForm').hide();
  });
  // Toggles the "Login" box
  $('#signButton').click(()=>{
    $('#signForm').toggle();
    $('#logForm').hide();
  });

  // Toggle the box for changing the password with title
  $('#changePs').hide();
  $('#change-Pw-Btn').click(()=>{
    $('#changePs').toggle();
    $('#deleteBox').hide();
  });
  // Close the box for changing the password with 'CANCEL' button
  $('#change-Pw-cancel').click(()=>{
    $('#changePs').hide();
  });

  //Toggle the box for using the 'SEARCH' option
  var findPoint = "up";
  if ($("#searchResults").css('display') != 'block') {
    $("#findGroupBox").hide();
  } else {
    $("#findGrpVimg").css('transform','scaleY(-1)');
    var findPoint = "down";
  };
  $("#findGroup").click(()=>{
    $("#findGroupBox").toggle();
    if (findPoint == "up") {
      $("#findGrpVimg").css('transform','scaleY(-1)');
      $("#searchResults").css('display','block');
      findPoint = "down";
    } else {
      $("#findGrpVimg").css('transform','scaleY(1)');
      $("#searchResults").css('display','none');
      findPoint = "up";
    };
  });

  // Toggle the box for adding a new group
  var addPoint = "up";
  $("#addGroupBox").hide();
  $('#showAddBox').click(()=>{
    $('#deleteBox').hide();
    $("#addGroupBox").toggle();
    if (addPoint == "up") {
      $("#showAddVimg").css('transform','scaleY(-1)');
      addPoint = "down";
    } else {
      $("#showAddVimg").css('transform','scaleY(1)');
      addPoint = "up";
    };
  });
  $('#cancelGroup').click(()=>{
    $("#addGroupBox").hide();
  });

  // Toggle the box for deleting account in player.php
  $('#deleteBox').hide();
  $('#showDeleteBox').click(()=>{
    $('#changePs').hide();
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
  $("#leaveBrktBox").hide();
  $("#leaveBrktButton").click(()=>{
    $("#leaveBrktBox").toggle();
  });
  // Remove the cancelation box
  $("#hideBrktBttn").click(()=>{
    $("#leaveBrktBox").hide();
  });

  // Toggle the box to deleting an existing bracket in bracket_view page
  $("#delBox").hide();
  $("#showDelBox").click(()=>{
    $("#delBox").toggle();
  });
  // Remove the cancelation box
  $("#hideDelBox").click(()=>{
    $("#delBox").hide();
  });

})

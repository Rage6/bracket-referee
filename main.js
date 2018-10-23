$(()=>{
  console.log("test successful");

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

  // findGroup
  $("#findGroupBox").hide();
  $('#findGroup').click(()=>{
    $("#addGroupBox").hide();
    $('#deleteBox').hide();
    $("#findGroupBox").toggle();
  });

  // Toggle the box for adding a new group
  $("#addGroupBox").hide();
  $('#showAddBox').click(()=>{
    $("#findGroupBox").hide();
    $('#deleteBox').hide();
    $("#addGroupBox").toggle();
  });
  $('#cancelGroup').click(()=>{
    $("#addGroupBox").hide();
  });

  // Toggle the box for deleting account in player.php
  $('#deleteBox').hide();
  $('#showDeleteBox').click(()=>{
    $("#findGroupBox").hide();
    $("#addGroupBox").hide();
    $('#deleteBox').toggle();
  });
  // Close the account deletion account
  $('#cancelDelete').click(()=>{
    $('#deleteBox').hide();
  });
})

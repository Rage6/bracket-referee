$(()=>{
  console.log("old test successful");

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
})

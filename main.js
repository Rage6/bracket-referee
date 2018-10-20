$(()=>{
  console.log("test successful");
  // Starting variables


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

  $('#cancelGroup').click(()=>{
    console.log("Make this toggle later");
  });

  // Toggle the box for deleting account in player.php
  $('#deleteBox').hide();
  $('#showDeleteBox').click(()=>{
    $('#deleteBox').toggle();
  });
  // Close the account deletion account
  $('#cancelDelete').click(()=>{
    $('#deleteBox').hide();
  });
})

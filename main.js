$(()=>{

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
})

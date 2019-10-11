$(()=>{
  console.log("main.js check...");

  // Measures the background box and adjusts the word lines accordingly
  var titleHeight = $('#indexWords').height();
  $('#indexTitle').css('height',titleHeight);

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

  // Toggle the 'Reset' box
  $("#forgotBttn").click(()=>{
    if ($("#forgotBox").css('display') == "block") {
      $("#forgotBox").css('display','none');
    } else {
      $("#forgotBox").css('display','block');
    };
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

  // Finds the correct path names based on whether it is in a local or public host
  var currentHost = window.location.host;
  var currentPlayerPath = null;
  var currentViewPath = null;
  var currentGroupPath = null;
  if (currentHost == 'localhost:8888') {
    currentPlayerPath = "/bracket-referee/player.php";
    currentViewPath = "/bracket-referee/bracket_view.php";
    currentGroupPath = "/bracket-referee/group.php";
  } else {
    currentPlayerPath = "/player.php";
    currentViewPath = "/bracket_view.php";
    currentGroupPath = "/group.php";
  };

  // Turns on the hide/show for the "Join A Group" options if the width is less than 1366px
  if (window.location.pathname == currentPlayerPath) {
    if (window.innerWidth < 1366) {
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
        $("#showAddVimg").css('transform','scaleY(1)');
        addPoint = "up";
      });
    } else {
      $("#allNewOpts").css('display','flex').css('justify-content','space-between');
    };
  };

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

  // Takes the final total at the bottom of bracket_view.php and displays it at the top;
  if (window.location.pathname == currentViewPath) {
    var currentScore = $("#bottomPoints").text();
    $("#currentScore").text(currentScore);
  };

  // This is 1) how only the first rounds is shown on a mobile device, and 2) how the user can click to go to the next one
  if (window.location.pathname == currentGroupPath) {
    var lastRound = $(".allRounds").length;
    console.log(lastRound);
    var initLayerNum = null;
    if ($("#layer_0").attr("data-check") == "true") {
      initLayerNum = 0;
      lastRound--;
    } else {
      initLayerNum = 1;
    };
    var initLayerId = "layer_" + initLayerNum;
    for (var layer = initLayerNum + 1; layer <= lastRound; layer++) {
      var layerId = "#layer_" + layer;
      console.log(layerId);
      $(layerId).css('display','none');
    };
    var currentLayer = initLayerNum;
    const checkArrows = () => {
      if (currentLayer == initLayerNum) {
        $("#scrollLeft").css('color','green');
        $("#scrollRight").css('color','white');
      } else if (currentLayer == lastRound) {
        $("#scrollRight").css('color','green');
        $("#scrollLeft").css('color','white');
      } else {
        $("#scrollLeft").css('color','white');
        $("#scrollRight").css('color','white');
      };
    };
    checkArrows();
    $("#scrollLeft").click(()=>{
      if (currentLayer != initLayerNum) {
        $("#layer_"+currentLayer).css('display','none');
        currentLayer--;
        $("#layer_"+currentLayer).css('display','block');
      };
      checkArrows();
    });
    $("#scrollRight").click(()=>{
      if (currentLayer != lastRound) {
        $("#layer_"+currentLayer).css('display','none');
        currentLayer++;
        $("#layer_"+currentLayer).css('display','block');
      };
      checkArrows();
    });
  };

  // Opens the 'Login' box and closes the 'Create' box on group_invite.php
  $("#inviteLogin").click(()=>{
    $("#inviteLogin").css('border-radius','20px 20px 0 0');
    $("#inviteCreate").css('border-radius','20px 20px 0 0');
    $("#inviteLoginBox").css('display','block');
    $("#inviteCreateBox").css('display','none');
  });

  // Opens the 'Create' box and closes the 'Login' box on group_invite.php
  $("#inviteCreate").click(()=>{
    $("#inviteCreate").css('border-radius','20px 20px 0 0');
    $("#inviteLogin").css('border-radius','20px 20px 0 0');
    $("#inviteCreateBox").css('display','block');
    $("#inviteLoginBox").css('display','none');
  });

  // For copying the 'invite link' by clicking the "COPY" button on group.php
  // Answer found at: https://brianscode.com/jquery-copy-span-contents-clipboard-example/#comment-755
  	$("#clickLink").click(() => {
  		var chooseLinkDiv = document.getElementById("copyLink");
  		var range = document.createRange();
  		range.selectNodeContents(chooseLinkDiv);
  		var chooseSelection = window.getSelection();
  		chooseSelection.removeAllRanges();
  		chooseSelection.addRange(range);
  		document.execCommand('copy');
      $("#copyLink").css('background-color','lightgreen')
  		return true;
  	});

  // Generic function that makes the screen slide down when a form appears after clicking
  const toNewForm = (targetElmt) => {
    var offSetValue = $(targetElmt).offset().top;
    $('html,body').animate({
      scrollTop:offSetValue
    },500);
  };

  // Slides to "login form" on index.php
  $("#logButton").click(()=>{
    toNewForm("#logButton");
  });

  // Slides to "sign in form" on index.php
  $("#signButton").click(()=>{
    toNewForm("#signButton");
  });

  // Slides to "Delete A Group form" on group_edit.php
  $("#delGrpButton").click(()=>{
    toNewForm("#delGrpButton");
  });

  // Slides to "Delete a Bracket form" on bracket_view.php
  $("#showDelBox").click(()=>{
    toNewForm("#showDelBox");
  });

  // Slides to "Change Passwords form" on player_edit.php
  $("#change-Pw-Btn").click(()=>{
    toNewForm("#change-Pw-Btn");
  });

  // Slides to "Delete Your Account form" on player_edit.php
  $("#showDeleteBox").click(()=>{
    toNewForm("#showDeleteBox");
  });

  // Slides to "Delete Your Account form" on player_edit.php
  $("#leaveGrpButton").click(()=>{
    toNewForm("#leaveGrpBox");
  });

  // Slide to 'login' form on group_invite.php
  $("#inviteLogin").click(()=>{
    toNewForm("#inviteLogin");
  });

  // Slide to 'create' form on group_invite.php
  $("#inviteCreate").click(()=>{
    toNewForm("#inviteCreate");
  });

  // Opens and closes the box where you can find team names and ids in admin.php
  $("#findTeamBox").hide();
  $("#makeTeamBox").hide();
  $("#findTeamBttn").click(()=>{
    $("#findTeamBox").toggle();
    $("#makeTeamBox").hide();
  });


  $("#makeTeamBttn").click(()=>{
    $("#makeTeamBox").toggle();
    $("#findTeamBox").hide();
  });

  // Opens the 'EDIT' for a message
  $('[data-edit][data-num]').click(()=>{
    var isEditBttn = event.target.dataset.edit;
    var msgBoxId = "#oneMsgBox_" + event.target.dataset.num;
    var editBoxId = "#oneMsgEditBox_" + event.target.dataset.num;
    if (isEditBttn == "false") {
      $(msgBoxId).css('display','none');
      $(editBoxId).css('display','block');
    } else {
      $(msgBoxId).css('display','block');
      $(editBoxId).css('display','none');
    };
  });

});

$(()=>{

  console.log("testing");
  $(document).ready(()=>{
    $.getJSON('json_tournament.php',(data)=>{
      console.log(data);
    });
  });

});

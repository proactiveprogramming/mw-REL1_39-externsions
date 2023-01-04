$(document).ready(function() {
   
 	
	$("#p-navi").hide();

 var a = $("<div>&nbsp;</div>").addClass("navi-toggle");
 $('#p-idcard').before(a);


   $(".navi-toggle").click(function(){

      if ($("#p-navi").is(":hidden")) {
        $("#p-navi").slideDown("fast");
	$(this).addClass("active");
         $.cookie('showTop', 'collapsed');
	return false;
	
      } else {
        $("#p-navi").slideUp("fast");
	$(this).removeClass("active");
        $.cookie('showTop', 'expanded');
	return false;

      }
	
   });

// COOKIES
    // Header State
    var showTop = $.cookie('showTop');

    // Set the user's selection for the Header State
    if (showTop == 'collapsed') {
	$("#p-navi").show();
	$(".navi-toggle").addClass("active");
	
    };
 
});

// Credits: Matt Rossi - ifohdesigns.com    
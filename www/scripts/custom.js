var connectionFlag = 0;

var logoutFlag = 0;

$(document).ready(function(){

	$(".afterClr ").after("<div class='clr'></div>");

	$(".beforeClr ").before("<div class='clr'></div>");

	$('.appendClr, .viewForm li, .connListSec .connList').append('<div class="clr"></div>');	



	//add common css properties on global elements

	$('input[type="radio"]').css({'border' : '0', 'width' : 'auto !important'});

	$('input[type="checkbox"]').css({'border' : '0', 'width' : 'auto !important'});

	$('input[type="submit"]').css({'cursor' : 'pointer', 'overflow' : 'visible', 'border' :'0'})

	$('input[type="button"]').css({'cursor' : 'pointer', 'overflow' : 'visible', 'border' :'0'})



//	$("#regForm").before("<div class='bgTop'></div>");

//	$("#regForm").after("<div class='bgBott'></div>");





//function header: my connections

	$("#ddMyConn").click(function (e){

	  e.stopPropagation();

      if(connectionFlag!=1){

	          hideShowBar("icnMyConn","ddMyConnList","show");

	          connectionFlag = 1;

          } else {

              hideShowBar("icnMyConn","ddMyConnList","hide");

              connectionFlag = 0;

          }

          // bindBodyClick();

	});

//function header: my Profile

	$("#myProfLink").click(function (e){

	     e.stopPropagation();

	     if(logoutFlag!=1){

	           hideShowBar("arwDwn","ddMyProf","show");

    	       logoutFlag = 1;

         } else {

               hideShowBar("arwDwn","ddMyProf","hide");

               logoutFlag = 0;

         }

    });

    

// function to avoid parent event on connection div click    

    $("#ddMyConnList").click(function (e){

        e.stopPropagation();

    });

// function for equal columns hieght

	setHeight('.eqlCH');

	

	$("#midColLayout").resize(function (){

		//alert('s');

					 

	});

    

    

    $("body").click(function(){

       if(connectionFlag == 1){

           hideShowBar("icnMyConn","ddMyConnList","hide");

           connectionFlag = 0;

       }

       if(logoutFlag == 1){

         hideShowBar("arwDwn","ddMyProf","hide");

         logoutFlag = 0;

       }

   })

   

    

	var gMapH= $('.gMapSec').height();

	//	alert(gMapH);

	var crM= maxHeight-gMapH;

	//alert(crM);

	//$('.footer').css('marginTop',crM-22);
    $('.footer').css('margin-top:15px;');
	

	$('.viewForm li:even, .connListSec .connList:even').addClass('bgEven');

	$('.viewForm li:odd,  .connListSec .connList:odd').addClass('bgOdd');

});

	



//global variable, this will store the highest height value

var maxHeight = 0;



function setHeight(col) {

	//Get all the element with class = col

	col = $(col);

	

	//Loop all the col

    col.each(function() {        

		//Store the highest value

		if($(this).height() > maxHeight) {

            maxHeight = $(this).height();

        }

    });

	

	col.each(function() {

		$(this).css('min-height', maxHeight);

		

	});

}









function hideShowBar(imageId,divId,action){

    if(action == 'hide'){

       $("#"+imageId).removeClass('on');

      // $("#"+imageId).addClass('off');

	   $("#"+divId).hide();

    } else {

       //$("#"+imageId).removeClass('off');

       $("#"+imageId).addClass('on');

	   $("#"+divId).show();

    }

}

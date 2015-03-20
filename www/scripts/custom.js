$(document).ready(function(){

	$(".afterClr ").after("<div class='clr'></div>");

	$(".beforeClr ").before("<div class='clr'></div>");

	$('.appendClr, .viewForm li, .connListSec .connList').append('<div class="clr"></div>');	



	//add common css properties on global elements

	$('input[type="radio"]').css({'border' : '0', 'width' : 'auto !important'});

	$('input[type="checkbox"]').css({'border' : '0', 'width' : 'auto !important'});

	$('input[type="submit"]').css({'cursor' : 'pointer', 'overflow' : 'visible', 'border' :'0'})

	$('input[type="button"]').css({'cursor' : 'pointer', 'overflow' : 'visible', 'border' :'0'})

// function for equal columns hieght

	setHeight('.eqlCH');

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

function LightBox1(id,width) {

    var popID = id; //$(this).attr('rel'); //Get Popup Name

    //var popURL = $(this).attr('href'); //Get Popup href to define size

    //Pull Query & Variables from href URL
    //var query= popURL.split('?');
    //var dim= query[1].split('&');
    var popWidth = width; //dim[0].split('=')[1]; //Gets the first query string value
    /// <reference path="Images/close.png" />

    //Fade in the Popup and add close button
    $('#' + popID).fadeIn().css({ 'width': Number(popWidth) }).prepend('<a href="javascript:void(0);" class="close"><img src='+baseUrlGlobe+'"/www/default/images/close.png" class="btn_close" title="Close Window" alt="Close" border="0" /></a>');

    //Define margin for center alignment (vertical   horizontal) - we add 80px to the height/width to accomodate for the padding  and border width defined in the css
    var popMargTop = ($('#' + popID).height()) / 2;
    var popMargLeft = ($('#' + popID).width()) / 2;

    //Apply Margin to Popup
    $('#' + popID).css({
        'margin-top': -popMargTop,       
		'margin-left': -popMargLeft
    });

    //Fade in Background
    $('body').append('<div id="fade"></div>'); //Add the fade layer to bottom of the body tag.
    $('#fade').css({ 'filter': 'alpha(opacity=80)' }).fadeIn(); //Fade in the fade layer - .css({'filter' : 'alpha(opacity=80)'}) is used to fix the IE Bug on fading transparencies 

    return false;
}


function LightBox(id) {
    
    var popID = "popup_name"; //$(this).attr('rel'); //Get Popup Name
    
    //var popURL = $(this).attr('href'); //Get Popup href to define size

    //Pull Query & Variables from href URL
    //var query= popURL.split('?');
    //var dim= query[1].split('&');
    var popWidth = 800; //dim[0].split('=')[1]; //Gets the first query string value
    /// <reference path="Images/close.png" />

    //Fade in the Popup and add close button
    $('#' + popID).fadeIn().css({ 'width': Number(popWidth) }).prepend('<a href="javascript:void(0);" class="close"><img src="'+baseUrlGlobe+'/Images/close.png" class="btn_close" title="Close Window" alt="Close" border="0" /></a>');

    //Define margin for center alignment (vertical   horizontal) - we add 80px to the height/width to accomodate for the padding  and border width defined in the css
    var popMargTop = ($('#' + popID).height() + 80) / 2;
    var popMargLeft = ($('#' + popID).width() + 80) / 2;

    //Apply Margin to Popup
    $('#' + popID).css({
        'margin-top': -popMargTop,
        'margin-left': -popMargLeft
    });

    //Fade in Background
    $('body').append('<div id="fade"></div>'); //Add the fade layer to bottom of the body tag.
    $('#fade').css({ 'filter': 'alpha(opacity=80)' }).fadeIn(); //Fade in the fade layer - .css({'filter' : 'alpha(opacity=80)'}) is used to fix the IE Bug on fading transparencies 

    return false;
}
$(document).ready(function () {
    //Close Popups and Fade Layer
    $('a.close, #fade').live('click', function () { //When clicking on the close or fade layer...
        $('#fade , .popup_block').fadeOut(function () {
            $('#fade, a.close').remove();  //fade them both out
        });
        return false;
    });

    //Code goes here
});

function HideLightBox() {
    //Close Popups and Fade Layer
    $('a.close, #fade').live('click', function () { //When clicking on the close or fade layer...
        $('#fade , .popup_block').fadeOut(function () {
            $('#fade, a.close').remove();  //fade them both out
        });
        return false;
    });
    
    //Code goes here
}
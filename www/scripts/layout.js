$(function(){
    var width = screen.width,
    height = screen.height;
    setInterval(function () {
        if (screen.width !== width || screen.height !== height) {
            width = screen.width;
            height = screen.height;
            $(window).trigger('resolutionchange');
        }
    }, 5);
});

function goLoc(url) {

    window.location.href = baseUrl+url;

}

function searchPlaceBy(){

    var str = $("#searchPlace").attr('value');

    getPlaceByAddress(str,'search'); 

}



function showNewsScreen() {

    $("#loading").width($("#newsData").width());

    $('#loading').show();

}

function showLoadingScreen() {
    $('#loading').show();
    $("#loading").width($("#newsData").width());

}



function hideNewsScreen() {

    $('#loading').hide();

}



function closePopup(){

    $('#cboxClose').trigger('click');

    $('#useAddress').attr('disabled','disabled');

    $('#useAddress').removeClass();

}

$(document).ready(function() { 
    
    $(".Message-Popup-Class").colorbox({
        width:"40%",
        height:"45%", 
        inline:true, 
        href:"#Message-Popup"
    },function(){
        $('html, body').animate({
            scrollTop: 0
        }, 0);
    });

    $(".Thanks-Popup-Class").colorbox({
        width:"40%",
        height:"20%", 
        inline:true, 
        href:"#Thanks-Popup"
    },function(){
        $('html, body').animate({
            scrollTop: 0
        }, 0);
    });

    $('#imageUploader').click(function(event) {

        $('#uploadImage').click();

    });	

});

function closeThisPopup() {

    $('#cboxClose').trigger('click');

} 

var preurl ="http://graph.facebook.com/";

var posturl ="/picture?type=square";

var picture = "";

function message() {

    var message = $('#message').val();

    var subject = $('#subject').val();
    
   // alert(reciever_userid);
    
    $.ajax({

        type: "POST",

        url: baseUrl+"message/send",

        data: {
            'subject':subject, 
            'message':message, 
            'user_id':reciever_userid  // declare in layout.html
        },

        success: function (msg) { 

            msg = JSON.parse(msg);

            if(msg && msg.errors) {

                if(msg && msg.errors['subject'] != "") {

                    $('#subject_error').html(msg.errors['subject']);

                }

                if(msg && msg.errors['message'] != "") {

                    $('#message_error').html(msg.errors['message']);

                }

            }else if(msg && msg.success) {

                $('#message').val('');

                $('#subject').val('');

                $("#thanksButton").trigger('click');                

            }			

        }     

    });

}

function removeError(that) {

    $('#'+that.id+'_error').html('');

}



var emailError = 0;

var emailVal;

function emailValidation() {

    $('#email_error').html('<img src="'+baseUrl+'public/www/images/waiting.gif"/>');

    var emailReg = /^([\w-\.]+@([\w-]+\.)+[\w-]{2,4})?$/;

    var email = $.trim($('#emailField').val());

    if(email == '') {

        $('#email_error').html('Enter the email id');

        emailVal = emailError = 1;

    } else if(!emailReg.test(email)) {

        $('#email_error').html('Enter the valid email id');

        emailVal = emailError = 2;

    } else {

        $.post(baseUrl+'index/email-check/', {
            'email': email
        }, returnEmailVal, "text");

    }

    function returnEmailVal(obj) {

        var json = JSON.parse(obj);

        if(json.errors) {

            $('#email_error').html('Email id already exist');

            emailVal = emailError = 3;

        }else {

            $('#email_error').html('');

            emailVal = emailError = 0;

        }

    }

}

var emailval = 0;

function emailLoginValidation() {

    emailval = 0;

    var emailReg = /^([\w-\.]+@([\w-]+\.)+[\w-]{2,4})?$/;

    var email = $.trim($('#emailLogin').val());

    if(email == '') {

        return emailval = 1;

    } else if(!emailReg.test(email)) {

        return emailval = 2;

    } else {

        return emailval = 0;

    }

}



function loginValidation(){

    clearError();

    var emailValid = emailLoginValidation();

    var email = $.trim($('#emailLogin').val());

    var pass  = $.trim($('#passwordLogin').val());

    if($('#remember').attr('checked')) {

        var remember = $('#remember').attr('checked'); 

    } else {

        var remember = ""; 

    } 

    if(email != '' && pass != ''){

        $(document).ready(function(){

            $.post(baseUrl+'index/login/', {
                'email': email, 
                'pass': pass, 
                'remember': remember
            }, returnLogin, "text");

        });

    } else {

        if(email == '') $('#emailLogin').css('border','1px solid red');

        else if(pass == '') $('#passwordLogin').css('border','1px solid red');

    }

}



function returnLogin(obj){

    document.body.style.cursor = 'default';

    var jsonObj = JSON.parse(obj);

    if(jsonObj.error == 0) {

        if(jsonObj.redirect.indexOf('edit-profile') > 0 && returnUrl != '') {

            window.location.href = jsonObj.redirect+'?url='+returnUrl;

			

        } else { 

            if(jsonObj.redirect.indexOf('home') > 0 && returnUrl != '') {

                window.location.href = returnUrl;

            } else {

                window.location.href = jsonObj.redirect;

            }

        }

    }else if(jsonObj.error == 1) {

        $('#loginError').html('Invalid email or password.');

    }else if(jsonObj.error == 2) {

        $('#loginError').html('Inactive user account');

    }

}



function clearError() {

    $('#loginError').html('');

    $('#name_error').html('');

    $('#email_error').html('');

    $('#pass_error').html('');

    $('#repass_error').html('');

    $('#gender_error').html('');

    $('#street_error').html('');

    $('#city_error').html('');

    $('#state_error').html('');

    $('#zip_error').html('');

    $('#country_error').html('');

    $('#user_id_error').html('');

    $('#address_error').html('');

    $('#emailLogin').css('border','1px solid #7F9DB9');

    $('#passwordLogin').css('border','1px solid #7F9DB9');

}

function fillAddress() {

    $("#cboxClose").trigger('click');

    $("#address").attr('value',$("#currentAddress").attr('value'));

}

$(document).ready(function(e){

    $('#passwordLogin , #emailLogin').bind('keypress', function(event) {

        var code = (event.keyCode ? event.keyCode : event.which);

        if(code===13){

            loginValidation();

        }

    });  
});



// code for allow and denay for new friend request  



if(isLogin) {

    $(document).ready(function() {

        notification();

        setInterval('notification()', 120000);

    });

	

    function notification() {

        $.ajax({

            url : baseUrl+'contacts/friends-notification',

            type: 'post',

            data : {},

            success: function(obj){

                obj = $.parseJSON(obj);

                if(obj && obj.total > 0) {

                    if($("#noteTotal")) {

                        $("#noteTotal").html(obj.total);

                        $("#noteTotal").show();

                    }

                } 

                if(obj && obj.totalFriends) { 

                    if($("#totalFriend")) {

                        $("#totalFriend").html(obj.totalFriends);

                    }

                } else {

                    if($("#totalFriend")) {

                        $("#totalFriend").html(0);

                    }

                }
                if(obj && obj.msgTotal > 0) {

                    if($("#msgTotal")) {

                        $("#msgTotal").html(obj.msgTotal);

                        $("#msgTotal").show();

                    }

                } else {

                    $("#msgTotal").html(obj.msgTotal);

                    $("#msgTotal").hide();

                }

            }

        });

    }
}

/*

* function that concatinate the string at given length 

* @param {string,length to concatinate}

* @reurn {concatinated string}

*/



String.prototype.breakAt= function(breakAt) {

    var len = 0;

    var newString = '';

    var tempBreakAt = breakAt;

    while (len < this.length) {

        if(breakAt == len){

            if(this.charAt(len+1)!=' '){

                newString+='-<br/>-';

            } else {

                newString+='<br/>';

            }

            newString+=this.charAt(len);

            breakAt+=Number(tempBreakAt);

          

        }else{

            newString+=this.charAt(len);

        }

        len++;

    }

    return newString;

    

};

/*   function to store voting value by user

            @created by : D

            @created date : 28/12/2012    */

            

function voting(thisone, action, elid,userid) {

    if(action && elid && userid) {

        $.ajax({

            url  : baseUrl+'home/store-voting',

            type : 'post',

            data : {
                action:action, 
                id:elid, 
                user_id:userid
            },

            success: function(data) {

                data = $.parseJSON(data);

                if(data && data.noofvotes_1){

                // $('#message_success_'+elid).attr("<strong>Hello</strong>");

                } 

                    

                if(data && data.successalready){

                    $('#thumbs_up2_'+elid).show('slow');

                    $('#thumbs_up1_'+elid).hide();

                    //$('#thumbsup_blck_'+elid).css('display:none');

                    $('#img1_'+elid).hide();

                    $('#img2_'+elid).show();

                }

                if(data && data.success) {

                    if(action =='comments') {

                    // alert('in comments part');

                    //$('#comment_'+elid).show();

                    } else if(action == 'news') {

                        $('#thumbs_up1_'+elid).show('slow');

                        $('#thumbs_up2_'+elid).hide();

                        //  $('#thumbsup_blck_'+elid).hide();

                        $('#img1_'+elid).hide();

                        $('#img2_'+elid).show();

                        var doFlag = true;

                    } else {

                    //$(thione).remove();

                    }

                }

                    

                if(data && data.noofvotes_2){

                    //alert(data.noofvotes_2);

                    $('#message_success_'+elid).html(data.noofvotes_2); 

                }  

                else if(data && data.error) {

                    alert('Sorry! we are unable to perform voting action');

                } 

            }

        });

        

    } else {

        alert('Sorry! we are unable to performe delete action');

    }

}

function mergeArray(mainArray,indexNumber){

    var tempArray = new Array();

    var counter = 0;

    for(i in mainArray){

        if(i != indexNumber){

            tempArray[counter++] =  mainArray[i];

        }

    }

    return tempArray;

    

}

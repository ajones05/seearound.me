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

function emailValidation() {

    $('#email_error').html('<img src="'+baseUrl+'public/www/images/waiting.gif"/>');

    var emailReg = /^([\w-\.]+@([\w-]+\.)+[\w-]{2,4})?$/;

    var email = $.trim($('#emailField').val());

    if(email == '') {

        $('#email_error').html('Enter the email id');

    } else if(!emailReg.test(email)) {

        $('#email_error').html('Enter the valid email id');

    } else {

        $.post(baseUrl+'index/email-check/', {
            'email': email
        }, returnEmailVal, "text");

    }

    function returnEmailVal(obj) {

        var json = JSON.parse(obj);

        if(json.errors) {

            $('#email_error').html('Email id already exist');

        }else {

            $('#email_error').html('');

        }

    }

}

function fillAddress() {

    $("#cboxClose").trigger('click');

    $("#address").attr('value',$("#currentAddress").attr('value'));

}

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

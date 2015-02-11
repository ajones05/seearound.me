$(document).ready(function(){
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

$(window).bind('resolutionchange', function(){
    console.log('Resolution ='+ screen.width+'*'+screen.height);
})

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

$(document).ready(function () {

    window.fbAsyncInit = function () {

        FB.init({
            appId: facebook_appId, 
            status: true, 
            cookie: true,

            xfbml: true

        });

    };

    (function () {

        var e = document.createElement('script');

        e.type = 'text/javascript';

        e.src = document.location.protocol +

        '//connect.facebook.net/en_US/all.js';

        e.async = true;

        document.getElementById('fb-root').appendChild(e);

    } ());

});



var preurl ="http://graph.facebook.com/";

var posturl ="/picture?type=square";

var picture = "";



function fbLogin() {

    FB.login(function(response) {

        FbCallback()

    },{
        scope: 'email,user_likes,user_birthday'
    });

}



function FbCallback() {

    FB.api('/me', function (response) {

        var nwId = response.id;

        var name = response.name;

        var email = response.email;

        var gender = response.gender;

        var dob = response.birthday;

        picture = preurl+nwId+posturl; 

        if(nwId != undefined) { 

            document.body.style.cursor = 'wait';

            $.post(baseUrl+'index/fb-login/', {
                'id':nwId,
                'name':name,
                'email': email,
                'picture':picture,
                'gender':gender,
                'dob':dob
            }, returnLogin, "text");

        }			

    });

}



function fbLogout(url) {

    if(isFbLogin){

        FB.init({
            appId: facebook_appId, 
            status: true, 
            cookie: true, 
            xfbml: true
        });

        FB.getLoginStatus(function(response) {

            if(response.authResponse) {

                FB.logout(function () {

                    window.location.href = baseUrl+url;

                });

            } else {

                window.location.href = baseUrl+url;

            }

        }); 

    } else {

        window.location.href = baseUrl+url;

    }

}

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



/* function clearErrors() {
    
	if($('#message')) {		

		$('#message').val("");

	} 

	if($('#subject')) {	

		$('#subject').val("");	

	}

	if($('#to')) {	

		$('#to').val("");	

	}

}   */



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



function checkuser_id(that){

    $('#user_id_error').html('<img src="'+baseUrl+'public/www/images/waiting.gif"/>');

    var user_id = $.trim(that);



    if(user_id != ''){

        $(document).ready(function(){

            $.post(baseUrl+'index/check-user_id/', {
                'user_id': user_id
            }, returnuser_id, "text");

        });

    } else {

        $('#user_id_error').html('');

        $('#user_id_error').html('Enter your desire user id');

    }

}



var user_idNotError = true;

function returnuser_id(obj){

    var json = JSON.parse(obj);

    $('#user_id_error').html('');

    //$('#user_id_error').html(json.result);

    if(json.error == 0){

        user_idNotError = true;

        $('#user_id_error').css('color','#0066FF');

        $('#user_id_error').html('User id available');

    } else if(json.error == 1){

        user_idNotError = false;

        $('#user_id_error').css('color','#FF0000');

        $('#user_id_error').html('Choose another user id');

    } else if(json.error == 2){

        user_idNotError = false;

        $('#user_id_error').css('color','#FF0000');

        $('#user_id_error').html('Enter the user id');

    }

}



function captchaValidation() {

    var captcha_id = $("#captcha-id").attr('value');

    var captcha_input = $("#captcha-input").attr('value');

    var validateCaptcha;



    $.ajax({

        type: "POST",

        url: baseUrl+"captcha/check-captcha",

        data: {

            id: captcha_id ,

            input: captcha_input

        },

        success: function (msg) { 

            msg = JSON.parse(msg);

            if(msg.html) {			

                document.getElementById('captcha-div').innerHTML = msg.html ;

            }

            if (msg.valid == 'true') {

                document.getElementById('captcha_error').style.display = "none";

                registrationProcess(1);

            } else if (msg.valid == 'false') {

                validateCaptcha = 2;

                document.getElementById('captcha_error').style.display = "block"

                document.getElementById('captcha_error').innerHTML = "<span style='color:#FF0000'>Incorrect captcha value</span>";

                registrationProcess(2);

            }   

        }     

    });

}



function registrationValidation() {

    captchaValidation();

}



function registrationProcess(captchavalidate) {

    clearError();

    emailValidation();

    var name = $.trim($('#nameField').val());

    var email = $.trim($('#emailField').val());

    var pass = $.trim($('#passField').val());

    var repass = $.trim($('#repassField').val());

    var state = $.trim($('#stateField').val());

    var user_id = $.trim($('#user_idField').val());

    var countryName = $('#countryField option:selected').text();

    var address =  $.trim($('#address').val());



    if(name != '' && emailVal == 0 && pass != '' && repass != '' && address != '' && user_idNotError && captchavalidate == 1) {

        if(pass == repass ) {

            $(document).ready(function(){

                $.post(baseUrl+'here-spy-index/registration/', 

                {
                        'name': name, 
                        'email': email, 
                        'pass': pass, 
                        'countryName': countryName, 
                        'state': state, 
                        'address':address,
                        'latt': userLatitude, 
                        'long': userLongitude,
                        'user_id':user_id
                    },

                    returnRegistration, "text");

            });

        } else {

            $('#repass_error').html('Both password not matched.');

        }

    } else {

        if(pass == '') $('#pass_error').html('Enter the password');

        if(repass == '') $('#repass_error').html('Enter the repassword');

        if(name == '') $('#name_error').html('Enter your name');

        //if(state == '') $('#state_error').html('Select your state');

        if(address == '') $('#address_error').html('Enter your address');

    }

}



function returnRegistration(obj) { 

    var json = JSON.parse(obj);

    if(json && json.success) {

        window.location.href = json.redirect;

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



function selectedState(thisOne) {

    $('#useAddress').attr('disabled','disabled');

    var str = "";

    $("#"+thisOne.id+" option:selected").each(function () {

        str += $(this).text() + " ";

    });



    if(thisOne.value) {

        getPlaceByAddress(str+",USA",'centerpoint'); 

        $("#address").attr('value','');

        $("#addressField").show();

    } else {

        $("#address").attr('value','');

        $("#addressField").hide();

    }

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

    $('#captcha-input,#repassField,#passField,#emailField,#nameField').bind('keypress', function(event) {

        var code = (event.keyCode ? event.keyCode : event.which);

        if(code===13){

            registrationValidation();

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

	

    function showFriendRequest(thisone) {

        var html = '<p class="bgTop"></p>'+

        '<img style=" margin: 7px 0 7px 112px;" src="'+baseUrl+'www/images/wait.gif" /><br>'+

        '<p class="pendReq2"></p>';

        $("#ddMyConnList").html(html);

        $.ajax({

            url : baseUrl+'contacts/requests',

            type : "post",

            data :{},

            success : function(obj) {

                if(obj) {

                    obj = $.parseJSON(obj); 

                    if(obj.total > 0) { 

                        html = '';

                        html += '<p class="bgTop"></p>';

                        currentRequests = 0;

                        for(var x in obj.data) { 

                            currentRequests++;

                            var imgsrc = baseUrl+'www/images/img-prof40x40.jpg';

                            if((obj.data[x]).Profile_image) {

                                if((obj.data[x]).Profile_image != 'null' || (obj.data[x]).Profile_image != '') { 

                                    if(((obj.data[x]).Profile_image).indexOf('://') > 0) {

                                        imgsrc = (obj.data[x]).Profile_image;

                                    }else {

                                        imgsrc = baseUrl+'uploads/'+(obj.data[x]).Profile_image;

                                    }

                                }

                            }

	

                            html += '<ul class="connList afterClr">'+

                            '<li class="thumb">'+

                            '<img src="'+imgsrc+'" width="40" height="40" />'+

                            '</li>'+

                            '<li class="name">'+

                            (obj.data[x]).Name +

                            '</li>'+

                            '<li class="adrs">';

                            if((obj.data[x]).address) {

                                html += (obj.data[x]).address.breakAt(32);

                            } else {

                                html += '<br><br>';

                            }

                            html +='</li>'+

                            '<li class="btnSet">'+

                            '<input class="curPnt" type="button" value="Accept" onclick="allow(this,'+(obj.data[x]).fid+');" />&nbsp;&nbsp;'+

                            '<input class="curPnt" type="button" value="Deny" onclick="deny(this,'+(obj.data[x]).fid+');" />&nbsp;&nbsp;'+

                            '<img class="imgAlowDeny" height="20" width="50" src="'+baseUrl+'www/images/loader.gif" />'+

                            '</li>'+

                                

                            '</ul><div class="clear"></div>';

                        }

	

                        if(obj.total >= 5) {

                            html+='<p class="pendReq">'+

                            '<a href="'+baseUrl+'contacts/all-requests">View all pending requests</a>'+

                            '</p>';

                        }else {

                            html+='<p class="pendReq2"></p>';

                        }

                        $("#noteTotal").html(obj.total); 

                        $("#ddMyConnList").html(html);

                    } else {

                        html = '<p class="pendReq">No new friend request found.</p>';								

                        $("#ddMyConnList").html(html);

                    }

                  //$("#ddMyConnList").append('<a style="text-decoration:underline;color:blue;margin-left:52px;padding:5px;" href="'+baseUrl+'contacts/friends-list">Go to my connections</a>');
                    $("#ddMyConnList").append('<a class="friend" href="'+baseUrl+'contacts/friends-list">Go to my friends </a>');
                } else {

                    $(thisone).parent().siblings('div').html("");

                }

            },

            error : function() {

                $(thisone).parent().siblings('div').html();

            }

	

        });

    }

	

    function allow(thisone, id) {

        if(id) {

            $(thisone).siblings('img').toggle();

            $.ajax({

                url : baseUrl+'contacts/allow',

                type : 'post',

                data : {
                    id: id
                },

                success : function(obj) {

                    if(obj) { 

                        obj = $.parseJSON(obj);

                        if(obj.total >= 0) {

                            $('#noteTotal').html(obj.total);

                            $(thisone).parent().parent().remove();

                            currentRequests--;

                            if(obj.total == 0) {

                                if($("#noteTotal")) {

                                    $("#noteTotal").hide();

                                }

                            }

                        }

                        if(obj.totalFriends >= 0) {

                            $("#totalFriend").html(obj.totalFriends);

                        }

                        if(currentRequests <= 0) {

                            showFriendRequest();

                        }

                    }

                }

            });

        }

    }

	

    function deny(thisone, id) {

        if(id) {

            $(thisone).siblings('img').toggle();

            $.ajax({

                url : baseUrl+'contacts/deny',

                type : 'post',

                data : {
                    id: id
                },

                success : function(obj) {

                    if(obj) {

                        obj = $.parseJSON(obj);

                        if(obj.total >= 0) {

                            $('#noteTotal').html(obj.total);

                            $(thisone).parent().parent().remove();

                            currentRequests--;

                            if(obj.total == 0) {

                                if($("#noteTotal")) {

                                    $("#noteTotal").hide();

                                }

                            }

                        }

                        if(currentRequests <= 0) {

                            showFriendRequest();

                        }

                    }

                } 

            });

        }

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

    var connectionFlag = false;

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



function timeconversion(a){

    var b = time();

    var c = strtotime($a);

    var d = b-c;

    var minute = 60;

    var hour = minute * 60;

    var day = hour * 24;

    var week = day * 7;

    if(d < 2) return "right now";

    if(d < minute) return floor(d) + " seconds ago";

    if(d < minute * 2) return "about 1 minute ago";

    if(d < hour) return floor(d / minute) + " minutes ago";

    if(d < hour * 2) return "about 1 hour ago";

    if(d < day) return floor(d / hour) + " hours ago";

    if(d > day && d < day * 2) return "yesterday";

    if(d < day * 365) return floor(d / day) + " days ago";

    return "over a year ago";	

}



function deletes(thisone, action, elid) {

    if(action && elid) {

        if(confirm('Are you sure you want to delete?')) {

            $.ajax({

                url  : baseUrl+'home/delete',

                type : 'post',

                data : {
                    action:action, 
                    id:elid
                },

                success: function(data) {

                    data = $.parseJSON(data);

                    if(data && data.success) {

                        if(action =='comments') {

                            $('#comment_'+elid).remove();

                        } else if(action == 'news') {

                            $('#scrpBox_'+elid).remove();

                            var doFlag = true;

                            for(x in commonMap.bubbleArray) {

                                for(y in commonMap.bubbleArray[x].newsId) {

                                    if(commonMap.bubbleArray[x].newsId[y] == elid) {

                                        if(commonMap.bubbleArray[x].newsId.length == 1) {

                                            if(x==0){

                                                commonMap.bubbleArray[0].contentArgs[0][2] = 'This is me!';

                                                commonMap.bubbleArray[0].contentArgs[0][6] = 0;

                                                //commonMap.bubbleArray[x].contentArgs[y].splice(y, 1);

                                                commonMap.bubbleArray[0].newsId = new Array(); 

                                                commonMap.bubbleArray[0].currentNewsId = null;

                                                commonMap.bubbleArray[x].total = 0;

                                                commonMap.bubbleArray[0].divContent =  commonMap.createContent(commonMap.bubbleArray[0].contentArgs[0][0],commonMap.bubbleArray[0].contentArgs[0][1],commonMap.bubbleArray[0].contentArgs[0][2],commonMap.bubbleArray[0].contentArgs[0][3],commonMap.bubbleArray[0].contentArgs[0][4],commonMap.bubbleArray[0].contentArgs[0][5],commonMap.bubbleArray[0].contentArgs[0][6],true);

                                                $(document).find("#mainContent_0").each(function(){

                                                    if($(this).html()=='')

                                                        $(this).remove();

                                                    else{

                                                        $(this).attr('currentdiv',1);

                                                        $(this).attr('totalDiv',1);

                                                        $("#prevDiv_0").css('display','none');

                                                        $("#nextDiv_0").css('display','none');

                                                    }

                                                })

                                                

                                            }else{

                                                commonMap.bubbleArray[x].contentArgs = new Array();

                                                commonMap.bubbleArray[x].newsId = new Array();

                                                //commonMap.bubbleArray = mergeArray(commonMap.bubbleArray,x);

                                                commonMap.marker[x].setMap(null);

                                                commonMap.marker = mergeArray(commonMap.marker,x);

                                                doFlag = false;

                                                break;

                                            }

                                            

                                        } else {

                                            commonMap.bubbleArray[x].contentArgs = mergeArray(commonMap.bubbleArray[x].contentArgs,y);

                                            commonMap.bubbleArray[x].newsId = mergeArray(commonMap.bubbleArray[x].newsId,y);

                                            commonMap.bubbleArray[x].user_id = mergeArray(commonMap.bubbleArray[x].user_id,y);

                                            commonMap.bubbleArray[x].currentNewsId = commonMap.bubbleArray[x].newsId[0];

                                            commonMap.bubbleArray[x].total = commonMap.bubbleArray[x].user_id.length;

                                            commonMap.bubbleArray[x].contentArgs[0][4] = 'first';

                                            commonMap.bubbleArray[x].contentArgs[0][5] = 1;

                                            var arrowflag = true;

                                            if(commonMap.bubbleArray[x].newsId.length > 1){

                                                arrowflag = false;

                                            }

                                            commonMap.bubbleArray[x].divContent =  commonMap.createContent(commonMap.bubbleArray[x].contentArgs[0][0],commonMap.bubbleArray[x].contentArgs[0][1],commonMap.bubbleArray[x].contentArgs[0][2],1,commonMap.bubbleArray[x].contentArgs[0][4],commonMap.bubbleArray[x].contentArgs[0][5],commonMap.bubbleArray[x].contentArgs[0][6],arrowflag);

                                            // commonMap.bubbleArray[x].infobubble.content("<div id='mainContent_"+x+"' class='bubbleContent' totalDiv='"+(commonMap.bubbleArray[x].newsId).length+"' currentDiv='1' onmouseover='setPopupCloseTimer(0)'  onmouseout='setPopupCloseTimer(700)'>"+commonMap.bubbleArray[x].divContent+"</div>");

                                            $(document).find("#mainContent_"+x).each(function(){

                                                                                             

                                                if($(this).html()=='')

                                                    $(this).remove();

                                                else{

                                                    $(this).attr('currentdiv',1);

                                                    $(this).attr('totalDiv',commonMap.bubbleArray[x].newsId.length);

                                                    $(this).html(commonMap.bubbleArray[x].divContent);

                                                }

                                            })

                                        }

                                    }

                                }

                                if(!doFlag)

                                    break;

                            }

                        } else {

                        //                            $(thione).remove();

                        }

                    } else if(data && data.error) {

                        alert('Sorry! we are unable to performe delete action');

                    } else {

                        alert('Sorry! we are unable to performe delete action');

                    }

                }

            });

        }

    } else {

        alert('Sorry! we are unable to performe delete action');

    }

}



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

                /* if(data && data.count){

                      $('#voting_up_'+elid).show();

                    } */

                else if(data && data.error) {

                    alert('Sorry! we are unable to perform voting action');

                } 

            }

        });

        

    } else {

        alert('Sorry! we are unable to performe delete action');

    }

}

/*  function voting ends  */

/* function to vote for individual pages*/

function votingIndividual(thisone, action, elid,userid) {

    if(action && elid && userid) {
console.log(action);
console.log(elid);
console.log(userid);
        $.ajax({

            url  : baseUrl+'info/store-voting-individual',

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

                    $('#thumbs_up2').show('slow');

                    $('#thumbs_up1').hide();

                    //$('#thumbsup_blck_'+elid).css('display:none');

                    // $('#img1').hide();

                    $('#img1').hide();
                    $('#img2').show();

                }

                if(data && data.success) {

                    if(action =='comments') {

                    // alert('in comments part');

                    //$('#comment_'+elid).show();

                    } else if(action == 'news') {

                        $('#thumbs_up1').show('slow');

                        $('#thumbs_up2').hide();

                        //  $('#thumbsup_blck_'+elid).hide();

                        //$('#img1').hide();

                        $('#img1').hide();
                        $('#img2').show();

                        var doFlag = true;

                    } else {

                    //$(thione).remove();

                    }

                }

                    

                if(data && data.noofvotes_2){

                    //alert(data.noofvotes_2);

                    $('#message_success').html(data.noofvotes_2); 

                }  

                /* if(data && data.count){

                      $('#voting_up_'+elid).show();

                    } */

                else if(data && data.error) {

                    alert('Sorry! we are unable to perform voting action');

                } 

            }

        });

        

    } else {

        alert('Sorry! we are unable to performe delete action');

    }

}

/* end of function */







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

/* script to show delete ,sahre functionality in hovering*/
//$('.scrpBox').live('mouseover mouseout', function(event) {
//    if (event.type == 'mouseover') {
//        $(this).find('.floatR').show();
//        $(this).find('.deteleIcon').show();

//    } else {
//        $(this).find('.floatR').hide();
//        $(this).find('.deteleIcon').hide();

//    }

//});






      
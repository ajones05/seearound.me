$(function(){
  $('body').append($('<div/>',{id:'fb-root'}));

  (function(d, s, id){
    var js, fjs = d.getElementsByTagName(s)[0];
    if (d.getElementById(id)) {return;}
    js = d.createElement(s); js.id = id;
    js.src = "https://connect.facebook.net/en_US/sdk.js";
    fjs.parentNode.insertBefore(js, fjs);
  }(document, 'script', 'facebook-jssdk'))

  $('.login-f').click(function(){
    FB.login(function(response){
      if (response.status !== 'connected'){
        return false;
      }
      window.location.href='/index/fb-auth';
    },{scope:'email'});
  })

  $('.loginForm form').on('submit',function(){
    var form=$(this),
      u=$('[name=email]',form),
      p=$('[name=password]',form),
      isValid=true;

    if ($.trim(u.val())===''){
      u.addClass('error');
      isValid=false;
    }

    if ($.trim(p.val())===''){
      p.addClass('error');
      isValid=false;
    }

    if (!isValid){
      return false;
    }

    return true;
  })
})

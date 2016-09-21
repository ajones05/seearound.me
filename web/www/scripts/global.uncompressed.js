$(function(){
	window.fbAsyncInit = function(){
		FB.init({
			appId: facebook_appId,
			xfbml: true,
			cookie: true,
			version: facebook_apiVer
		});
	};

	(function(d, s, id){
		var js, fjs = d.getElementsByTagName(s)[0];
		if (d.getElementById(id)) {return;}
		js = d.createElement(s); js.id = id;
		js.src = "https://connect.facebook.net/en_US/sdk.js";
		fjs.parentNode.insertBefore(js, fjs);
	}(document, 'script', 'facebook-jssdk'));

	$.ajaxSetup({
		error: function(jqXHR, exception){
			if (jqXHR.status === 0) {
				// alert('Not connect.n Verify Network.');
			} else if (jqXHR.status == 404) {
				alert('Requested page not found. [404]');
			} else if (jqXHR.status == 500) {
				alert('Internal Server Error [500].');
			} else if (exception === 'parsererror') {
				alert('Requested JSON parse failed.');
			} else if (exception === 'timeout') {
				alert('Time out error.');
			} else if (exception === 'abort') {
				alert('Ajax request aborted.');
			} else {
				alert('Uncaught Error.n' + jqXHR.responseText);
			}
		}
	});

	if (isLogin){
		var notification = function(){
			ajaxJson({
				url: baseUrl+'contacts/friends-notification',
				failMessage: false,
				done: function(response){
					if (response.friends > 0){
						$("#noteTotal").html(response.friends).show();
					} else {
						$("#noteTotal").hide();
					}
					if (response.messages > 0){
						$("#msgTotal").html(response.messages).show();
					} else {
						$("#msgTotal").hide();
					}
					setTimeout(notification, 2000);
				},
				fail: function(data, textStatus, jqXHR){
					if (data && data.code == 401){
						window.location.href = baseUrl;
						return false;
					}
				}
			});
		};

		notification();

		$('#myProfLink').click(function(e){
			e.preventDefault();
			e.stopPropagation();

			$('#ddMyProf').show();

			$('body').on('click.menu', function(){
				$('#ddMyProf').hide();
				$(this).off('click.menu');
			});
		});

		$("#ddMyConn").click(function (e){
			e.preventDefault();
			e.stopPropagation();

			$('#ddMyConnList').show();

			$('body').on('click.menu', function(){
				$('#ddMyConnList').hide();
				$(this).off('click.menu');
			});
		});

		$('#searchNews .search').click(function(){
			$('#searchNews').submit();
		});

		$('#ddMyConn').on('click', function(e){
			e.preventDefault();
			$("#ddMyConnList").html('')
				.append(
					$('<p/>').addClass('bgTop'),
					$('<img/>', {src: baseUrl + 'www/images/wait.gif'}).addClass('loading'),
					$('<p/>').addClass('pendReq2')
				);
			ajaxJson({
				url: baseUrl + 'contacts/requests',
				done: function(response){
					$("#ddMyConnList").html('');
					if (response.data){
						$("#ddMyConnList").append($('<p/>').addClass('bgTop'));
						for (var x in response.data){ 
							$("#ddMyConnList").append(
								$('<a/>', {href: response.data[x].link}).addClass('profileLink').append(
									$('<ul/>').addClass('connList').append(
										$('<li/>').addClass('thumb').append(
											$('<img/>', {
												src: response.data[x].image,
												width: 40,
												height: 40
											})
										),
										$('<li/>').addClass('name').html(response.data[x].name + ' is<br/>following you')
									),
									$('<div/>').addClass('clear')
								)
							);
						}
						$('#noteTotal').hide();
					} else {
						$("#ddMyConnList").append($('<p/>').addClass('pendReq').text('No new followers'));
					}
					$("#ddMyConnList").append('<a class="friend" href="'+baseUrl+'contacts/friends-list">See all</a>');
				},
				fail: function(){
					$("#ddMyConnList").hide();
				}
			});
		});
	} else {
		$('#facebookLogin').click(function(){
			FB.login(function(response){
				if (response.status !== 'connected'){
					return false;
				}
				window.location.href=baseUrl+'index/fb-auth';
			},{scope: 'email'});
		});

		$('#loginForm').validate({
			errorPlacement: function(error, element){
			},
			rules: {
				email: {
					required: true,
					email: true
				},
				password: {
					required: true
				}
			}
		});
	}
});

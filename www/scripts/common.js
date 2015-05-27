var ERROR_MESSAGE = 'Internal server error';
var currentRequests = 0;

$(function(){
	window.fbAsyncInit = function(){
		FB.init({
			appId: facebook_appId,
			xfbml: true,
			cookie: true,
			version: 'v2.1'
		});
	};

	(function(d, s, id){
		var js, fjs = d.getElementsByTagName(s)[0];
		if (d.getElementById(id)) {return;}
		js = d.createElement(s); js.id = id;
		js.src = "//connect.facebook.net/en_US/sdk.js";
		fjs.parentNode.insertBefore(js, fjs);
	}(document, 'script', 'facebook-jssdk'));

	if (isLogin){
        notification();
        setInterval('notification()', 120000);

		$('#logoutLi a').click(function(e){
			var url = $(this).attr('href');

			e.preventDefault();

			FB.getLoginStatus(function(response){
				if (response.authResponse){
					FB.logout(function(){
						window.location.href = url;
					});
				} else {
					window.location.href = url;
				}
			}); 
		});

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
	} else {
		$('#facebookLogin').click(function(e){
			e.preventDefault();

			var url = $(this).attr('href');
			
			FB.login(function(response){
				if (response.authResponse){
					FB.api('/me', function(response){
						if (response.email){
							window.location.href = url;
						} else {
							alert('Email not activated');
						}
					});
				}
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
			},
			submitHandler: function(form){
				$('#loginError').html('');

				$.ajax({
					url: $(form).attr('action'),
					data: $(form).serialize(),
					type: 'POST',
					dataType: 'json'
				}).done(function(response){
					if (response && response.status){
						if (!response.active){
							window.location.href = response.redirect;
							return false;
						}

						if (typeof returnUrl !== 'undefined' && returnUrl != ''){
							window.location.href = returnUrl;
							return false;
						}

						window.location.href = response.redirect;
					} else if (response){
						$('#loginError').html(response.error.message);
					} else {
						alert(ERROR_MESSAGE);
					}
				}).fail(function(jqXHR, textStatus){
					alert(textStatus);
				});
			}
		});
	}
});

function showFriendRequest(thisone){
	$("#ddMyConnList")
		.html('')
		.append(
			$('<p/>').addClass('bgTop'),
			$('<img/>', {src: baseUrl + 'www/images/wait.gif'}).addClass('loading'),
			$('<p/>').addClass('pendReq2')
		);

	$.ajax({
		url: baseUrl + 'contacts/requests',
		type: 'POST',
		dataType: 'json'
	}).done(function(response){
		if (response && response.status){
			$("#ddMyConnList").html('');

			if (response.data){
				currentRequests = 0;
				$("#ddMyConnList").append($('<p/>').addClass('bgTop'));

				for (var x in response.data){ 
					$("#ddMyConnList").append(
						$('<ul/>').addClass('connList').append(
							$('<li/>').addClass('thumb').append(
								$('<img/>', {src: response.data[x].image, width: 40, height: 40})
							),
							$('<li/>').addClass('name').html(response.data[x].name + ' is<br/>following you')
						),
						$('<div/>').addClass('clear')
					);

					currentRequests++;
				}

				if (response.total >= 5){
					$("#ddMyConnList").append(
						$('<div/>').addClass('pendReq').append(
							$('<a/>', {href: baseUrl + 'contacts/all-requests'}).text('View all pending requests')
						)
					);
				} else {
					$("#ddMyConnList").append($('<p/>').addClass('pendReq2'));
				}

				$("#noteTotal").html(response.total);
			} else {
				$("#ddMyConnList").append($('<p/>').addClass('pendReq').text('No new friend request found'));
			}

			$("#ddMyConnList").append('<a class="friend" href="'+baseUrl+'contacts/friends-list">Go to my friends </a>');
		} else {
			alert(response ? response.error.message : ERROR_MESSAGE);
			$("#ddMyConnList").hide();
		}
	}).fail(function(jqXHR, textStatus){
		alert(textStatus);
		$("#ddMyConnList").hide();
	});
}

function friendRequest(thisone, id, action){
	$(thisone).siblings('img').toggle();

	$.ajax({
		url: baseUrl + 'contacts/friend',
		type: 'POST',
		data: {
			user: id,
			action: action,
			total: true
		},
		dataType: 'json'
	}).done(function(response){
		if (response && response.status){
			$('#noteTotal').html(response.total);
			$(thisone).parent().parent().remove();

			currentRequests--;

			if (response.total == 0){
				$('#noteTotal').hide();
			}

			if (currentRequests <= 0){
				showFriendRequest();
			}
		} else {
			alert(response ? response.error.message : ERROR_MESSAGE);
		}
	}).fail(function(jqXHR, textStatus){
		alert(textStatus);
	});
}

// TODO: merge with friendRequest

function deleteFriend(userId, target, callback){
	if (!confirm("Are you sure to delete this friend?")){
		return false;
	}

	$.ajax({
		url: baseUrl + 'contacts/friend',
		type: 'POST',
		data: {
			user: userId,
			action: 'reject'
		},
		dataType: 'json'
	}).done(function(response){
		if (response && response.status){
			$(target).remove();

			if (typeof callback === 'function'){
				callback.call();
			}
		} else {
			alert(response ? response.error.message : ERROR_MESSAGE);
		}
	}).fail(function(jqXHR, textStatus){
		alert(textStatus);
	});
}

// TODO: remove
function googleMapsPlacesAutocompleteReset(input){
	google.maps.event.clearInstanceListeners(input);
	$('.pac-container').remove();
	return new google.maps.places.Autocomplete(input);
}

function setGoogleMapsAutocompleteValue(input, value){
	input.blur();
	$('.pac-container .pac-item').addClass('hidden');
	setTimeout(function(){
		input.val(value).change();
	}, .1);
}

function userMessageDialog(userId){
	$('body').css({overflow: 'hidden'});

	$('<div/>', {'class': 'message-dialog'})
		.append(
			$('<img/>', {src: baseUrl + 'www/images/mail_send.gif'}),
			$('<span/>', {'class': 'locppmsgimg'}).text('Send Message'),
			$('<div/>', {'class': 'row-content'}).append(
				$('<form/>').append(
					$('<div/>').append(
						$('<input/>', {
							type: 'text',
							name: 'subject',
							placeholder: 'Please enter subject...'
						})
					),
					$('<div/>').append(
						$('<textarea/>', {
							name: 'message',
							placeholder: 'Please enter message...'
						})
					),
					$('<div/>').append(
						$('<input/>', {
							type: 'submit',
							value: 'Send',
							'class': 'btnBlueRpt'
						}),
						$('<input/>', {
							type: 'button',
							value: 'Cancel',
							'class': 'btnBlueRpt'
						}).click(function(){
							$('.message-dialog').dialog('close');
						})
					),
					$('<input/>', {type: 'hidden', name: 'user_id'}).val(userId)
				)
			)
		)
		.appendTo($('body'))
		.dialog({
			modal: true,
			resizable: false,
			drag: false,
			width: 540,
			height: 260,
			dialogClass: 'colorbox',
			beforeClose: function(event, ui){
				$('body').css({overflow: 'visible'});
				$(event.target).dialog('destroy').remove();
			},
			open: function(event, ui){
				$('form', event.target).validate({
					rules: {
						subject: {
							required: true
						},
						message: {
							required: true
						}
					},
					submitHandler: function(form){
						$(event.target).mask('Loading...');

						$.ajax({
							url: baseUrl + 'message/send',
							data: $(form).serialize(),
							type: 'POST',
							dataType: 'json'
						}).done(function(response){
								if (response && response.status){
									$(event.target)
										.empty()
										.append(
											$('<div/>', {'class': 'messageSuccess'}).append(
												$('<img/>', {src: baseUrl + 'www/images/correct.gif'}),
												$('<span/>').text('Message sent successful')
											)
										);
								} else {
									$(event.target).unmask();
									alert(response ? response.error.message : ERROR_MESSAGE);
								}
							}).fail(function(jqXHR, textStatus){
								$(event.target).unmask();
								alert(textStatus);
							});
					}
				});
			}
		});
}

function userAddressTooltip(address, image){
    return '<div class="profile-map-info">' +
		'<div class="user-img">' +
			'<img src="' + image + '" />' +
		'</div>' +
		'<div class="user-address">' + address + '</div>' +
	'</div>';
}

function notification(){
	$.ajax({
		url: baseUrl + 'contacts/friends-notification',
		type: 'POST',
		dataType: 'json'
	}).done(function(response){
		if (response && response.status){
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
		} else {
			alert(response ? response.error.message : ERROR_MESSAGE);
		}
	}).fail(function(jqXHR, textStatus){
		alert(textStatus);
	});
}

// TODO: ???
function setThisHeight(height){
	var commentClass = ['leftCol eqlCH', 'rightCol textAlignCenter eqlCH'];

	$(".mainContainer div").each(function(){
		for (var xxx in commentClass){
			if (commentClass[xxx].indexOf($(this).attr('class')) >= 0){
				$(this).css('min-height','auto');
				$(this).height("100%");
					if($(this).attr('id')!='mapDiv') {
					$(this).css("position","absolute");
				}
			}
		}
	});

	$('#loading').hide();
	$("#newsData").css("position","relative");
	$("#newsData").css("height","100%");
}

// TODO: ???
function setHeight(col){
	var maxHeight = 0;

    $(col).each(function(){
		if ($(this).height() > maxHeight){
            maxHeight = $(this).height();
        }
    });

	$(col).css('min-height', maxHeight);
}

function keyCode(event){
	return event.keyCode || event.which;
}

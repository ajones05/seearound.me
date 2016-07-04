var ERROR_MESSAGE = 'Internal server error';
var currentRequests = 0;

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
		js.src = "//connect.facebook.net/en_US/sdk.js";
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

					currentRequests++;
				}

				$('#noteTotal').hide();
			} else {
				$("#ddMyConnList").append($('<p/>').addClass('pendReq').text('No new followers'));
			}

			$("#ddMyConnList").append('<a class="friend" href="'+baseUrl+'contacts/friends-list">See all</a>');
		} else {
			alert(response ? response.message : ERROR_MESSAGE);
			$("#ddMyConnList").hide();
		}
	}).fail(function(jqXHR, textStatus){
		alert(textStatus);
		$("#ddMyConnList").hide();
	});
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
												$('<span/>').text('Message sent')
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
			if (response && response.code == 401){
				window.location.href = baseUrl;
				return false;
			}

			alert(response ? response.message : ERROR_MESSAGE);
		}
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
					if(!$(this).hasClass('mapDiv')) {
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

function ajaxJson(url, settings){
	if (typeof url === 'string'){
		settings.url = url;
	} else if (typeof url === 'object'){
		settings = url;
	}

	settings.dataType = 'json';

	var jqxhr = $.ajax($.extend(settings, {type: 'POST'}))
		.done(function(data, textStatus, jqXHR){
			var isObject = typeof data === 'object';
			if (!isObject || data.status == 0){
				if (typeof settings.fail === 'function'){
					settings.fail(data, textStatus, jqXHR);
				}
				alert(isObject ? data.message : ERROR_MESSAGE);
				return false;
			}

			if (typeof settings.done === 'function'){
				return settings.done(data, textStatus, jqXHR);
			}
		}).fail(function(jqXHR, textStatus){
			if (jqXHR.readyState == 0 || jqXHR.status == 0){
				return true;
			}
			if (typeof settings.fail === 'function'){
				settings.fail({}, textStatus, jqXHR);
			}
			alert('Internal server error');
		});

	return jqxhr;
}

Object.size = function(obj){
	var size = 0, key;
	for (key in obj){
		if (obj.hasOwnProperty(key)) size++;
	}
	return size;
};

function parsePlaceAddress(place){
	var placeData = {}, addressFields = {
		street_number: 'street_number',
		route: 'street_name',
		locality: 'city',
		administrative_area_level_1: 'state',
		country: 'country',
		postal_code: 'zip'
	};

	for (var i in place.address_components){
		var component = place.address_components[i],
			type = component.types[0];
		if (addressFields[type]){
			placeData[addressFields[type]] = type==='route'?
				component.long_name:component.short_name;
		}
	}

	return placeData;
}

function latlngDistance(firstPoint, secondPoint, unit){
	var lat1 = firstPoint.lat();
	var lon1 = firstPoint.lng();
	var lat2 = secondPoint.lat();
	var lon2 = secondPoint.lng();

	if (lat1 === lat2 && lon1 === lon2){
		return 0;
	}

	var theta = lon1 - lon2;
	var dist = Math.sin(lat1 * Math.PI / 180) * Math.sin(lat2 * Math.PI / 180) +  Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * Math.cos(theta * Math.PI / 180);
	var miles = (Math.acos(dist) * 180 / Math.PI) * 60 * 1.1515;

	if (unit == "K"){
		return (miles * 1.609344);
	} else if (unit == "N"){
		return (miles * 0.8684);
	} else {
		return miles;
	}
}

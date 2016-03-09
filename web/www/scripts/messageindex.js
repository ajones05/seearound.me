$(function(){
	$('.message-list > div').click(function(){
		var target = $(this).closest('.message-list');
		$('.errors', target).remove();

		if (target.hasClass('open')){
			return true;
		}

		$('.message-list').removeClass('open');
		$('.replyFrm').remove();
		target.addClass('open').removeClass("highLight");
		target.find(".message-third img").attr("src", baseUrl + "www/images/message_checked.png");
		loadReplyMessages(target);
		$('html, body').animate({scrollTop: target.offset().top - 50}, 0);
		$('#msgTotal').hide();
	});
});

function loadReplyMessages(target){
	var messageId = target.attr('id').replace('row_', ''),
		start = $('.replyFrm .desc', target).size();

	target.append($("<img/>", {src: baseUrl + "www/images/wait.gif", 'class': 'loading'}));

	$.ajax({
		url: baseUrl + 'message/viewed',
		data: {
			id: messageId,
			start: start
		},
		type: 'POST',
		dataType: 'json'
	}).done(function(response){
		$('.loading', target).remove();

		if (!response || !response.status){
			alert(response ? response.error.message : ERROR_MESSAGE);
			return false;
		}

		if (!start){
			$(target).append(
				$('<ul/>').addClass('replyFrm').append(
					$('<li/>').addClass('replyTxt').append(
						$('<span/>').append(
							$('<img/>', {src: baseUrl + 'www/images/wait.gif'}).addClass('rpl-loading')
						)
					),
					$('<li/>').addClass('reply-wrapper').append(
						$('<img/>', {src: imagePath}),
						$('<div/>').append(
							$('<textarea/>', {name: 'message', placeholder: 'Enter your message'})
						)
					),
					$('<li/>').addClass('btn').append(
						$('<input/>', {type: 'submit'})
							.val('Reply')
							.click(function(){
								var $submitField = $(this),
									$messageField = $('[name=message]', target),
									message = $messageField.val();

								if ($.trim(message) === ''){
									replyMessageError('Please enter value', target);
									return false;
								}

								$('.errors', target).remove();
								$(".rpl-loading", target).show();
								$submitField.attr("disabled", true);

								ajaxJson({
									url:baseUrl+'message/reply',
									data:{id:messageId,message:message},
									done: function(replyResponse){
										$(".rpl-loading", target).hide();
										$submitField.attr("disabled", false);
										$('.replyTxt', target).before(renderReplyMessage(replyResponse.message));
										$messageField.val('');
										setThisHeight(Number($("#midColLayout").height()));
									},
									fail: function(data, textStatus, jqXHR){
										$(".rpl-loading", target).hide();
										$submitField.attr("disabled", false);
									}
								});
							})
					)
				)
			)
		}

		if (response.reply){
			for (x in response.reply){
				$('.replyFrm', target).prepend(renderReplyMessage(response.reply[x]));
				start++;
			}
		}

		if (response.total > start){
			$('.replyFrm', target).prepend(
				$('<li/>').addClass('all').append(
					$('<a/>', {href: '#'}).text('Show all').click(function(e){
						e.preventDefault();
						$('.all', target).remove();
						loadReplyMessages(target);
					})
				)
			);
		}

		setThisHeight(Number($("#midColLayout").height()));
	});
}

function replyMessageError(message, target){
	$('.replyTxt', target).append($('<span/>').addClass('errors').text(message));
}

function renderReplyMessage(data){
	var message = $('<li/>').addClass('desc').append(
		$('<div/>').addClass('desc-frist').append(
			$('<img/>').attr('src', data.sender.image)
		),
		$('<div/>').addClass('desc-second').append(
			$('<b/>').text(data.sender.name),
			$('<span/>').text(data.reply_text)
		),
		$('<div/>').addClass('desc-third').append(
			$('<span/>').addClass('dateTime').text(data.created)
		),
		$('<div/>').addClass('clr')
	)

	if (user_id == data.receiver_id && data.receiver_read == 0){
		message.addClass('unread');
	}

	return message;
}

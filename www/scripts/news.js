function addCommentHandle(e){
	if (!isLogin){
		$.colorbox({
			width: '26%',
			height: '20%',
			inline: true,
			href: '#login-id',
			open: true
		}, function(){
			$('html, body').animate({scrollTop: 0}, 0);
		});

		return false;
	}

	var $target = $(this),
		comment = $target.val(),
		news_id = $target.closest('.scrpBox').attr('id').replace('scrpBox_', '');

	if (comment.length == 0){
		return true;
	}

	if (comment.length > 250){
		$target.val(comment = comment.substring(0, 250));
		alert("The comment should be less the 250 Characters");
		return false;
	}

	if (comment.indexOf('<') > 0 || comment.indexOf('>') > 0){
		alert('You enter invalid text');
		$target.val(comment.replace('<', '').replace('>', ''));
		return false;
	}

	if (e.keyCode === 13){
		$target.attr('disabled', true);

	    $('.commentLoading').show();

		$.ajax({
			url: baseUrl + 'home/add-new-comments',
			data: {
				comment: comment,
				news_id: news_id
			},
			type: 'POST',
			dataType: 'json',
			async: false
		}).done(function(response){
			if (response && response.status){
				e.preventDefault();
				$target.val('').attr('disabled', false).blur();
				$target.closest('.cmntList-last').before(response.html);
				$('.commentLoading').hide();
			} else {
				// ???
				alert(ERROR_MESSAGE);
			}
		}).fail(function(jqXHR, textStatus){
			alert(textStatus);
		});
	} else {
		$target.autoGrow();

        if (Number($("#newsData").height()) > 714){
			// ???
            setThisHeight(Number($("#newsData").height()) + 100);
		}
	}
	
}

function showComment(){
	if (!isLogin){
		$.colorbox({
			width: '26%',
			height: '20%',
			inline: true,
			href: '#login-id',
			open: true
		}, function(){
			$('html, body').animate({scrollTop: 0}, 0);
		});

		return false;
	}

	var news_item = $(this).closest('.scrpBox');

	$('.cmntList-last', news_item).show();
	$('.textAreaClass', news_item).focus();

	if ($("#newsData").height() > 714){
		setThisHeight(Number($("#newsData").height())+100);
	}

	$(this).closest('.post-comment').remove();
}

function removeNews(){
	if (confirm('Are you sure you want to delete?')){
		var $target = $(this).closest('.scrpBox'),
			news_id = $target.attr('id').replace('scrpBox_', '');

		$.ajax({
			url: baseUrl + 'home/delete',
			type: 'POST',
			dataType: 'json',
			data: {id: news_id}
		}).done(function(response){
			if (response && response.status){
				$target.remove();

				var doFlag = true;

				for (x in commonMap.bubbleArray){
					for (y in commonMap.bubbleArray[x].newsId){
						if (commonMap.bubbleArray[x].newsId[y] == news_id){
							if (commonMap.bubbleArray[x].newsId.length == 1){
								if (x == 0){
									commonMap.bubbleArray[0].contentArgs[0][2] = 'This is me!';
									commonMap.bubbleArray[0].contentArgs[0][6] = 0;
									commonMap.bubbleArray[0].newsId = new Array(); 
									commonMap.bubbleArray[0].currentNewsId = null;
									commonMap.bubbleArray[x].total = 0;
									commonMap.bubbleArray[0].divContent = commonMap.createContent(
										commonMap.bubbleArray[0].contentArgs[0][0],
										commonMap.bubbleArray[0].contentArgs[0][1],
										commonMap.bubbleArray[0].contentArgs[0][2],
										commonMap.bubbleArray[0].contentArgs[0][3],
										commonMap.bubbleArray[0].contentArgs[0][4],
										commonMap.bubbleArray[0].contentArgs[0][5],
										commonMap.bubbleArray[0].contentArgs[0][6],
										true
									);

									$(document).find("#mainContent_0").each(function(){
										if ($(this).html()==''){
											$(this).remove();
										} else {
											$(this).attr('currentdiv', 1);
											$(this).attr('totalDiv', 1);
											$("#prevDiv_0").css('display', 'none');
											$("#nextDiv_0").css('display', 'none');
										}
									});
								} else {
									commonMap.bubbleArray[x].contentArgs = new Array();
									commonMap.bubbleArray[x].newsId = new Array();
									commonMap.marker[x].setMap(null);
									commonMap.marker = mergeArray(commonMap.marker,x);
									doFlag = false;
									break;
								}
							} else {
								commonMap.bubbleArray[x].contentArgs = mergeArray(commonMap.bubbleArray[x].contentArgs, y);
								commonMap.bubbleArray[x].newsId = mergeArray(commonMap.bubbleArray[x].newsId, y);
								commonMap.bubbleArray[x].user_id = mergeArray(commonMap.bubbleArray[x].user_id, y);
								commonMap.bubbleArray[x].currentNewsId = commonMap.bubbleArray[x].newsId[0];
								commonMap.bubbleArray[x].total = commonMap.bubbleArray[x].user_id.length;
								commonMap.bubbleArray[x].contentArgs[0][4] = 'first';
								commonMap.bubbleArray[x].contentArgs[0][5] = 1;

								var arrowflag = true;

								if (commonMap.bubbleArray[x].newsId.length > 1){
									arrowflag = false;
								}

								commonMap.bubbleArray[x].divContent = commonMap.createContent(
									commonMap.bubbleArray[x].contentArgs[0][0],
									commonMap.bubbleArray[x].contentArgs[0][1],
									commonMap.bubbleArray[x].contentArgs[0][2],
									1,
									commonMap.bubbleArray[x].contentArgs[0][4],
									commonMap.bubbleArray[x].contentArgs[0][5],
									commonMap.bubbleArray[x].contentArgs[0][6],
									arrowflag
								);

								$(document).find("#mainContent_"+x).each(function(){
									if ($(this).html()==''){
										$(this).remove();
									} else {
										$(this).attr('currentdiv',1);
										$(this).attr('totalDiv',commonMap.bubbleArray[x].newsId.length);
										$(this).html(commonMap.bubbleArray[x].divContent);
									}
								});
							}
						}
					}

					if (!doFlag){
						break;
					}
				}
			} else {
				alert('Sorry! we are unable to performe delete action');
			}
		}).fail(function(jqXHR, textStatus){
			alert(textStatus);
		});
	}
}

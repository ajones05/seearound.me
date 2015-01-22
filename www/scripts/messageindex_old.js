    var currentOpen = 0;

    function showData(that, number) { 

        number = Number(number);

        $('#message_success_'+number).hide(); 

        $('#error_'+number).html('');	

        if(currentOpen == number) {

            $("#show_data_"+currentOpen).slideToggle();

        } else {

            $("#show_data_"+currentOpen).hide();

            if($("#show_data_"+number).html() == "") {

                $("#loading_"+number).show();

                $.ajax({

                    type: "POST",

                    url: baseUrl+"message/viewed",

                    data: {id:number},

                    success: function (msg) { 

                        msg = JSON.parse(msg);

                        if(msg && msg.errors) {

                            $("#loading_"+number).hide();

                            alert("Error...");

                        }else if(msg && msg.success) {

                            var html = '<li class="desc">'+(msg.inboxData).message+'</li>';

								if(msg.replyData) {

									html +='<div id="replyDiv_'+number+'">';

									for(x in msg.replyData) {

										html += '<li class="desc" ';

										if(user_id == (msg.replyData[x]).receiver_id && (msg.replyData[x]).receiver_read == 'false') {

											html +='style="border-top: 0px none;background-color: #FFFFFF;"';

										} else {

											html +='style="border-top: 0px none;"';

										}

										html +='><b style="color: #0069D6;font-size:12px;">'+(msg.replyData[x]).name+'</b><span class="dateTime afterClr">'+(msg.replyData[x]).created+'</span><div style="padding:4px 4px 4px 0;">'+(msg.replyData[x]).reply_text+'</div><div class="clr"></div></li>';

									}

									html +='</div>';

								}

                                html += '<li class="replyTxt">Reply'+

                                    '&nbsp;&nbsp;<span  style="margin-left:2px;" class="errors" id="error_'+(msg.inboxData).id+'"></span>'+

                                    '<span style="color:#33CC00;display:none;" id="message_success_'+(msg.inboxData).id+'" >'+

                                        '<img src="'+baseUrl+'www/images/correct.gif" />&nbsp;Message sent successful'+

                                    '</span> <span><img id="rpl_loading_'+(msg.inboxData).id+'" style="display:none;margin-bottom: -6px;" src="'+baseUrl+'www/images/wait.gif" /></span>'+

                                '</li>'+

                                '<li>'+

                                    '<input type="hidden" id="reply_user_'+(msg.inboxData).id+'" name="reply_user_'+(msg.inboxData).id+'" value="';

										if(user_id == (msg.inboxData).sender_id) {

											html += (msg.inboxData).receiver_id;

										} else {

											html += (msg.inboxData).sender_id;

										}

									 html +='" />'+

                                    '<input type="hidden" id="reply_subject_'+(msg.inboxData).id+'" name="reply_subject_'+(msg.inboxData).id+'" value="'+(msg.inboxData).subject+'" />'+

                                    '<textarea id="reply_text_area_'+(msg.inboxData).id+'" placeholder="Enter your message"></textarea>'+

                                '</li>'+

                                '<li class="btn"><input id="forFocus_'+number+'" type="submit" value="Reply" onclick="sendReply(this, '+(msg.inboxData).id+');if (event.stopPropagation){event.stopPropagation();}else if (window.event){window.event.cancelBubble = true;}"/>';

								html +='</li>';

								if(msg.replyDataTotal && msg.replyDataTotal > 5) {

									$('#showAlll_'+number).show();	

								}

								

                            $("#show_data_"+number).html(html);

							$("#show_data_"+number).show();

                            $(that).removeClass("highLight");

                            $("#loading_"+number).hide();

                            setThisHeight(Number($("#midColLayout").height()));

							$.ajax({

								type: "POST",

								url: baseUrl+"message/reply-viewed",

								data: {id:(msg.inboxData).id},

								success: function (msg) { 

								

								}  

							});

                        }

                    }     

                });

            } else {

                $("#show_data_"+number).slideToggle();                

            }

            currentOpen = number;

        }

		$('html, body').animate({scrollTop: ($("#row_"+number).offset().top)-50 }, 0);

		notification();

            

    } 

		

    function sendReply(that, id) {
        //$("#forFocus_"+id).attr("disabled", true);
        $('#error_'+id).html('');	
        
        var message = $('#reply_text_area_'+id).val();

        var subject = $('#reply_subject_'+id).val();

        var user_id = $('#reply_user_'+id).val();

        if($('#reply_text_area_'+id).val() != "") {
             
            $("#rpl_loading_"+id).show();
            $("#forFocus_"+id).attr("disabled", true);
            $('#message_success_'+id).hide();

            $('#error_'+id).html('');	

            $.ajax({

                type: "POST",

                url: baseUrl+"message/reply",

                data: {'id':id,'subject':subject, 'message':message, 'user_id':user_id},

                success: function (msg) { 

                    $("#rpl_loading_"+id).hide();
                    $("#forFocus_"+id).attr("disabled", false);
                    msg = JSON.parse(msg);
                    if(msg && msg.errors) {

                        $('#error_'+id).html('There are some errors');	

                        $("#rpl_loading_"+id).hide();

                    }else if(msg && msg.replyData) {

						var html = '<li class="desc" style="border-top: 0px solid #E9EAEB;"><b style="color: #0069D6;font-size:12px;">'+(msg.replyData[0]).name+'</b><span class="dateTime afterClr">'+(msg.replyData[0]).created+'</span><div style="padding:4px 4px 4px 0;">'+(msg.replyData[0]).reply_text+'</div><div class="clr"></div></li>';

						$('#replyDiv_'+id).append(html);

                        $('#reply_text_area_'+id).val('');

                        $('#subject').val('');

                        $('#message_success_'+id).slideToggle();  

                        $('#reply_box_'+id).slideToggle();      

						setThisHeight(Number($("#midColLayout").height()));

                    }			

                }     

            });

        }else {

            $('#error_'+id).html('Please enter value');

            $('#message_success_'+id).hide();

        }

		

    }

	

	function showAllReply(thisone, id) {

		$('#loading_'+id).show();

		$.ajax({

                type: "POST",

                url: baseUrl+"message/show-all-reply",

                data: {'id':id},

                success: function (msg) { 

					msg = JSON.parse(msg);

                    var html = '';

					for(x in msg.replyData) {

						html += '<li class="desc" style="border-top: 0px none;;"><b style="color: #0069D6;font-size:12px;">'+(msg.replyData[x]).name+'</b><span class="dateTime afterClr">'+(msg.replyData[x]).created+'</span><div style="padding:4px 4px 4px 0;">'+(msg.replyData[x]).reply_text+'</div><div class="clr"></div></li>';

					}					

					$('#replyDiv_'+id).html(html);

					$('#loading_'+id).hide();

					setThisHeight(Number($("#midColLayout").height()));

					$('#showAlll_'+id).hide();	

				}

		});

	}
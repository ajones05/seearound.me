var map, infowindow;

$(function(){
	map = new google.maps.Map($('#map_canvas')[0], {
		zoom: 14,
		minZoom: 13,
		maxZoom: 15,
		center: new google.maps.LatLng(userLatitude, userLongitude),
		disableDefaultUI: true,
		panControl: false,
		zoomControl: false,
		scaleControl: false,
		streetViewControl: false,
		overviewMapControl: false,
		mapTypeId: google.maps.MapTypeId.ROADMAP
	});

	var marker = new google.maps.Marker({
		position: map.getCenter(),
		map: map,
		icon: baseUrl + 'www/images/icons/icon_1.png'
	});

	infowindow = new google.maps.InfoWindow({
		content: userAddressTooltip(userAddress, imagePath)
	});

	infowindow.open(map, marker);

	moreFriends(0,'OTHER');
	setHeight('.eqlCH');

        $("#search")
			.val('')
			.autocomplete({
            minLength: 1,
            source: function (request, response) {
            $("#sacrchWait").show();
            $.ajax({
                url: baseUrl+"contacts/search",
                dataType: "json",
                data: {            
                    search: request.term
                },
                success: function (data) {
                    $("#sacrchWait").hide();
                    response(data.success);
                }
                });
            },
            focus: function (event, ui) {
            if (ui.item.id < 0)
                return false;
            $("#search").val(ui.item.Name);
                return false;
            },
            select: function (event, ui) {
               //alert(11);
                if (ui.item.id < 0)
                    return false;        
                window.location.href = baseUrl+"home/profile/user/"+ui.item.id;    
                //return false;
            }
        })
        .data("ui-autocomplete")._renderItem = function (ul, item) {
            var imgsrc ="";
            var address = "";
            if(item.Profile_image) {
                if(item.Profile_image.indexOf("://") > 0) {
                    imgsrc = item.Profile_image;
                }else {
                    imgsrc = baseUrl+"uploads/"+item.Profile_image;
                }
            } else {
                imgsrc = baseUrl+"www/images/img-prof40x40.jpg";
            } 
            if(item.address != null) {
                address = item.address;
            }
            return $("<li></li>")
            .data("item.autocomplete", item)
            .append("<a><div class='ui_image'><img height='50' width='50' src='"+imgsrc+"' /></div><div class='ui_main_text'><span class='ui_name'>"+ item.Name +"</span><br><span class='ui_address'>"+ address +"</span></div></a>")
            .appendTo(ul);
        };
});

    function moreFriends(page,type){
        $("#sacrchWait2").toggle();
        $("#moreText").toggle();
        $.ajax({
            url : baseUrl+'contacts/friends-list',
            type : "post",
            data : {page:page},
            success : function(data) {
                data = $.parseJSON(data);
                if(data.more > 0) {
                    var pageHtml = '<div class="row show-grid">'+
                        '<div align="center" onclick=moreFriends('+data.page+',"OTHER") class="postClass_after">'+
                            '<lable id="moreText">More</lable><img id="sacrchWait2" class="searchWait" src="'+baseUrl+'www/images/wait.gif"/>'+
                        '</div>'+
                    '</div>';
                    $("#pagingDiv").html(pageHtml);
                } else {
                    $("#pagingDiv").toggle();
                }

                if (data && data.frlist){
                    for (var x in data.frlist){
						$("#friendList").append(
							$('<div/>', {'class': 'invtFrndList', 'id': 'user-' + data.frlist[x].id}).append(
								$('<ul/>', {'class': 'invtFrndRow'}).append(
									$('<li/>', {'class': 'img'}).append(
										$('<a/>', {href: baseUrl + 'home/profile/user/' + data.frlist[x].id}).append(
											$('<img/>', {src: data.frlist[x].Profile_image})
										)
									),
									$('<li/>', {'class': 'name'}).append(
										data.frlist[x].Name,
										$('<span/>', {'class': 'loc'}).text(data.frlist[x].address)
									),
									$('<li/>', {'class': 'message btnCol'}).append(
										$('<img/>', {src: baseUrl + 'www/images/envelope-icon.gif'}).click(function(){
											userMessageDialog($(this).closest('.invtFrndList').attr('id').replace('user-', ''));
										})
									),
									$('<li/>', {'class': 'delete btnCol'}).append(
										$('<img/>', {src: baseUrl + 'www/images/delete-icon.png'}).click(function(){
											var $target = $(this).closest('.invtFrndList');
											deleteFriend($target.attr('id').replace('user-', ''), $target);
										})
									),
									$('<div/>', {'class': 'clr'})
								),
								$('<div/>', {'class': 'clr'})
							),
							$('<div/>', {'class': 'clr'})
						);

						var marker = new google.maps.Marker({
							map: map,
							position: new google.maps.LatLng(data.frlist[x].latitude, data.frlist[x].longitude),
							icon: baseUrl + 'www/images/icons/icon_2.png',
							data: {
								address: data.frlist[x].address,
								image: data.frlist[x].Profile_image,
							}
						});

						google.maps.event.addListener(marker, 'click', function(){
							if (infowindow){
								infowindow.close();
							}

							infowindow.setContent(userAddressTooltip(this.data.address, this.data.image));
							infowindow.open(map, this);
						});

                        if ($("#midColLayout").height()>714)
                            setThisHeight(Number($("#midColLayout").height()));
                        }
                }
            
               
            }
        });
    }

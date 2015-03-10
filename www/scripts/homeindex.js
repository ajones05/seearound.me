    var imageHeight = '';
    var imageWidth = '';
/*
* Function to open the post image in colorbox
* @pram {imageName : Name of the image,w : width of image,h : height of image}
*/    
function openImage(imageName, w, h) {
  
    	if(w==0 && h==0) {
		var newImg = new Image();										
		newImg.src = baseUrl+'newsimages/'+imageName;
		w = newImg.width;
		h = newImg.height;
	}
	$("#imagePopup").attr('src', '');
        $('html, body').animate({ scrollTop: 0 }, 0);
        aaa =w;
		if(Number(w) < 400) {
			imageWidth = Number(w)+40;
		} else {
			imageWidth = w;
		}
		if(Number(h) < 400) {
			imageHeight = Number(h)+40;
		} else {
			imageHeight = h;  
		}
    $("#imagePopup").attr('src', baseUrl+"newsimages/"+imageName);
    self.focus();
}

/*
* Function to initialize the map and sub map popup
*/  
$(document).ready(function(){
    initialize();
    $(".Map-Popup-Class1").colorbox({width:"70%",height:"", inline:true, href:"#Map-Popup-id"},function(){
          $('html, body').animate({ scrollTop: 0 }, 0);																										   	
          $("#useNewAddress").show();
          $("#useAddress").hide();
		  $("#searchAddresHome").val('');
	  	  $("#searchCityHome").val('');
		  $("#searchStateHome").val('');
          $("#colorbox").css("top", "85px");
          relatedMap();
     });
    
   $(".Change-Post-Location-Popup-Class").colorbox({width:"70%",height:"", inline:true, href:"#Change-Postlocation-Map-Popup-id"},function(){
          $('html, body').animate({ scrollTop: 0 }, 0);																										   	
          $("#useNewAddress").show();
          $("#useAddress").hide();
		  $("#searchAddresHome").val('');
		  $("#searchCityHome").val('');
		  $("#searchStateHome").val('');
          relatedMap();
     });

 
});

 
/*
* Function to clear the upload div form and iframe
*/      
function clearUpload() {
    $('#fileNm').html("");
    var ufchilds = $("#mainForm").children();
    $(ufchilds[0]).val("");
}


var formContainor = ''; //To store the uploader form parent containor

/*
* Function to initialize image uploader
*/
$(document).ready(function(){
    upclick({
		dataname: 'images',
        element: 'uploader',
        onstart: function(filename) {
            var uploadedImage = filename.split('\\');
            var imageRealName = uploadedImage[uploadedImage.length-1];
            if(((imageRealName.length) - 50)>4){
                var fileExtension = imageRealName.split('.');
                imageRealName = imageRealName.substring(0,50)+"..."+fileExtension[fileExtension.length-1];
            }    
            filename = "<br/><div id='case1' style='margin-left: -25px;margin-top: -9px;'><div style='float:left;font: normal 12px lato-Regular;'>"+imageRealName;
            filename += '</div><div>&nbsp;<img class="image_delete" onclick="clearUpload();" src="'+baseUrl+'www/images/delete-icon.png"/></div></div>';
			$("#fileNm").html(filename);
			filename = uploadedImage[uploadedImage.length-1].split('.');
            var filenameArrayLength = filename.length;
			if(filename[filenameArrayLength-1] == 'jpg' || filename[filenameArrayLength-1] == 'gif' || filename[filenameArrayLength-1] == 'jpeg' || filename[filenameArrayLength-1] == 'png' || filename[filenameArrayLength-1] == 'bmp'|| filename[filenameArrayLength-1] == 'JPG') {
				
			}else {
				alert("Invalid file format");	
				clearUpload();
			}
        }
    });
});

/*
* Function to reinitialize uploader for the next accesss
*/    
function reinitilizeUpload() {
     upclick({
		dataname: 'images',
        element: 'uploader',
        onstart: function(filename) {
            var uploadedImage = filename.split('\\');
            var imageRealName = uploadedImage[uploadedImage.length-1];
            if(((imageRealName.length) - 50)>4){
                var fileExtension = imageRealName.split('.');
                imageRealName = imageRealName.substring(0,50)+"..."+fileExtension[fileExtension.length-1];
            }    
            filename = "<br/><div id='case2' style='margin-left:0px;margin-top:-37px;'><div style='margin-bottom : 2px;float:left;'>"+imageRealName;
            filename += '</div><div>&nbsp;<img onclick="clearUpload();" src="'+baseUrl+'www/images/delete-icon.png" style="cursor:pointer;"/></div></div>';
            $("#fileNm").html(filename);
        }
    });
}

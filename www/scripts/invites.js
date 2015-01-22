var regex=/\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}\b/i;
function validateEmail(field) { 
	return (regex.test(field)) ? true : false;
}

function inviteStatus() { 
	var result = $('#emails').val().split(",");
	for(var i = 0;i < result.length;i++) {
		if(!validateEmail(result[i])) {
			$('.errors').html("Please enter valid comma separated emails<br>").show();
			return false;              
		}
	}
	return true;
}
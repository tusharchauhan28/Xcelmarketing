function sendOTP() {
	$(".error").html("").hide();
	var name = $("#name").val();
	var s_id = $("#s_id").val();
	var email = $("#email").val();
	var number = $("#mobile").val();
	var service = $("#service").val();
	var message = $("#message").val();
	if (number.length == 10 && number != null) {
		var input = {
			"name" : name,
			"s_id" : s_id,
			"email" : email,
			"mobile_number" : number,
			"service" : service,
			"message" : message,
			"action" : "send_otp"
		};
		
		$.ajax({
			url : 'otp/controller.php',
			type : 'POST',
			data : input,
			success : function(response) {
				console.log(response);
				$(".form-box").html(response);
			}
		});
	} else {
		$(".error").html('Please enter a valid number!')
		$(".error").show();
	}
}

function verifyOTP() {
	$(".error").html("").hide();
	$(".success").html("").hide();
	var otp = $("#mobileOtp").val();
	var input = {
		"otp" : otp,
		"action" : "verify_otp"
	};
	if (otp.length == 4 && otp != null) {
		$.ajax({
			url : 'otp/controller.php',
			type : 'POST',
			dataType : "json",
			data : input,
			success : function(response) {
				console.log(response.message);
				$("." + response.type).html(response.message)
				$("." + response.type).show();
				if(response.message=='"Done"'){
				   window.location.href = "https://xcelmarketing.in/thank-you.php";
				}
			},
			error : function() {
				alert("ss");
			}
		});
	} else {
		$(".error").html('You have entered wrong OTP.')
		$(".error").show();
	}
}
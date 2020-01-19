$(document).ready(function() {
	$("#formBuyTicket").submit(function() {
		if(!status_wait) {
			status_wait = true;
			$.ajax({
				type: "POST",
				url: "/func/buy",
				data: {code: $("#FormVoucherCode").val()},
				success: function(result) {
					if(result.status == 'error') {
						alert(result.error);
					} else {
						$("#loading-spinner").show();
						$("#activationStatus").html('Wait. This may take several minutes.');
						last_voucher_id = result.data.id;
						checkVoucherStatus(last_voucher_id);
						intervalID = setInterval(function() {
							checkVoucherStatus(last_voucher_id);
						}, 5000);
					}
				},
				dataType: "json"
			});
		}
		return false;
	});
});

var last_voucher_id = 0;
var intervalID;
var status_wait = false;
function checkVoucherStatus(voucher_id = 1) {
	$.ajax({
		type: "POST",
		url: "/func/check",
		data: {id: voucher_id},
		success: function(result) {
			if(result.status == 'error') {
				alert(result.error);
			} else {
				switch(result.data.status) {
					case 'done':
						//ticket ready
						clearInterval(intervalID);
						$("#loading-spinner").hide();
						$("#activationStatus").html('your ticket code: ' + result.data.ticket_code);
						status_wait = false;
						break;
					case 'error':
						clearInterval(intervalID);
						$("#loading-spinner").hide();
						$("#activationStatus").html('Error: ' + result.data.error);
						break;
				}
			}
		},
		dataType: "json"
	});
}

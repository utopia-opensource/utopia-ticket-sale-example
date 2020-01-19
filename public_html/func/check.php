<?php
	session_start();
	require_once __DIR__ . '/../../vendor/autoload.php';
	
	$logic = new App\Logic();
	$logic->init_client();
	
	$activation_id = App\Utilities::checkINT($_POST['id']);
	if($activation_id == '') {
		$logic->printAPIerror('Activation id not entered');
	}
	
	$activation_data = $logic->checkVoucherActivation($activation_id);
	
	if($activation_data['status'] == 'done') {
		if($activation_data['used'] == '0') {
			if($activation_data['amount'] >= getenv('ticket_price')) {
				//OK!
				$ticket_code = $logic->buyTicketForVoucher($activation_id);
				if($ticket_code == '') {
					$logic->printAPIerror($logic->last_error);
				} else {
					$activation_data['ticket_code'] = $ticket_code;
					$logic->printAPIresult($activation_data);
				}
			} else {
				$logic->printAPIerror('not enought crp in voucher');
				//recreate voucher and return to user?
			}
		} else {
			//voucher has already been used
			$logic->printAPIresult($activation_data);
		}
	} else {
		$logic->printAPIresult($activation_data);
	}
	
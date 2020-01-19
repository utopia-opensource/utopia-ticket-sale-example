<?php
	require_once __DIR__ . '/../../vendor/autoload.php';
	
	$logic = new App\Logic();
	$logic->init_client();
	
	$voucher_code = App\Utilities::data_filter($_POST['code']);
	if($voucher_code == '') {
		$logic->printAPIerror('Voucher code not entered');
	}
	
	//check if there are free tickets
	$status_success = $logic->areThereAnyFreeTickets();
	if(!$status_success) {
		$logic->printAPIerror('Free tickets are over');
	}
	
	//voucher activation and amount verification
	$activation_data = $logic->cryptonat->activateVoucher($voucher_code);
	if($activation_data['referenceNumber'] == '') {
		$logic->printAPIerror('Failed to activate the voucher, the voucher code may have been entered incorrectly');
	}
	$entry_id = $logic->saveVoucherToDB($voucher_code, $activation_data['referenceNumber']);
	if($entry_id > 0) {
		$logic->printAPIresult([
			'result' => 'ok',
			'id'     => $entry_id
		]);
	} else {
		$logic->printAPIerror('Failed to save your voucher. there might be a problem on the server');
	}
	
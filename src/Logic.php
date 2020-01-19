<?php
	namespace App;
	
	class Logic {
		public $cryptonat = null;
		public $last_error = '';
		
		protected $client = null;
		protected $db = null;
		//protected $user = null;
		
		public function __construct() {
			$this->loadEnvironment();
			$this->db = new DataBase();
			//$this->user = new User();
		}
		
		public function init_client() {
			$this->client = new \UtopiaLib\Client(
				getenv('api_token'),
				getenv('api_host'),
				getenv('api_port')
			);
			if(! $this->client->checkClientConnection()) {
				$this->printAPIerror('It looks like a server error has occurred: Unable to connect to the Utopia client. Come back later.');
			}
			$this->cryptonat = new \Cryptonat\Handler($this->client);
		}
		
		function loadEnvironment() {
			$dotenv = \Dotenv\Dotenv::create(__DIR__ . "/../");
			$dotenv->load();
		}
		
		public function printAPIresult($data = []) {
			exit(json_encode([
				'status' => 'success',
				'data'   => $data,
				'error'  => ''
			]));
		}
		
		public function printAPIerror($error = '') {
			exit(json_encode([
				'status' => 'error',
				'data'   => [],
				'error'  => $error
			]));
		}
		
		public function areThereAnyFreeTickets(): bool {
			$sql_query = "SELECT COUNT(*) as tickets_count FROM tickets WHERE is_free='1'";
			$result = $this->db->query2arr($sql_query);
			if($result['tickets_count'] > 0) {
				return true;
			} else {
				return false;
			}
		}
		
		public function saveVoucherToDB($voucher_code = '', $referenceNumber = ''): int {
			//try to find if there is a voucher already recorded
			$sql_query_voucher = "SELECT id FROM vouchers WHERE code='" . $voucher_code . "' LIMIT 1";
			$result = $this->db->query2arr($sql_query_voucher);
			if($result != []) {
				//record found
				return $result['id'];
			}
			//record not found
			$sql_query = "INSERT INTO vouchers SET code='" . $voucher_code . "', referenceNumber='" . $referenceNumber . "'";
			if(! $this->db->tryQuery($sql_query)) {
				//failed to record
				return 0;
			}
			//find record id
			$result = $this->db->query2arr($sql_query_voucher);
			if($result == []) {
				//record not found
				return 0;
			}
			//record found
			return $result['id'];
		}
		
		public function checkVoucherActivation($activation_id = 0): array {
			$activation_data = [
				'status' => 'pending',
				'amount' => 0,
				'used'   => '0',
				'error'  => ''
			];
			if($activation_id == 0) {
				$activation_data['status'] = 'error';
				$activation_data['error']  = 'Invalid activation ID received';
				return $activation_data;
			}
			//find voucher entry
			$sql_query = 'SELECT code,referenceNumber,used FROM vouchers WHERE id=' . $activation_id;
			$result = $this->db->query2arr($sql_query);
			if($result == []) {
				$activation_data['status'] = 'error';
				$activation_data['error']  = 'Voucher not found';
				return $activation_data;
			}
			
			if($result['used'] == '1') {
				$activation_data['status'] = 'error';
				$activation_data['used']   = '1';
				$activation_data['error']  = 'voucher has already been used';
				return $activation_data;
			}
			
			//check voucher status
			$voucher_data = $this->cryptonat->checkVoucherStatus($result['referenceNumber']);
			$activation_data['status'] = $voucher_data['status'];
			$activation_data['amount'] = $voucher_data['amount'];
			if($voucher_data['amount'] > 0) {
				//update voucher entry
				$sql_query = "UPDATE vouchers SET amount=" . $voucher_data['amount'] . " WHERE id=" . $activation_id;
				$this->db->tryQuery($sql_query);
			}
			return $activation_data;
		}
		
		public function buyTicketForVoucher($activation_id = 0): string {
			if($activation_id == 0) {
				$this->last_error = 'Invalid activation ID received';
				return '';
			}
			
			//find entry
			$sql_query = "SELECT id FROM vouchers WHERE id=" . $activation_id;
			if(! $this->db->checkRowExists($sql_query)) {
				$this->last_error = 'Activation not found';
				return '';
			}
			
			//mark the voucher as used
			$sql_query = "UPDATE vouchers SET used='1' WHERE id=" . $activation_id;
			if(! $this->db->tryQuery($sql_query)) {
				$this->last_error = 'Failed to mark voucher as used';
				return '';
			}
			
			//choose an unused ticket
			$sql_query = "SELECT id,code FROM tickets WHERE is_free='1' LIMIT 1";
			$ticket_data = $this->db->query2arr($sql_query);
			if($ticket_data == []) {
				$this->last_error = 'Failed to select ticket';
				return '';
			}
			
			//mark the ticket as busy
			$sql_query = "UPDATE tickets SET is_free='0', voucher_id=" . $activation_id . ", buy_date=NOW() WHERE id=" . $ticket_data['id'];
			if(! $this->db->tryQuery($sql_query)) {
				$this->last_error = 'Failed to mark ticket as used';
				return '';
			}
			
			return $ticket_data['code'];
		}
	}
	
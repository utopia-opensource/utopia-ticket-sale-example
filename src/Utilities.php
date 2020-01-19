<?php
	namespace App;
	
	class Utilities {
		function data_filter($string = "", $db_link = null) {
			//\App\Model\Utilities::data_filter
			$string = strip_tags($string);
			$string = stripslashes($string);
			$string = htmlspecialchars($string);
			$string = trim($string);
			if(isset($db_link) && $db_link != null) {
				$string = $db_link->filter_string($string);
			}
			return $string;
		}
		
		function checkINT($value = 0, $db_link = null): int {
			//\App\Utilities::checkINT
			if($value == '') {
				return 0;
			}
			$value = Utilities::data_filter($value, $db_link) + 0;
			if(!is_int($value)) {
				$value = 0;
			}
			return $value;
		}
		
		function generateCode($length = 6): string {
			// \App\Model\Utilities::generateCode
			$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPRQSTUVWXYZ0123456789";
			$code = "";
			$clen = strlen($chars) - 1;
			while (strlen($code) < $length) {
				$code .= $chars[mt_rand(0, $clen)];
			}
			return $code;
		}
	}
	
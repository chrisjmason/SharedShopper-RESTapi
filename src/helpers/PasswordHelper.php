<?php

class PasswordHelper{

	public static function hashPassword($password){

		$fp = fopen('passwordcheck.txt', 'w');
    	fwrite($fp, password_hash($password, PASSWORD_BCRYPT));
    	fclose($fp);

    	return password_hash($password, PASSWORD_BCRYPT);

		
	}

	public static function checkPassword($password, $hash){
		return password_verify($password, $hash);
	}
}

?>
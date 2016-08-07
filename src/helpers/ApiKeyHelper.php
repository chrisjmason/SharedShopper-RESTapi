<?php

class ApiKeyHelper{
	public static function generateApiKey(){
		return uniqid(rand(), true);
	}
}

?>

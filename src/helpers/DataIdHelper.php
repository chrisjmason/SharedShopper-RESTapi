<?php
	
	class DataIdHelper{
		public static function generateDataId(){
			$dataId = substr(sha1(mt_rand() . microtime()), mt_rand(0,35), 6);
			return $dataId;
		}
	}

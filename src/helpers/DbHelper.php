<?php

class DbHelper{
	private $conn;

	function __construct(){
		require_once dirname(__FILE__) .'db/DbConnect.php';
		$db = new DbConnect();
		$this->conn = $db->connect();
	}


	public function createUser($username, $password){
		require_once 'PasswordHelper.php';
		require_once 'ApiKeyHelper.php';
		require_once 'DataIdHelper.php';

		if(!$this->userExists($username)){
			$passwordHash = PasswordHelper::hashPassword($password);
			$apikey = ApiKeyHelper::generateApiKey();
			$dataid = DataIdHelper::generateDataId();

			$stmt = $this->conn->prepare("INSERT INTO `users` (`username`, `passwordhash`, `apikey`,`dataid`) VALUES (?,?,?,?)");
			$stmt->bind_param("ssss", $username,$passwordHash,$apikey,$dataid);
		
			$result = $stmt->execute();
			$stmt->close();

			$result = array();

			if(result){
				$result['status'] = USER_CREATED;
				$result['apikey'] = $apikey;
				$result['dataid'] = $dataid;
			}else{
				$result['status'] = USER_NOT_CREATED;
			}
		}else{
			$result['status'] = USER_ALREADY_EXISTS;
		}
		return $result;
	}

	public function checkLogin($username, $password){
		require_once 'PasswordHelper.php';

		$stmt = $this->conn->prepare("SELECT passwordhash FROM users WHERE username = ?");
		$stmt->bind_param("s", $username);
		$stmt->execute();
		$stmt->store_result();

		$num_rows = $stmt->num_rows;

		$stmt->bind_result($passwordhash);
		
		if($num_rows > 0){
			$stmt->fetch();
			if(PasswordHelper::checkPassword($password,$passwordhash)){
				return TRUE;
			}else{
				return FALSE;
			}
		}else{
			return FALSE;
		}
		$stmt->close();
	}

	public function checkApikey($apikey){
		$stmt = $this->conn->prepare("SELECT username from `users` WHERE apikey = ?");
		$stmt->bind_param("s",$apikey);
		$stmt->execute();

		$stmt->store_result();
		$num_rows = $stmt->num_rows;
		$stmt->close();
		return $num_rows > 0;
	}

	public function getUser($username){
		$stmt = $this->conn->prepare("SELECT apikey, dataid, datecreated from users WHERE username = ?");
		$stmt->bind_param("s", $username);
		$stmt->execute();

		if($stmt->execute()){
			$stmt->bind_result($apikey,$dataid,$datecreated);
			$stmt->fetch();

			$user = array();
			$user['apikey'] = $apikey;
			$user['dataid'] = $dataid;

			return $user;
		}else{
			return NULL;
		}
	}

	public function userExists($username){
		$stmt = $this->conn->prepare("SELECT id from `users` WHERE username = ?");
		$stmt->bind_param("s",$username);
		$stmt->execute();

		$stmt->store_result();
		$num_rows = $stmt->num_rows;
		$stmt->close();
		return $num_rows > 0;
	}


	public function addItem($title, $desc, $colour,$date, $code, $dataid){
		$stmt = $this->conn->prepare("INSERT INTO `items` (`title`, `description`, `colour`, `date`, `itemcode`, `dataid`) VALUES (?,?,?,?,?,?)");
		$stmt->bind_param("ssisss",$title, $desc,$colour,$date,$code,$dataid);
		$result = $stmt->execute();
		$stmt->close();
	}

	public function getAllItems($dataid){
		$sql = "SELECT `id`, `title`, `description`, `colour`, `date`, `itemcode`, `dataid` FROM `items` WHERE `dataid` = '$dataid'  ORDER BY `date` ASC";
		$result = $this->conn->query($sql);
		return $result;
	}

	public function deleteItem($code){
		$stmt = $this->conn->prepare("DELETE FROM `items` WHERE `itemcode` = ?");
		$stmt->bind_param("s",$code);
		$stmt->execute();
		$stmt->close();
	}

	public function updateDataid($dataid, $apikey){
		if($this->checkDataid($dataid)){
			$stmt = $this->conn->prepare("UPDATE `users` SET `dataid` = ? WHERE `apikey` = ?");
			$stmt->bind_param("ss",$dataid,$apikey);
			$result = $stmt->execute();
			$stmt->close();
			return $result;
		}else{
			return false;
		}
	}

	public function checkDataid($dataid){
		$stmt = $this->conn->prepare("SELECT username from `users` WHERE dataid = ?");
		$stmt->bind_param("s",$dataid);
		$stmt->execute();

		$stmt->store_result();
		$num_rows = $stmt->num_rows;
		$stmt->close();
		return $num_rows > 0;
	}
}
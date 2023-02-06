<?php
class MyPDO extends PDO
{
	public $db_name;
    public function __construct($file = 'my_setting.ini')
    {
        if (!$settings = parse_ini_file( dirname(__FILE__) . '/' .$file, TRUE)){
        	throw new exception(  'Unable to open '. dirname(__FILE__) . '/' . $file . '.');
        }
       
        $dns = $settings['database']['driver'] .
        ':host=' . $settings['database']['host'] .
        ((!empty($settings['database']['port'])) ? (';port=' . $settings['database']['port']) : '') .
        ';dbname=' . $settings['database']['schema'];
       	// var_dump($settings);

		$options = [];
		if(isset($settings['database']['strict']) && $settings['database']['strict'] == true){
			$options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))";
		}

		$this->db_name = $settings['database']['schema'];
        parent::__construct($dns, $settings['database']['username'], $settings['database']['password'], $options);
    }
}

if(!function_exists("returnSingleField")){
	function returnSingleField(&$pdo, $query, $field, $params = null){
		$statement = $pdo->prepare($query);
		if(is_null($params)){
			$statement->execute();
		} elseif (is_array($params)) {
			$statement->execute($params);
		} else{
			throw new Exception("params should be null or array ".gettype($params)." is found.", 1);
		}

		$row = $statement->fetch(PDO::FETCH_ASSOC);
		if(!is_array($row)){
			return null;
		}
		if(array_key_exists($field, $row)){
			return $row[$field];
		}
		// var_dump($row, $query, $params);
		throw new Exception("Requested columns '".$field."' is not in the selected columns", 1);
	}
}

if(!function_exists("returnAllData")){
	function returnAllData(&$pdo, $query, $params=null){
		// var_dump($query);
		// echo $query. implode(" ", $params);
		try{
			// var_dump($query);
			$statement = $pdo->prepare($query);
			if(is_null($params)){
				$statement->execute();
			} elseif (is_array($params)) {
				$statement->execute($params);
			} else{
				throw new Exception("params should be null or array ".gettype($params)." is found.", 1);
			}

			return $statement->fetchAll(PDO::FETCH_ASSOC);
		} catch(Exception $e){
			throw new Exception($e->getMessage(), 1);
			
		}
	}
}

if(!function_exists("first")){
	function first($pdo, $query, $params=null){
		try{
			$statement = $pdo->prepare($query);
			if(is_null($params)){
				$statement->execute();
			} elseif (is_array($params)) {
				$statement->execute($params);
			} else{
				throw new Exception("params should be null or array ".gettype($params)." is found.", 1);
			}
			// var_dump($query, $params);
			return $statement->fetch(PDO::FETCH_ASSOC);

			// var_dump($row);
		} catch(\Exception $e){
			throw new Exception($e->getMessage(), 1);
		}
	}
}

if(!function_exists("saveData")){
	function saveData($pdo, $query, $params = null){
		try{
			$statement = $pdo->prepare($query);
			if(is_null($params)){
				$statement->execute();
			} elseif (is_array($params)) {
				$statement->execute($params);
			} else{
				throw new Exception("params should be null or array ".gettype($params)." is found.", 1);
			}
		} catch(\Exception $e){
			// var_dump($params);
			// throw new Exception($e->getMessage()." ".$query." ".implode(",", $params), 1);
			throw new Exception($e->getMessage(), 1);
			
		}
		return true;
	}
}

if(!function_exists("saveAndReturnID")){
	function saveAndReturnID(&$pdo, $query, $params = null){
		try{
			$statement = $pdo->prepare($query);

			if(is_null($params)){
				$statement->execute();
			} elseif (is_array($params)) {
				$statement->execute($params);
			} else{
				throw new Exception("params should be null or array ".gettype($params)." is found.", 1);
			}

			return $pdo->lastInsertId();
		} catch(\Exception $e){
			// var_dump($params);
			// throw new Exception($e->getMessage()." ".$query." ".implode(",", $params), 1);
			throw new Exception( sprintf("%s with query strin of %s ", $e->getMessage(), $query), 1);
		}
		return null;
	}
}

if(!function_exists("insertOrReturnID")){
	function insertOrReturnID(&$pdo, $sql1, $sql2, $field, $params=null, $params2 = null){
		
		$check = returnSingleField($pdo, $sql2,$field,$params2);
		if($check){
			return $check;
		}
		return saveAndReturnID($pdo, $sql1, $params);
	}
}

if(!function_exists("isDataExist")){
	function isDataExist(&$pdo, $query, $params=null){
		$statement = $pdo->prepare($query);
		if(is_null($params)){
			$statement->execute();
		} elseif (is_array($params)) {
			$statement->execute($params);
		} else{
			throw new Exception("params should be null or array ".gettype($params)." is found.", 1);
		}
		// var_dump($query, $params);
		return count($statement->fetchAll(PDO::FETCH_ASSOC));
	}
}

if(!function_exists("isTableDefined")){
	function isTableDefined(&$pdo, $table){
		try{
			$statement = $pdo->query("DESCRIBE ?");
			$statement->execute($table);
		} catch(\Exception $e){
			throw new Exception(sprintf("unable to execute the requested task %s", $e->getMessage()));
		}

		return count($statement->fetchAll(PDO::FETCH_ASSOC));
	}
}

if(!function_exists("execute_statement")){
	function execute_statement(&$pdo, $query){
		try{
			$statement = $pdo->query($query);
			$statement->execute();
		} catch(\Exception $e){
			throw new Exception(sprintf("unable to execute the requested task %s the query was %s", $e->getMessage(), $query));
		}
	}
}

if(!function_exists("RoundUp")){
	function RoundUp($value, $check=5){
		$value = round($value, 0);
		return ($value + (($value%$check)?($check - ($value%$check)):0) );
	}
}

if(!function_exists("RoundDown")){
	function RoundDown($value, $check=5){
		return $value - ($value%$check);
	}
}


$db = new MyPDO("my_setting.ini");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


$db_ref = new MyPDO("my_setting_refe.ini");
$db_ref->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$db_corunum = new MyPDO("my_setting_corunum.ini");
$db_corunum->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$db_temp = new MyPDO("temp_db.ini");
$db_temp->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
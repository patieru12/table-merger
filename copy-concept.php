<?php

require_once("db_config.php");

//Get concept class from reference application which are not registered in openmrs_corunum

$query1 = "SELECT * FROM `{$db_ref->db_name}`.`concept_class` WHERE uuid NOT IN (SELECT uuid FROM `{$db_corunum->db_name}`.`concept_class`)";
$query2 = "SELECT * FROM `{$db_ref->db_name}`.`concept` WHERE uuid NOT IN (SELECT uuid FROM `{$db_corunum->db_name}`.`concept`)";

// echo $query1.PHP_EOL;
echo $query2.PHP_EOL;
// execute_statement($db_corunum, "SET FOREIGN_KEY_CHECKS=0");
$db_corunum->beginTransaction();
$executed=0;
try{
    $concept_info = returnALlData($db_ref, $query2);

    if(count($concept_info) > 0){
        //Here make sure all table got data
        $sql = "SELECT DISTINCT table_name AS foreign_table, COLUMN_NAME, '>-' AS rel, concat(referenced_table_schema, '.', referenced_table_name) AS primary_table, referenced_table_name, REFERENCED_COLUMN_NAME FROM information_schema.key_column_usage WHERE referenced_table_name = 'concept' AND table_schema = '{$db_corunum->db_name}' AND table_name LIKE '%concept%' ORDER BY foreign_table";
        // echo $sql.PHP_EOL; die();
        $foreign_info = returnAllData($db_corunum, $sql);

        // print_r($foreign_info);
        foreach($concept_info AS $concept_record){
            //Now check if the concept will cause any duplicate
            $check = "SELECT * FROM `{$db_corunum->db_name}`.`concept` WHERE concept_id = ?";
            $data = returnSingleField($db_corunum, $check, "concept_id", [$concept_record['concept_id']]);

            // var_dump($check, $concept_record['concept_id'], $data);
            if(is_null($data)){
                //Here we can create all the concept without any problem
                continue;
                //USe insert into select statement to complete the request
                // $query_7 = "INSERT INTO `{$db_corunum->db_name}`.`concept` SELECT * FROM `{$db_ref->db_name}`.`concept` WHERE `concept_id` = '{$concept_record['concept_id']}'";
                // $query_7 = "INSERT INTO `{$db_corunum->db_name}`.`concept` SELECT * FROM `{$db_ref->db_name}`.`concept` WHERE `concept_id` = '{$concept_record['concept_id']}'";
                $query_7 = "INSERT INTO `{$db_corunum->db_name}`.`concept` SET ";
                $column_data = [];
                $concept_id = null;
                $uuid = null;
                foreach($concept_record AS $field=>$column_value){
                    if($field == "concept_id"){
                        $concept_id = $column_value;
                        // continue;
                    }
                    if($field == "uuid"){
                        // $query_7 .= ", uuid='EMPTY'";
                        $uuid = $column_value;
                        // continue;
                    }
                    if(count($column_data) > 0){
                        $query_7 .= ", ";
                    }
                    // var_dump($field, $column_value);
                    $query_7 .= "`{$field}` = ".((is_null($column_value))?"NULL":"'{$column_value}'");
                    $column_data[] = $column_value;
                }
                echo $query_7."; ".PHP_EOL;
                // echo $executed++ .". ".$query_7.PHP_EOL;
                // execute_statement($db_corunum, $query_7);
                // echo "ERRORORROOROROROROOROROOR!!!!!!!!!!!!!!!!!!!!";
                // saveData($db_corunum, "UPDATE `{$db_corunum}`.concept SET concept_id = ?, uuid=? WHERE concept_id = ?", [$concept_id, $uuid,$db_corunum->lastInsertId()]);
                foreach($foreign_info AS $table_info){
                    if(in_array($table_info['foreign_table'], ['concept_word'])){
                        continue;
                    }
                    // echo "Checking ".$table_info['foreign_table']." For the concept_id of:".$concept_record['concept_id'].PHP_EOL;

                    $query_5 = "SHOW INDEX FROM `{$table_info['foreign_table']}` WHERE Key_name = 'PRIMARY'";
                    $primary_key = returnSingleField($db_corunum, $query_5, "Column_name");

                    $query_3 = "SELECT * FROM `{$db_ref->db_name}`.`{$table_info['foreign_table']}` WHERE `{$table_info['COLUMN_NAME']}`=?";

                    $data_in_table = returnAllData($db_ref, $query_3, [$concept_record['concept_id']]);

                    // print_r($data_in_table);
                    if($data_in_table){

                        // print_r($data_in_table);
                        foreach($data_in_table AS $single_row_data){
                            //Now wi have to handle the data creation process
                            $query_4 = "SELECT * FROM `{$db_corunum->db_name}`.`{$table_info['foreign_table']}` WHERE `{$primary_key}`= ?";
                            // echo $query_4.PHP_EOL;
                            $primary_key_exists = returnSingleField($db_corunum, $query_4, $primary_key, [$single_row_data[$primary_key]]);

                            // var_dump($primary_key_exists);
                            if(is_null($primary_key_exists)){
                                continue;
                                //Now use insert select into statement
                                // $query_6 = "INSERT INTO `{$db_corunum->db_name}`.`{$table_info['foreign_table']}` SELECT * FROM `{$db_ref->db_name}`.`{$table_info['foreign_table']}` WHERE `{$primary_key}` = '{$single_row_data[$primary_key]}'";
                                $query_6 = "INSERT INTO `{$db_corunum->db_name}`.`{$table_info['foreign_table']}` SET ";
                                $column_data_ = [];
                                $key_value = null;
                                $uuid = null;
                                // print_r($single_row_data);
                                foreach($single_row_data AS $field=>$column_value){
                                    if($field == $primary_key){
                                        $key_value = $column_value;
                                        // continue;
                                    }
                                    if($field == 'uuid'){
                                        // $query_6 .= ", uuid='EMPTY'";
                                        $key_value = $column_value;
                                        // continue;
                                    }
                                    if(count($column_data_) > 0){
                                        $query_6 .= ", ";
                                    }
                                    // var_dump($query_6, $field);

                                    $query_6 .= "`{$field}` = ".((is_null($column_value))?"NULL":"'{$column_value}'");
                                    $column_data_[] = $column_value;
                                }
                                echo $query_6."; ".PHP_EOL;
                                // execute_statement($db_corunum, $query_6);
                                // saveData($db_corunum, "UPDATE `{$db_corunum->db_name}`.`{$table_info['foreign_table']}` SET `$primary_key`=?, uuid=? WHERE $primary_key = ?", [$key_value, $uuid, $db_corunum->lastInsertId()]);
                            } else {
                                //Build the create statement
                            }
                        }
                    }
                }
            } else {
                //Here we shall add the new concept after all records
                $query_7 = "INSERT INTO `{$db_corunum->db_name}`.`concept` SET ";
                $column_data = [];
                $concept_id = null;
                $concept_id_new = null;
                $uuid = null;
                foreach($concept_record AS $field=>$column_value){
                    if($field == "concept_id"){
                        $concept_id = $column_value;
                        continue;
                    }
                    if($field == "uuid"){
                        // $query_7 .= ", uuid='EMPTY'";
                        $uuid = $column_value;
                        // continue;
                    }

                    if($field == "class_id" && $column_value == 25){
                        $column_value = 21;
                    }
                    if(count($column_data) > 0){
                        $query_7 .= ", ";
                    }
                    // var_dump($field, $column_value);
                    $query_7 .= "`{$field}` = ".((is_null($column_value))?"NULL":"'{$column_value}'");
                    $column_data[] = $column_value;
                }
                echo $query_7."; ".PHP_EOL;

                $concept_id_new = saveAndReturnID($db_corunum, $query_7);
                foreach($foreign_info AS $table_info){
                    if(in_array($table_info['foreign_table'], ['concept_word'])){
                        continue;
                    }
                    // echo "Checking ".$table_info['foreign_table']." For the concept_id of:".$concept_record['concept_id'].PHP_EOL;

                    $query_5 = "SHOW INDEX FROM `{$table_info['foreign_table']}` WHERE Key_name = 'PRIMARY'";
                    $primary_key = returnSingleField($db_corunum, $query_5, "Column_name");

                    $query_3 = "SELECT * FROM `{$db_ref->db_name}`.`{$table_info['foreign_table']}` WHERE `{$table_info['COLUMN_NAME']}`=?";

                    $data_in_table = returnAllData($db_ref, $query_3, [$concept_record['concept_id']]);

                    // print_r($data_in_table);
                    if($data_in_table){

                        // print_r($data_in_table);
                        foreach($data_in_table AS $single_row_data){
                            //Now wi have to handle the data creation process
                            $query_4 = "SELECT * FROM `{$db_corunum->db_name}`.`{$table_info['foreign_table']}` WHERE `{$primary_key}`= ?";
                            // echo $query_4.PHP_EOL;
                            $primary_key_exists = returnSingleField($db_corunum, $query_4, $primary_key, [$single_row_data[$primary_key]]);

                            // var_dump($primary_key_exists);
                            if(is_null($primary_key_exists)){
                                continue;
                                //Now use insert select into statement
                                // $query_6 = "INSERT INTO `{$db_corunum->db_name}`.`{$table_info['foreign_table']}` SELECT * FROM `{$db_ref->db_name}`.`{$table_info['foreign_table']}` WHERE `{$primary_key}` = '{$single_row_data[$primary_key]}'";
                                $query_6 = "INSERT INTO `{$db_corunum->db_name}`.`{$table_info['foreign_table']}` SET ";
                                $column_data_ = [];
                                $key_value = null;
                                $uuid = null;
                                // print_r($single_row_data);
                                $ignore = false;
                                foreach($single_row_data AS $field=>$column_value){
                                    if($field == $primary_key){
                                        $key_value = $column_value;
                                        // continue;
                                    }

                                    
                                    if($field == 'uuid'){
                                        //check if the uuid had somedata 
                                        $uuid_data = returnSingleField($db_corunum, $info_query = "SELECT * FROM `{$db_corunum->db_name}`.`{$table_info['foreign_table']}` WHERE uuid = ?", 'uuid', [$column_value]);
                                        // $query_6 .= ", uuid='EMPTY'";
                                        echo $uuid_data." ==> ".$info_query." ".$column_value.PHP_EOL;
                                        if($uuid_data){
                                            $ignore = true;
                                            break;
                                        }
                                        $uuid = $column_value;
                                        // continue;
                                    }
                                    if(count($column_data_) > 0){
                                        $query_6 .= ", ";
                                    }
                                    // var_dump($query_6, $field);
                                    if($field == $table_info['COLUMN_NAME']){
                                        $column_value = $concept_id_new;
                                    }
                                    $query_6 .= "`{$field}` = ".((is_null($column_value))?"NULL":"'{$column_value}'");
                                    $column_data_[] = $column_value;
                                }
                                if(!$ignore) {
                                    echo $query_6."; ".PHP_EOL;
                                    execute_statement($db_corunum, $query_6);
                                }
                                // saveData($db_corunum, "UPDATE `{$db_corunum->db_name}`.`{$table_info['foreign_table']}` SET `$primary_key`=?, uuid=? WHERE $primary_key = ?", [$key_value, $uuid, $db_corunum->lastInsertId()]);
                            } else {
                                //Here now make to build the query

                                $query_6 = "INSERT INTO `{$db_corunum->db_name}`.`{$table_info['foreign_table']}` SET ";
                                $column_data_ = [];
                                $key_value = null;
                                $uuid = null;
                                $ignore = false;
                                // print_r($single_row_data);
                                foreach($single_row_data AS $field=>$column_value){
                                    if($field == $primary_key){
                                        $key_value = $column_value;
                                        continue;
                                    }

                                    
                                    if($field == 'uuid'){
                                        // $query_6 .= ", uuid='EMPTY'";
                                        $uuid_data = returnSingleField($db_corunum, $info_query = "SELECT * FROM `{$db_corunum->db_name}`.`{$table_info['foreign_table']}` WHERE uuid = '{$column_value}'", 'uuid');
                                        // $query_6 .= ", uuid='EMPTY'";
                                        echo $uuid_data." ||==> ".$info_query." ".$column_value.PHP_EOL;
                                        if($uuid_data){
                                            $ignore = true;
                                            break;
                                        }
                                        $uuid = $column_value;
                                        // continue;
                                    }
                                    if(count($column_data_) > 0){
                                        $query_6 .= ", ";
                                    }
                                    // var_dump($query_6, $field);
                                    if($field == $table_info['COLUMN_NAME']){
                                        echo "OLD DB: ".$column_value.PHP_EOL;
                                        $column_value = $concept_id_new;
                                    }
                                    $query_6 .= "`{$field}` = ".((is_null($column_value))?"NULL":"'{$column_value}'");
                                    $column_data_[] = $column_value;
                                }
                                if(!$ignore) {
                                    echo $query_6."; ;;;;;;;;;;;;;;;;;;;;;;;;".PHP_EOL;
                                    execute_statement($db_corunum, $query_6);
                                }
                            }
                        }
                    }
                }
            }
            echo PHP_EOL.PHP_EOL;
        }

    }
    if($db_corunum->inTransaction()){
        $db_corunum->rollback();
    }
} catch(\Exception $e){
    if($db_corunum->inTransaction()){
        $db_corunum->rollback();
    }
    echo $e->getMessage();
}
// execute_statement($db_corunum, "SET FOREIGN_KEY_CHECKS=1");
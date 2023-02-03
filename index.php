<?php
require_once("db_config.php");

//try to drop temp database
// execute_statement($db_temp, "DROP DATABASE `merged_data`; CREATE DATABASE `merged_data`;");

$table_to_manupulate = ["concept", "form", "encounter_type"];
$synced_tables = [];

$table_list_data = "";

foreach($table_to_manupulate AS $table){
    // echo "master table: ".$table.PHP_EOL;
    $query = "CREATE TABLE IF NOT EXISTS `{$table}` LIKE `{$db_ref->db_name}`.`{$table}`;";
    // echo $query.PHP_EOL;
    execute_statement($db_temp, $query);

    //Copy Data FROM REference database to temp table for later reference
    if(!in_array($table, $synced_tables)){
        $query = "INSERT INTO `{$db_temp->db_name}`.`{$table}` SELECT * FROM `{$db_ref->db_name}`.`{$table}`";
        echo $query.PHP_EOL;
        // execute_statement($db_temp, $query);    
        $synced_tables[] = $table;
        $table_list_data .= " ".$table;
    }
    // echo $query.PHP_EOL;
    //Make sure to ge the list of all table related to the concept table
    $sql = "select distinct table_name as foreign_table, COLUMN_NAME, '>-' as rel, concat(referenced_table_schema, '.', referenced_table_name) as primary_table, REFERENCED_COLUMN_NAME FROM information_schema.key_column_usage where referenced_table_name = '{$table}' and table_schema = '{$db_ref->db_name}' order by foreign_table";


    $table_list = returnAllData($db_ref, $sql);

    //Create all table in tempt_db
    // var_dump($table_list);
    if(is_array($table_list)){
        foreach($table_list AS $data){
            //Create a query to create a table in temp_database with the schema from reference application
            $query = "CREATE TABLE IF NOT EXISTS `{$data['foreign_table']}` LIKE `{$db_ref->db_name}`.`{$data['foreign_table']}`;";
            execute_statement($db_temp, $query);

            //Copy Data FROM REference database to temp table for later referenceif(!in_array($table, $synced_tables)){
            if(!in_array($data['foreign_table'], $synced_tables)){
                // execute_statement($db_temp, "SET foreign_key_checks = 0");
                $query = "INSERT INTO `{$db_temp->db_name}`.`{$data['foreign_table']}` SELECT * FROM `{$db_ref->db_name}`.`{$data['foreign_table']}`";
                echo implode( ", ", $synced_tables)." ==> ".$query.PHP_EOL;
                // execute_statement($db_temp, $query);
                // execute_statement($db_temp, "SET foreign_key_checks = 1");
                $synced_tables[] = $data['foreign_table'];
                $table_list_data .= " ".$data['foreign_table'];
            }
            // echo $query.PHP_EOL;
        }
    }
    echo PHP_EOL;
}

echo $table_list_data;
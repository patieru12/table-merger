<?php
require_once("db_config.php");

//try to drop temp database
// execute_statement($db_temp, "DROP DATABASE `merged_data`; CREATE DATABASE `merged_data`;");

$table_to_manupulate = ["concept_class", "concept", "form", "encounter_type"];
$primary_key = ["concept_class" => "concept_class_id", "concept" => "concept_id", "form" => "form_id", "encounter_type" => "encounter_type_id"];
$new_primary_key_value = [
    "concept" => [],
    "form" => [],
    "encounter_type" => [],
];
$synced_tables = [];

$table_list_data = "";
execute_statement($db_temp, "SET FOREIGN_KEY_CHECKS=0");
$db_temp->beginTransaction();
try{
    //disable foreign key for now
    foreach($table_to_manupulate AS $table){
        // echo "master table: ".$table.PHP_EOL;
        $query = "SELECT * FROM `{$db_corunum->db_name}`.`{$table}` WHERE uuid NOT IN (SELECT uuid FROM `{$db_temp->db_name}`.`{$table}`)";
        // $query = "SELECT * FROM `{$db_temp->db_name}`.`{$table}` WHERE uuid NOT IN (SELECT uuid FROM `{$db_corunum->db_name}`.`{$table}`)";
        // echo $query.PHP_EOL;
        $concept_data = returnAllData($db_temp, $query);
        var_dump($query, count($concept_data));// die();

        $insert_query = "INSERT INTO `{$db_temp->db_name}`.`{$table}` SET ";
        
        foreach($concept_data AS $single_row){
            // echo $single_row[$primary_key[$table]]."...................>";
            $field_counter = 0;
            $data_query = $insert_query;
            $query_params = [];

            //if the target table is name  handle the speciual case for it
            
            foreach($single_row AS $field => $field_value){
                if($primary_key[$table] == $field){
                    continue;
                }
                if($field_counter++ > 0){
                    $data_query .= ", ";
                }
                $data_query .= "`{$field}`=?";
                $query_params[] = $field_value;
            }
            echo $data_query.PHP_EOL;
            print_r($query_params);

            //Here Execute the query right now
            $new_id = 0; //saveAndReturnId($db_temp, $data_query, $query_params);

            //now keep the new ID
            $new_primary_key_value[$table][$single_row[$primary_key[$table]]] = $new_id;
            // echo $new_id.PHP_EOL;
        }

        // print_r($new_primary_key_value);

        //Make sure to ge the list of all table related to the concept table
        $sql = "select distinct table_name as foreign_table, COLUMN_NAME, '>-' as rel, concat(referenced_table_schema, '.', referenced_table_name) as primary_table, referenced_table_name, REFERENCED_COLUMN_NAME FROM information_schema.key_column_usage where referenced_table_name = '{$table}' and table_schema = '{$db_ref->db_name}' order by foreign_table";


        $table_list = returnAllData($db_ref, $sql);

        //Create all table in tempt_db
        // var_dump($table_list);
        if(is_array($table_list)){
            foreach($table_list AS $table_info){
                // print_r($table_info);
                foreach($new_primary_key_value[$table_info['REFERENCED_TABLE_NAME']] AS $old_id => $new_id){
                    if($table_info['REFERENCED_TABLE_NAME'] == "htmlformentry_html_form"){
                        //Make sure to save information from reference application to corunum datab
                        $ref_forms = "SELECT * FROM `{$db_corunum}`.``";
                    } else {
                        //build the update query
                        $update_query = "UPDATE `{$db_temp->db_name}`.`{$table_info['foreign_table']}` SET `{$table_info['COLUMN_NAME']}` = ? WHERE `` = ?";

                        $data_query_info = [$new_id, $old_id];

                        // echo $update_query;
                        // print_r($data_query_info);

                    }
                }
            }
        }
        if($db_temp->inTransaction())
            $db_temp->rollback();
        // echo "Done!".PHP_EOL;
    }
} catch(\Exception $e){
    if($db_temp->inTransaction())
        $db_temp->rollback();
    print_r(["status" => "Failed", "message" => $e->getMessage()]);
}

execute_statement($db_temp, "SET FOREIGN_KEY_CHECKS=1");
echo $table_list_data;
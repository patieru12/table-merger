<?php
require_once("db_config.php");

//Get all table related to user table
$sql = "select distinct table_name as foreign_table, COLUMN_NAME, '>-' as rel, concat(referenced_table_schema, '.', referenced_table_name) as primary_table, referenced_table_name, REFERENCED_COLUMN_NAME FROM information_schema.key_column_usage where referenced_table_name = 'users' and table_schema = '{$db->db_name}' order by foreign_table";

$table_list = returnAllData($db_ref, $sql);

// var_dump($table_list);
$user_to_be_fixed = "62";
foreach($table_list AS $single_table){
    $query = "UPDATE `{$db->db_name}`.`{$single_table['foreign_table']}` SET `{$single_table['COLUMN_NAME']}`=1 WHERE `{$db->db_name}`.`{$single_table['foreign_table']}`='{$user_to_be_fixed}';";

    echo $query.PHP_EOL;
}
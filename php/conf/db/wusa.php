<?php
$config = array();
$config['db']['adapterNamespace'] = "Tomato_Db";
$config['db']['adapter'] = "mysqli";
$config['db']['prefix'] = "";

$config['db']['global']['dbname'] = 'wusa';
$config['db']['global']['username'] = 'wusa';
$config['db']['global']['password'] = '';
$config['db']['global']['charset'] = 'utf8';

/**
 * Master DB Virtuelle IP-Adresse
 */
$config['db']['master']['default'] = "master0";

/**
 * Slave DBs
 * vorerst nur localhost, 10.232.190.41 und 10.232.190.51
 */
$config['db']['slave']['default'] = 'slave1';


$config['db']['master']['master0']['host'] = "127.0.0.1";
$config['db']['master']['master0']['port'] = "3306";
$config['db']['master']['master0']['dbname'] = $config['db']['global']['dbname'];
$config['db']['master']['master0']['username'] = $config['db']['global']['username'];
$config['db']['master']['master0']['password'] = $config['db']['global']['password'];
$config['db']['master']['master0']['charset'] = $config['db']['global']['charset'];


$config['db']['slave']['slave1']['host'] = "127.0.0.1";
$config['db']['slave']['slave1']['port'] = "3306";
$config['db']['slave']['slave1']['dbname'] = $config['db']['global']['dbname'];
$config['db']['slave']['slave1']['username'] = $config['db']['global']['username'];
$config['db']['slave']['slave1']['password'] = $config['db']['global']['password'];
$config['db']['slave']['slave1']['charset'] = $config['db']['global']['charset'];

return $config;
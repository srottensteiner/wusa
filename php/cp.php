<?php
ini_set('display_errors',1);
error_reporting(E_ALL);
require_once("library/autoload.inc.php");
define('PORTAL_SHORT','COUNTER');
//Autoloader
Wusa_Counter::count();

// Bild ausgeben :)
// Header('Content-Type: image/gif');
// echo base64_decode('R0lGODlhAwABAIABALCvn////yH5BAEAAAEALAAAAAADAAEAAAICRFIAOw==');

//register_shutdown_function(array('Tk_App_Counter','count'));
/*
function doIt() {
require_once("/appl/www/library/autoload.inc.php");
    $db = Tomato_Db::factory('master','statistik');
    
    $mapping = array('_wudp' => 'url','_wuut'=>'uzeitpunkt','_wuhn' => 'domain','_wudr'=>'referer','_wucid' => 'session');
    
    $values = array('domain','url','uzeitpunkt','referer','session');
    
    
    $data = array(
            'ip' => $_SERVER['REMOTE_ADDR'],
            'useragent' => $_SERVER['HTTP_USER_AGENT'],
            'uzeitpunkt' => NULL,
            'szeitpunkt' => date('Y-m-d H:i:s'),
            'domain' => '',
            'url' => '',
            'referer' => NULL,
            'session' => NULL,
            );
    
    foreach ($mapping as $valname => $key)
    {
        if(array_key_exists($valname,$_REQUEST))
        {
            $data[$key] = trim($_REQUEST[$valname]);
        }
    }
    
    foreach ($values as $key)
    {
        if(array_key_exists($key,$_REQUEST))
        {
            $data[$key] = trim($_REQUEST[$key]);
        }
    }
    
    foreach ($data as $key => &$value)
    {
        if(!$value) $value = NULL;
    }
    
    $data['uzeitpunkt'] = date('Y-m-d H:i:s',$data['uzeitpunkt']/1000);
    $db->insert('cp_pi', $data);
}*/
?>
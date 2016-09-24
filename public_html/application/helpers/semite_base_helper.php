<?php
/**
 * Created by PhpStorm.
 * User: smt2016
 * Date: 16.9.16.
 * Time: 14.54
 */

function shapeSpace_check_https($mode = 0) {

    if (!$mode){

        return true;
    } else if ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) {

        return true;
    }
    return true;
}

/* Add your own functions here */
function token($length = 32) {
    // Create random token
    $string = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

    $max = strlen($string) - 1;

    $token = '';

    for ($i = 0; $i < $length; $i++) {
        $token .= $string[mt_rand(0, $max)];
    }

    return $token;
}

function get_fraudlabs_value($arg=""){

    $CI =& get_instance();
    $fraudlabs_data = $CI->db->get('tblfraudlabs')->result_array();

    $fraudlabs = array();

    foreach ($fraudlabs_data as $value){
        $fraudlabs[$value['key']] = $value['value'];
    }

    if (isset($fraudlabs[$arg])) {
        return $fraudlabs[$arg];
    }

    return FALSE;
}

function fraudlabspro_hash($s){
    $hash = 'fraudlabspro_' . $s;
    for($i=0; $i<65536; $i++) $hash = sha1('fraudlabspro_' . $hash);

    return $hash;
}
<?php
/**
 * Created by PhpStorm.
 * User: smt2016
 * Date: 22.9.16.
 * Time: 14.42
 */

class Applications_model extends CI_Model{

    function __construct()
    {

        parent::__construct();


    }
    public function add_pre_application($application_data){

        $this->db->insert('tblpreapplications', $application_data);
    }
} 
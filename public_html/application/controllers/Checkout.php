<?php

/**
 * Created by PhpStorm.
 * User: root
 * Date: 10/14/16
 * Time: 6:51 PM
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Checkout extends CI_Controller
{

    function __construct()
    {
        parent::__construct();
    }

    public function index(){

        $data['title'] = 'Checkout';
        $this->load->view('application_success');
    }

}
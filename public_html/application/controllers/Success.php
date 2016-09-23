<?php
/**
 * Created by PhpStorm.
 * User: mbicanin
 * Date: 7/22/16
 * Time: 10:45 AM
 */

defined('BASEPATH') OR exit('No direct script access allowed');

class Success extends CI_Controller
{

    function __construct()
    {
        parent::__construct();
    }

    public function index(){

        $data['title'] = 'Success';
        $this->load->view('application_success');
    }

}
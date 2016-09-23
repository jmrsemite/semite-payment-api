<?php
/**
 * Created by PhpStorm.
 * User: mbicanin
 * Date: 7/22/16
 * Time: 10:45 AM
 */

defined('BASEPATH') OR exit('No direct script access allowed');

class Application extends CI_Controller
{

    function __construct()
    {
        parent::__construct();
    }

    public function index(){

        if ($this->input->post()){

            $this->load->model('applications_model');
            $this->applications_model->add_pre_application($this->input->post());

            redirect(base_url('success'));
        }

        $data['title'] = 'Online Application Form';
        $this->load->view('application');
    }
} 
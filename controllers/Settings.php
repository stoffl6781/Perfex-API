<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Settings extends AdminController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        if (!has_permission('api', '', 'view')) {
            access_denied('api');
        }

        $data['title'] = _l('api_settings');
        $this->load->view('settings', $data);
    }

    public function reset()
    {
        update_option('api_token', '');
        redirect(admin_url('api/settings'));
    }

    public function save()
    {
        update_option('api_token', $this->input->post('token'));
        redirect(admin_url('api/settings'));

    }
}

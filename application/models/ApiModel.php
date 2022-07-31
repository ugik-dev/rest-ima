<?php
defined('BASEPATH') or exit('No direct script access allowed');

class ApiModel extends CI_Model
{

    public function login($email)
    {
        return  $this->db->get_where('mp_user_customer', ['email' => $email])->result_array();
    }

    public function getToken($token)
    {
        return  $this->db->get_where('token_login', ['key' => $token])->result_array();
    }

    public function add_token($data)
    {
        $this->db->insert('token_login', $data);
        // echo json_encode($data);
        // die();
        return $this->db->affected_rows();
    }
}

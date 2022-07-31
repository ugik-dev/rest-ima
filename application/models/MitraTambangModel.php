<?php
defined('BASEPATH') or exit('No direct script access allowed');

class MitraTambangModel extends CI_Model
{

    public function cek($token)
    {
        $this->db->select('*');
        $this->db->from('token_login as tl');
        $this->db->join('mitra_tambang u', 'u.id_user = tl.user_id');

        $res = $this->db->get();
        return $res->result_array();
    }
    public function register($data)
    {
        $this->db->insert('mitra_tambang', DataStructure::slice($data, [
            'nama', 'jenis_mitra', 'id_user', 'alamat', 'file_persyaratan', 'nomor_nib', 'file_pengaju',
        ], TRUE));

        $data['id']  = $this->db->insert_id();

        return $data;
    }
}

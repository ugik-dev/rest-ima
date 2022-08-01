<?php
defined('BASEPATH') or exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;

class MitraTambang extends RestController
{

    function __construct()
    {
        parent::__construct();
        $this->load->model(array('ApiModel', 'UserModel', 'MitraTambangModel'));
    }

    public function cek_get()
    {
        $regData['token'] = !empty($this->input->request_headers()['X-API-KEY']) ? $this->input->request_headers()['X-API-KEY'] : $this->input->request_headers()['x-api-key'];
        $dataToken = $this->ApiModel->getToken($regData['token']);
        if (!empty($dataToken[0]) && $regData['token'] != 'ima_mobile_apps') {
            $data = $this->MitraTambangModel->cek($regData['token']);

            if (!empty($data)) {
                $this->response([
                    'status' => true,
                    'message' => 'Terdaftar sebagai mitra tambang',
                    'data' => $data[0]
                ], 200);
            } else {
                $this->response([
                    'status' => true,
                    'message' => 'Belum mendaftar sebagai mitra',
                ], 200);
            }
        } else {
            $this->response([
                'status' => false,
                'message' => 'Invalid Token!',
            ], 400);
        }
    }

    public function register_post()
    {
        $data = $this->input->post();
        $regData['jenis_mitra'] = $this->post('jenis_mitra');
        $regData['user_id'] = $this->post('user_id');
        $regData['nama'] = $this->post('nama');
        $regData['alamat'] = $this->post('alamat');
        $regData['nomor_nib'] = $this->post('nomor_nib');
        $regData['token'] = !empty($this->input->request_headers()['X-API-KEY']) ? $this->input->request_headers()['X-API-KEY'] : $this->input->request_headers()['x-api-key'];
        // $regData['file_persyaratan'] = $this->post('file_persyaratan');
        $dataToken = $this->ApiModel->getToken($regData['token']);
        // $dataToken = $this->ApiModel->login($dataToken['email']);
        // echo json_encode(!empty($dataToken[0]) && $regData['token'] != 'ima_mobile_apps');
        if (!empty($dataToken[0]) && $regData['token'] != 'ima_mobile_apps') {
            if (!empty($_FILES['persyaratan'])) {
                $this->load->library('upload');
                $this->upload->initialize(array(
                    'upload_path' => realpath(APPPATH . '../uploads/file_persyaratan'),
                    'allowed_types' => 'jpg|jpeg|png|gif|doc|docx|pdf',
                    'max_size' => '5000',
                    'encrypt_name' => true,
                ));
                if (!$this->upload->do_upload('persyaratan')) {
                    // echo 'er';
                    // throw new UserException($this->upload->display_errors(), UPLOAD_FAILED_CODE);
                } else {
                    echo 'ups';
                    $data_cek2 = $this->MitraTambangModel->cek($regData['token']);
                    $data_persyaratan = $this->upload->data();
                    $regData['file_persyaratan'] = $data_persyaratan['file_name'];
                    $regData['id_user'] = $dataToken[0]['user_id'];
                    // $dataToken = $this->MitraTambangModel->getData(['id_user' => $regData['id_user']]);
                    $data = $this->MitraTambangModel->register($regData);
                }
            } else {
                // $this->response([
                //     'status' => false,
                //     'message' => 'Masukkan dokumen peryaratan.',
                // ], 400);
                $this->response([
                    'status' => true,
                    'message' => 'Update Success.',
                ], 200);
            }
            // if (empty($dataToken[0]) or $dataToken[0]['id'] == 1)
            //     $this->response([
            //         'status' => false,
            //         'message' => 'Invalid X-API-KEY',
            //     ], 400);
            // else {
            //     $this->response([
            //         'status' => true,
            //         'message' => 'Register berhasil, jika data anda lulus verifikasi kami akan mengirimkan email.',
            //         'data' => $regData
            //     ], 200);
        }
        // $this->response([
        //     'status' => false,
        //     'message' => 'Terjadi Kesalahan!',
        //     // 'hed' => $this->input->request_headers(),
        //     'cek' =>  $dataToken[0],
        //     'data' => $regData
        // ], 400);
    }

    public function email_send($data, $action)
    {
        $serv = $this->UserModel->getEmailConfig();

        $send['to'] = $data['email'];
        if ($action == 'lupa_password') {
            $send['subject'] = 'Lupa Password Indometal Asia Aplication';
            $emailContent = '<!DOCTYPE><html><head></head><body><table width="600px" style="border:1px solid #cccccc;margin: auto;border-spacing:0;"><tr><td style="background: white;padding-left:3%"><img src="http://indometalasia.com/apps/assets/img/ima-transparent2.png" width="200px" vspace=0 /></td></tr>';
            $emailContent .= '<tr><td style="height:20px"></td></tr>';
            $url_act = base_url("/forgot-password-verification/{$data['id_user']}/{$data['token']}");
            $emailContent .= "<br><br> Email :  {$data['email']}
						<br> Token :  {$data['token']}
						<br> 
						<br>Untuk reset password silahkan<a href='{$url_act}' > klik ini </a>atau masuk melalui url di bawah. 
						<br>{$url_act}";
            $emailContent .= '<tr><td style="height:20px"></td></tr>';
            $emailContent .= "<tr><td style='background:#000000;color: #999999;padding: 2%;text-align: center;font-size: 13px;'><p style='margin-top:1px;'><a href='indometalasia.com/index.php/login' target='_blank' style='text-decoration:none;color: #60d2ff;'>indometalasia.com</a></p></td></tr></table></body></html>";
        } else {
            $send['subject'] = 'Activation Store PT INDOMETALASIA';
            $emailContent = '<!DOCTYPE><html><head></head><body><table width="600px" style="border:1px solid #cccccc;margin: auto;border-spacing:0;"><tr><td style="background:#F00000;padding-left:3%"><img src="https://store.indometalasia.com/assets/images/ima-transparent2.png" width="60px" vspace=0 /></td></tr>';
            $emailContent .= '<tr><td style="height:20px"></td></tr>';
            $url_act = base_url("/activator/{$data['id']}/{$data['activator']}");
            $emailContent .= "<br><br> Email :  {$data['email']}
						<br> Password :  {$data['password_hash']}
						<br> Activator :  {$data['activator']}
						<br> 
						<br><a href='{$url_act}' target='_blank' style='text-decoration:none;color: #60d2ff;'>Click this to activate</a>

						<br> manual activate = {$url_act}";
            $emailContent .= '<tr><td style="height:20px"></td></tr>';
            $emailContent .= "<tr><td style='background:#000000;color: #999999;padding: 2%;text-align: center;font-size: 13px;'><p style='margin-top:1px;'><a href='indometalasia.com' target='_blank' style='text-decoration:none;color: #60d2ff;'>indometalasia.com</a></p></td></tr></table></body></html>";
        }
        $send['message'] = $emailContent;

        $config['protocol']    = 'smtp';
        $config['smtp_host']    = $serv['stmp_mail']['url_'];
        $config['smtp_port']    = '587';
        $config['smtp_timeout'] = '60';
        $config['smtp_user']    = $serv['stmp_mail']['username'];    //Important
        $config['smtp_pass']    = $serv['stmp_mail']['key'];  //Important
        $config['charset']    = 'utf-8';
        $config['newline']    = '\r\n';
        $config['mailtype'] = 'html'; // or html
        $config['validation'] = TRUE; // bool whether to validate email or not 
        $send['config'] = $config;

        $this->email->initialize($send['config']);
        $this->email->set_mailtype("html");
        $this->email->from($serv['stmp_mail']['username']);
        $this->email->to($send['to']);
        $this->email->subject($send['subject']);
        $this->email->message($send['message']);
        $res = $this->email->send();
        $mes = $this->email->print_debugger();
        // var_dump($res);
        // var_dump($res);
        // die();
        return 0;
    }
}

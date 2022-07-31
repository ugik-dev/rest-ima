<?php
defined('BASEPATH') or exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;

class Users extends RestController
{

    function __construct()
    {
        parent::__construct();
        $this->load->model(array('ApiModel', 'UserModel'));
    }

    public function login_post()
    {
        $loginData['email'] = $this->post('email');
        $loginData['password'] = $this->post('password');
        // echo 'ss';
        $user = $this->ApiModel->login($loginData['email']);
        if (!empty($user[0])) {
            if ($user[0]['email'] == $loginData['email'] &&  $user[0]['password'] == md5($loginData['password'])) {
                $token = [
                    'user_id' => $user[0]['id_user'],
                    'key' => bin2hex(random_bytes(20)),
                    'ip_addresses' => $this->input->ip_address(),
                ];
                $res_token =  $this->ApiModel->add_token($token);
                // echo $res_token;
                // echo json_encode([
                //     $user[0]['email'], $loginData['email'], $user[0]['password'], md5($loginData['password'])
                // ]);
                if ($res_token > 0) {
                    // echo json_encode(strlen($token['key']));
                    $user[0]['token'] = $token['key'];
                    // echo json_encode([
                    //     'status' => true,
                    //     'message' => 'Succcess Login',
                    //     'data' => $user[0]
                    // ]);
                    $this->response([
                        'status' => true,
                        'message' => 'Succcess Login',
                        'data' => $user[0]
                    ], 200);
                } else {
                    $this->response([
                        'status' => false,
                        'message' => 'Terjadi kesalahan !!',
                    ], 200);
                }
            } else {
                $this->response([
                    'status' => false,
                    'message' => 'Password salah !!',
                ], 404);
            }
        } else {
            $this->response([
                'status' => false,
                'message' => 'Email tidak ditemukan !!',
            ], 404);
        }
        // $this->response([
        //     'status' => false,
        //     'message' => 'Terjadi kesalahan !!',
        // ], 404);
    }

    public function register_post()
    {
        $regData['email'] = $this->post('email');
        $regData['password'] = $this->post('password');
        $regData['repassword'] = $this->post('repassword');
        $regData['nama'] = $this->post('nama');
        $regData['alamat'] = $this->post('alamat');
        $regData['no_telp'] = $this->post('no_telp');

        $user = $this->ApiModel->login($regData['email']);
        if (!empty($user)) {
            $this->response([
                'status' => false,
                'message' => 'Email sudah terdaftar!!',
            ], 400);
            return;
        }
        if (empty($regData['password'])) {
            $this->response([
                'status' => false,
                'message' => 'Password tidak boleh kosong!!',
            ], 400);
            return;
        }
        if ($regData['password'] != $regData['repassword']) {
            $this->response([
                'status' => false,
                'message' => 'Password salah !!',
            ], 400);
            return;
        }
        $data = $this->UserModel->registerUser($regData);
        $this->email_send($data, 'registr');
        $this->response([
            'status' => true,
            'message' => 'Register berhasil, silahkan buka email anda untuk aktivasi!',
        ], 400);
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

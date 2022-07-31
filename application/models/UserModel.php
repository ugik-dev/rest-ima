<?php
defined('BASEPATH') or exit('No direct script access allowed');

class UserModel extends CI_Model
{

	public function getAllUser($filter = [])
	{
		$this->db->select("u.*");

		$this->db->from('mp_user_customer as u');
		// $this->db->join('role as r', 'r.id_role = u.id_role');
		// $this->db->join('seller as eks', 'eks.id_user = u.id_user', 'LEFT');
		// $this->db->join('buyer as b', 'b.id_user = u.id_user', 'LEFT');
		if (empty($filter['is_login'])) {
			$this->db->select("NULL as password", FALSE);
		}
		if (isset($filter['is_not_self'])) $this->db->where('u.id_user !=', $this->session->userdata('id_user'));
		if (isset($filter['username'])) $this->db->where('u.username', $filter['username']);
		if (isset($filter['id_user'])) $this->db->where('u.id_user', $filter['id_user']);
		if (isset($filter['except_roles'])) $this->db->where_not_in('u.id_role', $filter['except_roles']);
		if (isset($filter['just_roles'])) $this->db->where_in('u.id_role', $filter['just_roles']);
		if (!empty($filter['id_role'])) $this->db->where('u.id_role', $filter['id_role']);
		$res = $this->db->get();
		// var_dump($res->result_array());
		// die();
		return DataStructure::keyValue($res->result_array(), 'id_user');
	}

	public function activatorUser($data)
	{
		$this->db->select("*");
		$this->db->from('mp_user_tmp as u');
		$this->db->where("u.id_user ", $data['id']);
		$this->db->where("u.activator ", $data['activator']);
		$res = $this->db->get();
		$res = $res->result_array();
		// var_dump($res);
		// die();
		if (empty($res)) {
			throw new UserException('Activation failed or has active please check your email', USER_NOT_FOUND_CODE);
		} else {
			$this->cekUserByEmail($res[0]);
			// $this->cekUserByEmailSeller($res[0]);
			$this->cekUserByUsername($res[0]['username']);
			// die();
			// if ($res[0]['jenis_akun'] == 'B') {
			// $res[0]['id_role'] = '12';
			// $res[0]['password'] = $res[0]['password_hash'];
			$res[0]['id_user'] = $this->addUser($res[0]);
			// $this->addBuyer($res[0]);
			$this->db->where('id', $res[0]['id']);
			$this->db->delete('mp_user_tmp');
			return $res[0];
		};
	}

	public function getEmailConfig()
	{
		$this->db->select('*');
		$this->db->from('mp_config_email');
		$res = $this->db->get();
		// $res = $res->result_array();
		// var_dump($res);
		// return $res;
		return DataStructure::keyValue($res->result_array(), 'type');
	}

	public function getUser($idUser = NULL)
	{
		$row = $this->getAllUser(['id_user' => $idUser]);
		if (empty($row)) {
			throw new UserException("User yang kamu cari tidak ditemukan", USER_NOT_FOUND_CODE);
		}
		return $row[$idUser];
	}

	public function cekUserByUsername($username = NULL)
	{
		$row = $this->getAllUser(['username' => $username, 'is_login' => TRUE]);
		if (!empty($row)) {
			throw new UserException("User yang kamu daftarkan sudah ada", USER_NOT_FOUND_CODE);
		}
	}


	public function cekUserByEmailBuyer($data)
	{
		$this->db->select("email");
		$this->db->from('buyer as u');
		$this->db->where('u.email', $data['email']);
		$res = $this->db->get();
		$row = $res->result_array();
		if (!empty($row)) {
			throw new UserException("Email yang kamu daftarkan sudah ada", USER_NOT_FOUND_CODE);
		}
	}

	public function cekUserByEmail($data)
	{

		$this->db->select("email");
		$this->db->from('mp_user_customer as u');
		$this->db->where('u.email', $data['email']);
		$res = $this->db->get();
		$row = $res->result_array();
		if (!empty($row)) {
			throw new UserException("Email yang kamu daftarkan sudah ada", USER_NOT_FOUND_CODE);
		}
	}

	public function LupaPassword($data, $ajax = true)
	{

		$this->db->select("*");
		$this->db->from('mp_lupas_token as u');
		$this->db->where('u.id_user like "' . $data['id_user'] . '"');
		$this->db->where('u.token like "' . $data['token'] . '"');
		$res = $this->db->get();
		$row = $res->result_array();
		// echo $this->db->last_query();
		if (!empty($row[0])) {
			$row = $row[0];
			if (strtotime($row['date_token']) < strtotime("-1 day"))
				if ($ajax)
					throw new UserException("Token expired", USER_NOT_FOUND_CODE);
				else {
					$row['error'] = true;
					$row['message'] = "Token Expired";
				}
			return $row;
		} else {
			if ($ajax)
				throw new UserException("Token tidak valid", USER_NOT_FOUND_CODE);
			else {
				$row['error'] = true;
				$row['message'] = "Token tidak valid";
			}
		}
	}

	public function cekUserByEmailLupaPassword($data)
	{

		$this->db->select("id_user, nama,username, email");
		$this->db->from('mp_user_customer as u');
		$this->db->where('u.email', $data['email']);
		$res = $this->db->get();
		$row = $res->result_array();
		if (!empty($row[0])) {
			$row = $row[0];
			// var_dump($row[0]);
			$token = sha1(md5(time() . $row['id_user']));
			$res_data = array(
				'id_user' => $row['id_user'], 'token' => $token
			);
			$this->db->insert('mp_lupas_token', $res_data);

			$row['token'] = $token;
			return $row;
		} else
			throw new UserException("Email tidak ditemukan", USER_NOT_FOUND_CODE);
	}

	public function getUserByUsername($username = NULL)
	{
		$row = $this->getAllUser(['username' => $username, 'is_login' => TRUE]);
		if (empty($row)) {
			throw new UserException("User yang kamu cari tidak ditemukan", USER_NOT_FOUND_CODE);
		}
		return array_values($row)[0];
	}


	public function getUserByEmail($email = NULL)
	{
		$row = $this->getAllUser(['email' => $email, 'is_login' => TRUE]);
		if (empty($row)) {
			throw new UserException("User yang kamu cari tidak ditemukan", USER_NOT_FOUND_CODE);
		}
		return array_values($row)[0];
	}

	public function login($loginData)
	{
		$user = $this->getUserByEmail($loginData['email']);
		if (md5(($loginData['password'])) !== $user['password'])
			throw new UserException("Password yang kamu masukkan salah.", WRONG_PASSWORD_CODE);
		return $user;
	}

	public function addUser($data)
	{
		// $data['hash_pwd'] = $data['password'];
		// $data['password'] = md5(sha1($data['password']));

		$this->db->insert('mp_user_customer', DataStructure::slice($data, [
			'username', 'nama', 'password', 'email', 'company_name', 'alamat', 'zip_code', 'no_telp'
		], TRUE));
		ExceptionHandler::handleDBError($this->db->error(), "Tambah User", "User");

		$id_user = $this->db->insert_id();

		if ($data['id_role'] == 2) {
			ini_set('date.timezone', 'Asia/Jakarta');
			$date = date("Y-m-d h:i:s");
			if (!empty($data['nama_seller'])) $this->db->set('nama_seller', $data['nama_seller']);
			if (!empty($data['alamat_seller'])) $this->db->set('alamat_seller', $data['alamat_seller']);

			$this->db->insert('seller', ['id_user' => $id_user, 'date_modified' => $date]);
			ExceptionHandler::handleDBError($this->db->error(), "Tambah User", "Perusahaan");
		}

		return $id_user;
	}

	public function addBuyer($data)
	{
		ini_set('date.timezone', 'Asia/Jakarta');
		$data['date_modified'] = date("Y-m-d h:i:s");

		$this->db->insert('buyer', DataStructure::slice($data, [
			'id_user', 'nama_perusahaan', 'alamat', 'region', 'email', 'date_modified'
		], TRUE));
		ExceptionHandler::handleDBError($this->db->error(), "Tambah User", "User");

		$id_user = $this->db->insert_id();

		if ($data['id_role'] == 2) {
			ini_set('date.timezone', 'Asia/Jakarta');
			$date = date("Y-m-d h:i:s");
			$this->db->insert('perusahaan', ['id_user' => $id_user, 'date_modified' => $date]);
			ExceptionHandler::handleDBError($this->db->error(), "Tambah User", "Perusahaan");
		}
	}

	public function addPerusahaan($data)
	{
		ini_set('date.timezone', 'Asia/Jakarta');
		$data['date_modified'] = date("Y-m-d h:i:s");

		$this->db->insert('perusahaan', DataStructure::slice($data, [
			'id_user', 'nama_perusahaan', 'alamat', 'region', 'email', 'date_modified'
		], TRUE));
		ExceptionHandler::handleDBError($this->db->error(), "Tambah User", "User");

		$id_user = $this->db->insert_id();

		return $id_user;
	}

	public function registerUser($data)
	{
		// $this->cekUserByUsername($data['username']);
		// $this->cekUserByEmail($data);
		// $this->cekUserByEmailSeller($data);
		$data['ip_address'] = $this->input->ip_address();
		$data['password_hash'] = $data['password'];
		$data['password'] = md5($data['password']);
		$permitted_activtor = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$data['activator'] =  substr(str_shuffle($permitted_activtor), 0, 20);
		// echo $act;
		$this->db->insert('mp_user_tmp', DataStructure::slice($data, [
			'nama', 'password', 'activator', 'email', 'company_name', 'alamat', 'zip_code', 'no_telp', 'ip_address'
		], TRUE));
		// ExceptionHandler::handleDBError($this->db->error(), "Tambah User", "User");

		$data['id']  = $this->db->insert_id();

		return $data;
	}

	public function editUser($data)
	{
		if (!empty($data['password'])) $this->db->set('hash_pwd', $data['password']);
		if (!empty($data['password'])) $this->db->set('password', md5($data['password']));
		$this->db->set(DataStructure::slice($data, ['username', 'nama', 'id_role']));
		$this->db->where('id_user', $data['id_user']);
		$this->db->update('user');

		ExceptionHandler::handleDBError($this->db->error(), "Ubah User", "User");

		return $data['id_user'];
	}

	public function deleteUser($data)
	{
		$this->db->where('id_user', $data['id_user']);
		$this->db->delete('user');

		ExceptionHandler::handleDBError($this->db->error(), "Hapus User", "User");
	}

	public function changePassword($data)
	{
		$idUser = $this->session->userdata('nama_role') == 'admin' ? $data['id_user'] : $this->session->userdata('id_user');
		$this->db->set('password', md5($data['password']));
		$this->db->where('id_user', $idUser);
		$this->db->update('user');
	}

	public function changePasswordCustomer($data)
	{
		// $idUser = $this->session->userdata('nama_role') == 'admin' ? $data['id_user'] : $this->session->userdata('id_user');
		$this->db->set('password', md5($data['password']));
		$this->db->where('id_user', $data['id_user']);
		$this->db->update('mp_user_customer');
	}


	public function changeUsername($data)
	{
		$this->db->set('username', $data['username_new']);
		$this->db->where('username', $data['username']);
		$this->db->update('user');

		ExceptionHandler::handleDBError($this->db->error(), "Ganti Username", "User");
	}

	public function deleteBatch($ids)
	{
		$this->db->where_in('id_user', $ids);
		$this->db->delete('user');

		ExceptionHandler::handleDBError($this->db->error(), "Hapus User", "User");
	}
}

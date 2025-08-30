<?php

namespace App\Controllers\Role\Admin;

use App\Controllers\BaseController;
use App\Models\UserModel;

class User extends BaseController
{
    protected $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    public function index()
    {
        $data = [
            'users' => $this->userModel->orderBy('created_at', 'DESC')->findAll(),
            'total_users' => $this->userModel->countAll(),
            'active_users' => $this->userModel->where('status', 'aktif')->countAllResults(),
            'inactive_users' => $this->userModel->where('status', 'nonaktif')->countAllResults(),
        ];

        return view('role/admin/user/index', $data);
    }

    public function store()
    {
        $validation = \Config\Services::validation();
        
        $rules = [
            'nama_lengkap' => 'required|min_length[3]|max_length[100]',
            'email' => 'required|valid_email|is_unique[users.email]',
            'password' => 'required|min_length[6]',
            'role' => 'required|in_list[admin,presenter,audience,reviewer]',
            'status' => 'required|in_list[aktif,nonaktif]'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $validation->getErrors());
        }

        $data = [
            'nama_lengkap' => $this->request->getPost('nama_lengkap'),
            'email' => $this->request->getPost('email'),
            'password' => password_hash($this->request->getPost('password'), PASSWORD_DEFAULT),
            'role' => $this->request->getPost('role'),
            'status' => $this->request->getPost('status'),
            'email_verified_at' => date('Y-m-d H:i:s')
        ];

        if ($this->userModel->save($data)) {
            return redirect()->to('admin/users')->with('success', 'User berhasil ditambahkan!');
        } else {
            return redirect()->back()->withInput()->with('error', 'Gagal menambahkan user.');
        }
    }

    public function edit($id)
    {
        $user = $this->userModel->find($id);
        
        if (!$user) {
            return redirect()->to('admin/users')->with('error', 'User tidak ditemukan.');
        }

        return $this->response->setJSON($user);
    }

    public function update($id)
    {
        $user = $this->userModel->find($id);
        
        if (!$user) {
            return redirect()->to('admin/users')->with('error', 'User tidak ditemukan.');
        }

        $validation = \Config\Services::validation();
        
        $rules = [
            'nama_lengkap' => 'required|min_length[3]|max_length[100]',
            'email' => "required|valid_email|is_unique[users.email,id_user,$id]",
            'role' => 'required|in_list[admin,presenter,audience,reviewer]',
            'status' => 'required|in_list[aktif,nonaktif]'
        ];

        // Only validate password if it's provided
        if ($this->request->getPost('password')) {
            $rules['password'] = 'min_length[6]';
        }

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $validation->getErrors());
        }

        $data = [
            'nama_lengkap' => $this->request->getPost('nama_lengkap'),
            'email' => $this->request->getPost('email'),
            'role' => $this->request->getPost('role'),
            'status' => $this->request->getPost('status')
        ];

        // Update password if provided
        if ($this->request->getPost('password')) {
            $data['password'] = password_hash($this->request->getPost('password'), PASSWORD_DEFAULT);
        }

        if ($this->userModel->update($id, $data)) {
            return redirect()->to('admin/users')->with('success', 'User berhasil diupdate!');
        } else {
            return redirect()->back()->withInput()->with('error', 'Gagal mengupdate user.');
        }
    }

    public function delete($id)
    {
        $user = $this->userModel->find($id);
        
        if (!$user) {
            return redirect()->to('admin/users')->with('error', 'User tidak ditemukan.');
        }

        // Prevent deleting own account
        if ($id == session('id_user')) {
            return redirect()->to('admin/users')->with('error', 'Tidak bisa menghapus akun sendiri.');
        }

        if ($this->userModel->delete($id)) {
            return redirect()->to('admin/users')->with('success', 'User berhasil dihapus!');
        } else {
            return redirect()->to('admin/users')->with('error', 'Gagal menghapus user.');
        }
    }
}
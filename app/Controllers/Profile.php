<?php

namespace App\Controllers;

use App\Models\UserModel;

class Profile extends BaseController
{
    protected $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    // Halaman profil
    public function index()
    {
        $userId = session()->get('id_user');
        $user   = $this->userModel->find($userId);

        return view('profile/index', [
            'title' => 'Profil Saya',
            'user'  => $user
        ]);
    }

    // Update data profil
    public function update()
    {
        $userId = session()->get('id_user');

        $data = [
            'nama_lengkap' => $this->request->getPost('nama_lengkap'),
            'no_hp'        => $this->request->getPost('no_hp'),
            'instansi'     => $this->request->getPost('instansi')
        ];

        // Handle foto upload
        $fileFoto = $this->request->getFile('foto');
        if ($fileFoto && $fileFoto->isValid() && !$fileFoto->hasMoved()) {
            $newName = $fileFoto->getRandomName();
            $fileFoto->move(FCPATH . 'uploads/profile', $newName);
            $data['foto'] = $newName;
        }

        $this->userModel->update($userId, $data);

        return redirect()->to('/profile')->with('success', 'Profil berhasil diperbarui');
    }

    // Ganti password
    public function changePassword()
    {
        $userId = session()->get('id_user');
        $user   = $this->userModel->find($userId);

        $oldPassword = $this->request->getPost('old_password');
        $newPassword = $this->request->getPost('new_password');
        $confirm     = $this->request->getPost('confirm_password');

        if (!password_verify($oldPassword, $user['password'])) {
            return redirect()->back()->with('error', 'Password lama salah');
        }

        if ($newPassword !== $confirm) {
            return redirect()->back()->with('error', 'Konfirmasi password tidak sesuai');
        }

        $this->userModel->update($userId, [
            'password' => password_hash($newPassword, PASSWORD_DEFAULT)
        ]);

        return redirect()->to('/profile')->with('success', 'Password berhasil diubah');
    }
}

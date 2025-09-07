<?php

namespace App\Controllers;

use App\Models\UserModel;

class Profile extends BaseController
{
    protected $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        helper(['filesystem']);
    }

    public function index()
    {
        $userId = (int) session()->get('id_user');
        $user   = $this->userModel->find($userId);

        return view('profile/index', [
            'title' => 'Profil Saya',
            'user'  => $user
        ]);
    }

    public function update()
    {
        $userId = (int) session()->get('id_user');
        if (!$userId) return redirect()->to('/auth/login');

        // kolom di DB: gunakan 'institusi' (bukan instansi)
        $nama       = (string) $this->request->getPost('nama_lengkap');
        $noHp       = (string) $this->request->getPost('no_hp');
        $institusi  = (string) $this->request->getPost('institusi');

        $data = [
            'nama_lengkap' => $nama,
            'no_hp'        => $noHp,
            'institusi'    => $institusi,
            'updated_at'   => date('Y-m-d H:i:s'),
        ];

        // Upload foto (opsional)
        $fileFoto = $this->request->getFile('foto');
        if ($fileFoto && $fileFoto->isValid() && !$fileFoto->hasMoved()) {
            // validasi sederhana mime/type
            $ext  = strtolower($fileFoto->getClientExtension() ?: '');
            if (!in_array($ext, ['jpg','jpeg','png','webp'])) {
                return redirect()->back()->with('error','Format foto harus jpg/jpeg/png/webp.');
            }

            $dir = FCPATH . 'uploads/profile';
            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }

            $newName = time().'_'.$fileFoto->getRandomName();
            try {
                $fileFoto->move($dir, $newName);
            } catch (\Throwable $e) {
                return redirect()->back()->with('error','Gagal menyimpan foto: '.$e->getMessage());
            }

            $data['foto'] = $newName;

            // Update SESSION supaya navbar langsung refresh avatar
            session()->set('foto', $newName);
            session()->set('foto_ver', time());
        }

        // Simpan DB
        $this->userModel->update($userId, $data);

        // Update session teks yang terlihat di navbar
        if ($nama) {
            session()->set('nama_lengkap', $nama);
            session()->set('nama', $nama); // alias
        }

        return redirect()->to('/profile')->with('success', 'Profil berhasil diperbarui');
    }

    public function changePassword()
    {
        $userId = (int) session()->get('id_user');
        if (!$userId) return redirect()->to('/auth/login');

        $user   = $this->userModel->find($userId);
        $old    = (string) $this->request->getPost('old_password');
        $new    = (string) $this->request->getPost('new_password');
        $confirm= (string) $this->request->getPost('confirm_password');

        if (!$user || !password_verify($old, $user['password'])) {
            return redirect()->back()->with('error', 'Password lama salah');
        }
        if ($new !== $confirm) {
            return redirect()->back()->with('error', 'Konfirmasi password tidak sesuai');
        }

        $this->userModel->update($userId, [
            'password'   => password_hash($new, PASSWORD_DEFAULT),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to('/profile')->with('success', 'Password berhasil diubah');
    }
}

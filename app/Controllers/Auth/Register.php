<?php

namespace App\Controllers\Auth;

use App\Controllers\BaseController;
use App\Models\UserModel;
use CodeIgniter\I18n\Time;

class Register extends BaseController
{
    public function index()
    {
        return view('auth/register');
    }

    public function store()
    {
        $userModel = new UserModel();

        // ambil data dari form
        $nama     = $this->request->getPost('nama_lengkap');
        $email    = $this->request->getPost('email');
        $password = $this->request->getPost('password');
        $role     = $this->request->getPost('role'); // default audience/presenter

        // generate token verifikasi
        $token = bin2hex(random_bytes(32));

        // simpan user
        $userModel->save([
            'nama_lengkap'      => $nama,
            'email'             => $email,
            'password'          => password_hash($password, PASSWORD_DEFAULT),
            'role'              => $role ?? 'audience',
            'status'            => 'nonaktif',
            'verification_token'=> $token,
            'created_at'        => Time::now(),
        ]);

        // kirim email verifikasi
        $emailService = \Config\Services::email();
        $emailService->setTo($email);
        $emailService->setFrom('no-reply@snia.com', 'SNIA System');
        $emailService->setSubject('Verifikasi Email Akun SNIA');
        $link = base_url("auth/verify/{$token}");
        $message = "
            <h3>Halo, $nama</h3>
            <p>Terima kasih sudah mendaftar. Klik link di bawah untuk verifikasi akun:</p>
            <p><a href='$link'>$link</a></p>
            <p>Jika tidak merasa mendaftar, abaikan email ini.</p>
        ";
        $emailService->setMessage($message);
        $emailService->send();

        return redirect()->to('/auth/login')->with('success', 'Registrasi berhasil. Silakan cek email untuk verifikasi.');
    }
}

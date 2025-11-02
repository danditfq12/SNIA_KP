<?php

namespace App\Controllers\Auth;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Models\PendingRegistrationModel;

class Register extends BaseController
{
    public function index()
    {
        return view('auth/register');
    }

    public function store()
    {
        // Validasi dengan pesan Indonesia
        $rules = [
            'nama_lengkap' => [
                'rules'  => 'required|min_length[3]',
                'errors' => [
                    'required'   => 'Nama lengkap wajib diisi.',
                    'min_length' => 'Nama lengkap minimal 3 karakter.',
                ]
            ],
            'email' => [
                'rules'  => 'required|valid_email',
                'errors' => [
                    'required'    => 'Email wajib diisi.',
                    'valid_email' => 'Format email tidak valid.',
                ]
            ],
            'password' => [
                'rules'  => 'required|min_length[6]',
                'errors' => [
                    'required'   => 'Kata sandi wajib diisi.',
                    'min_length' => 'Kata sandi harus minimal 6 karakter.',
                ]
            ],
            'password2' => [
                'rules'  => 'required|matches[password]',
                'errors' => [
                    'required' => 'Konfirmasi kata sandi wajib diisi.',
                    'matches'  => 'Konfirmasi kata sandi tidak cocok.',
                ]
            ],
            'role' => [
                'rules'  => 'required|in_list[presenter,audience]',
                'errors' => [
                    'required' => 'Role wajib dipilih.',
                    'in_list'  => 'Role tidak valid.',
                ]
            ],
        ];

        if (! $this->validate($rules)) {
            // PENTING: JANGAN back(), arahkan eksplisit ke /auth/register
            return redirect()
                ->to(site_url('auth/register'))
                ->withInput()
                ->with('error', $this->validator->listErrors());
        }

        // Normalisasi
        $namaLengkap = (string) $this->request->getPost('nama_lengkap');
        $email       = strtolower(trim((string) $this->request->getPost('email')));
        $password    = (string) $this->request->getPost('password');
        $role        = (string) $this->request->getPost('role'); // presenter|audience

        $userModel = new UserModel();

        // 1) Sudah ada user aktif dengan email & role yang sama → tampilkan alert di register
        $userSame = $userModel->where(['email' => $email, 'role' => $role])->first();
        if ($userSame) {
            return redirect()
                ->to(site_url('auth/register'))
                ->withInput()
                ->with('error', 'Email sudah terdaftar sebagai ' . ucfirst($role) . '. Silakan masuk.');
        }

        // 2) Sudah ada user aktif dengan email yang sama tapi role berbeda → beri info
        $userAny = $userModel->where('email', $email)->first();
        if ($userAny && ! $userSame) {
            $existingRole = $userAny['role'] ?? '-';
            return redirect()
                ->to(site_url('auth/register'))
                ->withInput()
                ->with('error', 'Email sudah terdaftar sebagai ' . ucfirst($existingRole) . '. Silakan masuk. Untuk mengubah peran, hubungi admin.');
        }

        // 3) Pending verifikasi untuk kombinasi email+role → perbarui OTP & arahkan ke verify
        $pendingModel = new PendingRegistrationModel();
        $pendingSame  = $pendingModel->where(['email' => $email, 'role' => $role])->first();

        // OTP & expiry
        $otp     = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expired = date('Y-m-d H:i:s', strtotime('+10 minutes'));

        $payload = [
            'nama_lengkap'  => $namaLengkap,
            'email'         => $email,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
            'role'          => $role,
            'otp_code'      => $otp,
            'otp_expired'   => $expired,
            'updated_at'    => date('Y-m-d H:i:s'),
        ];

        if ($pendingSame) {
            $pendingModel->update($pendingSame['id'], $payload);
        } else {
            $payload['created_at'] = date('Y-m-d H:i:s');
            $pendingModel->insert($payload);
        }

        // Kirim OTP (best-effort)
        try {
            $mail = \Config\Services::email();
            $mail->setFrom(config('Email')->fromEmail, config('Email')->fromName);
            $mail->setTo($email);
            $mail->setSubject('Kode OTP Verifikasi - SNIA');
            $mail->setMessage("
                <p>Halo {$namaLengkap},</p>
                <p>Kode OTP verifikasi akun Anda:</p>
                <h2 style='letter-spacing:6px;'>{$otp}</h2>
                <p>Kode berlaku 10 menit.</p>
            ");
            $mail->setMailType('html');
            $mail->setNewline("\r\n");
            $mail->setCRLF("\r\n");
            $mail->send();
        } catch (\Throwable $e) {
            log_message('error', 'Exception kirim email OTP: ' . $e->getMessage());
        }

        // simpan email untuk verify
        session()->set('email_verifikasi', $email);

        $msg = $pendingSame
            ? 'Pendaftaran sebelumnya ditemukan. Kode OTP baru telah dikirim ke email Anda.'
            : 'Kode OTP telah dikirim ke email Anda.';
        return redirect()->to('/auth/verify?email=' . urlencode($email))
            ->with('success', $msg);
    }
}
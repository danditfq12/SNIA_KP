<?php

namespace App\Controllers\Auth;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Models\PasswordResetModel;

class PasswordReset extends BaseController
{
    // STEP 1: Form input email
    public function requestForm()
    {
        if (session()->get('isLoggedIn')) {
            return redirect()->to(site_url('dashboard'));
        }
        return view('auth/forgot_password');
    }

    // STEP 1: Kirim OTP ke email (robust + logging)
    public function requestSend()
    {
        $rules = [
            'email' => [
                'rules'  => 'required|valid_email',
                'errors' => [
                    'required'    => 'Email wajib diisi.',
                    'valid_email' => 'Format email tidak valid.',
                ]
            ],
        ];

        if (! $this->validate($rules)) {
            return redirect()->to(site_url('auth/forgot-password'))
                ->withInput()
                ->with('error', $this->validator->listErrors());
        }

        $email = strtolower(trim((string) $this->request->getPost('email')));

        // Cek user di tabel users (privasi: tetap pesan generik kalau tidak ada)
        $user = (new UserModel())->where('email', $email)->first();
        if (! $user) {
            return redirect()->to(site_url('auth/forgot-password'))
                ->withInput()
                ->with('success', 'Jika email terdaftar, kami telah mengirimkan kode verifikasi.');
        }

        // Generate / refresh OTP
        $reset  = new PasswordResetModel();
        $otp    = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));

        $existing = $reset->where('email', $email)->first();
        $data = [
            'email'       => $email,
            'otp_code'    => $otp,
            'otp_expired' => $expiry,
            'attempts'    => 0,
            'updated_at'  => date('Y-m-d H:i:s'),
        ];
        if ($existing) {
            $reset->update($existing['id'], $data);
        } else {
            $data['created_at'] = date('Y-m-d H:i:s');
            $reset->insert($data);
        }

        // Kirim email OTP (robust)
        try {
            $cfg  = config('Email');
            $from = $cfg->fromEmail ?? 'no-reply@example.com';
            $name = $cfg->fromName  ?? 'SNIA';

            $mail = \Config\Services::email();
            $mail->setFrom($from, $name);
            $mail->setTo($email);
            $mail->setSubject('Kode Reset Password - SNIA');
            $mail->setMailType('html');
            $mail->setNewline("\r\n");
            $mail->setCRLF("\r\n");

            $mail->setMessage("
                <p>Halo,</p>
                <p>Kode reset password Anda adalah:</p>
                <h2 style='letter-spacing:6px;'>{$otp}</h2>
                <p>Kode berlaku 10 menit.</p>
            ");

            if (! $mail->send()) {
                log_message('error', 'Email OTP gagal: ' . print_r($mail->printDebugger(['headers','subject','body']), true));
            }
        } catch (\Throwable $e) {
            log_message('error', 'Exception kirim email OTP: ' . $e->getMessage());
        }

        session()->set('reset_email', $email);

        return redirect()->to(site_url('auth/reset/verify?email=' . urlencode($email)))
            ->with('success', 'Kode verifikasi telah dikirim ke email Anda.');
    }

    // STEP 2: Form verifikasi OTP
    public function verifyForm()
    {
        $email = (string) ($this->request->getGet('email') ?? session()->get('reset_email'));
        if (! $email) {
            return redirect()->to(site_url('auth/forgot-password'))
                ->with('error', 'Sesi verifikasi tidak ditemukan. Silakan ulangi.');
        }
        return view('auth/reset_verify', ['email' => $email]);
    }

    // STEP 2: Proses verifikasi OTP
    public function verifyCheck()
    {
        $email = strtolower(trim((string) $this->request->getPost('email')));
        $otp   = (string) $this->request->getPost('otp');

        if ($email === '' || $otp === '') {
            return redirect()->to(site_url('auth/reset/verify?email=' . urlencode($email)))
                ->withInput()
                ->with('error', 'Email dan kode verifikasi wajib diisi.');
        }

        $reset = (new PasswordResetModel())->where('email', $email)->first();
        if (! $reset) {
            return redirect()->to(site_url('auth/forgot-password'))
                ->with('error', 'Permintaan reset tidak ditemukan. Silakan ulangi.');
        }

        if (strtotime($reset['otp_expired']) < time()) {
            return redirect()->to(site_url('auth/forgot-password'))
                ->with('error', 'Kode verifikasi kedaluwarsa. Silakan minta ulang.');
        }

        if ((int) $reset['attempts'] >= 5) {
            return redirect()->to(site_url('auth/forgot-password'))
                ->with('error', 'Percobaan verifikasi melebihi batas. Silakan minta ulang kode.');
        }

        if ($reset['otp_code'] !== $otp) {
            (new PasswordResetModel())->update($reset['id'], [
                'attempts'   => (int) $reset['attempts'] + 1,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            return redirect()->to(site_url('auth/reset/verify?email=' . urlencode($email)))
                ->withInput()
                ->with('error', 'Kode verifikasi salah.');
        }

        session()->set('reset_verified', true);
        session()->set('reset_email', $email);

        return redirect()->to(site_url('auth/reset/new'))
            ->with('success', 'Verifikasi berhasil. Silakan buat kata sandi baru.');
    }

    // STEP 3: Form password baru
    public function newPasswordForm()
    {
        if (! session()->get('reset_verified') || ! session()->get('reset_email')) {
            return redirect()->to(site_url('auth/forgot-password'))
                ->with('error', 'Sesi reset tidak valid. Silakan ulangi.');
        }
        return view('auth/reset_new');
    }

    // STEP 3: Simpan password baru (update tabel users)
    public function updatePassword()
    {
        if (! session()->get('reset_verified') || ! session()->get('reset_email')) {
            return redirect()->to(site_url('auth/forgot-password'))
                ->with('error', 'Sesi reset tidak valid. Silakan ulangi.');
        }

        $rules = [
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
        ];

        if (! $this->validate($rules)) {
            return redirect()->to(site_url('auth/reset/new'))
                ->withInput()
                ->with('error', $this->validator->listErrors());
        }

        $email    = (string) session()->get('reset_email');
        $password = (string) $this->request->getPost('password');

        $userModel = new UserModel();
        $user      = $userModel->where('email', $email)->first();
        if (! $user) {
            return redirect()->to(site_url('auth/forgot-password'))
                ->with('error', 'Akun tidak ditemukan. Silakan daftar.');
        }

        // Update password di tabel users (PK: id_user)
        $userModel->update($user['id_user'], [
            'password'   => password_hash($password, PASSWORD_BCRYPT),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Hapus data reset & sesi
        (new PasswordResetModel())->where('email', $email)->delete();
        session()->remove('reset_verified');
        session()->remove('reset_email');

        return redirect()->to(site_url('auth/login'))
            ->with('success', 'Kata sandi berhasil diperbarui. Silakan login.');
    }
}
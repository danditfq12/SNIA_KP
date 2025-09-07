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
        $rules = [
            'nama_lengkap' => 'required|min_length[3]',
            'email'        => 'required|valid_email',
            'password'     => 'required|min_length[6]',
            'password2'    => 'required|matches[password]',
            'role'         => 'permit_empty|in_list[presenter,audience]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('error', $this->validator->listErrors());
        }

        // Normalisasi email
        $email = strtolower(trim((string) $this->request->getPost('email')));

        // 1) Jika email sudah aktif di USERS -> tolak
        $userModel = new UserModel();
        if ($userModel->where('email', $email)->first()) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Email sudah terdaftar & aktif. Silakan login.');
        }

        // 2) Generate OTP & expiry
        $otp     = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expired = date('Y-m-d H:i:s', strtotime('+10 minutes'));

        // 3) Upsert ke PENDING (kalau sudah ada, update saja)
        $pending = new PendingRegistrationModel();
        $payload = [
            'nama_lengkap'  => (string) $this->request->getPost('nama_lengkap'),
            'email'         => $email,
            'password_hash' => password_hash((string) $this->request->getPost('password'), PASSWORD_BCRYPT),
            'role'          => (string) ($this->request->getPost('role') ?: 'audience'),
            'otp_code'      => $otp,
            'otp_expired'   => $expired,
            'updated_at'    => date('Y-m-d H:i:s'),
        ];

        $existing = $pending->where('email', $email)->first();
        if ($existing) {
            $pending->update($existing['id'], $payload);
        } else {
            $payload['created_at'] = date('Y-m-d H:i:s');
            $pending->insert($payload);
        }

        // 4) Kirim OTP via email (tambah mailType & newline/CRLF)
        try {
            $mail = \Config\Services::email();
            $mail->setFrom(config('Email')->fromEmail, config('Email')->fromName);
            $mail->setTo($email);
            $mail->setSubject('Kode OTP Verifikasi - SNIA');

            $html = "
                <p>Halo {$payload['nama_lengkap']},</p>
                <p>Kode OTP verifikasi akun Anda:</p>
                <h2 style='letter-spacing:6px;'>{$otp}</h2>
                <p>Kode berlaku 10 menit.</p>
            ";
            $mail->setMessage($html);
            $mail->setMailType('html');
            $mail->setNewline("\r\n");
            $mail->setCRLF("\r\n");

            if (!$mail->send()) {
                log_message('error', 'Email OTP gagal: ' . print_r($mail->printDebugger(['headers','subject','body']), true));
            }
        } catch (\Throwable $e) {
            log_message('error', 'Exception kirim email OTP: ' . $e->getMessage());
        }

        // 5) Simpan email ke session + redirect ke verify
        session()->set('email_verifikasi', $email);

        return redirect()->to('/auth/verify?email=' . urlencode($email))
            ->with('success', 'Kode OTP telah dikirim ke email Anda.');
    }
}

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
            'no_hp' => [
                'rules'  => 'required|numeric|min_length[10]|max_length[15]',
                'errors' => [
                    'required'   => 'Nomor HP wajib diisi.',
                    'numeric'    => 'Nomor HP harus berupa angka.',
                    'min_length' => 'Nomor HP minimal 10 digit.',
                    'max_length' => 'Nomor HP maksimal 15 digit.',
                ]
            ],
            'institusi' => [
                'rules'  => 'required|min_length[3]',
                'errors' => [
                    'required'   => 'Institusi wajib diisi.',
                    'min_length' => 'Institusi minimal 3 karakter.',
                ]
            ],
            'jenis_peserta' => [
                'rules'  => 'required|in_list[mahasiswa,dosen,peneliti,umum,lainnya]',
                'errors' => [
                    'required' => 'Jenis peserta wajib dipilih.',
                    'in_list'  => 'Jenis peserta tidak valid.',
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
        $namaLengkap   = (string) $this->request->getPost('nama_lengkap');
        $email         = strtolower(trim((string) $this->request->getPost('email')));
        $password      = (string) $this->request->getPost('password');
        $role          = (string) $this->request->getPost('role'); // presenter|audience
        $noHp          = (string) $this->request->getPost('no_hp');
        $institusi     = (string) $this->request->getPost('institusi');
        $jenisPeserta  = (string) $this->request->getPost('jenis_peserta');

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
            'no_hp'         => $noHp,
            'institusi'     => $institusi,
            'jenis_peserta' => $jenisPeserta,
            'otp_code'      => $otp,
            'otp_expired'   => $expired,
            'updated_at'    => date('Y-m-d H:i:s'),
        ];

        if ($pendingSame) {
            // Update data pending yang sudah ada
            $pendingModel->update($pendingSame['id'], $payload);
        } else {
            // Insert pendaftaran baru
            $payload['created_at'] = date('Y-m-d H:i:s');
            $pendingModel->insert($payload);
        }

        // Kirim OTP setiap kali registrasi (baik baru maupun update) dengan error handling lebih detail
        $emailSent = false;
        $emailError = '';
        
        try {
            // Clear previous email instance
            $mail = \Config\Services::email();
            $mail->clear();
            
            // Set email configuration
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
            
            // Attempt to send
            $emailSent = $mail->send();
            
            if (!$emailSent) {
                $emailError = $mail->printDebugger(['headers', 'subject', 'body']);
                log_message('error', 'Email OTP gagal dikirim: ' . $emailError);
            } else {
                log_message('info', 'Email OTP berhasil dikirim ke: ' . $email);
            }
            
        } catch (\Throwable $e) {
            $emailError = $e->getMessage();
            log_message('error', 'Exception kirim email OTP: ' . $emailError);
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
        }

        // Simpan email dan OTP di session untuk ditampilkan di member area
        session()->set([
            'email_verifikasi' => $email,
            'show_otp_code' => $otp,        // OTP ditampilkan di halaman verify
            'otp_created_at' => time(),     // Untuk tracking waktu generate
            'email_sent_status' => $emailSent // Status pengiriman email
        ]);

        // Pesan disesuaikan dengan status pengiriman email
        if ($pendingSame) {
            $msg = $emailSent 
                ? 'Kode OTP baru telah dikirim ke email Anda dan ditampilkan di bawah.'
                : 'Kode OTP ditampilkan di bawah. Email gagal dikirim, silakan gunakan kode yang tersedia.';
        } else {
            $msg = $emailSent
                ? 'Kode OTP telah dikirim ke email Anda dan ditampilkan di bawah. Silakan masukkan kode untuk verifikasi.'
                : 'Kode OTP ditampilkan di bawah. Email gagal dikirim, silakan gunakan kode yang tersedia.';
        }
        
        return redirect()->to('/auth/verify?email=' . urlencode($email))
            ->with('success', $msg);
    }
}
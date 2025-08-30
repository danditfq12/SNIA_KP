<?php

namespace App\Controllers\Role\Admin;

use App\Controllers\BaseController;
use App\Models\DokumenModel;
use App\Models\UserModel;
use App\Models\AbstrakModel;

class Dokumen extends BaseController
{
    protected $dokumenModel;
    protected $userModel;
    protected $abstrakModel;

    public function __construct()
    {
        $this->dokumenModel = new DokumenModel();
        $this->userModel = new UserModel();
        $this->abstrakModel = new AbstrakModel();
    }

    public function index()
    {
        // Get all documents with user info
        $dokumens = $this->dokumenModel->getDokumenWithUser();

        // Separate by type
        $loa_documents = array_filter($dokumens, fn($d) => $d['tipe'] === 'LOA');
        $sertifikat_documents = array_filter($dokumens, fn($d) => $d['tipe'] === 'Sertifikat');

        // Get users who need LOA (presenters with accepted abstracts)
        $needLOA = $this->abstrakModel
                       ->select('abstrak.id_user, users.nama_lengkap, users.email, COUNT(abstrak.id_abstrak) as total_accepted')
                       ->join('users', 'users.id_user = abstrak.id_user')
                       ->where('abstrak.status', 'diterima')
                       ->where('users.role', 'presenter')
                       ->groupBy('abstrak.id_user, users.nama_lengkap, users.email')
                       ->findAll();

        // Filter out users who already have LOA
        $existingLOA = array_column($loa_documents, 'id_user');
        $needLOA = array_filter($needLOA, fn($user) => !in_array($user['id_user'], $existingLOA));

        // Get users who need certificates (all active users with verified payments)
        $needSertifikat = $this->userModel
                              ->select('users.*, pembayaran.status as payment_status')
                              ->join('pembayaran', 'pembayaran.id_user = users.id_user', 'left')
                              ->where('users.status', 'aktif')
                              ->whereIn('users.role', ['presenter', 'audience'])
                              ->where('pembayaran.status', 'verified')
                              ->findAll();

        $existingSertifikat = array_column($sertifikat_documents, 'id_user');
        $needSertifikat = array_filter($needSertifikat, fn($user) => !in_array($user['id_user'], $existingSertifikat));

        $data = [
            'loa_documents' => $loa_documents,
            'sertifikat_documents' => $sertifikat_documents,
            'need_loa' => $needLOA,
            'need_sertifikat' => $needSertifikat,
            'total_loa' => count($loa_documents),
            'total_sertifikat' => count($sertifikat_documents),
            'pending_loa' => count($needLOA),
            'pending_sertifikat' => count($needSertifikat)
        ];

        return view('role/admin/dokumen/index', $data);
    }

    public function uploadLoa($userId)
    {
        $user = $this->userModel->find($userId);
        
        if (!$user) {
            return redirect()->back()->with('error', 'User tidak ditemukan.');
        }

        // Check if user has accepted abstracts
        $acceptedAbstrak = $this->abstrakModel->where('id_user', $userId)
                                             ->where('status', 'diterima')
                                             ->countAllResults();

        if ($acceptedAbstrak == 0) {
            return redirect()->back()->with('error', 'User tidak memiliki abstrak yang diterima.');
        }

        $validation = \Config\Services::validation();
        
        $rules = [
            'loa_file' => 'uploaded[loa_file]|max_size[loa_file,5120]|ext_in[loa_file,pdf]'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->with('errors', $validation->getErrors());
        }

        $file = $this->request->getFile('loa_file');
        
        if ($file->isValid() && !$file->hasMoved()) {
            $fileName = 'LOA_' . $userId . '_' . time() . '.pdf';
            $file->move(WRITEPATH . 'uploads/dokumen/', $fileName);

            // Save to database
            $dokumenData = [
                'id_user' => $userId,
                'tipe' => 'LOA',
                'file_path' => $fileName,
                'syarat' => 'Abstrak diterima',
                'uploaded_at' => date('Y-m-d H:i:s')
            ];

            if ($this->dokumenModel->save($dokumenData)) {
                return redirect()->to('admin/dokumen')->with('success', 'LOA berhasil diupload!');
            } else {
                // Remove uploaded file if database save fails
                unlink(WRITEPATH . 'uploads/dokumen/' . $fileName);
                return redirect()->back()->with('error', 'Gagal menyimpan data LOA.');
            }
        }

        return redirect()->back()->with('error', 'File tidak valid atau gagal diupload.');
    }

    public function uploadSertifikat($userId)
    {
        $user = $this->userModel->find($userId);
        
        if (!$user) {
            return redirect()->back()->with('error', 'User tidak ditemukan.');
        }

        // Check if user has verified payment
        $db = \Config\Database::connect();
        $payment = $db->table('pembayaran')
                     ->where('id_user', $userId)
                     ->where('status', 'verified')
                     ->get()->getRow();

        if (!$payment) {
            return redirect()->back()->with('error', 'User belum memiliki pembayaran yang terverifikasi.');
        }

        $validation = \Config\Services::validation();
        
        $rules = [
            'sertifikat_file' => 'uploaded[sertifikat_file]|max_size[sertifikat_file,5120]|ext_in[sertifikat_file,pdf]'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->with('errors', $validation->getErrors());
        }

        $file = $this->request->getFile('sertifikat_file');
        
        if ($file->isValid() && !$file->hasMoved()) {
            $fileName = 'CERT_' . $userId . '_' . time() . '.pdf';
            $file->move(WRITEPATH . 'uploads/dokumen/', $fileName);

            // Save to database
            $dokumenData = [
                'id_user' => $userId,
                'tipe' => 'Sertifikat',
                'file_path' => $fileName,
                'syarat' => 'Pembayaran terverifikasi',
                'uploaded_at' => date('Y-m-d H:i:s')
            ];

            if ($this->dokumenModel->save($dokumenData)) {
                return redirect()->to('admin/dokumen')->with('success', 'Sertifikat berhasil diupload!');
            } else {
                // Remove uploaded file if database save fails
                unlink(WRITEPATH . 'uploads/dokumen/' . $fileName);
                return redirect()->back()->with('error', 'Gagal menyimpan data sertifikat.');
            }
        }

        return redirect()->back()->with('error', 'File tidak valid atau gagal diupload.');
    }

    public function download($id)
    {
        $dokumen = $this->dokumenModel->find($id);
        
        if (!$dokumen) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Dokumen tidak ditemukan.');
        }

        $filePath = WRITEPATH . 'uploads/dokumen/' . $dokumen['file_path'];
        
        if (!file_exists($filePath)) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('File tidak ditemukan.');
        }

        return $this->response->download($filePath, null);
    }

    public function delete($id)
    {
        $dokumen = $this->dokumenModel->find($id);
        
        if (!$dokumen) {
            return redirect()->back()->with('error', 'Dokumen tidak ditemukan.');
        }

        $filePath = WRITEPATH . 'uploads/dokumen/' . $dokumen['file_path'];
        
        // Delete file
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        // Delete from database
        if ($this->dokumenModel->delete($id)) {
            return redirect()->back()->with('success', 'Dokumen berhasil dihapus!');
        } else {
            return redirect()->back()->with('error', 'Gagal menghapus dokumen.');
        }
    }

    public function generateBulkLOA()
    {
        // Get all users who need LOA but don't have it yet
        $needLOA = $this->abstrakModel
                       ->select('abstrak.id_user, users.nama_lengkap, users.email')
                       ->join('users', 'users.id_user = abstrak.id_user')
                       ->where('abstrak.status', 'diterima')
                       ->where('users.role', 'presenter')
                       ->groupBy('abstrak.id_user, users.nama_lengkap, users.email')
                       ->findAll();

        $existingLOA = $this->dokumenModel->where('tipe', 'LOA')
                                         ->select('id_user')
                                         ->findAll();
        $existingLOAIds = array_column($existingLOA, 'id_user');

        $generated = 0;
        foreach ($needLOA as $user) {
            if (!in_array($user['id_user'], $existingLOAIds)) {
                // Generate LOA template (you can implement PDF generation here)
                $fileName = 'LOA_' . $user['id_user'] . '_' . time() . '.pdf';
                
                // For demo purposes, create a dummy file
                $this->generateLOATemplate($user, WRITEPATH . 'uploads/dokumen/' . $fileName);
                
                $dokumenData = [
                    'id_user' => $user['id_user'],
                    'tipe' => 'LOA',
                    'file_path' => $fileName,
                    'syarat' => 'Auto-generated',
                    'uploaded_at' => date('Y-m-d H:i:s')
                ];

                $this->dokumenModel->save($dokumenData);
                $generated++;
            }
        }

        return redirect()->back()->with('success', "Berhasil generate $generated LOA!");
    }

    public function generateBulkSertifikat()
    {
        // Get all users who need certificates
        $needSertifikat = $this->userModel
                              ->select('users.*')
                              ->join('pembayaran', 'pembayaran.id_user = users.id_user')
                              ->where('users.status', 'aktif')
                              ->whereIn('users.role', ['presenter', 'audience'])
                              ->where('pembayaran.status', 'verified')
                              ->findAll();

        $existingSertifikat = $this->dokumenModel->where('tipe', 'Sertifikat')
                                                ->select('id_user')
                                                ->findAll();
        $existingSertifikatIds = array_column($existingSertifikat, 'id_user');

        $generated = 0;
        foreach ($needSertifikat as $user) {
            if (!in_array($user['id_user'], $existingSertifikatIds)) {
                // Generate certificate template
                $fileName = 'CERT_' . $user['id_user'] . '_' . time() . '.pdf';
                
                // For demo purposes, create a dummy file
                $this->generateSertifikatTemplate($user, WRITEPATH . 'uploads/dokumen/' . $fileName);
                
                $dokumenData = [
                    'id_user' => $user['id_user'],
                    'tipe' => 'Sertifikat',
                    'file_path' => $fileName,
                    'syarat' => 'Auto-generated',
                    'uploaded_at' => date('Y-m-d H:i:s')
                ];

                $this->dokumenModel->save($dokumenData);
                $generated++;
            }
        }

        return redirect()->back()->with('success', "Berhasil generate $generated Sertifikat!");
    }

    private function generateLOATemplate($user, $filePath)
    {
        // Simple PDF template generation (you can use TCPDF or similar library)
        $content = "Letter of Acceptance\n\nDear {$user['nama_lengkap']},\n\nYour abstract has been accepted for presentation at SNIA Conference.\n\nBest regards,\nOrganizing Committee";
        
        // Create a simple text file for demo (replace with actual PDF generation)
        file_put_contents($filePath, $content);
    }

    private function generateSertifikatTemplate($user, $filePath)
    {
        // Simple certificate template
        $content = "Certificate of Participation\n\nThis certifies that\n\n{$user['nama_lengkap']}\n\nhas successfully participated in SNIA Conference\n\nOrganizing Committee";
        
        // Create a simple text file for demo (replace with actual PDF generation)
        file_put_contents($filePath, $content);
    }
}
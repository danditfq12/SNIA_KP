<?php

namespace App\Controllers\Role\Presenter;

use App\Controllers\BaseController;
use App\Models\DokumenModel;

class Dokumen extends BaseController
{
    protected $dokumenModel;

    public function __construct()
    {
        $this->dokumenModel = new DokumenModel();
    }

    /**
     * Index: daftar dokumen milik presenter
     */
    public function index()
    {
        $userId   = session()->get('id_user');
        $dokumen  = $this->dokumenModel->getUserDocs($userId);

        return view('role/presenter/dokumen/index', [
            'title'   => 'Dokumen Saya',
            'dokumen' => $dokumen,
        ]);
    }

    /**
     * Download LOA
     */
    public function downloadLoa($idDokumen)
    {
        $userId = session()->get('id_user');
        $doc    = $this->dokumenModel->getOneWithDetails($idDokumen);

        if (!$doc || $doc['id_user'] != $userId || $doc['tipe'] !== 'loa') {
            return redirect()->to('/presenter/dokumen')->with('error', 'Dokumen LOA tidak ditemukan.');
        }

        $filePath = WRITEPATH . 'uploads/loa/' . $doc['file_path'];
        if (!file_exists($filePath)) {
            return redirect()->to('/presenter/dokumen')->with('error', 'File LOA tidak tersedia.');
        }

        return $this->response->download($filePath, null)->setFileName('LOA_' . $doc['event_title'] . '.pdf');
    }

    /**
     * Download Sertifikat
     */
    public function downloadSertifikat($idDokumen)
    {
        $userId = session()->get('id_user');
        $doc    = $this->dokumenModel->getOneWithDetails($idDokumen);

        if (!$doc || $doc['id_user'] != $userId || $doc['tipe'] !== 'sertifikat') {
            return redirect()->to('/presenter/dokumen')->with('error', 'Dokumen sertifikat tidak ditemukan.');
        }

        $filePath = WRITEPATH . 'uploads/sertifikat/' . $doc['file_path'];
        if (!file_exists($filePath)) {
            return redirect()->to('/presenter/dokumen')->with('error', 'File sertifikat tidak tersedia.');
        }

        return $this->response->download($filePath, null)->setFileName('SERTIFIKAT_' . $doc['event_title'] . '.pdf');
    }
}
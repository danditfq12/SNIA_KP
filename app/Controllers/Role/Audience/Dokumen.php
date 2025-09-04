<?php
namespace App\Controllers\Role\Audience;

use App\Controllers\BaseController;
use App\Models\DokumenModel;

class Dokumen extends BaseController
{
    public function sertifikat()
    {
        $idUser = (int) (session('id_user') ?? 0);
        $docs   = (new DokumenModel())->listSertifikatByUser($idUser);

        return view('role/audience/dokumen/sertifikat', [
            'certs' => $docs,
        ]);
    }

    /**
     * /audience/dokumen/sertifikat/download/{id_dokumen|namaFile}?preview=1
     */
    public function downloadSertifikat($segment)
    {
        $idUser = (int) (session('id_user') ?? 0);
        $dm     = new DokumenModel();

        // Ambil dokumen berdasarkan id atau nama file
        if (ctype_digit((string)$segment)) {
            $doc = $dm->where('id_user', $idUser)->where('tipe', 'sertifikat')
                      ->find((int)$segment);
        } else {
            $doc = $dm->where('id_user', $idUser)->where('tipe', 'sertifikat')
                      ->where('file_path', $segment)->first();
        }

        if (!$doc) {
            return redirect()->back()->with('error', 'Sertifikat tidak ditemukan / bukan milik Anda.');
        }

        $abs = $dm->resolveAbsolutePath($doc);

        // Jika file_path adalah URL (drive/s3), arahkan langsung
        if ($abs && preg_match('~^https?://~i', $abs)) {
            return redirect()->to($abs);
        }

        if (!$abs || !is_file($abs)) {
            return redirect()->back()->with('error', 'File sertifikat tidak ditemukan di server.');
        }

        // Preview inline jika diminta
        if ($this->request->getGet('preview')) {
            $ext = strtolower(pathinfo($abs, PATHINFO_EXTENSION));
            $mime = $ext === 'pdf' ? 'application/pdf'
                  : (in_array($ext, ['jpg','jpeg']) ? 'image/jpeg'
                  : ($ext === 'png' ? 'image/png' : null));

            if ($mime) {
                return $this->response
                    ->setHeader('Content-Type', $mime)
                    ->setHeader('Content-Disposition', 'inline; filename="'.basename($abs).'"')
                    ->setBody(file_get_contents($abs));
            }
        }

        // Default: force download
        return $this->response->download($abs, null)->setFileName(basename($abs));
    }
}

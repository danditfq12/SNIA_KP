<?php

namespace App\Controllers;

use App\Models\UserModel;

class Profile extends BaseController
{
    protected $userModel;
    protected $db;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->db        = \Config\Database::connect();
        helper(['filesystem','text']);
    }

    /* ===== Helpers ===== */

    private function tExists(string $t): bool { return $this->db->tableExists($t); }

    private function countJoinedEvents(int $userId): int
    {
        $cands = [
            ['table' => 'event_registrations', 'user' => 'id_user'],
            ['table' => 'peserta_event',       'user' => 'id_user'],
            ['table' => 'registrations',       'user' => 'user_id'],
        ];
        foreach ($cands as $c) {
            if ($this->tExists($c['table'])) {
                return (int) $this->db->table($c['table'])
                    ->where($c['user'], $userId)->countAllResults();
            }
        }
        return 0;
    }

    /** Ambil dokumen user; selalu kembalikan ['loa'=>[], 'sertifikat'=>[]] */
    private function getUserDocs(int $userId): array
    {
        $docs = ['loa'=>[], 'sertifikat'=>[]];

        $docTables = [
            ['table'=>'dokumen',   'user'=>'id_user', 'type'=>'tipe', 'path'=>'file_path'],
            ['table'=>'documents', 'user'=>'user_id', 'type'=>'type', 'path'=>'path'],
        ];

        foreach ($docTables as $d) {
            if (!$this->tExists($d['table'])) continue;

            $fields   = array_flip($this->db->getFieldNames($d['table']) ?: []);
            $nameCand = ['nama_file','nama_dokumen','filename','original_name','name','judul','title'];
            $nameCol  = null;
            foreach ($nameCand as $c) { if (isset($fields[$c])) { $nameCol = $c; break; } }
            $createdCol = isset($fields['created_at']) ? 'created_at' : null;

            $select = "{$d['type']} AS tipe, {$d['path']} AS path";
            $select .= $nameCol ? ", {$nameCol} AS filename" : ", {$d['path']} AS filename";
            if ($createdCol) $select .= ", {$createdCol}";

            $rows = $this->db->table($d['table'])
                ->select($select)
                ->where($d['user'], $userId)
                ->whereIn($d['type'], ['loa','sertifikat'])
                ->orderBy($createdCol ?: $d['path'], 'DESC')
                ->get()->getResultArray();

            foreach ($rows as $r) {
                $key   = strtolower($r['tipe']) === 'loa' ? 'loa' : 'sertifikat';
                $fname = trim((string)($r['filename'] ?? '')) !== '' ? $r['filename'] : basename((string)$r['path']);
                $docs[$key][] = [
                    'name'       => $fname,
                    'path'       => $r['path'],
                    'created_at' => $createdCol ? ($r[$createdCol] ?? null) : null,
                ];
            }
            if ($rows) return $docs; // cukup dari salah satu tabel
        }

        // Fallback: scan folder publik
        $map = ['loa'=>'uploads/loa', 'sertifikat'=>'uploads/sertifikat'];
        foreach ($map as $key=>$dirRel) {
            $dir = FCPATH.$dirRel;
            if (!is_dir($dir)) continue;
            $patterns = ["*_{$userId}.*", "{$userId}_*.*", "*-{$userId}.*", "{$userId}.*"];
            foreach ($patterns as $p) {
                foreach (glob(rtrim($dir, '/')."/".$p) ?: [] as $f) {
                    $docs[$key][] = [
                        'name'       => basename($f),
                        'path'       => $dirRel.'/'.basename($f),
                        'created_at' => null,
                    ];
                }
            }
        }

        return $docs;
    }

    private function photoUrl(?string $fname): string
    {
        $file = trim((string)$fname) !== '' ? $fname : 'default.png';
        if (preg_match('~^https?://~', $file)) return $file;
        if (is_file(FCPATH.'uploads/profile/'.$file)) {
            return base_url('uploads/profile/'.$file).'?v='.((int) session('foto_ver') ?: time());
        }
        if (is_file(FCPATH.'uploads/profile/default.png')) {
            return base_url('uploads/profile/default.png');
        }
        return 'https://ui-avatars.com/api/?name='.rawurlencode(session('nama_lengkap') ?: 'User').'&background=6366f1&color=fff';
    }

    /* ===== Pages ===== */

    public function index()
    {
        $uid  = (int) session('id_user');
        if (!$uid) return redirect()->to('/auth/login');

        $user = $this->userModel->find($uid);
        if (!$user) return redirect()->to('/auth/login');

        // role dipakai lintas-role (admin/presenter/audience)
        $role        = strtolower(trim((string)($user['role'] ?? session('role') ?? 'audience'))) ?: 'audience';
        $eventsCount = $this->countJoinedEvents($uid);
        $docs        = $this->getUserDocs($uid);

        // jika bukan presenter → sembunyikan LOA dari tampilan
        if ($role !== 'presenter') $docs['loa'] = [];

        return view('profile/index', [
            'title'        => 'Profil Saya',
            'user'         => $user,
            'role'         => $role,
            'avatar'       => $this->photoUrl($user['foto'] ?? null),
            'events_count' => $eventsCount,
            'loa_count'    => count($docs['loa']),
            'cert_count'   => count($docs['sertifikat']),
            'docs'         => $docs,
        ]);
    }

    public function update()
    {
        $uid = (int) session('id_user');
        if (!$uid) return redirect()->to('/auth/login');

        $data = [
            'nama_lengkap' => (string) $this->request->getPost('nama_lengkap'),
            'jenis_peserta' => (string) $this->request->getPost('jenis_peserta'),
            'institusi'    => (string) $this->request->getPost('institusi'),
            'no_hp'        => (string) $this->request->getPost('no_hp'),
            'updated_at'   => date('Y-m-d H:i:s'),
        ];

        $fileFoto = $this->request->getFile('foto');
        if ($fileFoto && $fileFoto->isValid() && !$fileFoto->hasMoved()) {
            $resp = $this->processPhoto($fileFoto, $uid);
            if ($resp !== true) return redirect()->back()->with('error', $resp);
        } else {
            $this->userModel->update($uid, $data);
        }

        if (!empty($data['nama_lengkap'])) {
            session()->set('nama_lengkap', $data['nama_lengkap']);
            session()->set('nama', $data['nama_lengkap']);
        }

        return redirect()->to('/profile')->with('success', 'Profil berhasil diperbarui');
    }

    public function uploadPhoto()
    {
        $uid = (int) session('id_user');
        if (!$uid) return redirect()->to('/auth/login');

        $file = $this->request->getFile('foto');
        if (!$file || !$file->isValid()) {
            return redirect()->to('/profile')->with('error', 'File foto tidak valid.');
        }

        $resp = $this->processPhoto($file, $uid);
        if ($resp !== true) return redirect()->to('/profile')->with('error', $resp);

        return redirect()->to('/profile')->with('success', 'Foto profil diperbarui.');
    }

    private function processPhoto($fileFoto, int $uid)
    {
        $ext  = strtolower($fileFoto->getClientExtension() ?: '');
        if (!in_array($ext, ['jpg','jpeg','png','webp'])) return 'Format foto harus jpg/jpeg/png/webp.';
        if ($fileFoto->getSize() > 2*1024*1024)          return 'Ukuran foto maksimal 2MB.';

        $dir = FCPATH.'uploads/profile';
        if (!is_dir($dir)) @mkdir($dir, 0777, true);

        $newName = 'u'.$uid.'_'.time().'.'.$ext;
        try { $fileFoto->move($dir, $newName); }
        catch (\Throwable $e) { return 'Gagal menyimpan foto: '.$e->getMessage(); }

        $this->userModel->update($uid, ['foto'=>$newName, 'updated_at'=>date('Y-m-d H:i:s')]);
        session()->set('foto', $newName);
        session()->set('foto_ver', time());
        return true;
    }

    public function changePassword()
    {
        $uid = (int) session('id_user');
        if (!$uid) return redirect()->to('/auth/login');

        $user    = $this->userModel->find($uid);
        $old     = trim((string) $this->request->getPost('old_password'));
        $new     = trim((string) $this->request->getPost('new_password'));
        $confirm = trim((string) $this->request->getPost('confirm_password'));

        // wajib isi semua
        if ($old === '' || $new === '' || $confirm === '') {
            return redirect()->back()->with('error', 'Semua field password wajib diisi.');
        }
        if (!$user || !password_verify($old, $user['password'])) {
            return redirect()->back()->with('error', 'Password lama salah');
        }
        if (strlen($new) < 6) {
            return redirect()->back()->with('error', 'Panjang password minimal 6 karakter.');
        }
        if ($new !== $confirm) {
            return redirect()->back()->with('error', 'Konfirmasi password tidak sesuai.');
        }

        $this->userModel->update($uid, [
            'password'   => password_hash($new, PASSWORD_DEFAULT),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to('/profile')->with('success', 'Password berhasil diubah');
    }
}
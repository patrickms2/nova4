<?php
/**
 * Module: AdminController
 * Created: 2026-06-23
 * Author: Raditya Natha Azra
 * Synopsis: Controller untuk manajemen administrasi anggota, jabatan, dan ekspor data UKM
 * 
 * Functions:
 *   - dashboard() : view -> Tampilkan dashboard admin/pengurus
 *   - indexAnggota() : view -> Daftar anggota UKM
 *   - createAnggota() : view -> Form tambah anggota UKM
 *   - storeAnggota(Request) : redirect -> Simpan anggota UKM baru
 *   - editAnggota($id) : view -> Form edit anggota UKM
 *   - updateAnggota(Request, $id) : redirect -> Update data anggota UKM
 *   - destroyAnggota($id) : redirect -> Hapus anggota UKM
 *   - updateJabatan(Request, $id) : redirect -> Update jabatan anggota UKM
 *   - exportAnggota($ukm_id) : stream -> Ekspor data anggota ke CSV
 *   - cetakAnggota($ukm_id) : view -> Halaman cetak laporan anggota
 * 
 * Input Parameters:
 *   - ukm_id : int -> ID UKM
 *   - user_id : int -> ID User
 *   - jabatan : string -> Jabatan anggota (ketua, sekretaris, anggota)
 * 
 * Return Values:
 *   - 0 : gagal
 *   - 1 : berhasil
 */

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Ukm;
use App\Models\Pendaftaran;
use App\Models\AnggotaUkm;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function dashboard()
    {
        $user = auth()->user();
        if (!$user->isAdmin() && !$user->isPengurus()) {
            return redirect()->route('mahasiswa.index');
        }

        // Pengurus (Ketua/Sekretaris): hanya data UKM mereka sendiri
        if ($user->isPengurus()) {
            $myUkmId = $user->getUkmId();
            $myUkm = Ukm::find($myUkmId);
            $totalAnggota = AnggotaUkm::where('ukm_id', $myUkmId)->count();
            $anggotaAktif = AnggotaUkm::where('ukm_id', $myUkmId)->count();
            $anggotaList = AnggotaUkm::with(['user', 'ukm'])->where('ukm_id', $myUkmId)->get();

            return view('admin.dashboard', compact(
                'totalAnggota',
                'anggotaAktif',
                'anggotaList',
                'myUkm'
            ));
        }

        // Admin: data lengkap
        $totalMahasiswa = User::where('Role', '!=', 'administrator')->count();
        $totalUkm = Ukm::count();
        $totalAnggota = AnggotaUkm::count();
        $pendaftaranPending = Pendaftaran::where('status', 'pending')->count();
        $pendaftaranList = Pendaftaran::with(['user', 'ukm'])->latest()->take(10)->get();

        return view('admin.dashboard', compact(
            'totalMahasiswa',
            'totalUkm',
            'totalAnggota',
            'pendaftaranPending',
            'pendaftaranList'
        ));
    }

    public function indexAnggota()
    {
        $user = auth()->user();

        // ADMIN: lihat semua anggota
        if ($user->isAdmin()) {
            $anggotaList = AnggotaUkm::with(['user', 'ukm'])->get();
            $ukmList = Ukm::all();
            return view('admin.anggota.index', compact('anggotaList', 'ukmList'));
        }

        // ANGGOTA: hanya lihat anggota satu UKM
        $myUkmId = AnggotaUkm::where('user_id', $user->id)->first()?->ukm_id;
        $anggotaList = AnggotaUkm::with(['user', 'ukm'])
            ->when($myUkmId, fn($q) => $q->where('ukm_id', $myUkmId))
            ->get();
        $ukmList = Ukm::where('id', $myUkmId)->get();

        return view('anggota.ukm.index', compact('anggotaList', 'ukmList'));
    }

    public function createAnggota()
    {
        $mahasiswaList = User::where('Role', '!=', 'administrator')
            ->where('status', 'approved')
            ->doesntHave('anggotaAktif')
            ->get();
        $ukmList = Ukm::all();
        return view('admin.anggota.create', compact('mahasiswaList', 'ukmList'));
    }

    public function storeAnggota(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'ukm_id' => 'required|exists:ukms,id',
            'jabatan' => 'required|in:ketua,sekretaris,anggota',
        ]);

        $user = User::findOrFail($request->user_id);

        // Cek belum jadi anggota
        if ($user->anggotaAktif) {
            return back()->with('error', 'User sudah terdaftar di UKM lain');
        }

        $currentUser = auth()->user();

        // Pengurus: buat Pendaftaran (pending) — admin yg approve
        if ($currentUser->isPengurus()) {
            $existing = Pendaftaran::where('user_id', $user->id)
                ->where('ukm_id', $request->ukm_id)
                ->where('status', 'pending')
                ->exists();
            if ($existing) {
                return back()->with('error', 'Mahasiswa ini sudah pernah didaftarkan dan menunggu persetujuan admin.');
            }

            Pendaftaran::create([
                'user_id' => $user->id,
                'ukm_id' => $request->ukm_id,
                'status' => 'pending',
            ]);

            return redirect()->route('ukm.index')->with('success', 'Anggota berhasil didaftarkan. Menunggu persetujuan admin.');
        }

        // Cek constraint: hanya 1 ketua dan 1 sekretaris per UKM
        if (in_array($request->jabatan, ['ketua', 'sekretaris'])) {
            $exists = AnggotaUkm::where('ukm_id', $request->ukm_id)
                ->where('jabatan', $request->jabatan)
                ->exists();
            if ($exists) {
                $label = $request->jabatan === 'ketua' ? 'Ketua' : 'Sekretaris';
                return back()->with('error', "UKM ini sudah memiliki {$label}. Hanya boleh 1 {$label} per UKM.");
            }
        }

        $ukm = Ukm::find($request->ukm_id);

        $user->update([
            'Role' => $request->jabatan,
            'UKM' => $ukm->nama,
        ]);

        AnggotaUkm::create([
            'user_id' => $user->id,
            'ukm_id' => $request->ukm_id,
            'jabatan' => $request->jabatan,
            'tanggal_bergabung' => now(),
        ]);

        return redirect()->route('ukm.index')->with('success', 'Anggota berhasil ditambahkan sebagai ' . ucfirst($request->jabatan));
    }

    public function editAnggota($id)
    {
        $anggotaData = AnggotaUkm::with('user')->findOrFail($id);
        $ukmList = Ukm::all();
        return view('admin.anggota.edit', compact('anggotaData', 'ukmList'));
    }

    public function updateAnggota(Request $request, $id)
    {
        $anggotaData = AnggotaUkm::findOrFail($id);

        $request->validate([
            'nama' => 'required',
            'nim' => 'required|unique:users,nim,' . $anggotaData->user_id,
            'kelas' => 'required',
            'prodi' => 'required',
            'jurusan' => 'required',
            'ukm_id' => 'required|exists:ukms,id',
        ]);

        $anggotaData->user->update($request->only(['nama', 'nim', 'kelas', 'prodi', 'jurusan']));
        $anggotaData->update(['ukm_id' => $request->ukm_id]);

        return redirect()->route('admin.anggota.index')->with('success', 'Anggota berhasil diupdate');
    }

    public function destroyAnggota($id)
    {
        AnggotaUkm::findOrFail($id)->delete();
        return back()->with('success', 'Anggota berhasil dihapus');
    }

    public function updateJabatan(Request $request, $id)
    {
        $anggota = AnggotaUkm::findOrFail($id);

        $request->validate([
            'jabatan' => 'required|in:ketua,sekretaris,anggota',
        ]);

        // Cek constraint: hanya 1 ketua dan 1 sekretaris per UKM
        if (in_array($request->jabatan, ['ketua', 'sekretaris'])) {
            $exists = AnggotaUkm::where('ukm_id', $anggota->ukm_id)
                ->where('jabatan', $request->jabatan)
                ->where('id', '!=', $id)
                ->exists();
            if ($exists) {
                $label = ucfirst($request->jabatan);
                return back()->with('error', "UKM ini sudah memiliki {$label}. Hanya boleh 1 {$label} per UKM.");
            }
        }

        $anggota->update(['jabatan' => $request->jabatan]);
        $anggota->user->update(['Role' => $request->jabatan]);

        return back()->with('success', 'Jabatan berhasil diubah menjadi ' . ucfirst($request->jabatan));
    }

    public function exportAnggota($ukmId = null)
    {
        $query = AnggotaUkm::with(['user', 'ukm']);
        if ($ukmId) $query->where('ukm_id', $ukmId);
        $anggotaList = $query->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="anggota_ukm.csv"',
        ];

        $callback = function () use ($anggotaList) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['No', 'Nama', 'NIM', 'Kelas', 'Prodi', 'Jurusan', 'UKM', 'Tanggal Bergabung']);
            foreach ($anggotaList as $index => $a) {
                fputcsv($file, [
                    $index + 1,
                    $a->user->nama,
                    $a->user->nim,
                    $a->user->kelas,
                    $a->user->prodi,
                    $a->user->jurusan,
                    $a->ukm->nama,
                    $a->tanggal_bergabung->format('d-m-Y'),
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function cetakAnggota($ukmId = null)
    {
        $query = AnggotaUkm::with(['user', 'ukm']);
        if ($ukmId) $query->where('ukm_id', $ukmId);
        $anggotaList = $query->get();
        return view('admin.anggota.cetak', compact('anggotaList'));
    }
}

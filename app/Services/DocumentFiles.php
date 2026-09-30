<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Penyimpanan berkas aman untuk upload/ganti file concorrent:
 * - nama berkas selalu unik (hash) sehingga upload bersamaan tidak tabrakan
 * - ganti file: simpan baru dulu, DB commit, baru hapus lama (tidak pernah kehilangan berkas)
 * - tulis atomik (temp + rename) agar pembaca tidak mendapat file setengah tulis
 * - orphan (berkas tanpa record) dibersihkan bila transaksi gagal
 */
class DocumentFiles
{
    private const DISK = 'private';

    /**
     * Pastikan file yang dicoba diunggah valid. File yang dibuang PHP secara diam-diam
     * (melebihi upload_max_filesize) terdeteksi di sini agar tidak lolos tanpa pesan.
     */
    public static function ensureValidUpload(Request $request, string $key = 'file'): void
    {
        $file = $request->file($key);
        if ($file && ! $file->isValid()) {
            throw ValidationException::withMessages([
                $key => 'File gagal diunggah — ukurannya melebihi batas server (maks 9 MB). Kecilkan atau kompres dulu PDF-nya.',
            ]);
        }
    }

    /**
     * Jalankan $callback dalam transaksi; bila gagal, hapus berkas baru yang sempat tersimpan.
     *
     * @param  callable(array &$gate): mixed  $callback  isi $gate['paths'][] untuk berkas baru
     */
    public static function transactional(callable $callback): mixed
    {
        $gate = ['paths' => []];
        try {
            return DB::transaction(fn () => $callback($gate));
        } catch (\Throwable $e) {
            foreach ($gate['paths'] as $p) {
                Storage::disk(self::DISK)->delete($p);
            }
            throw $e;
        }
    }

    /** Simpan upload baru. Kembalikan [path, size]. */
    public static function storeUpload(UploadedFile $file, array &$gate): array
    {
        $path = $file->store('documents', self::DISK);
        $gate['paths'][] = $path;

        return [$path, $file->getSize()];
    }

    /** Ganti berkas lama dengan upload baru secara aman. Kembalikan [path, size]. */
    public static function replace(?string $oldPath, UploadedFile $file, array &$gate): array
    {
        [$path, $size] = static::storeUpload($file, $gate);
        if ($oldPath && $oldPath !== $path) {
            DB::afterCommit(fn () => Storage::disk(self::DISK)->delete($oldPath));
        }

        return [$path, $size];
    }

    /** Tulis konten secara atomik (aman dibaca saat upload bersamaan). */
    public static function putAtomically(string $path, string $contents): void
    {
        $tmp = dirname($path).'/tmp-'.Str::random(10).'.pdf';
        Storage::disk(self::DISK)->put($tmp, $contents);
        Storage::disk(self::DISK)->move($tmp, $path);
    }
}

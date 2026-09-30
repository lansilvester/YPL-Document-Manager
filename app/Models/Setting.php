<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Pengaturan web (khusus Super Admin) dalam pasangan kunci-nilai.
 * Nilai yang belum pernah disimpan memakai DEFAULTS sehingga instalasi baru tetap jalan.
 */
class Setting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    public $timestamps = true;

    protected static array $cache = [];

    public const DEFAULTS = [
        'site_name' => 'Lokon Dokumen Digital',
        'foundation_name' => 'Yayasan Pendidikan Lokon',
        'logo_path' => null,
        'reg_enabled' => '1',
        'upload_enabled' => '1',
        'editor_enabled' => '1',
        'download_enabled' => '1',
        'like_enabled' => '1',
        'favorite_enabled' => '1',
        'share_enabled' => '1',
        'embed_enabled' => '1',
        'reg_domains' => '',
    ];

    public const FEATURES = [
        'reg' => 'Pendaftaran akun baru',
        'upload' => 'Unggah PDF',
        'editor' => 'Tulis via editor',
        'download' => 'Unduh PDF & HTML',
        'like' => 'Tombol suka',
        'favorite' => 'Simpan favorit',
        'share' => 'Bagikan dokumen',
        'embed' => 'Sematkan (embed)',
    ];

    public static function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, static::$cache)) {
            return static::$cache[$key];
        }
        $row = static::find($key);
        $value = $row?->value ?? static::DEFAULTS[$key] ?? $default;
        static::$cache[$key] = $value;

        return $value;
    }

    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value === null ? null : (string) $value]);
        static::$cache[$key] = $value === null ? null : (string) $value;
    }

    public static function feature(string $name): bool
    {
        return static::get($name.'_enabled', '1') === '1';
    }

    public static function logoUrl(): string
    {
        $path = static::get('logo_path');
        if ($path) {
            return asset('storage/'.$path);
        }

        return asset('images/logo-ypl.jpg');
    }

    public static function flush(): void
    {
        static::$cache = [];
    }
}

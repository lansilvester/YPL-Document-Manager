<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Kode Reset Kata Sandi</title>
</head>
<body style="margin:0;padding:24px;background:#eef1f4;font-family:Arial,Helvetica,sans-serif;color:#1e293b">
  <div style="max-width:480px;margin:0 auto;background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #e2e8f0">
    <div style="background:#002f80;color:#ffffff;padding:20px 24px">
      <div style="font-size:12px;letter-spacing:.08em;opacity:.8;text-transform:uppercase">Yayasan Pendidikan Lokon</div>
      <div style="font-size:18px;font-weight:700;margin-top:4px">Kode Reset Kata Sandi</div>
    </div>
    <div style="padding:24px">
      <p style="margin:0 0 16px;line-height:1.6">Kami menerima permintaan mengatur ulang kata sandi akun Lokon Dokumen Digital Anda. Gunakan kode berikut:</p>
      <div style="font-size:30px;font-weight:700;letter-spacing:.4em;color:#002f80;background:#f1f5f9;border-radius:12px;padding:16px;text-align:center;font-family:'Courier New',monospace">{{ $code }}</div>
      <p style="margin:16px 0 0;line-height:1.6;font-size:14px;color:#475569">Kode berlaku <strong>{{ $ttl }} menit</strong> dan hanya dapat dipakai sekali. Bila Anda tidak meminta ini, abaikan email ini — kata sandi tidak berubah.</p>
    </div>
    <div style="padding:16px 24px;background:#f8fafc;font-size:12px;color:#64748b">
      Email otomatis dari YPL Smart Digital Library — tidak perlu dibalas.
    </div>
  </div>
</body>
</html>

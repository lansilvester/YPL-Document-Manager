<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Uji SMTP</title>
</head>
<body style="margin:0;padding:24px;background:#eef1f4;font-family:Arial,Helvetica,sans-serif;color:#1e293b">
  <div style="max-width:480px;margin:0 auto;background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #e2e8f0">
    <div style="background:#002f80;color:#ffffff;padding:20px 24px">
      <div style="font-size:12px;letter-spacing:.08em;opacity:.8;text-transform:uppercase">Yayasan Pendidikan Lokon</div>
      <div style="font-size:18px;font-weight:700;margin-top:4px">Konfigurasi SMTP Berhasil</div>
    </div>
    <div style="padding:24px">
      <p style="margin:0 0 12px;line-height:1.6">Email ini dikirim otomatis oleh perintah <strong>ypl:mail-test</strong> untuk memverifikasi pengiriman.</p>
      <p style="margin:0 0 4px;line-height:1.6;font-size:14px;color:#475569">Mailer aktif: <strong>{{ $mailer }}</strong></p>
      <p style="margin:0;line-height:1.6;font-size:14px;color:#475569">Waktu: <strong>{{ $waktu }}</strong></p>
    </div>
    <div style="padding:16px 24px;background:#f8fafc;font-size:12px;color:#64748b">
      Email otomatis dari YPL Smart Digital Library — tidak perlu dibalas.
    </div>
  </div>
</body>
</html>

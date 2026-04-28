<?php
$baseUrl = 'http://localhost:8000';

echo "=== DEBUG SESSION ===\n";

// Step 1: Ambil CSRF
$ch = curl_init($baseUrl . '/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Step 1 - GET /login: HTTP {$code}\n";

preg_match('/<meta name="csrf-token" content="([^"]+)"/', $response, $tm);
preg_match('/Set-Cookie:\s*laravel_session=([^;]+)/i', $response, $sm);
$token   = $tm[1] ?? '';
$session = $sm[1] ?? '';

echo "Token  : " . ($token   ? substr($token, 0, 30) . '...' : 'TIDAK ADA') . "\n";
echo "Session: " . ($session ? substr($session, 0, 30) . '...' : 'TIDAK ADA') . "\n\n";

if (empty($token) || empty($session)) {
    echo "ERROR: Gagal mendapatkan CSRF token. Pastikan php artisan serve berjalan.\n";
    exit(1);
}

// Step 2: Login
$ch2 = curl_init($baseUrl . '/login');
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_FOLLOWLOCATION, false);
curl_setopt($ch2, CURLOPT_HEADER, true);
curl_setopt($ch2, CURLOPT_TIMEOUT, 10);
curl_setopt($ch2, CURLOPT_POST, true);
curl_setopt($ch2, CURLOPT_POSTFIELDS, http_build_query([
    '_token'   => $token,
    'email'    => 'admin@admin.com',
    'password' => 'admin123',
]));
curl_setopt($ch2, CURLOPT_COOKIE, "laravel_session={$session}");
curl_setopt($ch2, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
$resp2 = curl_exec($ch2);
$code2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
curl_close($ch2);

echo "Step 2 - POST /login: HTTP {$code2}\n";

// Tampilkan semua Set-Cookie
preg_match_all('/Set-Cookie:\s*([^\r\n]+)/i', $resp2, $cookies);
echo "Cookies setelah login:\n";
foreach ($cookies[1] as $c) {
    echo "  " . substr($c, 0, 80) . "\n";
}

preg_match('/Location:\s*([^\r\n]+)/i', $resp2, $loc);
echo "Location: " . ($loc[1] ?? 'none') . "\n\n";

preg_match_all('/Set-Cookie:\s*laravel_session=([^;]+)/i', $resp2, $sm2);
$newSession = !empty($sm2[1]) ? end($sm2[1]) : $session;
echo "Session baru: " . substr($newSession, 0, 30) . "...\n\n";

// Step 3: Akses /admin dengan session baru
$ch3 = curl_init($baseUrl . '/admin');
curl_setopt($ch3, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch3, CURLOPT_FOLLOWLOCATION, false);
curl_setopt($ch3, CURLOPT_HEADER, true);
curl_setopt($ch3, CURLOPT_TIMEOUT, 10);
curl_setopt($ch3, CURLOPT_COOKIE, "laravel_session={$newSession}");
$resp3 = curl_exec($ch3);
$code3 = curl_getinfo($ch3, CURLINFO_HTTP_CODE);
curl_close($ch3);

preg_match('/Location:\s*([^\r\n]+)/i', $resp3, $loc3);
echo "Step 3 - GET /admin: HTTP {$code3}\n";
echo "Location: " . ($loc3[1] ?? 'none') . "\n";
echo "Kesimpulan: " . ($code3 === 200 ? "BERHASIL masuk dashboard" : "REDIRECT ke " . ($loc3[1] ?? 'unknown')) . "\n\n";

// Step 4: Coba dengan XSRF-TOKEN juga
preg_match('/Set-Cookie:\s*XSRF-TOKEN=([^;]+)/i', $resp2, $xsrf);
$xsrfToken = $xsrf[1] ?? '';
if ($xsrfToken) {
    echo "XSRF-TOKEN ditemukan: " . substr($xsrfToken, 0, 30) . "...\n";
    $ch4 = curl_init($baseUrl . '/admin');
    curl_setopt($ch4, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch4, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch4, CURLOPT_HEADER, true);
    curl_setopt($ch4, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch4, CURLOPT_COOKIE, "laravel_session={$newSession}; XSRF-TOKEN={$xsrfToken}");
    $resp4 = curl_exec($ch4);
    $code4 = curl_getinfo($ch4, CURLINFO_HTTP_CODE);
    curl_close($ch4);
    preg_match('/Location:\s*([^\r\n]+)/i', $resp4, $loc4);
    echo "Step 4 - GET /admin dengan XSRF: HTTP {$code4}\n";
    echo "Location: " . ($loc4[1] ?? 'none') . "\n";
}

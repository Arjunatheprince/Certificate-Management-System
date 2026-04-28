<?php
/**
 * Security Testing: Manipulasi Session
 * Sistem Autentikasi Sertifikat Digital
 *
 * Jalankan: php test_session_manipulation.php
 */

$baseUrl = 'http://localhost:8000';
$results = [];

// ── Helper: HTTP Request ───────────────────────────────────────
function makeRequest(string $url, string $method = 'GET', string $cookies = '', array $data = [], array $headers = []): array
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    if ($cookies) {
        curl_setopt($ch, CURLOPT_COOKIE, $cookies);
    }

    $defaultHeaders = ['Accept: text/html,application/json'];
    if (!empty($headers)) {
        $defaultHeaders = array_merge($defaultHeaders, $headers);
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $defaultHeaders);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    } elseif ($method === 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error    = curl_error($ch);
    curl_close($ch);

    return [
        'code'     => $httpCode,
        'response' => $response,
        'error'    => $error,
    ];
}

// ── Helper: Print Result ───────────────────────────────────────
function printResult(int $no, string $skenario, string $expected, int $actual, bool $status): void
{
    $icon  = $status ? "\033[32m[BERHASIL]\033[0m" : "\033[31m[GAGAL]\033[0m";
    $label = $status ? 'BERHASIL' : 'GAGAL';
    echo "\n{$icon} Skenario {$no}: {$skenario}";
    echo "\n  Expected : {$expected}";
    echo "\n  Actual   : HTTP {$actual}";
    echo "\n  Status   : {$label}";
    echo "\n" . str_repeat('-', 65);
}

// ── Helper: Cek redirect ke login ─────────────────────────────
function isRedirectToLogin(array $res): bool
{
    return $res['code'] === 302 && str_contains($res['response'], 'login');
}

// ── Helper: Cek forbidden ──────────────────────────────────────
function isForbidden(array $res): bool
{
    return in_array($res['code'], [302, 403]);
}

echo "\n" . str_repeat('=', 65);
echo "\n  SECURITY TESTING — MANIPULASI SESSION";
echo "\n  Sistem Autentikasi Sertifikat Digital";
echo "\n" . str_repeat('=', 65);

// ══════════════════════════════════════════════════════════════
// BAGIAN 1 — AKSES TANPA AUTENTIKASI
// ══════════════════════════════════════════════════════════════
echo "\n\n[BAGIAN 1] Akses Tanpa Autentikasi\n";
echo str_repeat('-', 65);

// Skenario 1
$res    = makeRequest("{$baseUrl}/admin");
$passed = isRedirectToLogin($res);
printResult(1, 'Akses /admin tanpa login', 'Redirect ke /login (302)', $res['code'], $passed);
$results[] = ['no' => 1, 'skenario' => 'Akses /admin tanpa login', 'passed' => $passed];

// Skenario 2
$res    = makeRequest("{$baseUrl}/admin/users");
$passed = isRedirectToLogin($res);
printResult(2, 'Akses /admin/users tanpa login', 'Redirect ke /login (302)', $res['code'], $passed);
$results[] = ['no' => 2, 'skenario' => 'Akses /admin/users tanpa login', 'passed' => $passed];

// Skenario 3
$res    = makeRequest("{$baseUrl}/admin/activity-logs");
$passed = isRedirectToLogin($res);
printResult(3, 'Akses /admin/activity-logs tanpa login', 'Redirect ke /login (302)', $res['code'], $passed);
$results[] = ['no' => 3, 'skenario' => 'Akses /admin/activity-logs tanpa login', 'passed' => $passed];

// Skenario 4
$res    = makeRequest("{$baseUrl}/admin/upload");
$passed = isRedirectToLogin($res);
printResult(4, 'Akses /admin/upload tanpa login', 'Redirect ke /login (302)', $res['code'], $passed);
$results[] = ['no' => 4, 'skenario' => 'Akses /admin/upload tanpa login', 'passed' => $passed];

// Skenario 5
$res    = makeRequest("{$baseUrl}/admin/certificates/1", 'DELETE');
$passed = isForbidden($res);
printResult(5, 'DELETE /admin/certificates/1 tanpa login', 'Redirect ke /login atau 403', $res['code'], $passed);
$results[] = ['no' => 5, 'skenario' => 'DELETE /admin/certificates/1 tanpa login', 'passed' => $passed];

// ══════════════════════════════════════════════════════════════
// BAGIAN 2 — MANIPULASI SESSION COOKIE
// ══════════════════════════════════════════════════════════════
echo "\n\n[BAGIAN 2] Manipulasi Session Cookie\n";
echo str_repeat('-', 65);

// Skenario 6
$fakeSession = 'laravel_session=FAKE_SESSION_MANIPULATED_12345';
$res         = makeRequest("{$baseUrl}/admin/users", 'GET', $fakeSession);
$passed      = isForbidden($res);
printResult(6, 'Akses dengan session cookie palsu', 'Redirect ke /login atau 403', $res['code'], $passed);
$results[] = ['no' => 6, 'skenario' => 'Akses dengan session cookie palsu', 'passed' => $passed];

// Skenario 7
$manipulatedCookie = 'laravel_session=FAKE; role=superadmin; is_admin=1; user_role=superadmin';
$res               = makeRequest("{$baseUrl}/admin/users", 'GET', $manipulatedCookie);
$passed            = isForbidden($res);
printResult(7, 'Inject role=superadmin via cookie', 'Redirect ke /login atau 403', $res['code'], $passed);
$results[] = ['no' => 7, 'skenario' => 'Inject role=superadmin via cookie', 'passed' => $passed];

// Skenario 8
$manipulatedCookie = 'laravel_session=FAKE; is_superadmin=true; auth=1';
$res               = makeRequest("{$baseUrl}/admin/activity-logs", 'GET', $manipulatedCookie);
$passed            = isForbidden($res);
printResult(8, 'Inject is_superadmin=true via cookie', 'Redirect ke /login atau 403', $res['code'], $passed);
$results[] = ['no' => 8, 'skenario' => 'Inject is_superadmin=true via cookie', 'passed' => $passed];

// ══════════════════════════════════════════════════════════════
// BAGIAN 3 — MANIPULASI QUERY STRING & HEADER
// ══════════════════════════════════════════════════════════════
echo "\n\n[BAGIAN 3] Manipulasi Query String & Header\n";
echo str_repeat('-', 65);

// Skenario 9
$res    = makeRequest("{$baseUrl}/admin/users?role=superadmin");
$passed = isForbidden($res);
printResult(9, 'Inject role=superadmin via query string', 'Redirect ke /login atau 403', $res['code'], $passed);
$results[] = ['no' => 9, 'skenario' => 'Inject role=superadmin via query string', 'passed' => $passed];

// Skenario 10
$res    = makeRequest("{$baseUrl}/admin/activity-logs?is_admin=1&role=superadmin");
$passed = isForbidden($res);
printResult(10, 'Inject is_admin=1 via query string', 'Redirect ke /login atau 403', $res['code'], $passed);
$results[] = ['no' => 10, 'skenario' => 'Inject is_admin=1 via query string', 'passed' => $passed];

// Skenario 11
$res    = makeRequest("{$baseUrl}/admin/users", 'GET', '', [], [
    'X-User-Role: superadmin',
    'X-Admin: true',
    'X-Auth-User: 1',
]);
$passed = isForbidden($res);
printResult(11, 'Inject role via custom HTTP header', 'Redirect ke /login atau 403', $res['code'], $passed);
$results[] = ['no' => 11, 'skenario' => 'Inject role via custom HTTP header', 'passed' => $passed];

// ══════════════════════════════════════════════════════════════
// BAGIAN 4 — AKSES LANGSUNG URL TERPROTEKSI
// ══════════════════════════════════════════════════════════════
echo "\n\n[BAGIAN 4] Akses Langsung URL Terproteksi\n";
echo str_repeat('-', 65);

// Skenario 12
$res    = makeRequest("{$baseUrl}/admin/users/create");
$passed = isRedirectToLogin($res);
printResult(12, 'Akses /admin/users/create tanpa login', 'Redirect ke /login (302)', $res['code'], $passed);
$results[] = ['no' => 12, 'skenario' => 'Akses /admin/users/create tanpa login', 'passed' => $passed];

// Skenario 13
$res    = makeRequest("{$baseUrl}/admin/certificates/1/edit");
$passed = isRedirectToLogin($res);
printResult(13, 'Akses /admin/certificates/1/edit tanpa login', 'Redirect ke /login (302)', $res['code'], $passed);
$results[] = ['no' => 13, 'skenario' => 'Akses /admin/certificates/1/edit tanpa login', 'passed' => $passed];

// Skenario 14
$res    = makeRequest("{$baseUrl}/admin/users/1", 'GET', '', [], [
    'X-Requested-With: XMLHttpRequest',
    'Accept: application/json',
]);
$passed = isForbidden($res);
printResult(14, 'Akses /admin/users/1 via AJAX tanpa login', 'Redirect ke /login atau 401/403', $res['code'], $passed);
$results[] = ['no' => 14, 'skenario' => 'Akses /admin/users/1 via AJAX tanpa login', 'passed' => $passed];

// ══════════════════════════════════════════════════════════════
// REKAPITULASI
// ══════════════════════════════════════════════════════════════
$total   = count($results);
$success = count(array_filter($results, fn($r) => $r['passed']));
$failed  = $total - $success;
$percent = round(($success / $total) * 100, 1);

if ($percent === 100.0) {
    $status = "\033[32mSANGAT AMAN\033[0m";
} elseif ($percent >= 75) {
    $status = "\033[33mAMAN\033[0m";
} else {
    $status = "\033[31mPERLU PERBAIKAN\033[0m";
}

echo "\n\n" . str_repeat('=', 65);
echo "\n  REKAPITULASI HASIL SECURITY TESTING";
echo "\n" . str_repeat('=', 65);
echo "\n  Total Skenario  : {$total}";
echo "\n  Berhasil        : \033[32m{$success}\033[0m";
echo "\n  Gagal           : \033[31m{$failed}\033[0m";
echo "\n  Persentase      : {$percent}%";
echo "\n  Status Keamanan : {$status}";
echo "\n" . str_repeat('=', 65);

if ($failed > 0) {
    echo "\n\n  [!] Skenario yang GAGAL:";
    foreach ($results as $r) {
        if (!$r['passed']) {
            echo "\n  - No.{$r['no']}: {$r['skenario']}";
        }
    }
}

echo "\n\n  Catatan: Sistem menggunakan Laravel Session (server-side)";
echo "\n  sehingga manipulasi cookie/query string tidak berpengaruh.";
echo "\n" . str_repeat('=', 65) . "\n\n";

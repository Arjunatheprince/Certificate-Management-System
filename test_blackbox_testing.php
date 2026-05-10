<?php
/**
 * Rekapitulasi Black Box Testing
 * Sistem Autentikasi Sertifikat Digital
 *
 * Jalankan:
 *   php test_blackbox_testing.php
 *
 * Catatan penjelasan:
 * - Script ini tidak membuat hasil secara manual.
 * - Script menjalankan PHPUnit pada folder tests/Feature.
 * - Status BERHASIL hanya diberikan jika PHPUnit benar-benar mengembalikan exit code 0
 *   dan ringkasan OK, artinya semua expected result sesuai dengan actual result.
 * - Jika satu fitur gagal, PHPUnit akan mengembalikan exit code gagal dan rekap ini ikut gagal.
 */

// Daftar ini adalah pemetaan skenario Black Box dari dokumen pengujian ke file PHPUnit.
// Setiap modul tetap dinilai dari perilaku luar sistem: input, proses, output, dan status.
$modules = [
    ['no' => 1,  'modul' => 'Verifikasi Manual',                  'skenario' => '1-5',   'file' => 'VerifikasiManualTest.php'],
    ['no' => 2,  'modul' => 'Verifikasi QR Upload',               'skenario' => '6-10',  'file' => 'VerifikasiQrUploadTest.php'],
    ['no' => 3,  'modul' => 'Scan QR Kamera',                     'skenario' => '11-13', 'file' => 'ScanQrKameraTest.php'],
    ['no' => 4,  'modul' => 'Login, Logout, Akses Dashboard',     'skenario' => '14-20', 'file' => 'AuthTest.php'],
    ['no' => 5,  'modul' => 'Upload Sertifikat',                  'skenario' => '21-32', 'file' => 'SertifikatTest.php'],
    ['no' => 6,  'modul' => 'Daftar Sertifikat',                  'skenario' => '33-40', 'file' => 'SertifikatTest.php'],
    ['no' => 7,  'modul' => 'Edit Sertifikat',                    'skenario' => '41-44', 'file' => 'SertifikatTest.php'],
    ['no' => 8,  'modul' => 'Hapus Sertifikat',                   'skenario' => '45-46', 'file' => 'AdminUserAuthorizationAndThrottleTest.php'],
    ['no' => 9,  'modul' => 'Log Aktivitas',                      'skenario' => '47-48', 'file' => 'AdminUserAuthorizationAndThrottleTest.php'],
    ['no' => 10, 'modul' => 'Manajemen User',                     'skenario' => '49-57', 'file' => 'AdminUserAuthorizationAndThrottleTest.php'],
    ['no' => 11, 'modul' => 'Otorisasi Akses',                    'skenario' => '58-59', 'file' => 'AdminUserAuthorizationAndThrottleTest.php'],
    ['no' => 12, 'modul' => 'Pembatasan Akses',                   'skenario' => '60',    'file' => 'AdminUserAuthorizationAndThrottleTest.php'],
];

function runFeatureTests(): array
{
    // PHPUnit adalah mesin pengujian utama.
    // Script ini hanya menjadi "rekapitulasi", sedangkan pengecekan fitur dilakukan
    // oleh test case di tests/Feature seperti AuthTest, SertifikatTest, dan lainnya.
    $phpunit = __DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'phpunit';
    $cmd = [
        PHP_BINARY,
        $phpunit,
        'tests/Feature',
    ];

    $descriptorSpec = [
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $process = proc_open($cmd, $descriptorSpec, $pipes, __DIR__);

    if (!is_resource($process)) {
        return [
            'exit_code' => 1,
            'output' => 'PHPUnit gagal dijalankan.',
            'summary' => 'PHPUnit gagal dijalankan',
        ];
    }

    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    $exitCode = proc_close($process);
    $output = trim($stdout . PHP_EOL . $stderr);

    // Ringkasan OK hanya muncul dari PHPUnit jika semua test lolos.
    // Bagian ini penting untuk membuktikan bahwa output bukan tulisan statis,
    // tetapi diambil dari hasil eksekusi PHPUnit yang sebenarnya.
    preg_match('/OK \((\d+) tests?, (\d+) assertions?\)/', $output, $matches);
    $summary = $matches
        ? "OK ({$matches[1]} tests, {$matches[2]} assertions)"
        : 'PHPUnit tidak menghasilkan ringkasan OK';

    return [
        'exit_code' => $exitCode,
        'output' => $output,
        'summary' => $summary,
    ];
}

function statusText(bool $passed): string
{
    return $passed ? 'BERHASIL' : 'GAGAL';
}

function printLine(): void
{
    echo str_repeat('-', 108) . PHP_EOL;
}

$result = runFeatureTests();

// Kriteria lulus dibuat ketat:
// 1. exit_code harus 0, berarti proses PHPUnit tidak menemukan kegagalan.
// 2. summary harus diawali "OK", berarti PHPUnit menyatakan seluruh test berhasil.
$allPassed = $result['exit_code'] === 0 && str_starts_with($result['summary'], 'OK');

// Total 60 skenario berasal dari tabel Black Box Testing.
// Jika PHPUnit gagal, rekap tidak memaksa hasil menjadi berhasil, melainkan menampilkan 0 berhasil
// dan mencetak detail output PHPUnit agar bagian yang gagal bisa ditelusuri.
$totalScenarios = 60;
$successScenarios = $allPassed ? 60 : 0;
$failedScenarios = $totalScenarios - $successScenarios;
$percentage = round(($successScenarios / $totalScenarios) * 100, 1);
$category = $percentage === 100.0
    ? 'SANGAT BAIK'
    : ($percentage >= 75 ? 'BAIK' : ($percentage >= 50 ? 'CUKUP' : 'KURANG'));

echo PHP_EOL;
echo str_repeat('=', 108) . PHP_EOL;
echo '  REKAPITULASI BLACK BOX TESTING' . PHP_EOL;
echo '  Sistem Autentikasi Sertifikat Digital' . PHP_EOL;
echo str_repeat('=', 108) . PHP_EOL;
printf("%-4s %-38s %-12s %-42s %-10s\n", 'No', 'Modul Pengujian', 'Skenario', 'File Test', 'Status');
printLine();

foreach ($modules as $module) {
    printf(
        "%-4s %-38s %-12s %-42s %-10s\n",
        $module['no'],
        $module['modul'],
        $module['skenario'],
        $module['file'],
        statusText($allPassed)
    );
}

printLine();
echo "Total Skenario     : {$totalScenarios}" . PHP_EOL;
echo "Berhasil           : {$successScenarios}" . PHP_EOL;
echo "Gagal              : {$failedScenarios}" . PHP_EOL;
echo "Persentase         : {$percentage}%" . PHP_EOL;
echo "Kategori           : {$category}" . PHP_EOL;
echo "PHPUnit Result     : {$result['summary']}" . PHP_EOL;
echo str_repeat('=', 108) . PHP_EOL;

if (!$allPassed) {
    echo PHP_EOL;
    echo '[DETAIL OUTPUT PHPUNIT]' . PHP_EOL;
    echo $result['output'] . PHP_EOL;
    echo str_repeat('=', 108) . PHP_EOL;
}

exit($allPassed ? 0 : 1);

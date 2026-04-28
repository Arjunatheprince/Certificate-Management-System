<?php
/**
 * Generate lampiran kode program dalam format Markdown.
 *
 * Jalankan:
 *   php generate_code_appendix.php
 */

$root = __DIR__;
$outputFile = $root . DIRECTORY_SEPARATOR . 'lampiran_kode_program.md';

$includeFiles = [
    'composer.json',
    'package.json',
    'phpunit.xml',
    'vite.config.js',
    'tailwind.config.js',
    'postcss.config.js',
    'test_blackbox_testing.php',
    'test_security_testing.php',
    'test_owasp_security.php',
];

$includeDirs = [
    'app',
    'bootstrap',
    'config',
    'database',
    'resources',
    'routes',
    'tests',
];

$excludePatterns = [
    '#(^|[\\\\/])vendor[\\\\/]#',
    '#(^|[\\\\/])node_modules[\\\\/]#',
    '#(^|[\\\\/])storage[\\\\/]#',
    '#(^|[\\\\/])bootstrap[\\\\/]cache[\\\\/]#',
    '#(^|[\\\\/])public[\\\\/]build[\\\\/]#',
    '#(^|[\\\\/])public[\\\\/]uploads[\\\\/]#',
    '#(^|[\\\\/])public[\\\\/]qrcodes[\\\\/]#',
    '#\.env$#',
    '#\.env\.example$#',
    '#composer\.lock$#',
    '#package-lock\.json$#',
    '#database[\\\\/]database\.sqlite$#',
];

$allowedExtensions = [
    'php',
    'blade.php',
    'json',
    'xml',
    'js',
    'css',
    'md',
];

function normalizePath(string $path): string
{
    return str_replace('\\', '/', $path);
}

function shouldExclude(string $relativePath, array $excludePatterns): bool
{
    foreach ($excludePatterns as $pattern) {
        if (preg_match($pattern, $relativePath)) {
            return true;
        }
    }

    return false;
}

function detectLanguage(string $path): string
{
    return match (true) {
        str_ends_with($path, '.blade.php') => 'blade',
        str_ends_with($path, '.php') => 'php',
        str_ends_with($path, '.json') => 'json',
        str_ends_with($path, '.xml') => 'xml',
        str_ends_with($path, '.js') => 'javascript',
        str_ends_with($path, '.css') => 'css',
        str_ends_with($path, '.md') => 'markdown',
        default => 'text',
    };
}

function extensionAllowed(string $relativePath, array $allowedExtensions): bool
{
    foreach ($allowedExtensions as $extension) {
        if (str_ends_with($relativePath, '.' . $extension)) {
            return true;
        }
    }

    return false;
}

$files = [];

foreach ($includeFiles as $file) {
    $absolutePath = $root . DIRECTORY_SEPARATOR . $file;
    if (is_file($absolutePath)) {
        $files[] = $file;
    }
}

foreach ($includeDirs as $dir) {
    $absoluteDir = $root . DIRECTORY_SEPARATOR . $dir;
    if (!is_dir($absoluteDir)) {
        continue;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($absoluteDir, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $fileInfo) {
        if (!$fileInfo->isFile()) {
            continue;
        }

        $relativePath = normalizePath(substr($fileInfo->getPathname(), strlen($root) + 1));

        if (shouldExclude($relativePath, $excludePatterns)) {
            continue;
        }

        if (!extensionAllowed($relativePath, $allowedExtensions)) {
            continue;
        }

        $files[] = $relativePath;
    }
}

$files = array_values(array_unique($files));
sort($files, SORT_NATURAL | SORT_FLAG_CASE);

$content = [];
$content[] = '# Lampiran Kode Program';
$content[] = '';
$content[] = 'Lampiran ini berisi kode sumber utama dari Sistem Autentikasi Sertifikat Digital. File dependency, cache, hasil build, file upload, QR code hasil generate, database lokal, dan file environment tidak disertakan karena bukan bagian dari kode sumber inti atau dapat memuat konfigurasi sensitif.';
$content[] = '';
$content[] = '## Daftar File';
$content[] = '';

foreach ($files as $index => $file) {
    $content[] = ($index + 1) . '. `' . $file . '`';
}

$content[] = '';
$content[] = '## Isi Kode';
$content[] = '';

foreach ($files as $file) {
    $absolutePath = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file);
    $code = file_get_contents($absolutePath);

    if ($code === false) {
        continue;
    }

    $content[] = '### ' . $file;
    $content[] = '';
    $content[] = '```' . detectLanguage($file);
    $content[] = rtrim($code);
    $content[] = '```';
    $content[] = '';
}

file_put_contents($outputFile, implode(PHP_EOL, $content));

echo 'Lampiran berhasil dibuat: ' . $outputFile . PHP_EOL;
echo 'Total file dimasukkan: ' . count($files) . PHP_EOL;

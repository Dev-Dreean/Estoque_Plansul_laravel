<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$paths = [
    $root . '/app',
    $root . '/resources',
    $root . '/routes',
    $root . '/config',
];

$extensions = ['php', 'blade.php', 'js', 'ts', 'vue', 'md', 'json'];

$ignoreFragments = [
    '/vendor/',
    '/node_modules/',
    '/storage/',
    '/bootstrap/cache/',
    '.backup',
    '.modal-backup',
];

$legacyEncodingExceptions = [
    '/app/Http/Controllers/PatrimonioController.php',
    '/resources/views/relatorios/bens/index.blade.php',
];

$mojibakeTokens = [
    'Ã¡', 'Ã©', 'Ãª', 'Ã­', 'Ã³', 'Ãº',
    'Ã£', 'Ãµ', 'Ã§', 'Ã¢', 'Ã´',
    'â€”', 'â€“', 'â€œ', 'â€', 'â€¢',
    'Ãƒ', 'Ã¢', 'ðŸ', '�',
];

$errors = [];

$shouldCheckFile = static function (string $file) use ($extensions, $ignoreFragments, $legacyEncodingExceptions): bool {
    $normalized = str_replace('\\', '/', $file);

    foreach ($ignoreFragments as $fragment) {
        if (str_contains($normalized, $fragment)) {
            return false;
        }
    }

    foreach ($legacyEncodingExceptions as $exceptionPath) {
        if (str_ends_with($normalized, $exceptionPath)) {
            return false;
        }
    }

    foreach ($extensions as $ext) {
        if (str_ends_with($normalized, '.' . $ext)) {
            return true;
        }
    }

    return false;
};

foreach ($paths as $path) {
    if (!is_dir($path)) {
        continue;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $fileInfo) {
        if (!$fileInfo->isFile()) {
            continue;
        }

        $file = $fileInfo->getPathname();
        if (!$shouldCheckFile($file)) {
            continue;
        }

        $content = @file_get_contents($file);
        if ($content === false) {
            $errors[] = $file . ' => não foi possível ler o arquivo.';
            continue;
        }

        if (!preg_match('//u', $content)) {
            $errors[] = $file . ' => arquivo não está em UTF-8 válido.';
            continue;
        }

        foreach ($mojibakeTokens as $token) {
            if (str_contains($content, $token)) {
                $errors[] = $file . " => possível caractere corrompido: '{$token}'";
                break;
            }
        }
    }
}

if ($errors !== []) {
    fwrite(STDERR, "Foram encontrados problemas de idioma/codificação:\n");
    foreach ($errors as $error) {
        fwrite(STDERR, " - {$error}\n");
    }
    exit(1);
}

fwrite(STDOUT, "Validação PT-BR/UTF-8 concluída com sucesso.\n");

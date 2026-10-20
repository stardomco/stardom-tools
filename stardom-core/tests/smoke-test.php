<?php
/**
 * Stardom Core syntax smoke test.
 * Run: php stardom-core/tests/smoke-test.php
 * Checks every PHP file, not only the original plugin entry points.
 */
$root = dirname(__DIR__);
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);
$checked = 0;
foreach ($iterator as $file) {
    if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
        continue;
    }
    $output = array();
    $status = 0;
    exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file->getPathname()) . ' 2>&1', $output, $status);
    if ($status !== 0) {
        fwrite(STDERR, implode("\n", $output) . "\n");
        exit(1);
    }
    ++$checked;
}
if ($checked < 2) {
    fwrite(STDERR, "Expected PHP source files were not found.\n");
    exit(1);
}
echo "Stardom Core syntax checks passed for {$checked} PHP files.\n";

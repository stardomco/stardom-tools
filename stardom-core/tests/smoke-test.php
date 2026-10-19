<?php
/**
 * Stardom Core minimal smoke test.
 * Run: php stardom-core/tests/smoke-test.php
 * This test does not bootstrap WordPress or write data.
 */
$files = array(
    dirname(__DIR__) . '/stardom-core.php',
    dirname(__DIR__) . '/includes/class-stardom-core.php',
);
foreach ($files as $file) {
    if (!is_file($file)) {
        fwrite(STDERR, "Missing: {$file}\n");
        exit(1);
    }
    $output = array();
    $status = 0;
    exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file) . ' 2>&1', $output, $status);
    if ($status !== 0) {
        fwrite(STDERR, implode("\n", $output) . "\n");
        exit(1);
    }
}
echo "Stardom Core syntax smoke test passed.\n";

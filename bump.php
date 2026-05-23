<?php

declare(strict_types=1);

// Updates src/Version.php in lockstep with a release tag. Called from
// the release workflow with the bare numeric tag, e.g.
// `php bump.php 0.0.33`.
//
// Kept as a small script (rather than sed) so the file shape can
// evolve without rewriting the workflow.

$version = $argv[1] ?? null;
if ($version === null || $version === "") {
    fwrite(STDERR, "Usage: php bump.php <version>\n");
    exit(1);
}

$path = __DIR__ . "/src/Version.php";
$src = file_get_contents($path);
if ($src === false) {
    fwrite(STDERR, "could not read $path\n");
    exit(1);
}

$updated = preg_replace(
    '/public const VALUE = "[^"]*";/',
    'public const VALUE = "' . $version . '";',
    $src,
    1,
    $count,
);
if ($updated === null || $count !== 1) {
    fwrite(STDERR, "failed to rewrite Version::VALUE in $path\n");
    exit(1);
}

file_put_contents($path, $updated);
echo "Bumped to $version\n";

#!/usr/bin/env php
<?php

declare(strict_types=1);

$pluginDir = dirname(__DIR__);

$options = getopt('', ['major', 'minor', 'patch', 'set:']);

if ($options === false) {
    fwrite(STDERR, "Unable to parse options.\n");
    exit(1);
}

$setVersion = $options['set'] ?? null;
$flags = array_intersect_key($options, array_flip(['major', 'minor', 'patch']));

if ($setVersion !== null && !empty($flags)) {
    fwrite(STDERR, "--set cannot be combined with --major/--minor/--patch.\n");
    exit(1);
}

if ($setVersion !== null) {
    $setVersion = trim((string) $setVersion);
    if (!preg_match('/^\d+\.\d+\.\d+$/', $setVersion)) {
        fwrite(STDERR, "Invalid version format for --set. Use X.Y.Z\n");
        exit(1);
    }
}

$bumpType = 'patch';
foreach (['major', 'minor', 'patch'] as $type) {
    if (array_key_exists($type, $flags)) {
        $bumpType = $type;
    }
}

$pluginFile = null;
foreach (glob($pluginDir . '/*.php') as $file) {
    $handle = fopen($file, 'rb');
    if (! $handle) {
        continue;
    }
    $header = fread($handle, 8192) ?: '';
    fclose($handle);

    if (preg_match('/^\s*\/\*\*.*Plugin Name:/ms', $header)) {
        $pluginFile = $file;
        break;
    }
}

if ($pluginFile === null) {
    fwrite(STDERR, "Unable to locate plugin main file.\n");
    exit(1);
}

$contents = file_get_contents($pluginFile);
if ($contents === false) {
    fwrite(STDERR, "Unable to read plugin file.\n");
    exit(1);
}

$bom = '';
if (strncmp($contents, "\xEF\xBB\xBF", 3) === 0) {
    $bom = "\xEF\xBB\xBF";
    $contents = substr($contents, 3);
}

if (!preg_match('/^([\s\/*#@]*Version:\s*)([0-9]+\.[0-9]+\.[0-9]+)(.*)$/mi', $contents, $matches, PREG_OFFSET_CAPTURE)) {
    fwrite(STDERR, "Version header not found in plugin file.\n");
    exit(1);
}

$currentVersion = $matches[2][0];
$newVersion = $currentVersion;

if ($setVersion !== null) {
    $newVersion = $setVersion;
} else {
    [$major, $minor, $patch] = array_map('intval', explode('.', $currentVersion));
    switch ($bumpType) {
        case 'major':
            $major += 1;
            $minor = 0;
            $patch = 0;
            break;
        case 'minor':
            $minor += 1;
            $patch = 0;
            break;
        default:
            $patch += 1;
            break;
    }
    $newVersion = sprintf('%d.%d.%d', $major, $minor, $patch);
}

$replaced = false;
$contents = preg_replace_callback(
    '/^([\s\/*#@]*Version:\s*)([0-9]+\.[0-9]+\.[0-9]+)(.*)$/mi',
    static function (array $m) use ($newVersion, &$replaced) {
        if ($replaced) {
            return $m[0];
        }
        $replaced = true;
        return $m[1] . $newVersion . $m[3];
    },
    $contents,
    1
);

if (!is_string($contents) || ! $replaced) {
    fwrite(STDERR, "Failed to update version header.\n");
    exit(1);
}

$constReplacements = 0;
$contents = preg_replace_callback(
    '/(const\s+VERSION\s*=\s*[\'"])([0-9]+\.[0-9]+\.[0-9]+)([\'"];)/i',
    static function (array $m) use ($newVersion, &$constReplacements) {
        $constReplacements++;
        return $m[1] . $newVersion . $m[3];
    },
    $contents,
    1
);

if ($constReplacements === 0 && preg_match('/const\s+VERSION/i', $contents)) {
    fwrite(STDERR, "Failed to update VERSION constant.\n");
    exit(1);
}

if (file_put_contents($pluginFile, $bom . $contents) === false) {
    fwrite(STDERR, "Unable to write plugin file.\n");
    exit(1);
}

echo $newVersion . "\n";

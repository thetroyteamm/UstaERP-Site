<?php
/**
 * Copy the current Android and Windows builds into the site's dist/ folder and stamp their
 * version, size and build date into index.html.
 *
 *     E:\XAMPP\php\php.exe tools\publish_builds.php
 *
 * Run it whenever a new build is published, then upload dist/ together with index.html.
 *
 * The site is static, so nothing can read a file's size at request time the way the local
 * install page (prototype/dist/index.php) does — the figures have to be written into the page
 * instead. That is exactly the kind of number that goes stale silently, which is why this
 * script exists rather than a note asking someone to remember: it takes the version, the byte
 * count and the build date off the files themselves, so the page cannot claim a build it is
 * not shipping.
 *
 * A build that is missing from the source folder is written as null and its download button
 * disappears from the page, rather than being offered as a dead link.
 */

$siteRoot = dirname(__DIR__);

// Where publish_apk.sh and publish_exe.sh leave their output. The default assumes the CRM
// project sits beside this one, which is how the projects are laid out; it is written as a
// relative path rather than an absolute one so this file names nobody's machine. Set
// USTACRM_BUILDS to point somewhere else.
$source  = getenv('USTACRM_BUILDS') ?: $siteRoot . '/../MEBEL_CRM/prototype/dist';
$distDir = $siteRoot . '/dist';
$page    = $siteRoot . '/index.html';

if (!is_dir($source)) {
    fwrite(STDERR, "No build folder at: $source\n");
    fwrite(STDERR, "Set USTACRM_BUILDS to where ustacrm.apk and UstaCRM.exe are published.\n");
    exit(1);
}
$source = str_replace('\\', '/', realpath($source));

// file in dist/, the file holding its version, and the key the page reads it by
$builds = [
    'android' => ['file' => 'ustacrm.apk',  'version' => 'version.txt'],
    'windows' => ['file' => 'UstaCRM.exe',  'version' => 'exe-version.txt'],
];

if (!is_dir($distDir) && !mkdir($distDir, 0777, true)) {
    fwrite(STDERR, "Could not create $distDir\n");
    exit(1);
}

$meta = [];
foreach ($builds as $key => $build) {
    $src = $source . '/' . $build['file'];
    if (!is_file($src)) {
        fwrite(STDERR, "skipped {$build['file']} — not in $source\n");
        $meta[$key] = null;
        continue;
    }

    $dst = $distDir . '/' . $build['file'];
    if (!copy($src, $dst)) {
        fwrite(STDERR, "Could not copy $src\n");
        exit(1);
    }
    // Keep the source's mtime: the build date shown on the page should be when the build was
    // made, not when it was last copied here.
    touch($dst, filemtime($src));

    $versionFile = $source . '/' . $build['version'];
    $meta[$key] = [
        'file'    => 'dist/' . $build['file'],
        'version' => is_file($versionFile) ? trim(file_get_contents($versionFile)) : '—',
        'size'    => round(filesize($src) / 1024 / 1024, 1),
        'built'   => date('d.m.Y', filemtime($src)),
    ];
    printf("published %-14s v%-8s %5.1f MB  %s\n",
        $build['file'], $meta[$key]['version'], $meta[$key]['size'], $meta[$key]['built']);
}

// Rewrite the block between the markers in index.html. Anchored on the markers rather than on
// the shape of the JSON so that reformatting the page cannot break this script.
$html = file_get_contents($page);
$json = json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
$json = preg_replace('/^/m', '  ', $json);   // indent to match the surrounding script

$block  = "/* BUILDS:start — written by tools/publish_builds.php, do not edit by hand */\n";
$block .= "const BUILDS =\n" . $json . ";\n";
$block .= "/* BUILDS:end */";

$updated = preg_replace(
    '#/\* BUILDS:start.*?/\* BUILDS:end \*/#s',
    // A literal $ or backslash in the replacement would be read as a backreference.
    str_replace(['\\', '$'], ['\\\\', '\\$'], $block),
    $html,
    1,
    $count
);

if ($count !== 1) {
    fwrite(STDERR, "Could not find the BUILDS block in index.html — nothing written.\n");
    exit(1);
}

file_put_contents($page, $updated);
echo "index.html updated\n";

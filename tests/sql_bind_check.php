<?php
// Quick static checker: find prepare() followed by bind_param() and validate counts
$root = __DIR__ . '/..';
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
$files = [];
foreach ($rii as $file) {
    if ($file->isDir()) continue;
    if (preg_match('/\.php$/i', $file->getFilename())) {
        $files[] = $file->getPathname();
    }
}
$issues = [];
foreach ($files as $file) {
    $content = file_get_contents($file);
    // find prepare assignments: $var = $conn->prepare('...')
    if (preg_match_all('/\$(\w+)\s*=\s*\$conn->prepare\s*\(\s*([\'\"])([\s\S]*?)\2\s*\)/', $content, $m, PREG_SET_ORDER)) {
        // Build list of prepares with their offsets
        $prepares = [];
        preg_match_all('/\$(\w+)\s*=\s*\$conn->prepare\s*\(\s*([\'\"])([\s\S]*?)\2\s*\)/', $content, $pm, PREG_OFFSET_CAPTURE);
        for ($i = 0; $i < count($pm[0]); $i++) {
            $var = $pm[1][$i][0];
            $query = $pm[3][$i][0];
            $offset = $pm[0][$i][1];
            $prepares[] = ['var'=>$var, 'query'=>$query, 'offset'=>$offset];
        }
        // For each prepare, find the nearest bind_param for the same var after its offset
        foreach ($prepares as $pr) {
            $var = $pr['var'];
            $query = $pr['query'];
            $placeholderCount = substr_count($query, '?');
            $offset = $pr['offset'];
            $pattern = '/\$' . preg_quote($var, '/') . '\s*->\s*bind_param\s*\(\s*([^\)]*)\)/';
            if (preg_match($pattern, $content, $bm, PREG_OFFSET_CAPTURE, $offset)) {
                $args = $bm[1][0];
                // first arg should be a string literal of types
                if (preg_match('/([\'\"])(.*?)\1\s*,/', $args, $typem)) {
                    $types = $typem[2];
                } elseif (preg_match('/([\'\"])(.*?)\1\s*\)/', $args, $typem2)) {
                    $types = $typem2[2];
                } else {
                    $types = null;
                }
                if ($types !== null) {
                    $typeCount = strlen($types);
                    if ($typeCount !== $placeholderCount) {
                        $issues[] = [
                            'file' => $file,
                            'var' => $var,
                            'placeholders' => $placeholderCount,
                            'types' => $types,
                            'typeCount' => $typeCount,
                            'querySnippet' => substr(trim(preg_replace('/\s+/', ' ', $query)),0,200)
                        ];
                    }
                }
            }
        }
    }
}
if (empty($issues)) {
    echo "No prepare/bind_param mismatches found.\n";
    exit(0);
}

echo "Potential mismatches found:\n";
foreach ($issues as $it) {
    echo "- File: " . $it['file'] . "\n";
    echo "  Var: $" . $it['var'] . " placeholders=" . $it['placeholders'] . " types_count=" . $it['typeCount'] . " types='" . $it['types'] . "'\n";
    echo "  Query: " . $it['querySnippet'] . "\n\n";
}
exit(0);

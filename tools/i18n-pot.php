<?php
declare(strict_types=1);

const MODULIX_TRANSLATION_FUNCTIONS = [
    '__' => ['message' => 0, 'domain' => 1],
    '_e' => ['message' => 0, 'domain' => 1],
    '_x' => ['message' => 0, 'context' => 1, 'domain' => 2],
    '_ex' => ['message' => 0, 'context' => 1, 'domain' => 2],
    '_n' => ['message' => 0, 'plural' => 1, 'domain' => 3],
    '_nx' => ['message' => 0, 'plural' => 1, 'context' => 3, 'domain' => 4],
    '_n_noop' => ['message' => 0, 'plural' => 1, 'domain' => 2],
    '_nx_noop' => ['message' => 0, 'plural' => 1, 'context' => 2, 'domain' => 3],
    'esc_html__' => ['message' => 0, 'domain' => 1],
    'esc_html_e' => ['message' => 0, 'domain' => 1],
    'esc_html_x' => ['message' => 0, 'context' => 1, 'domain' => 2],
    'esc_attr__' => ['message' => 0, 'domain' => 1],
    'esc_attr_e' => ['message' => 0, 'domain' => 1],
    'esc_attr_x' => ['message' => 0, 'context' => 1, 'domain' => 2],
];

const MODULIX_JS_TRANSLATION_FUNCTIONS = [
    '__' => ['message' => 0, 'domain' => 1],
    '_x' => ['message' => 0, 'context' => 1, 'domain' => 2],
    '_n' => ['message' => 0, 'plural' => 1, 'domain' => 3],
    '_nx' => ['message' => 0, 'plural' => 1, 'context' => 3, 'domain' => 4],
];

/** @return string[] */
function modulix_php_files(string $root): array {
    $excluded = ['.git', 'build', 'languages', 'memory', 'node_modules', 'tests', 'tools', 'vendor'];
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveCallbackFilterIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
            static function (SplFileInfo $item) use ($excluded): bool {
                return !$item->isDir() || !in_array($item->getFilename(), $excluded, true);
            }
        )
    );
    foreach ($iterator as $item) {
        if ($item instanceof SplFileInfo && $item->isFile() && strtolower($item->getExtension()) === 'php') {
            $files[] = $item->getPathname();
        }
    }
    sort($files, SORT_STRING);
    return $files;
}

/** @return string[] */
function modulix_js_files(string $root): array {
    $excluded = ['.git', 'build', 'languages', 'memory', 'node_modules', 'tests', 'tools', 'vendor'];
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveCallbackFilterIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
            static function (SplFileInfo $item) use ($excluded): bool {
                return !$item->isDir() || !in_array($item->getFilename(), $excluded, true);
            }
        )
    );
    foreach ($iterator as $item) {
        if ($item instanceof SplFileInfo && $item->isFile()
            && strtolower($item->getExtension()) === 'js'
            && !str_ends_with(strtolower($item->getFilename()), '.min.js')) {
            $files[] = $item->getPathname();
        }
    }
    sort($files, SORT_STRING);
    return $files;
}

/** @param array<int, array|string> $tokens @return array<int, array<int, array|string>> */
function modulix_call_arguments(array $tokens, int $open): array {
    $arguments = [[]];
    $depth = 0;
    $count = count($tokens);
    for ($index = $open + 1; $index < $count; $index++) {
        $token = $tokens[$index];
        $text = is_array($token) ? $token[1] : $token;
        if (in_array($text, ['(', '[', '{'], true)) {
            $depth++;
            $arguments[array_key_last($arguments)][] = $token;
            continue;
        }
        if (in_array($text, [')', ']', '}'], true)) {
            if ($text === ')' && $depth === 0) {
                break;
            }
            $depth--;
            $arguments[array_key_last($arguments)][] = $token;
            continue;
        }
        if ($text === ',' && $depth === 0) {
            $arguments[] = [];
            continue;
        }
        $arguments[array_key_last($arguments)][] = $token;
    }
    return $arguments;
}

/** @param array<int, array|string> $argument */
function modulix_literal_string(array $argument): ?string {
    $meaningful = array_values(array_filter(
        $argument,
        static fn(array|string $token): bool => !(is_array($token)
            && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true))
    ));
    if (count($meaningful) !== 1 || !is_array($meaningful[0]) || $meaningful[0][0] !== T_CONSTANT_ENCAPSED_STRING) {
        return null;
    }

    $raw = $meaningful[0][1];
    $body = substr($raw, 1, -1);
    if ($raw[0] === "'") {
        return preg_replace_callback(
            '/\\\\([\\\\\'])/',
            static fn(array $match): string => $match[1],
            $body
        );
    }
    return stripcslashes($body);
}

function modulix_relative_path(string $root, string $path): string {
    return str_replace(DIRECTORY_SEPARATOR, '/', substr($path, strlen(rtrim($root, DIRECTORY_SEPARATOR)) + 1));
}

function modulix_translator_comment(string $comment): ?string {
    $comment = preg_replace('/^\s*\/\*+|\*\/\s*$/', '', $comment) ?? $comment;
    $comment = preg_replace('/^\s*\/\/\s?/m', '', $comment) ?? $comment;
    $lines = preg_split('/\R/', $comment) ?: [];
    $clean = [];
    foreach ($lines as $line) {
        $line = preg_replace('/^\s*\*\s?/', '', $line) ?? $line;
        $line = trim($line);
        if ($line !== '') {
            $clean[] = $line;
        }
    }
    $comment = implode(' ', $clean);
    if (!preg_match('/\btranslators:\s*(.+)$/i', $comment, $match)) {
        return null;
    }
    return 'translators: ' . trim($match[1]);
}

/** @param array<int, array|string> $tokens @return string[] */
function modulix_php_translator_comments(array $tokens, int $index): array {
    $comments = [];
    for ($previous = $index - 1; $previous >= 0; $previous--) {
        $token = $tokens[$previous];
        if (is_array($token) && $token[0] === T_WHITESPACE) {
            continue;
        }
        if (is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
            $comment = modulix_translator_comment($token[1]);
            if ($comment !== null) {
                $comments[$comment] = $comment;
            }
            continue;
        }
        break;
    }
    return array_values($comments);
}

function modulix_has_format_placeholder(string $message): bool {
    return preg_match('/(?<!%)%(?:\d+\$)?[-+0-9\'.]*[bcdeEfFgGosuxX]/', $message) === 1;
}

/**
 * @param array<string,array{message:string,plural:?string,context:?string,references:array<string,string>,comments:array<string,string>,flags:array<string,string>}> $entries
 * @param string[] $comments
 */
function modulix_add_entry(
    array &$entries,
    string $message,
    ?string $plural,
    ?string $context,
    string $reference,
    array $comments,
    string $format
): void {
    $key = ($context ?? '') . "\0" . $message . "\0" . ($plural ?? '');
    if (!isset($entries[$key])) {
        $entries[$key] = [
            'message' => $message,
            'plural' => $plural,
            'context' => $context,
            'references' => [],
            'comments' => [],
            'flags' => [],
        ];
    }
    $entries[$key]['references'][$reference] = $reference;
    foreach ($comments as $comment) {
        $entries[$key]['comments'][$comment] = $comment;
    }
    if (modulix_has_format_placeholder($message)
        || ($plural !== null && modulix_has_format_placeholder($plural))) {
        $entries[$key]['flags'][$format] = $format;
    }
}

function modulix_js_code_mask(string $source): string {
    $masked = $source;
    $length = strlen($source);
    for ($index = 0; $index < $length; $index++) {
        $character = $source[$index];
        if ($character === '/' && ($source[$index + 1] ?? '') === '/') {
            for (; $index < $length && $source[$index] !== "\n"; $index++) {
                $masked[$index] = ' ';
            }
            continue;
        }
        if ($character === '/' && ($source[$index + 1] ?? '') === '*') {
            $masked[$index++] = ' ';
            $masked[$index] = ' ';
            while (++$index < $length) {
                if ($source[$index] === '*' && ($source[$index + 1] ?? '') === '/') {
                    $masked[$index] = ' ';
                    $masked[++$index] = ' ';
                    break;
                }
                if ($source[$index] !== "\n" && $source[$index] !== "\r") {
                    $masked[$index] = ' ';
                }
            }
            continue;
        }
        if ($character !== "'" && $character !== '"' && $character !== '`') {
            continue;
        }
        $quote = $character;
        $masked[$index] = ' ';
        while (++$index < $length) {
            if ($source[$index] === '\\') {
                $masked[$index] = ' ';
                if (++$index < $length && $source[$index] !== "\n" && $source[$index] !== "\r") {
                    $masked[$index] = ' ';
                }
                continue;
            }
            if ($source[$index] === $quote) {
                $masked[$index] = ' ';
                break;
            }
            if ($source[$index] !== "\n" && $source[$index] !== "\r") {
                $masked[$index] = ' ';
            }
        }
    }
    return $masked;
}

/** @return string[] */
function modulix_js_call_arguments(string $source, int $open): array {
    $arguments = [];
    $start = $open + 1;
    $depth = 0;
    $quote = null;
    $length = strlen($source);
    for ($index = $start; $index < $length; $index++) {
        $character = $source[$index];
        if ($quote !== null) {
            if ($character === '\\') {
                $index++;
                continue;
            }
            if ($character === $quote) {
                $quote = null;
            }
            continue;
        }
        if ($character === "'" || $character === '"' || $character === '`') {
            $quote = $character;
            continue;
        }
        if ($character === '/' && ($source[$index + 1] ?? '') === '/') {
            $newline = strpos($source, "\n", $index + 2);
            $index = $newline === false ? $length : $newline;
            continue;
        }
        if ($character === '/' && ($source[$index + 1] ?? '') === '*') {
            $close = strpos($source, '*/', $index + 2);
            $index = $close === false ? $length : $close + 1;
            continue;
        }
        if (in_array($character, ['(', '[', '{'], true)) {
            $depth++;
            continue;
        }
        if (in_array($character, [')', ']', '}'], true)) {
            if ($character === ')' && $depth === 0) {
                $arguments[] = substr($source, $start, $index - $start);
                return $arguments;
            }
            $depth--;
            continue;
        }
        if ($character === ',' && $depth === 0) {
            $arguments[] = substr($source, $start, $index - $start);
            $start = $index + 1;
        }
    }
    return [];
}

function modulix_js_literal_string(string $argument): ?string {
    $argument = trim($argument);
    if (strlen($argument) < 2 || !in_array($argument[0], ["'", '"'], true)
        || $argument[strlen($argument) - 1] !== $argument[0]) {
        return null;
    }
    $quote = $argument[0];
    $body = substr($argument, 1, -1);
    $decoded = '';
    $length = strlen($body);
    $escapes = ['b' => "\x08", 'f' => "\x0c", 'n' => "\n", 'r' => "\r", 't' => "\t", 'v' => "\x0b"];
    for ($index = 0; $index < $length; $index++) {
        if ($body[$index] !== '\\') {
            $decoded .= $body[$index];
            continue;
        }
        $escaped = $body[++$index] ?? '';
        if (isset($escapes[$escaped])) {
            $decoded .= $escapes[$escaped];
        } elseif ($escaped === $quote || $escaped === '\\' || $escaped === '"' || $escaped === "'") {
            $decoded .= $escaped;
        } elseif ($escaped === 'x' && preg_match('/^[0-9A-Fa-f]{2}/', substr($body, $index + 1), $match)) {
            $decoded .= chr((int) hexdec($match[0]));
            $index += 2;
        } elseif ($escaped === 'u' && preg_match('/^[0-9A-Fa-f]{4}/', substr($body, $index + 1), $match)) {
            $sequence = '\\u' . $match[0];
            if ((int) hexdec($match[0]) >= 0xD800 && (int) hexdec($match[0]) <= 0xDBFF
                && preg_match('/^\\\\u([0-9A-Fa-f]{4})/', substr($body, $index + 5), $low)) {
                $sequence .= '\\u' . $low[1];
                $index += 6;
            }
            $decodedUnicode = json_decode('"' . $sequence . '"', true);
            $decoded .= is_string($decodedUnicode) ? $decodedUnicode : $sequence;
            $index += 4;
        } elseif ($escaped === "\n" || $escaped === "\r") {
            if ($escaped === "\r" && ($body[$index + 1] ?? '') === "\n") {
                $index++;
            }
        } else {
            $decoded .= $escaped;
        }
    }
    return $decoded;
}

/** @return string[] */
function modulix_js_translator_comments(string $source, int $offset): array {
    $prefix = substr($source, 0, $offset);
    preg_match_all('/\/\*[\s\S]*?\*\/|\/\/[^\r\n]*/', $prefix, $matches, PREG_OFFSET_CAPTURE);
    $match = $matches[0] === [] ? null : end($matches[0]);
    if ($match === null || trim(substr($prefix, $match[1] + strlen($match[0]))) !== '') {
        return [];
    }
    $comment = modulix_translator_comment($match[0]);
    return $comment === null ? [] : [$comment];
}

/** @return array{domains:string[],entries:array<string,array{message:string,plural:?string,context:?string,references:string[],comments:string[],flags:string[]}>} */
function modulix_extract(string $root): array {
    $domains = [];
    $entries = [];
    foreach (modulix_php_files($root) as $path) {
        $source = file_get_contents($path);
        if ($source === false) {
            throw new RuntimeException("Unable to read {$path}");
        }
        $tokens = token_get_all($source);
        $count = count($tokens);
        for ($index = 0; $index < $count; $index++) {
            $token = $tokens[$index];
            if (!is_array($token) || $token[0] !== T_STRING || !isset(MODULIX_TRANSLATION_FUNCTIONS[$token[1]])) {
                continue;
            }

            $previous = $index - 1;
            while ($previous >= 0 && is_array($tokens[$previous])
                && in_array($tokens[$previous][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                $previous--;
            }
            if ($previous >= 0 && is_array($tokens[$previous])
                && in_array($tokens[$previous][0], [T_FUNCTION, T_OBJECT_OPERATOR, T_DOUBLE_COLON], true)) {
                continue;
            }

            $open = $index + 1;
            while ($open < $count && is_array($tokens[$open])
                && in_array($tokens[$open][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                $open++;
            }
            if (($tokens[$open] ?? null) !== '(') {
                continue;
            }

            $arguments = modulix_call_arguments($tokens, $open);
            $specification = MODULIX_TRANSLATION_FUNCTIONS[$token[1]];
            $domain = modulix_literal_string($arguments[$specification['domain']] ?? []);
            if ($domain === null) {
                $domains['<dynamic>'] = true;
                continue;
            }
            $domains[$domain] = true;
            if ($domain !== 'modulix') {
                continue;
            }

            $message = modulix_literal_string($arguments[$specification['message']] ?? []);
            $plural = isset($specification['plural'])
                ? modulix_literal_string($arguments[$specification['plural']] ?? [])
                : null;
            $context = isset($specification['context'])
                ? modulix_literal_string($arguments[$specification['context']] ?? [])
                : null;
            if ($message === null || (isset($specification['plural']) && $plural === null)
                || (isset($specification['context']) && $context === null)) {
                continue;
            }

            $reference = modulix_relative_path($root, $path) . ':' . $token[2];
            modulix_add_entry(
                $entries,
                $message,
                $plural,
                $context,
                $reference,
                modulix_php_translator_comments($tokens, $index),
                'php-format'
            );
        }
    }

    $callPattern = '/(?:\(\s*0\s*,\s*(?:[A-Za-z_$][A-Za-z0-9_$]*\s*\.\s*)*([_A-Za-z][_A-Za-z0-9$]*)\s*\)\s*\(|(?:[A-Za-z_$][A-Za-z0-9_$]*\s*\.\s*)*([_A-Za-z][_A-Za-z0-9$]*)\s*\()/';
    foreach (modulix_js_files($root) as $path) {
        $source = file_get_contents($path);
        if ($source === false) {
            throw new RuntimeException("Unable to read {$path}");
        }
        $masked = modulix_js_code_mask($source);
        preg_match_all($callPattern, $masked, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
        foreach ($matches as $match) {
            $function = $match[1][0] !== '' ? $match[1][0] : $match[2][0];
            if (!isset(MODULIX_JS_TRANSLATION_FUNCTIONS[$function])) {
                continue;
            }
            $call = $match[0][0];
            $offset = $match[0][1];
            $open = $offset + strlen($call) - 1;
            $arguments = modulix_js_call_arguments($source, $open);
            $specification = MODULIX_JS_TRANSLATION_FUNCTIONS[$function];
            $domain = modulix_js_literal_string($arguments[$specification['domain']] ?? '');
            if ($domain !== 'modulix') {
                continue;
            }
            $message = modulix_js_literal_string($arguments[$specification['message']] ?? '');
            $plural = isset($specification['plural'])
                ? modulix_js_literal_string($arguments[$specification['plural']] ?? '')
                : null;
            $context = isset($specification['context'])
                ? modulix_js_literal_string($arguments[$specification['context']] ?? '')
                : null;
            if ($message === null || (isset($specification['plural']) && $plural === null)
                || (isset($specification['context']) && $context === null)) {
                continue;
            }
            $line = substr_count(substr($source, 0, $offset), "\n") + 1;
            modulix_add_entry(
                $entries,
                $message,
                $plural,
                $context,
                modulix_relative_path($root, $path) . ':' . $line,
                modulix_js_translator_comments($source, $offset),
                'javascript-format'
            );
        }
    }

    $domains = array_keys($domains);
    sort($domains, SORT_STRING);
    ksort($entries, SORT_STRING);
    foreach ($entries as &$entry) {
        $entry['references'] = array_values($entry['references']);
        sort($entry['references'], SORT_STRING);
        $entry['comments'] = array_values($entry['comments']);
        sort($entry['comments'], SORT_STRING);
        $entry['flags'] = array_values($entry['flags']);
        sort($entry['flags'], SORT_STRING);
    }
    unset($entry);
    return ['domains' => $domains, 'entries' => $entries];
}

function modulix_po_quote(string $value): string {
    return '"' . addcslashes($value, "\\\"\n\r\t") . '"';
}

/** @param array<string,array{message:string,plural:?string,context:?string,references:string[],comments:string[],flags:string[]}> $entries */
function modulix_render_pot(array $entries): string {
    $output = <<<'POT'
# Copyright (C) Modulix contributors
# This file is distributed under the GPL-3.0-or-later license.
msgid ""
msgstr ""
"Project-Id-Version: Modulix\n"
"PO-Revision-Date: YEAR-MO-DA HO:MI+ZONE\n"
"Last-Translator: FULL NAME <EMAIL@ADDRESS>\n"
"Language-Team: LANGUAGE <LL@li.org>\n"
"Language: \n"
"MIME-Version: 1.0\n"
"Content-Type: text/plain; charset=UTF-8\n"
"Content-Transfer-Encoding: 8bit\n"
"X-Domain: modulix\n"

POT;
    $output .= "\n";
    foreach ($entries as $entry) {
        foreach ($entry['comments'] as $comment) {
            $output .= '#. ' . $comment . "\n";
        }
        $output .= '#: ' . implode(' ', $entry['references']) . "\n";
        if ($entry['flags'] !== []) {
            $output .= '#, ' . implode(', ', $entry['flags']) . "\n";
        }
        if ($entry['context'] !== null) {
            $output .= 'msgctxt ' . modulix_po_quote($entry['context']) . "\n";
        }
        $output .= 'msgid ' . modulix_po_quote($entry['message']) . "\n";
        if ($entry['plural'] !== null) {
            $output .= 'msgid_plural ' . modulix_po_quote($entry['plural']) . "\n";
            $output .= "msgstr[0] \"\"\nmsgstr[1] \"\"\n\n";
        } else {
            $output .= "msgstr \"\"\n\n";
        }
    }
    return $output;
}

$options = getopt('', ['root:', 'output:', 'check', 'list-domains']);
$rootArgument = $options['root'] ?? dirname(__DIR__);
$root = realpath((string) $rootArgument);
if ($root === false || !is_dir($root)) {
    fwrite(STDERR, "Invalid root: {$rootArgument}\n");
    exit(2);
}
$output = (string) ($options['output'] ?? ($root . '/languages/modulix.pot'));

try {
    $extracted = modulix_extract($root);
    if (isset($options['list-domains'])) {
        echo implode("\n", $extracted['domains']) . "\n";
        exit(0);
    }
    $catalog = modulix_render_pot($extracted['entries']);
    if (isset($options['check'])) {
        $current = is_file($output) ? file_get_contents($output) : false;
        if ($current !== $catalog) {
            fwrite(STDERR, "Catalog is stale: {$output}\n");
            exit(1);
        }
        exit(0);
    }
    $directory = dirname($output);
    if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
        throw new RuntimeException("Unable to create {$directory}");
    }
    if (file_put_contents($output, $catalog) === false) {
        throw new RuntimeException("Unable to write {$output}");
    }
    echo $output . "\n";
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage() . "\n");
    exit(2);
}

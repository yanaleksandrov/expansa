<?php

declare(strict_types=1);

// run: php tests/Syntax.php
// Files loaded before App\Support\Requirements::check() must parse on an older PHP to show the
// requirements page; bootstrap.php already needs PHP 8.0 (named arguments), so 8.1+ syntax is refused.
require_once __DIR__ . '/bootstrap.php';
/**
 * Syntax newer than PHP 8.0 found in the code, as "line: description".
 *
 * @return string[]
 */
function newerSyntax(string $code): array
{
    $tokens = array_values(array_filter(
        token_get_all($code),
        fn ($token) => ! is_array($token) || ! in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)
    ));

    $text  = fn (int $i) => isset($tokens[$i]) ? (is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i]) : '';
    $id    = fn (int $i) => isset($tokens[$i]) && is_array($tokens[$i]) ? $tokens[$i][0] : null;
    $line  = function (int $i) use ($tokens): int {
        for (; $i >= 0; $i--) {
            if (is_array($tokens[$i])) {
                return $tokens[$i][2];
            }
        }

        return 0;
    };
    $names = [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED, T_NAME_RELATIVE, T_STATIC];

    $found = [];
    foreach ($tokens as $i => $token) {
        $type = $id($i);

        if (in_array($type, [T_PUBLIC_SET, T_PROTECTED_SET, T_PRIVATE_SET], true)) {
            $found[] = $line($i) . ': asymmetric visibility (8.4)';
        } elseif ($type === T_READONLY) {
            $found[] = $line($i) . ': readonly (8.1)';
        } elseif ($type === T_ENUM) {
            $found[] = $line($i) . ': enum (8.1)';
        } elseif ($type === T_ELLIPSIS && $text($i - 1) === '(' && $text($i + 1) === ')') {
            $found[] = $line($i) . ': first-class callable (8.1)';
        } elseif ($type === T_CONST && in_array($id($i + 1), $names, true) && in_array($id($i + 2), $names, true)) {
            $found[] = $line($i) . ': typed class constant (8.3)';
        } elseif ($type === T_NEW && $text($i - 1) !== '(' && in_array($id($i + 1), $names, true) && $text($i + 2) === '(') {
            // skip the constructor arguments, then look at what follows them
            $depth = 0;
            for ($j = $i + 2; isset($tokens[$j]); $j++) {
                $depth += $text($j) === '(' ? 1 : ($text($j) === ')' ? -1 : 0);
                if ($depth === 0) {
                    break;
                }
            }
            if (in_array($text($j + 1), ['->', '?->', '::', '['], true)) {
                $found[] = $line($i) . ': new without parentheses (8.4)';
            }
        }
    }

    return $found;
}

// the detector itself, so a passing file really means "nothing newer was found"
$samples = [
    'new without parentheses'   => '<?php new Foo()->bar();',
    'new with a qualified name' => '<?php return new Expansa\Builders\Form()->make($uid);',
    'asymmetric visibility'     => '<?php class A { public private(set) int $a = 1; }',
    'typed class constant'      => '<?php class A { const int B = 1; }',
    'readonly'                  => '<?php class A { public function __construct(public readonly int $a) {} }',
    'enum'                      => '<?php enum Status { case On; }',
    'first-class callable'      => '<?php $f = strlen(...);',
];
foreach ($samples as $title => $code) {
    check("detects $title", newerSyntax($code) !== []);
}
check('allows PHP 8.0 syntax', newerSyntax('<?php (new Foo())->bar(); $a = new Foo(); f(name: 1); $b = fn () => match (1) { 1 => 2 }; $c = $d?->e; const X = [1];') === []);

$files = [
    'bootstrap.php',
    'autoload.php',
    'env.example.php',
    'env.php',
    'expansa/functions.php',
    'app/Support/Requirements.php',
    'dashboard/error.php',
    'index.php',
];
foreach ($files as $file) {
    if (! is_file(EX_PATH . $file)) {
        continue;
    }

    $found = newerSyntax(file_get_contents(EX_PATH . $file));
    check("$file has no syntax newer than PHP 8.0" . ($found ? ' - ' . implode(', ', $found) : ''), $found === []);
}

exit($failures > 0 ? 1 : 0);

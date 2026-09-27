<?php

declare(strict_types=1);

namespace Expansa\Sniffs\WhiteSpace;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Standards\Squiz\Sniffs\WhiteSpace\ScopeClosingBraceSniff as SquizScopeClosingBraceSniff;

/**
 * Squiz closing brace check, except that an empty body `{}` may close on its opening line:
 * `) {}` of a constructor with promoted properties, `class X extends Y {}`.
 */
class ScopeClosingBraceSniff extends SquizScopeClosingBraceSniff
{
    /**
     * Skip empty inline bodies, check the others as Squiz does.
     *
     * @param File $phpcsFile
     * @param int  $stackPtr
     * @return void
     */
    public function process(File $phpcsFile, int $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();
        $opener = $tokens[$stackPtr]['scope_opener'] ?? null;

        if ($opener !== null && ($tokens[$stackPtr]['scope_closer'] ?? null) === $opener + 1) {
            return;
        }

        parent::process($phpcsFile, $stackPtr);
    }
}

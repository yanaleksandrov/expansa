<?php

declare(strict_types=1);

namespace Expansa\Sniffs\Classes;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Standards\PSR2\Sniffs\Classes\ClassDeclarationSniff as PsrClassDeclarationSniff;

/**
 * PSR-2 class declaration, except that an empty body stays on the declaration line:
 * `final class InvalidLevel extends InvalidArgumentException {}`.
 */
class ClassDeclarationSniff extends PsrClassDeclarationSniff
{
    /**
     * Skip classes with an empty body on the declaration line, check the others as PSR-2 does.
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

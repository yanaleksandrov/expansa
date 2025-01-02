<?php

declare(strict_types=1);

namespace Expansa\Sniffs\Formatting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

class EmptyConstructorSniff implements Sniff
{
    public function register(): array
    {
        return [T_FUNCTION];
    }

    public function process(File $phpcsFile, $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();

        // Check that the function is a constructor
        $functionName = $phpcsFile->getDeclarationName($stackPtr);
        if ($functionName !== '__construct') {
            return;
        }

        // Find the opening and closing braces of the function
        $openBrace  = $phpcsFile->findNext(T_OPEN_CURLY_BRACKET, $stackPtr);
        $closeBrace = $tokens[$openBrace]['bracket_closer'] ?? null;

        if ($closeBrace === null) {
            return; // Invalid function structure
        }

        // Check if the constructor is empty
        $content = $phpcsFile->getTokensAsString($openBrace + 1, $closeBrace - $openBrace - 1);
        if (trim($content) !== '') {
            return; // Constructor is not empty
        }

        // Check the line positions of the opening and closing braces
        $openBraceLine  = $tokens[$openBrace]['line'];
        $closeBraceLine = $tokens[$closeBrace]['line'];

        if ($openBraceLine === $closeBraceLine) {
            return; // Braces are already on the same line
        }

        // Report and fix the issue
        $error = 'Empty constructors should have the closing brace on the same line as the opening brace.';
        $fix   = $phpcsFile->addFixableError($error, $closeBrace, 'EmptyConstructor');

        if ($fix) {
            $phpcsFile->fixer->beginChangeset();
            // Remove all tokens between the braces
            for ($i = $openBrace + 1; $i < $closeBrace; $i++) {
                $phpcsFile->fixer->replaceToken($i, '');
            }
            // Move the closing brace to the same line as the opening brace
            $phpcsFile->fixer->replaceToken($openBrace, '{}');
            $phpcsFile->fixer->replaceToken($closeBrace, '');
            $phpcsFile->fixer->addContent($closeBrace, ' // phpcs:ignore');
            $phpcsFile->fixer->endChangeset();
        }
    }
}

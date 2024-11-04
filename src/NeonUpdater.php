<?php

namespace Maryo\NeonUpdater;

use LogicException;
use Nette\Neon\Lexer;
use Nette\Neon\Neon;
use Nette\Neon\Node;
use Nette\Neon\Node\ArrayNode;
use Nette\Neon\Node\BlockArrayNode;
use Nette\Neon\Node\InlineArrayNode;
use Nette\Neon\Parser;
use Nette\Neon\Token;
use Nette\Neon\TokenStream;
use RuntimeException;

class NeonUpdater
{
    // @phpstan-ignore shipmonk.publicPropertyNotReadonly
    public static string $defaultIndentation = "\t";

    /**
     * @param array<string|null> $path
     */
    public static function update(string $neon, array $path, mixed $value, ?string $defaultIndentation = null): string
    {
        $tokenStream = (new Lexer())->tokenize($neon);
        $parser = new Parser();
        $matchedDepth = 0;
        $root = $parser->parse($tokenStream);
        $node = $root;
        $nodeIndentation = self::getNodeIndentation($node) ?? '';
        $indentation = null;

        self::traverseNeon($root, function (Node $currentNode, array $currentPath, string $currentIndentation) use ($path, &$node, &$matchedDepth, &$indentation, &$nodeIndentation): bool {
            $indentation ??= self::getNodeIndentation($currentNode);
            $depth = count($currentPath);

            if ($currentNode instanceof InlineArrayNode && count($path) > $depth) {
                throw new RuntimeException('Updating inline arrays is not supported.'); // @phpstan-ignore shipmonk.checkedExceptionInCallable
            }

            if ($depth > $matchedDepth && array_slice($path, 0, $depth) === $currentPath) {
                $matchedDepth = $depth;
                $node = $currentNode;
                $nodeIndentation = $currentIndentation;
            }

            return true;
        });

        $updatedNeon = '';
        $updated = false;
        $depth = count($path);

        if ($matchedDepth < $depth) {
            $value = self::nestValue(array_slice($path, $matchedDepth), $value);
        }

        $indentation ??= self::detectIndentation($tokenStream) ?? $defaultIndentation ?? self::$defaultIndentation;
        $neonValue = rtrim(Neon::encode($value, true, $indentation));
        $neonValue = self::normalizeMultilineStringIndentation($neonValue, $indentation);
        /** @var list<Token> $tokens */
        $tokens = $tokenStream->getTokens();

        if ($tokens === []) {
            return $neonValue;
        }

        $itemIndentation = self::getNodeIndentation($node) ?? $indentation;

        if ($matchedDepth > 0) {
            if (is_array($value) || is_object($value)) {
                $neonValue = preg_replace('~^~m', $nodeIndentation . $itemIndentation, $neonValue);
            } elseif (is_string($value) && str_contains($value, "\n")) {
                // First line (the opening triple quotation mark) of the multiline string must not be indented.
                $neonValue = preg_replace('~(?<=\n)^~m', $nodeIndentation, $neonValue);
            }

            if ($neonValue === null) {
                throw self::createExceptionFromLastPcreError();
            }
        }

        $isTrimmed = $node instanceof BlockArrayNode || is_array($value) || is_object($value);
        [$startPosition, $endPosition] = $isTrimmed
            ? self::resolveTrimmedPositions($node, $tokenStream, $matchedDepth)
            : [$node->startTokenPos, $node->endTokenPos];

        if ($startPosition === null || $endPosition === null) {
            throw new LogicException('Unknown token position.');
        }

        $lastPosition = array_key_last($tokens);
        $trailingNewline = null;

        foreach ($tokens as $position => $token) {
            if ($position === $lastPosition && $token->type === Token::Newline) {
                $trailingNewline = $token->value;
                break;
            }

            if (
                ($matchedDepth === $depth || !$node instanceof ArrayNode)
                && $position >= $startPosition && $position <= $endPosition
            ) {
                if ($updated) {
                    continue;
                }

                $isImplicitNull = $startPosition === $endPosition
                    && ($token->type === Token::Newline || $token->type === Token::Whitespace);

                if (is_array($value) || is_object($value)) {
                    if ($matchedDepth > 0) {
                        $updatedNeon .= "\n";
                    }

                    $updatedNeon .= $neonValue;
                } else {
                    if ($matchedDepth === 0) {
                        $updatedNeon .= $nodeIndentation;
                    } elseif ($isTrimmed || $isImplicitNull) {
                        $updatedNeon .= ' ';
                    }

                    $updatedNeon .= $neonValue;
                }

                if ($isImplicitNull) {
                    $nextToken = $tokens[$position + 1] ?? null;

                    if ($token->type !== Token::Whitespace || $nextToken?->type !== Token::Newline) {
                        $updatedNeon .= $token->value;
                    }
                }

                $updated = true;
                continue;
            }

            $updatedNeon .= $token->value;

            if (!$updated && $position === $endPosition) {
                $updatedNeon .= "\n" . $nodeIndentation . $neonValue;
                $updated = true;
            }
        }

        if (!$updated) {
            $previousToken = $tokens[($node->startTokenPos ?? 0) - ($trailingNewline !== null ? 2 : 1)] ?? null;

            if ($previousToken?->type === Token::Comment) {
                $updatedNeon .= "\n";
            } elseif (!in_array($previousToken?->type, [Token::Whitespace, Token::Newline, null], true)) {
                $updatedNeon .= ' ';
            }

            $updatedNeon .= $neonValue;
        }

        if ($trailingNewline !== null) {
            $updatedNeon .= $trailingNewline;
        }

        return $updatedNeon;
    }

    /**
     * @param callable(Node, array<string|null>, string): ?bool $callback
     * @param string[] $path
     */
    private static function traverseNeon(
        Node $node,
        callable $callback,
        array $path = [],
        string $indentation = ''
    ): bool
    {
        if ($callback($node, $path, $indentation) === false) {
            return false;
        }

        if (!$node instanceof ArrayNode) {
            return true;
        }

        $indentation .= self::getNodeIndentation($node) ?? '';
        $tempArray = [];

        foreach ($node->items as $item) {
            if ($item->key !== null) {
                $key = $item->key->toValue();

                if (!is_scalar($key)) {
                    throw new LogicException('Unexpected NEON key type.');
                }

                $tempArray[(string) $key] = null;
            } else {
                $tempArray[] = null;
            }

            $itemPath = $path;
            $itemPath[] = (string) array_key_last($tempArray);

            if (self::traverseNeon($item->value, $callback, $itemPath, $indentation) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<string|null> $path
     */
    private static function nestValue(array $path, mixed $value): mixed
    {
        $array = [];
        $current = &$array;

        foreach ($path as $key) {
            if ($key === null) {
                $current[] = [];
                $key = array_key_last($current);
            }

            $current = &$current[$key];
        }

        $current = $value;

        return $array;
    }

    private static function getNodeIndentation(Node $node): ?string
    {
        return $node instanceof BlockArrayNode && $node->indentation !== ''
            ? $node->indentation
            : null;
    }

    /**
     * Autodetects indentation from whitespace tokens or multiline strings.
     */
    private static function detectIndentation(TokenStream $tokenStream): ?string
    {
        $newLine = true;

        foreach ($tokenStream->getTokens() as $token) {
            if (
                $token->type === Token::String
                && preg_match('~^(?:\'\'\'|""")\n([\t ]++)~', $token->value, $matches) === 1
            ) {
                return $matches[1];
            }

            if ($newLine && $token->type === Token::Whitespace && $token->value !== ' ') {
                return $token->value;
            }

            $newLine = false;

            if ($token->type === Token::Newline) {
                $newLine = true;
            }
        }

        return null;
    }

    /**
     * Regardless of the passed indentation, Neon::encode() always uses tabs for multiline strings.
     */
    private static function normalizeMultilineStringIndentation(string $neon, string $indentation): string
    {
        if ($indentation === "\t") {
            return $neon;
        }

        // https://regex101.com/r/0uwHak/1
        $neon = preg_replace(sprintf('~^(%s)*+\K\t~m', preg_quote($indentation, '~')), $indentation, $neon);

        if ($neon === null) {
            throw self::createExceptionFromLastPcreError();
        }

        return $neon;
    }

    /**
     * @return array{int, int}
     */
    private static function resolveTrimmedPositions(Node $node, TokenStream $tokenStream, int $depth): array
    {
        $startPosition = $node->startTokenPos;
        $endPosition = $node->endTokenPos;

        if ($startPosition === null || $endPosition === null) {
            throw new LogicException('Unknown token position.');
        }

        // Include comments preceding the value except comments at the beginning of the file.
        $nonSignificantTokens = $depth > 0
            ? [Token::Whitespace, Token::Newline, Token::Comment]
            : [Token::Whitespace];
        $tokens = $tokenStream->getTokens();
        while (in_array(($tokens[--$startPosition] ?? null)?->type, $nonSignificantTokens, true));
        $startPosition++;
        // Include comment following the last line of the value.
        while (in_array(($tokens[++$endPosition] ?? null)?->type, [Token::Whitespace, Token::Comment], true));
        $endPosition--;

        return [$startPosition, $endPosition];
    }

    private static function createExceptionFromLastPcreError(): LogicException
    {
        $code = preg_last_error();

        if ($code === PREG_INTERNAL_ERROR) {
            $lastErrorMessage = error_get_last()['message'] ?? null;

            if ($lastErrorMessage !== null && str_starts_with($lastErrorMessage, 'preg_')) {
                $message = $lastErrorMessage;
            }
        }

        return new LogicException($message ?? preg_last_error_msg(), $code);
    }
}

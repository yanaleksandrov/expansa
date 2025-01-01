<?php

declare(strict_types=1);

namespace Grafema\Algorithms\Huffman;

class Encoder
{
    protected string $keyStr = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_.";

    protected Node $codes;

    public function __construct(
	    protected string $prefix = 'Pp'
    ) {
        $codes    = [];
        $alphabet = [',:1-','023456789[]{}'];

        foreach ($alphabet as $chars) {
            $aCodes = [];
            $aChars = str_split($chars);

            foreach ($aChars as $char) {
                $aCodes[] = Node::create($char);
            }

            while (count($aCodes) > 2) {
                $nodes = [];

                while (count($nodes) < 2) {
                    $nodes[] = $this->getCodeNodeMinWeight($aCodes);
                }

                $aCodes[] = Node::merge($nodes[0], $nodes[1]);
            }

            $codes[] = Node::merge($aCodes[0], $aCodes[1]);
        }

        $this->codes = Node::merge($codes[0], $codes[1]);
    }

    public function encode(array $array): string
    {
        $jsonCompressed = $this->compressJsonFromArray($array);

        if (empty($jsonCompressed)) {
            return '';
        }
        
        $jsonCompressed = strlen($jsonCompressed).':'.$jsonCompressed;
        $jsonCompressed = str_split($jsonCompressed);

        $binaryString = '';
        foreach ($jsonCompressed as $char) {
            $binaryString .= $this->getCodeByChar($char, $this->codes);
        }

        $encodedString = '';
        for ($i = 0; $i < strlen($binaryString); $i = $i + 6) {
            $bin = substr($binaryString, $i, 6);
            $bin = str_pad($bin, 6, '1');
            $encodedString .= substr($this->keyStr, bindec($bin), 1);
        }

        return $encodedString;
    }

    public function decode(string $string): ?array
    {
        if (empty($string)) {
            return [];
        }

        $chars = str_split($string);

        $binaryString = '';
        foreach ($chars as $char) {
            $bin = decbin(strpos($this->keyStr, $char));
            $bin = str_pad($bin, 6, '0', STR_PAD_LEFT);

            $binaryString.= $bin;
        }

        $jsonCompressed = '';
        while ([$char, $binaryString] = $this->getCharByCodes($binaryString, $this->codes)) {
            $jsonCompressed .= $char;
        }
        
        list($length, $jsonCompressed) = explode(':', $jsonCompressed, 2);
        
        $jsonCompressed = substr($jsonCompressed, 0, (int)$length);

        return $this->decompressJsonToArray($jsonCompressed);
    }

    protected function compressJsonFromArray(array $array): string
    {
        $json = json_encode($array);

        $json = preg_replace('/"/', "", $json);
        $json = preg_replace('/^{/', "", $json);

	    return preg_replace('/}$/', "", $json);
    }

    protected function decompressJsonToArray(string $json): array
    {
        $json = '{'.$json.'}';
        $json = preg_replace('/({|,)(\d+):/', "$1\"$2\":", $json);
        $json = preg_replace('/({|,)(min|max):/', "$1\"$2\":", $json);
        $json = preg_replace('/(\d+)-(\d+)/', "{\"min\":$1,\"max\":$2}", $json);

        try {
            return json_decode($json, true);
        } catch (\Throwable $e) {

        }

        return [];
    }

    protected function getCodeByChar(string $char, Node $codes, string $code = ''): string
    {
        if (is_null($codes->leafs())) {
            return $code;
        }

        $left  = $codes->left();
        $right = $codes->right();

        if ($left->length() === 1 && $left->chars(0) === $char) {
            return $code.'0';
        }

        if ($right->length() === 1 && $right->chars(0) === $char) {
            return $code.'1';
        }

        if ($left->length() > 1 && in_array($char, $left->chars()) ) {
            return $this->getCodeByChar($char, $left, $code.'0');
        }

        if ($right->length() > 1 && in_array($char, $right->chars()) ) {
            return $this->getCodeByChar($char, $right, $code.'1');
        }

        return '';
    }

    protected function getCharByCodes(string $binaryString, Node $codes): ?array
    {
        if (! $codes->leafs()) {
            return [$codes->chars(0), $binaryString];
        }

        if (strlen($binaryString) === 0) {
            return null;
        }

        $code = (int)substr($binaryString, 0, 1);
        $binaryString = substr($binaryString, 1);

        return $this->getCharByCodes($binaryString, $codes->leafs($code));
    }

    protected function getCodeNodeMinWeight(array &$nodes)
    {
        $min    = 9999;
		$result = null;
		$index  = null;

        foreach ($nodes as $key => $node) {
            if ($node->weight() <= $min) {
                $min = $node->weight();

                $index  = $key;
                $result = $node;
            }
        }

        array_splice($nodes, $index, 1);

        return $result;
    }
}
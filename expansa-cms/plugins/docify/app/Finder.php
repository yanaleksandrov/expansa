<?php

namespace Docify\App;

use Expansa\Error;

/**
 * Documentation block parser.
 */
class Finder
{
    public function classname(string $filepath): string
    {
        $namespace = '';
        $classname = '';
        $code      = file_get_contents($filepath);
        if (preg_match('/namespace\s+([^\s;]+)/', $code, $matches)) {
            $namespace = $matches[1] ?? '';
        }
        if (preg_match('/\/\*\*.*?\*\/\s*class\s+(\w+)/s', $code, $matches)) {
            $classname = $matches[1] ?? '';
        }
        return $namespace ? sprintf('%s\%s', $namespace, $classname) : $classname;
    }

    public function methods(array $files): array
    {
        $classes = [];
        foreach ($files as $file) {
            $classname = $this->classname($file);
            if (! class_exists($classname)) {
                continue;
            }

            try {
                $reflection = new \ReflectionClass($classname);
                $methods    = $reflection->getMethods();
                foreach ($methods as $method) {
                    $classes[ $file ][] = [
                        'class'     => $classname,
                        'namespace' => $method->getNamespaceName(),
                        'method'    => $method->getName(),
                        'docblock'  => $method->getDocComment(),
                        'start'     => $method->getStartLine(),
                        'end'       => $method->getEndLine(),
                        'params'    => $method->getParameters(),
                        'content'   => trim(
                            implode(
                                '',
                                array_slice(
                                    file($method->getFileName()),
                                    $method->getStartLine() - 1,
                                    $method->getEndLine() - $method->getStartLine() + 1
                                )
                            )
                        ),
                    ];
                }
            } catch (\Exception $e) {

            }
        }
        return $classes;
    }
}

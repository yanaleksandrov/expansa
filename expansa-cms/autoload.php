<?php

declare(strict_types=1);

// runs before the PHP version check, so it stays free of PHP 8 syntax and functions
spl_autoload_register(static function (string $class): void {
    // PSR-4 prefixes of the framework, the app and the libraries shipped with them: prefix => [length, directory]
    static $prefixes = [
        'Expansa\\'              => [8, EX_PATH . 'expansa/'],
        'App\\'                  => [4, EX_PATH . 'app/'],
        'Spatie\\'               => [7, EX_PATH . 'expansa/Images/Spatie/'],
        'PHPMailer\\PHPMailer\\' => [20, EX_PATH . 'expansa/Mail/PHPMailer/'],
    ];

    // class => file relative to EX_PATH, written by `php artisan autoload:dump`; no file checks for these classes
    static $classes = null;
    if ($classes === null) {
        $classes = is_file(EX_PATH . 'cache/classmap.php') ? require EX_PATH . 'cache/classmap.php' : [];
    }

    // the autoloader runs only for a class not declared yet, so its file was not included yet
    if (isset($classes[$class])) {
        require EX_PATH . $classes[$class];

        return;
    }

    // classes found missing in this request, so a repeated class_exists() checks no file again
    static $missing = [];
    if (isset($missing[$class])) {
        return;
    }

    // a class added after the dump; a missing file leaves the class undefined, so class_exists() works as usual
    foreach ($prefixes as $prefix => [$length, $directory]) {
        if (strncmp($class, $prefix, $length) === 0) {
            $file = $directory . strtr(substr($class, $length), '\\', '/') . '.php';
            if (is_file($file)) {
                require $file;
            } else {
                $missing[$class] = true;
            }

            return;
        }
    }
});

<?php

// Constants for static analysis: bootstrap.php declares the first ones, env.example.php the rest.
const EX_PATH                   = __DIR__ . '/../../expansa-cms/';
const EX_VERSION                = '0.0';
const EX_REQUIRED_PHP_VERSION   = '8.4';
const EX_REQUIRED_MYSQL_VERSION = '8.0';
const EX_REQUIRED_MEMORY        = 128;
const EX_CORE                   = EX_PATH . 'expansa/';
const EX_DASHBOARD              = EX_PATH . 'dashboard/';
const EX_PLUGINS                = EX_PATH . 'plugins/';
const EX_THEMES                 = EX_PATH . 'themes/';
const EX_STORAGE                = EX_PATH . 'storage/';
const EX_I18N                   = EX_PATH . 'i18n/';

require_once EX_PATH . 'env.example.php';

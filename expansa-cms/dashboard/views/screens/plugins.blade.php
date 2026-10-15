<?php
/**
 * Installed plugins list.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/screens/plugins.php
 *
 * @var App\Tables\Plugins $table
 *
 * @package Expansa\Templates
 */

echo view('parts/table/table', ['table' => $table]);

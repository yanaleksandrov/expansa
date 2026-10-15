<?php

use Expansa\Facades\Hook;

/**
 * Posts list template can be overridden by copying it to themes/yourtheme/dashboard/views/screens/edit.php
 *
 * @var Expansa\Builders\Table $table
 *
 * @package Expansa\Templates
 */

Hook::add('renderDashboardFooter', function () {
    echo view('parts/dialogs/emails-editor');
});

echo view('parts/table/table', ['table' => $table]);

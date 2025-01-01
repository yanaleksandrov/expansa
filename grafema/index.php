<?php
/**
 * Loads the Grafema Environment.
 *
 * @since 2025.1
 */
ini_set( 'display_errors', '1' );
ini_set( 'display_startup_errors', '1' );
error_reporting( E_ALL );

if ( extension_loaded( 'xhprof' ) ) {
	xhprof_enable( XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY );
}

require_once __DIR__ . '/bootstrap.php';

if ( extension_loaded( 'xhprof' ) ) {
	$xhprof_data = xhprof_disable();
	$xhprof_root = 'C:\OpenServer\domains\grfm.loc\xhprof';
	$xhprof_lib  = $xhprof_root . '\xhprof_lib\utils\xhprof_lib.php';
	$xhprof_runs = $xhprof_root . '\xhprof_lib\utils\xhprof_runs.php';

	// Подключаем необходимые файлы
	include_once $xhprof_lib;
	include_once $xhprof_runs;

	// Сохраняем данные профилирования
	$xhprof_runs = new \XHProfRuns_Default();
	$run_id      = $xhprof_runs->save_run( $xhprof_data, 'my_app' );
	$report_url  = "http://grfm.loc/xhprof/xhprof_html/index.php?run={$run_id}&source=my_app";

	// Выводим отчёт на текущей странице
	echo '<code>';
	echo "<a href='{$report_url}' target='_blank'>Ссылка на ваш отчет</a>";
	echo '</code>';
}

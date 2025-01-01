<?php

namespace Grafema\Listeners;

use Grafema\Hooks\HookListenerPriority;

class Test
{
	#[HookListenerPriority(400)]
	public function testHook($var, $name, $is, $yan) {
		var_dump($var);
		var_dump($name);
		var_dump($is);
		var_dump($yan);
		var_dump('Run Test test Hook');
	}
}

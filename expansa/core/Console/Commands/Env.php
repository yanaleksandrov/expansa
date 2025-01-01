<?php

declare(strict_types=1);

namespace Expansa\Console\Commands;

use Expansa\I18n;
use Expansa\Console\Command;

class Env extends Command
{
    protected string $name = 'env';

    protected string $description = 'Display the current framework environment.';

    protected string $signature = 'env';

    protected array $options = [];

    public function handle() : void
    {
	    $this->info(I18n::_f('Current application environment: [green]#:env#', 'local'));

	    $this->liveLine('Processing...');
	    sleep(2);
	    $this->liveLine('50% complete...');
	    sleep(2);
	    $this->liveLine('100% complete!', true);
    }

    public function getDescription() : string
    {
        return I18n::_t('Display the current Expansa CMS environment');
    }
}

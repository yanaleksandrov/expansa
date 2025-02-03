<?php

declare(strict_types=1);

namespace Expansa\Builders\Forms\Fields;

use Expansa\Builders\Forms\Field;

class Media extends Field
{
    public function __construct()
    {
        $this->type        = 'input';
        $this->label       = t('Text');
        $this->category    = 'basic';
        $this->icon        = 'ph ph-text-t';
        $this->description = t('A basic text input, useful for storing single string values.');
        $this->preview     = '';
        $this->view        = view('install')->render();
        $this->defaults    = [];
    }

    public function assets()
    {

    }

    public function render()
    {

    }

    public function settings()
    {

    }

    public function validate()
    {

    }
}

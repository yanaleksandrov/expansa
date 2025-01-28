<?php

declare(strict_types=1);

namespace Expansa\Builders\Forms\Fields;

use Expansa\Builders\Forms\Field;

class Custom extends Field
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
        // TODO: Implement assets() method.
    }

    public function render()
    {
        // TODO: Implement render() method.
    }

    public function settings()
    {
        // TODO: Implement settings() method.
    }

    public function validate()
    {
        // TODO: Implement validate() method.
    }
}

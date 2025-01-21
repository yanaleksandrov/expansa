<?php

declare(strict_types=1);

namespace Dashboard\Forms\Fields;

use app\View;
use Dashboard\Forms\Field;

class Input extends Field
{
    public function __construct()
    {
        $this->type        = 'input';
        $this->label       = t('Text');
        $this->category    = 'basic';
        $this->icon        = 'ph ph-text-t';
        $this->description = t('A basic text input, useful for storing single string values.');
        $this->preview     = '';
        $this->view        = View::get();
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

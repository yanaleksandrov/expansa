<?php

declare(strict_types=1);

namespace App\Api\Options;

use App\Models\Options;
use Expansa\Support\Arr;

final class OptionsService
{
    public function update(array $input): array
    {
        $options = Arr::exclude($input, ['nonce']);

        foreach ($options as $option => $value) {
            Options::update($option, $value);
        }

        return [
            ['target' => 'body', 'notify' => t('Options is updated successfully')],
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Api\Options;

use App\Models\Option;
use App\Support\RoleSettings;
use Expansa\Support\Arr;

final class OptionsService
{
    public function update(array $input): array
    {
        $options = Arr::exclude($input, ['nonce']);

        foreach ($options as $option => $value) {
            // the roles keep the administrator's permissions and take the new role, see RoleSettings
            if ($option === 'roles' && is_array($value)) {
                $value = RoleSettings::normalize($value);
            }

            Option::update($option, $value);
        }

        return [
            ['target' => 'body', 'notify' => t('Options updated successfully.')],
        ];
    }
}

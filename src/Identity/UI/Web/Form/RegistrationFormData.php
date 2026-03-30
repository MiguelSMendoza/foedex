<?php

declare(strict_types=1);

namespace App\Identity\UI\Web\Form;

final class RegistrationFormData
{
    public string $email = '';
    public string $displayName = '';
    public string $plainPassword = '';
}

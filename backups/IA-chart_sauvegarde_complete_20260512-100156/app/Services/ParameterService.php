<?php

namespace App\Services;

use App\Models\Parametre;
use Illuminate\Support\Facades\Config;
use Throwable;

// Ce service fournit les paramètres globaux de l'application.
class ParameterService
{
    public function current(): Parametre
    {
        try {
            return Parametre::firstOrCreate(
                ['id' => 1],
                [
                    'company_name' => 'DEV IA',
                    'currency' => 'USD',
                    'default_tax_rate' => 16,
                    'default_discount_rate' => 0,
                    'invoice_number_format' => 'FAC-{YEAR}-{SEQ}',
                    'invoice_due_days' => 15,
                    'global_minimum_stock' => 5,
                ]
            );
        } catch (Throwable $exception) {
            return new Parametre([
                'company_name' => 'DEV IA',
                'currency' => 'USD',
                'default_tax_rate' => 16,
                'default_discount_rate' => 0,
                'invoice_number_format' => 'FAC-{YEAR}-{SEQ}',
                'invoice_due_days' => 15,
                'global_minimum_stock' => 5,
            ]);
        }
    }

    public function applyMailConfiguration(): void
    {
        Config::set('mail.default', env('MAIL_MAILER', 'smtp'));
        Config::set('mail.mailers.smtp.host', env('MAIL_HOST'));
        Config::set('mail.mailers.smtp.port', env('MAIL_PORT'));
        Config::set('mail.mailers.smtp.username', env('MAIL_USERNAME'));
        Config::set('mail.mailers.smtp.password', env('MAIL_PASSWORD'));
        Config::set('mail.mailers.smtp.encryption', env('MAIL_ENCRYPTION'));
        Config::set('mail.from.address', env('MAIL_FROM_ADDRESS'));
        Config::set('mail.from.name', env('MAIL_FROM_NAME', 'DEV IA'));
    }
}

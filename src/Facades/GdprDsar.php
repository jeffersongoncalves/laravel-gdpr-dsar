<?php

namespace JeffersonGoncalves\GdprDsar\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \JeffersonGoncalves\GdprDsar\GdprDsar
 */
class GdprDsar extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'laravel-gdpr-dsar';
    }
}

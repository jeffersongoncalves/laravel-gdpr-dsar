<?php

namespace JeffersonGoncalves\GdprDsar\Facades;

use Illuminate\Support\Facades\Facade;
use JeffersonGoncalves\GdprDsar\DsarManager;

/**
 * @method static \JeffersonGoncalves\GdprDsar\Models\DataSubjectRequest open(\JeffersonGoncalves\GdprDsar\Enums\RequestType $type, ?\Illuminate\Database\Eloquent\Model $subject = null, array $requester = [])
 * @method static \JeffersonGoncalves\GdprDsar\Models\DataSubjectRequest verify(\JeffersonGoncalves\GdprDsar\Models\DataSubjectRequest $request, ?\Illuminate\Database\Eloquent\Model $subject = null)
 * @method static \JeffersonGoncalves\GdprDsar\Models\DataSubjectRequest fulfil(\JeffersonGoncalves\GdprDsar\Models\DataSubjectRequest $request, ?string $response = null)
 * @method static \JeffersonGoncalves\GdprDsar\Models\DataSubjectRequest complete(\JeffersonGoncalves\GdprDsar\Models\DataSubjectRequest $request, ?string $response = null)
 * @method static \JeffersonGoncalves\GdprDsar\Models\DataSubjectRequest reject(\JeffersonGoncalves\GdprDsar\Models\DataSubjectRequest $request, string $reason)
 * @method static array<string, list<array<string, mixed>>> export(\Illuminate\Database\Eloquent\Model $subject)
 * @method static string exportToFile(\Illuminate\Database\Eloquent\Model $subject, string $filename)
 * @method static int erase(\Illuminate\Database\Eloquent\Model $subject)
 *
 * @see DsarManager
 */
class GdprDsar extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return DsarManager::class;
    }
}

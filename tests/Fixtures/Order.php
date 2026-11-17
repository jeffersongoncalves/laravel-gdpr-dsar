<?php

namespace JeffersonGoncalves\GdprDsar\Tests\Fixtures;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use JeffersonGoncalves\Gdpr\Attributes\PersonalData;
use JeffersonGoncalves\Gdpr\Concerns\InteractsWithPersonalData;
use JeffersonGoncalves\Gdpr\Contracts\HasPersonalData;
use JeffersonGoncalves\GdprDsar\Contracts\ResolvesDataSubject;

#[PersonalData('shipping_address')]
class Order extends Model implements HasPersonalData, ResolvesDataSubject
{
    use InteractsWithPersonalData;

    protected $guarded = [];

    public $timestamps = false;

    public static function dataSubjectQuery(Model $subject): Builder
    {
        return static::query()->where('user_id', $subject->getKey());
    }
}

<?php

namespace JeffersonGoncalves\GdprDsar\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use JeffersonGoncalves\Gdpr\Attributes\PersonalData;
use JeffersonGoncalves\Gdpr\Concerns\InteractsWithPersonalData;
use JeffersonGoncalves\Gdpr\Contracts\HasPersonalData;
use JeffersonGoncalves\Gdpr\Enums\AnonymizeStrategy;
use JeffersonGoncalves\Gdpr\Enums\LegalBasis;
use JeffersonGoncalves\GdprDsar\Enums\RequestStatus;
use JeffersonGoncalves\GdprDsar\Enums\RequestType;

/**
 * @property RequestType $type
 * @property RequestStatus $status
 * @property string|null $export_path
 * @property Carbon $due_at
 * @property Carbon|null $verified_at
 * @property Carbon|null $extended_at
 * @property string|null $extension_reason
 * @property Carbon|null $completed_at
 * @property Model|null $subject
 */
#[PersonalData('requester_name', purpose: 'Answer data subject requests (Arts. 12 to 22)', legalBasis: LegalBasis::LegalObligation, anonymize: AnonymizeStrategy::Mask)]
#[PersonalData('requester_email', purpose: 'Answer data subject requests (Arts. 12 to 22)', legalBasis: LegalBasis::LegalObligation, anonymize: AnonymizeStrategy::Hash)]
class DataSubjectRequest extends Model implements HasPersonalData
{
    use InteractsWithPersonalData;

    protected $guarded = [];

    public function getTable(): string
    {
        return config('gdpr-dsar.table', 'gdpr_dsar_requests');
    }

    protected function casts(): array
    {
        return [
            'type' => RequestType::class,
            'status' => RequestStatus::class,
            'due_at' => 'datetime',
            'verified_at' => 'datetime',
            'extended_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->whereIn('status', RequestStatus::open());
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeOverdue(Builder $query): void
    {
        $query->whereIn('status', RequestStatus::open())->where('due_at', '<', now());
    }

    public function isOpen(): bool
    {
        return in_array($this->status, RequestStatus::open(), true);
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }
}

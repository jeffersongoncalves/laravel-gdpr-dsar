<?php

namespace JeffersonGoncalves\GdprDsar;

use Carbon\CarbonInterval;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use JeffersonGoncalves\Gdpr\Contracts\HasPersonalData;
use JeffersonGoncalves\Gdpr\Gdpr;
use JeffersonGoncalves\GdprDsar\Contracts\ResolvesDataSubject;
use JeffersonGoncalves\GdprDsar\Enums\RequestStatus;
use JeffersonGoncalves\GdprDsar\Enums\RequestType;
use JeffersonGoncalves\GdprDsar\Events\DataSubjectRequestClosed;
use JeffersonGoncalves\GdprDsar\Events\DataSubjectRequestOpened;
use JeffersonGoncalves\GdprDsar\Models\DataSubjectRequest;
use LogicException;

class DsarManager
{
    public function __construct(protected Gdpr $gdpr) {}

    /**
     * @param  array{name?: string|null, email?: string|null, details?: string|null}  $requester
     */
    public function open(RequestType $type, ?Model $subject = null, array $requester = []): DataSubjectRequest
    {
        $request = DataSubjectRequest::create([
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject === null ? null : (string) $subject->getKey(),
            'requester_name' => $requester['name'] ?? null,
            'requester_email' => $requester['email'] ?? null,
            'details' => $requester['details'] ?? null,
            'type' => $type,
            'status' => RequestStatus::Pending,
            'due_at' => now()->add(CarbonInterval::make((string) config('gdpr-dsar.deadline', '1 month'))),
        ]);

        event(new DataSubjectRequestOpened($request));

        return $request;
    }

    /**
     * Mark the requester's identity as checked. Required before fulfil(): never export or erase on an unverified request.
     */
    public function verify(DataSubjectRequest $request, ?Model $subject = null): DataSubjectRequest
    {
        $request->forceFill(array_filter([
            'verified_at' => now(),
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject === null ? null : (string) $subject->getKey(),
        ]))->save();

        return $request;
    }

    /**
     * Run the automatic types (access, portability, anonymization, deletion) and complete the request.
     */
    public function fulfil(DataSubjectRequest $request, ?string $response = null): DataSubjectRequest
    {
        if (! $request->isOpen()) {
            throw new LogicException("Request [{$request->getKey()}] is already closed.");
        }

        if (! $request->isVerified()) {
            throw new LogicException("Verify the requester's identity before fulfilling request [{$request->getKey()}].");
        }

        if (! $request->type->isAutomatic()) {
            throw new LogicException("Requests of type [{$request->type->value}] are answered by hand: call complete() when done.");
        }

        $subject = $request->subject;

        if (! $subject instanceof Model) {
            throw new LogicException("Request [{$request->getKey()}] has no data subject: pass one to verify().");
        }

        match ($request->type) {
            RequestType::Access, RequestType::Portability => $request->export_path = $this->exportToFile($subject, "request-{$request->getKey()}.json"),
            default => $this->erase($subject),
        };

        return $this->complete($request, $response);
    }

    /**
     * Extend the deadline once by two further months for a complex or numerous request (Art. 12(3)).
     * Tell the data subject, with the reasons, within the first month.
     */
    public function extend(DataSubjectRequest $request, string $reason): DataSubjectRequest
    {
        if (! $request->isOpen() || $request->extended_at !== null) {
            throw new LogicException("Request [{$request->getKey()}] is closed or was already extended.");
        }

        if ($request->due_at->isPast()) {
            throw new LogicException("Request [{$request->getKey()}] is past its deadline: extend it within the first month.");
        }

        $request->forceFill([
            'due_at' => $request->due_at->copy()->add(CarbonInterval::make((string) config('gdpr-dsar.extension', '2 months'))),
            'extended_at' => now(),
            'extension_reason' => $reason,
        ])->save();

        return $request;
    }

    public function complete(DataSubjectRequest $request, ?string $response = null): DataSubjectRequest
    {
        return $this->close($request, RequestStatus::Completed, $response);
    }

    public function reject(DataSubjectRequest $request, string $reason): DataSubjectRequest
    {
        return $this->close($request, RequestStatus::Rejected, $reason);
    }

    /**
     * Every declared personal data of the subject, keyed by table (Arts. 15 and 20).
     *
     * @return array<string, list<array<string, mixed>>>
     */
    public function export(Model $subject): array
    {
        $data = [];

        foreach ($this->records($subject) as $record) {
            $data[$record->getTable()][] = $record->exportPersonalData();
        }

        return $data;
    }

    /**
     * @return string the path on the configured disk
     */
    public function exportToFile(Model $subject, string $filename): string
    {
        $path = trim((string) config('gdpr-dsar.directory', 'gdpr/dsar'), '/').'/'.$filename;

        Storage::disk(config('gdpr-dsar.disk'))->put(
            $path,
            (string) json_encode($this->export($subject), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        );

        return $path;
    }

    /**
     * Anonymize every record of the subject with each column's strategy (Keep columns stay: Art. 17(3) exceptions).
     *
     * @return int records anonymized
     */
    public function erase(Model $subject): int
    {
        return DB::transaction(function () use ($subject): int {
            $count = 0;

            foreach ($this->records($subject) as $record) {
                $record->anonymizePersonalData();
                $count++;
            }

            return $count;
        });
    }

    /**
     * Records of gdpr.models that belong to the subject: the subject itself, plus ResolvesDataSubject models.
     *
     * @return iterable<int, Model&HasPersonalData>
     */
    public function records(Model $subject): iterable
    {
        foreach ($this->gdpr->models() as $model) {
            if ($subject instanceof $model) {
                yield $subject;

                continue;
            }

            if (! is_subclass_of($model, ResolvesDataSubject::class)) {
                continue;
            }

            /** @var Builder<Model&HasPersonalData> $query */
            $query = $model::dataSubjectQuery($subject);

            foreach ($query->lazyById() as $record) {
                yield $record;
            }
        }
    }

    protected function close(DataSubjectRequest $request, RequestStatus $status, ?string $response): DataSubjectRequest
    {
        $request->forceFill([
            'status' => $status,
            'response' => $response,
            'completed_at' => now(),
        ])->save();

        event(new DataSubjectRequestClosed($request));

        return $request;
    }
}

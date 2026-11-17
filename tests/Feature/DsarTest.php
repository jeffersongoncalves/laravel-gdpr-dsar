<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use JeffersonGoncalves\GdprDsar\Enums\RequestStatus;
use JeffersonGoncalves\GdprDsar\Enums\RequestType;
use JeffersonGoncalves\GdprDsar\Events\DataSubjectRequestClosed;
use JeffersonGoncalves\GdprDsar\Events\DataSubjectRequestOpened;
use JeffersonGoncalves\GdprDsar\Events\DataSubjectRequestOverdue;
use JeffersonGoncalves\GdprDsar\Facades\GdprDsar;
use JeffersonGoncalves\GdprDsar\Tests\Fixtures\Order;
use JeffersonGoncalves\GdprDsar\Tests\Fixtures\User;

beforeEach(function () {
    $this->user = User::create(['name' => 'Mary Smith', 'email' => 'maria@example.com', 'tax_id' => '123']);
    Order::create(['user_id' => $this->user->id, 'shipping_address' => 'Main St 1', 'total' => 100]);
    Order::create(['user_id' => $this->user->id, 'shipping_address' => 'Main St 2', 'total' => 200]);

    $other = User::create(['name' => 'John', 'email' => 'joao@example.com']);
    Order::create(['user_id' => $other->id, 'shipping_address' => 'Main St 3', 'total' => 300]);
});

it('opens a request with the legal deadline', function () {
    Event::fake([DataSubjectRequestOpened::class]);
    $this->freezeSecond(); // datetime columns drop microseconds

    $request = GdprDsar::open(RequestType::Access, requester: ['email' => 'maria@example.com']);

    expect($request->status)->toBe(RequestStatus::Pending)
        ->and($request->due_at->equalTo(now()->addMonth()))->toBeTrue()
        ->and($request->subject)->toBeNull();

    Event::assertDispatched(DataSubjectRequestOpened::class);
});

it('exports only the subject data, keyed by table', function () {
    expect(GdprDsar::export($this->user))->toBe([
        'users' => [['name' => 'Mary Smith', 'email' => 'maria@example.com', 'tax_id' => '123']],
        'orders' => [['shipping_address' => 'Main St 1'], ['shipping_address' => 'Main St 2']],
    ]);
});

it('refuses to fulfil an unverified request', function () {
    GdprDsar::fulfil(GdprDsar::open(RequestType::Access, $this->user));
})->throws(LogicException::class, 'Verify');

it('fulfils access with a JSON export on the private disk', function () {
    Storage::fake('local');
    Event::fake([DataSubjectRequestClosed::class]);

    $request = GdprDsar::open(RequestType::Portability);
    GdprDsar::verify($request, $this->user);
    GdprDsar::fulfil($request, 'Sent by email');

    $request->refresh();

    expect($request->status)->toBe(RequestStatus::Completed)
        ->and($request->response)->toBe('Sent by email')
        ->and($request->export_path)->toBe("gdpr/dsar/request-{$request->id}.json")
        ->and(json_decode(Storage::disk('local')->get($request->export_path), true)['orders'])->toHaveCount(2);

    Event::assertDispatched(DataSubjectRequestClosed::class);
});

it('fulfils erasure by anonymizing every record of the subject only', function () {
    $request = GdprDsar::verify(GdprDsar::open(RequestType::Erasure, $this->user));
    GdprDsar::fulfil($request);

    $user = $this->user->fresh();

    expect($user->name)->toBe('M*********')
        ->and($user->email)->not->toBe('maria@example.com')
        ->and($user->tax_id)->toBe('123') // Keep: legal retention
        ->and(Order::where('user_id', $user->id)->pluck('shipping_address')->all())->toBe([null, null])
        ->and(Order::whereNotNull('shipping_address')->pluck('shipping_address')->all())->toBe(['Main St 3']);
});

it('leaves manual types to complete()', function () {
    GdprDsar::fulfil(GdprDsar::verify(GdprDsar::open(RequestType::Objection, $this->user)));
})->throws(LogicException::class, 'by hand');

it('rejects with a reason and refuses to reopen', function () {
    $request = GdprDsar::reject(GdprDsar::open(RequestType::Erasure, $this->user), 'Legal retention');

    expect($request->status)->toBe(RequestStatus::Rejected);

    GdprDsar::fulfil(GdprDsar::verify($request));
})->throws(LogicException::class, 'closed');

it('dispatches an event per overdue request', function () {
    Event::fake([DataSubjectRequestOverdue::class]);
    GdprDsar::open(RequestType::Access, $this->user);
    GdprDsar::complete(GdprDsar::open(RequestType::Access, $this->user));

    $this->travel(32)->days();
    Artisan::call('gdpr:dsar-overdue');

    Event::assertDispatchedTimes(DataSubjectRequestOverdue::class, 1);
});

it('extends the deadline once by two months, within the first month', function () {
    $this->freezeSecond();
    $request = GdprDsar::open(RequestType::Access, $this->user);
    $due = $request->due_at->copy();

    GdprDsar::extend($request, 'Thousands of records across several systems');

    expect($request->fresh()->due_at->equalTo($due->addMonths(2)))->toBeTrue()
        ->and($request->fresh()->extension_reason)->toBe('Thousands of records across several systems')
        ->and(fn () => GdprDsar::extend($request, 'again'))->toThrow(LogicException::class, 'already extended');
});

it('refuses to extend after the first month', function () {
    $request = GdprDsar::open(RequestType::Access, $this->user);

    $this->travel(32)->days();

    GdprDsar::extend($request, 'Late');
})->throws(LogicException::class, 'first month');

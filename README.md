# Laravel GDPR DSAR

![Laravel GDPR DSAR](https://raw.githubusercontent.com/jeffersongoncalves/laravel-gdpr-dsar/main/art/jeffersongoncalves-laravel-gdpr-dsar.png)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/jeffersongoncalves/laravel-gdpr-dsar.svg?style=flat-square)](https://packagist.org/packages/jeffersongoncalves/laravel-gdpr-dsar)
[![Tests](https://img.shields.io/github/actions/workflow/status/jeffersongoncalves/laravel-gdpr-dsar/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/jeffersongoncalves/laravel-gdpr-dsar/actions/workflows/tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/jeffersongoncalves/laravel-gdpr-dsar.svg?style=flat-square)](https://packagist.org/packages/jeffersongoncalves/laravel-gdpr-dsar)

Handles data subject requests under the EU General Data Protection Regulation (GDPR, Arts. 12 to 22), using the data you declared with [laravel-gdpr](https://github.com/jeffersongoncalves/laravel-gdpr):

- tracks each request: its type (access, rectification, erasure, restriction, portability, objection and automated decisions, Arts. 15 to 22), its status and the legal deadline: one month, extendable once by two further months (Art. 12(3));
- requires an **identity check** before any export or erasure;
- **exports** every piece of personal data of the subject, from all your models, as JSON on a private disk (access and portability);
- **erases** the subject's data by applying each column's anonymize strategy. Columns marked `Keep` are left untouched (the Art. 17(3) exceptions, such as a legal obligation);
- fires an event for every request past its deadline, so you can alert your Data Protection Officer.

> This package gives you tooling. It does not make you compliant on its own. You still need legal review, for example to decide which requests you can reject and why.

## Installation

```bash
composer require jeffersongoncalves/laravel-gdpr-dsar
php artisan vendor:publish --tag="gdpr-dsar-migrations"
php artisan migrate
php artisan vendor:publish --tag="gdpr-dsar-config"
```

## Locating a subject's data

Models listed in `gdpr.models` (see [laravel-gdpr](https://github.com/jeffersongoncalves/laravel-gdpr)) are searched for the subject's records:

- the subject's own model (e.g. `User`) is found without extra code;
- every other model says how to find the records that belong to a subject:

```php
use JeffersonGoncalves\GdprDsar\Contracts\ResolvesDataSubject;

class Order extends Model implements HasPersonalData, ResolvesDataSubject
{
    use InteractsWithPersonalData;

    public static function dataSubjectQuery(Model $subject): Builder
    {
        return static::query()->where('user_id', $subject->getKey());
    }
}
```

A model that does not implement `ResolvesDataSubject` is skipped.

## Usage

```php
use JeffersonGoncalves\GdprDsar\Enums\RequestType;
use JeffersonGoncalves\GdprDsar\Facades\GdprDsar;

// 1. Open: from a logged-in user, or from a contact form (subject unknown yet)
$request = GdprDsar::open(RequestType::Portability, $user);
$request = GdprDsar::open(RequestType::Erasure, requester: ['name' => 'Mary', 'email' => 'mary@example.com']);

// 2. Verify the requester's identity, linking the subject if needed
GdprDsar::verify($request, $user);

// 3a. Access / portability / erasure run automatically
GdprDsar::fulfil($request, 'Your data is attached.');
$request->export_path; // gdpr/dsar/request-1.json on the configured disk

// 3b. Other rights (rectification, restriction, objection, automated decisions): handle them yourself, then:
GdprDsar::complete($request, 'Your address was corrected.');
GdprDsar::reject($request, 'Data kept to comply with a legal obligation (Art. 17(3)(b)).');
```

Complex or numerous requests can be extended once, by two further months. Do it within the first month and tell the data subject why (Art. 12(3)):

```php
GdprDsar::extend($request, 'Thousands of records across several systems');   // due_at += 2 months
```

Lower-level helpers: `GdprDsar::export($user)`, `GdprDsar::exportToFile($user, 'file.json')`, `GdprDsar::erase($user)`. `erase` runs inside a transaction.

### Events

| Event | When |
|---|---|
| `DataSubjectRequestOpened` | a request is opened. Use it to confirm receipt to the requester |
| `DataSubjectRequestClosed` | a request is completed or rejected. Use it to notify the requester, sending the export as needed |
| `DataSubjectRequestOverdue` | `gdpr:dsar-overdue` found an open request past its deadline |

Schedule the deadline check:

```php
Schedule::command('gdpr:dsar-overdue')->daily();
```

## Testing

```bash
composer test
```

## Changelog

See [CHANGELOG](CHANGELOG.md).

## License

The MIT License (MIT). See [License File](LICENSE.md).

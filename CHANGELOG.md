# Changelog

All notable changes to this project will be documented in this file.

## 1.0.0 - 2026-11-17

First release: GDPR data subject requests for Laravel, part of the laravel-gdpr suite.

- Requests for access, rectification, erasure, restriction, portability, objection and automated decisions (Arts. 15 to 22), with status and an identity check before any export or erasure.
- One-month deadline (Art. 12(3)). `extend()` adds two further months, once, within the first month and with a reason.
- Access and portability export every declared column as JSON to a private disk. Erasure anonymizes every record of the subject; `Keep` columns stay, for the Art. 17(3) exceptions.
- `ResolvesDataSubject` to find a subject's records in other models.
- The `Opened`, `Closed` and `Overdue` events, and `gdpr:dsar-overdue` for the deadline check.

## [Unreleased]

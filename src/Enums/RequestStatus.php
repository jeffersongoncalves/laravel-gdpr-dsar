<?php

namespace JeffersonGoncalves\GdprDsar\Enums;

enum RequestStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Rejected = 'rejected';

    /**
     * @return list<self>
     */
    public static function open(): array
    {
        return [self::Pending];
    }
}

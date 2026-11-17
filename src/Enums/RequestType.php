<?php

namespace JeffersonGoncalves\GdprDsar\Enums;

/**
 * Data subject rights (GDPR Arts. 15 to 22).
 */
enum RequestType: string
{
    case Access = 'access';                          // Art. 15
    case Rectification = 'rectification';            // Art. 16
    case Erasure = 'erasure';                        // Art. 17
    case Restriction = 'restriction';                // Art. 18
    case Portability = 'portability';                // Art. 20
    case Objection = 'objection';                    // Art. 21
    case AutomatedDecision = 'automated_decision';   // Art. 22

    /**
     * Types fulfil() handles on its own; the others are answered by hand, then complete().
     */
    public function isAutomatic(): bool
    {
        return in_array($this, [self::Access, self::Portability, self::Erasure], true);
    }
}

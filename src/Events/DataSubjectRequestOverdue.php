<?php

namespace JeffersonGoncalves\GdprDsar\Events;

use JeffersonGoncalves\GdprDsar\Models\DataSubjectRequest;

class DataSubjectRequestOverdue
{
    public function __construct(public DataSubjectRequest $request) {}
}

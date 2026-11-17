<?php

namespace JeffersonGoncalves\GdprDsar\Events;

use JeffersonGoncalves\GdprDsar\Models\DataSubjectRequest;

class DataSubjectRequestOpened
{
    public function __construct(public DataSubjectRequest $request) {}
}

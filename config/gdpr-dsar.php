<?php

return [

    'table' => 'gdpr_dsar_requests',

    /*
     * Time to answer a request: one month from receipt (GDPR Art. 12(3)).
     */
    'deadline' => '1 month',

    /*
     * Extension for complex or numerous requests: two further months (Art. 12(3)).
     * Tell the data subject, with the reasons, within the first month.
     */
    'extension' => '2 months',

    /*
     * Where exports (JSON, Arts. 15 and 20) are written. Keep it a private disk:
     * the file holds the data subject's personal data.
     */
    'disk' => 'local',
    'directory' => 'gdpr/dsar',

];

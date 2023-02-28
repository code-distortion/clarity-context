<?php

return [

    /*
     |--------------------------------------------------------------------------
     | Turn Clarity Context On or Off
     |--------------------------------------------------------------------------
     |
     | When enabled, Clarity will keep track of the context details your
     | application sets. When turned off, Clarity saves time by not
     | tracking them. Your code operates the same, either way.
     |
     | boolean
     |
     */

    'enabled' => env('CLARITY_CONTEXT__ENABLED', true),

];

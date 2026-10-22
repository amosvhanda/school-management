<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Require two-factor authentication for privileged roles
    |--------------------------------------------------------------------------
    |
    | When true, listed roles (and always super_admin) must confirm 2FA before
    | accessing protected APIs. Defaults to true in production.
    |
    */
    'require_two_factor' => filter_var(
        env(
            'REQUIRE_TWO_FACTOR',
            env('APP_ENV') === 'production' ? 'true' : 'false'
        ),
        FILTER_VALIDATE_BOOLEAN
    ),

    'require_two_factor_roles' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env(
            'REQUIRE_TWO_FACTOR_ROLES',
            'super_admin,admin,school_admin,finance,accounts'
        ))
    ))),
];

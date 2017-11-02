<?php

return [
    'clientId' => env('VSTS_APP_ID'),
    'clientSecret' => env('VSTS_CLIENT_SECRET'),
    'redirectUri' => env('VSTS_REDIRECT_URL'),
    'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
    'urlAuthorize' => 'https://app.vssps.visualstudio.com/oauth2/authorize',
    'urlAccessToken' => 'https://app.vssps.visualstudio.com/oauth2/token',
    'urlResourceOwnerDetails' => 'https://app.vssps.visualstudio.com/oauth2/resource',
    'scopes' => 'vso.agentpools_manage vso.build_execute vso.code_full vso.code_status vso.codesearch vso.connected_server vso.dashboards vso.dashboards_manage vso.entitlements vso.extension.data_write vso.extension_manage vso.gallery_acquire vso.gallery_manage vso.identity_manage vso.loadtest_write vso.notification_manage vso.packaging_manage vso.profile_write vso.project_manage vso.release_manage vso.security_manage vso.serviceendpoint_manage vso.taskgroups_manage vso.test_write vso.wiki_write vso.work_full vso.workitemsearch',
    'account' => env('VSTS_ACCOUNT'),
    'collection' => env('VSTS_COLLECTION', 'DefaultCollection'),
    'domain' => env('VSTS_API_DOMAIN', 'visualstudio.com'),
    'version' => env('VSTS_API_VERSION', '1.0')
];

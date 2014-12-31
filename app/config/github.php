<?php

return array(
    'organization' => $_ENV['GITHUB_ORGANIZATION'],

    'client_id' => $_ENV['GITHUB_CLIENT_ID'],
    'client_secret' => $_ENV['GITHUB_CLIENT_SECRET'],

    'scope' => 'user,read:org,repo',

    'proxy' => $_ENV['GITHUB_CLIENT_PROXY'],
);

<?php

return array(
    'user' => $_ENV['JENKINS_AUTH_USER'],
    'pass' => $_ENV['JENKINS_AUTH_PASS'],

    'url' => $_ENV['JENKINS_CI_URL'],
    'github_token' => $_ENV['JENKINS_GITHUB_TOKEN'],
    'token' => $_ENV['JENKINS_CI_TOKEN'],
    "jobs" => $_ENV['JENKINS_GOLANG_JOBS'],
);

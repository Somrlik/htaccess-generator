<?php

require __DIR__ . '/vendor/autoload.php';

use HtaccessGenerator\HtaccessGenerator;
use Symfony\Component\Yaml\Yaml;

const CREDENTIALS_FILE = __DIR__ . '/credentials.yml';

if (file_exists(CREDENTIALS_FILE)) {
    $credentials = Yaml::parse(file_get_contents(CREDENTIALS_FILE));
} else {
    $example = Yaml::dump(['credentials' => ['user1' => 'password1', 'user2' => 'password2']]);
    file_put_contents(CREDENTIALS_FILE, $example);
    die('Failed to access ' . CREDENTIALS_FILE . '. An example one has been generated.' . PHP_EOL);
}
$credentials = $credentials['credentials'];

$directory = array_key_exists(1, $argv) ? $argv[1] : null;

if (empty($directory)) {
    HtaccessGenerator::generateApacheFiles($credentials);
} else {
    HtaccessGenerator::generateApacheFiles($credentials, $argv[1]);
}

echo "Generated .htaccess and .htpasswd for $directory from credentials" . PHP_EOL;
var_dump($credentials);
exit(0);
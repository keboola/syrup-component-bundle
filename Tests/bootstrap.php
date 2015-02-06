<?php
require_once __DIR__ . '/../vendor/autoload.php';

defined('SYRUP_APP_NAME') || define('SYRUP_APP_NAME', getenv('SYRUP_APP_NAME')? getenv('SYRUP_APP_NAME') : 'syrup-component-bundle-test');
defined('SYRUP_DATABASE_PORT') || define('SYRUP_DATABASE_PORT', getenv('SYRUP_DATABASE_PORT')? getenv('SYRUP_DATABASE_PORT') : null);
defined('SYRUP_DATABASE_HOST') || define('SYRUP_DATABASE_HOST', getenv('SYRUP_DATABASE_HOST')? getenv('SYRUP_DATABASE_HOST') : '127.0.0.1');
defined('SYRUP_DATABASE_USER') || define('SYRUP_DATABASE_USER', getenv('SYRUP_DATABASE_USER')? getenv('SYRUP_DATABASE_USER') : 'syrup');
defined('SYRUP_DATABASE_PASSWORD') || define('SYRUP_DATABASE_PASSWORD', getenv('SYRUP_DATABASE_PASSWORD')? getenv('SYRUP_DATABASE_PASSWORD') : null);
defined('SYRUP_DATABASE_NAME') || define('SYRUP_DATABASE_NAME', getenv('SYRUP_DATABASE_NAME')? getenv('SYRUP_DATABASE_NAME') : 'syrup');
defined('SYRUP_AWS_KEY') || define('SYRUP_AWS_KEY', getenv('SYRUP_AWS_KEY')? getenv('SYRUP_AWS_KEY') : null);
defined('SYRUP_AWS_SECRET') || define('SYRUP_AWS_SECRET', getenv('SYRUP_AWS_SECRET')? getenv('SYRUP_AWS_SECRET') : null);
defined('SYRUP_BITLY_LOGIN') || define('SYRUP_BITLY_LOGIN', getenv('SYRUP_BITLY_LOGIN')? getenv('SYRUP_BITLY_LOGIN') : null);
defined('SYRUP_BITLY_KEY') || define('SYRUP_BITLY_KEY', getenv('SYRUP_BITLY_KEY')? getenv('SYRUP_BITLY_KEY') : null);
defined('SYRUP_SAPI_TEST_TOKEN') || define('SYRUP_SAPI_TEST_TOKEN', getenv('SYRUP_SAPI_TEST_TOKEN')? getenv('SYRUP_SAPI_TEST_TOKEN') : null);
defined('SYRUP_ELASTICSEARCH_HOST') || define('SYRUP_ELASTICSEARCH_HOST', getenv('SYRUP_ELASTICSEARCH_HOST')? getenv('SYRUP_ELASTICSEARCH_HOST') : 'http://127.0.0.1:9200');
defined('SYRUP_SQS_URL') || define('SYRUP_SQS_URL', getenv('SYRUP_SQS_URL')? getenv('SYRUP_SQS_URL') : 'https://sqs.us-east-1.amazonaws.com/[id]/[name]');
defined('SYRUP_S3_BUCKET') || define('SYRUP_S3_BUCKET', getenv('SYRUP_S3_BUCKET')? getenv('SYRUP_S3_BUCKET') : 'keboola-logs/debug-files');

$paramsYaml = \Symfony\Component\Yaml\Yaml::dump([
    'parameters' => [
        'app_name' => SYRUP_APP_NAME,
        'secret' => md5(uniqid()),
        'encryption_key' => md5(uniqid()),
        'database_driver' => 'pdo_mysql',
        'database_port' => SYRUP_DATABASE_PORT,
        'database_host' => SYRUP_DATABASE_HOST,
        'database_user' => SYRUP_DATABASE_USER,
        'database_password' => SYRUP_DATABASE_PASSWORD,
        'database_name' => SYRUP_DATABASE_NAME,
        'syrup.driver' => 'pdo_mysql',
        'syrup.port' => SYRUP_DATABASE_PORT,
        'syrup.host' => SYRUP_DATABASE_HOST,
        'syrup.user' => SYRUP_DATABASE_USER,
        'syrup.password' => SYRUP_DATABASE_PASSWORD,
        'syrup.name' => SYRUP_DATABASE_NAME,
        'locks_db.driver' => 'pdo_mysql',
        'locks_db.port' => SYRUP_DATABASE_PORT,
        'locks_db.host' => SYRUP_DATABASE_HOST,
        'locks_db.user' => SYRUP_DATABASE_USER,
        'locks_db.password' => SYRUP_DATABASE_PASSWORD,
        'locks_db.name' => SYRUP_DATABASE_NAME,
        'uploader' => [
            'aws-access-key' => SYRUP_AWS_KEY,
            'aws-secret-key' => SYRUP_AWS_SECRET,
            's3-upload-path' => SYRUP_S3_BUCKET,
            'bitly-login' => SYRUP_BITLY_LOGIN,
            'bitly-api-key' => SYRUP_BITLY_KEY
        ],
        'storage_api.url' => 'https://connection.keboola.com/',
        'storage_api.test.url' => 'https://connection.keboola.com/',
        'storage_api.test.token' => SYRUP_SAPI_TEST_TOKEN,
        'shared_sapi' => [
            'url' => 'https://connection.keboola.com/',
            'token' => SYRUP_SAPI_TEST_TOKEN
        ],
        'elasticsearch' => [
            'hosts' => [SYRUP_ELASTICSEARCH_HOST]
        ],
        'queue' => [
            'url' => null,
            'db_table' => 'queues'
        ],
        'job_manager' => [
            'index_prefix' => 'devel'
        ],
        'components' => [

        ]
    ]
]);
file_put_contents(__DIR__ . '/../vendor/keboola/syrup/app/config/parameters.yml', $paramsYaml);
touch(__DIR__ . '/../vendor/keboola/syrup/app/config/parameters_shared.yml');

$db = \Doctrine\DBAL\DriverManager::getConnection([
    'driver' => 'pdo_mysql',
    'host' => SYRUP_DATABASE_HOST,
    'dbname' => SYRUP_DATABASE_NAME,
    'user' => SYRUP_DATABASE_USER,
    'password' => SYRUP_DATABASE_PASSWORD,
    'port' => SYRUP_DATABASE_PORT
]);

$stmt = $db->prepare(file_get_contents(__DIR__ . '/db.sql'));
$stmt->execute();
$stmt->closeCursor();

$db->insert('queues', [
    'id' => 'default',
    'access_key' => SYRUP_AWS_KEY,
    'secret_key' => SYRUP_AWS_SECRET,
    'region' => 'us-east-1',
    'url' => SYRUP_SQS_URL
]);

passthru('php vendor/sensio/distribution-bundle/Sensio/Bundle/DistributionBundle/Resources/bin/build_bootstrap.php '
    . 'vendor/keboola/syrup/app vendor');
passthru(sprintf('php "%s/../vendor/keboola/syrup/app/console" cache:clear --env=test', __DIR__));
passthru(sprintf('php "%s/../vendor/keboola/syrup/app/console" syrup:create-index -d', __DIR__));

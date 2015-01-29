<?php
require_once __DIR__ . '/../vendor/autoload.php';

function setupConst($name, $default=null)
{
    defined($name) || define($name, getenv($name)? getenv($name) : $default);
}
setupConst('SYRUP_DATABASE_PORT');
setupConst('SYRUP_DATABASE_HOST', 'localhost');
setupConst('SYRUP_DATABASE_USER');
setupConst('SYRUP_DATABASE_PASSWORD');
setupConst('SYRUP_DATABASE_NAME', 'syrup');
setupConst('SYRUP_AWS_KEY');
setupConst('SYRUP_AWS_SECRET');
setupConst('SYRUP_BITLY_LOGIN');
setupConst('SYRUP_BITLY_KEY');
setupConst('SYRUP_SAPI_TEST_TOKEN');
setupConst('SYRUP_ELASTICSEARCH_HOST');

$paramsYaml = \Symfony\Component\Yaml\Yaml::dump([
    'parameters' => [
        'app_name' => 'syrup-component-bundle',
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
            's3-upload-path' => 'keboola-logs/debug-files',
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
    'access_key' => getenv('SYRUP_AWS_KEY'),
    'secret_key' => getenv('SYRUP_AWS_SECRET'),
    'region' => 'us-east-1',
    'url' => 'https://sqs.us-east-1.amazonaws.com/147946154733/syrup_devel'
]);

passthru(sprintf('php "%s/../vendor/keboola/syrup/app/console" cache:clear --env=test --no-warmup', __DIR__));
passthru(sprintf('php "%s/../vendor/keboola/syrup/app/console" syrup:create-index -d', __DIR__));
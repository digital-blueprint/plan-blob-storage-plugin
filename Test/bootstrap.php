<?php

declare(strict_types=1);

// Define these before Kanboard's test base loads app/constants.php. Never use
// a database or logging configuration inherited from the host environment.
define('DB_DRIVER', 'sqlite');
define('DB_FILENAME', ':memory:');
define('DB_RUN_MIGRATIONS', true);
define('DB_WAL_MODE', false);
define('DEBUG', false);
define('LOG_DRIVER', 'none');

require dirname(__DIR__) . '/vendor/autoload.php';

if (!class_exists(\KanboardTests\units\Base::class)) {
    throw new RuntimeException(
        'Kanboard test files are missing. Run: composer reinstall kanboard/kanboard --prefer-source'
    );
}

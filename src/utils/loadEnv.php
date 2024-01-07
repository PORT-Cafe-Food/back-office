<?php
function loadEnv($filePath)
{
    if (!file_exists($filePath)) {
        throw new Exception('.env file not found');
    }

    // Read the file into an array
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    if ($lines === false) {
        throw new Exception('Error reading .env file');
    }

    // Process each line
    foreach ($lines as $line) {
        // Skip comments and empty lines
        if (empty($line) || $line[0] === '#') {
            continue;
        }

        // Split the line into key and value
        list($key, $value) = explode('=', $line, 2);

        // Remove leading/trailing whitespaces
        $key = trim($key);
        $value = trim($value);

        // Set the environment variable
        putenv("$key=$value");
    }
}

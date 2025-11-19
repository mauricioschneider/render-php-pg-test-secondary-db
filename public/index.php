<?php
// File: public/index.php
require __DIR__ . '/../vendor/autoload.php';

// --- Terminal Formatting Constants ---
// Note: \n is included in HEADER_START and HEADER_END for clear separation
define('HEADER_START', "\n\033[1;34m"); // Newline + Bold Blue
define('HEADER_END', "\033[0m\n");     // Reset + Newline
define('SUCCESS', "\033[0;32m");       // Green
define('ERROR', "\033[0;31m");         // Red
define('RESET', "\033[0m");            // Reset

// The SQL query to be executed on both databases
$query = "SELECT * FROM test LIMIT 1";

// --- 1. Get Connection Strings from Environment Variables ---

$dsn_a = getenv('DB_CONN_A');
$dsn_b = getenv('DB_CONN_B');

if (!$dsn_a || !$dsn_b) {
    // Ensure the fatal error message also ends with a newline
    die(ERROR . "FATAL ERROR: Both DB_CONN_A and DB_CONN_B environment variables must be set." . RESET . "\n");
}

/**
 * Connects to a PostgreSQL database using PDO, executes a query, and prints the result.
 */
function connectAndQuery(string $dsn, string $label, string $sql): void
{
    // Print a clearly visible header
    echo HEADER_START . "--- 🔍 DATABASE CONNECTION: $label ---" . HEADER_END;

    // Simple DSN parsing
    $dsn_parts = explode(';', $dsn);
    $conn_dsn = '';
    $user = null;
    $password = null;

    foreach ($dsn_parts as $part) {
        if (strpos($part, 'user=') === 0) {
            $user = substr($part, 5);
        } elseif (strpos(trim($part), 'password=') === 0) { // Added trim just in case
            $password = substr(trim($part), 9);
        } else {
            if (!empty($conn_dsn)) $conn_dsn .= ';';
            $conn_dsn .= $part;
        }
    }

    // Output DSN info with explicit newlines
    echo "  > DSN (partial): " . str_replace("pgsql:", "", $conn_dsn) . "\n";
    echo "  > Query: $sql\n";


    try {
        // 1. Establish the Connection
        $pdo = new PDO($conn_dsn, $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

        echo SUCCESS . "  ✅ STATUS: Connection successful." . RESET . "\n";

        // 2. Execute the Query
        $statement = $pdo->query($sql);
        $result = $statement->fetch();

        echo "\n  --- RESULT DATA ---\n";

        if ($result) {
            echo SUCCESS . "  Total Fields: " . count($result) . RESET . "\n";
            echo "  Data Row:\n";
            // Print array contents with indentation and ensure print_r's internal newlines are preserved
            // We use true for print_r to return the string, then print it
            $data_output = print_r($result, true);
            echo "  " . str_replace("\n", "\n  ", $data_output) . "\n";
        } else {
            echo "  No rows were returned from 'test' table.\n";
        }

    } catch (PDOException $e) {
        echo ERROR . "  ❌ ERROR: PDO Connection or Query Failed." . RESET . "\n";
        $error_message = strtok($e->getMessage(), "\n");
        // Ensure the error details line has a newline
        echo "  Details: " . $error_message . "\n";
    } finally {
        $pdo = null; // Close the connection
    }
    // End the block with a final newline separator
    echo "\n----------------------------------------\n";
}

// --- 2. Call the function for both databases ---

connectAndQuery($dsn_a, "Database A (Primary)", $query);
connectAndQuery($dsn_b, "Database B (Secondary)", $query);
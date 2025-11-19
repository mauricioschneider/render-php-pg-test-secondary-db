<?php
// CRITICAL: Load the Composer autoloader from the parent directory
require __DIR__ . '/../vendor/autoload.php';

// The SQL query to be executed on both databases
$query = "SELECT * FROM test LIMIT 1";

// --- 1. Get Connection Strings from Environment Variables ---

// Example DSN format: "pgsql:host=localhost;port=5432;dbname=mydb;user=myuser;password=mypass"
$dsn_a = getenv('DB_CONN_A');
$dsn_b = getenv('DB_CONN_B');

if (!$dsn_a || !$dsn_b) {
    // Note: Use error_log in a real web app instead of die() for non-CLI
    die("Error: Both DB_CONN_A and DB_CONN_B environment variables must be set.\n");
}

/**
 * Connects to a PostgreSQL database using PDO, executes a query, and prints the result.
 *
 * @param string $dsn The Data Source Name (DSN) for the connection.
 * @param string $label A label to identify the connection (e.g., "Database A").
 * @param string $sql The SQL query to execute.
 */
function connectAndQuery(string $dsn, string $label, string $sql): void
{
    echo "--- Connecting to **$label** ---\n";

    // Simple DSN parsing to extract user/password for PDO constructor
    $dsn_parts = explode(';', $dsn);
    $conn_dsn = '';
    $user = null;
    $password = null;

    foreach ($dsn_parts as $part) {
        if (strpos($part, 'user=') === 0) {
            $user = substr($part, 5);
        } elseif (strpos($part, 'password=') === 0) {
            $password = substr($part, 9);
        } else {
            if (!empty($conn_dsn)) $conn_dsn .= ';';
            $conn_dsn .= $part;
        }
    }

    try {
        // Establish the Connection
        $pdo = new PDO($conn_dsn, $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

        echo "Successfully connected to $label.\n";

        // Execute the Query
        $statement = $pdo->query($sql);
        $result = $statement->fetch();

        if ($result) {
            echo "Query Result for $label (first row):\n";
            print_r($result);
        } else {
            echo "Query executed successfully, but no rows were returned from 'test' table in $label.\n";
        }

    } catch (PDOException $e) {
        echo "PDO Connection/Query Error for **$label**:\n";
        echo "Error details: ". $e->getMessage() . "\n";
    } finally {
        $pdo = null; // Close the connection
    }
    echo "\n";
}

// --- 2. Call the function for both databases ---

connectAndQuery($dsn_a, "Database A", $query);
connectAndQuery($dsn_b, "Database B", $query);
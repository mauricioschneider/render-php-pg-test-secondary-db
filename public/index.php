<?php
// File: public/index.php

// Ensure the page starts with HTML structure
echo "<!DOCTYPE html>\n";
echo "<html><head><title>DB Connection Check</title>";
echo "<style>
    body { font-family: Arial, sans-serif; background-color: #f4f4f4; color: #333; }
    .container { width: 80%; margin: 20px auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
    h2 { color: #0056b3; border-bottom: 2px solid #ccc; padding-bottom: 5px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    pre { background: #eee; padding: 10px; border: 1px solid #ddd; border-radius: 4px; overflow-x: auto; }
</style>";
echo "</head><body><div class='container'>";

require __DIR__ . '/../vendor/autoload.php';

// The SQL query to be executed on both databases
$query = "SELECT * FROM test LIMIT 1";

// --- 1. Get Connection Strings from Environment Variables ---

$dsn_a = getenv('DB_CONN_A');
$dsn_b = getenv('DB_CONN_B');

if (!$dsn_a || !$dsn_b) {
    echo "<p class='error'>FATAL ERROR: Both DB_CONN_A and DB_CONN_B environment variables must be set.</p></div></body></html>";
    exit;
}

/**
 * Connects to a PostgreSQL database using PDO, executes a query, and prints the result in HTML.
 */
function connectAndQuery(string $dsn, string $label, string $sql): void
{
    // Print a clearly visible HTML header for this connection attempt
    echo "<h2>🔍 DATABASE CONNECTION: $label</h2>\n";

    // Simple DSN parsing
    $dsn_parts = explode(';', $dsn);
    $conn_dsn = '';
    $user = null;
    $password = null;

    foreach ($dsn_parts as $part) {
        if (strpos($part, 'user=') === 0) {
            $user = substr($part, 5);
        } elseif (strpos(trim($part), 'password=') === 0) {
            $password = substr(trim($part), 9);
        } else {
            if (!empty($conn_dsn)) $conn_dsn .= ';';
            $conn_dsn .= $part;
        }
    }

    // Output DSN info (excluding password for security)
    echo "<p><strong>DSN (partial):</strong> " . htmlspecialchars(str_replace("pgsql:", "", $conn_dsn)) . "</p>\n";
    echo "<p><strong>Query:</strong> " . htmlspecialchars($sql) . "</p>\n";


    try {
        // 1. Establish the Connection
        $pdo = new PDO($conn_dsn, $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

        echo "<p class='success'>✅ STATUS: Connection successful.</p>\n";

        // 2. Execute the Query
        $statement = $pdo->query($sql);
        $result = $statement->fetch();

        echo "<h3>RESULT DATA:</h3>\n";

        if ($result) {
            echo "<p><strong>Total Fields:</strong> " . count($result) . "</p>\n";
            echo "<p><strong>Data Row:</strong></p>\n";
            // Use <pre> tag to display raw array structure cleanly
            echo "<pre>" . htmlspecialchars(print_r($result, true)) . "</pre>";
        } else {
            echo "<p>No rows were returned from 'test' table.</p>\n";
        }

    } catch (PDOException $e) {
        echo "<p class='error'>❌ ERROR: PDO Connection or Query Failed.</p>\n";
        // Display the full error message in a <pre> block
        echo "<p><strong>Details:</strong></p><pre>" . htmlspecialchars($e->getMessage()) . "</pre>\n";
    } finally {
        $pdo = null; // Close the connection
    }
    echo "<hr>\n";
}

// --- 2. Call the function for both databases ---

connectAndQuery($dsn_a, "Database A (Primary)", $query);
connectAndQuery($dsn_b, "Database B (Secondary)", $query);

// Close the main HTML structure
echo "</div></body></html>";
?>
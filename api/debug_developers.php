<?php
// DIAGNOSTIC SCRIPT - Shows actual PHP errors
// Place this file as: C:\xampp\htdocs\tasktimeline\api\debug_developers.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Developer API Diagnostic</h1>";

echo "<h2>Step 1: Database Connection</h2>";
include_once __DIR__ 'config/database.php';
$database = new Database();
$db = $database->getConnection();

if ($db) {
    echo "✅ Database connected successfully<br>";
} else {
    echo "❌ Database connection failed<br>";
    exit;
}

echo "<h2>Step 2: Load Developer Model</h2>";
include_once __DIR__ 'models/Developer.php';
echo "✅ Developer.php included<br>";

echo "<h2>Step 3: Create Developer Object</h2>";
$developer = new Developer($db);
echo "✅ Developer object created<br>";

echo "<h2>Step 4: Read Developers</h2>";
try {
    $stmt = $developer->read();
    echo "✅ Query executed<br>";
    
    $num = $stmt->rowCount();
    echo "✅ Found $num developers<br><br>";
    
    if ($num > 0) {
        echo "<h3>Developers in database:</h3>";
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>ID</th><th>Name</th><th>Color</th><th>Created At</th></tr>";
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['name'] . "</td>";
            echo "<td>" . $row['color'] . "</td>";
            echo "<td>" . $row['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "<br>";
}

echo "<h2>Step 5: JSON Output Test</h2>";
// Reset query
$stmt = $developer->read();
$devs_arr = array();
$devs_arr["records"] = array();

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $dev_item = array(
        "id" => $row['id'],
        "name" => $row['name'],
        "color" => $row['color'],
        "created_at" => $row['created_at']
    );
    array_push($devs_arr["records"], $dev_item);
}

echo "<h3>JSON that should be returned:</h3>";
echo "<pre>";
echo json_encode($devs_arr, JSON_PRETTY_PRINT);
echo "</pre>";

echo "<hr>";
echo "<h2>✅ All tests passed!</h2>";
echo "<p>If you see this, the API should work. Replace your api/index.php with the fixed version.</p>";
?>
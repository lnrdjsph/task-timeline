<?php
include_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

if($db) {
    echo "Database connection successful!";
} else {
    echo "Database connection failed!";
}
?>
```

Visit: `http://localhost/tasktimeline/api/test.php`

## Step 5: Test API Endpoints

Use your browser or a tool like Postman to test:

**Get all tasks:**
```
GET http://localhost/tasktimeline/api/tasks
```

**Get all developers:**
```
GET http://localhost/tasktimeline/api/developers
```

**Create a task (use Postman or cURL):**
```
POST http://localhost/tasktimeline/api/tasks
Body (JSON):
{
    "task_date": "2026-01-15",
    "title": "Test Task",
    "description": "Testing the API",
    "priority": "high",
    "assigned_to": 1
}
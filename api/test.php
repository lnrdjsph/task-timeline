<?php
include_once __DIR__ . 'config/database.php';

$database = new Database();
$db = $database->getConnection();

if($db) {
    echo "Database connection successful!";
} else {
    echo "Database connection failed!";
}
?>
```

Visit: `http://172.20.60.21/task-timeline/api/test.php`

## Step 5: Test API Endpoints

Use your browser or a tool like Postman to test:

**Get all tasks:**
```
GET http://172.20.60.21/task-timeline/api/tasks
```

**Get all developers:**
```
GET http://172.20.60.21/task-timeline/api/developers
```

**Create a task (use Postman or cURL):**
```
POST http://172.20.60.21/task-timeline/api/tasks
Body (JSON):
{
    "task_date": "2026-01-15",
    "title": "Test Task",
    "description": "Testing the API",
    "priority": "high",
    "assigned_to": 1
}
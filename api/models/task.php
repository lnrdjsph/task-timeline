<?php
class Task
{
    private $conn;
    private $table_name = "tasks";

    public $id;
    public $task_date;
    public $title;
    public $description;
    public $priority;
    public $status;
    public $assigned_to;
    public $start_date;
    public $category;
    public $recurring;
    public $comments;
    public $created_at;
    public $updated_at;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // Get all tasks
    public function read()
    {
        $query = "SELECT t.*, d.name as developer_name, d.color as developer_color
                  FROM " . $this->table_name . " t
                  LEFT JOIN developers d ON t.assigned_to = d.id
                  ORDER BY t.task_date ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Get tasks by date range
    public function readByDateRange($start_date, $end_date)
    {
        $query = "SELECT t.*, d.name as developer_name, d.color as developer_color
                  FROM " . $this->table_name . " t
                  LEFT JOIN developers d ON t.assigned_to = d.id
                  WHERE t.task_date BETWEEN :start_date AND :end_date
                  ORDER BY t.task_date ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":start_date", $start_date);
        $stmt->bindParam(":end_date", $end_date);
        $stmt->execute();
        return $stmt;
    }

    // Get tasks by developer
    public function readByDeveloper($developer_id)
    {
        $query = "SELECT t.*, d.name as developer_name, d.color as developer_color
                  FROM " . $this->table_name . " t
                  LEFT JOIN developers d ON t.assigned_to = d.id
                  WHERE t.assigned_to = :developer_id
                  ORDER BY t.task_date ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":developer_id", $developer_id);
        $stmt->execute();
        return $stmt;
    }

    // Create task
    public function create()
    {
        $query = "INSERT INTO " . $this->table_name . "
              SET task_date=:task_date, 
                  title=:title, 
                  description=:description,
                  priority=:priority,
                  status=:status,
                  assigned_to=:assigned_to,
                  start_date=:start_date,
                  category=:category,
                  recurring=:recurring,
                  comments=:comments";

        $stmt = $this->conn->prepare($query);

        // Sanitize - but DON'T sanitize NULL values
        $this->task_date = htmlspecialchars(strip_tags($this->task_date));
        $this->title = htmlspecialchars(strip_tags($this->title));
        $this->description = htmlspecialchars(strip_tags($this->description ?? ''));
        $this->priority = htmlspecialchars(strip_tags($this->priority));
        $this->status = htmlspecialchars(strip_tags($this->status));

        // Handle start_date - keep NULL if it's NULL, sanitize if it has a value
        if ($this->start_date !== null && $this->start_date !== '') {
            $this->start_date = htmlspecialchars(strip_tags($this->start_date));
        } else {
            $this->start_date = null;
        }

        $this->category = htmlspecialchars(strip_tags($this->category ?? ''));
        $this->recurring = htmlspecialchars(strip_tags($this->recurring ?? 'none'));

        // Bind values
        $stmt->bindParam(":task_date", $this->task_date);
        $stmt->bindParam(":title", $this->title);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":priority", $this->priority);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":assigned_to", $this->assigned_to);
        $stmt->bindParam(":start_date", $this->start_date); // This will bind NULL or the date
        $stmt->bindParam(":category", $this->category);
        $stmt->bindParam(":recurring", $this->recurring);
        $stmt->bindParam(":comments", $this->comments);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Update task - SIMPLIFIED VERSION
    public function update()
    {
        $query = "UPDATE " . $this->table_name . "
                  SET title=:title,
                      description=:description,
                      priority=:priority,
                      status=:status,
                      assigned_to=:assigned_to,
                      start_date=:start_date,
                      category=:category,
                      recurring=:recurring";

        // Only include comments if it's set
        if (isset($this->comments)) {
            $query .= ", comments=:comments";
        }

        $query .= " WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->title = htmlspecialchars(strip_tags($this->title));
        $this->description = htmlspecialchars(strip_tags($this->description ?? ''));
        $this->priority = htmlspecialchars(strip_tags($this->priority));
        $this->status = htmlspecialchars(strip_tags($this->status));
        // In the update() method, the binding should handle NULL properly:
        $this->start_date = $this->start_date ? htmlspecialchars(strip_tags($this->start_date)) : null;
        $stmt->bindParam(":start_date", $this->start_date);
        $this->category = htmlspecialchars(strip_tags($this->category ?? ''));
        $this->recurring = htmlspecialchars(strip_tags($this->recurring ?? 'none'));
        $this->id = htmlspecialchars(strip_tags($this->id));

        // Bind values
        $stmt->bindParam(":title", $this->title);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":priority", $this->priority);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":assigned_to", $this->assigned_to);
        $stmt->bindParam(":start_date", $this->start_date);
        $stmt->bindParam(":category", $this->category);
        $stmt->bindParam(":recurring", $this->recurring);
        $stmt->bindParam(":id", $this->id);

        if (isset($this->comments)) {
            $stmt->bindParam(":comments", $this->comments);
        }

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Delete task
    public function delete()
    {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Get task by ID
    public function readOne()
    {
        $query = "SELECT t.*, d.name as developer_name, d.color as developer_color
                  FROM " . $this->table_name . " t
                  LEFT JOIN developers d ON t.assigned_to = d.id
                  WHERE t.id = ?
                  LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->task_date = $row['task_date'];
            $this->title = $row['title'];
            $this->description = $row['description'];
            $this->priority = $row['priority'];
            $this->status = $row['status'];
            $this->assigned_to = $row['assigned_to'];
            $this->start_date = $row['start_date'];
            $this->category = $row['category'] ?? '';
            $this->recurring = $row['recurring'] ?? 'none';
            $this->comments = $row['comments'] ?? '[]';
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return true;
        }
        return false;
    }
}
?>
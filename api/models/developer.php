<?php
class Developer {
    private $conn;
    private $table_name = "developers";

    public $id;
    public $name;
    public $color;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Get all developers
    public function read() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY name ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Create developer
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET name=:name, color=:color";

        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->color = htmlspecialchars(strip_tags($this->color));

        // Bind values
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":color", $this->color);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Update developer
    public function update() {
        $query = "UPDATE " . $this->table_name . "
                  SET name=:name,
                      color=:color
                  WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->color = htmlspecialchars(strip_tags($this->color));
        $this->id = htmlspecialchars(strip_tags($this->id));

        // Bind values
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":color", $this->color);
        $stmt->bindParam(":id", $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Delete developer
    public function delete() {
        // First, unassign all tasks from this developer
        $query = "UPDATE tasks SET assigned_to = NULL WHERE assigned_to = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();

        // Now delete the developer
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Get developer by ID
    public function readOne() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            $this->name = $row['name'];
            $this->color = $row['color'];
            $this->created_at = $row['created_at'];
            return true;
        }
        return false;
    }
}
?>
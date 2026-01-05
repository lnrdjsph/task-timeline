<?php
class Phase {
    private $conn;
    private $table = 'project_phases';

    public $id;
    public $project_id;
    public $phase_name;
    public $phase_order;
    public $description;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Get all phases
    public function getAll() {
        $query = "SELECT * FROM " . $this->table . " ORDER BY phase_order ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Get single phase
    public function getOne() {
        $query = "SELECT * FROM " . $this->table . " WHERE id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        return $stmt;
    }

    // Get phases by project
    public function getByProject() {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE project_id = ? 
                  ORDER BY phase_order ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->project_id);
        $stmt->execute();
        return $stmt;
    }

    // Get phases with task count
    public function getByProjectWithTaskCount() {
        $query = "SELECT 
                    ph.*,
                    COUNT(pt.id) as task_count,
                    SUM(CASE WHEN pt.status = 'Completed' THEN 1 ELSE 0 END) as completed_tasks,
                    AVG(pt.progress_percent) as avg_progress
                  FROM " . $this->table . " ph
                  LEFT JOIN project_tasks pt ON ph.id = pt.phase_id
                  WHERE ph.project_id = ?
                  GROUP BY ph.id
                  ORDER BY ph.phase_order ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->project_id);
        $stmt->execute();
        return $stmt;
    }

    // Create phase
    public function create() {
        $query = "INSERT INTO " . $this->table . " 
                  SET project_id = :project_id,
                      phase_name = :phase_name,
                      phase_order = :phase_order,
                      description = :description";

        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->phase_name = htmlspecialchars(strip_tags($this->phase_name));
        $this->description = htmlspecialchars(strip_tags($this->description));

        // Bind
        $stmt->bindParam(':project_id', $this->project_id);
        $stmt->bindParam(':phase_name', $this->phase_name);
        $stmt->bindParam(':phase_order', $this->phase_order);
        $stmt->bindParam(':description', $this->description);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }

        printf("Error: %s.\n", $stmt->error);
        return false;
    }

    // Update phase
    public function update() {
        $query = "UPDATE " . $this->table . "
                  SET phase_name = :phase_name,
                      phase_order = :phase_order,
                      description = :description
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->phase_name = htmlspecialchars(strip_tags($this->phase_name));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->id = htmlspecialchars(strip_tags($this->id));

        // Bind
        $stmt->bindParam(':phase_name', $this->phase_name);
        $stmt->bindParam(':phase_order', $this->phase_order);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':id', $this->id);

        if ($stmt->execute()) {
            return true;
        }

        printf("Error: %s.\n", $stmt->error);
        return false;
    }

    // Delete phase
    public function delete() {
        $query = "DELETE FROM " . $this->table . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);

        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Reorder phases
    public function reorder($phases_array) {
        try {
            $this->conn->beginTransaction();

            $query = "UPDATE " . $this->table . " SET phase_order = ? WHERE id = ?";
            $stmt = $this->conn->prepare($query);

            foreach ($phases_array as $order => $phase_id) {
                $stmt->bindParam(1, $order);
                $stmt->bindParam(2, $phase_id);
                $stmt->execute();
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            return false;
        }
    }
}

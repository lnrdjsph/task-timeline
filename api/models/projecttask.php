<?php
class ProjectTask {
    private $conn;
    private $table = 'project_tasks';

    public $id;
    public $project_id;
    public $phase_id;
    public $task_number;
    public $task_title;
    public $responsible_person;
    public $plan_start_date;
    public $plan_end_date;
    public $plan_duration;
    public $actual_start_date;
    public $actual_end_date;
    public $actual_duration;
    public $progress_percent;
    public $status;
    public $remarks;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Get all tasks
    public function getAll() {
        $query = "SELECT 
                    pt.*,
                    p.name as project_name,
                    p.category as project_category,
                    ph.phase_name
                  FROM " . $this->table . " pt
                  LEFT JOIN projects p ON pt.project_id = p.id
                  LEFT JOIN project_phases ph ON pt.phase_id = ph.id
                  ORDER BY pt.task_number ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Get single task
    public function getOne() {
        $query = "SELECT 
                    pt.*,
                    p.name as project_name,
                    p.category as project_category,
                    ph.phase_name
                  FROM " . $this->table . " pt
                  LEFT JOIN projects p ON pt.project_id = p.id
                  LEFT JOIN project_phases ph ON pt.phase_id = ph.id
                  WHERE pt.id = ? 
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        return $stmt;
    }

    // Get tasks by project
    public function getByProject() {
        $query = "SELECT 
                    pt.*,
                    ph.phase_name,
                    ph.phase_order
                  FROM " . $this->table . " pt
                  LEFT JOIN project_phases ph ON pt.phase_id = ph.id
                  WHERE pt.project_id = ?
                  ORDER BY ph.phase_order ASC, pt.task_number ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->project_id);
        $stmt->execute();
        return $stmt;
    }

    // Get tasks by phase
    public function getByPhase() {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE phase_id = ? 
                  ORDER BY task_number ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->phase_id);
        $stmt->execute();
        return $stmt;
    }

    // Get tasks by responsible person
    public function getByResponsiblePerson() {
        $query = "SELECT 
                    pt.*,
                    p.name as project_name,
                    p.category as project_category,
                    ph.phase_name
                  FROM " . $this->table . " pt
                  LEFT JOIN projects p ON pt.project_id = p.id
                  LEFT JOIN project_phases ph ON pt.phase_id = ph.id
                  WHERE pt.responsible_person = ?
                  ORDER BY pt.plan_start_date ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->responsible_person);
        $stmt->execute();
        return $stmt;
    }

    // Get tasks by status
    public function getByStatus() {
        $query = "SELECT 
                    pt.*,
                    p.name as project_name,
                    p.category as project_category,
                    ph.phase_name
                  FROM " . $this->table . " pt
                  LEFT JOIN projects p ON pt.project_id = p.id
                  LEFT JOIN project_phases ph ON pt.phase_id = ph.id
                  WHERE pt.status = ?
                  ORDER BY pt.plan_start_date ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->status);
        $stmt->execute();
        return $stmt;
    }

    // Create task
    public function create() {
        $query = "INSERT INTO " . $this->table . " 
                  SET project_id = :project_id,
                      phase_id = :phase_id,
                      task_number = :task_number,
                      task_title = :task_title,
                      responsible_person = :responsible_person,
                      plan_start_date = :plan_start_date,
                      plan_end_date = :plan_end_date,
                      plan_duration = :plan_duration,
                      actual_start_date = :actual_start_date,
                      actual_end_date = :actual_end_date,
                      actual_duration = :actual_duration,
                      progress_percent = :progress_percent,
                      status = :status,
                      remarks = :remarks";

        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->task_title = htmlspecialchars(strip_tags($this->task_title));
        $this->responsible_person = htmlspecialchars(strip_tags($this->responsible_person));
        $this->status = htmlspecialchars(strip_tags($this->status));
        $this->remarks = htmlspecialchars(strip_tags($this->remarks));

        // Bind
        $stmt->bindParam(':project_id', $this->project_id);
        $stmt->bindParam(':phase_id', $this->phase_id);
        $stmt->bindParam(':task_number', $this->task_number);
        $stmt->bindParam(':task_title', $this->task_title);
        $stmt->bindParam(':responsible_person', $this->responsible_person);
        $stmt->bindParam(':plan_start_date', $this->plan_start_date);
        $stmt->bindParam(':plan_end_date', $this->plan_end_date);
        $stmt->bindParam(':plan_duration', $this->plan_duration);
        $stmt->bindParam(':actual_start_date', $this->actual_start_date);
        $stmt->bindParam(':actual_end_date', $this->actual_end_date);
        $stmt->bindParam(':actual_duration', $this->actual_duration);
        $stmt->bindParam(':progress_percent', $this->progress_percent);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':remarks', $this->remarks);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }

        printf("Error: %s.\n", $stmt->error);
        return false;
    }

    // Update task
    public function update() {
        $query = "UPDATE " . $this->table . "
                  SET phase_id = :phase_id,
                      task_number = :task_number,
                      task_title = :task_title,
                      responsible_person = :responsible_person,
                      plan_start_date = :plan_start_date,
                      plan_end_date = :plan_end_date,
                      plan_duration = :plan_duration,
                      actual_start_date = :actual_start_date,
                      actual_end_date = :actual_end_date,
                      actual_duration = :actual_duration,
                      progress_percent = :progress_percent,
                      status = :status,
                      remarks = :remarks
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->task_title = htmlspecialchars(strip_tags($this->task_title));
        $this->responsible_person = htmlspecialchars(strip_tags($this->responsible_person));
        $this->status = htmlspecialchars(strip_tags($this->status));
        $this->remarks = htmlspecialchars(strip_tags($this->remarks));
        $this->id = htmlspecialchars(strip_tags($this->id));

        // Bind
        $stmt->bindParam(':phase_id', $this->phase_id);
        $stmt->bindParam(':task_number', $this->task_number);
        $stmt->bindParam(':task_title', $this->task_title);
        $stmt->bindParam(':responsible_person', $this->responsible_person);
        $stmt->bindParam(':plan_start_date', $this->plan_start_date);
        $stmt->bindParam(':plan_end_date', $this->plan_end_date);
        $stmt->bindParam(':plan_duration', $this->plan_duration);
        $stmt->bindParam(':actual_start_date', $this->actual_start_date);
        $stmt->bindParam(':actual_end_date', $this->actual_end_date);
        $stmt->bindParam(':actual_duration', $this->actual_duration);
        $stmt->bindParam(':progress_percent', $this->progress_percent);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':remarks', $this->remarks);
        $stmt->bindParam(':id', $this->id);

        if ($stmt->execute()) {
            return true;
        }

        printf("Error: %s.\n", $stmt->error);
        return false;
    }

    // Update progress only (for quick updates)
    public function updateProgress() {
        $query = "UPDATE " . $this->table . "
                  SET progress_percent = :progress_percent,
                      status = :status
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':progress_percent', $this->progress_percent);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':id', $this->id);

        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Delete task
    public function delete() {
        $query = "DELETE FROM " . $this->table . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);

        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Get task statistics by project
    public function getProjectStats() {
        $query = "SELECT 
                    COUNT(*) as total_tasks,
                    SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed_tasks,
                    SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as in_progress_tasks,
                    SUM(CASE WHEN status = 'Not Started' THEN 1 ELSE 0 END) as not_started_tasks,
                    SUM(CASE WHEN status = 'Blocked' THEN 1 ELSE 0 END) as blocked_tasks,
                    AVG(progress_percent) as avg_progress,
                    SUM(plan_duration) as total_planned_duration,
                    SUM(actual_duration) as total_actual_duration
                  FROM " . $this->table . "
                  WHERE project_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->project_id);
        $stmt->execute();
        return $stmt;
    }

    // Get overdue tasks
    public function getOverdueTasks() {
        $query = "SELECT 
                    pt.*,
                    p.name as project_name,
                    ph.phase_name
                  FROM " . $this->table . " pt
                  LEFT JOIN projects p ON pt.project_id = p.id
                  LEFT JOIN project_phases ph ON pt.phase_id = ph.id
                  WHERE pt.plan_end_date < CURDATE()
                    AND pt.status NOT IN ('Completed')
                  ORDER BY pt.plan_end_date ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Get tasks for Gantt chart (by project)
    public function getGanttData() {
        $query = "SELECT 
                    pt.id,
                    pt.task_title,
                    pt.task_number,
                    pt.responsible_person,
                    pt.plan_start_date,
                    pt.plan_end_date,
                    pt.actual_start_date,
                    pt.actual_end_date,
                    pt.progress_percent,
                    pt.status,
                    ph.phase_name,
                    ph.phase_order
                  FROM " . $this->table . " pt
                  LEFT JOIN project_phases ph ON pt.phase_id = ph.id
                  WHERE pt.project_id = ?
                  ORDER BY ph.phase_order ASC, pt.task_number ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->project_id);
        $stmt->execute();
        return $stmt;
    }
}

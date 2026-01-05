<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once __DIR__ . 'config/database.php';
include_once __DIR__ . 'models/Task.php';
include_once __DIR__ . 'models/Developer.php';
include_once __DIR__ . 'models/Project.php';
include_once __DIR__ . 'models/Phase.php';
include_once __DIR__ . 'models/ProjectTask.php';

$database = new Database();
$db = $database->getConnection();

// Get the request URI and method
$request_uri = $_SERVER['REQUEST_URI'];
$request_method = $_SERVER['REQUEST_METHOD'];

// Parse the URI
$uri_parts = explode('/', trim(parse_url($request_uri, PHP_URL_PATH), '/'));

// Remove 'tasktimeline' and 'api' from the path
$uri_parts = array_values(array_filter($uri_parts, function ($part) {
    return !in_array($part, ['task-timeline', 'api']);
}));

$endpoint = $uri_parts[0] ?? '';
$id = $uri_parts[1] ?? null;

// Route handling
switch ($endpoint) {
    case 'tasks':
        handleTasks($db, $request_method, $id);
        break;
    case 'developers':
        handleDevelopers($db, $request_method, $id);
        break;
    case 'projects':                                    // ⭐ NEW
        handleProjects($db, $request_method, $id);      // ⭐ NEW
        break;                                           // ⭐ NEW
    case 'phases':                                      // ⭐ NEW
        handlePhases($db, $request_method, $id);        // ⭐ NEW
        break;                                           // ⭐ NEW
    case 'project_tasks':                               // ⭐ NEW
        handleProjectTasks($db, $request_method, $id);  // ⭐ NEW
        break;                                           // ⭐ NEW
    default:
        http_response_code(404);
        echo json_encode(array("message" => "Endpoint not found"));
        break;
}

// Handle Task endpoints
function handleTasks($db, $method, $id)
{
    $task = new Task($db);

    switch ($method) {
        case 'GET':
            if ($id) {
                // Get single task
                $task->id = $id;
                if ($task->readOne()) {
                    $task_arr = array(
                        "id" => $task->id,
                        "task_date" => $task->task_date,
                        "title" => $task->title,
                        "description" => $task->description,
                        "priority" => $task->priority,
                        "status" => $task->status,
                        "assigned_to" => $task->assigned_to,
                        "start_date" => $task->start_date,  // FIXED: Added start_date
                        "category" => $task->category,
                        "recurring" => $task->recurring,
                        "comments" => $task->comments,
                        "created_at" => $task->created_at,
                        "updated_at" => $task->updated_at
                    );
                    http_response_code(200);
                    echo json_encode($task_arr);
                } else {
                    http_response_code(404);
                    echo json_encode(array("message" => "Task not found"));
                }
            } else {
                // Get all tasks
                $stmt = $task->read();
                $num = $stmt->rowCount();

                if ($num > 0) {
                    $tasks_arr = array();
                    $tasks_arr["records"] = array();

                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        extract($row);
                        $task_item = array(
                            "id" => $id,
                            "task_date" => $task_date,
                            "title" => $title,
                            "description" => $description,
                            "priority" => $priority,
                            "status" => $status,
                            "assigned_to" => $assigned_to,
                            "start_date" => $start_date ?? null,  // FIXED: Added start_date
                            "category" => $category ?? '',
                            "recurring" => $recurring ?? 'none',
                            "comments" => $comments ?? '[]',
                            "developer_name" => $developer_name,
                            "developer_color" => $developer_color,
                            "created_at" => $created_at,
                            "updated_at" => $updated_at
                        );
                        array_push($tasks_arr["records"], $task_item);
                    }

                    http_response_code(200);
                    echo json_encode($tasks_arr);
                } else {
                    http_response_code(200);
                    echo json_encode(array("records" => array()));
                }
            }
            break;

        case 'POST':
            // Create task
            $data = json_decode(file_get_contents("php://input"));

            if (!empty($data->title) && !empty($data->task_date)) {
                $task->task_date = $data->task_date;
                $task->title = $data->title;
                $task->description = $data->description ?? '';
                $task->priority = $data->priority ?? 'medium';
                $task->status = $data->status ?? 'pending';
                $task->assigned_to = property_exists($data, 'assigned_to') ? $data->assigned_to : null;
                $task->start_date = property_exists($data, 'start_date') ? $data->start_date : null;
                $task->category = $data->category ?? '';
                $task->recurring = $data->recurring ?? 'none';
                $task->comments = $data->comments ?? '[]';

                if ($task->create()) {
                    http_response_code(201);
                    echo json_encode(array("message" => "Task created successfully"));
                } else {
                    http_response_code(503);
                    echo json_encode(array("message" => "Unable to create task"));
                }
            } else {
                http_response_code(400);
                echo json_encode(array("message" => "Unable to create task. Data is incomplete."));
            }
            break;

        case 'PUT':
            // Update task
            if ($id) {
                $data = json_decode(file_get_contents("php://input"));

                $task->id = $id;
                $task->title = $data->title ?? '';
                $task->description = $data->description ?? '';
                $task->priority = $data->priority ?? 'medium';
                $task->status = $data->status ?? 'pending';
                $task->assigned_to = property_exists($data, 'assigned_to') ? $data->assigned_to : null;
                $task->start_date = property_exists($data, 'start_date') ? $data->start_date : null;
                $task->category = $data->category ?? '';
                $task->recurring = $data->recurring ?? 'none';

                // Only set comments if it's provided in the request
                if (property_exists($data, 'comments')) {
                    $task->comments = $data->comments;
                }

                if ($task->update()) {
                    http_response_code(200);
                    echo json_encode(array("message" => "Task updated successfully"));
                } else {
                    http_response_code(503);
                    echo json_encode(array("message" => "Unable to update task"));
                }
            } else {
                http_response_code(400);
                echo json_encode(array("message" => "Task ID required"));
            }
            break;

        case 'DELETE':
            // Delete task
            if ($id) {
                $task->id = $id;

                if ($task->delete()) {
                    http_response_code(200);
                    echo json_encode(array("message" => "Task deleted successfully"));
                } else {
                    http_response_code(503);
                    echo json_encode(array("message" => "Unable to delete task"));
                }
            } else {
                http_response_code(400);
                echo json_encode(array("message" => "Task ID required"));
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(array("message" => "Method not allowed"));
            break;
    }
}

// Handle Developer endpoints
function handleDevelopers($db, $method, $id)
{
    $developer = new Developer($db);

    switch ($method) {
        case 'GET':
            if ($id) {
                // Get single developer
                $developer->id = $id;
                if ($developer->readOne()) {
                    $dev_arr = array(
                        "id" => $developer->id,
                        "name" => $developer->name,
                        "color" => $developer->color,
                        "created_at" => $developer->created_at
                    );
                    http_response_code(200);
                    echo json_encode($dev_arr);
                } else {
                    http_response_code(404);
                    echo json_encode(array("message" => "Developer not found"));
                }
            } else {
                // Get all developers
                $stmt = $developer->read();
                $num = $stmt->rowCount();

                if ($num > 0) {
                    $devs_arr = array();
                    $devs_arr["records"] = array();

                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        extract($row);
                        $dev_item = array(
                            "id" => $id,
                            "name" => $name,
                            "color" => $color,
                            "created_at" => $created_at
                        );
                        array_push($devs_arr["records"], $dev_item);
                    }

                    http_response_code(200);
                    echo json_encode($devs_arr);
                } else {
                    http_response_code(200);
                    echo json_encode(array("records" => array()));
                }
            }
            break;

        case 'POST':
            // Create developer
            $data = json_decode(file_get_contents("php://input"));

            if (!empty($data->name)) {
                $developer->name = $data->name;
                $developer->color = $data->color ?? '#3B82F6';

                if ($developer->create()) {
                    http_response_code(201);
                    echo json_encode(array("message" => "Developer created successfully"));
                } else {
                    http_response_code(503);
                    echo json_encode(array("message" => "Unable to create developer"));
                }
            } else {
                http_response_code(400);
                echo json_encode(array("message" => "Unable to create developer. Data is incomplete."));
            }
            break;

        case 'PUT':
            if ($id) {
                $data = json_decode(file_get_contents("php://input"));

                $developer->id = $id;
                $developer->name = $data->name ?? '';
                $developer->color = $data->color ?? '#3B82F6';

                if ($developer->update()) {
                    http_response_code(200);
                    echo json_encode(array("message" => "Developer updated successfully"));
                } else {
                    http_response_code(503);
                    echo json_encode(array("message" => "Unable to update developer"));
                }
            } else {
                http_response_code(400);
                echo json_encode(array("message" => "Developer ID required"));
            }
            break;

        case 'DELETE':
            // Delete developer
            if ($id) {
                $developer->id = $id;

                if ($developer->delete()) {
                    http_response_code(200);
                    echo json_encode(array("message" => "Developer deleted successfully"));
                } else {
                    http_response_code(503);
                    echo json_encode(array("message" => "Unable to delete developer"));
                }
            } else {
                http_response_code(400);
                echo json_encode(array("message" => "Developer ID required"));
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(array("message" => "Method not allowed"));
            break;
    }
}

// Handle Phase endpoints
function handlePhases($db, $method, $id)
{
    $phase = new Phase($db);

    switch ($method) {
        case 'GET':
            if ($id) {
                // Get single phase
                $phase->id = $id;
                $stmt = $phase->getOne();
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($row) {
                    http_response_code(200);
                    echo json_encode($row);
                } else {
                    http_response_code(404);
                    echo json_encode(array("message" => "Phase not found"));
                }
            } else {
                // Check for query parameters
                if (isset($_GET['project_id'])) {
                    $phase->project_id = $_GET['project_id'];
                    if (isset($_GET['with_tasks'])) {
                        $stmt = $phase->getByProjectWithTaskCount();
                    } else {
                        $stmt = $phase->getByProject();
                    }
                } else {
                    $stmt = $phase->getAll();
                }
                
                $phases_arr = array();
                $phases_arr["records"] = array();
                
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    array_push($phases_arr["records"], $row);
                }
                
                http_response_code(200);
                echo json_encode($phases_arr);
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents("php://input"));

            if (!empty($data->phase_name) && !empty($data->project_id)) {
                $phase->project_id = $data->project_id;
                $phase->phase_name = $data->phase_name;
                $phase->phase_order = $data->phase_order ?? 1;
                $phase->description = $data->description ?? '';

                if ($phase->create()) {
                    http_response_code(201);
                    echo json_encode(array(
                        "message" => "Phase created successfully",
                        "id" => $phase->id
                    ));
                } else {
                    http_response_code(503);
                    echo json_encode(array("message" => "Unable to create phase"));
                }
            } else {
                http_response_code(400);
                echo json_encode(array("message" => "Unable to create phase. Data is incomplete."));
            }
            break;

        case 'PUT':
            if ($id) {
                $data = json_decode(file_get_contents("php://input"));

                $phase->id = $id;
                $phase->phase_name = $data->phase_name ?? '';
                $phase->phase_order = $data->phase_order ?? 1;
                $phase->description = $data->description ?? '';

                if ($phase->update()) {
                    http_response_code(200);
                    echo json_encode(array("message" => "Phase updated successfully"));
                } else {
                    http_response_code(503);
                    echo json_encode(array("message" => "Unable to update phase"));
                }
            } else {
                http_response_code(400);
                echo json_encode(array("message" => "Phase ID required"));
            }
            break;

        case 'DELETE':
            if ($id) {
                $phase->id = $id;

                if ($phase->delete()) {
                    http_response_code(200);
                    echo json_encode(array("message" => "Phase deleted successfully"));
                } else {
                    http_response_code(503);
                    echo json_encode(array("message" => "Unable to delete phase"));
                }
            } else {
                http_response_code(400);
                echo json_encode(array("message" => "Phase ID required"));
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(array("message" => "Method not allowed"));
            break;
    }
}

// Handle ProjectTask endpoints
function handleProjectTasks($db, $method, $id)
{
    $task = new ProjectTask($db);

    switch ($method) {
        case 'GET':
            if ($id) {
                // Get single task
                $task->id = $id;
                $stmt = $task->getOne();
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($row) {
                    http_response_code(200);
                    echo json_encode($row);
                } else {
                    http_response_code(404);
                    echo json_encode(array("message" => "Task not found"));
                }
            } else {
                // Check for query parameters
                if (isset($_GET['project_id'])) {
                    $task->project_id = $_GET['project_id'];
                    if (isset($_GET['gantt'])) {
                        $stmt = $task->getGanttData();
                    } elseif (isset($_GET['stats'])) {
                        $stmt = $task->getProjectStats();
                        $row = $stmt->fetch(PDO::FETCH_ASSOC);
                        http_response_code(200);
                        echo json_encode($row);
                        return;
                    } else {
                        $stmt = $task->getByProject();
                    }
                } elseif (isset($_GET['phase_id'])) {
                    $task->phase_id = $_GET['phase_id'];
                    $stmt = $task->getByPhase();
                } elseif (isset($_GET['responsible_person'])) {
                    $task->responsible_person = $_GET['responsible_person'];
                    $stmt = $task->getByResponsiblePerson();
                } elseif (isset($_GET['status'])) {
                    $task->status = $_GET['status'];
                    $stmt = $task->getByStatus();
                } elseif (isset($_GET['overdue'])) {
                    $stmt = $task->getOverdueTasks();
                } else {
                    $stmt = $task->getAll();
                }
                
                $tasks_arr = array();
                $tasks_arr["records"] = array();
                
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    array_push($tasks_arr["records"], $row);
                }
                
                http_response_code(200);
                echo json_encode($tasks_arr);
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents("php://input"));

            if (!empty($data->task_title) && !empty($data->project_id)) {
                $task->project_id = $data->project_id;
                $task->phase_id = property_exists($data, 'phase_id') ? $data->phase_id : null;
                $task->task_number = $data->task_number ?? null;
                $task->task_title = $data->task_title;
                $task->responsible_person = $data->responsible_person ?? '';
                $task->plan_start_date = property_exists($data, 'plan_start_date') ? $data->plan_start_date : null;
                $task->plan_end_date = property_exists($data, 'plan_end_date') ? $data->plan_end_date : null;
                $task->plan_duration = $data->plan_duration ?? null;
                $task->actual_start_date = property_exists($data, 'actual_start_date') ? $data->actual_start_date : null;
                $task->actual_end_date = property_exists($data, 'actual_end_date') ? $data->actual_end_date : null;
                $task->actual_duration = $data->actual_duration ?? null;
                $task->progress_percent = $data->progress_percent ?? 0;
                $task->status = $data->status ?? 'Not Started';
                $task->remarks = $data->remarks ?? '';

                if ($task->create()) {
                    http_response_code(201);
                    echo json_encode(array(
                        "message" => "Task created successfully",
                        "id" => $task->id
                    ));
                } else {
                    http_response_code(503);
                    echo json_encode(array("message" => "Unable to create task"));
                }
            } else {
                http_response_code(400);
                echo json_encode(array("message" => "Unable to create task. Data is incomplete."));
            }
            break;

        case 'PUT':
            if ($id) {
                $data = json_decode(file_get_contents("php://input"));

                $task->id = $id;
                
                // Check if it's a quick progress update
                if (property_exists($data, 'progress_only') && $data->progress_only === true) {
                    $task->progress_percent = $data->progress_percent ?? 0;
                    $task->status = $data->status ?? 'Not Started';
                    
                    if ($task->updateProgress()) {
                        http_response_code(200);
                        echo json_encode(array("message" => "Progress updated successfully"));
                    } else {
                        http_response_code(503);
                        echo json_encode(array("message" => "Unable to update progress"));
                    }
                } else {
                    // Full update
                    $task->phase_id = property_exists($data, 'phase_id') ? $data->phase_id : null;
                    $task->task_number = $data->task_number ?? null;
                    $task->task_title = $data->task_title ?? '';
                    $task->responsible_person = $data->responsible_person ?? '';
                    $task->plan_start_date = property_exists($data, 'plan_start_date') ? $data->plan_start_date : null;
                    $task->plan_end_date = property_exists($data, 'plan_end_date') ? $data->plan_end_date : null;
                    $task->plan_duration = $data->plan_duration ?? null;
                    $task->actual_start_date = property_exists($data, 'actual_start_date') ? $data->actual_start_date : null;
                    $task->actual_end_date = property_exists($data, 'actual_end_date') ? $data->actual_end_date : null;
                    $task->actual_duration = $data->actual_duration ?? null;
                    $task->progress_percent = $data->progress_percent ?? 0;
                    $task->status = $data->status ?? 'Not Started';
                    $task->remarks = $data->remarks ?? '';

                    if ($task->update()) {
                        http_response_code(200);
                        echo json_encode(array("message" => "Task updated successfully"));
                    } else {
                        http_response_code(503);
                        echo json_encode(array("message" => "Unable to update task"));
                    }
                }
            } else {
                http_response_code(400);
                echo json_encode(array("message" => "Task ID required"));
            }
            break;

        case 'DELETE':
            if ($id) {
                $task->id = $id;

                if ($task->delete()) {
                    http_response_code(200);
                    echo json_encode(array("message" => "Task deleted successfully"));
                } else {
                    http_response_code(503);
                    echo json_encode(array("message" => "Unable to delete task"));
                }
            } else {
                http_response_code(400);
                echo json_encode(array("message" => "Task ID required"));
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(array("message" => "Method not allowed"));
            break;
    }
}
?>
<?php
session_start();

// Data structure: array
$todoList = isset($_SESSION["todoList"]) ? $_SESSION["todoList"] : [];

// User-defined function: appendData
function appendData($task, $todoList) {
    $todoList[] = array("task" => $task, "rating" => 0, "status" => "Not Watched");
    return $todoList;
}

// User-defined function: deleteData
function deleteData($index, $todoList) {
    if (array_key_exists($index, $todoList)) {
        unset($todoList[$index]);
    }
    return array_values($todoList); // Re-index the array
}

// User-defined function: editData
function editData($index, $task, $todoList) {
    if (array_key_exists($index, $todoList)) {
        $todoList[$index]["task"] = $task;
    }
    return $todoList;
}

// Processing form submission to add or edit a task
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["task"])) {
        $task = trim($_POST["task"]);
        if (empty($task)) {
            echo '<script>alert("Error: there is no data to add in array")</script>';
        } else {
            error_log("Task to add/edit: " . $task); // Log the task value for debugging
            if (isset($_POST["index"]) && $_POST["index"] !== '') {
                $index = intval($_POST["index"]);
                $todoList = editData($index, $task, $todoList); // Edit the task
            } else {
                $todoList = appendData($task, $todoList); // Append the task
            }
            $_SESSION["todoList"] = $todoList; // System-defined function: session management
            error_log("Updated todoList: " . print_r($todoList, true)); // Log the updated list
        }
    }
}

// Processing deletion of a task
if (isset($_GET['delete'])) {
    $indexToDelete = intval($_GET['delete']);
    $todoList = deleteData($indexToDelete, $todoList); // Delete the task
    $_SESSION["todoList"] = $todoList;
    header("Location: " . $_SERVER['PHP_SELF']); // Redirect to avoid resubmission
    exit;
}

// Processing editing of a task
$taskToEdit = '';
$indexToEdit = '';
if (isset($_GET['edit'])) {
    $indexToEdit = intval($_GET['edit']);
    if (array_key_exists($indexToEdit, $todoList)) {
        $taskToEdit = $todoList[$indexToEdit]['task'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movies to Watch</title>
    <!-- Bootstrap CSS -->
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <!-- FontAwesome for star ratings -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center">Movie List</h1>
        <h4>
            <?php
                $currentDateTime = new DateTime('now');
                $currentDate = $currentDateTime->format('l, F j, Y');
                echo $currentDate;
            ?>
        </h4>
        <div class="card">
            <div class="card-header">Add a new Movie</div>
            <div class="card-body">
                <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                    <div class="form-group">
                        <input type="text" class="form-control" name="task" placeholder="Enter the movie name here" value="<?php echo htmlspecialchars($taskToEdit); ?>">
                        <input type="hidden" name="index" value="<?php echo htmlspecialchars($indexToEdit); ?>">
                    </div>
                    <button type="submit" class="btn btn-primary"><?php echo $taskToEdit ? 'Edit Movie' : 'Add Movie'; ?></button>
                </form>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">Movies</div>
            <ul class="list-group list-group-flush">
            <?php
                foreach ($todoList as $index => $taskArray) {
                    $rating = intval($taskArray["rating"]);
                    $status = htmlspecialchars($taskArray["status"]);
                    echo '<li class="list-group-item d-flex justify-content-between align-items-center">' . 
                        htmlspecialchars($taskArray["task"]) . 
                        '<div class="star-wrapper">';
                    for ($i = 1; $i <= 5; $i++) {
                        $checked = $i <= $rating ? 'checked' : '';
                        echo '<i class="fa fa-star ' . $checked . '" data-rating="' . $i . '" data-index="' . $index . '"></i>';
                    }
                    echo '</div><div>';
                    echo '<select class="form-control status-select" data-index="' . $index . '">';
                    echo '<option value="Not Watched" ' . ($status === 'Not Watched' ? 'selected' : '') . '>Not Watched</option>';
                    echo '<option value="Watched" ' . ($status === 'Watched' ? 'selected' : '') . '>Watched</option>';
                    echo '</select>';
                    echo '<a href="' . $_SERVER['PHP_SELF'] . '?edit=' . $index . '" class="btn btn-info btn-sm ml-2">Edit</a>' .
                        '<a href="' . $_SERVER['PHP_SELF'] . '?delete=' . $index . '" class="btn btn-danger btn-sm ml-2">Delete</a>' .
                        '</div></li>';
                }
            ?>
            </ul>
        </div>
    </div>
   
    <a href="index.html" class="button1">Home</a>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        document.querySelectorAll('.star-wrapper .fa-star').forEach(star => {
            star.addEventListener('click', function() {
                let rating = this.getAttribute('data-rating');
                let index = this.getAttribute('data-index');
                let stars = this.parentElement.querySelectorAll('.fa-star');
                stars.forEach(s => s.classList.remove('checked'));
                stars.forEach(s => {
                    if (s.getAttribute('data-rating') <= rating) {
                        s.classList.add('checked');
                    }
                });
                updateRating(index, rating);
            });
        });

        function updateRating(index, rating) {
            fetch(`update_rating.php?index=${index}&rating=${rating}`)
                .then(response => response.json())
                .then(data => {
                    console.log('Rating updated:', data);
                })
                .catch(error => {
                    console.error('Error updating rating:', error);
                });
        }

        document.querySelectorAll('.status-select').forEach(select => {
            select.addEventListener('change', function() {
                let status = this.value;
                let index = this.getAttribute('data-index');
                updateStatus(index, status);
            });
        });

        function updateStatus(index, status) {
            fetch(`update_status.php?index=${index}&status=${status}`)
                .then(response => response.json())
                .then(data => {
                    console.log('Status updated:', data);
                })
                .catch(error => {
                    console.error('Error updating status:', error);
                });
        }
    </script>
</body>
</html>

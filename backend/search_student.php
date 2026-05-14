<?php
require_once '../config/database.php';

if (isset($_GET['q'])) {
    $search = mysqli_real_escape_string($conn, $_GET['q']);
    
    $query = "SELECT id_number, first_name, last_name, course, course_level 
              FROM users 
              WHERE (role = 'student' OR role IS NULL)
              AND (id_number LIKE '%$search%' 
                   OR first_name LIKE '%$search%' 
                   OR last_name LIKE '%$search%' 
                   OR email LIKE '%$search%')
              LIMIT 20";
    
    $result = mysqli_query($conn, $query);
    $students = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $students[] = $row;
    }
    
    header('Content-Type: application/json');
    echo json_encode($students);
}
?>
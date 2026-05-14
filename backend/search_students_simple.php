<?php
require_once '../config/database.php';

$q = mysqli_real_escape_string($conn, $_GET['q']);

$query = "SELECT id, id_number, first_name, last_name, course 
          FROM users 
          WHERE role = 'student' 
          AND (id_number LIKE '%$q%' OR first_name LIKE '%$q%' OR last_name LIKE '%$q%' OR CONCAT(first_name, ' ', last_name) LIKE '%$q%')
          ORDER BY last_name ASC
          LIMIT 10";

$result = mysqli_query($conn, $query);
$students = [];

while ($row = mysqli_fetch_assoc($result)) {
    $students[] = $row;
}

header('Content-Type: application/json');
echo json_encode($students);
?>
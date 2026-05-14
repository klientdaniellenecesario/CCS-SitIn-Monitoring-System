<?php
require_once '../config/database.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    $query = "SELECT s.*, u.first_name, u.last_name, u.id_number, u.course 
              FROM sit_in_sessions s 
              JOIN users u ON s.user_id = u.id 
              WHERE s.id = $id";
    
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $data = mysqli_fetch_assoc($result);
        $data['session_date'] = date('M d, Y', strtotime($data['session_date']));
        $data['session_time'] = date('h:i A', strtotime($data['session_time']));
        $data['created_at'] = date('M d, Y h:i A', strtotime($data['created_at']));
        echo json_encode($data);
    } else {
        echo json_encode(['error' => 'No record found']);
    }
}
?>
<?php
function checkAndUpdateTasks($user_id, $conn) {
    // Get student's total completed sit-in count
    $sit_in_count_query = "SELECT COUNT(*) as total FROM sit_in_sessions 
                           WHERE user_id = $user_id AND status = 'completed'";
    $count_result = mysqli_query($conn, $sit_in_count_query);
    $total_sit_ins = mysqli_fetch_assoc($count_result)['total'];
    
    // Get student's total duration minutes
    $duration_query = "SELECT SUM(duration_minutes) as total_minutes FROM sit_in_sessions 
                       WHERE user_id = $user_id AND status = 'completed'";
    $duration_result = mysqli_query($conn, $duration_query);
    $total_minutes = mysqli_fetch_assoc($duration_result)['total_minutes'] ?? 0;
    
    // Get all active tasks
    $tasks_query = "SELECT * FROM tasks WHERE is_active = 1";
    $tasks_result = mysqli_query($conn, $tasks_query);
    
    $points_earned = 0;
    
    while ($task = mysqli_fetch_assoc($tasks_result)) {
        // Check if student already has this task
        $check_student_task = mysqli_query($conn, "SELECT * FROM student_tasks 
                                                   WHERE user_id = $user_id AND task_id = {$task['id']}");
        
        if (mysqli_num_rows($check_student_task) == 0) {
            // Create new student task
            $insert = "INSERT INTO student_tasks (user_id, task_id, progress, status) 
                       VALUES ($user_id, {$task['id']}, 0, 'in_progress')";
            mysqli_query($conn, $insert);
            $student_task_id = mysqli_insert_id($conn);
        } else {
            $existing = mysqli_fetch_assoc($check_student_task);
            if ($existing['status'] == 'completed') {
                continue; // Already completed, skip
            }
            $student_task_id = $existing['id'];
        }
        
        // Update progress based on task type
        $progress = 0;
        $completed = false;
        
        if ($task['task_type'] == 'sit_in_count') {
            $progress = min($total_sit_ins, $task['target_value']);
            if ($progress >= $task['target_value']) {
                $completed = true;
            }
        } elseif ($task['task_type'] == 'hours_milestone') {
            $progress = min($total_minutes, $task['target_value']);
            if ($progress >= $task['target_value']) {
                $completed = true;
            }
        }
        
        // Update progress
        $update = "UPDATE student_tasks SET progress = $progress WHERE id = $student_task_id";
        mysqli_query($conn, $update);
        
        // If completed and not already marked
        if ($completed) {
            $complete_update = "UPDATE student_tasks SET status = 'completed', completed_at = NOW() 
                               WHERE id = $student_task_id AND status = 'in_progress'";
            if (mysqli_query($conn, $complete_update) && mysqli_affected_rows($conn) > 0) {
                // Add points to user
                $points_reward = $task['points_reward'];
                mysqli_query($conn, "UPDATE users SET reward_points = reward_points + $points_reward,
                                     total_reward_points = total_reward_points + $points_reward 
                                     WHERE id = $user_id");
                $points_earned += $points_reward;
                
                // Create notification
                $notification = "🎉 Task Completed: {$task['title']}! You earned $points_reward points!";
                mysqli_query($conn, "INSERT INTO notifications (user_id, message, type, created_at) 
                                     VALUES ($user_id, '$notification', 'reward', NOW())");
            }
        }
    }
    
    return $points_earned;
}
?>
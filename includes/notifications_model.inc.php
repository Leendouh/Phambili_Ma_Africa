<?php

declare(strict_types=1);

// Create a new notification
function create_notification(object $pdo, int $userId, string $userType, string $title, string $message, string $type = 'general'): bool
{
    $query = "INSERT INTO notifications (user_id, title, message, type, is_read, created_at) 
              VALUES (:user_id, :title, :message, :type, 0, NOW())";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(":user_id", $userId, PDO::PARAM_INT);
    $stmt->bindParam(":title", $title);
    $stmt->bindParam(":message", $message);
    $stmt->bindParam(":type", $type);
    return $stmt->execute();
}

// Get notifications for a user
function get_notifications(object $pdo, int $userId, string $userType, bool $unreadOnly = false, int $limit = 10): array
{
    $query = "SELECT * FROM notifications WHERE user_id = :user_id";
    
    if ($unreadOnly) {
        $query .= " AND is_read = 0";
    }
    
    $query .= " ORDER BY created_at DESC LIMIT :limit";
    
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(":user_id", $userId, PDO::PARAM_INT);
    $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get unread notification count
function get_unread_notification_count(object $pdo, int $userId, string $userType): int
{
    $query = "SELECT COUNT(*) as count FROM notifications WHERE user_id = :user_id AND is_read = 0";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(":user_id", $userId, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return (int)($result['count'] ?? 0);
}

// Mark a specific notification as read
function mark_notification_read(object $pdo, int $notificationId): bool
{
    $query = "UPDATE notifications SET is_read = 1 WHERE ID = :id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(":id", $notificationId, PDO::PARAM_INT);
    return $stmt->execute();
}

// Mark all notifications as read for a user
function mark_all_notifications_read(object $pdo, int $userId, string $userType): bool
{
    $query = "UPDATE notifications SET is_read = 1 WHERE user_id = :user_id AND is_read = 0";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(":user_id", $userId, PDO::PARAM_INT);
    return $stmt->execute();
}

// Delete a specific notification
function delete_notification(object $pdo, int $notificationId): bool
{
    $query = "DELETE FROM notifications WHERE ID = :id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(":id", $notificationId, PDO::PARAM_INT);
    return $stmt->execute();
}

// Delete all notifications for a user
function delete_all_notifications(object $pdo, int $userId, string $userType): bool
{
    $query = "DELETE FROM notifications WHERE user_id = :user_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(":user_id", $userId, PDO::PARAM_INT);
    return $stmt->execute();
}

// Clean up old notifications (older than specified days)
function cleanup_old_notifications(object $pdo, int $days = 90): int
{
    $query = "DELETE FROM notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY) AND is_read = 1";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(":days", $days, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->rowCount();
}

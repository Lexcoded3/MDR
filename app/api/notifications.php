<?php
session_start();
if (!isset($_SESSION['id'])) {
    http_response_code(401);
    exit(json_encode(['error' => 'Unauthorized']));
}
require_once '../config/db.php';

$user_id   = (int)$_SESSION['id'];
$user_role = $_SESSION['role'] ?? '';
$action    = $_GET['action'] ?? 'list';

header('Content-Type: application/json');

// Mark single as read
if ($action === 'read' && isset($_GET['id'])) {
    $nid = (int)$_GET['id'];
    $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND (user_id = ? OR (user_role = ? AND user_id IS NULL))")
         ->bind_param("iis", $nid, $user_id, $user_role)
         ->execute();
    exit(json_encode(['success' => true]));
}

// Mark all as read
if ($action === 'read_all') {
    $conn->prepare("UPDATE notifications SET is_read = 1 WHERE is_read = 0 AND (user_id = ? OR (user_role = ? AND user_id IS NULL))")
         ->bind_param("is", $user_id, $user_role)
         ->execute();
    exit(json_encode(['success' => true]));
}

// Fetch notifications
$limit       = min(50, (int)($_GET['limit'] ?? 30));
$offset      = (int)($_GET['offset'] ?? 0);
$filter_type = $_GET['type'] ?? '';

$where  = ["(n.user_id = ? OR (n.user_role = ? AND n.user_id IS NULL))"];
$params = [$user_id, $user_role];
$types  = "is";

if (in_array($filter_type, ['alert', 'event', 'log', 'info'])) {
    $where[]  = "n.type = ?";
    $params[] = $filter_type;
    $types   .= "s";
}

$where_sql = implode(' AND ', $where);

// Count unread
$count_stmt = $conn->prepare("
    SELECT COUNT(*) FROM notifications n 
    WHERE $where_sql AND n.is_read = 0
");
$count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$unread = (int)$count_stmt->get_result()->fetch_column();
$count_stmt->close();

// Fetch list
$list_stmt = $conn->prepare("
    SELECT n.id, n.type, n.title, n.message, n.icon_bg, n.icon, n.link, n.is_read, n.created_at
    FROM notifications n 
    WHERE $where_sql
    ORDER BY n.is_read ASC, n.created_at DESC
    LIMIT $limit OFFSET $offset
");
$list_stmt->bind_param($types, ...$params);
$list_stmt->execute();
$items = $list_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$list_stmt->close();

// Format time ago — uses server timezone correctly
function time_ago($datetime) {
    $now  = new DateTime('now');
    $ago  = new DateTime($datetime);
    $diff = $now->diff($ago);

    if ($diff->y > 0) return $diff->y . 'y ago';
    if ($diff->m > 0) return $diff->m . 'mo ago';
    if ($diff->d > 0) return $diff->d . 'd ago';
    if ($diff->h > 0) return $diff->h . 'h ago';
    if ($diff->i > 0) return $diff->i . 'm ago';
    return 'Just now';
}

$result = [
    'unread_count'  => $unread,
    'notifications' => array_map(function($n) {
        return [
            'id'      => (int)$n['id'],
            'type'    => $n['type'],
            'title'   => $n['title'],
            'message' => $n['message'],
            'icon_bg' => $n['icon_bg'],
            'icon'    => $n['icon'],
            'link'    => $n['link'],
            'is_read' => (int)$n['is_read'],
            'time'    => time_ago($n['created_at']),
        ];
    }, $items)
];

echo json_encode($result);
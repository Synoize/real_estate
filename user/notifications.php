<?php
require_once __DIR__ . '/../includes/app_helpers.php';

requireLogin();

$userId = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'mark_read') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            markNotificationRead($id, $userId);
        }
    } elseif ($action === 'mark_all_read') {
        markAllNotificationsRead($userId);
    }
    exit;
}

$pageTitle = 'Notifications';
require_once __DIR__ . '/../includes/header.php';
$notifications = fetchNotifications($userId);
$unreadCount = unreadNotificationCount($userId);
?>

<section class="mt-20 bg-gray-50 py-6 md:py-10 min-h-[calc(100vh-80px)]">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-10">
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
            <div class="flex items-center justify-between p-5 border-b border-gray-200">
                <div>
                    <h1 class="text-xl font-semibold text-primary">Notifications</h1>
                    <p class="text-sm text-gray-500"><?php echo $unreadCount; ?> unread</p>
                </div>
                <?php if ($unreadCount > 0): ?>
                    <form method="post" action="<?php echo BASE_URL; ?>user/notifications" onsubmit="event.preventDefault(); markAllRead();">
                        <input type="hidden" name="action" value="mark_all_read">
                        <button type="submit" class="text-sm font-semibold text-amber-600 hover:underline">Mark all as read</button>
                    </form>
                <?php endif; ?>
            </div>
            <div class="divide-y divide-gray-100">
                <?php if (empty($notifications)): ?>
                    <div class="p-10 text-center">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 text-gray-400 mb-4">
                            <i class="fa-regular fa-bell text-2xl"></i>
                        </div>
                        <p class="text-gray-500">No notifications yet.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($notifications as $n): ?>
                        <a href="<?php echo e($n['link'] ?? '#'); ?>" onclick="<?php echo $n['is_read'] ? '' : "markRead({$n['id']});"; ?>" class="flex gap-4 px-5 py-4 hover:bg-gray-50 transition <?php echo $n['is_read'] ? '' : 'bg-amber-50/50'; ?>">
                            <div class="shrink-0 w-10 h-10 rounded-full flex items-center justify-center text-base <?php echo $n['type'] === 'site_visit' ? 'bg-blue-100 text-blue-600' : 'bg-green-100 text-green-600'; ?>">
                                <i class="fa-solid <?php echo $n['type'] === 'site_visit' ? 'fa-calendar-check' : 'fa-message'; ?>"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-sm font-semibold text-gray-900"><?php echo e($n['title']); ?></p>
                                    <span class="text-[10px] text-gray-400 shrink-0"><?php echo date('d M h:i A', strtotime($n['created_at'])); ?></span>
                                </div>
                                <p class="text-sm text-gray-600 mt-0.5"><?php echo e($n['message']); ?></p>
                            </div>
                            <?php if (!$n['is_read']): ?>
                                <div class="shrink-0 w-2 h-2 rounded-full bg-amber-500 mt-2"></div>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<script>
function markRead(id) {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '<?php echo BASE_URL; ?>user/notifications', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() { if (xhr.status === 200) location.reload(); };
    xhr.send('action=mark_read&id=' + id);
}
function markAllRead() {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '<?php echo BASE_URL; ?>user/notifications', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() { if (xhr.status === 200) location.reload(); };
    xhr.send('action=mark_all_read');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

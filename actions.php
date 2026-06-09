<?php

require_once __DIR__ . '/includes/app_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL);
}

$action = $_POST['action'] ?? '';
$projectId = (int)($_POST['project_id'] ?? 0);
$isAjax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';

function jsonActionResponse($payload, $statusCode = 200)
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}

try {
    if ($projectId <= 0) {
        throw new RuntimeException('Please select a valid project.');
    }

    $stmt = $pdo->prepare("
        SELECT id, builder_id, assigned_manager_id, slug, project_name
        FROM projects
        WHERE id = :id AND status = 'published'
        LIMIT 1
    ");
    $stmt->execute([':id' => $projectId]);
    $project = $stmt->fetch();

    if (!$project) {
        throw new RuntimeException('Project is not available.');
    }

    if ($action === 'wishlist') {
        if (!isLoggedIn()) {
            if ($isAjax) {
                jsonActionResponse([
                    'success' => false,
                    'message' => 'Please login to save projects.',
                    'login_url' => BASE_URL . 'login'
                ], 401);
            }

            requireLogin();
        }

        $stmt = $pdo->prepare("
            INSERT IGNORE INTO wishlist (user_id, project_id)
            VALUES (:user_id, :project_id)
        ");
        $stmt->execute([
            ':user_id' => $_SESSION['user_id'],
            ':project_id' => $projectId
        ]);

        if ($stmt->rowCount() > 0) {
            $pdo->prepare('UPDATE projects SET total_wishlist = total_wishlist + 1 WHERE id = ?')->execute([$projectId]);
            $message = 'Project saved to your wishlist.';
            setFlash($message, 'success');
        } else {
            $message = 'Project is already in your wishlist.';
            setFlash($message, 'success');
        }

        if ($isAjax) {
            jsonActionResponse([
                'success' => true,
                'saved' => true,
                'message' => $message,
                'wishlist_count' => getWishlistCount()
            ]);
        }

        $redirectTo = trim($_POST['redirect_to'] ?? '');

        if ($redirectTo !== '' && strpos($redirectTo, BASE_URL) === 0) {
            redirect($redirectTo);
        }

        redirect(BASE_URL . 'project/' . urlencode($project['slug']));
    }

    $name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $budget = trim($_POST['budget'] ?? '');
    $preferredTime = trim($_POST['preferred_time'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($name === '' || $email === '' || $phone === '') {
        throw new RuntimeException('Name, email and phone are required.');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Please enter a valid email address.');
    }

    $userId = leadUserId($name, $email, $phone);

    if ($action === 'inquiry') {
        $stmt = $pdo->prepare("
            INSERT INTO inquiries (
                inquiry_id, user_id, builder_id, project_id,
                assigned_manager_id, full_name, email, phone,
                budget, preferred_time, message, source
            ) VALUES (
                :inquiry_id, :user_id, :builder_id, :project_id,
                :assigned_manager_id, :full_name, :email, :phone,
                :budget, :preferred_time, :message, 'website'
            )
        ");

        $stmt->execute([
            ':inquiry_id' => makeCode('INQ'),
            ':user_id' => $userId,
            ':builder_id' => $project['builder_id'],
            ':project_id' => $projectId,
            ':assigned_manager_id' => $project['assigned_manager_id'],
            ':full_name' => $name,
            ':email' => $email,
            ':phone' => $phone,
            ':budget' => $budget,
            ':preferred_time' => $preferredTime,
            ':message' => $message
        ]);

        $pdo->prepare('UPDATE projects SET total_inquiries = total_inquiries + 1 WHERE id = ?')->execute([$projectId]);
        setFlash('Inquiry sent. Our team will contact you shortly.', 'success');
        redirect(BASE_URL . 'project/' . urlencode($project['slug']));
    }

    if ($action === 'site_visit') {
        $visitDate = trim($_POST['visit_date'] ?? '');

        if ($visitDate === '') {
            throw new RuntimeException('Please select a visit date.');
        }

        $stmt = $pdo->prepare("
            INSERT INTO site_visit_bookings (
                booking_id, user_id, builder_id, project_id,
                assigned_manager_id, full_name, email, phone,
                preferred_time, budget, visit_date, message, source
            ) VALUES (
                :booking_id, :user_id, :builder_id, :project_id,
                :assigned_manager_id, :full_name, :email, :phone,
                :preferred_time, :budget, :visit_date, :message, 'website'
            )
        ");

        $stmt->execute([
            ':booking_id' => makeCode('VIS'),
            ':user_id' => $userId,
            ':builder_id' => $project['builder_id'],
            ':project_id' => $projectId,
            ':assigned_manager_id' => $project['assigned_manager_id'],
            ':full_name' => $name,
            ':email' => $email,
            ':phone' => $phone,
            ':preferred_time' => $preferredTime,
            ':budget' => $budget,
            ':visit_date' => $visitDate,
            ':message' => $message
        ]);

        setFlash('Site visit booked. We will confirm the slot soon.', 'success');
        redirect(BASE_URL . 'project/' . urlencode($project['slug']));
    }

    throw new RuntimeException('Unsupported action.');
} catch (Throwable $e) {
    if ($isAjax) {
        jsonActionResponse([
            'success' => false,
            'message' => $e->getMessage()
        ], 422);
    }

    setFlash($e->getMessage(), 'danger');
    $fallback = !empty($project['slug']) ? BASE_URL . 'project/' . urlencode($project['slug']) : BASE_URL;
    redirect($fallback);
}

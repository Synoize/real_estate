<?php

require_once __DIR__ . '/db_connect.php';

function makeSlug($value)
{
    $slug = strtolower(trim((string)$value));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    return trim($slug, '-') ?: bin2hex(random_bytes(4));
}

function cityUrl($city, $params = [])
{
    $query = array_filter($params, static function ($value) {
        return $value !== null && $value !== '';
    });

    return BASE_URL . makeSlug($city) . ($query ? '?' . http_build_query($query) : '');
}

function fetchAvailableProjectCities($limit = 12)
{
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT city, COUNT(*) AS total
        FROM projects
        WHERE status = 'published'
          AND deleted_at IS NULL
          AND city IS NOT NULL
          AND city <> ''
        GROUP BY city
        HAVING total > 0
        ORDER BY total DESC, city ASC
        LIMIT " . (int)$limit
    );
    $stmt->execute();

    return $stmt->fetchAll();
}

function fetchAvailableProjectLocalities($limit = 8)
{
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT city, locality, COUNT(*) AS total
        FROM projects
        WHERE status = 'published'
          AND deleted_at IS NULL
          AND city IS NOT NULL
          AND city <> ''
          AND locality IS NOT NULL
          AND locality <> ''
        GROUP BY city, locality
        HAVING total > 0
        ORDER BY total DESC, city ASC, locality ASC
        LIMIT " . (int)$limit
    );
    $stmt->execute();

    return $stmt->fetchAll();
}

function fetchAvailableProjectBudgets($filters = [], $limit = 20)
{
    global $pdo;

    $where = [
        "status = 'published'",
        "deleted_at IS NULL",
        "min_price IS NOT NULL",
        "min_price > 0"
    ];
    $params = [];

    if (!empty($filters['city'])) {
        $where[] = 'city = :city';
        $params[':city'] = $filters['city'];
    }

    if (!empty($filters['type'])) {
        $where[] = 'project_type = :type';
        $params[':type'] = $filters['type'];
    }

    $stmt = $pdo->prepare("
        SELECT DISTINCT min_price AS value
        FROM projects
        WHERE " . implode(' AND ', $where) . "
        ORDER BY min_price ASC
        LIMIT " . (int)$limit
    );
    $stmt->execute($params);

    return array_map(static function ($row) {
        $value = (float)$row['value'];

        return [
            'value' => rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.'),
            'label' => formatCurrency($value)
        ];
    }, $stmt->fetchAll());
}

function makeCode($prefix)
{
    return strtoupper($prefix) . date('ymdHis') . random_int(100, 999);
}

function projectImage($project)
{
    if (!empty($project['featured_image'])) {
        return getImageUrl($project['featured_image']);
    }

    if (!empty($project['thumbnail_image'])) {
        return getImageUrl($project['thumbnail_image']);
    }

    return 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=1200&q=80';
}

function projectPriceRange($project)
{
    $min = (float)($project['min_price'] ?? 0);
    $max = (float)($project['max_price'] ?? 0);

    if ($min > 0 && $max > 0 && $max > $min) {
        return formatCurrency($min) . ' - ' . formatCurrency($max);
    }

    if ($min > 0) {
        return 'From ' . formatCurrency($min);
    }

    return 'Price on request';
}

function fetchProjectUnitPlansForProjects($projectIds, $limitPerProject = 5)
{
    global $pdo;

    $projectIds = array_values(array_unique(array_filter(array_map('intval', $projectIds))));

    if (empty($projectIds)) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($projectIds), '?'));
    $stmt = $pdo->prepare("
        SELECT *
        FROM project_unit_plans
        WHERE project_id IN ($placeholders)
        ORDER BY project_id ASC, price ASC, id ASC
    ");
    $stmt->execute($projectIds);

    $plansByProject = [];

    foreach ($stmt->fetchAll() as $plan) {
        $projectId = (int)$plan['project_id'];

        if (!isset($plansByProject[$projectId])) {
            $plansByProject[$projectId] = [];
        }

        if (count($plansByProject[$projectId]) < $limitPerProject) {
            $plansByProject[$projectId][] = $plan;
        }
    }

    return $plansByProject;
}

function fetchProjectPrimaryVideosForProjects($projectIds)
{
    global $pdo;

    $projectIds = array_values(array_unique(array_filter(array_map('intval', $projectIds))));

    if (empty($projectIds)) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($projectIds), '?'));
    $stmt = $pdo->prepare("
        SELECT *
        FROM project_videos
        WHERE project_id IN ($placeholders)
        ORDER BY project_id ASC, id ASC
    ");
    $stmt->execute($projectIds);

    $videosByProject = [];

    foreach ($stmt->fetchAll() as $video) {
        $projectId = (int)$video['project_id'];

        if (!isset($videosByProject[$projectId])) {
            $videosByProject[$projectId] = $video;
        }
    }

    return $videosByProject;
}

function projectAreaRange($project, $unitPlans = [])
{
    $areas = [];
    $unitLabel = 'sqft';

    foreach ($unitPlans as $plan) {
        $area = trim((string)($plan['area'] ?? ''));

        if ($area === '') {
            continue;
        }

        if (preg_match('/([\d,.]+)/', $area, $matches)) {
            $areas[] = (float)str_replace(',', '', $matches[1]);

            if (stripos($area, 'sq') !== false) {
                $unitLabel = trim(preg_replace('/[\d,.\s-]+/', '', $area)) ?: 'sqft';
            }
        }
    }

    $areas = array_values(array_unique(array_filter($areas)));
    sort($areas);

    if (count($areas) > 1) {
        return rtrim(rtrim(number_format($areas[0], 2), '0'), '.') . ' - ' .
            rtrim(rtrim(number_format($areas[count($areas) - 1], 2), '0'), '.') . ' ' . $unitLabel;
    }

    if (count($areas) === 1) {
        return rtrim(rtrim(number_format($areas[0], 2), '0'), '.') . ' ' . $unitLabel;
    }

    return $project['total_area'] ?: 'Area on request';
}

function projectSaleBadge($project)
{
    if (($project['project_status'] ?? '') === 'Completed') {
        return 'Ready for Sale';
    }

    if (($project['project_status'] ?? '') === 'Ongoing') {
        return 'Under Construction';
    }

    if (($project['project_status'] ?? '') === 'Upcoming') {
        return 'Upcoming';
    }

    return (int)($project['is_verified'] ?? 0) === 1 ? 'Verified' : '';
}

function fetchPublishedProjects($filters = [], $limit = 12)
{
    global $pdo;

    $where = ["p.status = 'published'", "p.deleted_at IS NULL"];
    $params = [];

    if (!empty($filters['city'])) {
        $where[] = 'p.city = :city';
        $params[':city'] = $filters['city'];
    }

    if (!empty($filters['type'])) {
        $where[] = 'p.project_type = :type';
        $params[':type'] = $filters['type'];
    }

    if (!empty($filters['q'])) {
        $where[] = '(
            p.project_name LIKE :q_project
            OR p.locality LIKE :q_locality
            OR p.city LIKE :q_city
            OR b.company_name LIKE :q_builder
        )';
        $searchTerm = '%' . $filters['q'] . '%';
        $params[':q_project'] = $searchTerm;
        $params[':q_locality'] = $searchTerm;
        $params[':q_city'] = $searchTerm;
        $params[':q_builder'] = $searchTerm;
    }

    if (!empty($filters['budget_max'])) {
        $where[] = 'p.min_price <= :budget_max';
        $params[':budget_max'] = (float)$filters['budget_max'];
    }

    $sql = "
        SELECT p.*, b.company_name, b.builder_name, b.phone AS builder_phone,
               b.whatsapp_number, b.company_logo
        FROM projects p
        INNER JOIN builders b ON b.id = p.builder_id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY p.is_featured DESC, p.created_at DESC
        LIMIT " . (int)$limit;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function fetchProjectBySlug($slug)
{
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT p.*, b.company_name, b.builder_name, b.email AS builder_email,
               b.phone AS builder_phone, b.whatsapp_number, b.company_logo,
               b.company_description
        FROM projects p
        INNER JOIN builders b ON b.id = p.builder_id
        WHERE p.slug = :slug
          AND p.status = 'published'
          AND p.deleted_at IS NULL
        LIMIT 1
    ");

    $stmt->execute([':slug' => $slug]);
    return $stmt->fetch();
}

function leadUserId($name, $email, $phone)
{
    global $pdo;

    if (isLoggedIn()) {
        return (int)$_SESSION['user_id'];
    }

    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email OR phone = :phone LIMIT 1');
    $stmt->execute([':email' => $email, ':phone' => $phone]);
    $user = $stmt->fetch();

    if ($user) {
        return (int)$user['id'];
    }

    $stmt = $pdo->prepare("
        INSERT INTO users (
            uuid, full_name, email, phone, password,
            is_verified, terms_accepted, privacy_accepted, status
        ) VALUES (
            UUID(), :full_name, :email, :phone, :password,
            0, 1, 1, 'active'
        )
    ");

    $stmt->execute([
        ':full_name' => $name,
        ':email' => $email,
        ':phone' => $phone,
        ':password' => password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT)
    ]);

    return (int)$pdo->lastInsertId();
}

function tableCount($table, $where = '1=1', $params = [])
{
    global $pdo;

    $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM {$table} WHERE {$where}");
    $stmt->execute($params);
    $row = $stmt->fetch();

    return (int)($row['total'] ?? 0);
}

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
    $useLocalityPath = !empty($params['_locality_path']);
    unset($params['_locality_path']);

    $locality = $useLocalityPath ? trim((string)($params['q'] ?? '')) : '';

    if ($useLocalityPath && $locality !== '') {
        unset($params['q']);
    }

    $query = array_filter($params, static function ($value) {
        return $value !== null && $value !== '';
    });

    $localitySegment = $locality !== '' ? str_replace('%20', '-', rawurlencode($locality)) : '';

    return BASE_URL . makeSlug($city) . ($localitySegment !== '' ? '/' . $localitySegment : '') . ($query ? '?' . http_build_query($query) : '');
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

function fetchAvailableProjectTypes($filters = [], $limit = 8)
{
    global $pdo;

    $where = [
        "p.status = 'published'",
        "p.deleted_at IS NULL",
        "p.project_type IS NOT NULL",
        "p.project_type <> ''",
        "(c.id IS NULL OR c.status = 'active')"
    ];
    $params = [];

    if (!empty($filters['city'])) {
        $where[] = 'p.city = :city';
        $params[':city'] = $filters['city'];
    }

    $stmt = $pdo->prepare("
        SELECT p.project_type, COUNT(*) AS total
        FROM projects p
        LEFT JOIN project_categories c ON c.category_name = p.project_type
        WHERE " . implode(' AND ', $where) . "
        GROUP BY p.project_type
        HAVING total > 0
        ORDER BY MIN(COALESCE(c.id, 999)), p.project_type ASC
        LIMIT " . (int)$limit
    );
    $stmt->execute($params);

    return array_column($stmt->fetchAll(), 'project_type');
}

function fetchAvailableProjectBudgets($filters = [], $limit = 20)
{
    global $pdo;

    $where = [
        "p.status = 'published'",
        "p.deleted_at IS NULL",
        "up.price IS NOT NULL",
        "up.price > 0"
    ];
    $params = [];

    if (!empty($filters['city'])) {
        $where[] = 'p.city = :city';
        $params[':city'] = $filters['city'];
    }

    if (!empty($filters['type'])) {
        $where[] = 'p.project_type = :type';
        $params[':type'] = $filters['type'];
    }

    $stmt = $pdo->prepare("
        SELECT DISTINCT up.price AS value
        FROM project_unit_plans up
        INNER JOIN projects p ON p.id = up.project_id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY up.price ASC
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

function fetchProjectGalleryImagesForProjects($projectIds, $limitPerProject = 8)
{
    global $pdo;

    $projectIds = array_values(array_unique(array_filter(array_map('intval', $projectIds))));

    if (empty($projectIds)) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($projectIds), '?'));
    $stmt = $pdo->prepare("
        SELECT project_id, image
        FROM project_images
        WHERE project_id IN ($placeholders)
          AND image_type IN ('gallery', 'banner')
        ORDER BY project_id ASC, image_type = 'banner' DESC, sort_order ASC, id ASC
    ");
    $stmt->execute($projectIds);

    $imagesByProject = [];

    foreach ($stmt->fetchAll() as $image) {
        $projectId = (int)$image['project_id'];

        if (!isset($imagesByProject[$projectId])) {
            $imagesByProject[$projectId] = [];
        }

        if (count($imagesByProject[$projectId]) < $limitPerProject) {
            $imagesByProject[$projectId][] = getImageUrl($image['image']);
        }
    }

    return $imagesByProject;
}

function projectGalleryImages($project, $imagesByProject = [])
{
    $projectId = (int)($project['id'] ?? 0);
    $images = [projectImage($project)];

    foreach (($imagesByProject[$projectId] ?? []) as $image) {
        $images[] = $image;
    }

    return array_values(array_unique(array_filter($images)));
}

function projectPriceRange($project, $unitPlans = [])
{
    $prices = [];

    foreach ($unitPlans as $plan) {
        $price = (float)($plan['price'] ?? 0);
        if ($price > 0) {
            $prices[] = $price;
        }
    }

    if (empty($prices) && !empty($project['id'])) {
        global $pdo;
        $stmt = $pdo->prepare("
            SELECT MIN(price) AS min_price, MAX(price) AS max_price
            FROM project_unit_plans
            WHERE project_id = ? AND price > 0
        ");
        $stmt->execute([(int)$project['id']]);
        $row = $stmt->fetch();
        if ($row && (float)$row['min_price'] > 0) {
            $min = (float)$row['min_price'];
            $max = (float)$row['max_price'];
            if ($max > $min) {
                return formatCurrency($min) . ' - ' . formatCurrency($max);
            }
            return formatCurrency($min);
        }
        return 'Price on request';
    }

    if (!empty($prices)) {
        $min = min($prices);
        $max = max($prices);
        if ($max > $min) {
            return formatCurrency($min) . ' - ' . formatCurrency($max);
        }
        return formatCurrency($min);
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

function fetchHomepageProjectVideos($limit = 8)
{
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT v.*, p.project_name, p.slug, p.city, p.locality, b.company_name
        FROM project_videos v
        INNER JOIN projects p ON p.id = v.project_id
        INNER JOIN builders b ON b.id = p.builder_id
        WHERE p.status = 'published'
          AND p.deleted_at IS NULL
          AND v.video_url IS NOT NULL
          AND v.video_url <> ''
        ORDER BY p.is_featured DESC, v.created_at DESC, v.id DESC
        LIMIT " . (int)$limit
    );
    $stmt->execute();

    return $stmt->fetchAll();
}

function videoEmbedUrl($url)
{
    $url = trim((string)$url);

    if ($url === '') {
        return '';
    }

    if (preg_match('~(?:youtube\.com/watch\?v=|youtu\.be/|youtube\.com/shorts/|youtube\.com/embed/)([A-Za-z0-9_-]{6,})~', $url, $matches)) {
        return 'https://www.youtube.com/embed/' . $matches[1];
    }

    if (preg_match('~vimeo\.com/(?:video/)?(\d+)~', $url, $matches)) {
        return 'https://player.vimeo.com/video/' . $matches[1];
    }

    return preg_match('~^https?://~i', $url) ? $url : '';
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

function fetchPublishedProjects($filters = [], $limit = 12, $offset = 0)
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
        $where[] = 'EXISTS (
            SELECT 1 FROM project_unit_plans up
            WHERE up.project_id = p.id AND up.price > 0 AND up.price <= :budget_max
        )';
        $params[':budget_max'] = (float)$filters['budget_max'];
    }

    $sql = "
        SELECT p.*, b.company_name, b.builder_name, b.phone AS builder_phone,
               b.whatsapp_number, b.company_logo
        FROM projects p
        INNER JOIN builders b ON b.id = p.builder_id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY p.is_featured DESC, p.created_at DESC";

    if ($limit !== null) {
        $sql .= " LIMIT " . (int)$limit . " OFFSET " . max(0, (int)$offset);
    }

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

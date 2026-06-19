<?php
require_once __DIR__ . '/../includes/app_helpers.php';

requireLogin();

$errors = [];
$successMessage = '';

function profileDate($value, $fallback = 'Not available')
{
    if (empty($value)) {
        return $fallback;
    }

    $timestamp = strtotime($value);

    return $timestamp ? date('M j, Y', $timestamp) : $fallback;
}

function initialsFromName($name)
{
    $parts = preg_split('/\s+/', trim((string)$name));
    $initials = '';

    foreach ($parts as $part) {
        if ($part !== '') {
            $initials .= strtoupper(substr($part, 0, 1));
        }

        if (strlen($initials) >= 2) {
            break;
        }
    }

    return $initials ?: 'U';
}

function statusBadgeClass($status)
{
    $status = strtolower((string)$status);

    if (in_array($status, ['new', 'pending'], true)) {
        return 'bg-blue-50 text-blue-700 border-blue-100';
    }

    if (in_array($status, ['contacted', 'scheduled', 'site visit planned', 'qualified'], true)) {
        return 'bg-amber-50 text-amber-700 border-amber-100';
    }

    if (in_array($status, ['booked', 'visited', 'successful'], true)) {
        return 'bg-emerald-50 text-emerald-700 border-emerald-100';
    }

    if (in_array($status, ['lost', 'cancelled'], true)) {
        return 'bg-rose-50 text-rose-700 border-rose-100';
    }

    return 'bg-gray-50 text-gray-700 border-gray-100';
}

try {
    $stmt = $pdo->prepare("
        SELECT id, full_name, email, phone, profile_image, gender, dob, city, state,
               country, address, pincode, is_verified, marketing_emails, status,
               created_at, last_login_at
        FROM users
        WHERE id = ?
          AND status = 'active'
        LIMIT 1
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $currentUser = $stmt->fetch();

    if (!$currentUser) {
        setFlash('Account not found or inactive.', 'danger');
        redirect(BASE_URL . 'login');
    }
} catch (PDOException $e) {
    $errors[] = 'Failed to load profile. Please try again.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'update_profile';

    if ($action === 'update_profile') {
        $fullName = trim((string)($_POST['full_name'] ?? ''));
        $gender = trim((string)($_POST['gender'] ?? ''));
        $dob = trim((string)($_POST['dob'] ?? ''));
        $city = trim((string)($_POST['city'] ?? ''));
        $state = trim((string)($_POST['state'] ?? ''));
        $country = trim((string)($_POST['country'] ?? 'India'));
        $address = trim((string)($_POST['address'] ?? ''));
        $pincode = trim((string)($_POST['pincode'] ?? ''));
        $marketingEmails = isset($_POST['marketing_emails']) ? 1 : 0;

        if ($fullName === '') {
            $errors[] = 'Full name is required.';
        }

        if ($gender !== '' && !in_array($gender, ['male', 'female', 'other'], true)) {
            $errors[] = 'Please select a valid gender.';
        }

        if ($dob !== '' && strtotime($dob) === false) {
            $errors[] = 'Please enter a valid date of birth.';
        }

        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("
                    UPDATE users
                    SET full_name = ?,
                        gender = ?,
                        dob = ?,
                        city = ?,
                        state = ?,
                        country = ?,
                        address = ?,
                        pincode = ?,
                        marketing_emails = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $fullName,
                    $gender !== '' ? $gender : null,
                    $dob !== '' ? $dob : null,
                    $city !== '' ? $city : null,
                    $state !== '' ? $state : null,
                    $country !== '' ? $country : 'India',
                    $address !== '' ? $address : null,
                    $pincode !== '' ? $pincode : null,
                    $marketingEmails,
                    $_SESSION['user_id']
                ]);

                $_SESSION['user_name'] = $fullName;
                $successMessage = 'Profile updated successfully.';
            } catch (PDOException $e) {
                $errors[] = 'Failed to update profile. Please try again.';
            }
        }
    }

    if ($action === 'change_password') {
        $currentPassword = (string)($_POST['current_password'] ?? '');
        $newPassword = (string)($_POST['new_password'] ?? '');
        $confirmPassword = (string)($_POST['confirm_password'] ?? '');

        if ($currentPassword === '') {
            $errors[] = 'Current password is required.';
        }

        if (strlen($newPassword) < 6) {
            $errors[] = 'New password must be at least 6 characters.';
        }

        if ($newPassword !== $confirmPassword) {
            $errors[] = 'New password and confirmation do not match.';
        }

        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare('SELECT password FROM users WHERE id = ? LIMIT 1');
                $stmt->execute([$_SESSION['user_id']]);
                $passwordRow = $stmt->fetch();

                if (!$passwordRow || !password_verify($currentPassword, $passwordRow['password'])) {
                    $errors[] = 'Current password is incorrect.';
                } else {
                    $stmt = $pdo->prepare('UPDATE users SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE id = ?');
                    $stmt->execute([password_hash($newPassword, PASSWORD_DEFAULT), $_SESSION['user_id']]);
                    $successMessage = 'Password changed successfully.';
                }
            } catch (PDOException $e) {
                $errors[] = 'Failed to change password. Please try again.';
            }
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                SELECT id, full_name, email, phone, profile_image, gender, dob, city, state,
                       country, address, pincode, is_verified, marketing_emails, status,
                       created_at, last_login_at
                FROM users
                WHERE id = ?
                  AND status = 'active'
                LIMIT 1
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $currentUser = $stmt->fetch();
        } catch (PDOException $e) {
            $errors[] = 'Profile was saved, but the latest details could not be reloaded.';
        }
    }
}

$stats = [
    'wishlist' => 0,
    'inquiries' => 0,
    'visits' => 0
];
$wishlistProjects = [];
$wishlistProjectGalleries = [];
$inquiries = [];
$siteVisits = [];

try {
    $stats['wishlist'] = tableCount('wishlist', 'user_id = ?', [$_SESSION['user_id']]);
    $stats['inquiries'] = tableCount('inquiries', 'user_id = ?', [$_SESSION['user_id']]);
    $stats['visits'] = tableCount('site_visit_bookings', 'user_id = ?', [$_SESSION['user_id']]);

    $stmt = $pdo->prepare("
        SELECT p.*, b.company_name, w.created_at AS saved_at
        FROM wishlist w
        INNER JOIN projects p ON p.id = w.project_id
        INNER JOIN builders b ON b.id = p.builder_id
        WHERE w.user_id = ?
          AND p.status = 'published'
          AND p.deleted_at IS NULL
        ORDER BY w.created_at DESC
        LIMIT 4
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $wishlistProjects = $stmt->fetchAll();
    $wishlistProjectGalleries = fetchProjectGalleryImagesForProjects(array_column($wishlistProjects, 'id'), 4);

    $stmt = $pdo->prepare("
        SELECT i.inquiry_id, i.inquiry_status, i.budget, i.preferred_time, i.message,
               i.created_at, p.project_name, p.slug, p.city, p.locality, b.company_name
        FROM inquiries i
        INNER JOIN projects p ON p.id = i.project_id
        INNER JOIN builders b ON b.id = i.builder_id
        WHERE i.user_id = ?
        ORDER BY i.created_at DESC
        LIMIT 6
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $inquiries = $stmt->fetchAll();

    $stmt = $pdo->prepare("
        SELECT s.booking_id, s.status, s.visit_date, s.preferred_time, s.budget,
               s.created_at, p.project_name, p.slug, p.city, p.locality, b.company_name
        FROM site_visit_bookings s
        INNER JOIN projects p ON p.id = s.project_id
        INNER JOIN builders b ON b.id = s.builder_id
        WHERE s.user_id = ?
        ORDER BY COALESCE(s.visit_date, DATE(s.created_at)) DESC, s.created_at DESC
        LIMIT 6
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $siteVisits = $stmt->fetchAll();
} catch (PDOException $e) {
    $errors[] = 'Some account activity could not be loaded.';
}

$pageTitle = 'My Profile';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="mt-20 bg-gray-50 py-6 md:py-10">
    <div class="max-w-[1920px] mx-auto px-4 sm:px-6 lg:px-10">
        <div class="grid gap-5 lg:grid-cols-[320px_1fr]">

            <aside class="md:sticky md:top-30 self-start space-y-5">
                <div class="bg-white border border-gray-200 rounded-lg p-5 shadow-sm">
                    <div class="flex items-center gap-4">
                        <?php if (!empty($currentUser['profile_image'])): ?>
                            <img src="<?php echo e(getImageUrl($currentUser['profile_image'], 'uploads')); ?>" alt="<?php echo e($currentUser['full_name']); ?>" class="h-16 w-16 rounded-full object-cover">
                        <?php else: ?>
                            <div class="h-16 w-16 rounded-full bg-primary text-white flex items-center justify-center text-xl font-semibold">
                                <?php echo e(initialsFromName($currentUser['full_name'] ?? '')); ?>
                            </div>
                        <?php endif; ?>
                        <div class="min-w-0">
                            <h1 class="text-xl font-semibold text-primary truncate"><?php echo e($currentUser['full_name'] ?? 'User'); ?></h1>
                            <p class="text-sm text-gray-500">Member since <?php echo e(profileDate($currentUser['created_at'] ?? null)); ?></p>
                        </div>
                    </div>

                    <div class="mt-4 space-y-3 text-sm">
                        <div class="rounded-md bg-gray-50 border border-gray-200 p-3">
                            <div class="text-xs text-gray-500">Email</div>
                            <div class="mt-1 flex items-center justify-between gap-2">
                                <span class="truncate"><?php echo e($currentUser['email'] ?? ''); ?></span>
                                <i class="fa-solid fa-lock text-gray-400 text-xs"></i>
                            </div>
                        </div>
                        <div class="rounded-md bg-gray-50 border border-gray-200 p-3">
                            <div class="text-xs text-gray-500">Phone</div>
                            <div class="mt-1 flex items-center justify-between gap-2">
                                <span class="truncate"><?php echo e($currentUser['phone'] ?? ''); ?></span>
                                <i class="fa-solid fa-lock text-gray-400 text-xs"></i>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 grid grid-cols-3 gap-2 text-center">
                        <div class="rounded-lg border border-gray-200 p-3">
                            <div class="text-lg font-semibold text-primary"><?php echo (int)$stats['wishlist']; ?></div>
                            <div class="text-[11px] text-gray-500">Saved</div>
                        </div>
                        <div class="rounded-lg border border-gray-200 p-3">
                            <div class="text-lg font-semibold text-primary"><?php echo (int)$stats['inquiries']; ?></div>
                            <div class="text-[11px] text-gray-500">Inquired</div>
                        </div>
                        <div class="rounded-lg border border-gray-200 p-3">
                            <div class="text-lg font-semibold text-primary"><?php echo (int)$stats['visits']; ?></div>
                            <div class="text-[11px] text-gray-500">Visits</div>
                        </div>
                    </div>

                    <div class="mt-5 space-y-2 text-sm">
                        <a href="#edit-profile" data-open-profile-edit class="flex items-center justify-between rounded-md border border-gray-200 px-3 py-3 hover:border-accent hover:bg-amber-50/40">
                            <span><i class="fa-solid fa-pen-to-square mr-2 text-accent"></i>Edit profile</span>
                            <i class="fa-solid fa-chevron-right text-xs text-gray-400"></i>
                        </a>
                        <a href="#security" class="flex items-center justify-between rounded-md border border-gray-200 px-3 py-3 hover:border-accent hover:bg-amber-50/40">
                            <span><i class="fa-solid fa-key mr-2 text-accent"></i>Change password</span>
                            <i class="fa-solid fa-chevron-right text-xs text-gray-400"></i>
                        </a>
                        <a href="<?php echo BASE_URL; ?>wishlist" class="flex items-center justify-between rounded-md border border-gray-200 px-3 py-3 hover:border-accent hover:bg-amber-50/40">
                            <span><i class="fa-solid fa-heart mr-2 text-accent"></i>Wishlist</span>
                            <i class="fa-solid fa-chevron-right text-xs text-gray-400"></i>
                        </a>
                        <a href="<?php echo BASE_URL; ?>logout" class="flex items-center justify-between rounded-md border border-rose-100 px-3 py-3 text-rose-600 hover:bg-rose-50">
                            <span><i class="fa-solid fa-right-from-bracket mr-2"></i>Logout</span>
                            <i class="fa-solid fa-chevron-right text-xs"></i>
                        </a>
                    </div>
                </div>
            </aside>

            <main class="space-y-5">
                <?php if ($successMessage): ?>
                    <div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                        <?php echo e($successMessage); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="rounded-md border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                        <ul class="list-disc list-inside space-y-1">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo e($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div id="edit-profile" class="bg-white border border-gray-200 rounded-lg shadow-sm">
                    <div class="flex flex-col gap-3 border-b border-gray-200 p-5 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-accent">Profile</p>
                            <h2 class="mt-1 text-xl font-semibold text-primary">Personal details</h2>
                        </div>
                        <button type="button" data-toggle-profile-edit class="inline-flex items-center justify-center gap-2 rounded-md bg-primary px-4 py-2.5 text-sm font-semibold text-white">
                            <i class="fa-solid fa-pen-to-square"></i>
                            Edit
                        </button>
                    </div>

                    <div data-profile-view class="p-5">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="rounded-md border border-gray-200 p-4">
                                <div class="text-xs text-gray-500">Full name</div>
                                <div class="mt-1 font-semibold text-primary"><?php echo e($currentUser['full_name'] ?? ''); ?></div>
                            </div>
                            <div class="rounded-md border border-gray-200 p-4">
                                <div class="text-xs text-gray-500">Gender</div>
                                <div class="mt-1 font-semibold text-primary"><?php echo e($currentUser['gender'] ? ucfirst($currentUser['gender']) : 'Not added'); ?></div>
                            </div>
                            <div class="rounded-md border border-gray-200 p-4">
                                <div class="text-xs text-gray-500">Date of birth</div>
                                <div class="mt-1 font-semibold text-primary"><?php echo e(profileDate($currentUser['dob'] ?? null, 'Not added')); ?></div>
                            </div>
                            <div class="rounded-md border border-gray-200 p-4">
                                <div class="text-xs text-gray-500">Location</div>
                                <div class="mt-1 font-semibold text-primary">
                                    <?php
                                    $location = array_filter([
                                        $currentUser['city'] ?? '',
                                        $currentUser['state'] ?? '',
                                        $currentUser['country'] ?? ''
                                    ]);
                                    echo e($location ? implode(', ', $location) : 'Not added');
                                    ?>
                                </div>
                            </div>
                            <div class="rounded-md border border-gray-200 p-4 md:col-span-2">
                                <div class="text-xs text-gray-500">Address</div>
                                <div class="mt-1 font-semibold text-primary"><?php echo e($currentUser['address'] ?: 'Not added'); ?></div>
                                <?php if (!empty($currentUser['pincode'])): ?>
                                    <div class="mt-1 text-sm text-gray-500">Pincode: <?php echo e($currentUser['pincode']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <form method="POST" data-profile-form class="hidden p-5">
                        <input type="hidden" name="action" value="update_profile">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                                <input type="text" name="full_name" required value="<?php echo e($currentUser['full_name'] ?? ''); ?>" class="w-full rounded-md border border-gray-300 px-4 py-3 text-sm outline-none focus:border-accent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                                <select name="gender" class="w-full rounded-md border border-gray-300 px-4 py-3 text-sm outline-none focus:border-accent">
                                    <option value="">Select gender</option>
                                    <option value="male" <?php echo ($currentUser['gender'] ?? '') === 'male' ? 'selected' : ''; ?>>Male</option>
                                    <option value="female" <?php echo ($currentUser['gender'] ?? '') === 'female' ? 'selected' : ''; ?>>Female</option>
                                    <option value="other" <?php echo ($currentUser['gender'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Date of Birth</label>
                                <input type="date" name="dob" value="<?php echo e($currentUser['dob'] ?? ''); ?>" class="w-full rounded-md border border-gray-300 px-4 py-3 text-sm outline-none focus:border-accent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">City</label>
                                <input type="text" name="city" value="<?php echo e($currentUser['city'] ?? ''); ?>" class="w-full rounded-md border border-gray-300 px-4 py-3 text-sm outline-none focus:border-accent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">State</label>
                                <input type="text" name="state" value="<?php echo e($currentUser['state'] ?? ''); ?>" class="w-full rounded-md border border-gray-300 px-4 py-3 text-sm outline-none focus:border-accent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Country</label>
                                <input type="text" name="country" value="<?php echo e($currentUser['country'] ?? 'India'); ?>" class="w-full rounded-md border border-gray-300 px-4 py-3 text-sm outline-none focus:border-accent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Pincode</label>
                                <input type="text" name="pincode" value="<?php echo e($currentUser['pincode'] ?? ''); ?>" class="w-full rounded-md border border-gray-300 px-4 py-3 text-sm outline-none focus:border-accent">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                                <textarea name="address" rows="3" class="w-full rounded-md border border-gray-300 px-4 py-3 text-sm outline-none focus:border-accent"><?php echo e($currentUser['address'] ?? ''); ?></textarea>
                            </div>
                            <div class="md:col-span-2">
                                <label class="inline-flex items-center gap-3 text-sm text-gray-700">
                                    <input type="checkbox" name="marketing_emails" value="1" class="h-4 w-4 rounded border-gray-300 text-accent" <?php echo (int)($currentUser['marketing_emails'] ?? 1) === 1 ? 'checked' : ''; ?>>
                                    Send me project updates and recommendations
                                </label>
                            </div>
                        </div>
                        <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:justify-end">
                            <button type="button" data-cancel-profile-edit class="rounded-md border border-gray-300 px-5 py-3 text-sm font-semibold text-gray-700">Cancel</button>
                            <button type="submit" class="rounded-md bg-accent px-5 py-3 text-sm font-semibold text-white">Save Changes</button>
                        </div>
                    </form>
                </div>

                <div id="security" class="bg-white border border-gray-200 rounded-lg p-5 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-lg bg-gray-100 flex items-center justify-center text-primary">
                            <i class="fa-solid fa-key"></i>
                        </div>
                        <div>
                            <h2 class="text-xl font-semibold text-primary">Change password</h2>
                            <p class="text-sm text-gray-500">Use your current password to set a new one.</p>
                        </div>
                    </div>

                    <form method="POST" class="mt-5 grid gap-4 md:grid-cols-3">
                        <input type="hidden" name="action" value="change_password">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                            <input type="password" name="current_password" required class="w-full rounded-md border border-gray-300 px-4 py-3 text-sm outline-none focus:border-accent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                            <input type="password" name="new_password" required minlength="6" class="w-full rounded-md border border-gray-300 px-4 py-3 text-sm outline-none focus:border-accent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                            <input type="password" name="confirm_password" required minlength="6" class="w-full rounded-md border border-gray-300 px-4 py-3 text-sm outline-none focus:border-accent">
                        </div>
                        <div class="md:col-span-3">
                            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-md bg-primary px-5 py-3 text-sm font-semibold text-white">
                                <i class="fa-solid fa-shield-halved"></i>
                                Update Password
                            </button>
                        </div>
                    </form>
                </div>

                <div class="grid gap-5 xl:grid-cols-2">
                    <section class="bg-white border border-gray-200 rounded-lg p-5 shadow-sm">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <h2 class="text-xl font-semibold text-primary">Wishlist</h2>
                                <p class="text-sm text-gray-500">Recently saved projects</p>
                            </div>
                            <a href="<?php echo BASE_URL; ?>wishlist" class="text-sm font-semibold text-accent">View all</a>
                        </div>

                        <?php if (empty($wishlistProjects)): ?>
                            <div class="mt-5 rounded-md border border-dashed border-gray-300 p-6 text-center">
                                <i class="fa-regular fa-heart text-3xl text-gray-400"></i>
                                <p class="mt-2 text-sm font-semibold text-primary">No saved projects yet</p>
                                <a href="<?php echo BASE_URL; ?>projects" class="mt-3 inline-flex rounded-md bg-primary px-4 py-2 text-sm text-white">Browse Projects</a>
                            </div>
                        <?php else: ?>
                            <div class="mt-5 space-y-3">
                                <?php foreach ($wishlistProjects as $project): ?>
                                    <?php $galleryImages = projectGalleryImages($project, $wishlistProjectGalleries ?? []); ?>
                                    <a href="<?php echo BASE_URL . 'project/' . urlencode($project['slug']); ?>" class="flex gap-3 rounded-md border border-gray-200 p-3 hover:border-accent">
                                        <span class="relative block h-20 w-24 shrink-0 overflow-hidden rounded-md" data-project-gallery>
                                            <?php foreach ($galleryImages as $imageIndex => $imageUrl): ?>
                                                <img src="<?php echo e($imageUrl); ?>" alt="<?php echo e($project['project_name']); ?>" class="h-20 w-24 object-cover <?php echo $imageIndex === 0 ? '' : 'hidden'; ?>" data-gallery-image>
                                            <?php endforeach; ?>
                                            <?php if (count($galleryImages) > 1): ?>
                                                <button type="button" data-gallery-prev onclick="event.preventDefault(); event.stopPropagation();" class="absolute left-1 top-1/2 z-20 flex h-5 w-5 -translate-y-1/2 items-center justify-center rounded-full bg-black/45 text-white" aria-label="Previous image">
                                                    <i class="fa-solid fa-chevron-left text-[8px]"></i>
                                                </button>
                                                <button type="button" data-gallery-next onclick="event.preventDefault(); event.stopPropagation();" class="absolute right-1 top-1/2 z-20 flex h-5 w-5 -translate-y-1/2 items-center justify-center rounded-full bg-black/45 text-white" aria-label="Next image">
                                                    <i class="fa-solid fa-chevron-right text-[8px]"></i>
                                                </button>
                                                <span class="absolute bottom-1 left-1/2 z-20 flex -translate-x-1/2 gap-1">
                                                    <?php foreach ($galleryImages as $imageIndex => $imageUrl): ?>
                                                        <button type="button" data-gallery-dot="<?php echo (int)$imageIndex; ?>" onclick="event.preventDefault(); event.stopPropagation();" class="h-1 rounded-full bg-white/70 transition-all <?php echo $imageIndex === 0 ? 'w-3' : 'w-1'; ?>" aria-label="Show image <?php echo (int)$imageIndex + 1; ?>"></button>
                                                    <?php endforeach; ?>
                                                </span>
                                            <?php endif; ?>
                                        </span>
                                        <div class="min-w-0 flex-1">
                                            <h3 class="font-semibold text-primary truncate"><?php echo e($project['project_name']); ?></h3>
                                            <p class="mt-1 text-xs text-gray-500 truncate"><?php echo e($project['company_name']); ?></p>
                                            <div class="flex justify-between gap-2 mt-2">
                                                <p class=" text-xs text-gray-500"><i class="fa-solid fa-location-dot text-orange-500 mr-1"></i><?php echo e(trim(($project['locality'] ? $project['locality'] . ', ' : '') . $project['city'])); ?></p>
                                                <p class="text-sm font-semibold text-emerald-600"><?php echo e(projectPriceRange($project)); ?></p>
                                            </div>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </section>

                    <section class="bg-white border border-gray-200 rounded-lg p-5 shadow-sm">
                        <div>
                            <h2 class="text-xl font-semibold text-primary">Inquired projects</h2>
                            <p class="text-sm text-gray-500">Projects where you requested details</p>
                        </div>

                        <?php if (empty($inquiries)): ?>
                            <div class="mt-5 rounded-md border border-dashed border-gray-300 p-6 text-center">
                                <i class="fa-regular fa-message text-3xl text-gray-400"></i>
                                <p class="mt-2 text-sm font-semibold text-primary">No inquiries yet</p>
                            </div>
                        <?php else: ?>
                            <div class="mt-5 space-y-3">
                                <?php foreach ($inquiries as $inquiry): ?>
                                    <div class="rounded-md border border-gray-200 p-4">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <a href="<?php echo BASE_URL . 'project/' . urlencode($inquiry['slug']); ?>" class="font-semibold text-primary hover:text-accent"><?php echo e($inquiry['project_name']); ?></a>
                                                <p class="mt-1 text-xs text-gray-500"><?php echo e($inquiry['company_name']); ?> · <?php echo e(profileDate($inquiry['created_at'])); ?></p>
                                            </div>
                                            <span class="shrink-0 rounded-full border px-2.5 py-1 text-[11px] font-semibold <?php echo statusBadgeClass($inquiry['inquiry_status']); ?>">
                                                <?php echo e($inquiry['inquiry_status']); ?>
                                            </span>
                                        </div>
                                        <?php if (!empty($inquiry['budget']) || !empty($inquiry['preferred_time'])): ?>
                                            <div class="mt-3 flex flex-wrap gap-2 text-xs text-gray-600">
                                                <?php if (!empty($inquiry['budget'])): ?>
                                                    <span class="rounded-full bg-gray-100 px-3 py-1">Budget: <?php echo e($inquiry['budget']); ?></span>
                                                <?php endif; ?>
                                                <?php if (!empty($inquiry['preferred_time'])): ?>
                                                    <span class="rounded-full bg-gray-100 px-3 py-1">Preferred: <?php echo e($inquiry['preferred_time']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </section>
                </div>

                <section class="bg-white border border-gray-200 rounded-lg p-5 shadow-sm">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h2 class="text-xl font-semibold text-primary">Site visits</h2>
                            <p class="text-sm text-gray-500">Your booked or requested project visits</p>
                        </div>
                        <a href="<?php echo BASE_URL; ?>projects" class="hidden sm:inline-flex rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700">Explore more</a>
                    </div>

                    <?php if (empty($siteVisits)): ?>
                        <div class="mt-5 rounded-md border border-dashed border-gray-300 p-6 text-center">
                            <i class="fa-regular fa-calendar-check text-3xl text-gray-400"></i>
                            <p class="mt-2 text-sm font-semibold text-primary">No site visits booked</p>
                        </div>
                    <?php else: ?>
                        <div class="mt-5 overflow-x-auto">
                            <table class="w-full min-w-[700px] text-left text-sm">
                                <thead class="border-b border-gray-200 text-xs uppercase text-gray-500">
                                    <tr>
                                        <th class="py-3 pr-4 font-semibold">Project</th>
                                        <th class="py-3 pr-4 font-semibold">Visit Date</th>
                                        <th class="py-3 pr-4 font-semibold">Preferred Time</th>
                                        <th class="py-3 pr-4 font-semibold">Status</th>
                                        <th class="py-3 font-semibold">Booked On</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <?php foreach ($siteVisits as $visit): ?>
                                        <tr>
                                            <td class="py-3 pr-4">
                                                <a href="<?php echo BASE_URL . 'project/' . urlencode($visit['slug']); ?>" class="font-semibold text-primary hover:text-accent"><?php echo e($visit['project_name']); ?></a>
                                                <div class="text-xs text-gray-500"><?php echo e($visit['company_name']); ?></div>
                                            </td>
                                            <td class="py-3 pr-4 text-gray-700"><?php echo e(profileDate($visit['visit_date'] ?? null, 'Not scheduled')); ?></td>
                                            <td class="py-3 pr-4 text-gray-700"><?php echo e($visit['preferred_time'] ?: 'Any time'); ?></td>
                                            <td class="py-3 pr-4">
                                                <span class="rounded-full border px-2.5 py-1 text-[11px] font-semibold <?php echo statusBadgeClass($visit['status']); ?>">
                                                    <?php echo e($visit['status']); ?>
                                                </span>
                                            </td>
                                            <td class="py-3 text-gray-700"><?php echo e(profileDate($visit['created_at'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </section>
            </main>
        </div>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const view = document.querySelector('[data-profile-view]');
        const form = document.querySelector('[data-profile-form]');
        const openButtons = document.querySelectorAll('[data-toggle-profile-edit], [data-open-profile-edit]');
        const cancelButton = document.querySelector('[data-cancel-profile-edit]');

        function showEdit() {
            if (!view || !form) {
                return;
            }

            view.classList.add('hidden');
            form.classList.remove('hidden');
        }

        function hideEdit() {
            if (!view || !form) {
                return;
            }

            form.classList.add('hidden');
            view.classList.remove('hidden');
        }

        openButtons.forEach(function(button) {
            button.addEventListener('click', showEdit);
        });

        if (cancelButton) {
            cancelButton.addEventListener('click', hideEdit);
        }
    });
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';

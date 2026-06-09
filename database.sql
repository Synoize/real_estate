-- -------------------------------------------------------------
-- REAL ESTATE CRM + PROPERTY LISTING PLATFORM
-- ENTERPRISE PRODUCTION DATABASE STRUCTURE
-- TECHNOLOGY : PHP + MYSQL
-- ENGINE     : INNODB
-- CHARSET    : UTF8MB4
-- -------------------------------------------------------------

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

CREATE DATABASE IF NOT EXISTS real_estate_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE real_estate_db;

-- -------------------------------------------------------------
-- USERS
-- -------------------------------------------------------------

CREATE TABLE users (

    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    uuid CHAR(36) NOT NULL UNIQUE,

    full_name VARCHAR(150) NOT NULL,

    email VARCHAR(150) NOT NULL UNIQUE,

    phone VARCHAR(20) NOT NULL UNIQUE,

    password VARCHAR(255) NOT NULL,

    profile_image VARCHAR(255) DEFAULT NULL,

    gender ENUM('male','female','other') DEFAULT NULL,

    dob DATE DEFAULT NULL,

    city VARCHAR(100) DEFAULT NULL,

    state VARCHAR(100) DEFAULT NULL,

    country VARCHAR(100) DEFAULT 'India',

    address TEXT DEFAULT NULL,

    pincode VARCHAR(20) DEFAULT NULL,

    email_verified_at TIMESTAMP NULL DEFAULT NULL,

    phone_verified_at TIMESTAMP NULL DEFAULT NULL,

    auth_token TEXT DEFAULT NULL,

    remember_token VARCHAR(255) DEFAULT NULL,

    reset_token VARCHAR(255) DEFAULT NULL,

    reset_token_expiry DATETIME DEFAULT NULL,

    last_login_at DATETIME DEFAULT NULL,

    last_login_ip VARCHAR(100) DEFAULT NULL,

    login_device TEXT DEFAULT NULL,

    is_verified TINYINT(1) DEFAULT 0,

    is_blocked TINYINT(1) DEFAULT 0,

    terms_accepted TINYINT(1) DEFAULT 0,

    privacy_accepted TINYINT(1) DEFAULT 0,

    marketing_emails TINYINT(1) DEFAULT 1,

    status ENUM(
        'active',
        'inactive',
        'blocked',
        'deleted'
    ) DEFAULT 'active',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP,

    deleted_at TIMESTAMP NULL DEFAULT NULL,

    INDEX idx_users_email(email),
    INDEX idx_users_phone(phone),
    INDEX idx_users_city(city),
    INDEX idx_users_status(status)

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- ADMINS
-- -------------------------------------------------------------

CREATE TABLE admins (

    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    uuid CHAR(36) NOT NULL UNIQUE,

    full_name VARCHAR(150) NOT NULL,

    email VARCHAR(150) NOT NULL UNIQUE,

    password VARCHAR(255) NOT NULL,

    profile_image VARCHAR(255) DEFAULT NULL,

    auth_token TEXT DEFAULT NULL,

    remember_token VARCHAR(255) DEFAULT NULL,

    reset_token VARCHAR(255) DEFAULT NULL,

    reset_token_expiry DATETIME DEFAULT NULL,

    last_login_at DATETIME DEFAULT NULL,

    last_login_ip VARCHAR(100) DEFAULT NULL,

    role ENUM(
        'super_admin',
        'admin'
    ) DEFAULT 'admin',

    status ENUM(
        'active',
        'inactive'
    ) DEFAULT 'active',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------------
-- EMPLOYEES
-- -------------------------------------------------------------

CREATE TABLE employees (

    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    uuid CHAR(36) NOT NULL UNIQUE,

    employee_code VARCHAR(50) NOT NULL UNIQUE,

    full_name VARCHAR(150) NOT NULL,

    email VARCHAR(150) NOT NULL UNIQUE,

    phone VARCHAR(20) DEFAULT NULL,

    password VARCHAR(255) NOT NULL,

    department VARCHAR(100) DEFAULT NULL,

    designation VARCHAR(100) DEFAULT NULL,

    profile_image VARCHAR(255) DEFAULT NULL,

    auth_token TEXT DEFAULT NULL,

    remember_token VARCHAR(255) DEFAULT NULL,

    reset_token VARCHAR(255) DEFAULT NULL,

    reset_token_expiry DATETIME DEFAULT NULL,

    last_login_at DATETIME DEFAULT NULL,

    last_login_ip VARCHAR(100) DEFAULT NULL,

    status ENUM(
        'active',
        'inactive',
        'blocked'
    ) DEFAULT 'active',

    created_by BIGINT UNSIGNED DEFAULT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (created_by)
    REFERENCES admins(id)
    ON DELETE SET NULL,

    INDEX idx_employee_code(employee_code),
    INDEX idx_employee_email(email)

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------------
-- BUILDERS
-- -------------------------------------------------------------

CREATE TABLE builders (

    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    uuid CHAR(36) NOT NULL UNIQUE,

    company_name VARCHAR(255) NOT NULL,

    company_slug VARCHAR(255) NOT NULL UNIQUE,

    builder_name VARCHAR(150) NOT NULL,

    email VARCHAR(150) NOT NULL UNIQUE,

    phone VARCHAR(20) NOT NULL UNIQUE,

    alternate_phone VARCHAR(20) DEFAULT NULL,

    whatsapp_number VARCHAR(20) DEFAULT NULL,

    password VARCHAR(255) NOT NULL,

    company_logo VARCHAR(255) DEFAULT NULL,

    company_banner VARCHAR(255) DEFAULT NULL,

    rera_number VARCHAR(100) DEFAULT NULL,

    gst_number VARCHAR(100) DEFAULT NULL,

    website VARCHAR(255) DEFAULT NULL,

    company_description LONGTEXT DEFAULT NULL,

    address TEXT DEFAULT NULL,

    city VARCHAR(100) DEFAULT NULL,

    state VARCHAR(100) DEFAULT NULL,

    country VARCHAR(100) DEFAULT 'India',

    pincode VARCHAR(20) DEFAULT NULL,

    latitude DECIMAL(10,8) DEFAULT NULL,

    longitude DECIMAL(11,8) DEFAULT NULL,

    auth_token TEXT DEFAULT NULL,

    remember_token VARCHAR(255) DEFAULT NULL,

    reset_token VARCHAR(255) DEFAULT NULL,

    reset_token_expiry DATETIME DEFAULT NULL,

    last_login_at DATETIME DEFAULT NULL,

    last_login_ip VARCHAR(100) DEFAULT NULL,

    email_verified_at TIMESTAMP NULL DEFAULT NULL,

    is_verified TINYINT(1) DEFAULT 0,

    total_projects INT DEFAULT 0,

    total_leads INT DEFAULT 0,

    total_visits INT DEFAULT 0,

    status ENUM(
        'pending',
        'active',
        'inactive',
        'blocked'
    ) DEFAULT 'pending',

    created_by_employee BIGINT UNSIGNED DEFAULT NULL,

    approved_by_admin BIGINT UNSIGNED DEFAULT NULL,

    approved_at DATETIME DEFAULT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP,

    deleted_at TIMESTAMP NULL DEFAULT NULL,

    FOREIGN KEY (created_by_employee)
    REFERENCES employees(id)
    ON DELETE SET NULL,

    FOREIGN KEY (approved_by_admin)
    REFERENCES admins(id)
    ON DELETE SET NULL,

    INDEX idx_builder_company(company_name),
    INDEX idx_builder_city(city),
    INDEX idx_builder_status(status)

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------------
-- ASSOCIATE MANAGERS
-- -------------------------------------------------------------

CREATE TABLE associate_managers (

    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    uuid CHAR(36) NOT NULL UNIQUE,

    builder_id BIGINT UNSIGNED NOT NULL,

    manager_code VARCHAR(50) NOT NULL UNIQUE,

    full_name VARCHAR(150) NOT NULL,

    email VARCHAR(150) UNIQUE,

    phone VARCHAR(20),

    whatsapp_number VARCHAR(20) DEFAULT NULL,

    password VARCHAR(255) NOT NULL,

    profile_image VARCHAR(255) DEFAULT NULL,

    designation VARCHAR(100) DEFAULT 'Associate Manager',

    auth_token TEXT DEFAULT NULL,

    remember_token VARCHAR(255) DEFAULT NULL,

    reset_token VARCHAR(255) DEFAULT NULL,

    reset_token_expiry DATETIME DEFAULT NULL,

    last_login_at DATETIME DEFAULT NULL,

    last_login_ip VARCHAR(100) DEFAULT NULL,

    total_assigned_projects INT DEFAULT 0,

    total_leads INT DEFAULT 0,

    successful_visits INT DEFAULT 0,

    status ENUM(
        'active',
        'inactive',
        'blocked'
    ) DEFAULT 'active',

    created_by BIGINT UNSIGNED DEFAULT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (builder_id)
    REFERENCES builders(id)
    ON DELETE CASCADE,

    FOREIGN KEY (created_by)
    REFERENCES builders(id)
    ON DELETE SET NULL,

    INDEX idx_manager_builder(builder_id),
    INDEX idx_manager_email(email)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------------
-- PROJECT CATEGORIES
-- -------------------------------------------------------------

CREATE TABLE project_categories (

    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    category_name VARCHAR(100) NOT NULL UNIQUE,

    category_slug VARCHAR(100) NOT NULL UNIQUE,

    category_icon VARCHAR(255) DEFAULT NULL,

    status ENUM('active','inactive') DEFAULT 'active',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------------
-- PROJECTS
-- -------------------------------------------------------------

CREATE TABLE projects (

    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    uuid CHAR(36) NOT NULL UNIQUE,

    builder_id BIGINT UNSIGNED NOT NULL,

    assigned_manager_id BIGINT UNSIGNED DEFAULT NULL,

    category_id BIGINT UNSIGNED DEFAULT NULL,

    project_type ENUM(
        'Apartment',
        'Plot',
        'Villa',
        'Commercial'
    ) NOT NULL,

    project_name VARCHAR(255) NOT NULL,

    slug VARCHAR(255) NOT NULL UNIQUE,

    project_code VARCHAR(100) DEFAULT NULL,

    rera_number VARCHAR(100) DEFAULT NULL,

    city VARCHAR(100) NOT NULL,

    state VARCHAR(100) NOT NULL,

    locality VARCHAR(150) DEFAULT NULL,

    address TEXT DEFAULT NULL,

    latitude DECIMAL(10,8) DEFAULT NULL,

    longitude DECIMAL(11,8) DEFAULT NULL,

    overview LONGTEXT DEFAULT NULL,

    location_details LONGTEXT DEFAULT NULL,

    pros LONGTEXT DEFAULT NULL,

    cons LONGTEXT DEFAULT NULL,

    amenities LONGTEXT DEFAULT NULL,

    legal_details LONGTEXT DEFAULT NULL,

    litigation_details LONGTEXT DEFAULT NULL,

    bank_details LONGTEXT DEFAULT NULL,

    payment_scheme LONGTEXT DEFAULT NULL,

    brochure_file VARCHAR(255) DEFAULT NULL,

    thumbnail_image VARCHAR(255) DEFAULT NULL,

    featured_image VARCHAR(255) DEFAULT NULL,

    youtube_video_link TEXT DEFAULT NULL,

    total_towers INT DEFAULT 0,

    total_units INT DEFAULT 0,

    total_floors INT DEFAULT 0,

    total_area VARCHAR(100) DEFAULT NULL,

    possession_date DATE DEFAULT NULL,

    launch_date DATE DEFAULT NULL,

    min_price DECIMAL(15,2) DEFAULT 0,

    max_price DECIMAL(15,2) DEFAULT 0,

    meta_title VARCHAR(255) DEFAULT NULL,

    meta_keywords TEXT DEFAULT NULL,

    meta_description TEXT DEFAULT NULL,

    total_views BIGINT DEFAULT 0,

    total_wishlist BIGINT DEFAULT 0,

    total_inquiries BIGINT DEFAULT 0,

    is_featured TINYINT(1) DEFAULT 0,

    is_verified TINYINT(1) DEFAULT 0,

    project_status ENUM(
        'Upcoming',
        'Ongoing',
        'Completed'
    ) DEFAULT 'Upcoming',

    status ENUM(
        'draft',
        'pending',
        'published',
        'rejected'
    ) DEFAULT 'draft',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP,

    deleted_at TIMESTAMP NULL DEFAULT NULL,

    FOREIGN KEY (builder_id)
    REFERENCES builders(id)
    ON DELETE CASCADE,

    FOREIGN KEY (assigned_manager_id)
    REFERENCES associate_managers(id)
    ON DELETE SET NULL,

    FOREIGN KEY (category_id)
    REFERENCES project_categories(id)
    ON DELETE SET NULL,

    INDEX idx_project_city(city),
    INDEX idx_project_status(status),
    INDEX idx_project_type(project_type),
    INDEX idx_project_price(min_price, max_price),
    FULLTEXT(project_name, overview)

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------------
-- PROJECT IMAGES
-- -------------------------------------------------------------

CREATE TABLE project_images (

    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    project_id BIGINT UNSIGNED NOT NULL,

    image VARCHAR(255) NOT NULL,

    image_type ENUM(
        'gallery',
        'banner',
        'floor_plan',
        'master_plan'
    ) DEFAULT 'gallery',

    sort_order INT DEFAULT 0,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_project_images_project_type(project_id, image_type, sort_order),

    FOREIGN KEY (project_id)
    REFERENCES projects(id)
    ON DELETE CASCADE

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------------
-- PROJECT VIDEOS
-- -------------------------------------------------------------

CREATE TABLE project_videos (

    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    project_id BIGINT UNSIGNED NOT NULL,

    video_title VARCHAR(255) DEFAULT NULL,

    video_url TEXT NOT NULL,

    thumbnail VARCHAR(255) DEFAULT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_project_videos_project(project_id),

    FOREIGN KEY (project_id)
    REFERENCES projects(id)
    ON DELETE CASCADE

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------------
-- PROJECT AMENITIES
-- -------------------------------------------------------------

CREATE TABLE project_amenities (

    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    project_id BIGINT UNSIGNED NOT NULL,

    amenity_name VARCHAR(255) NOT NULL,

    amenity_icon VARCHAR(255) DEFAULT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_project_amenities_project(project_id),

    FOREIGN KEY (project_id)
    REFERENCES projects(id)
    ON DELETE CASCADE

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------------
-- PROJECT UNIT PLANS
-- -------------------------------------------------------------

CREATE TABLE project_unit_plans (

    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    project_id BIGINT UNSIGNED NOT NULL,

    unit_name VARCHAR(255),

    bhk_type VARCHAR(100),

    area VARCHAR(100),

    facing VARCHAR(100),

    price DECIMAL(15,2),

    booking_amount DECIMAL(15,2) DEFAULT 0,

    description TEXT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_unit_plans_project_price(project_id, price),
    INDEX idx_unit_plans_bhk(bhk_type),

    FOREIGN KEY (project_id)
    REFERENCES projects(id)
    ON DELETE CASCADE

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------------
-- USER WISHLIST
-- -------------------------------------------------------------

CREATE TABLE wishlist (

    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    user_id BIGINT UNSIGNED NOT NULL,

    project_id BIGINT UNSIGNED NOT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY unique_wishlist(user_id, project_id),

    FOREIGN KEY (user_id)
    REFERENCES users(id)
    ON DELETE CASCADE,

    FOREIGN KEY (project_id)
    REFERENCES projects(id)
    ON DELETE CASCADE

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------------
-- SITE VISIT BOOKINGS
-- -------------------------------------------------------------

CREATE TABLE site_visit_bookings (

    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    booking_id VARCHAR(100) NOT NULL UNIQUE,

    user_id BIGINT UNSIGNED NOT NULL,

    builder_id BIGINT UNSIGNED NOT NULL,

    project_id BIGINT UNSIGNED NOT NULL,

    assigned_manager_id BIGINT UNSIGNED DEFAULT NULL,

    full_name VARCHAR(150),

    email VARCHAR(150),

    phone VARCHAR(20),

    preferred_time VARCHAR(100),

    budget VARCHAR(100),

    visit_date DATE,

    message TEXT,

    source ENUM(
        'website',
        'whatsapp',
        'call',
        'facebook',
        'google_ads'
    ) DEFAULT 'website',

    status ENUM(
        'Pending',
        'Contacted',
        'Scheduled',
        'Visited',
        'Successful',
        'Cancelled'
    ) DEFAULT 'Pending',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id)
    REFERENCES users(id)
    ON DELETE CASCADE,

    FOREIGN KEY (builder_id)
    REFERENCES builders(id)
    ON DELETE CASCADE,

    FOREIGN KEY (project_id)
    REFERENCES projects(id)
    ON DELETE CASCADE,

    FOREIGN KEY (assigned_manager_id)
    REFERENCES associate_managers(id)
    ON DELETE SET NULL

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------------
-- INQUIRIES / LEADS
-- -------------------------------------------------------------

CREATE TABLE inquiries (

    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    inquiry_id VARCHAR(100) NOT NULL UNIQUE,

    user_id BIGINT UNSIGNED DEFAULT NULL,

    builder_id BIGINT UNSIGNED NOT NULL,

    project_id BIGINT UNSIGNED NOT NULL,

    assigned_manager_id BIGINT UNSIGNED DEFAULT NULL,

    full_name VARCHAR(150) NOT NULL,

    email VARCHAR(150) NOT NULL,

    phone VARCHAR(20) NOT NULL,

    budget VARCHAR(100) DEFAULT NULL,

    preferred_time VARCHAR(100) DEFAULT NULL,

    message TEXT DEFAULT NULL,

    source ENUM(
        'website',
        'whatsapp',
        'call',
        'facebook',
        'google_ads'
    ) DEFAULT 'website',

    inquiry_status ENUM(
        'New',
        'Contacted',
        'Qualified',
        'Site Visit Planned',
        'Booked',
        'Lost'
    ) DEFAULT 'New',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id)
    REFERENCES users(id)
    ON DELETE SET NULL,

    FOREIGN KEY (builder_id)
    REFERENCES builders(id)
    ON DELETE CASCADE,

    FOREIGN KEY (project_id)
    REFERENCES projects(id)
    ON DELETE CASCADE,

    FOREIGN KEY (assigned_manager_id)
    REFERENCES associate_managers(id)
    ON DELETE SET NULL,

    INDEX idx_inquiry_project(project_id),
    INDEX idx_inquiry_status(inquiry_status),
    INDEX idx_inquiry_created(created_at)

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------------
-- ACTIVITY LOGS
-- -------------------------------------------------------------

CREATE TABLE activity_logs (

    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    user_type VARCHAR(50) NOT NULL,

    user_id BIGINT UNSIGNED DEFAULT NULL,

    action_title VARCHAR(150) NOT NULL,

    action_description TEXT DEFAULT NULL,

    ip_address VARCHAR(100) DEFAULT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_activity_user(user_type, user_id),
    INDEX idx_activity_created(created_at)

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4;

COMMIT;

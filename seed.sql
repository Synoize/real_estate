-- Seed data for real_estate_db
-- Default passwords are: Password@123

USE `real_estate_db`;

-- Admin
INSERT INTO `admins` (`full_name`, `phone`, `email`, `password`, `status`)
VALUES (
  'Super Admin',
  '+919999999999',
  'admin@example.com',
  '$2y$10$hR5M9QeStH5vZp2tXrTg9OjwS8o6vS0p8c7l5vH8x.5hB0S8x7l5m',
  1
);

-- Employee
INSERT INTO `employees` (`admin_id`, `full_name`, `phone`, `email`, `password`, `status`)
VALUES (
  1,
  'Default Employee',
  '+919888888888',
  'employee@example.com',
  '$2y$10$hR5M9QeStH5vZp2tXrTg9OjwS8o6vS0p8c7l5vH8x.5hB0S8x7l5m',
  1
);

-- Builder
INSERT INTO `builders` (
  `builder_code`, `created_by_role`, `created_by_id`,
  `company_name`, `contact_name`, `phone`, `email`,
  `address`, `city`, `state`,
  `password`, `status`
)
VALUES (
  'B0001',
  'admin',
  1,
  'Default Builder Pvt Ltd',
  'Builder Contact',
  '+919777777777',
  'builder@example.com',
  '221B Business Street',
  'Mumbai',
  'Maharashtra',
  '$2y$10$hR5M9QeStH5vZp2tXrTg9OjwS8o6vS0p8c7l5vH8x.5hB0S8x7l5m',
  1
);

-- Manager
INSERT INTO `managers` (
  `manager_code`, `builder_id`,
  `full_name`, `phone`, `email`,
  `password`, `status`
)
VALUES (
  'M0001',
  1,
  'Default Manager',
  '+919666666666',
  'manager@example.com',
  '$2y$10$hR5M9QeStH5vZp2tXrTg9OjwS8o6vS0p8c7l5vH8x.5hB0S8x7l5m',
  1
);

-- Public user
INSERT INTO `users` (`full_name`, `phone`, `email`, `password`, `status`)
VALUES (
  'Default User',
  '+919555555555',
  'user@example.com',
  '$2y$10$hR5M9QeStH5vZp2tXrTg9OjwS8o6vS0p8c7l5vH8x.5hB0S8x7l5m',
  1
);

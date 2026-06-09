-- Seed data for real_estate_db
-- Default password for every seeded account: Password@123

USE `real_estate_db`;

SET @password_hash = '$2y$10$aX4tvq/QEVw/CUfXFkzg/.xcJBzqXf9Z94Ehgj2dZPJxGrNwPC0tm';

INSERT IGNORE INTO admins (
  uuid, full_name, email, password, role, status
) VALUES (
  UUID(), 'Super Admin', 'admin@example.com', @password_hash, 'super_admin', 'active'
);

INSERT IGNORE INTO employees (
  uuid, employee_code, full_name, email, phone, password,
  department, designation, status, created_by
) VALUES (
  UUID(), 'EMP001', 'Default Employee', 'employee@example.com',
  '+919888888888', @password_hash, 'Operations', 'Sales Coordinator',
  'active', 1
);

INSERT IGNORE INTO builders (
  uuid, company_name, company_slug, builder_name, email, phone,
  whatsapp_number, password, rera_number, company_description,
  address, city, state, pincode, latitude, longitude,
  is_verified, status, approved_by_admin, approved_at
) VALUES
(
  UUID(), 'Skyline Developers', 'skyline-developers', 'Aarav Mehta',
  'builder@example.com', '+919777777777', '+919777777777',
  @password_hash, 'A51900001761',
  'Premium residential developer with direct-builder pricing and verified inventory.',
  'Bandra Kurla Complex', 'Mumbai', 'Maharashtra', '400051',
  19.05961000, 72.86561000, 1, 'active', 1, NOW()
),
(
  UUID(), 'Green Vista Realty', 'green-vista-realty', 'Neha Kapoor',
  'green@example.com', '+919777777778', '+919777777778',
  @password_hash, 'P52100033211',
  'Lifestyle-focused homes near technology corridors and transit hubs.',
  'Hinjewadi Phase 1', 'Pune', 'Maharashtra', '411057',
  18.59127000, 73.73891000, 1, 'active', 1, NOW()
);

INSERT IGNORE INTO associate_managers (
  uuid, builder_id, manager_code, full_name, email, phone,
  whatsapp_number, password, status, created_by
) VALUES (
  UUID(), 1, 'MGR001', 'Default Manager', 'manager@example.com',
  '+919666666666', '+919666666666', @password_hash, 'active', 1
);

INSERT IGNORE INTO project_categories (
  category_name, category_slug, category_icon, status
) VALUES
  ('Apartment', 'apartment', 'building-2', 'active'),
  ('Plot', 'plot', 'map', 'active');

UPDATE projects
SET status = 'draft',
    deleted_at = COALESCE(deleted_at, NOW())
WHERE slug = 'skyline-garden-villas'
  AND project_type NOT IN ('Apartment', 'Plot');

INSERT IGNORE INTO projects (
  uuid, builder_id, assigned_manager_id, category_id, project_type,
  project_name, slug, project_code, rera_number, city, state, locality,
  address, overview, amenities, brochure_file, thumbnail_image,
  featured_image, youtube_video_link, total_towers, total_units,
  total_floors, total_area, possession_date, launch_date,
  min_price, max_price, is_featured, is_verified, project_status, status
) VALUES
(
  UUID(), 1, 1, 1, 'Apartment', 'Skyline Marina Residences',
  'skyline-marina-residences', 'PRJ001', 'A51900001761',
  'Mumbai', 'Maharashtra', 'Bandra',
  'Near Bandra Kurla Complex, Mumbai',
  'Sea-facing luxury apartments with smart layouts, direct builder offers, and assisted site visits.',
  'Clubhouse, Swimming Pool, Gym, Kids Play Area, Sky Lounge, Security',
  NULL,
  'https://images.unsplash.com/photo-1605146769289-440113cc3d00?auto=format&fit=crop&w=900&q=80',
  'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=1600&q=80',
  NULL, 3, 420, 42, '2.4 acres', '2028-12-31', '2025-04-01',
  12500000, 38500000, 1, 1, 'Ongoing', 'published'
),
(
  UUID(), 2, NULL, 1, 'Apartment', 'Green Vista Heights',
  'green-vista-heights', 'PRJ002', 'P52100033211',
  'Pune', 'Maharashtra', 'Hinjewadi',
  'Hinjewadi Phase 1, Pune',
  'Transit-friendly residences close to IT parks with no-brokerage assistance and flexible visit slots.',
  'Coworking Lounge, Garden, Jogging Track, Gym, EV Charging, CCTV',
  NULL,
  'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=900&q=80',
  'https://images.unsplash.com/photo-1600566753190-17f0baa2a6c3?auto=format&fit=crop&w=1600&q=80',
  NULL, 5, 760, 35, '4.8 acres', '2027-06-30', '2024-11-01',
  7200000, 16500000, 1, 1, 'Ongoing', 'published'
),
(
  UUID(), 1, 1, 2, 'Plot', 'Skyline Garden Plots',
  'skyline-garden-plots', 'PRJ003', 'A51900001999',
  'Mumbai', 'Maharashtra', 'Thane',
  'Ghodbunder Road, Thane',
  'Low-density plotted development with clear title, internal roads, and guided online presentations.',
  'Gated Entry, Internal Roads, Street Lighting, Water Connection, 24x7 Security',
  NULL,
  'https://images.unsplash.com/photo-1600047509807-ba8f99d2cdde?auto=format&fit=crop&w=900&q=80',
  'https://images.unsplash.com/photo-1600607687920-4e2a09cf159d?auto=format&fit=crop&w=1600&q=80',
  NULL, 0, 120, 0, '12 acres', '2028-03-31', '2025-01-15',
  4500000, 12500000, 0, 1, 'Upcoming', 'published'
);

INSERT INTO project_unit_plans (
  project_id, unit_name, bhk_type, area, facing, price, booking_amount, description
)
SELECT p.id, '2 BHK Comfort', '2 BHK', '760 sqft', 'East', 12500000, 500000, 'Compact sea-facing apartment plan.'
FROM projects p
WHERE p.slug = 'skyline-marina-residences'
  AND NOT EXISTS (
    SELECT 1 FROM project_unit_plans u
    WHERE u.project_id = p.id AND u.unit_name = '2 BHK Comfort'
  );

INSERT INTO project_unit_plans (
  project_id, unit_name, bhk_type, area, facing, price, booking_amount, description
)
SELECT p.id, '3 BHK Signature', '3 BHK', '1180 sqft', 'West', 24500000, 750000, 'Larger apartment with premium balcony space.'
FROM projects p
WHERE p.slug = 'skyline-marina-residences'
  AND NOT EXISTS (
    SELECT 1 FROM project_unit_plans u
    WHERE u.project_id = p.id AND u.unit_name = '3 BHK Signature'
  );

INSERT INTO project_unit_plans (
  project_id, unit_name, bhk_type, area, facing, price, booking_amount, description
)
SELECT p.id, '2 BHK Urban', '2 BHK', '680 sqft', 'East', 7200000, 300000, 'Efficient apartment for IT corridor buyers.'
FROM projects p
WHERE p.slug = 'green-vista-heights'
  AND NOT EXISTS (
    SELECT 1 FROM project_unit_plans u
    WHERE u.project_id = p.id AND u.unit_name = '2 BHK Urban'
  );

INSERT INTO project_unit_plans (
  project_id, unit_name, bhk_type, area, facing, price, booking_amount, description
)
SELECT p.id, '3 BHK Garden', '3 BHK', '1040 sqft', 'North', 11800000, 400000, 'Family apartment with garden-facing views.'
FROM projects p
WHERE p.slug = 'green-vista-heights'
  AND NOT EXISTS (
    SELECT 1 FROM project_unit_plans u
    WHERE u.project_id = p.id AND u.unit_name = '3 BHK Garden'
  );

INSERT INTO project_unit_plans (
  project_id, unit_name, bhk_type, area, facing, price, booking_amount, description
)
SELECT p.id, 'Residential Plot 1000', 'Plot', '1000 sqft', 'East', 4500000, 250000, 'Entry-sized plotted development unit.'
FROM projects p
WHERE p.slug = 'skyline-garden-plots'
  AND NOT EXISTS (
    SELECT 1 FROM project_unit_plans u
    WHERE u.project_id = p.id AND u.unit_name = 'Residential Plot 1000'
  );

INSERT INTO project_unit_plans (
  project_id, unit_name, bhk_type, area, facing, price, booking_amount, description
)
SELECT p.id, 'Residential Plot 1800', 'Plot', '1800 sqft', 'West', 8100000, 350000, 'Larger plotted development unit for future construction planning.'
FROM projects p
WHERE p.slug = 'skyline-garden-plots'
  AND NOT EXISTS (
    SELECT 1 FROM project_unit_plans u
    WHERE u.project_id = p.id AND u.unit_name = 'Residential Plot 1800'
  );

INSERT INTO project_videos (
  project_id, video_title, video_url, thumbnail
)
SELECT p.id, 'Skyline Marina Walkthrough', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', p.thumbnail_image
FROM projects p
WHERE p.slug = 'skyline-marina-residences'
  AND NOT EXISTS (
    SELECT 1 FROM project_videos v
    WHERE v.project_id = p.id AND v.video_title = 'Skyline Marina Walkthrough'
  );

INSERT INTO project_videos (
  project_id, video_title, video_url, thumbnail
)
SELECT p.id, 'Green Vista Heights Tour', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', p.thumbnail_image
FROM projects p
WHERE p.slug = 'green-vista-heights'
  AND NOT EXISTS (
    SELECT 1 FROM project_videos v
    WHERE v.project_id = p.id AND v.video_title = 'Green Vista Heights Tour'
  );

INSERT INTO project_videos (
  project_id, video_title, video_url, thumbnail
)
SELECT p.id, 'Skyline Garden Plots Overview', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', p.thumbnail_image
FROM projects p
WHERE p.slug = 'skyline-garden-plots'
  AND NOT EXISTS (
    SELECT 1 FROM project_videos v
    WHERE v.project_id = p.id AND v.video_title = 'Skyline Garden Plots Overview'
  );

INSERT IGNORE INTO users (
  uuid, full_name, phone, email, password, city, state,
  is_verified, terms_accepted, privacy_accepted, status
) VALUES (
  UUID(), 'Default User', '+919555555555', 'user@example.com',
  @password_hash, 'Mumbai', 'Maharashtra', 1, 1, 1, 'active'
);
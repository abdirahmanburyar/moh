-- Somali Regions and Districts Seeder SQL
-- Run this to populate your regions and districts tables

-- First, insert all regions
INSERT IGNORE INTO regions (name, created_at, updated_at) VALUES
('Awdal', NOW(), NOW()),
('Woqooyi Galbeed', NOW(), NOW()),
('Togdheer', NOW(), NOW()),
('Sanaag', NOW(), NOW()),
('Sool', NOW(), NOW()),
('Bari', NOW(), NOW()),
('Nugaal', NOW(), NOW()),
('Mudug', NOW(), NOW()),
('Galguduud', NOW(), NOW()),
('Hiran', NOW(), NOW()),
('Middle Shabelle', NOW(), NOW()),
('Banaadir', NOW(), NOW()),
('Lower Shabelle', NOW(), NOW()),
('Bakool', NOW(), NOW()),
('Bay', NOW(), NOW()),
('Gedo', NOW(), NOW()),
('Middle Jubba', NOW(), NOW()),
('Lower Jubba', NOW(), NOW());

-- Then, insert all districts with their corresponding regions
INSERT IGNORE INTO districts (name, region, created_at, updated_at) VALUES
-- Awdal Region
('Borama', 'Awdal', NOW(), NOW()),
('Baki', 'Awdal', NOW(), NOW()),
('Lughaya', 'Awdal', NOW(), NOW()),
('Zeila', 'Awdal', NOW(), NOW()),

-- Woqooyi Galbeed Region
('Hargeisa', 'Woqooyi Galbeed', NOW(), NOW()),
('Berbera', 'Woqooyi Galbeed', NOW(), NOW()),
('Gabiley', 'Woqooyi Galbeed', NOW(), NOW()),
('Sheikh', 'Woqooyi Galbeed', NOW(), NOW()),

-- Togdheer Region
('Burao', 'Togdheer', NOW(), NOW()),
('Oodweyne', 'Togdheer', NOW(), NOW()),
('Buhoodle', 'Togdheer', NOW(), NOW()),

-- Sanaag Region
('Erigavo', 'Sanaag', NOW(), NOW()),
('Las Qorey', 'Sanaag', NOW(), NOW()),
('Badhan', 'Sanaag', NOW(), NOW()),
('Dhahar', 'Sanaag', NOW(), NOW()),

-- Sool Region
('Las Anod', 'Sool', NOW(), NOW()),
('Taleex', 'Sool', NOW(), NOW()),
('Hudun', 'Sool', NOW(), NOW()),
('Yagori', 'Sool', NOW(), NOW()),

-- Bari Region
('Bosaso', 'Bari', NOW(), NOW()),
('Qardho', 'Bari', NOW(), NOW()),
('Iskushuban', 'Bari', NOW(), NOW()),
('Alula', 'Bari', NOW(), NOW()),

-- Nugaal Region
('Garowe', 'Nugaal', NOW(), NOW()),
('Eyl', 'Nugaal', NOW(), NOW()),
('Dangorayo', 'Nugaal', NOW(), NOW()),

-- Mudug Region
('Galkayo', 'Mudug', NOW(), NOW()),
('Hobyo', 'Mudug', NOW(), NOW()),
('Harardhere', 'Mudug', NOW(), NOW()),
('Wisil', 'Mudug', NOW(), NOW()),

-- Galguduud Region
('Dhusamareb', 'Galguduud', NOW(), NOW()),
('Abudwaq', 'Galguduud', NOW(), NOW()),
('Adado', 'Galguduud', NOW(), NOW()),
('Balanbale', 'Galguduud', NOW(), NOW()),

-- Hiran Region
('Beledweyne', 'Hiran', NOW(), NOW()),
('Buulobarde', 'Hiran', NOW(), NOW()),
('Jalalaqsi', 'Hiran', NOW(), NOW()),

-- Middle Shabelle Region
('Jowhar', 'Middle Shabelle', NOW(), NOW()),
('Balad', 'Middle Shabelle', NOW(), NOW()),
('Warsheikh', 'Middle Shabelle', NOW(), NOW()),
('Adale', 'Middle Shabelle', NOW(), NOW()),

-- Banaadir Region (Mogadishu)
('Mogadishu', 'Banaadir', NOW(), NOW()),
('Afgooye', 'Banaadir', NOW(), NOW()),
('Marka', 'Banaadir', NOW(), NOW()),
('Wanlaweyn', 'Banaadir', NOW(), NOW()),

-- Lower Shabelle Region
('Qoryooley', 'Lower Shabelle', NOW(), NOW()),
('Kurtunwaarey', 'Lower Shabelle', NOW(), NOW()),
('Sablale', 'Lower Shabelle', NOW(), NOW()),

-- Bakool Region
('Xuddur', 'Bakool', NOW(), NOW()),
('Tiyeglow', 'Bakool', NOW(), NOW()),
('Wajid', 'Bakool', NOW(), NOW()),
('El Barde', 'Bakool', NOW(), NOW()),

-- Bay Region
('Baidoa', 'Bay', NOW(), NOW()),
('Burhakaba', 'Bay', NOW(), NOW()),
('Dinsoor', 'Bay', NOW(), NOW()),
('Qansax Dheere', 'Bay', NOW(), NOW()),

-- Gedo Region
('Garbahaarey', 'Gedo', NOW(), NOW()),
('Luuq', 'Gedo', NOW(), NOW()),
('Bardhere', 'Gedo', NOW(), NOW()),
('El Wak', 'Gedo', NOW(), NOW()),

-- Middle Jubba Region
('Bu\'aale', 'Middle Jubba', NOW(), NOW()),
('Jilib', 'Middle Jubba', NOW(), NOW()),
('Sakow', 'Middle Jubba', NOW(), NOW()),

-- Lower Jubba Region
('Kismayo', 'Lower Jubba', NOW(), NOW()),
('Jamame', 'Lower Jubba', NOW(), NOW()),
('Badhaadhe', 'Lower Jubba', NOW(), NOW()),
('Afmadow', 'Lower Jubba', NOW(), NOW());

-- Verify the data was inserted
SELECT 'Regions count:' as info, COUNT(*) as count FROM regions
UNION ALL
SELECT 'Districts count:', COUNT(*) FROM districts;

-- Pharmacy Management System Database
CREATE DATABASE IF NOT EXISTS pharmacy_db;
USE pharmacy_db;

-- Table for medication information (detailed medical info)
CREATE TABLE medications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class VARCHAR(255) NOT NULL,
    active_ingredient VARCHAR(255) NOT NULL UNIQUE,
    mechanism_of_action TEXT,
    indication TEXT,
    side_effects TEXT,
    contraindication TEXT,
    pregnancy_safe BOOLEAN DEFAULT FALSE,
    lactation_safe BOOLEAN DEFAULT FALSE,
    adult_dosage_1 VARCHAR(255),
    adult_frequency_1 VARCHAR(255),
    adult_dosage_2 VARCHAR(255),
    adult_frequency_2 VARCHAR(255),
    children_dosage_1 VARCHAR(255),
    children_frequency_1 VARCHAR(255),
    children_dosage_2 VARCHAR(255),
    children_frequency_2 VARCHAR(255),
    dosage TEXT, -- Multiple doses separated by pipe (|) - for backward compatibility
    dose_frequency TEXT, -- Multiple frequencies separated by pipe (|) - for backward compatibility
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table for cosmetics products
CREATE TABLE cosmetics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    barcode VARCHAR(255) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    company VARCHAR(255) NOT NULL,
    indication TEXT,
    notes TEXT,
    class VARCHAR(255),
    price DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_barcode (barcode)
);

-- Table for dental products
CREATE TABLE dental (
    id INT AUTO_INCREMENT PRIMARY KEY,
    barcode VARCHAR(255) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    company VARCHAR(255) NOT NULL,
    indication TEXT,
    notes TEXT,
    class VARCHAR(255),
    price DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_barcode (barcode)
);

-- Table for products (barcode-scanned items)
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    barcode VARCHAR(255) NOT NULL UNIQUE,
    product_name VARCHAR(255) NOT NULL,
    company VARCHAR(255) NOT NULL,
    active_ingredient VARCHAR(255) NOT NULL,
    dose VARCHAR(255),
    form VARCHAR(255), -- tablet, capsule, syrup, etc.
    price DECIMAL(10,2),
    image_url VARCHAR(500),
    medication_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (medication_id) REFERENCES medications(id) ON DELETE SET NULL,
    INDEX idx_active_ingredient (active_ingredient),
    INDEX idx_barcode (barcode)
);

-- Insert sample medications
INSERT INTO medications (class, active_ingredient, mechanism_of_action, indication, side_effects, contraindication, pregnancy_safe, lactation_safe, dosage, dose_frequency) VALUES
('Analgesic/Antipyretic', 'Paracetamol', 'Inhibits cyclooxygenase enzymes in the central nervous system', 'Pain relief, fever reduction', 'Nausea, skin rash, blood disorders (rare)', 'Severe liver disease', TRUE, TRUE, '500mg|1000mg', '1-2 tablets every 4-6 hours|1 tablet every 6-8 hours'),

('Antibiotic (Penicillin)', 'Amoxicillin', 'Inhibits bacterial cell wall synthesis', 'Bacterial infections', 'Diarrhea, nausea, skin rash, allergic reactions', 'Penicillin allergy', FALSE, FALSE, '500mg|250mg', '3 times daily|4 times daily'),

('Antidiabetic (Biguanide)', 'Metformin', 'Decreases hepatic glucose production, increases insulin sensitivity', 'Type 2 diabetes mellitus', 'Gastrointestinal upset, lactic acidosis (rare)', 'Severe kidney disease, heart failure', FALSE, FALSE, '500mg|850mg', 'Twice daily with meals|Once daily with dinner'),

('ACE Inhibitor', 'Lisinopril', 'Inhibits angiotensin-converting enzyme', 'Hypertension, heart failure', 'Dry cough, hyperkalemia, angioedema', 'Pregnancy, bilateral renal artery stenosis', FALSE, FALSE, '10mg|20mg', 'Once daily|Twice daily'),

('Proton Pump Inhibitor', 'Omeprazole', 'Inhibits H+/K+-ATPase enzyme in gastric parietal cells', 'GERD, peptic ulcers', 'Headache, diarrhea, vitamin B12 deficiency (long-term)', 'Hypersensitivity to proton pump inhibitors', TRUE, TRUE, '20mg|40mg', 'Once daily before breakfast|Twice daily before meals');

-- Insert sample products
INSERT INTO products (barcode, product_name, company, active_ingredient, dose, form, price, image_url, medication_id) VALUES
('1234567890123', 'Panadol Extra', 'GSK', 'Paracetamol', '500mg', 'Tablet', 3.25, 'images/panadol.jpg', 1),
('2345678901234', 'Augmentin', 'GSK', 'Amoxicillin', '625mg', 'Tablet', 15.50, 'images/augmentin.jpg', 2),
('3456789012345', 'Glucophage', 'Merck', 'Metformin', '850mg', 'Tablet', 14.75, 'images/glucophage.jpg', 3),
('4567890123456', 'Prinivil', 'Merck', 'Lisinopril', '5mg', 'Tablet', 12.80, 'images/prinivil.jpg', 4),
('5678901234567', 'Losec', 'AstraZeneca', 'Omeprazole', '40mg', 'Capsule', 22.30, 'images/losec.jpg', 5);

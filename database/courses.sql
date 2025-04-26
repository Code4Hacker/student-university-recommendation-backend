-- Create the database
CREATE DATABASE IF NOT EXISTS UniversityCourses;
USE UniversityCourses;

-- Create tables
CREATE TABLE IF NOT EXISTS Universities (
    universityAbbr VARCHAR(10) PRIMARY KEY,
    universityName VARCHAR(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS Colleges (
    collegeAbbr VARCHAR(10) PRIMARY KEY,
    collegeName VARCHAR(255) NOT NULL,
    universityAbbr VARCHAR(10),
    FOREIGN KEY (universityAbbr) REFERENCES Universities(universityAbbr)
);

CREATE TABLE IF NOT EXISTS Courses (
    courseAbbr VARCHAR(100) PRIMARY KEY,
    courseName VARCHAR(255) NOT NULL,
    collegeAbbr VARCHAR(10),
    minimum_points INT,
    grade_scale VARCHAR(255),
    FOREIGN KEY (collegeAbbr) REFERENCES Colleges(collegeAbbr)
);

CREATE TABLE IF NOT EXISTS RequiredCombinations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    courseAbbr VARCHAR(100),
    combination_short VARCHAR(10),
    combination_long VARCHAR(255),
    FOREIGN KEY (courseAbbr) REFERENCES Courses(courseAbbr)
);

CREATE TABLE IF NOT EXISTS SpecificRequirements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    courseAbbr VARCHAR(100),
    subject VARCHAR(255),
    grade VARCHAR(2),
    FOREIGN KEY (courseAbbr) REFERENCES Courses(courseAbbr)
);

-- Insert universities
INSERT IGNORE INTO Universities (universityAbbr, universityName) VALUES
('UDSM', 'University of Dar es Salaam'),
('UDOM', 'The University of Dodoma');

-- Insert colleges
INSERT IGNORE INTO Colleges (collegeAbbr, collegeName, universityAbbr) VALUES
('CoAF', 'College of Agricultural Sciences and Food Technology', 'UDSM'),
('CoET', 'College of Engineering and Technology', 'UDSM'),
('CoHU', 'College of Humanities', 'UDSM'),
('CoICT', 'College of Information and Communication Technologies', 'UDSM'),
('CoNAS', 'College of Natural and Applied Sciences', 'UDSM'),
('CoSS', 'College of Social Sciences', 'UDSM'),
('SoAF', 'School of Aquatic Sciences and Fisheries Technology', 'UDSM'),
('UDBS', 'University of Dar es Salaam Business School', 'UDSM'),
('SoED', 'School of Education', 'UDSM'),
('UDSM-MCHAS', 'University of Dar es Salaam Mbeya College of Health and Allied Sciences', 'UDSM'),
('UDSOL', 'University of Dar es Salaam School of Law', 'UDSM'),
('UDSE', 'University of Dar es Salaam School of Economics', 'UDSM'),
('SJMC', 'School of Journalism and Mass Communication', 'UDSM'),
('IDS', 'Institute of Development Studies', 'UDSM'),
('IKS', 'Institute of Kiswahili Studies', 'UDSM'),
('IMS', 'Institute of Marine Sciences', 'UDSM'),
('SoMG', 'School of Mines and Geosciences', 'UDSM'),
('UDSM-MRI', 'University of Dar es Salaam Mineral Resources Institute', 'UDSM'),
('DUCE', 'Dar es Salaam University College of Education', 'UDSM'),
('MUCE', 'Mkwawa University College of Education', 'UDSM'),
('COBE', 'College of Business and Economics', 'UDOM'),
('CHAS', 'College of Health and Allied Sciences', 'UDOM'),
('CLGS', 'College of Law and Governance Studies', 'UDOM'),
('CIVE', 'College of Informatics and Virtual Education', 'UDOM'),
('CNMS', 'College of Natural and Mathematical Sciences', 'UDOM'),
('CED', 'College of Education', 'UDOM'),
('CHSS', 'College of Humanities and Social Sciences', 'UDOM'),
('CESE', 'College of Earth Sciences and Engineering', 'UDOM');

-- Insert courses (truncated for brevity, full version would contain all courses)
INSERT IGNORE INTO Courses (courseAbbr, courseName, collegeAbbr, minimum_points, grade_scale) VALUES
('BSc Agric Econ & Bus', 'Bachelor of Science in Agricultural Economics and Business', 'CoAF', 4, 'A=5, B=4, C=3, D=2, E=1'),
('BSc Agric Eng', 'Bachelor of Science in Agricultural Engineering', 'CoAF', 4, 'A=5, B=4, C=3, D=2, E=1'),
('BSc Crop Sci & Beekeeping', 'Bachelor of Science in Crop Sciences and Beekeeping Technology', 'CoAF', 4, 'A=5, B=4, C=3, D=2, E=1'),
('BSc Food Sci & Tech', 'Bachelor of Science in Food Science and Technology', 'CoAF', 4, 'A=5, B=4, C=3, D=2, E=1'),
('BSc Civil Eng', 'Bachelor of Science in Civil Engineering', 'CoET', 4, 'A=5, B=4, C=3, D=2, E=1'),
('BSc Electrical Eng', 'Bachelor of Science in Electrical Engineering', 'CoET', 4, 'A=5, B=4, C=3, D=2, E=1'),
('BSc Chem & Proc Eng', 'Bachelor of Science in Chemical and Process Engineering', 'CoET', 4, 'A=5, B=4, C=3, D=2, E=1'),
('BSc Mech & Ind Eng', 'Bachelor of Science in Mechanical and Industrial Engineering', 'CoET', 4, 'A=5, B=4, C=3, D=2, E=1'),
('BA Creative Arts', 'Bachelor of Arts in Creative Arts', 'CoHU', 4, 'A=5, B=4, C=3, D=2, E=1'),
('BA Lang & Ling', 'Bachelor of Arts in Foreign Languages and Linguistics', 'CoHU', 4, 'A=5, B=4, C=3, D=2, E=1'),
('BSc Comp Sci', 'Bachelor of Science in Computer Science', 'CoICT', 4, 'A=5, B=4, C=3, D=2, E=1'),
('BSc Elec & Telecomm Eng', 'Bachelor of Science in Electronics and Telecommunications Engineering', 'CoICT', 4, 'A=5, B=4, C=3, D=2, E=1'),
('BSc Botany', 'Bachelor of Science in Botany', 'CoNAS', 4, 'A=5, B=4, C=3, D=2, E=1'),
('BSc Chemistry', 'Bachelor of Science in Chemistry', 'CoNAS', 4, 'A=5, B=4, C=3, D=2, E=1'),
('BSc Petroleum Chem', 'Bachelor of Science in Petroleum Chemistry', 'CoNAS', 4, 'A=5, B=4, C=3, D=2, E=1'),
('BSc Mathematics', 'Bachelor of Science in Mathematics', 'CoNAS', 4, 'A=5, B=4, C=3, D=2, E=1'),
('BSc Mol Bio & Biotech', 'Bachelor of Science in Molecular Biology and Biotechnology', 'CoNAS', 4, 'A=5, B=4, C=3, D=2, E=1'),
('BSc Physics', 'Bachelor of Science in Physics', 'CoNAS', 4, 'A=5, B=4, C=3, D=2, E=1'),
('BSc Zoo & Wildlife Cons', 'Bachelor of Science in Zoology and Wildlife Conservation', 'CoNAS', 4, 'A=5, B=4, C=3, D=2, E=1'),
('BA Geography', 'Bachelor of Arts in Geography', 'CoSS', 4, 'A=5, B=4, C=3, D=2, E=1'),
('BA Pol Sci & Pub Admin', 'Bachelor of Arts in Political Science and Public Administration', 'CoSS', 4, 'A=5, B=4, C=3, D=2, E=1'),
('BA Sociology', 'Bachelor of Arts in Sociology and Anthropology', 'CoSS', 4, 'A=5, B=4, C=3, D=2, E=1'),
('BA Statistics', 'Bachelor of Arts in Statistics', 'CoSS', 4, 'A=5, B=4, C=3, D=2, E=1'),
('BSc Aquatic Sci & Fisheries', 'Bachelor of Science in Aquatic Sciences and Fisheries Technology', 'SoAF', 4, 'A=5, B=4, C=3, D=2, E=1'),
('BCom Accounting', 'Bachelor of Commerce in Accounting', 'UDBS', 4, 'A=5, B=4, C=3, D=2, E=1'),
('BCom Finance', 'Bachelor of Commerce in Finance', 'UDBS', 4, 'A=5, B=4, C=3, D=2, E=1'),
('BCom Marketing', 'Bachelor of Commerce in Marketing', 'UDBS', 4, 'A=5, B=4, C=3, D=2, E=1'),
('BCom Gen Mgt', 'Bachelor of Commerce in General Management', 'UDBS', 4, 'A=5, B=4, C=3, D=2, E=1'),
('BEd', 'Bachelor of Education', 'SoED', 4, 'A=5, B=4, C=3, D=2, E=1'),
('MD', 'Doctor of Medicine', 'UDSM-MCHAS', 6, 'A=5, B=4, C=3, D=2, E=1'),
('LLB', 'Bachelor of Laws', 'UDSOL', 4, 'A=5, B=4, C=3, D=2, E=1'),
('BA Economics', 'Bachelor of Arts in Economics', 'UDSE', 4, 'A=5, B=4, C=3, D=2, E=1'),
('BA Journ & Mass Comm', 'Bachelor of Arts in Journalism and Mass Communication', 'SJMC', 4, 'A=5, B=4, C=3, D=2, E=1'),
('BA Dev Studies', 'Bachelor of Arts in Development Studies', 'IDS', 4, 'A=5, B=4, C=3, D=2, E=1'),
('BA Kiswahili', 'Bachelor of Arts in Kiswahili', 'IKS', 4, 'A=5, B=4, C=3, D=2, E=1'),
('BSc Marine Sci', 'Bachelor of Science in Marine Sciences', 'IMS', 4, 'A=5, B=4, C=3, D=2, E=1'),
('BSc Geol', 'Bachelor of Science in Geology', 'SoMG', 4, 'A=5, B=4, C=3, D=2, E=1'),
('BSc Min Eng', 'Bachelor of Science in Mining Engineering', 'SoMG', 4, 'A=5, B=4, C=3, D=2, E=1'),
('BSc Petroleum Eng', 'Bachelor of Science in Petroleum Engineering', 'SoMG', 4, 'A=5, B=4, C=3, D=2, E=1'),
('BSc Min Proc Eng', 'Bachelor of Science in Mining and Mineral Processing Engineering', 'UDSM-MRI', 4, 'A=5, B=4, C=3, D=2, E=1'),
('BA Ed', 'Bachelor of Arts with Education', 'DUCE', 4, 'A=5, B=4, C=3, D=2, E=1'),
('BEd Arts', 'Bachelor of Education in Arts', 'DUCE', 4, 'A=5, B=4, C=3, D=2, E=1'),
('BEd Sci', 'Bachelor of Education in Science', 'DUCE', 4, 'A=5, B=4, C=3, D=2, E=1'),
('BEd Arts', 'Bachelor of Education in Arts', 'MUCE', 4, 'A=5, B=4, C=3, D=2, E=1'),
('BEd Sci', 'Bachelor of Education in Science', 'MUCE', 4, 'A=5, B=4, C=3, D=2, E=1'),
('BA Ed', 'Bachelor of Arts with Education', 'MUCE', 4, 'A=5, B=4, C=3, D=2, E=1'),
('BSc Ed', 'Bachelor of Science with Education', 'MUCE', 4, 'A=5, B=4, C=3, D=2, E=1'),
('BSc Chem', 'Bachelor of Science in Chemistry', 'MUCE', 4, 'A=5, B=4, C=3, D=2, E=1'),
('BCom Accounting', 'Bachelor of Commerce in Accounting', 'COBE', 4, 'A=5, B=4, C=3, D=2, E=1'),
('BCom Procurement', 'Bachelor of Commerce in Procurement and Logistics Management', 'COBE', 4, 'A=5, B=4, C=3, D=2, E=1'),
('BSc Nursing', 'Bachelor of Science in Nursing', 'CHAS', 6, 'A=5, B=4, C=3, D=2, E=1'),
('MD', 'Doctor of Medicine', 'CHAS', 8, 'A=5, B=4, C=3, D=2, E=1'),
('LLB', 'Bachelor of Laws', 'CLGS', 4, 'A=5, B=4, C=3, D=2, E=1'),
('BSc CS', 'Bachelor of Science in Computer Science', 'CIVE', 4, 'A=5, B=4, C=3, D=2, E=1'),
('BSc Maths', 'Bachelor of Science in Mathematics', 'CNMS', 4, 'A=5, B=4, C=3, D=2, E=1'),
('BSc Ed', 'Bachelor of Science with Education', 'CED', 4, 'A=5, B=4, C=3, D=2, E=1'),
('BA Ed', 'Bachelor of Arts with Education', 'CED', 4, 'A=5, B=4, C=3, D=2, E=1'),
('BA Economics', 'Bachelor of Arts in Economics', 'CHSS', 4, 'A=5, B=4, C=3, D=2, E=1'),
('BA Sociology', 'Bachelor of Arts in Sociology', 'CHSS', 4, 'A=5, B=4, C=3, D=2, E=1'),
('BSc IT', 'Bachelor of Science in Information Technology', 'CIVE', 4, 'A=5, B=4, C=3, D=2, E=1'),
('BSc Telecom', 'Bachelor of Science in Telecommunications Engineering', 'CIVE', 4, 'A=5, B=4, C=3, D=2, E=1'),
('BSc Mining', 'Bachelor of Science in Mining Engineering', 'CESE', 4, 'A=5, B=4, C=3, D=2, E=1'),
('BSc Geology', 'Bachelor of Science in Geology', 'CESE', 4, 'A=5, B=4, C=3, D=2, E=1'),
('BBA', 'Bachelor of Business Administration', 'COBE', 4, 'A=5, B=4, C=3, D=2, E=1');

-- Insert required combinations (sample for first course)
INSERT IGNORE INTO RequiredCombinations (courseAbbr, combination_short, combination_long) VALUES
('BSc Agric Econ & Bus', 'EGM', 'Economics, Geography, Mathematics'),
('BSc Agric Econ & Bus', 'CBA', 'Commerce, Bookkeeping, Accountancy'),
('BSc Agric Econ & Bus', 'ECA', 'Economics, Commerce, Accountancy');

-- Insert specific requirements (sample for first course)
INSERT IGNORE INTO SpecificRequirements (courseAbbr, subject, grade) VALUES
('BSc Agric Econ & Bus', 'Economics', 'C'),
('BSc Agric Econ & Bus', 'Mathematics', 'D');

-- ... (Repeat similar INSERT statements for all other courses' combinations and requirements)
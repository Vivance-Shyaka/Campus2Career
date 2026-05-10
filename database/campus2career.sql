-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 10, 2026 at 10:29 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `campus2career`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `admin_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`admin_id`, `user_id`) VALUES
(1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--

CREATE TABLE `applications` (
  `application_id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `internship_id` int(11) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `interview_date` datetime DEFAULT NULL,
  `interview_location` varchar(255) DEFAULT NULL,
  `interview_notes` text DEFAULT NULL,
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `applications`
--

INSERT INTO `applications` (`application_id`, `student_id`, `internship_id`, `status`, `interview_date`, `interview_location`, `interview_notes`, `applied_at`) VALUES
(1, 1, 1, 'approved', '2026-05-16 22:16:00', '', '', '2026-05-08 19:29:34'),
(2, 1, 2, 'pending', NULL, NULL, NULL, '2026-05-08 19:30:26'),
(3, 4, 3, 'rejected', NULL, NULL, NULL, '2026-05-09 21:48:28');

-- --------------------------------------------------------

--
-- Table structure for table `companies`
--

CREATE TABLE `companies` (
  `company_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `company_name` varchar(100) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `description` longtext DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `companies`
--

INSERT INTO `companies` (`company_id`, `user_id`, `company_name`, `location`, `description`, `website`) VALUES
(1, 4, 'MTN Rwanda', 'Kigali', NULL, NULL),
(2, 5, 'Irembo Ltd', 'Kigali', NULL, NULL),
(3, 8, 'RDB Company', 'Kigali', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `internships`
--

CREATE TABLE `internships` (
  `internship_id` int(11) NOT NULL,
  `company_id` int(11) DEFAULT NULL,
  `title` varchar(150) NOT NULL,
  `description` longtext DEFAULT NULL,
  `responsibilities` longtext DEFAULT NULL,
  `expectations` longtext DEFAULT NULL,
  `internship_location` varchar(160) DEFAULT NULL,
  `requirements` longtext DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `internships`
--

INSERT INTO `internships` (`internship_id`, `company_id`, `title`, `description`, `responsibilities`, `expectations`, `internship_location`, `requirements`, `created_at`) VALUES
(1, 1, 'Web Development Intern', 'A Web Development Intern assists in designing, coding, and maintaining websites and web applications while gaining hands-on experience under senior developer supervision. Key duties include writing HTML/CSS/JS code, debugging, API integration, and updating CMS content. It is a fast-paced role requiring proficiency in front-end basics and basic database familiarity.Key ResponsibilitiesFront-End & Back-End Development: Writing clean, efficient, and well-documented code using HTML, CSS, JavaScript, and languages like PHP or Python.Website Maintenance: Updating existing website content, layouts, and troubleshooting issues.Collaboration: Working with UI/UX designers and senior engineers on team projects to implement new features.Testing and Debugging: Running tests for responsiveness, cross-browser compatibility, and overall website performance.API & Database Support: Assisting with backend integration and database operations (e.g., MySQL).Key Skills & RequirementsTechnical Knowledge: Familiarity with modern frameworks (e.g., React, Angular, Vue.js) and version control tools like Git.Programming Basics: Strong knowledge of HTML, CSS, and JavaScript is standard.Educational Background: Currently pursuing or recently completed a degree in Computer Science, IT, or a related field.Soft Skills: Strong communication, problem-solving skills, and a desire to learn in an agile team environment.Common Perks & ExperienceMentorship from senior developers.Real-world portfolio building.Flexible scheduling to accommodate school studies.', NULL, NULL, NULL, 'HTML, CSS, JS, PHP', '2026-05-08 19:20:56'),
(2, 2, 'Backend Developer Intern', 'Develop APIs and database systems', NULL, NULL, NULL, 'PHP, MySQL', '2026-05-08 19:20:56'),
(3, 3, 'A Business Administration Intern', 'A Business Administration Intern supports company operations by assisting with daily administrative tasks, data management, and project coordination. Typical responsibilities include data entry, scheduling, conducting research, preparing reports, and coordinating meetings. This role provides hands-on experience in business operations and strengthens organizational and communication skills.', 'Key Responsibilities\n\n1.Administrative Support: Maintain databases, manage filing systems, process documents, and handle internal/external communication.\n2.Operational Assistance: Support day-to-day operations by scheduling meetings, conducting research, and preparing meeting minutes\n3.Data & Reporting: Perform data entry, analyze business data, and assist in creating presentations or reports.\n4.Project & Event Coordination: Support project management tasks and assist in organizing company events or workshops.\n6.Collaboration: Work with various departments (Marketing, HR, Finance) to streamline business processes and improve efficiency.', 'Qualifications\n\n1.Education: Currently pursuing or recently graduated with a degree in Business Administration, Management, or a related field.\n2.Skills: Proficiency in Microsoft Office Suite (Word, Excel, PowerPoint) and Google Workspace.\n3.Competencies: Strong organizational, communication, and multitasking skills.\n4.Attributes: Detail-oriented, eager to learn, and proactive.', 'kigali', 'Core Technical (Hard) Skills\n\n.Financial Literacy & Accounting: You will learn to read financial statements, manage budgets, track expenses, and understand risk management.\n\nData Analysis & Research: Courses teach you how to collect and interpret data using tools like Excel or SPSS to make informed business decisions.\n\nStrategic Planning: You learn to set long-term goals, analyze competitive environments, and develop plans to improve organizational performance.\n\nTechnological Proficiency: Most programs require mastery of Microsoft Office (Word, Excel, PowerPoint), project management software, and CRM (Customer Relationship Management) tools.\n\nMarketing Fundamentals: You gain skills in market research, understanding customer behavior, and creating basic marketing strategies.\n\nCore Interpersonal (Soft) Skills\n\n.Leadership & Team Management: You develop the ability to motivate others, delegate tasks, and oversee projects from start to finish.\n\nCritical Thinking & Problem-Solving: Courses use case studies to help you diagnose issues and propose logical, data-backed solutions\n\n.Advanced Communication: You will practice clear professional writing, public speaking, and active listening to convey complex ideas effectively.\n\nTime Management & Organization: The course load itself helps you master prioritizing tasks, meeting deadlines, and managing resources efficiently.\n\nAdaptability & Ethics: You learn to navigate rapid changes in the business world and understand the legal and ethical implications of business decisions', '2026-05-09 21:45:39');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('approval','rejection','interview','info') DEFAULT 'info',
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_id`, `type`, `message`, `is_read`, `created_at`) VALUES
(1, 2, 'approval', 'Your application for \"Web Development Intern\" has been approved.', 0, '2026-05-09 20:16:14'),
(2, 2, 'interview', 'Interview scheduled for \"Web Development Intern\" on May 16, 2026 at 10:16 PM.', 0, '2026-05-09 20:16:36'),
(3, 8, 'info', 'New application from Prince SHEMA for \"A Business Administration Intern\".', 1, '2026-05-09 21:48:28'),
(4, 7, 'rejection', 'Your application for \"A Business Administration Intern\" was not selected this time.', 0, '2026-05-09 21:50:06');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `university` varchar(100) DEFAULT NULL,
  `course` varchar(100) DEFAULT NULL,
  `skills` text DEFAULT NULL,
  `cv_file` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_id`, `user_id`, `university`, `course`, `skills`, `cv_file`) VALUES
(1, 2, 'AUCA', 'Software Engineering', 'HTML, CSS, JavaScript', NULL),
(3, 6, 'AUCA', 'Software Engineering', 'HTML,JAVASCRIPT,PYTHON', 'cv_69ff7f8f1aff25.46833326.pdf'),
(4, 7, 'ULK', 'BUSINESS ADMINISTRATION', 'leadership, financial literacy (budgeting, analysis), strategic planning, and effective communication', 'cv_69ffa7c5e5dba3.39707390.pdf'),
(5, 9, 'University of Kigali', 'Information Technology', 'PHP, MySQL', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `student_certificates`
--

CREATE TABLE `student_certificates` (
  `certificate_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_certificates`
--

INSERT INTO `student_certificates` (`certificate_id`, `student_id`, `file_name`, `original_name`, `uploaded_at`) VALUES
(1, 4, 'cert_69ffa7ddcb29f3.72140325.pdf', 'ShemaPrince.pdf', '2026-05-09 21:32:13');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('student','company','admin') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `account_status` enum('active','disabled') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `name`, `email`, `password`, `role`, `created_at`, `account_status`) VALUES
(1, 'Admin User', 'admin@c2c.com', '$2y$10$DadYRQmd72gydafIie/EvOVyevd7D5.oyUdSdyaobipikpT.HySaW', 'admin', '2026-05-08 19:20:56', 'active'),
(2, 'John Student', 'john@student.com', '$2y$10$p43x8MXp9t8C4wx14DF2Aexta1LkVBI0r4k6sfYxeP5HO38WeJR76', 'student', '2026-05-08 19:20:56', 'active'),
(4, 'MTN Rwanda', 'mtn@company.com', '$2y$10$AqPNmBQKRfiZPeh0bcfAtu790t7JHwAlaL51d5yaO9ghVTeVuNWka', 'company', '2026-05-08 19:20:56', 'active'),
(5, 'Irembo Ltd', 'irembo@company.com', '$2y$10$CBWNxFSysa2k0B4cGwN2bO.nNe/P3gYd9/fo5q0rxnAXh8AqZrome', 'company', '2026-05-08 19:20:56', 'active'),
(6, 'VIVANCE SHYAKA', 'vivance@gmail.com', '$2y$10$3p3J96MhJvB18g3dVrd1AOd3orQgPNjCeXeHWIfSjTJUy1jWDNh1K', 'student', '2026-05-09 18:39:13', 'active'),
(7, 'Prince SHEMA', 'prince@gmail.com', '$2y$10$dzXWB8sY0WfDag/TgINf2OlQSga0INzqRNkVZuYKbMtDJ9xTQFHTS', 'student', '2026-05-09 21:30:38', 'active'),
(8, 'RDB(Rwanda Development Board)', 'rdb@gmail.com', '$2y$10$vgwdCrLpi9RcgWrQgXJipuLeP.eAFEBka/4HSjwsk7smFRPvIZHVq', 'company', '2026-05-09 21:35:53', 'active'),
(9, 'Alice Student', 'alice@student.com', '$2y$10$ULAegO/RSs2BAoRoMZT/Ju1rAqaYgEiH4agmK2jHNZgEPVED9jx.y', 'student', '2026-05-09 22:33:43', 'active');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `applications`
--
ALTER TABLE `applications`
  ADD PRIMARY KEY (`application_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `internship_id` (`internship_id`);

--
-- Indexes for table `companies`
--
ALTER TABLE `companies`
  ADD PRIMARY KEY (`company_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `internships`
--
ALTER TABLE `internships`
  ADD PRIMARY KEY (`internship_id`),
  ADD KEY `company_id` (`company_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `student_certificates`
--
ALTER TABLE `student_certificates`
  ADD PRIMARY KEY (`certificate_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `applications`
--
ALTER TABLE `applications`
  MODIFY `application_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `companies`
--
ALTER TABLE `companies`
  MODIFY `company_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `internships`
--
ALTER TABLE `internships`
  MODIFY `internship_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `student_certificates`
--
ALTER TABLE `student_certificates`
  MODIFY `certificate_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admins`
--
ALTER TABLE `admins`
  ADD CONSTRAINT `admins_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `applications`
--
ALTER TABLE `applications`
  ADD CONSTRAINT `applications_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `applications_ibfk_2` FOREIGN KEY (`internship_id`) REFERENCES `internships` (`internship_id`) ON DELETE CASCADE;

--
-- Constraints for table `companies`
--
ALTER TABLE `companies`
  ADD CONSTRAINT `companies_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `internships`
--
ALTER TABLE `internships`
  ADD CONSTRAINT `internships_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `student_certificates`
--
ALTER TABLE `student_certificates`
  ADD CONSTRAINT `student_certificates_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

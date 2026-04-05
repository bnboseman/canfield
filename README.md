# Canfield Scientific Inc Test

Single page web application that touches on database, PHP, HTML/CSS 
and JavaScript/jQuery/AJAX. Displays a small set of movies pulled from 
a MySQL database, each with title, description and thumbs up/down count totals.
Movies can be up/down voted  and stored in the database. 
The votes should be submitted via AJAX to a middleware layer.

The database is accessed using an instance of a PHP class that manages 
the connection and provides methods for accessing the movie table. 
Initial page load is from JavaScript

Developed by B. Nichole Boseman

## Initial Setup
Create SQL Tables
```
CREATE TABLE `movies` (
`id` int NOT NULL,
`title` varchar(255) NOT NULL,
`description` text,
`image_link` varchar(500) DEFAULT NULL,
`created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `movies`
--

INSERT INTO `movies` (`title`, `description`, `image_link`, `created_at`) VALUES
('The Shawshank Redemption', 'Two imprisoned men bond over years, finding solace and eventual redemption through acts of common decency.', 'images/shawshank_redemption.jpg', '2026-04-04 11:27:48'),
('The Godfather', 'The aging patriarch of an organized crime dynasty transfers control of his empire to his reluctant son.', '/images/the_godfather.jpg', '2026-04-04 11:28:57'),
('The Dark Knight', 'Batman faces the Joker, a criminal mastermind who plunges Gotham into chaos and tests the hero’s moral limits.', '/images/the_dark_knight.jpg', '2026-04-04 11:28:57'),
('Pulp Fiction', 'The lives of two mob hitmen, a boxer, and others intertwine in a series of violent and darkly comedic stories.', 'images/pulp_fiction.jpg', '2026-04-04 11:59:04'),
('Forest Gump', 'The story of a simple man who unintentionally influences several decades of American history through his extraordinary life journey.', 'images/forest_gump.jpg', '2026-04-04 11:59:33');

CREATE TABLE `votes` (
  `id` int NOT NULL,
  `movie_id` int NOT NULL,
  `vote_type` tinyint NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `session_id` varchar(128) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Indexes for table `votes`
--
ALTER TABLE `votes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_vote` (`movie_id`,`session_id`); 
```
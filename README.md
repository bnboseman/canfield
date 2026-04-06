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
('The Shawshank Redemption', 'Two imprisoned men bond over years, finding solace and eventual redemption through acts of common decency.', 'images/shawshank_redemption.jpg'),
('The Godfather', 'The aging patriarch of an organized crime dynasty transfers control of his empire to his reluctant son.', '/images/the_godfather.jpg'),
('The Dark Knight', 'Batman faces the Joker, a criminal mastermind who plunges Gotham into chaos and tests the hero’s moral limits.', '/images/the_dark_knight.jpg'),
('Pulp Fiction', 'The lives of two mob hitmen, a boxer, and others intertwine in a series of violent and darkly comedic stories.', 'images/pulp_fiction.jpg'),
('Forest Gump', 'The story of a simple man who unintentionally influences several decades of American history through his extraordinary life journey.', 'images/forest_gump.jpg');
('Mad Max: Fury Road', 'In a post-apocalyptic wasteland, Max teams up with Furiosa to escape a tyrant in a high-speed desert chase.', 'images/mad_max_fury_road.jpg'),
('Avengers: Endgame', 'After the devastating events of Infinity War, the Avengers assemble once more to reverse Thanos\' actions and restore balance.', 'images/advengers_endgame.jpg'),
('Gladiator', 'A former Roman general seeks vengeance against the corrupt emperor who murdered his family and sent him into slavery.', 'images/gladiator.jpg'),
('The Matrix', 'A hacker discovers that reality is a simulation and joins a rebellion against the machines controlling humanity.', 'images/matrix.jpg'),
('Interstellar', 'A team of explorers travels through a wormhole in space to ensure humanity\'s survival.', 'images/intersteller.jpg'),
('Inception', 'A thief who steals corporate secrets through dream-sharing technology is given a chance to plant an idea into a target\'s mind.', 'images/inception.jpg');

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

Copy `config.sample.php` to `config.php`. Update `config.php` with your database credentials

## Recalculate Vote Counts
Upvotes and Downvotes are saved to the Movies database table, but the source of truth is the votes table. To 
recalculate vote counts run the query

```sql
UPDATE movies m
LEFT JOIN (
    SELECT 
        movie_id,
        SUM(CASE WHEN vote_type = 1 THEN 1 ELSE 0 END) AS upvotes,
        SUM(CASE WHEN vote_type = -1 THEN 1 ELSE 0 END) AS downvotes
    FROM votes
    GROUP BY movie_id
) v ON m.id = v.movie_id
SET 
    m.upvotes = COALESCE(v.upvotes, 0),
    m.downvotes = COALESCE(v.downvotes, 0);
```
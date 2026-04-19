# Canfield Scientific, Inc Test

This is a single-page web application that demonstrates integration between a MySQL 
database, PHP backend, and a JavaScript/jQuery frontend.

The application displays a list of movies with upvote and downvote functionality. 
Votes are processed via AJAX and persisted in the database without requiring a full page reload.

The database is accessed using an instance of a PHP class that manages
the connection and provides methods for accessing the movie table.
Database transactions are used when updating votes to ensure consistency
between the votes table and aggregated movie vote counts.

Validation is implemented on both the client and server side. The API ensures that only 
valid vote values (1 or -1) are accepted, prevents duplicate voting per session, and enforces 
a cooldown period to avoid abuse. Cooldown timer can be set on the backend in the config.php file.

Each vote is unique per movie and session, ensuring a user can only vote once per movie.

This ensures that a user can only have one vote per movie. If a vote is submitted again for the same movie,
the existing vote is updated instead of creating a duplicate.

If a user votes again on the same movie, the existing vote is updated rather than inserting a new record.

The application includes error handling for invalid input, duplicate voting attempts, and server-side failures, 
ensuring a consistent user experience.

Input is validated server-side to ensure only valid vote values (1 or -1) are accepted.

The seeding script generates a unique session_id for each vote to comply with the database constraint 
on (movie_id, session_id). It performs destructive operations (dropping and recreating tables) and is 
intended strictly for local development.

**It should not be included or executed in a production environment.**

Database transactions are used when updating votes to ensure consistency between the 
votes table and aggregated movie vote counts.

Developed by B. Nichole Boseman

## Initial Setup

You can run the seeding script by navigating to the migrations directory and running:
`php seed.php`

This will drop existing movie and vote tables, recreate them, and populate the database.

```
-- Movies table
CREATE TABLE `movies` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `image_link` VARCHAR(500) DEFAULT NULL,
  `upvotes` INT DEFAULT 0,
  `downvotes` INT DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed movies
INSERT INTO `movies` (`title`, `description`, `image_link`) VALUES
('The Shawshank Redemption', 'Two imprisoned men bond over years, finding solace and eventual redemption through acts of common decency.', 'images/shawshank_redemption.jpg'),
('The Godfather', 'The aging patriarch of an organized crime dynasty transfers control of his empire to his reluctant son.', 'images/the_godfather.jpg'),
('The Dark Knight', 'Batman faces the Joker, a criminal mastermind who plunges Gotham into chaos and tests the hero’s moral limits.', 'images/the_dark_knight.jpg'),
('Pulp Fiction', 'The lives of two mob hitmen, a boxer, and others intertwine in a series of violent and darkly comedic stories.', 'images/pulp_fiction.jpg'),
('Forest Gump', 'The story of a simple man who unintentionally influences several decades of American history through his extraordinary life journey.', 'images/forest_gump.jpg'),
('Mad Max: Fury Road', 'In a post-apocalyptic wasteland, Max teams up with Furiosa to escape a tyrant in a high-speed desert chase.', 'images/mad_max_fury_road.jpg'),
('Avengers: Endgame', 'After the devastating events of Infinity War, the Avengers assemble once more to reverse Thanos\' actions and restore balance.', 'images/avengers_endgame.jpg'),
('Gladiator', 'A former Roman general seeks vengeance against the corrupt emperor who murdered his family and sent him into slavery.', 'images/gladiator.jpg'),
('The Matrix', 'A hacker discovers that reality is a simulation and joins a rebellion against the machines controlling humanity.', 'images/matrix.jpg'),
('Interstellar', 'A team of explorers travels through a wormhole in space to ensure humanity\'s survival.', 'images/interstellar.jpg'),
('Inception', 'A thief who steals corporate secrets through dream-sharing technology is given a chance to plant an idea into a target\'s mind.', 'images/inception.jpg');

-- Votes table
CREATE TABLE `votes` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `movie_id` INT NOT NULL,
  `vote_type` TINYINT NOT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `session_id` VARCHAR(128) DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_vote` (`movie_id`, `session_id`),
  CONSTRAINT `fk_votes_movie`
    FOREIGN KEY (`movie_id`) REFERENCES `movies`(`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

Copy `/config/config.sample.php` to `/config/config.php`. Update `config.php` with your database credentials

## Running the Application

1. Configure your database in `config.php`
2. Run the migration/seeding script:
   php migrations/seed.php
3. Start your local server (e.g. Apache or PHP built-in server)
4. Open the application in your browser

## Recalculate Vote Counts
Vote data is stored in the votes table, and totals are reflected in the movies table.
To recalculate vote counts run the query

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
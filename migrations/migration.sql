-- Drop tables if they exist (safe reset)
DROP TABLE IF EXISTS votes;
DROP TABLE IF EXISTS movies;

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
('Avengers: Endgame', 'After the devastating events of Infinity War, the Avengers assemble once more to reverse Thanos\' actions and restore balance.', 'images/advengers_endgame.jpg'),
('Gladiator', 'A former Roman general seeks vengeance against the corrupt emperor who murdered his family and sent him into slavery.', 'images/gladiator.jpg'),
('The Matrix', 'A hacker discovers that reality is a simulation and joins a rebellion against the machines controlling humanity.', 'images/matrix.jpg'),
('Interstellar', 'A team of explorers travels through a wormhole in space to ensure humanity\'s survival.', 'images/intersteller.jpg'),
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

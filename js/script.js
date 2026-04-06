$(function () {
    // track diabled movies
    const voteCooldown = {};

    // disable voting on a movie for 30 seconds after vote
    const COOLDOWN_TIME = 30000;

    /**
     *  Update the error bar
     * @param message
     */
    function showError(message) {
        const errorElement = $('#error');
        errorElement.text(message);
        errorElement.show();
    }

    /**
     * Clear the errors
     */
    function clearError() {
        $('#error').hide().text('');
    }

    /**
     * Shows all the Movies
     * @param movies
     */
    function renderMovies(movies) {
        const container = $('.movies');
        container.empty();

        const now = Date.now();
        movies.forEach(movie => {
        const isCoolingDown = voteCooldown[movie.id] && voteCooldown[movie.id] > now;


            const html = `
    <div 
        class="movie ${isCoolingDown ? 'disabled' : ''}" 
        id="movie_${movie.id}"
        aria-labelledby="movie_title_${movie.id}"
    >
        <div class="title">
            <h2 id="movie_title_${movie.id}">${movie.title}</h2>
        </div>

        <img 
            src="${movie.image_link}" 
            alt="Poster for ${movie.title}" 
            class="poster" 
        />
        
        <div class="description">
            <p>${movie.description}</p>
        </div> 
        
        <div class="ranking" aria-live="polite">
            <span class="upvotes">
                Upvotes: ${movie.upvotes}
            </span>
            <span class="downvotes">
                Downvotes: ${movie.downvotes}
            </span>
        </div>

        <!-- Cooldown status -->
        <div class="cooldown-status" id="cooldown_${movie.id}">
            <span class="visible-timer">
                ${isCoolingDown ? `(${Math.ceil((voteCooldown[movie.id] - Date.now()) / 1000)}s)` : ''}
            </span>
            <span class="sr-only" aria-live="polite">
                ${isCoolingDown 
                ?`You can vote again in ${Math.ceil((voteCooldown[movie.id] - Date.now()) / 1000)} seconds for ${movie.title}` 
                : ''
            }
            </span>
        </div>

        <div class="actions">
            <button 
                class="upvote button" 
                data-movie-id="${movie.id}" 
                data-vote="1"
                aria-label="Upvote ${movie.title}"
                ${isCoolingDown ? 'disabled aria-disabled="true"' : ''}
            >
                <svg 
                    class="icon" 
                    xmlns="http://www.w3.org/2000/svg" 
                    viewBox="0 0 640 640"
                    aria-hidden="true"
                >
                    <path d="M144 224C161.7 224 176 238.3 176 256L176 512C176 529.7 161.7 544 144 544L96 544C78.3 544 64 529.7 64 512L64 256C64 238.3 78.3 224 96 224L144 224zM334.6 80C361.9 80 384 102.1 384 129.4L384 133.6C384 140.4 382.7 147.2 380.2 153.5L352 224L512 224C538.5 224 560 245.5 560 272C560 291.7 548.1 308.6 531.1 316C548.1 323.4 560 340.3 560 360C560 383.4 543.2 402.9 521 407.1C525.4 414.4 528 422.9 528 432C528 454.2 513 472.8 492.6 478.3C494.8 483.8 496 489.8 496 496C496 522.5 474.5 544 448 544L360.1 544C323.8 544 288.5 531.6 260.2 508.9L248 499.2C232.8 487.1 224 468.7 224 449.2L224 262.6C224 247.7 227.5 233 234.1 219.7L290.3 107.3C298.7 90.6 315.8 80 334.6 80z"/>
                </svg>
            </button>

            <button 
                class="downvote button" 
                data-movie-id="${movie.id}" 
                data-vote="-1"
                aria-label="Downvote ${movie.title}"
                ${isCoolingDown ? 'disabled aria-disabled="true"' : ''}
            >
                <svg 
                    class="icon" 
                    xmlns="http://www.w3.org/2000/svg" 
                    viewBox="0 0 640 640"
                    aria-hidden="true"
                >
                    <path d="M448 96C474.5 96 496 117.5 496 144C496 150.3 494.7 156.2 492.6 161.7C513 167.2 528 185.8 528 208C528 217.1 525.4 225.6 521 232.9C543.2 237.1 560 256.6 560 280C560 299.7 548.1 316.6 531.1 324C548.1 331.4 560 348.3 560 368C560 394.5 538.5 416 512 416L352 416L380.2 486.4C382.7 492.7 384 499.5 384 506.3L384 510.5C384 537.8 361.9 559.9 334.6 559.9C315.9 559.9 298.8 549.3 290.4 532.6L234.1 420.3C227.4 407 224 392.3 224 377.4L224 190.8C224 171.4 232.9 153 248 140.8L260.2 131.1C288.6 108.4 323.8 96 360.1 96L448 96zM144 160C161.7 160 176 174.3 176 192L176 448C176 465.7 161.7 480 144 480L96 480C78.3 480 64 465.7 64 448L64 192C64 174.3 78.3 160 96 160L144 160z"/>
                </svg>
            </button>
        </div>
    </div>
`;

            container.append(html);
        });
    }

    /**
     * Initial Load of the movies from PHP
     */
    function loadMovies() {
        $.ajax({
            url: '/api/movies.php',
            method: 'GET',
            dataType: 'json',
            success: function (response) {
                renderMovies(response.data);
                clearError();
            },
            error: function (response) {
                console.error(response.responseJSON);
                showError(response.responseJSON.error);
            }
        });
    }

    $(document).on('click', '.button', function() {
        const button = $(this)
        const movieId = button.data('movie-id');
        const container = button.closest('.movie');

        if (voteCooldown[movieId] && Date.now() < voteCooldown[movieId]) {
            const title = container.find('h2').text();
            showError('Please wait before voting for ' + title + ' again');
            return;
        }
        let voteType = button.data('vote');

        // Sanitize voteType
        voteType = voteType == -1  ? -1 : 1;

        // Disable buttons for the movie voted

        container.addClass('disabled');

        $.ajax({
            url: '/api/movies.php',
            method: 'POST',
            dataType: 'json',
            data: {
                id: movieId,
                vote_type: voteType
            },
            success: function(response) {
                if (response.success) {
                    clearError();
                    voteCooldown[movieId] = Date.now() + COOLDOWN_TIME;
                    renderMovies(response.data);

                    setTimeout(() => {
                        delete voteCooldown[movieId];
                        loadMovies(); // re-render to re-enable
                    }, COOLDOWN_TIME);
                } else {
                    console.error(response.responseJSON);
                    showError(response.responseJSON.error);
                    container.removeClass('disabled');
                }
            },
            error: function (response) {
                    console.error(response);

                    let message = 'Something went wrong';

                    if (response.responseJSON && response.responseJSON.error) {
                        message = response.responseJSON.error;
                    } else if (response.responseText) {
                        try {
                            const parsed = JSON.parse(response.responseText);
                            if (parsed.error) {
                                message = parsed.error;
                            }
                        } catch (e) {
                            message = response.responseText;
                        }
                    }

                    showError(message);
                    container.removeClass('disabled');
            }
        })
    });

    loadMovies();
})
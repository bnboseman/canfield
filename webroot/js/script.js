$(document).ready(page_loaded);

function page_loaded() {
    var movies = {};
    const movie_container = $('#movies_container');
    const error_container = $('#error_container');

    loadMovies();
    console.log(movies);

    function loadMovies() {
        $.ajax({
            url: '/api/movies.php',
            method: 'GET',
            dataType: 'json',
            success: function (response) {
                Object.values(response.data).forEach(movie => {
                    new Movie({
                        ...movie
                    });
                });
            },
            error: function (response) {
                console.error(response);
                showError(response.responseJSON?.error || 'Something went wrong');
            }
        });
    }

    function renderMovies() {
        console.log(movies);
    }

    function showError(message) {
        error_container.text(message);
        error_container.show();
    }

    function clearError() {
        error_container.hide().text('');
    }
    function Movie(params) {
        var movie = this;
        var $movie;
        var voteCooldown;


        init();

        function init() {
            const now = Date.now();

            $movie = $('<div>')
                .addClass('movie')
                .appendTo(movie_container);


            const $title = $('<div>').addClass('title').appendTo($movie);
            $('<h2>').text(params.title).appendTo($title);

            $('<img>')
                .addClass('poster')
                .attr({
                    src: params.image_link,
                    alt: `Poster for ${params.title}`
                })
                .appendTo($movie);

            $('<div>')
                .addClass('description')
                .append($('<p>').text(params.description))
                .appendTo($movie);

            const $ranking = $('<div>')
                .addClass('ranking')
                .attr('aria-live', 'polite')
                .appendTo($movie);

            $('<span>')
                .addClass('upvotes')
                .text(`Upvotes: ${params.upvotes}`)
                .appendTo($ranking);

            $('<span>')
                .addClass('downvotes')
                .text(`Downvotes: ${params.downvotes}`)
                .appendTo($ranking);

            const $actions = $('<div>').addClass('actions').appendTo($movie);

            createButton($actions, 1, 'Upvote', voteCooldown);
            createButton($actions, -1, 'Downvote', voteCooldown);
        }

        function createButton($container, voteType, label, disabled) {
            $('<button>')
                .addClass(`${voteType === 1 ? 'upvote' : 'downvote'} button`)
                .attr('aria-label', `${label} ${params.title}`)
                .prop('disabled', disabled)
                .text(voteType === 1 ? 'Upvote' : 'Downvote')
                .attr('aria-disabled', disabled)
                .on('click', function () {
                    handleVote(voteType);
                })
                .appendTo($container);
        }

        function handleVote(voteType) {
            console.log('Vote:', params.id, voteType);
            vote(voteType);
        }

        function vote(voteType) {
            voteType === -1 ? -1 : 1;
            $movie.addClass('disabled');

            $.ajax({
                url: '/api/movies.php',
                method: 'POST',
                dataType: 'json',
                data: {
                    id: params.id,
                    vote_type: voteType
                },
                success: function (response) {
                    if (response.success) {
                        clearError();
                        voteCooldown = Date.now() + window.APP.timeout;
                        renderMovies(response.data);

                        setTimeout(() => {
                            delete voteCooldown;
                            loadMovies(); // re-render to re-enable
                        }, COOLDOWN_TIME);
                    } else {
                        console.error(response);
                        showError(response.error || 'Something went wrong');
                        $movie.removeClass('disabled');
                    }
                },
                error: function (response) {
                    console.error(response.responseJSON);
                    showError(response.responseJSON?.error || 'Something went wrong');
                    $movie.removeClass('disabled');
                }
            })
        }
    }
}
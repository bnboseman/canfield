$(document).ready(page_loaded);

function page_loaded() {
    var movies = {};
    const movie_container = $('#movies_container');
    const error_container = $('#error_container');

    loadMovies();

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

    function showError(message) {
        error_container.show().text(message);
    }

    function clearError() {
        error_container.hide().text('');
    }

    function Movie(params) {
        var $movie;
        var voteCooldownTimer;
        var $buttons = $();
        var $actions;
        var $upvotes;
        var $downvotes;

        init();

        function init() {

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

            $upvotes = $('<span>')
                .addClass('upvotes')
                .text(`Upvotes: ${params.upvotes}`)
                .appendTo($ranking);

            $downvotes = $('<span>')
                .addClass('downvotes')
                .text(`Downvotes: ${params.downvotes}`)
                .appendTo($ranking);

            $actions = $('<div>').addClass('actions').appendTo($movie);

            createButton($actions, 1, 'Upvote');
            createButton($actions, -1, 'Downvote');
        }

        function createButton($container, voteType, label) {
            const $btn = $('<button>')
                .addClass(`${voteType === 1 ? 'upvote' : 'downvote'} button`)
                .attr('aria-label', `${label} ${params.title}`)
                .text(label)
                .on('click', function () {
                    vote(voteType);
                })
                .appendTo($container);

            $buttons = $buttons.add($btn);
        }

        function disableButtons() {
            $buttons.prop('disabled', true).attr('aria-disabled', true).addClass('disabled');
            $actions.addClass('disabled')

        }

        function enableButtons() {
            $buttons.prop('disabled', false).attr('aria-disabled', false).removeClass('disabled');
            $actions.removeClass('disabled')
        }

        function vote(voteType) {
            voteType = voteType === -1 ? -1 : 1;
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
                        disableButtons();
                        voteCooldownTimer = Date.now() + window.APP.timeout;

                        setTimeout(() => {
                            enableButtons();
                            voteCooldownTimer = null;
                        }, window.APP.timeout);

                        params.upvotes = response.data.movie.upvotes;
                        params.downvotes = response.data.movie.downvotes;

                        $upvotes.text(`Upvotes: ${params.upvotes}`);
                        $downvotes.text(`Downvotes: ${params.downvotes}`);
                    } else {
                        console.error(response);
                        showError(response.error || 'Something went wrong');
                        enableButtons();
                    }
                },
                error: function (response) {
                    console.error(response.responseJSON);
                    showError(response.responseJSON?.error || 'Something went wrong');
                    enableButtons();
                }
            })
        }
    }
}
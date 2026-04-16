function Movie(params) {
    var movie = this;
    var $movie;

    init();

    function init() {
        const now = Date.now();
        const isCoolingDown = voteCooldown[params.id] && voteCooldown[params.id] > now;

        $movie = $('<div>')
            .addClass('movie')
            .toggleClass('disabled', isCoolingDown)
            .appendTo(params.$container);

        // Title
        const $title = $('<div>').addClass('title').appendTo($movie);
        $('<h2>').text(params.title).appendTo($title);

        // Image
        $('<img>')
            .addClass('poster')
            .attr({
                src: params.image_link,
                alt: `Poster for ${params.title}`
            })
            .appendTo($movie);

        // Description
        $('<div>')
            .addClass('description')
            .append($('<p>').text(params.description))
            .appendTo($movie);

        // Ranking
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

        // Cooldown
        renderCooldown(isCoolingDown);

        // Actions
        const $actions = $('<div>').addClass('actions').appendTo($movie);

        createButton($actions, 1, 'Upvote', isCoolingDown);
        createButton($actions, -1, 'Downvote', isCoolingDown);
    }

    function createButton($container, voteType, label, disabled) {
        $('<button>')
            .addClass(`${voteType === 1 ? 'upvote' : 'downvote'} button`)
            .attr('aria-label', `${label} ${params.title}`)
            .prop('disabled', disabled)
            .attr('aria-disabled', disabled)
            .on('click', function () {
                handleVote(voteType);
            })
            .appendTo($container);
    }

    function handleVote(voteType) {
        console.log('Vote:', params.id, voteType);

        // your existing vote logic here
        // vote(params.id, voteType);

        voteCooldown[params.id] = Date.now() + 30000;
    }

    function renderCooldown(isCoolingDown) {
        const remaining = isCoolingDown
            ? Math.ceil((voteCooldown[params.id] - Date.now()) / 1000)
            : 0;

        const $cooldown = $('<div>')
            .addClass('cooldown-status')
            .appendTo($movie);

        $('<span>')
            .addClass('visible-timer')
            .text(isCoolingDown ? `(${remaining}s)` : '')
            .appendTo($cooldown);

        $('<span>')
            .addClass('sr-only')
            .attr('aria-live', 'polite')
            .text(
                isCoolingDown
                    ? `You can vote again in ${remaining} seconds for ${params.title}`
                    : ''
            )
            .appendTo($cooldown);
    }
}
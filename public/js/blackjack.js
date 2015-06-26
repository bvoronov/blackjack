$(function() {
    var gameTable = $('#gametable');
    var dealerHand = $('#dealerContainer');
    var playerHand = $('#playerContainer');
    var messageBox = $('#messageBox').hide();
    var historyBox = $('#historyBox').hide();
    var statsContainer = $('#stats');

    // process current state
    updateState({
        code: gameTable.data('state-code'),
        title: gameTable.data('state-title'),
        text: gameTable.data('state-text')
    });

    $('body').on('click', 'input[type=submit]', function() {
        $.post($(this).data('url'), function (response) {
            if (response.error) {
                console.log(response.error);
                return ;
            }

            updateHand(dealerHand, response.dealer);
            updateHand(playerHand, response.player);
            updateState(response.state);
            updateStats(response.stats);
        }, 'json');

        return false;
    });

    $('body').on('click', 'a.reset-link', function() {
        $.post($(this).data('url'), function (response) {
            if (response.error) {
                console.log(response.error);
                return ;
            }

            resetHand(dealerHand);
            resetHand(playerHand);
            updateState(response.state);
        }, 'json');

        return false;
    });

    $('body').on('click', 'a.history-link', function() {
        $.post($(this).data('url'), function (response) {
            updateHistory(response.html);
        }, 'json');

        return false;
    });

    $('body').on('click', 'a.history-close', function() {
        historyBox.hide();
        messageBox.show();

        return false;
    });

    function updateHistory(html)
    {
        messageBox.hide();
        historyBox.html(html);
        historyBox.show();
    }

    function updateState(state)
    {
        historyBox.hide();
        if (!state.title) {
            messageBox.hide();
            return ; // continue to play
        }

        // show notification message
        messageBox.find('.messageTitle').text(state.title);
        messageBox.find('.messageText').text(state.text);
        messageBox.show();
    }

    function updateStats(stats)
    {
        statsContainer.find('.wins').text(stats.wins + ' (' + stats.pwins + '%)');
        statsContainer.find('.looses').text(stats.looses + ' (' + stats.plooses + '%)');
        statsContainer.find('.draws').text(stats.draws + ' (' + stats.pdraws + '%)');
    }

    function updateHand($container, data) {
        $container.find('.card').remove();
        $container.find('.score').text(data.scores);

        $.each(data.cards, function(index, card) {
            $container.append(
                $('<div></div>')
                    .addClass('card')
                    .addClass('card-' + card[0])
                    .addClass('card-loc' + index)
            );
        });
    }

    function resetHand($container)
    {
        $container.find('.card').remove();
        $container.find('.score').text(0);
    }
});

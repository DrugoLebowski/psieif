(function () {
    var modal           = $('#modal'),
        modalTitle      = $('#modal-title-content'),
        modalContent    = $('#modal-text-content'),
        modalButton     = $('#modal-button');

    var Endpoints = {
        hash: '/hash/content?'
    };

    var Requester = (function () {
        return {
            hashContent: function (link) {
                var request = $.ajax({
                    url: '/hash/content?XDEBUG_SESSION_START',
                    method: 'post',
                    data: {
                        link: link
                    }
                });

                request.done(function (response) {
                    console.log(JSON.stringify(response));

                    // TODO: Manage the response
                });

                request.fail(function (error) {
                    var jResponse = error.responseJSON;

                    modalTitle.text('Error!');
                    modalButton.addClass('btn-danger');
                    switch (jResponse.code) {
                        case '@access_token_not_valid_error':
                            modalContent.text(jResponse.data.message);
                            modalButton.text('Returns to homepage.');
                            modal.on('click', function (e) {
                                location.href = '/';
                            });
                            break;
                        case '@invalid_uri_error':
                        case '@facebook_data_fetching_error':
                        case '@not_a_page_error':
                            modalContent.text(jResponse.data.message);
                            modalButton.text('Ok.');
                            modalButton.on('click', function (e) {
                                modal.modal('hide');
                            });
                            break;
                        default:
                            modalTitle.text('Unrecognized error!');
                            modalContent.text('It has happened an error that our monkeys cannot recognize.');
                            modalButton.text('mmm \'kay...');
                            modalButton.on('click', function (e) {
                                modal.modal('hide');
                            })
                    }

                    modal.modal('show');
                });
            }
        }
    }());

    $('button#check').on('click', function () {
        var link = $('#link').val(),
            requester = new Requester();

        if (!link) {
            modalTitle.text('Warning!');
            modalContent.text('The link cannot be empty.');
            modalButton.addClass('btn-warning').text('Ok');
            modal.modal('show');
        } else {
            requester.hashContent(link);
        }
    });
}());
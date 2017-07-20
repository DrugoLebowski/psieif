$(document).ready(function () {
    /**
     * ################ DECLARING COMPONENTS ################
     */

    // Contains the ref of the info modal and the inner components
    var modal           = $('#modal'),
        modalTitle      = $('#modal-title-content'),
        modalContent    = $('#modal-text-content'),
        modalButton     = $('#modal-button');

    // Contains the ref the login form
    var modalLogin      = $('#modal-login');

    var panel           = $('#panel');
    var checkButton     = $('button#checkBtn');
    var validateButton  = $('button#validateBtn');
    var linkTextInput   = $('input#link');
    var codeTextInput   = $('input#code');
    var hashTextInput   = $('input#hash');
    var hashFormGroup   = $('div#hasher > .form-group');
    var validateFormGroup = $('div#validate > .form-group');
    var linkIconSpan    = $('span#linkIcon');
    var tipsDiv         = $('div#tips');
    var hasherResultDiv = $('div#result');

    var progress        = $('div#progress'),
        progressComplete= $('div#progress .complete'),
        progressBar     = $('div#progress .progress-bar');

    var pollerResource = "";

    /**
     * ################ DECLARING MODULES ################
     */

    // Manages the request to the server
    var Requester = (function () {

        // Contains the endpoints of the app
        var Endpoints = {
            hash:       '/handler?XDEBUG_SESSION_START',
            validate:   '/validate?XDEBUG_SESSION_START',
            poller:     '/poller?XDEBUG_SESSION_START'
        };

        var errorHandler = function (e) {
            var jResponse = e.responseJSON;

            modalTitle.text('Error!');
            modalButton.addClass('btn-danger');
            modalButton.text('Ok.');
            modalButton.on('click', function (e) {
                modal.modal('hide');
            });

            switch (jResponse.code) {
                case '@access_token_not_valid_error':
                    Facebook.checkLoginState();
                    modalTitle.text('Attention!');
                    modalButton.addClass('btn-warning');
                    modalButton.text('Retry');
                    modalContent.text(jResponse.data.message);
                    modalButton.on('click', function (e) {
                       checkButton.click();
                       modal.modal('hide');
                    });
                    break;
                case '@invalid_uri_error':
                case '@facebook_data_fetching_error':
                case '@not_a_page_error':
                    modalContent.text(jResponse.data.message);
                    break;
                default:
                    modalTitle.text('Unrecognized error!');
                    modalContent.text('It has happened an error that our monkeys cannot recognize.');
                    modalButton.text('mmm \'kay...');
            }

            modal.modal('show');
        };

        return {
            hashContent: function (link) {
                pollerResource = md5(link + '_' + (new Date()).getTime());
                progress.fadeIn(100);

                var intervalId     = setInterval(this.poller, 1000),
                    request        = $.ajax({
                        url: Endpoints.hash,
                        method: 'post',
                        timeout: 360000,
                        data: {
                            link: link,
                            'hash_link': pollerResource
                        }
                    });

                request.done(function (response) {
                    var parsedRes   = JSON.parse(response),
                        resultOwner = $('#result_owner'),
                        resultDate  = $('#result_creation-date'),
                        resultCode  = $('#result_code'),
                        resultHash  = $('#result_hash'),
                        resultBut   = $('#result_download');

                    clearInterval(intervalId);
                    progressBar.css('width', '100%');
                    progressComplete.fadeIn(200);

                    setTimeout(function () {
                        progress.fadeOut(0);
                        progressComplete.fadeOut(0);
                        progressBar.css('width', '0%');
                        resultOwner.text(parsedRes.data.owner);
                        resultDate.text(parsedRes.data.date);
                        resultCode.text(parsedRes.data.code);
                        resultHash.text(parsedRes.data.hash);
                        resultBut.attr('href', parsedRes.data.location);
                        hasherResultDiv.fadeIn(400);
                    }, 1000);
                });

                request.fail(function (e) {
                    progress.fadeOut(200);
                    clearInterval(intervalId);
                    errorHandler(e);
                });
            },
            validateHash: function (code, hash) {
                var request = $.ajax({
                    url: Endpoints.validate,
                    method: 'get',
                    data: {
                        code: code,
                        hash: hash
                    }
                });

                request.done(function (response) {
                    var parsedRes = JSON.parse(response);

                    Util.removeClass(modalButton, /btn\-(success|danger){1}/g);

                    modalTitle.text("Success!");
                    modalContent.text(parsedRes.data.message);
                    modalButton
                        .text('Close')
                        .addClass('btn-success')
                        .on('click', function () {
                            modal.modal('hide');
                        });
                    modal.modal('show');
                });

                request.fail(errorHandler);
            },
            poller: function () {
                var request = $.ajax({
                    url:    Endpoints.poller,
                    method: 'post',
                    data: {
                        resource: pollerResource
                    }
                });


                request.done(function (response) {
                    var jResponse = JSON.parse(response);
                    progressBar.css('width', jResponse.data.global_progress + '%');
                });

                request.fail(function (error) {
                    console.log(JSON.stringify(error));
                })
            }
        }
    }());

    // Contains a set of utility functions
    var Util = (function() {

        /**
         * Removes from an element a class that matches the test
         *
         * @param element   A jQuery element
         * @param test      A RegExp test
         */
        var removeClass = function (element, test) {
            var matches = test.exec(element.attr('class'));
            if (matches && element.hasClass(matches[0])) {
                element.removeClass(matches[0]);
            }
        };

        /**
         * Removes from an element all the classes that match the tests
         *
         * @param elements  An array of jQuery element
         * @param tests     An array of RegExp test
         */
        var removeClassesFromElements = function (elements, tests) {
            if (elements.length === tests.length) {
                elements.forEach(function (e, i) {
                    tests[i].forEach(function (ts) {
                        removeClass(e, ts);
                    });
                });
            } else {
                console.log("removeClassesFromElements: Number mismatch");
            }
        };

        /**
         * Adds to an element the class `c`
         *
         * @param element   A jQuery element
         * @param c         A class as string
         */
        var addClass = function (element, c) {
            element.addClass(c);
        };

        /**
         * Adds to every elements the associated classes.
         *
         * @param elements
         * @param classes
         */
        var addClassesToElements = function (elements, classes) {
            if (elements.length === classes.length) {
                elements.forEach(function (e, i) {
                    classes[i].forEach(function (c) {
                        e.addClass(c);
                    });
                });
            } else {
                console.log("addClassesToElements: number mismatch");
            }
        };

        return {
            addClass: addClass,
            addClassesToElements: addClassesToElements,
            removeClass: removeClass,
            removeClassesFromElements: removeClassesFromElements
        }
    }());

    // Manages the validation of this app
    var Validator = (function () {
        var pattern = /^((https|http):\/\/)\n?((www|m|mbasic)\.facebook\.com)(\/((([a-zA-Z0-9.]+)(\/(((posts|videos)\/\d{1,})|((photos){1}\/a\.\d{1,}\.\d{1,}\.\d{1,}\/\d{1,}))))|(photo\.php)|(permalink\.php\?story_fbid=\d{1,}&id=\d{1,})))/;

        return {
            isFacebookUrlValid: function (url) {
                return pattern.test(url);
            }
        };
    }());


    /**
     * ################ APPLICATION ################
     */
    linkTextInput.on('change', function (e) {
        var text = linkTextInput.val();

        Util.removeClassesFromElements(
            [hashFormGroup, checkButton, linkIconSpan],
            [
                [/has\-(success|error)+/g],
                [/btn\-(success|danger)+/g],
                [/glyphicon\-(ok|remove)+/g]
            ]
        );

        if (Validator.isFacebookUrlValid(text)) {
            Util.addClassesToElements(
                [hashFormGroup, checkButton, linkIconSpan],
                [
                    ['has-success'],
                    ['btn-success'],
                    ['glyphicon-ok']
                ]
            );
            tipsDiv.hide("fast");
        } else {
            Util.addClassesToElements(
                [hashFormGroup, checkButton, linkIconSpan],
                [
                    ['has-error'],
                    ['btn-danger'],
                    ['glyphicon-remove']
                ]
            );
            tipsDiv.show("fast");
        }
    });

    checkButton.on('click', function () {
        var link = linkTextInput.val();

        hasherResultDiv.hide();

        // Checks if the Facebook cookie is present
        if (Cookies.readCookie('fbsr_' + Facebook.appId) &&
            Cookies.readCookie('at')) {
            // Checks if the facebook link is valid
            if (!Validator.isFacebookUrlValid(link)) {
                modalTitle.text('Warning!');
                modalContent.text('The link does not respect the rules.');
                modalButton
                    .addClass('btn-warning')
                    .text('Ok')
                    .on('click', function () {
                       modal.modal('hide');
                    });
                modal.modal('show');
            } else {
                Requester.hashContent(link);
            }
        } else {
            modalLogin.modal('show');
        }
    });

    validateButton.on('click', function () {
       var code = codeTextInput.val() || '',
           hash = hashTextInput.val() || '';


       // Checks if code and hash are present
       if (code && hash) {
           Requester.validateHash(code, hash);
       } else {
           modalTitle.text('Warning!');
           modalContent.text('There is a problem with the validation input data. Have you inserted all?.');
           modalButton
               .addClass('btn-warning')
               .text('Ok')
               .on('click', function () {
                   modal.modal('hide');
               });
           modal.modal('show');
       }
    });

}());
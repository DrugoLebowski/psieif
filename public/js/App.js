(function () {

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
    var checkButton     = $('button#check');
    var linkTextinput   = $('input#link');
    var hashFormGroup   = $('div.form-group');
    var linkIconSpan    = $('span#linkIcon');
    var tipsDiv         = $('div#tips');

    /**
     * ################ DECLARING MODULES ################
     */

    // Manages the request to the server
    var Requester = (function () {

        // Contains the endpoints of the app
        var Endpoints = {
            hash: '/hash?XDEBUG_SESSION_START'
        };

        var errorHandler = function (e) {
            var jResponse = e.responseJSON;

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
        };

        return {
            hashContent: function (link) {
                var request = $.ajax({
                    url: Endpoints.hash,
                    method: 'post',
                    data: {
                        link: link
                    }
                });

                request.done(function (response) {
                    console.log(JSON.stringify(response));

                    // TODO: Manage the response
                });

                request.fail(errorHandler);
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
        var pattern = /^((https|http):\/\/)\n?((www|m|mbasic)\.facebook\.com)(\/((([a-zA-Z0-9.]+)(\/(((posts|videos)\/\d{1,})|((photos){1}\/a\.\d{1,}\.\d{1,}\/\d{1,}))))|(photo\.php)|(permalink\.php\?story_fbid=\d{1,}&id=\d{1,})))/;

        return {
            isFacebookUrlValid: function (url) {
                return pattern.test(url);
            }
        };
    }());


    /**
     * ################ APPLICATION ################
     */
    linkTextinput.on('change', function (e) {
        var text = linkTextinput.val();

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
        var link = linkTextinput.val();

        // Checks if the Facebook cookie is present
        if (Cookies.readCookie('fbsr_' + Facebook.appId)) {
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
}());
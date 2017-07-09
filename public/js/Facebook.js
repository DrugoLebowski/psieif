var Facebook = {
    appId: '1536013056471626',
    checkLoginState: function () {
        FB.getLoginStatus(function (response) {
            if (response.status === "connected"
                && response.authResponse !== null
                && response.authResponse.accessToken !== null
                && response.authResponse.userID !== null) {
                Cookies.createCookie('at', response.authResponse.accessToken, 0.08333333333);
                $('#modal-login').modal('hide');
            } else if (response.status === "not_authorized") {
                console.log("Not authorized");
                // TODO: Show a modal error
            } else {
                console.log("Unknown");
            }
        });
    }
};

// Load the SDK asynchronously
(function (d, s, id) {
    var js;
    var fjs = d.getElementsByTagName(s)[0];
    if (d.getElementById(id)) {
        return;
    }
    js = d.createElement(s);
    js.id = id;
    js.src = "//connect.facebook.net/en_US/sdk.js";
    fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));

window.fbAsyncInit = function () {
    FB.init({
        appId: Facebook.appId,
        cookie: true,  // enable cookies to allow the server to access
                       // the session
        xfbml: true,  // parse social plugins on this page
        version: 'v2.9' // use graph api version 2.8
    });

    Facebook.checkLoginState();
};
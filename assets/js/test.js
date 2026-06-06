const config = {
    apiUrl: '/wp-json/hippoo-auth/v1/social-login',
    google: {
        clientId: hippooAuthConfig.google.clientId
    },
    facebook: {
        appId: hippooAuthConfig.facebook.appId
    },
    apple: {
        clientId: hippooAuthConfig.apple.clientId,
        redirectUri: window.location.origin
    }
};
const googleLoginBtn = document.getElementById('google-login');
const facebookLoginBtn = document.getElementById('facebook-login');
const appleLoginBtn = document.getElementById('apple-login');
const logoutBtn = document.getElementById('logout-btn');
const userInfoDiv = document.getElementById('user-info');
const userNameSpan = document.getElementById('user-name');
const userEmailSpan = document.getElementById('user-email');
const infoMessageDiv = document.getElementById('info-message');

let currentUser = null;

function initializeSDKs() {
    window.fbAsyncInit = function() {
        FB.init({
            appId: config.facebook.appId,
            cookie: true,
            xfbml: true,
            version: 'v22.0'
        });

        FB.AppEvents.logPageView();
        
        FB.getLoginStatus(function(response) {
            if (response.status === 'connected') {
                handleFacebookResponse(response);
            }
        });
    };
}

// Google
function initializeGoogleLogin() {
    googleLoginBtn.addEventListener('click', () => {
        const client = google.accounts.oauth2.initTokenClient({
            client_id: config.google.clientId,
            scope: 'email profile',
            callback: (tokenResponse) => {
                if (tokenResponse && tokenResponse.access_token) {
                    verifyGoogleToken(tokenResponse.access_token);
                }
            }
        });
        client.requestAccessToken();
    });
}

async function verifyGoogleToken(token) {
    try {
        const response = await fetch(config.apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ provider: 'google', token: token })
        });
        
        const data = await response.json();
        
        if (data.token) {
            handleLoginSuccess(data.user);
        } else {
            showError('Google login failed: ' + (data.message || 'Unknown error'));
        }
    } catch (error) {
        showError('Google login error: ' + error.message);
    }
}

// Facebook
function initializeFacebookLogin() {
    facebookLoginBtn.addEventListener('click', () => {
        FB.login(function(response) {
            handleFacebookResponse(response);
        }, { scope: 'email,public_profile' });
    });
}

function handleFacebookResponse(response) {
    if (response.status === 'connected') {
        verifyFacebookToken(response.authResponse.accessToken);
    } else {
        showError('Facebook login failed or was cancelled');
    }
}

async function verifyFacebookToken(token) {
    try {
        const response = await fetch(config.apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ provider: 'facebook', token: token })
        });
        
        const data = await response.json();
        
        if (data.token) {
            handleLoginSuccess(data.user);
        } else {
            showError('Facebook login failed: ' + (data.message || 'Unknown error'));
        }
    } catch (error) {
        showError('Facebook login error: ' + error.message);
    }
}

// Apple
function initializeAppleLogin() {
    if (!window.AppleID) {
        console.warn('Apple ID SDK not loaded');
        return;
    }
    
    AppleID.auth.init({
        clientId: config.apple.clientId,
        scope: 'name email',
        redirectURI: config.apple.redirectUri,
        usePopup: true
    });
    
    appleLoginBtn.addEventListener('click', async () => {
        try {
            const data = await AppleID.auth.signIn();
            
            if (data.authorization && data.authorization.id_token) {
                verifyAppleToken(data.authorization.id_token);
            } else {
                showError('Apple login failed - no token received');
            }
        } catch (error) {
            showError('Apple login error: ' + error.message);
        }
    });

    // https://www.tech-prastish.com/blog/apple-sign-in-in-a-php-website/
    // https://github.com/aaronpk/sign-in-with-apple-example/blob/master/index.php
    // https://developer.apple.com/documentation/signinwithapplerestapi/generate_and_validate_tokens
    // https://pieces.app/blog/integrating-the-sign-in-with-apple-feature-into-your-website
    // http://developer.apple.com/documentation/signinwithapplerestapi/request-an-authorization-to-the-sign-in-with-apple-server.
}

async function verifyAppleToken(token) {
    try {
        const response = await fetch(config.apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ provider: 'apple', token: token })
        });
        
        const data = await response.json();
        
        if (data.token) {
            handleLoginSuccess(data.user);
        } else {
            showError('Apple login failed: ' + (data.message || 'Unknown error'));
        }
    } catch (error) {
        showError('Apple login error: ' + error.message);
    }
}

// Functions
function handleLoginSuccess(userData) {
    currentUser = userData;
    userNameSpan.textContent = userData.name || userData.email.split('@')[0];
    userEmailSpan.textContent = userData.email;
    userInfoDiv.style.display = 'block';
    infoMessageDiv.textContent = JSON.stringify({ userData });
}

function showError(message) {
    infoMessageDiv.textContent = message;
    setTimeout(() => {
        infoMessageDiv.textContent = '';
    }, 5000);
}

// DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    initializeSDKs();
    initializeGoogleLogin();
    initializeFacebookLogin();
    initializeAppleLogin();
    initializeLogout();
});
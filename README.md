# twitter-login-with-ajax

## Setup

```
$ git clone https://github.com/franmomu/twitter-login.git
$ cd twitter-login
$ composer install
$ TWITTER_CONSUMER_KEY=xxxx TWITTER_CONSUMER_SECRET=yyy TWITTER_OAUTH_CALLBACK=zzz TWITTER_REDIRECT_URL=http://localhost:8080 php -S localhost:8000
```
Then you can access to:     

```
http://localhost:8000/twitter/login
http://localhost:8000/twitter/id
http://localhost:8000/twitter/callback
http://localhost:8000/twitter/logout
```

#### /twitter/login

This route provides back over AJAX the url to the Twitter authorization page . 

#### /twitter/callback

This retrieve the tokens from the request and stores the user information, then it returns the user twitter id.

#### /twitter/id

It returns a JSON file like `{"id": "user_id"}` where the user_id can be null if the user is not logged in

#### /twitter/logout

This removes the user from the session

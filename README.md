# twitter-login

These env variables should be configured:

```
TWITTER_CONSUMER_KEY
TWITTER_CONSUMER_SECRET
TWITTER_OAUTH_CALLBACK
```

#### /twitter/login

This url redirects to the Twitter authorize page or in case the user is already logged in, it redirects to `/twitter/id`. 

#### /twitter/callback

This retrieve the tokens from the request and stores the user information, then it redirects to `/twitter/id`.

#### /twitter/id

It returns a JSON file like `{"id": "user_id"}` or redirects to `/twitter/login` if the user is not logged in.

#### /twitter/logout

This removes the user from the session

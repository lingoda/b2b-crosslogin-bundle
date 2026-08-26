# Lingoda B2B Cross-Login Bundle
This bundle provides a way to cross-login between apps on B2B.

### Installation
```bash
composer require lingoda/b2b-crosslogin-bundle
```
Then add the bundle to your `config/bundles.php`, if it is not added automatically:
```php
return [
    // ...
    Lingoda\CrossLoginBundle\LingodaCrossLoginBundle::class => ['all' => true],
];
```

### Configuration
Add the following configuration to your `config/packages/lingoda_cross_login.yaml`:
```yaml
lingoda_cross_login:
    # This should match the value of lexik_jwt_authentication.query_parameter.name parameter,
    # if LexikJWTAuthenticationBundle built-in authenticator is used
    query_parameter_name: bearer
    issuer: 'https://your-issuer.com'
    # this overrides the value from LexikJWTAuthenticationBundle configuration, only for the cross-bundle JWT token,
    # and it will not affect other tokens generated via LexikJWTAuthenticationBundle
    token_ttl: 5 # in seconds; make it as short as possible, to minimize the risk of token theft; Maximum allowed value is 10, minimum is 1; Default and recommended value is 5
```
!!! For security reasons, the `audience` of a generated token on one app has to match the `issuer` of the other app,
and vice versa. Tokens that do not match this requirement will be rejected.
The `audience` is automatically generated from the `host` and `port` of the URL provided.

The following statements should be true, in order to have a successful cross-login:
- App A `issuer` ENV var = App B JWT token `audience` (`aud`)
- App B `issuer` ENV var = App A JWT token `audience` (`aud`)

### Configuration for LexikJWTAuthenticationBundle
Add the following configuration to your `config/packages/lexik_jwt_authentication.yaml`:
```yaml
lexik_jwt_authentication:
    secret_key: '%env(resolve:JWT_SECRET_KEY)%'
    public_key: '%env(resolve:JWT_PUBLIC_KEY)%'
    pass_phrase: '%env(JWT_PASSPHRASE)%'
    token_ttl: 3600
    token_extractors:
        # if LexikJWTAuthenticationBundle is not used for anything else, you can disable other extractors
#        authorization_header:
#            enabled: false
        query_parameter:
            enabled: true
            name: bearer
```

### Configuring the firewall
Then, add the following configuration to your `config/packages/security.yaml`:
```yaml
security:
    firewalls:
        your-firewall-name:
            # ...
            jwt: ~
```

### Configuring the routes
Finally, add the following to your `config/routes.yaml`:
```yaml
_lingoda_cross_login:
    resource: '@LingodaCrossLoginBundle/config/routes.php'
    prefix: /admin # optional, but recommended to have it behind a firewall, so it can't be accessed by unauthorized users
```

### Important note!
- Keep in mind, all apps need to have the same `JWT_PUBLIC_KEY` value (`public.pem` content, not path), so the JWT token can be validated across apps.

### Usage in Twig
You can use the following Twig functions to generate JWT tokens and URLs:
```twig
# to generate a JWT token, use the following function:
{{ crosslogin_generate_token(url('a_route_name_here')) }}

# to generate a URL that will redirect to the signed URL, use the following function:
{{ crosslogin_sign_and_redirect_url('https://example.com/?foo=bar#fragment') }}

# to generate a signed URL that you can use on an iFrame, use the following function:
{{ crosslogin_sign_url('https://example.com/?foo=bar#fragment') }}
```

### Use cases
#### 1. Bypassing JWT token authentication failure
If you don't want the authentication to fail if the JWT token is invalid, expired, or not provided, you can add the `BypassFailureJWTAuthenticator` to your firewall's `custom_authenticators`:
```yaml
# config/packages/security.yaml
security:
    firewalls:
        your-firewall-name:
            # ...
            jwt:
                authenticator: Lingoda\CrossLoginBundle\Security\Authenticator\BypassFailureJWTAuthenticator
```
And register the authenticator in your `config/services.yaml`:
```yaml
services:
    # ...
    Lingoda\CrossLoginBundle\Security\Authenticator\BypassFailureJWTAuthenticator:
        parent: lexik_jwt_authentication.security.jwt_authenticator
```

#### 2. Stateful cross-login
If you want to make the cross-login stateful, add the `jwt` configuration to a stateful firewall, e.g.:
```yaml
# config/packages/security.yaml
security:
    firewalls:
        admin:
            # ...
            jwt: ~
            form_login: ~
            entry_point: form_login
            # ... do not add stateless: true to this firewall, as it will make the cross-login stateless
```

## Receiving a cross-login

### First decide whether you need this at all

**If your receiving firewall is stateful, you do not.** A JWT on a stateful firewall already
authenticates the request and Symfony persists a session, so every URL is a valid landing page.
That is use case #2 above. Change nothing.

**You need a receive endpoint only when the receiving firewall is `stateless: true`.** A stateless
app has to exchange the short-lived cross-login token for its own durable credentials — typically
cookies — and that exchange is app-specific. The bundle owns the route, the claim decoding and the
failure shape, and dispatches events for the rest.

The receive side is off until you opt in, so upgrading cannot give an app an endpoint it did not ask
for. Opting in takes two steps, per receiver.

### 1. Configure the receiver

```yaml
# config/packages/lingoda_cross_login.yaml
lingoda_cross_login:
    issuer: '%env(APP_DOMAIN)%'
    receivers:
        my_receiver:
            default_route: app_main   # where to go when no listener sets a Response
            # error_query_parameter: crosslogin_error                  # optional, this is the default
            # jwt_manager: lexik_jwt_authentication.jwt_manager        # optional, this is the default
```

Receivers are named because one app can receive on behalf of several hosts. Override `jwt_manager`
when the app verifies cross-login tokens with a different key than it signs its own tokens with.

### 2. Import the route

```yaml
# config/routes.yaml
my_cross_login:
    resource: '@LingodaCrossLoginBundle/config/receive_routes.php'
    host: '%my_public_domain%'      # omit on a single-host app
    prefix: /cross-login
    name_prefix: 'my_cross_login_'
    defaults: { _crosslogin_receiver: my_receiver }
```

The bundle does not register the route itself, because only your app knows the host constraint and
the ordering — in an app with a catch-all route, this **must** be registered before that catch-all.
The route is named `receive`, so `name_prefix` is required to keep several receivers distinct.
`Lingoda\CrossLoginBundle\Routing\CrossLoginRoutes::RECEIVE_PATH` holds the recommended full path,
for senders in other apps that cannot generate the route.

### 3. Give the route a firewall

```yaml
# config/packages/security.yaml — above your catch-all firewall, first match wins
my_cross_login:
    host: '%my_public_domain%'
    pattern: ^/cross-login
    stateless: true
    provider: my_user_provider
    jwt:
        authenticator: Lingoda\CrossLoginBundle\Security\Authenticator\BypassFailureJWTAuthenticator
```

```yaml
# config/packages/security.yaml — access_control
- { path: ^/cross-login, host: '%my_public_domain%', roles: PUBLIC_ACCESS }
```

`PUBLIC_ACCESS` is required: `BypassFailureJWTAuthenticator` lets an unusable token through instead
of returning 401, so the controller can dispatch a failure your app can redirect on.

### 4. Listen

Three events fire, each under a name scoped to the receiver, so a listener in a multi-receiver app
can never be triggered by a different receiver's hand-off.

| Event name | Object | Use it to |
|---|---|---|
| `lingoda_crosslogin.<receiver>.token_received` | `CrossLoginTokenReceivedEvent` | inspect claims; `setUser()` to provision a user the app has not seen |
| `lingoda_crosslogin.<receiver>.succeeded` | `CrossLoginSucceededEvent` | `setResponse()` — mint your session and redirect |
| `lingoda_crosslogin.<receiver>.failed` | `CrossLoginFailedEvent` | `setResponse()` — usually a redirect to your own login |

```php
#[AsEventListener(event: 'lingoda_crosslogin.my_receiver.succeeded')]
final readonly class MyCrossLoginListener
{
    public function __invoke(CrossLoginSucceededEvent $event): void
    {
        $response = new RedirectResponse($this->urlGenerator->generate('app_main'));
        foreach ($this->cookieFactory->create($event->user) as $cookie) {
            $response->headers->setCookie($cookie);
        }

        $event->setResponse($response);
    }
}
```

If no listener sets a Response the controller redirects to `default_route`, and on failure it adds
the reason as a query parameter — `?crosslogin_error=invalid_token`. The codes are
`missing_token`, `invalid_token` and `unknown_user` (`CrossLoginError`). An exception message is
never put in the URL.

### Dependencies
- [LexikJWTAuthenticationBundle](https://github.com/lexik/LexikJWTAuthenticationBundle) is used for JWT token generating.

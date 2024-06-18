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
    token_ttl: 30 # in seconds; make it as short as possible, to minimize the risk of token theft
    hashing_key: '%env(resolve:JWT_PUBLIC_KEY)%' # or any other value, but it should be the same across all apps
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
- Keep in mind, all apps need to have the same `JWT_PUBLIC_KEY` value (`public.pem` content, not path) and `hashing_key` value, so the JWT token can be validated across apps.

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

### Dependencies
- [LexikJWTAuthenticationBundle](https://github.com/lexik/LexikJWTAuthenticationBundle) is used for JWT token generating.

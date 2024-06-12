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
    token_ttl: 300 # in seconds
```
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

Then, add the following configuration to your `config/packages/security.yaml`:
```yaml
security:
    firewalls:
        your-firewall-name:
            # ...
            jwt: ~
```

Add the following to your `config/routes.yaml`:
```yaml
_lingoda_cross_login:
    resource: '@LingodaCrossLoginBundle/config/routes.php'
    prefix: /admin # optional, but recommended to have it behind a firewall, so it can't be accessed by unauthorized users
```

### Dependencies
- [LexikJWTAuthenticationBundle](https://github.com/lexik/LexikJWTAuthenticationBundle) is used for JWT token generating.

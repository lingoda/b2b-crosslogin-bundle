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
```
Add the following configuration to your `config/packages/lexik_jwt_authentication.yaml`:
```yaml
lexik_jwt_authentication:
    secret_key: '%env(resolve:JWT_SECRET_KEY)%'
    public_key: '%env(resolve:JWT_PUBLIC_KEY)%'
    pass_phrase: '%env(JWT_PASSPHRASE)%'
    token_ttl: 3600
    token_extractors:
        authorization_header:
            enabled: false
        query_parameter:
            enabled: true
            name: bearer
```

Then, add the following configuration to your `config/packages/security.yaml`:
```yaml
security:
    firewalls:
        main:
            # ...
            jwt: ~
```

### Dependencies
- [LexikJWTAuthenticationBundle](https://github.com/lexik/LexikJWTAuthenticationBundle) is used for JWT token generating.
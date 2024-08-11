# Codyas Skeleton Bundle

## Installation

Make sure Composer is installed globally, as explained in the
[installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

Open a command console, enter your project directory and execute:

```console
$ composer require codyas/skeleton-bundle
```

## Configuration

### Security

#### 1. The base user class
The bundle provides a base class with common structure `Codyas\Skeleton\Model\UserModel`. The `UserModel` class 
already assumes email as primary identification and includes the following fields, so you don't need to implement them:
- id
- email
- enabled
- password
- roles
- isVerified

If your user class requires another behaviour feel free to implement your own structure but make sure to implement the
`UserModelInterface`

#### 2. Implementing the user class
Create an entity that will represent your users and extend the `Codyas\Skeleton\Model\UserModel` class.

```php
<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Codyas\Skeleton\Model\UserModel;
use Codyas\Skeleton\Model\UserModelInterface;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User extends UserModel implements UserModelInterface
{ }
```

Add any other fields in your class (if needed) and once done you are ready to sync the changes with your database.

#### 3. Security configuration
Go to `config/packages/security.yaml` and adjust the file with the following changes
```yaml
security:
    providers:
        app_user_provider:
            entity:
                class: App\Entity\User
                property: email
    firewalls:
        dev:
            pattern: ^/(_(profiler|wdt)|css|images|js)/
            security: false
        main:
            lazy: true
            provider: app_user_provider
            custom_authenticator: App\Security\EmailAuthenticator
            #form_login:
            #    login_path: app_login
            #    check_path: app_login
            #    enable_csrf: true
            logout:
                path: app_logout
                target: app_login

    access_control:
        - { path: ^/login, roles: PUBLIC_ACCESS } 
        - { path: ^/, roles: ROLE_ADMIN }
```
The `App\Security\EmailAuthenticator` class is automatically created when installing the bundle, but you can adjust it
to your needs or create a custom authenticator and modify the security configuration.

## Templating
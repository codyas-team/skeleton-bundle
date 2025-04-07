# Codyas Skeleton Bundle

## Skeleton project installation
For Codyas team members, express installation method is available.
CD to the folder you want to locate the project and execute the following command, make sure to replace PROJECT_NAME for your actual project name:
```console
curl -s https://gist.githubusercontent.com/leoantunez/0446c79dcc717de4fbbc0cb8214c26d9/raw | bash -s PROJECT_NAME
```

## Manual installation
Make sure Composer is installed globally, as explained in the
[installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

Open a command console, enter your project directory and execute:

```console
composer require codyas/skeleton-bundle
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
The default template is [Tabler](https://github.com/tabler/tabler) and integrated with
[TablerBundler](https://github.com/kevinpapst/TablerBundle), so when installing this bundle you should also adjust the 
`config/packages/tabler.yaml` file.

## Automated operations configuration
This bundle supports automated read and write operations via the CRUD manager. To allow the handler to operate entities 
in your application, you must ensure that you meet the following requirements:

### Required configuration

* Your entity must implement the \Codyas\SkeletonBundle\Model\CrudEntityInterface and its methods:
    * `getId(): ?int`: Return the entity's identifier.
    * `renderDataTableRow(\Codyas\SkeletonBundle\Model\RowRendererArguments $arguments): array`: This method must return an array, where each position relates with your entity columns.
    * `__toString(): string`: An string representation of the entity.

### Export entities data
If it is necessary to export the data from the table, it is possible to configure the entity for this purpose, although 
it will always be necessary to implement the method that generates the file. To activate this behavior you must follow 
the following steps:
* Configure the `exportConfiguration` key in the entity configuration attribute. This configuration parameter expects an 
array of `\Codyas\SkeletonBundle\Model\EntityExportDefinition` where each element represents a format in which the information is exported.
You also need to specify the both the implementation class and method that will generate the report:
    ```php
    #[CrudEntity(
        # (..)
        exportConfiguration: [
            new EntityExportDefinition(
                format: 'csv',
                label: 'CSV',
                icon: 'fas fa-file-csv',
                # Specify here the class that implements your report generation.
                implementationClass: ReportsGeneratorService::class,
                # Specify the class method that implements your report generation.
                callbackMethod: 'generateCSV'
            )
    ]
)]```
* Your **implementation class** must be tagged with csk_export_implementation to be discovered by the skeleton. In the 
following working example, you can see an implementation of a CSV export. The method receives the applied filters and 
current pagination as parameter. You can choose to generate the output for the current page or iterate over all pages for
a complete data dump.
```php
<?php

namespace App\Services;

use App\Entity\User;
use Codyas\SkeletonBundle\Model\CrudEntityInterface;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[AutoconfigureTag(name: CrudEntityInterface::EXPORT_IMPLEMENTATION_SERVICE_TAG)]
class ReportsGeneratorService
{
    public function generateCSV(PaginationInterface $pagination) : Response
    {
        $handle = fopen('php://temp', 'r+');
        /** @var User $user */
        foreach ($pagination->getItems() as $user) {
            fputcsv($handle, $user->toArray());
        }
        rewind($handle);
        $csvContent = stream_get_contents($handle);
        fclose($handle);
        $fileName = strtoupper(uniqid());
        return new Response(
            $csvContent,
            200,
            [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"{$fileName}.csv\"",
            ]
        );
    }
}
```
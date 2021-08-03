# Adgangsstyring

Composer package for acquiring users in specific Azure AD group.

## References

* [Microsoft Graph group members](https://docs.microsoft.com/en-us/graph/api/group-list-members?view=graph-rest-1.0&tabs=http)

## Usage

If you are looking to use this in a Symfony or Drupal project you should use
either:

* Symfony: [itk-dev/adgangsstyring-bundle](https://github.com/itk-dev/adgangsstyring-bundle)
* Drupal: [itk-dev/adgangsstyring_drupal](https://github.com/itk-dev/adgangsstyring_drupal)

### Direct installation

To install this package directly run

```shell
composer require itkdev/adgangsstyring
```

To use the package you must use the [Guzzle HTTP client](https://docs.guzzlephp.org/en/stable/).

### Flow

To start the flow one needs to call the
`Controller` `run(HandlerInterface $handler)` command.

Therefore, you must create your own handler that implements
 `HandlerInterface`.

#### Example Usage

```php
<?php

use ItkDev\Adgangsstyring\Handler\HandlerInterface;

class SomeHandler implements HandlerInterface
{
    public function start(): void
    {
        // Some start logic
    }

    public function retainUsers(array $users): void
    {
        // Some user logic
    }

    public function commit(): void
    {
        // Some commit logic
    }
}
```

Be aware that `retainUsers()` may be called multiple times,
as we are limited to 100 users per request.

To start the flow provide a Guzzle `Client`
and the required options seen beneath:

```php

use GuzzleHttp\Client;
use ItkDev\Adgangsstyring\Controller;


$options = [
  'tenant_id' => 'something.onmicrosoft.com', // Tenant id 
  'client_id' => 'some_client_id', // Client id assigned by authorizer
  'client_secret' => 'some_client_secret', // Client password assigned by authorizer
  'group_id' => 'some_group_id', // Group id provided by authorizer
];

$handler = new SomeHandler();

$client = new Client();
$controller = new Controller($client, $this->options);

$controller->run($handler);
```

### General comments

Note that this package does not do the synchronization
of users, instead it provides a list of all users that
currently are assigned to the group in question.

Should the specified group contain no users an exception will be
thrown. This is to avoid using systems to be under the impression
that every single user should be deleted.

## Development Setup

### Unit Testing

We use PHPUnit for unit testing. To run the tests:

```shell
./vendor/bin/phpunit tests
```

The test suite uses [Mocks](https://phpunit.de/manual/6.5/en/test-doubles.html)
for generation of test doubles.

### Check Coding Standard

* PHP files (PHP_CodeSniffer)

    ```shell
    composer check-coding-standards
    ```

* Markdown files (markdownlint standard rules)

    ```shell
    yarn install
    yarn check-coding-standards
    ```

### GitHub Actions

All code checks mentioned above are automatically run by [GitHub
Actions](https://github.com/features/actions) when a pull request is created.

To run the actions locally, install [act](https://github.com/nektos/act) and run

```sh
act -P ubuntu-latest=shivammathur/node:focal pull_request
```

Use `act -P ubuntu-latest=shivammathur/node:focal pull_request --list` to see
individual workflow jobs that can be run, e.g.

```sh
act -P ubuntu-latest=shivammathur/node:focal pull_request --job phpcsfixer
```

### Apply Coding Standards

* PHP files (PHP_CodeSniffer)

    ```shell
    composer apply-coding-standards
    ```

* Markdown files (markdownlint standard rules)

    ```shell
    yarn install
    yarn apply-coding-standards
    ```

## Versioning

We use [SemVer](http://semver.org/) for versioning.
For the versions available, see the
[tags on this repository](https://github.com/itk-dev/adgangsstyring/tags).

## License

This project is licensed under the MIT License - see the
[LICENSE.md](LICENSE.md) file for details

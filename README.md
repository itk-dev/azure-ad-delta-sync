# Work in progress

# Adgangsstyring

Composer package for acquiring users in specific Azure AD group.

## References

* [Microsoft Graph group members](https://docs.microsoft.com/en-us/graph/api/group-list-members?view=graph-rest-1.0&tabs=http)

## Usage

If you are looking to use this in a Symfony or Drupal project you should use
either:

* Symfony: LINK GOES HERE
* Drupal: LINK GOES HERE

### Direct installation

To install this package directly run

```shell
composer require itkdev/adgangsstyring
```

To use the package you must use the
[Symfony EventDispacther Component](https://symfony.com/doc/current/components/event_dispatcher.html)
and the [Guzzle HTTP client](https://docs.guzzlephp.org/en/stable/).

### Flow

The package will send out Symfony events which a using system
should then listen to.

`StartEvent` indicates that the flow has started.

`UserDataEvent` contains a list of users within the specified group.
The list of users is limited to 100, so it may well send more than one `UserDataEvent`.

`CommitEvent` suggests that no more events containing users are coming,
and you may proceed your synchronization logic.

Note that this package does not do the synchronization
of users, instead it provides a list of all users that
currently are assigned to the group in question.

Should the specified group contain no users an exception will be
thrown and a `CommitEvent` will not be dispatched.
This is to avoid using systems to be under the impression
that every single user should be deleted.

### Example usage

When an instance of `Controller` is created it is configured
with a Symfony `EventDispatcher`, a Guzzle `Client` and an array of `$options`.
See the required options beneath.

In order to handle the events sent by the `Controller` one
should implement some event listener or subscriber.

```php
use GuzzleHttp\Client;
use ItkDev\Adgangsstyring\Controller;
use Symfony\Component\EventDispatcher\EventDispatcher;


$options = [
  'tenant_id' => 'something.onmicrosoft.com', // Tenant id 
  'client_id' => 'some_client_id', // Client id assigned by authorizer
  'client_secret' => 'some_client_secret', // Client password assigned by authorizer
  'group_id' => 'some_group_id', // Group id provided by authorizer
];

$eventSubscriber = new SomeEventSubscriber();

$eventDispatcher = new EventDispatcher();
$eventDispatcher->addSubscriber($eventSubscriber);

$client = new Client();

$controller = new Controller($eventDispatcher, $client, $options);
```

The flow is then started as follows:

```php
$controller->run();
```

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

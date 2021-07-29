# Work in progress

# Adgangsstyring

Composer package for acquiring users in specific Azure AD group.

## References

* [Microsoft Graph group members](https://docs.microsoft.com/en-us/graph/api/group-list-members?view=graph-rest-1.0&tabs=http)

## Usage

### Framework

If you are looking to use this in a Symfony or Drupal project you should use
either:

* Symfony: LINK GOES HERE
* Drupal: LINK GOES HERE

## Direct installation

To install this library directly run

```shell
composer require itkdev/adgangsstyring
```

### Flow

The package will send out Symfony events which an implementation
should then listen to.

The `StartEvent` indicates that the flow has started.
`UserDataEvent` contains a list of users within the specified group.
The list of users is limited to 100, so it may well send more than one `UserDataEvent`.
The `CommitEvent` suggests that no more events containing users are coming,
and you may proceed your synchronization logic.

Note that this package does not do the synchronization
of users, instead it provides a list of all users that
currently are assigned to the group in question.

### Example usage

When an instance of `Controller` is created it is configured
with a Symfony `EventDispatcher` and an array of `$options`.

In order to handle the events sent by the `Controller` one
should implement some listener or subscriber.

```php
use ItkDev\Adgangsstyring\Controller;
use Symfony\Component\EventDispatcher\EventDispatcher;

$options = [
  'tenantId' => 'something.onmicrosoft.com', // Tenant id
  'clientId' => 'client_id', // Client id assigned by authorizer
  'clientSecret' => 'client_secret', // Client password assigned by authorizer
  'groupId' => 'group_id', // Group id provided by authorizer
];

$eventSubscriber = new SomeEventSubscriber();

$eventDispatcher = new EventDispatcher();
$eventDispatcher->addSubscriber($eventSubscriber);

$controller = new Controller($eventDispatcher, $options);
```

The flow is then started as follows:

```php
$controller->run();
```

## Development Setup

### Unit Testing

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

## Versioning

We use [SemVer](http://semver.org/) for versioning.
For the versions available, see the
[tags on this repository](https://github.com/itk-dev/adgangsstyring/tags).

## License

This project is licensed under the MIT License - see the
[LICENSE.md](LICENSE.md) file for details

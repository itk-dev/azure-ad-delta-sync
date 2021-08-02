# Adgangsstyring

Composer package for acquiring users in specific Azure AD group.

## References

* [Microsoft Graph group members](https://docs.microsoft.com/en-us/graph/api/group-list-members?view=graph-rest-1.0&tabs=http)

## Usage

If you are looking to use this in a Symfony or Drupal project you should use
either:

* Symfony: LINK GOES HERE
* Drupal: [itk-dev/adgangsstyring_drupal](https://github.com/itk-dev/adgangsstyring_drupal)

### Direct installation

To install this package directly run

```shell
composer require itkdev/adgangsstyring
```

To use the package you must use the
[Symfony EventDispacther Component](https://symfony.com/doc/current/components/event_dispatcher.html)
and the [Guzzle HTTP client](https://docs.guzzlephp.org/en/stable/).

### Flow

To start the flow one needs to call the
`Controller` `run(HandlerInterface $handler)` command.

You can either:

* Create your own handler that implements
 `HandlerInterface`
* Use the provided `EventDispatcherHandler`

Should you choose the latter you will need some EventListener
or EventSubscriber.

#### Custom handler way

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

#### EventSubscriber way

The provided `EventDispatcherHandler` dispatches
three types of events.

`StartEvent` indicates that the flow has started.

`UserDataEvent` contains a list of users within the specified group.
The list of users is limited to 100, so it may well send more than one `UserDataEvent`.

`CommitEvent` suggests that no more events containing users are coming,
and you may proceed your synchronization logic.

```php
<?php

use ItkDev\Adgangsstyring\Event\CommitEvent;
use ItkDev\Adgangsstyring\Event\StartEvent;
use ItkDev\Adgangsstyring\Event\UserDataEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SomeEventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            StartEvent::class => ['start'],
            UserDataEvent::class => ['retainUsers'],
            CommitEvent::class => ['commit'],
        ];
    }
    
    public function start(StartEvent $event)
    {
        // Some start logic
    }
    
    public function retainUsers(UserDataEvent $event)
    {
        // Some user logic
    }
    
    public function commit(CommitEvent $event)
    {
        // Some commit logic
    }
}
```

To start the flow provide a Guzzle `Client`
and the required options seen beneath:

```php
use GuzzleHttp\Client;
use ItkDev\Adgangsstyring\Controller;
use ItkDev\Adgangsstyring\Handler\EventDispatcherHandler;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;


$options = [
  'tenant_id' => 'something.onmicrosoft.com', // Tenant id 
  'client_id' => 'some_client_id', // Client id assigned by authorizer
  'client_secret' => 'some_client_secret', // Client password assigned by authorizer
  'group_id' => 'some_group_id', // Group id provided by authorizer
];

$handler = new EventDispatcherHandler($this->dispatcher);

$client = new Client();
$controller = new Controller($client, $this->options);

$controller->run($handler);
```

The EventDispatcher must be dependency injected as creating
a new will result in unregistered listener/subscriber.

### General comments

Note that this package does not do the synchronization
of users, instead it provides a list of all users that
currently are assigned to the group in question.

Should the specified group contain no users an exception will be
thrown and a `CommitEvent` will not be dispatched.
This is to avoid using systems to be under the impression
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

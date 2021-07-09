<?php

namespace ItkDev\Adgangsstyring;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use ItkDev\Adgangsstyring\Event\CommitEvent;
use ItkDev\Adgangsstyring\Event\StartEvent;
use ItkDev\Adgangsstyring\Event\UserDataEvent;
use ItkDev\Adgangsstyring\Exception\DataException;
use ItkDev\Adgangsstyring\Exception\TokenException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Controller
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var array
     */
    private $options;

    public function __construct(EventDispatcherInterface $eventDispatcher, Client $client, array $options)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->client = $client;

        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        $this->options = $resolver->resolve($options);
    }

    /**
     * @throws TokenException|DataException
     */
    public function run()
    {
        // Acquiring access token and token type
        $url = 'https://login.microsoftonline.com/' . $this->options['tenantId'] . '/oauth2/v2.0/token';

        try {
            $postResponse = $this->client->post($url, [
                'form_params' => [
                    'client_id' => $this->options['clientId'],
                    'client_secret' => $this->options['clientSecret'],
                    'scope' => 'https://graph.microsoft.com/.default',
                    'grant_type' => 'client_credentials',
                ],
            ]);
        } catch (RequestException $e) {
            throw new TokenException('Cannot get token.', $e->getCode(), $e);
        }

        $token = json_decode($postResponse->getBody()->getContents());

        // Construct group url for microsoft graph
        $groupUrl = 'https://graph.microsoft.com/v1.0/groups/' . $this->options['groupId'] . '/members';

        $tokenType = $token->token_type;
        $accessToken = $token->access_token;

        // Send start event
        $startEvent = new StartEvent();
        $this->eventDispatcher->dispatch($startEvent);

        $totalCount = 0;
        // Dispatch user data events containing users as long as next link exists
        while (null !== $groupUrl) {
            $data = $this->getData($groupUrl, $tokenType, $accessToken);

            if (array_key_exists('value', $data)) {
                $count = count($data['value']);

                if (0 !== $count) {
                    // Update total count
                    $totalCount += $count;

                    // Create and dispatch event containing users
                    $event = new UserDataEvent($data['value']);
                    $this->eventDispatcher->dispatch($event);
                }
            }

            // Get next uri containing users
            $groupUrl = $data['@odata.nextLink'] ?? null;
        }

        // Throw DataException if no users in group
        if (0 === $totalCount) {
            throw new DataException('No users found in group.');
        }

        // Send commit event indicating no more user data events coming
        $commitEvent = new CommitEvent();
        $this->eventDispatcher->dispatch($commitEvent);
    }

    /**
     * @param string $url
     * @param string $tokenType
     * @param string $accessToken
     * @return array
     * @throws DataException
     */
    private function getData(string $url, string $tokenType, string $accessToken): array
    {
        try {
            $response = $this->client->get($url, [
                'headers' => [
                    'authorization' => $tokenType . ' ' . $accessToken,
                ],
            ]);
        } catch (RequestException $e) {
            throw new DataException('Cannot get users.', $e->getCode(), $e);
        }

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['tenantId', 'clientId', 'clientSecret', 'groupId']);
    }
}

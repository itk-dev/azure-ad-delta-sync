<?php

namespace ItkDev\Adgangsstyring;

use GuzzleHttp\Client;
use ItkDev\Adgangsstyring\Event\CommitEvent;
use ItkDev\Adgangsstyring\Event\StartEvent;
use ItkDev\Adgangsstyring\Event\UserDataEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Controller
{
    private $tenantId;
    private $clientId;
    private $clientSecret;
    private $groupId;
    private $client;
    private $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher, Client $client, array $options)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->client = $client;

        $resolver = new OptionsResolver();
        $resolver->setRequired(['tenantId', 'clientId', 'clientSecret', 'groupId']);
        $resolver->resolve($options);
        $this->tenantId = $options['tenantId'];
        $this->clientId = $options['clientId'];
        $this->clientSecret = $options['clientSecret'];
        $this->groupId = $options['groupId'];
    }

    public function run()
    {
        // Acquiring access token and token type
        $url = 'https://login.microsoftonline.com/' . $this->tenantId . '/oauth2/v2.0/token';

        $token = json_decode($this->client->post($url, [
            'form_params' => [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'scope' => 'https://graph.microsoft.com/.default',
                'grant_type' => 'client_credentials',
            ],
        ])->getBody()->getContents());

        // Construct microsoft graph url for group
        $groupUrl = 'https://graph.microsoft.com/v1.0/groups/' . $this->groupId . '/members';

        $tokenType = $token->token_type;
        $accessToken = $token->access_token;

        // Send start event
        $startEvent = new StartEvent();
        $this->eventDispatcher->dispatch($startEvent);

        // Send user data events as long as next link exists
        while (null !== $groupUrl) {
            $data = $this->getData($groupUrl, $tokenType, $accessToken);
            $event = new UserDataEvent($data['value']);
            $this->eventDispatcher->dispatch($event);
            $groupUrl = $data['@odata.nextLink'] ?? null;
        }

        // Send commit event indicating no more user data events coming
        $commitEvent = new CommitEvent();
        $this->eventDispatcher->dispatch($commitEvent);
    }

    private function getData(string $url, string $tokenType, string $accessToken)
    {
        $response = $this->client->get($url, [
            'headers' => [
                'authorization' => $tokenType . ' ' . $accessToken,
            ],
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }
}

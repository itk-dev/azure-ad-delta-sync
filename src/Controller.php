<?php

namespace ItkDev\Adgangsstyring;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use ItkDev\Adgangsstyring\Event\CommitEvent;
use ItkDev\Adgangsstyring\Event\StartEvent;
use ItkDev\Adgangsstyring\Event\UserDataEvent;
use ItkDev\Adgangsstyring\Exception\TokenException;
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

    public function __construct(EventDispatcherInterface $eventDispatcher, array $options, Client $client)
    {
        $this->client = $client;
        $this->eventDispatcher = $eventDispatcher;

        $resolver = new OptionsResolver();
        $resolver->setRequired(['tenantId', 'clientId', 'clientSecret', 'groupId']);
        $resolver->resolve($options);
        $this->tenantId = $options['tenantId'];
        $this->clientId = $options['clientId'];
        $this->clientSecret = $options['clientSecret'];
        $this->groupId = $options['groupId'];
    }

    /**
     * @throws TokenException
     */
    public function run()
    {
        // Acquiring access token and token type
        $url = 'https://login.microsoftonline.com/' . $this->tenantId . '/oauth2/v2.0/token';


        try {
            $postResponse = $this->client->post($url, [
                'form_params' => [
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'scope' => 'https://graph.microsoft.com/.default',
                    'grant_type' => 'client_credentials',
                ],
            ]);
        } catch (RequestException $e) {
            throw new TokenException($e->getMessage());
        }


        $token = json_decode($postResponse->getBody()->getContents());

        // Construct group url for microsoft graph
        $groupUrl = 'https://graph.microsoft.com/v1.0/groups/' . $this->groupId . '/members';

        $tokenType = $token->token_type;
        $accessToken = $token->access_token;

        // Send start event
        $startEvent = new StartEvent();
        $this->eventDispatcher->dispatch($startEvent);

        $totalCount = 0;
        // Send user data events containing users as long as next link exists
        while (null !== $groupUrl) {
            $data = $this->getData($groupUrl, $tokenType, $accessToken);

            if (array_key_exists('value', $data)) {

                $count = count($data['value']);

                if (0 !== $count) {
                    $event = new UserDataEvent($data['value']);
                    $this->eventDispatcher->dispatch($event);
                }
            }

            $groupUrl = $data['@odata.nextLink'] ?? null;
        }

        // Send commit event indicating no more user data events coming
        $commitEvent = new CommitEvent();
        $this->eventDispatcher->dispatch($commitEvent);
    }

    /**
     * @throws TokenException
     */
    private function getData(string $url, string $tokenType, string $accessToken)
    {

        try {
            $response = $this->client->get($url, [
                'headers' => [
                    'authorization' => $tokenType . ' ' . $accessToken,
                ],
            ]);
        } catch (RequestException $e) {
            throw new TokenException($e->getMessage());
        }

        return json_decode($response->getBody()->getContents(), true);
    }
}

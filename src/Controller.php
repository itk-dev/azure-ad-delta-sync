<?php

namespace ItkDev\Adgangsstyring;

use GuzzleHttp\Client;
use ItkDev\Adgangsstyring\Event\ItkDevAccessControlEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Controller
{
    private $tenantId;
    private $clientId;
    private $clientSecret;
    private $groupId;
    private $client;
    private $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher, array $options)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->tenantId = $options['tenantId'];
        $this->clientId = $options['clientId'];
        $this->clientSecret = $options['clientSecret'];
        $this->groupId = $options['groupId'];
    }

    public function run()
    {
        $this->client = new Client();
        $url = 'https://login.microsoftonline.com/' . $this->tenantId . '/oauth2/token?api-version=1.0';
        $token = json_decode($this->client->post($url, [
            'form_params' => [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'resource' => 'https://graph.microsoft.com/',
                'grant_type' => 'client_credentials',
            ],
        ])->getBody()->getContents());

        $groupUrl = 'https://graph.microsoft.com/v1.0/groups/'.$this->groupId.'/members';

        $tokenType = $token->token_type;
        $accessToken = $token->access_token;

        $data = $this->getData($groupUrl, $tokenType, $accessToken);

        while (array_key_exists('@odata.nextLink', $data)){
            // Fjern slettemarkering på disse brugere
            // Dvs send event
            $event = new ItkDevAccessControlEvent($data['value']);

            $this->eventDispatcher->dispatch($event);

            $data = $this->getData($data['@odata.nextLink'], $tokenType, $accessToken);
            var_dump(count($data['value']));
        }
    }

    private function getData(string $url, string $tokenType, string $accessToken)
    {
        $response = $this->client->get($url, [
            'headers' => [
                'authorization' => $tokenType.' '.$accessToken,
            ],
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }
}
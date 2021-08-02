<?php

namespace ItkDev\Adgangsstyring;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use ItkDev\Adgangsstyring\Event\CommitEvent;
use ItkDev\Adgangsstyring\Event\StartEvent;
use ItkDev\Adgangsstyring\Event\UserDataEvent;
use ItkDev\Adgangsstyring\Exception\DataException;
use ItkDev\Adgangsstyring\Exception\TokenException;
use ItkDev\Adgangsstyring\Handler\EventDispatcherHandler;
use ItkDev\Adgangsstyring\Handler\HandlerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Controller
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var array
     */
    private $options;

    public function __construct(Client $client, array $options)
    {
        $this->client = $client;

        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        $this->options = $resolver->resolve($options);
    }

    /**
     * @throws TokenException|DataException
     */
    public function run(HandlerInterface $handler)
    {
        // Acquiring access token and token type
        $url = 'https://login.microsoftonline.com/' . $this->options['tenant_id'] . '/oauth2/v2.0/token';

        try {
            $postResponse = $this->client->post($url, [
                'form_params' => [
                    'client_id' => $this->options['client_id'],
                    'client_secret' => $this->options['client_secret'],
                    'scope' => 'https://graph.microsoft.com/.default',
                    'grant_type' => 'client_credentials',
                ],
            ]);
        } catch (RequestException $e) {
            throw new TokenException('Cannot get token.', $e->getCode(), $e);
        }

        $token = json_decode($postResponse->getBody()->getContents());

        // Construct group url for microsoft graph
        $groupUrl = 'https://graph.microsoft.com/v1.0/groups/' . $this->options['group_id'] . '/members';

        $tokenType = $token->token_type;
        $accessToken = $token->access_token;

        $handler->start();

        $totalCount = 0;
        // Dispatch user data events containing users as long as next link exists
        while (null !== $groupUrl) {
            $data = $this->getData($groupUrl, $tokenType, $accessToken);

            if (array_key_exists('value', $data)) {
                $count = count($data['value']);

                if (0 !== $count) {
                    // Update total count
                    $totalCount += $count;

                    $handler->retainUsers($data['value']);
                }
            }

            // Get next uri containing users
            $groupUrl = $data['@odata.nextLink'] ?? null;
        }

        // Throw DataException if no users in group
        if (0 === $totalCount) {
            throw new DataException('No users found in group.');
        }

        $handler->commit();
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
        $resolver->setRequired(['tenant_id', 'client_id', 'client_secret', 'group_id']);
    }
}

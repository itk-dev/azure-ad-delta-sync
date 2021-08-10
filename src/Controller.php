<?php

namespace ItkDev\Adgangsstyring;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use ItkDev\Adgangsstyring\Exception\DataException;
use ItkDev\Adgangsstyring\Exception\TokenException;
use ItkDev\Adgangsstyring\Handler\HandlerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Controller
{
    const MICROSOFT_LOGIN_DOMAIN = 'https://login.microsoftonline.com/';
    const MICROSOFT_TOKEN_SUBDIRECTORY = '/oauth2/v2.0/token';
    const MICROSOFT_GRAPH_GROUPS_DOMAIN = 'https://graph.microsoft.com/v1.0/groups/';
    const MICROSOFT_GRAPH_GROUPS_MEMBERS_SUBDIRECTORY = '/members';
    const MICROSOFT_GRAPH_SCOPE = 'https://graph.microsoft.com/.default';
    const MICROSOFT_GRANT_TYPE = 'client_credentials';

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
        $url =  self::MICROSOFT_LOGIN_DOMAIN . $this->options['tenant_id'] . self::MICROSOFT_TOKEN_SUBDIRECTORY;

        try {
            $postResponse = $this->client->post($url, [
                'form_params' => [
                    'client_id' => $this->options['client_id'],
                    'client_secret' => $this->options['client_secret'],
                    'scope' => self::MICROSOFT_GRAPH_SCOPE,
                    'grant_type' => self::MICROSOFT_GRANT_TYPE,
                ],
            ]);
        } catch (RequestException $e) {
            throw new TokenException('Cannot get token.', $e->getCode(), $e);
        }

        try {
            $token = json_decode($postResponse->getBody()->getContents(), false, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new DataException($e->getMessage(), $e->getCode(), $e);
        }

        // Construct group url for microsoft graph
        $groupUrl = self::MICROSOFT_GRAPH_GROUPS_DOMAIN . $this->options['group_id'] . self::MICROSOFT_GRAPH_GROUPS_MEMBERS_SUBDIRECTORY;

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

        try {
            $data = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new DataException($e->getMessage(), $e->getCode(), $e);
        }

        return $data;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['tenant_id', 'client_id', 'client_secret', 'group_id']);
    }
}

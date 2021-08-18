<?php

namespace ItkDev\AzureAdDeltaSync;

use Nyholm\Psr7\Request;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use ItkDev\AzureAdDeltaSync\Exception\DataException;
use ItkDev\AzureAdDeltaSync\Exception\NetworkException;
use ItkDev\AzureAdDeltaSync\Exception\TokenException;
use ItkDev\AzureAdDeltaSync\Handler\HandlerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class Controller
 *
 * Contains the logic needed for running the Azure AD Delta Sync flow.
 *
 * @package ItkDev\AzureAdDeltaSync
 */
class Controller
{
    private const MICROSOFT_LOGIN_DOMAIN = 'https://login.microsoftonline.com/';
    private const MICROSOFT_TOKEN_SUBDIRECTORY = '/oauth2/v2.0/token';
    private const MICROSOFT_GRAPH_GROUPS_DOMAIN = 'https://graph.microsoft.com/v1.0/groups/';
    private const MICROSOFT_GRAPH_GROUPS_MEMBERS_SUBDIRECTORY = '/members';
    private const MICROSOFT_GRAPH_SCOPE = 'https://graph.microsoft.com/.default';
    private const MICROSOFT_GRANT_TYPE = 'client_credentials';

    /**
     * @var ClientInterface
     */
    private ClientInterface $client;

    /**
     * @var array
     */
    private array $options;

    public function __construct(ClientInterface $client, array $options)
    {
        $this->client = $client;

        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        $this->options = $resolver->resolve($options);
    }

    /**
     * Runs the Azure AD Delta Sync flow.
     *
     * @param HandlerInterface $handler
     *
     * @throws TokenException|DataException|NetworkException
     */
    public function run(HandlerInterface $handler)
    {
        // Acquiring access token and token type
        $url =  self::MICROSOFT_LOGIN_DOMAIN . $this->options['tenant_id'] . self::MICROSOFT_TOKEN_SUBDIRECTORY;

        $request = new Request('POST', $url, [], http_build_query([
                'client_id' => $this->options['client_id'],
                'client_secret' => $this->options['client_secret'],
                'scope' => self::MICROSOFT_GRAPH_SCOPE,
                'grant_type' => self::MICROSOFT_GRANT_TYPE,
        ]));

        try {
            $postResponse = $this->client->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
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

        $handler->collectUsersForDeletionList();

        $totalCount = 0;
        // Handle users as long as next link exists
        while (null !== $groupUrl) {
            $data = $this->getData($groupUrl, $tokenType, $accessToken);

            if (array_key_exists('value', $data)) {
                $count = count($data['value']);

                if (0 !== $count) {
                    $totalCount += $count;

                    $handler->removeUsersFromDeletionList($data['value']);
                }
            }

            // Get next uri containing users
            $groupUrl = $data['@odata.nextLink'] ?? null;
        }

        // Throw DataException if no users in group
        if (0 === $totalCount) {
            throw new DataException('No users found in group.');
        }

        $handler->commitDeletionList();
    }

    /**
     * Gets users from current url.
     *
     * @param string $url
     * @param string $tokenType
     * @param string $accessToken
     *
     * @return array
     *
     * @throws NetworkException
     * @throws DataException
     */
    private function getData(string $url, string $tokenType, string $accessToken): array
    {
        $request = new Request('GET', $url, ['authorization' => $tokenType . ' ' . $accessToken]);

        try {
            $response = $this->client->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            throw new NetworkException('Cannot get users.', $e->getCode(), $e);
        }

        try {
            $data = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new DataException($e->getMessage(), $e->getCode(), $e);
        }

        return $data;
    }

    /**
     * Sets required options.
     *
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['tenant_id', 'client_id', 'client_secret', 'group_id']);
    }
}

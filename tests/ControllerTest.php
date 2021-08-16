<?php

namespace ItkDev\Adgangsstyring\Tests;

use GuzzleHttp\Client;
use ItkDev\Adgangsstyring\Controller;
use ItkDev\Adgangsstyring\Exception\DataException;
use ItkDev\Adgangsstyring\Exception\NetworkException;
use ItkDev\Adgangsstyring\Exception\TokenException;
use ItkDev\Adgangsstyring\Handler\HandlerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class ControllerTest extends TestCase
{
    private $controller;
    private $mockClient;
    private $mockClientGetOptions;
    private $mockClientPostOptions;
    private $mockGroupUrl;
    private $mockOptions;
    private $mockResponseInterfacePost;
    private $mockUrl;

    protected function setUp(): void
    {
        parent::setUp();

        // Create Controller
        $this->setUpControllerParameters();
        $this->controller = new Controller($this->mockClient, $this->mockOptions);

        $this->setUpClientPostCallParametersAndResponse();

        $this->setUpClientGetCallParameters();
    }

    /**
     * Testing the Controller run() function
     *
     * Ensure the function loops while a next link exists
     */
    public function testRun()
    {
        $this->mockClient
            ->expects($this->once())
            ->method('post')
            ->with($this->mockUrl, $this->mockClientPostOptions)
            ->willReturn($this->mockResponseInterfacePost);

        // Mock response from getBody function call
        $mockStreamInterfacePost = $this->createMock(StreamInterface::class);

        $this->mockResponseInterfacePost
            ->expects($this->once())
            ->method('getBody')
            ->willReturn($mockStreamInterfacePost);

        // Mock response from getContents function call
        $mockStringResponsePost = "{\"token_type\":\"mock_token_type\",\"expires_in\":1000,\"ext_expires_in\":1000,\"access_token\":\"mock_access_token\"}";

        $mockStreamInterfacePost
            ->expects($this->once())
            ->method('getContents')
            ->willReturn($mockStringResponsePost);

        // Now we need to handle the post function called in getData.

        $mockNextLink = 'mock_next_link';

        $mockResponseInterfaceGet = $this->createMock(ResponseInterface::class);

        $this->mockClient
            ->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive([$this->mockGroupUrl, $this->mockClientGetOptions], [$mockNextLink, $this->mockClientGetOptions])
            ->willReturn($mockResponseInterfaceGet);

        // Mock response from getBody function call
        $mockStreamInterfaceGet = $this->createMock(StreamInterface::class);

        $mockResponseInterfaceGet
            ->expects($this->exactly(2))
            ->method('getBody')
            ->willReturn($mockStreamInterfaceGet);

        // Mock response arrays
        // To simulate a response having a next link we simply add a mock next link
        $mockResponseArrayOne = [
            '@odata.type' => 'mock_data_context',
            '@odata.nextLink' => $mockNextLink,
            'value' => [
                '0' => [
                    'id' => 'mock_id_1',
                    'name' => 'mock_name_1'
                ],
                '1' => [
                    'id' => 'mock_id_2',
                    'name' => 'mock_name_2'
                ],
            ],
        ];

        $mockResponseArrayTwo = [
            '@odata.type' => 'mock_data_context',
            'value' => [
                '0' => [
                    'id' => 'mock_id_3',
                    'name' => 'mock_name_3'
                ],
                '1' => [
                    'id' => 'mock_id_4',
                    'name' => 'mock_name_4'
                ],
            ],
        ];

        // Their respective json encodings as this is what we get from getContents function call
        $mockStringResponseGetOne = json_encode($mockResponseArrayOne);
        $mockStringResponseGetTwo = json_encode($mockResponseArrayTwo);

        $mockStreamInterfaceGet
            ->expects($this->exactly(2))
            ->method('getContents')
            ->willReturnOnConsecutiveCalls($mockStringResponseGetOne, $mockStringResponseGetTwo);

      $handler = $this->createMock(HandlerInterface::class);
      $handler
        ->expects($this->once())
        ->method('collectUsersForDeletionList');
      $handler
        ->expects($this->exactly(2))
        ->method('removeUsersFromDeletionList');
      $handler
        ->expects($this->once())
        ->method('commitDeletionList');

      // Call the run function on Controller
        $this->controller->run($handler);
    }

    /**
     * Testing the Controller run() function
     *
     * Ensure the function does not call removeUsersFromDeletionList twice when theres no users on second list
     */
    public function testRunNoUsersOnSecondList()
    {
        $this->mockClient
            ->expects($this->once())
            ->method('post')
            ->with($this->mockUrl, $this->mockClientPostOptions)
            ->willReturn($this->mockResponseInterfacePost);

        // Mock response from getBody function call
        $mockStreamInterfacePost = $this->createMock(StreamInterface::class);

        $this->mockResponseInterfacePost
            ->expects($this->once())
            ->method('getBody')
            ->willReturn($mockStreamInterfacePost);

        // Mock response from getContents function call
        $mockStringResponsePost = "{\"token_type\":\"mock_token_type\",\"expires_in\":1000,\"ext_expires_in\":1000,\"access_token\":\"mock_access_token\"}";

        $mockStreamInterfacePost
            ->expects($this->once())
            ->method('getContents')
            ->willReturn($mockStringResponsePost);

        // Now we need to handle the post function called in getData.

        $mockNextLink = 'mock_next_link';

        $mockResponseInterfaceGet = $this->createMock(ResponseInterface::class);

        $this->mockClient
            ->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive([$this->mockGroupUrl, $this->mockClientGetOptions], [$mockNextLink, $this->mockClientGetOptions])
            ->willReturn($mockResponseInterfaceGet);

        // Mock response from getBody function call
        $mockStreamInterfaceGet = $this->createMock(StreamInterface::class);

        $mockResponseInterfaceGet
            ->expects($this->exactly(2))
            ->method('getBody')
            ->willReturn($mockStreamInterfaceGet);

        // Mock response arrays
        // To simulate a response having a next link we simply add a mock next link
        $mockResponseArrayOne = [
            '@odata.type' => 'mock_data_context',
            '@odata.nextLink' => $mockNextLink,
            'value' => [
                '0' => [
                    'id' => 'mock_id_1',
                    'name' => 'mock_name_1'
                ],
                '1' => [
                    'id' => 'mock_id_2',
                    'name' => 'mock_name_2'
                ],
            ],
        ];

        $mockResponseArrayTwo = [
            '@odata.type' => 'mock_data_context',
        ];

        // Their respective json encodings as this is what we get from getContents function call
        $mockStringResponseGetOne = json_encode($mockResponseArrayOne);
        $mockStringResponseGetTwo = json_encode($mockResponseArrayTwo);

        $mockStreamInterfaceGet
            ->expects($this->exactly(2))
            ->method('getContents')
            ->willReturnOnConsecutiveCalls($mockStringResponseGetOne, $mockStringResponseGetTwo);

      $handler = $this->createMock(HandlerInterface::class);
      $handler
        ->expects($this->once())
        ->method('collectUsersForDeletionList');
      $handler
        ->expects($this->once())
        ->method('removeUsersFromDeletionList');
      $handler
        ->expects($this->once())
        ->method('commitDeletionList');

      // Call the run function on Controller
        $this->controller->run($handler);
    }

    /**
     * Testing the Controller run() function
     *
     * Ensure TokenException is thrown when token is not acquired
     */
    public function testRunTokenException()
    {
        // Expect TokenException to be thrown
        $this->expectException(TokenException::class);

        $this->mockClient
            ->expects($this->once())
            ->method('post')
            ->with($this->mockUrl, $this->mockClientPostOptions)
            ->willThrowException(new TokenException('TokenException'));

      $handler = $this->createMock(HandlerInterface::class);
      $handler
        ->expects($this->never())
        ->method('collectUsersForDeletionList');
      $handler
        ->expects($this->never())
        ->method('removeUsersFromDeletionList');
      $handler
        ->expects($this->never())
        ->method('commitDeletionList');

      $this->controller->run($handler);
    }

    /**
     * Testing the Controller run() and getData() function
     *
     * Ensure NetworkException is thrown when acquiring data fails
     */
    public function testRunDataException()
    {
        // Expect DataException to be thrown
        $this->expectException(NetworkException::class);

        $this->mockClient
            ->expects($this->once())
            ->method('post')
            ->with($this->mockUrl, $this->mockClientPostOptions)
            ->willReturn($this->mockResponseInterfacePost);

        // Mock response from getBody function call
        $mockStreamInterfacePost = $this->createMock(StreamInterface::class);

        $this->mockResponseInterfacePost
            ->expects($this->once())
            ->method('getBody')
            ->willReturn($mockStreamInterfacePost);

        // Mock response from getContents function call
        $mockStringResponsePost = "{\"token_type\":\"mock_token_type\",\"expires_in\":1000,\"ext_expires_in\":1000,\"access_token\":\"mock_access_token\"}";

        $mockStreamInterfacePost
            ->expects($this->once())
            ->method('getContents')
            ->willReturn($mockStringResponsePost);

        // Now we need to handle the post function called in getData.

        $this->mockClient
            ->expects($this->once())
            ->method('get')
            ->with($this->mockGroupUrl, $this->mockClientGetOptions)
            ->willThrowException(new NetworkException('NetworkException'));

      $handler = $this->createMock(HandlerInterface::class);
      $handler
        ->expects($this->once())
        ->method('collectUsersForDeletionList');
      $handler
        ->expects($this->never())
        ->method('removeUsersFromDeletionList');
      $handler
        ->expects($this->never())
        ->method('commitDeletionList');

      // Call the run function on Controller
        $this->controller->run($handler);
    }

    /**
     * Testing the Controller run() function
     *
     * Ensure DataException is thrown when no users is in group
     */
    public function testRunNoUsersInGroup()
    {
        // Expect DataException to be thrown
        $this->expectException(DataException::class);

        $this->mockClient
            ->expects($this->once())
            ->method('post')
            ->with($this->mockUrl, $this->mockClientPostOptions)
            ->willReturn($this->mockResponseInterfacePost);

        // Mock response from getBody function call
        $mockStreamInterfacePost = $this->createMock(StreamInterface::class);

        $this->mockResponseInterfacePost
            ->expects($this->once())
            ->method('getBody')
            ->willReturn($mockStreamInterfacePost);

        // Mock response from getContents function call
        $mockStringResponsePost = "{\"token_type\":\"mock_token_type\",\"expires_in\":1000,\"ext_expires_in\":1000,\"access_token\":\"mock_access_token\"}";

        $mockStreamInterfacePost
            ->expects($this->once())
            ->method('getContents')
            ->willReturn($mockStringResponsePost);

        // Now we need to handle the post function called in getData.

        $mockResponseInterfaceGet = $this->createMock(ResponseInterface::class);

        $this->mockClient
            ->expects($this->exactly(1))
            ->method('get')
            ->with($this->mockGroupUrl, $this->mockClientGetOptions)
            ->willReturn($mockResponseInterfaceGet);

        // Mock response from getBody function call
        $mockStreamInterfaceGet = $this->createMock(StreamInterface::class);

        $mockResponseInterfaceGet
            ->expects($this->exactly(1))
            ->method('getBody')
            ->willReturn($mockStreamInterfaceGet);

        // Mock response array
        $mockResponseArray = [
            '@odata.type' => 'mock_data_context',
            'value' => [

            ],
        ];

        // The respective json encoding as this is what we get from getContents function call
        $mockStringResponseGetOne = json_encode($mockResponseArray);

        $mockStreamInterfaceGet
            ->expects($this->exactly(1))
            ->method('getContents')
            ->willReturn($mockStringResponseGetOne);

      $handler = $this->createMock(HandlerInterface::class);
      $handler
        ->expects($this->once())
        ->method('collectUsersForDeletionList');
      $handler
        ->expects($this->never())
        ->method('removeUsersFromDeletionList');
      $handler
        ->expects($this->never())
        ->method('commitDeletionList');

      // Call the run function on Controller
        $this->controller->run($handler);
    }

    private function setUpControllerParameters()
    {
        // Setup mock options for Controller options parameter
        $this->mockOptions = [
            'tenant_id' => 'mock_tenant_id',
            'client_id' => 'mock_client_id',
            'client_secret' => 'mock_client_secret',
            'group_id' => 'mock_group_id',
        ];

        // Mock Client for the Controller
        // Add methods post and get
        $mockClientBuilder = $this->getMockBuilder(Client::class)
            ->addMethods(['post', 'get']);

        $this->mockClient = $mockClientBuilder->getMock();
    }

    private function setUpClientPostCallParametersAndResponse()
    {
        // Mock arguments for post call on client
        $this->mockUrl = 'https://login.microsoftonline.com/' . $this->mockOptions['tenant_id'] . '/oauth2/v2.0/token';

        $this->mockClientPostOptions = [
            'form_params' => [
                'client_id' => $this->mockOptions['client_id'],
                'client_secret' => $this->mockOptions['client_secret'],
                'scope' => 'https://graph.microsoft.com/.default',
                'grant_type' => 'client_credentials',
            ],
        ];

        // Mock response from Client post function call
        $this->mockResponseInterfacePost = $this->createMock(ResponseInterface::class);
    }

    private function setUpClientGetCallParameters()
    {
        // Mock arguments and response for the get function call on client
        $this->mockGroupUrl = 'https://graph.microsoft.com/v1.0/groups/' . $this->mockOptions['group_id'] . '/members';

        $this->mockClientGetOptions = [
            'headers' => [
                'authorization' => 'mock_token_type' . ' ' . 'mock_access_token',
            ],
        ];
    }
}

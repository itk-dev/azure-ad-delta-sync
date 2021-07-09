<?php

namespace ItkDev\Adgangsstyring\Tests;

use ItkDev\Adgangsstyring\Controller;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ControllerTest extends TestCase
{
    /**
     * Testing the Controller run() function.
     *
     * Ensure the function loops while group url contain a next link
     */
    public function testRun()
    {
        // Mock options for the Controller
        $mockOptions = [
            'tenantId' => 'mock_tenant_id',
            'clientId' => 'mock_client_id',
            'clientSecret' => 'mock_client_secret',
            'groupId' => 'mock_group_id',
        ];

        // Mock EventDispatcher for the Controller
        $mockEventDispatcher = $this->createMock(EventDispatcherInterface::class);

        // Expect dispatch method called 4 times
        // One StartEvent, two UserDataEvents and one CommitEvent
        $mockEventDispatcher
            ->expects($this->exactly(4))
            ->method('dispatch');

        // Mock Client for the Controller
        // Add methods post and get
        $mockClientBuilder = $this->getMockBuilder(Client::class)
            ->addMethods(['post', 'get']);

        $mockClient = $mockClientBuilder->getMock();

        // Create Controller
        $controller = new Controller($mockEventDispatcher, $mockOptions, $mockClient);

        // Mock arguments for post call on client
        $mockUrl = 'https://login.microsoftonline.com/' . $mockOptions['tenantId'] . '/oauth2/v2.0/token';

        $mockClientPostOptions = [
            'form_params' => [
                'client_id' => $mockOptions['clientId'],
                'client_secret' => $mockOptions['clientSecret'],
                'scope' => 'https://graph.microsoft.com/.default',
                'grant_type' => 'client_credentials',
            ],
        ];

        // Mock response from Client post function call
        $mockResponseInterfacePost = $this->createMock(ResponseInterface::class);

        $mockClient
            ->expects($this->once())
            ->method('post')
            ->with($mockUrl, $mockClientPostOptions)
            ->willReturn($mockResponseInterfacePost);

        // Mock response from getBody function call
        $mockStreamInterfacePost = $this->createMock(StreamInterface::class);

        $mockResponseInterfacePost
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

        // Mock arguments and response for first and second get function call on client
        $mockGroupUrl = 'https://graph.microsoft.com/v1.0/groups/' . $mockOptions['groupId'] . '/members';

        $mockClientGetOptions = [
            'headers' => [
                'authorization' => 'mock_token_type' . ' ' . 'mock_access_token',
            ],
        ];

        $mockNextLink = 'mock_next_link';

        $mockResponseInterfaceGet = $this->createMock(ResponseInterface::class);

        $mockClient
            ->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive([$mockGroupUrl, $mockClientGetOptions], [$mockNextLink, $mockClientGetOptions])
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
                    'surname' => 'mock_surname_1'
                ],
                '1' => [
                    'id' => 'mock_id_2',
                    'surname' => 'mock_surname_2'
                ],
            ],
        ];

        $mockResponseArrayTwo = [
            '@odata.type' => 'mock_data_context',
            'value' => [
                '0' => [
                    'id' => 'mock_id_3',
                    'surname' => 'mock_surname_3'
                ],
                '1' => [
                    'id' => 'mock_id_4',
                    'surname' => 'mock_surname_4'
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

        // Call the run function on Controller
        $controller->run();
    }


    /**
     * Testing the Controller run() function.
     *
     * Ensure the function loops while group url contain a next link
     */
    public function testRun2()
    {
        // Mock options for the Controller
        $mockOptions = [
            'tenantId' => 'mock_tenant_id',
            'clientId' => 'mock_client_id',
            'clientSecret' => 'mock_client_secret',
            'groupId' => 'mock_group_id',
        ];

        // Mock EventDispatcher for the Controller
        $mockEventDispatcher = $this->createMock(EventDispatcherInterface::class);

        // Expect dispatch method called 3 times
        // One StartEvent, one UserDataEvent and one CommitEvent
        $mockEventDispatcher
            ->expects($this->exactly(3))
            ->method('dispatch');

        // Mock Client for the Controller
        // Add methods post and get
        $mockClientBuilder = $this->getMockBuilder(Client::class)
            ->addMethods(['post', 'get']);

        $mockClient = $mockClientBuilder->getMock();

        // Create Controller
        $controller = new Controller($mockEventDispatcher, $mockOptions, $mockClient);

        // Mock arguments for post call on client
        $mockUrl = 'https://login.microsoftonline.com/' . $mockOptions['tenantId'] . '/oauth2/v2.0/token';

        $mockClientPostOptions = [
            'form_params' => [
                'client_id' => $mockOptions['clientId'],
                'client_secret' => $mockOptions['clientSecret'],
                'scope' => 'https://graph.microsoft.com/.default',
                'grant_type' => 'client_credentials',
            ],
        ];

        // Mock response from Client post function call
        $mockResponseInterfacePost = $this->createMock(ResponseInterface::class);

        $mockClient
            ->expects($this->once())
            ->method('post')
            ->with($mockUrl, $mockClientPostOptions)
            ->willReturn($mockResponseInterfacePost);

        // Mock response from getBody function call
        $mockStreamInterfacePost = $this->createMock(StreamInterface::class);

        $mockResponseInterfacePost
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

        // Mock arguments and response for first and second get function call on client
        $mockGroupUrl = 'https://graph.microsoft.com/v1.0/groups/' . $mockOptions['groupId'] . '/members';

        $mockClientGetOptions = [
            'headers' => [
                'authorization' => 'mock_token_type' . ' ' . 'mock_access_token',
            ],
        ];

        $mockNextLink = 'mock_next_link';

        $mockResponseInterfaceGet = $this->createMock(ResponseInterface::class);

        $mockClient
            ->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive([$mockGroupUrl, $mockClientGetOptions], [$mockNextLink, $mockClientGetOptions])
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
                    'surname' => 'mock_surname_1'
                ],
                '1' => [
                    'id' => 'mock_id_2',
                    'surname' => 'mock_surname_2'
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

        // Call the run function on Controller
        $controller->run();
    }
}

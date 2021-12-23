<?php

declare(strict_types=1);

/**
 * Copyright (c) 2021 Kai Sassnowski
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/roach-php/roach
 */

namespace RoachPHP\Tests\Spider\Middleware;

use Generator;
use PHPUnit\Framework\TestCase;
use RoachPHP\Http\Request;
use RoachPHP\Http\Response;
use RoachPHP\ItemPipeline\Item;
use RoachPHP\ItemPipeline\ItemInterface;
use RoachPHP\Spider\Middleware\MiddlewareAdapter;
use RoachPHP\Spider\Middleware\ItemMiddlewareInterface;
use RoachPHP\Spider\Middleware\MiddlewareInterface;
use RoachPHP\Spider\Middleware\RequestMiddlewareInterface;
use RoachPHP\Spider\Middleware\ResponseMiddlewareInterface;
use RoachPHP\Support\Configurable;
use RoachPHP\Tests\InteractsWithRequestsAndResponses;

/**
 * @internal
 */
final class HandlerAdapterTest extends TestCase
{
    use InteractsWithRequestsAndResponses;

    /**
     * @dataProvider itemHandlerProvider
     */
    public function testItemHandlerImplementation(callable $testCase): void
    {
        $handler = new class() implements ItemMiddlewareInterface {
            use Configurable;

            public function handleItem(ItemInterface $item, Response $response): ItemInterface
            {
                return $item->set('::key::', '::value::');
            }
        };
        $adapter = new MiddlewareAdapter($handler);

        $testCase($adapter);
    }

    public function itemHandlerProvider(): Generator
    {
        yield 'return request unchanged' => [function (MiddlewareAdapter $adapter): void {
            $response = $this->makeResponse($this->makeRequest('::url-a::'));
            $request = $this->makeRequest('::url-b::');

            $result = $adapter->handleRequest($request, $response);

            self::assertEquals($request, $result);
        }];

        yield 'return response unchanged' => [function (MiddlewareAdapter $adapter): void {
            $response = $this->makeResponse($this->makeRequest('::url-a::'));

            $result = $adapter->handleResponse($response);

            self::assertEquals($response, $result);
        }];

        yield 'call handler function for items' => [function (MiddlewareAdapter $adapter): void {
            $response = $this->makeResponse($this->makeRequest());
            $item = new Item([]);

            $result = $adapter->handleItem($item, $response);

            self::assertEquals(['::key::' => '::value::'], $result->all());
        }];
    }

    /**
     * @dataProvider requestHandlerProvider
     */
    public function testRequestHandlerImplementation(callable $testCase): void
    {
        $handler = new class() implements RequestMiddlewareInterface {
            use Configurable;

            public function handleRequest(Request $request, Response $response): Request
            {
                return $request->withMeta('::key::', '::value::');
            }
        };
        $adapter = new MiddlewareAdapter($handler);

        $testCase($adapter);
    }

    public function requestHandlerProvider(): Generator
    {
        yield 'return response unchanged' => [function (MiddlewareAdapter $adapter): void {
            $response = $this->makeResponse($this->makeRequest());

            $result = $adapter->handleResponse($response);

            self::assertEquals($response, $result);
        }];

        yield 'return item unchanged' => [function (MiddlewareAdapter $adapter): void {
            $item = new Item(['::key::' => '::value::']);
            $response = $this->makeResponse($this->makeRequest());

            $result = $adapter->handleItem($item, $response);

            self::assertSame(['::key::' => '::value::'], $result->all());
        }];

        yield 'call handler function for requests' => [function (MiddlewareAdapter $adapter): void {
            $response = $this->makeResponse($this->makeRequest('::url-a::'));
            $request = $this->makeRequest('::url-b::');

            $result = $adapter->handleRequest($request, $response);

            self::assertSame('::value::', $result->getMeta('::key::'));
        }];
    }

    /**
     * @dataProvider responseHandlerProvider
     */
    public function testResponseHandlerImplementation(callable $testCase): void
    {
        $handler = new class() implements ResponseMiddlewareInterface {
            use Configurable;

            public function handleResponse(Response $response): Response
            {
                return $response->withMeta('::key::', '::value::');
            }
        };
        $adapter = new MiddlewareAdapter($handler);

        $testCase($adapter);
    }

    public function responseHandlerProvider(): Generator
    {
        yield 'return item unchanged' => [function (MiddlewareAdapter $adapter): void {
            $item = new Item(['::key::' => '::value::']);
            $response = $this->makeResponse($this->makeRequest());

            $result = $adapter->handleItem($item, $response);

            self::assertSame(['::key::' => '::value::'], $result->all());
        }];

        yield 'return request unchanged' => [function (MiddlewareAdapter $adapter): void {
            $response = $this->makeResponse($this->makeRequest('::url-a::'));
            $request = $this->makeRequest('::url-b::');

            $result = $adapter->handleRequest($request, $response);

            self::assertEquals($request, $result);
        }];

        yield 'call handler function for responses' => [function (MiddlewareAdapter $adapter): void {
            $response = $this->makeResponse($this->makeRequest('::url-a::'));

            $result = $adapter->handleResponse($response);

            self::assertSame('::value::', $result->getMeta('::key::'));
        }];
    }
}
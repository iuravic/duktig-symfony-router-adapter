<?php
namespace Duktig\Route\Router\Adapter\SymfonyRouter;

use PHPUnit\Framework\TestCase;
use Duktig\Route\Router\Adapter\SymfonyRouter\SymfonyRouterAdapter;
use Duktig\Core\Route\RouteProvider;
use Duktig\Core\Route\Route;
use Duktig\Core\Route\Router\RouterMatch;
use Duktig\Http\Factory\Adapter\Guzzle\GuzzleServerRequestFactory;
use Duktig\Core\Exception\HttpException;

class SymfonyRouterAdapterTest extends TestCase
{
    private $dispatcherMock;
    private $resolverMock;
    private $adapter;
    
    public function tearDown()
    {
        parent::tearDown();
        if ($container = \Mockery::getContainer()) {
            $this->addToAssertionCount($container->mockery_getExpectationCount());
        }
        \Mockery::close();
    }
    
    public function testMatchesRoute()
    {
        $routeproviderMock = \Mockery::mock(RouteProvider::class);
        $routeproviderMock->shouldReceive('getRoutes')
            ->andReturn([$this->getTestRoute()]);
        $routeproviderMock->shouldReceive('getRouteFromName')
            ->with('first-test-route')
            ->andReturn($this->getTestRoute());
        
        $adapter = new SymfonyRouterAdapter($routeproviderMock);
        $request = $this->getRequest('/first-test-route-path/page/testParam');
        $routeMatch = $adapter->match($request);
        
        $this->assertInstanceOf(RouterMatch::class, $routeMatch,
            "Router's match method did not return an object of expected type");
    }
    
    public function testNoRouteMatched()
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage("No route matched for path /invalid-uri");
        
        $routeproviderMock = \Mockery::mock(RouteProvider::class);
        $routeproviderMock->shouldReceive('getRoutes')
            ->andReturn([$this->getTestRoute()]);
        $routeproviderMock->shouldReceive('getRouteFromName')
            ->with('first-test-route')
            ->andReturn($this->getTestRoute());
        
        $adapter = new SymfonyRouterAdapter($routeproviderMock);
        $request = $this->getRequest('/invalid-uri');
        $adapter->match($request);
    }
    
    private function getTestRoute()
    {
        return new Route(
            'first-test-route',
            [],
            '/first-test-route-path/page/{uriParam1}{trailingSlash}',
            [],
            ['trailingSlash' => '/?',],
            'ResolvableHandler',
            'handlerMethod'
        );
    }
    
    private function getRequest(string $uri, string $method = 'GET')
    {
        return (new GuzzleServerRequestFactory)->createServerRequestFromArray([
            'REQUEST_METHOD' => $method,
            'REQUEST_URI' => $uri,
        ]);
    }
}
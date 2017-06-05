<?php
namespace Duktig\Route\Router\Adapter\SymfonyRouter;

use Duktig\Core\Route\Router\RouterInterface;
use Duktig\Core\Route\RouteProvider;
use Duktig\Core\Route\Router\RouterMatch;
use Duktig\Core\Exception\HttpException;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route as SymfonyRoute;

class SymfonyRouterAdapter implements RouterInterface
{
    /**
     * @var RouteProvider $routeProvider Route provider service
     */
    private $routeProvider;
    
    /**
     * @var array $routes An array of \Duktig\Core\Route\Route objects
     */
    private $routes = [];
    
    /**
     * @var RouteCollection $syRouteCollection
     */
    private $syRouteCollection;
    
    /**
     * @param array $routesArr Array of Route elements
     * @param RouterMatch $routerMatch
     */
    public function __construct(RouteProvider $routeProvider)
    {
        $this->routeProvider = $routeProvider;
        $this->setRoutes();
    }
    
    /**
     * Converts the Route objects provided by the RouteProvider service into a 
     * Sy RouteCollection.
     */
    private function setRoutes() : void
    {
        $this->routes = $this->routeProvider->getRoutes();
        $this->syRouteCollection = new RouteCollection();
        foreach ($this->routes as $route) {
            $syRoute = new SymfonyRoute(
                $route->getPath(),
                $route->getParamsDefaults(),
                $route->getParamsRequirements(),
                [],
                '',
                [],
                $route->getMethods()
            );
            $this->syRouteCollection->add($route->getName(), $syRoute);
        }
    }

    /**
     * {@inheritDoc}
     * @see \Duktig\Core\Route\Router\RouterInterface::match()
     */
    public function match(ServerRequestInterface $request) : RouterMatch
    {
        $path = $request->getUri()->getPath();
        $syContext = new RequestContext('/');
        $syMatcher = new UrlMatcher($this->syRouteCollection, $syContext);
        try {
            $params = $syMatcher->match($path);
        } catch (\Throwable $e) {
            throw new HttpException(404, "No route matched for path $path",
                null, $e);
        }
        
        $matchedRouteName = $params['_route'];
        unset($params['_route']);
        $route = $this->routeProvider->getRouteFromName($matchedRouteName);
        $routerMatch = new RouterMatch($route, $params);
        return $routerMatch;
    }
}
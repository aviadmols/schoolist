<?php
declare(strict_types=1);

namespace App\Core;

class Router
{
    private array $routes = [];
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function get(string $path, string $handler, array $middleware = []): void
    {
        $this->addRoute('GET', $path, $handler, $middleware);
    }

    public function post(string $path, string $handler, array $middleware = []): void
    {
        $this->addRoute('POST', $path, $handler, $middleware);
    }

    public function put(string $path, string $handler, array $middleware = []): void
    {
        $this->addRoute('PUT', $path, $handler, $middleware);
    }

    public function delete(string $path, string $handler, array $middleware = []): void
    {
        $this->addRoute('DELETE', $path, $handler, $middleware);
    }

    private function addRoute(string $method, string $path, string $handler, array $middleware): void
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
            'middleware' => $middleware
        ];
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $uri = rtrim($uri, '/') ?: '/';

        // Debug logging
        error_log("Router dispatch: method=$method, uri=$uri, total_routes=" . count($this->routes));

        $pathFound = false;
        $allowedMethods = [];

        foreach ($this->routes as $route) {
            $pattern = $this->convertToRegex($route['path']);
            if (preg_match($pattern, $uri, $matches)) {
                error_log("Route matched: {$route['method']} {$route['path']} (pattern: $pattern) -> {$route['handler']}");
                $pathFound = true;
                $allowedMethods[] = $route['method'];
                
                if ($route['method'] !== $method) {
                    continue;
                }
                // Remove full match, keep only named groups
                array_shift($matches);

                // Apply middleware
                foreach ($route['middleware'] as $mw) {
                    error_log("Applying middleware: $mw");
                    if (!$this->applyMiddleware($mw, $matches)) {
                        error_log("Middleware $mw blocked the request");
                        return;
                    }
                    error_log("Middleware $mw passed");
                }

                // Execute handler
                try {
                    error_log("Executing handler: {$route['handler']}");
                    [$controller, $method] = explode('@', $route['handler']);
                    $controllerClass = "App\\Controllers\\{$controller}";
                    
                    error_log("Controller class: $controllerClass");
                    
                    if (!class_exists($controllerClass)) {
                        error_log("Controller class not found: $controllerClass");
                        http_response_code(404);
                        $response = $this->container->get(\App\Core\Response::class);
                        if ($response) {
                            $response->json(['ok' => false, 'error_code' => 'NOT_FOUND', 'message_he' => 'Controller לא נמצא'], 404);
                        }
                        return;
                    }

                    error_log("Creating controller instance: $controllerClass");
                    $controllerInstance = new $controllerClass($this->container);
                    
                    if (!method_exists($controllerInstance, $method)) {
                        error_log("Method not found: $controllerClass::$method");
                        http_response_code(404);
                        $response = $this->container->get(\App\Core\Response::class);
                        if ($response) {
                            $response->json(['ok' => false, 'error_code' => 'NOT_FOUND', 'message_he' => 'Method לא נמצא'], 404);
                        }
                        return;
                    }

                    error_log("Calling method: $controllerClass::$method");
                    $controllerInstance->$method($matches);
                    error_log("Method completed: $controllerClass::$method");
                    return;
                } catch (Throwable $e) {
                    error_log("Router error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
                    $response = $this->container->get(\App\Core\Response::class);
                    if ($response) {
                        $response->json([
                            'ok' => false,
                            'error_code' => 'INTERNAL_ERROR',
                            'message_he' => 'שגיאה פנימית: ' . $e->getMessage()
                        ], 500);
                    }
                    return;
                }
            }
        }

        // If path was found but method doesn't match, return 405
        if ($pathFound) {
            http_response_code(405);
            $response = $this->container->get(Response::class);
            $response->json([
                'ok' => false,
                'error_code' => 'METHOD_NOT_ALLOWED',
                'message_he' => 'Method לא מותר. מותרים: ' . implode(', ', array_unique($allowedMethods))
            ], 405);
            return;
        }

        // Path not found
        $this->handleNotFound();
    }

    /**
     * Handles 404 errors by returning JSON for API calls or rendering a 404 view.
     */
    private function handleNotFound(): void
    {
        http_response_code(404);
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $isApi = str_starts_with($uri, '/api/') || str_starts_with($uri, '/setup/');

        $response = $this->container->get(Response::class);
        if ($isApi) {
            $response->json(['ok' => false, 'error_code' => 'NOT_FOUND', 'message_he' => 'דף לא נמצא']);
        } else {
            $response->view('error/404');
        }
    }

    private function convertToRegex(string $path): string
    {
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $path);
        return '#^' . $pattern . '$#';
    }

    private function applyMiddleware(string $name, array $params): bool
    {
        $middlewareClass = "App\\Middleware\\" . ucfirst($name) . 'Middleware';
        
        if (!class_exists($middlewareClass)) {
            return true;
        }

        $middleware = new $middlewareClass($this->container);
        return $middleware->handle($params);
    }
}


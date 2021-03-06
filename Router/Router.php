<?php
namespace Neton\DirectBundle\Router;

use Symfony\Component\DependencyInjection\ContainerAware;
use Neton\DirectBundle\Api\ControllerApi;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request as FoundationRequest;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Router is the ExtDirect Router class.
 *
 * It provide the ExtDirect Router mechanism.
 *
 * @author Otavio Fernandes <otavio@neton.com.br>
 */
class Router
{
    /**
     * The ExtDirect Request object.
     * 
     * @var Neton\DirectBundle\Request
     */
    protected $request;
    
    /**
     * The ExtDirect Response object.
     * 
     * @var Neton\DirectBundle\Response
     */
    protected $response;
    
    /**
     * The application container.
     * 
     * @var Symfony\Component\DependencyInjection\Container
     */
    protected $container;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    protected $httpKernel;
    
    /**
     * Initialize the router object.
     * 
     * @param Container $container
     */
    public function __construct($container)
    {
        $this->container = $container;
        $this->request = new Request($container->get('request'));
        $this->response = new Response($this->request->getCallType(), $this->request->isUpload());
        $this->defaultAccess = $container->getParameter('direct.api.default_access');
        $this->session = $this->container->get('session')->get($container->getParameter('direct.api.session_attribute'));
        $this->eventDispatcher = $this->container->get('event_dispatcher');
        $this->httpKernel = $this->container->get('http_kernel');
    }

    /**
     * Do the ExtDirect routing processing.
     *
     * @return JSON
     */
    public function route()
    {
        $batch = array();
        
        foreach ($this->request->getCalls() as $call) {
            $batch[] = $this->dispatch($call);
        }

        return $this->response->encode($batch);
    }

    /**
     * Dispatch a remote method call.
     * 
     * @param  Neton\DirectBundle\Router\Call $call
     * @return Mixed
     */
    private function dispatch($call)
    {
        $api = new ControllerApi($this->container, $this->getControllerClass($call->getAction()));

        $controller = $this->resolveController($call->getAction());

        $method = $call->getMethod()."Action";
        $accessType = $api->getMethodAccess($method);

        if (!is_callable(array($controller, $method))) {
            //todo: throw an exception method not callable
            return false;
        } else

        if ($this->defaultAccess == 'secure' && $accessType != 'anonymous'){
            if (!$this->session){
                $result = $call->getException(new \Exception('Access denied!'));
            }
        } else if ($accessType == 'secure'){
            if (!$this->session){
                $result = $call->getException(new \Exception('Access denied!'));
            }
        } else if ('form' == $this->request->getCallType()) {
            $result = $call->getResponse($controller->$method($call->getData(), $this->request->getFiles()));            
        }

        if (!isset($result)){
            try {
                $callable = array($controller, $method);

                $request = new FoundationRequest(array(), array('params' => $call->getData()), array('params' => $call->getData()));

                $event = new FilterControllerEvent($this->httpKernel, $callable, $request, HttpKernelInterface::MASTER_REQUEST);
                $this->eventDispatcher->dispatch(KernelEvents::CONTROLLER, $event);
                $callable = $event->getController();

                $resolver = new ControllerResolver();

                // controller arguments
                $arguments = $resolver->getArguments($request, $callable);

                // call controller
                $result = call_user_func_array($callable, $arguments);

                //$result = call_user_func_array(array($controller, $method), $call->getData());
                //$result = $controller->$method($call->getData());
                $result = $call->getResponse($result);
            }catch(\Exception $e){
                $result = $call->getException($e);
            }                  
        }

        return $result;
    }

    /**
     * Resolve the called controller from action.
     * 
     * @param  string $action
     * @return <type>
     */
    private function resolveController($action)
    {
        $class = $this->getControllerClass($action);

        try {
            $controller = new $class();

            if ($controller instanceof ContainerAware) {
                $controller->setContainer($this->container);
            }

            return $controller;
        } catch(Exception $e) {
            // todo: handle exception
        }
    }

    /**
     * Return the controller class name.
     *
     * @param $action
     */
    private function getControllerClass($action)
    {
        list($bundleName, $controllerName) = explode('_',$action);
        $bundleName.= "Bundle";

        $bundle = $this->container->get('kernel')->getBundle($bundleName);
        $namespace = $bundle->getNamespace()."\\Controller";

        $class = $namespace."\\".$controllerName."Controller";

        return $class;
    }
}

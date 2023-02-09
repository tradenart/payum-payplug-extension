<?php 
namespace Tradenart\Payum\Payplug;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Router
{
    private $router;
    
    public function __contruct(UrlGeneratorInterface $router)
    {
        $this->router = $router;
        
        
        die(0);
    }
    
    public function getRouter()
    {
        return $this->router;
    }
}

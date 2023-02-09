<?php
namespace Tradenart\Payum\Payplug;

use Http\Message\MessageFactory;
use Payum\Core\Exception\Http\HttpException;
use Payum\Core\HttpClientInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Payum\Core\Reply\HttpPostRedirect;

class Api
{
    
    public const STATUS_CREATED = 'new';
    
    public const STATUS_CAPTURED = 'captured';
    
    public const STATUS_CANCELED = 'canceled';
    
    public const STATUS_REFUSED = 'refused';
    
    
    /**
     * @var HttpClientInterface
     */
    protected $client;

    /**
     * @var MessageFactory
     */
    protected $messageFactory;

    /**
     * @var array
     */
    protected $options = [];
    
    protected $router;

    /**
     * @param array               $options
     * @param HttpClientInterface $client
     * @param MessageFactory      $messageFactory
     *
     * @throws \Payum\Core\Exception\InvalidArgumentException if an option is invalid
     */
    public function __construct(array $options, HttpClientInterface $client, MessageFactory $messageFactory, $router)
    {
        $this->options = $options;
        $this->client = $client;
        $this->messageFactory = $messageFactory;
        $this->router = $router->getRouter();
    }
    
    public function initPayplug()
    {
        \Payplug\Payplug::init(array(
            'secretKey' => $this->getSecretKey(),
            'apiVersion' => $this->options['api_version'],
        ));
    }
    
    private function getSecretKey()
    {
        if($this->options['sandbox'] == true){
            return $this->options['secret_key_dev'];
        }else{
            return $this->options['secret_key_prod'];
        }
    }
}

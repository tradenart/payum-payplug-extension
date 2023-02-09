<?php
namespace Tradenart\Payum\Payplug\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Request\Capture;
use Payum\Core\Exception\RequestNotSupportedException;
use Tradenart\Payum\Payplug\Action\Api\BaseApiAwareAction;
use Tradenart\Payum\Payplug\Api;
use Payum\Core\Security\TokenInterface;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\Security\GenericTokenFactoryAwareInterface;
use Payum\Core\Security\GenericTokenFactoryAwareTrait;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Reply\HttpRedirect;

class CaptureAction extends BaseApiAwareAction implements ActionInterface, GatewayAwareInterface, GenericTokenFactoryAwareInterface
{
    use GatewayAwareTrait;
    use GenericTokenFactoryAwareTrait;

    /**
     * {@inheritDoc}
     *
     * @param Capture $request
     */
    
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);
        
        $details = ArrayObject::ensureArrayObject($request->getModel());
        
        $httpRequest = new GetHttpRequest();
        $this->gateway->execute($httpRequest);
        
        $this->api->initPayplug();
                
        if(isset($details['status']) && isset($details['payment_id'])){
            if (Api::STATUS_CREATED !== $details['status']) {
                return;
            }
            
            $times = 0;
            
            do {
                $payment = \Payplug\Payment::retrieve((string) $details['payment_id']);
                
                if ($payment->is_paid) {
                    $details['status'] = Api::STATUS_CAPTURED;
                    
                    return;
                }
                
                sleep(1);
                
                ++$times;
            } while ($times < 3);
            
            return;
        }
        
        
        $notifyToken = $this->tokenFactory->createNotifyToken(
            $request->getToken()->getGatewayName(),
            $request->getToken()->getDetails()
        );
        
        $details['notification_url'] = $notifyToken->getTargetUrl();
        
        $details['hosted_payment'] = [
            'return_url' => $request->getToken()->getAfterUrl(),
            'cancel_url' => $request->getToken()->getTargetUrl() . '?&' . http_build_query(['status' => Api::STATUS_CANCELED]),
        ];
        
        $payment = \Payplug\Payment::create($details->getArrayCopy());
        
        $details['payment_id'] = $payment->id;
        $details['status'] = Api::STATUS_CREATED;
        
        throw new HttpRedirect($payment->hosted_payment->payment_url);
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Capture &&
            $request->getModel() instanceof \ArrayAccess
        ;
    }
}

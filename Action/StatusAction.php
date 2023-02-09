<?php
namespace Tradenart\Payum\Payplug\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\Request\GetStatusInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\Security\GenericTokenFactoryAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Security\GenericTokenFactoryAwareTrait;
use Tradenart\Payum\Payplug\Api;
use Tradenart\Payum\Payplug\Action\Api\BaseApiAwareAction;
use Payum\Core\Request\GetHttpRequest;

class StatusAction extends BaseApiAwareAction implements ActionInterface, GatewayAwareInterface, GenericTokenFactoryAwareInterface
{
    use GatewayAwareTrait;
    use GenericTokenFactoryAwareTrait;    
    
    /**
     * {@inheritDoc}
     *
     * @param GetStatusInterface $request
     */
    public function execute($request)
    {         
                
        RequestNotSupportedException::assertSupports($this, $request);
        
        $model = ArrayObject::ensureArrayObject($request->getModel());
        
        $details = $request->getFirstModel()->getDetails();

        if (!isset($details['status']) || !isset($details['payment_id'])) {
            $request->markNew();
            
            return;
        }
        
        if (isset($httpRequest->query['status']) && Api::STATUS_CANCELED === $httpRequest->query['status']) {
            $details['status'] = Api::STATUS_CANCELED;
            
        }
        
        
        $this->api->initPayplug();
        
        $input = file_get_contents('php://input');
        
        if($input){
            $resource = \Payplug\Notification::treat($input);
            
            if ($resource instanceof \Payplug\Resource\Payment && $resource->is_paid) {
                $details['status'] = Api::STATUS_CAPTURED;
            }else{
                $details['status'] = Api::STATUS_REFUSED;
            }
        }else{
            $payment = \Payplug\Payment::retrieve($details['payment_id']);
            
            if($payment->is_paid){
                $details['status'] = Api::STATUS_CAPTURED;
            }else{
                $details['status'] = Api::STATUS_REFUSED;
            }
        }
                
        switch ($details['status']) {
            case Api::STATUS_CANCELED:
                $request->markCanceled();
                break;
            case Api::STATUS_CREATED:
                $request->markPending();
                break;
            case Api::STATUS_CAPTURED:
                $request->markCaptured();
                break;
            case Api::STATUS_REFUSED:
                $request->markFailed();
                break;
            default:
                $request->markUnknown();
                break;
        }        
        
        $request->getFirstModel()->setDetails($details);
                
    }
    
    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
        $request instanceof GetStatusInterface &&
        $request->getModel() instanceof \ArrayAccess
        ;
    }
}

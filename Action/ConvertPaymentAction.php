<?php
namespace Tradenart\Payum\Payplug\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Model\PaymentInterface;
use Payum\Core\Request\Convert;
use Tradenart\Payum\Payplug\Api;
use Payum\Core\Request\GetCurrency;
use Payum\Core\Action\GatewayAwareAction;

class ConvertPaymentAction extends GatewayAwareAction implements ActionInterface, GatewayAwareInterface
{
    use GatewayAwareTrait;

    /**
     * {@inheritDoc}
     *
     * @param Convert $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        /** @var PaymentInterface $payment */
        $payment = $request->getSource();        
        $details = ArrayObject::ensureArrayObject($payment->getDetails());
        
        $this->gateway->execute($currency = new GetCurrency($payment->getCurrencyCode()));
        
        $details['currency'] = $currency->alpha3;
        $details['amount'] = $payment->getTotalAmount();
        
        $details['metadata'] = array(
            'commande_id' =>  $payment->getCommande()->getId(),
        );
        
        $adrFact = $payment->getCommande()->getAdresseFacturation();
        
        $details['billing'] = array(
            'first_name' => $adrFact->getPrenom(),
            'last_name' => $adrFact->getNom(),
            'email' => $payment->getCommande()->getUtilisateur()->getEmail(),
            'address1' => $adrFact->getAdresse1(),
            'address2' => $adrFact->getAdresse2(),
            'postcode' => $adrFact->getCp(),
            'city' => $adrFact->getVille(),
            'country' => $adrFact->getPays(),
        );
        
        $adrLivr = $payment->getCommande()->getAdresseLivraison();
        
        $delivery_type = 'OTHER';
        
        if($adrFact->getId() == $adrLivr->getId()){
            $delivery_type = 'BILLING';
        }
        
        $details['shipping'] = array(
            'first_name' => $adrLivr->getPrenom(),
            'last_name' => $adrLivr->getNom(),
            'email' => $payment->getCommande()->getUtilisateur()->getEmail(),
            'address1' => $adrLivr->getAdresse1(),
            'address2' => $adrLivr->getAdresse2(),
            'postcode' => $adrLivr->getCp(),
            'city' => $adrLivr->getVille(),
            'country' => $adrLivr->getPays(),
            'delivery_type' => $delivery_type,
        );
        
        $request->setResult((array) $details);
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Convert &&
            $request->getSource() instanceof PaymentInterface &&
            $request->getTo() == 'array'
        ;
    }
}

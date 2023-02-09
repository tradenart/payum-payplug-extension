<?php

namespace Tradenart\Payum\Payplug\Action;

use ArrayAccess;
use Payplug\Notification;
use Payplug\Resource\Payment;
use Payum\Core\Request\Notify;
use App\Service\CommandeService;
use Tradenart\Payum\Payplug\Api;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Reply\HttpResponse;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payplug\Exception\PayplugException;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Bundle\PayumBundle\Controller\PayumController;
use Tradenart\Payum\Payplug\Action\Api\BaseApiAwareAction;

class NotifyAction extends BaseApiAwareAction implements ActionInterface, GatewayAwareInterface
{
	use GatewayAwareTrait;

	/**
	 * {@inheritDoc}
	 * @param Notify $request
	 */
	public function execute($request)
	{
		RequestNotSupportedException::assertSupports($this, $request);

		$details = ArrayObject::ensureArrayObject($request->getModel());

		$input = file_get_contents('php://input');

		$this->api->initPayplug();

		try {

			$resource = Notification::treat($input);

			if ($resource instanceof Payment && $resource->is_paid) {
				$details['status'] = Api::STATUS_CAPTURED;
			} else {
				$details['status'] = Api::STATUS_REFUSED;
			}


		}
		catch (PayplugException $exception) {
			$details['status'] = Api::STATUS_REFUSED;
		}

		$request->getFirstModel()->setDetails($details);

		throw new HttpResponse('ok', 200);

	}

	/**
	 * {@inheritDoc}
	 */
	public function supports($request)
	{
		return
			$request instanceof Notify &&
			$request->getModel() instanceof ArrayAccess;
	}
}

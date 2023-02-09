<?php
namespace Tradenart\Payum\Payplug;

use Tradenart\Payum\Payplug\Action\AuthorizeAction;
use Tradenart\Payum\Payplug\Action\CancelAction;
use Tradenart\Payum\Payplug\Action\ConvertPaymentAction;
use Tradenart\Payum\Payplug\Action\CaptureAction;
use Tradenart\Payum\Payplug\Action\NotifyAction;
use Tradenart\Payum\Payplug\Action\RefundAction;
use Tradenart\Payum\Payplug\Action\StatusAction;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayFactory;

class PayplugGatewayFactory extends GatewayFactory
{
    /**
     * {@inheritDoc}
     */
    protected function populateConfig(ArrayObject $config)
    {
        $config->defaults([
            'payum.factory_name' => 'payplug',
            'payum.factory_title' => 'payplug',
            'payum.action.capture' => new CaptureAction(),
            'payum.action.refund' => new RefundAction(),
            'payum.action.cancel' => new CancelAction(),
            'payum.action.notify' => new NotifyAction(),
            'payum.action.status' => new StatusAction(),
            'payum.action.convert_payment' => new ConvertPaymentAction(),
        ]);

        if (false == $config['payum.api']) {
            $config['payum.default_options'] = array(
                'secret_key_dev'         => null,
                'secret_key_prod'         => null,
                'api_version'         => null,
                'sandbox'        => true,
            );
            $config->defaults($config['payum.default_options']);
            $config['payum.required_options'] = [
                'secret_key_dev',
                'secret_key_prod',
                'api_version',
            ];

            $config['payum.api'] = function (ArrayObject $config) {
                $config->validateNotEmpty($config['payum.required_options']);

                return new Api((array) $config, $config['payum.http_client'], $config['httplug.message_factory'], new Router());
            };
        }
    }
}

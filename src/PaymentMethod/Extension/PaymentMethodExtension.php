<?php declare(strict_types=1);

namespace Shopware\PaymentMethod\Extension;

use Shopware\Api\Read\FactoryExtensionInterface;
use Shopware\Api\Search\QueryBuilder;
use Shopware\Api\Search\QuerySelection;
use Shopware\Context\Struct\TranslationContext;
use Shopware\PaymentMethod\Event\PaymentMethodBasicLoadedEvent;
use Shopware\PaymentMethod\Event\PaymentMethodDetailLoadedEvent;
use Shopware\PaymentMethod\Struct\PaymentMethodBasicStruct;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

abstract class PaymentMethodExtension implements FactoryExtensionInterface, EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            PaymentMethodBasicLoadedEvent::NAME => 'paymentMethodBasicLoaded',
            PaymentMethodDetailLoadedEvent::NAME => 'paymentMethodDetailLoaded',
        ];
    }

    public function joinDependencies(
        QuerySelection $selection,
        QueryBuilder $query,
        TranslationContext $context
    ): void {
    }

    public function getDetailFields(): array
    {
        return [];
    }

    public function getBasicFields(): array
    {
        return [];
    }

    public function hydrate(
        PaymentMethodBasicStruct $paymentMethod,
        array $data,
        QuerySelection $selection,
        TranslationContext $translation
    ): void {
    }

    public function paymentMethodBasicLoaded(PaymentMethodBasicLoadedEvent $event): void
    {
    }

    public function paymentMethodDetailLoaded(PaymentMethodDetailLoadedEvent $event): void
    {
    }
}

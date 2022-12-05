<?php declare(strict_types=1);

namespace Shopware\Tests\Unit\Elasticsearch\Admin\Subscriber;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\DataAbstractionLayer\Event\RefreshIndexEvent;
use Shopware\Elasticsearch\Admin\AdminIndexingBehavior;
use Shopware\Elasticsearch\Admin\AdminSearchRegistry;
use Shopware\Elasticsearch\Admin\Subscriber\RefreshIndexSubscriber;

/**
 * @package system-settings
 *
 * @internal
 *
 * @covers \Shopware\Elasticsearch\Admin\Subscriber\RefreshIndexSubscriber
 */
class RefreshIndexSubscriberTest extends TestCase
{
    public function testGetSubscribedEvents(): void
    {
        static::assertArrayHasKey(RefreshIndexEvent::class, RefreshIndexSubscriber::getSubscribedEvents());
    }

    public function testHanded(): void
    {
        $registry = $this->createMock(AdminSearchRegistry::class);
        $registry->expects(static::once())->method('iterate')->with(new AdminIndexingBehavior(false));

        $subscriber = new RefreshIndexSubscriber($registry);
        $subscriber->handled(new RefreshIndexEvent(false));
    }
}

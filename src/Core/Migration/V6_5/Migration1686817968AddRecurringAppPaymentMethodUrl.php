<?php declare(strict_types=1);

namespace Shopware\Core\Migration\V6_5;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 */
#[Package('checkout')]
class Migration1686817968AddRecurringAppPaymentMethodUrl extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1686817968;
    }

    public function update(Connection $connection): void
    {
        $this->swAddColumn(
            $connection,
            'app_payment_method',
            'recurring_url',
            'VARCHAR(255)'
        );
    }
}

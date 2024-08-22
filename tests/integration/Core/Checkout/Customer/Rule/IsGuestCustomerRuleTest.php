<?php declare(strict_types=1);

namespace Shopware\Tests\Integration\Core\Checkout\Customer\Rule;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\CheckoutRuleScope;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Customer\Rule\IsGuestCustomerRule;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

/**
 * @internal
 */
#[Package('services-settings')]
class IsGuestCustomerRuleTest extends TestCase
{
    use DatabaseTransactionBehaviour;
    use KernelTestBehaviour;

    private EntityRepository $ruleRepository;

    private EntityRepository $conditionRepository;

    private Context $context;

    protected function setUp(): void
    {
        $this->ruleRepository = $this->getContainer()->get('rule.repository');
        $this->conditionRepository = $this->getContainer()->get('rule_condition.repository');
        $this->context = Context::createDefaultContext();
    }

    public function testIfRuleIsConsistent(): void
    {
        $ruleId = Uuid::randomHex();
        $this->ruleRepository->create(
            [['id' => $ruleId, 'name' => 'Demo rule', 'priority' => 1]],
            $this->context
        );

        $id = Uuid::randomHex();
        $this->conditionRepository->create([
            [
                'id' => $id,
                'type' => (new IsGuestCustomerRule())->getName(),
                'ruleId' => $ruleId,
                'value' => [
                    'isGuest' => true,
                ],
            ],
        ], $this->context);

        static::assertNotNull($this->conditionRepository->search(new Criteria([$id]), $this->context)->get($id));
        $this->ruleRepository->delete([['id' => $ruleId]], $this->context);
        $this->conditionRepository->delete([['id' => $id]], $this->context);
    }

    public function testThatFilledCompanyInformationMatchesToTrue(): void
    {
        $salesChannelContext = $this->createMock(SalesChannelContext::class);
        $customer = new CustomerEntity();
        $customer->setGuest(true);

        $salesChannelContext->method('getCustomer')
            ->willReturn($customer);
        $isGuestCustomerRule = new IsGuestCustomerRule(true);

        $scope = new CheckoutRuleScope($salesChannelContext);

        static::assertTrue($isGuestCustomerRule->match($scope));
    }

    public function testThatUnfilledCompanyInformationMatchesToFalse(): void
    {
        $salesChannelContext = $this->createMock(SalesChannelContext::class);
        $customer = new CustomerEntity();
        $customer->setGuest(false);

        $salesChannelContext->method('getCustomer')
            ->willReturn($customer);
        $isCompanyRule = new IsGuestCustomerRule(true);

        $scope = new CheckoutRuleScope($salesChannelContext);

        static::assertFalse($isCompanyRule->match($scope));
    }
}

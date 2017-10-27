<?php declare(strict_types=1);

namespace Shopware\ProductDetail\Writer\Resource;

use Shopware\Api\Write\Field\BoolField;
use Shopware\Api\Write\Field\DateField;
use Shopware\Api\Write\Field\FkField;
use Shopware\Api\Write\Field\FloatField;
use Shopware\Api\Write\Field\IntField;
use Shopware\Api\Write\Field\ReferenceField;
use Shopware\Api\Write\Field\StringField;
use Shopware\Api\Write\Field\SubresourceField;
use Shopware\Api\Write\Field\TranslatedField;
use Shopware\Api\Write\Field\UuidField;
use Shopware\Api\Write\Flag\Required;
use Shopware\Api\Write\WriteResource;
use Shopware\Context\Struct\TranslationContext;
use Shopware\Framework\Writer\Resource\PremiumProductWriteResource;
use Shopware\Product\Writer\Resource\ProductWriteResource;
use Shopware\ProductDetail\Event\ProductDetailWrittenEvent;
use Shopware\ProductDetailPrice\Writer\Resource\ProductDetailPriceWriteResource;
use Shopware\ProductMedia\Writer\Resource\ProductMediaWriteResource;
use Shopware\Shop\Writer\Resource\ShopWriteResource;

class ProductDetailWriteResource extends WriteResource
{
    protected const UUID_FIELD = 'uuid';
    protected const SUPPLIER_NUMBER_FIELD = 'supplierNumber';
    protected const IS_MAIN_FIELD = 'isMain';
    protected const SALES_FIELD = 'sales';
    protected const ACTIVE_FIELD = 'active';
    protected const STOCK_FIELD = 'stock';
    protected const MIN_STOCK_FIELD = 'minStock';
    protected const WEIGHT_FIELD = 'weight';
    protected const POSITION_FIELD = 'position';
    protected const WIDTH_FIELD = 'width';
    protected const HEIGHT_FIELD = 'height';
    protected const LENGTH_FIELD = 'length';
    protected const EAN_FIELD = 'ean';
    protected const UNIT_UUID_FIELD = 'unitUuid';
    protected const PURCHASE_STEPS_FIELD = 'purchaseSteps';
    protected const MAX_PURCHASE_FIELD = 'maxPurchase';
    protected const MIN_PURCHASE_FIELD = 'minPurchase';
    protected const PURCHASE_UNIT_FIELD = 'purchaseUnit';
    protected const REFERENCE_UNIT_FIELD = 'referenceUnit';
    protected const RELEASE_DATE_FIELD = 'releaseDate';
    protected const SHIPPING_FREE_FIELD = 'shippingFree';
    protected const PURCHASE_PRICE_FIELD = 'purchasePrice';
    protected const ADDITIONAL_TEXT_FIELD = 'additionalText';
    protected const PACK_UNIT_FIELD = 'packUnit';

    public function __construct()
    {
        parent::__construct('product_detail');

        $this->primaryKeyFields[self::UUID_FIELD] = (new UuidField('uuid'))->setFlags(new Required());
        $this->fields[self::SUPPLIER_NUMBER_FIELD] = new StringField('supplier_number');
        $this->fields[self::IS_MAIN_FIELD] = new BoolField('is_main');
        $this->fields[self::SALES_FIELD] = new IntField('sales');
        $this->fields[self::ACTIVE_FIELD] = new BoolField('active');
        $this->fields[self::STOCK_FIELD] = new IntField('stock');
        $this->fields[self::MIN_STOCK_FIELD] = new IntField('min_stock');
        $this->fields[self::WEIGHT_FIELD] = new FloatField('weight');
        $this->fields[self::POSITION_FIELD] = new IntField('position');
        $this->fields[self::WIDTH_FIELD] = new FloatField('width');
        $this->fields[self::HEIGHT_FIELD] = new FloatField('height');
        $this->fields[self::LENGTH_FIELD] = new FloatField('length');
        $this->fields[self::EAN_FIELD] = new StringField('ean');
        $this->fields[self::UNIT_UUID_FIELD] = new StringField('unit_uuid');
        $this->fields[self::PURCHASE_STEPS_FIELD] = new IntField('purchase_steps');
        $this->fields[self::MAX_PURCHASE_FIELD] = new IntField('max_purchase');
        $this->fields[self::MIN_PURCHASE_FIELD] = new IntField('min_purchase');
        $this->fields[self::PURCHASE_UNIT_FIELD] = new FloatField('purchase_unit');
        $this->fields[self::REFERENCE_UNIT_FIELD] = new FloatField('reference_unit');
        $this->fields[self::RELEASE_DATE_FIELD] = new DateField('release_date');
        $this->fields[self::SHIPPING_FREE_FIELD] = new BoolField('shipping_free');
        $this->fields[self::PURCHASE_PRICE_FIELD] = new FloatField('purchase_price');
        $this->fields['premiumProducts'] = new SubresourceField(PremiumProductWriteResource::class);
        $this->fields['product'] = new ReferenceField('productUuid', 'uuid', ProductWriteResource::class);
        $this->fields['productUuid'] = (new FkField('product_uuid', ProductWriteResource::class, 'uuid'))->setFlags(new Required());
        $this->fields[self::ADDITIONAL_TEXT_FIELD] = new TranslatedField('additionalText', ShopWriteResource::class, 'uuid');
        $this->fields[self::PACK_UNIT_FIELD] = new TranslatedField('packUnit', ShopWriteResource::class, 'uuid');
        $this->fields['translations'] = new SubresourceField(ProductDetailTranslationWriteResource::class, 'languageUuid');
        $this->fields['prices'] = new SubresourceField(ProductDetailPriceWriteResource::class);
        $this->fields['productMedias'] = new SubresourceField(ProductMediaWriteResource::class);
    }

    public function getWriteOrder(): array
    {
        return [
            PremiumProductWriteResource::class,
            ProductWriteResource::class,
            self::class,
            ProductDetailTranslationWriteResource::class,
            ProductDetailPriceWriteResource::class,
            ProductMediaWriteResource::class,
        ];
    }

    public static function createWrittenEvent(array $updates, TranslationContext $context, array $rawData = [], array $errors = []): ProductDetailWrittenEvent
    {
        $event = new ProductDetailWrittenEvent($updates[self::class] ?? [], $context, $rawData, $errors);

        unset($updates[self::class]);

        /**
         * @var WriteResource
         * @var string[]      $identifiers
         */
        foreach ($updates as $class => $identifiers) {
            if (!array_key_exists($class, $updates) || count($updates[$class]) === 0) {
                continue;
            }

            $event->addEvent($class::createWrittenEvent($updates, $context));
        }

        return $event;
    }
}

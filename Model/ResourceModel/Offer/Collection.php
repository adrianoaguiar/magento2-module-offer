<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\Offer
 * @author    Aurelien Foucret <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\Offer\Model\ResourceModel\Offer;

use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Psr\Log\LoggerInterface;
use Smile\Offer\Api\Data\OfferInterface;
use Smile\Seller\Api\Data\SellerInterface;
use Smile\Seller\Api\Data\SellerInterfaceFactory;

/**
 * Offer Collection
 *
 * @category Smile
 * @package  Smile\Offer
 * @author   Aurelien Foucret <aurelien.foucret@smile.fr>
 */
class Collection extends AbstractCollection
{
    /**
     * @var MetadataPool $metadataPool
     */
    private $metadataPool;

    /**
     * @var \Smile\Seller\Api\Data\SellerInterfaceFactory
     */
    private $sellerFactory;

    /**
     * Collection constructor.
     *
     * @param EntityFactoryInterface $entityFactory          Entity Factory
     * @param LoggerInterface        $logger                 Logger Interface
     * @param FetchStrategyInterface $fetchStrategy          Fetch Strategy
     * @param ManagerInterface       $eventManager           Event Manager
     * @param MetadataPool           $metadataPool           Metadata Pool
     * @param SellerInterfaceFactory $sellerInterfaceFactory Seller Interface
     * @param AdapterInterface|null  $connection             Database Connection
     * @param AbstractDb|null        $resource               Resource Model
     * @param string|null            $sellerType             The seller type to filter on. If Any.
     */
    public function __construct(
        EntityFactoryInterface $entityFactory,
        LoggerInterface $logger,
        FetchStrategyInterface $fetchStrategy,
        ManagerInterface $eventManager,
        MetadataPool $metadataPool,
        SellerInterfaceFactory $sellerInterfaceFactory,
        AdapterInterface $connection = null,
        AbstractDb $resource = null,
        $sellerType = null
    ) {
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $connection, $resource);
        $this->metadataPool  = $metadataPool;
        $this->sellerFactory = $sellerInterfaceFactory;
        if ($sellerType !== null) {
            $this->addSellerTypeFilter($sellerType);
        }
    }

    /**
     * Filtering the collection on a given seller type
     *
     * @param string $sellerType The seller Type
     *
     * @throws \Exception
     */
    public function addSellerTypeFilter($sellerType)
    {
        if (null !== $sellerType) {
            $sellerMetadata = $this->metadataPool->getMetadata(SellerInterface::class);
            $sellerResource = $this->sellerFactory->create()->getResource();
            $attributeSetId = $sellerResource->getAttributeSetIdByName($sellerType);
            $sellerTable    = $sellerMetadata->getEntityTable();
            $sellerPkName   = $sellerMetadata->getIdentifierField();

            if (null !== $attributeSetId) {
                $this->getSelect()->joinInner(
                    $this->getTable($sellerTable),
                    new \Zend_Db_Expr("{$sellerTable}.{$sellerPkName} = main_table." . OfferInterface::SELLER_ID)
                );

                $this->getSelect()->where("{$sellerTable}.attribute_set_id = ?", (int) $attributeSetId);
            }
        }
    }

    public function addProductFilter($productId)
    {
        $this->addFieldToFilter(OfferInterface::PRODUCT_ID, $productId);

        return $this;
    }

    public function addSellerFilter($sellerId)
    {
        $this->addFieldToFilter(OfferInterface::SELLER_ID, $sellerId);

        return $this;
    }

    public function addDateFilter($date)
    {
        $select = $this->getSelect();

        $startDateConditions = [
            'main_table.' . OfferInterface::START_DATE . ' IS NULL',
            'main_table.' . OfferInterface::START_DATE . ' <= ?',
        ];

        $select->where(sprintf('(%s)', implode(' OR ', $startDateConditions)), $date);

        $endDateConditions = [
            'main_table.' . OfferInterface::END_DATE . ' IS NULL',
            'main_table.' . OfferInterface::END_DATE . ' >= ?',
        ];
        $select->where(sprintf('(%s)', implode(' OR ', $endDateConditions)), $date);

        return $this;
    }

    /**
     * Define resource model
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName) Method is inherited.
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Smile\Offer\Model\Offer', 'Smile\Offer\Model\ResourceModel\Offer');
    }
}

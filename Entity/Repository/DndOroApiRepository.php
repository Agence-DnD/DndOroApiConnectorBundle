<?php

namespace Dnd\Bundle\DndOroApiConnectorBundle\Entity\Repository;

use Dnd\Bundle\DndOroApiConnectorBundle\Services\DndOroApiAlexa;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\PaymentBundle\Provider\PaymentStatusProvider;

/**
 * Class DndOroApiRepository
 *
 * @package   Dnd\Bundle\DndOroApiConnectorBundle\Entity\Repository
 * @author    Auriau Maxime <maxime.auriau@dnd.fr>
 * @copyright Copyright (c) 2017 Agence Dn'D
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      http://www.dnd.fr/
 */
class DndOroApiRepository
{
    /** @var EntityManager $entityManger */
    protected $entityManger;

    /** @var DndOroApiAlexa $dndOroApiAlexa */
    protected $dndOroApiAlexa;

    /** string GTE */
    const GTE = 'gte';

    /** @var string LTE */
    const LTE = 'lte';

    /** @var string NOT_LIKE */
    const NOT_LIKE = 'notLike';

    /** @var string LIKE */
    const LIKE = 'LIKE';

    /**
     * DndOroApiRepository constructor.
     * @param EntityManager  $entityManger
     * @param DndOroApiAlexa $dndOroApiAlexa
     */
    public function __construct(EntityManager $entityManger, DndOroApiAlexa $dndOroApiAlexa)
    {
        $this->entityManger = $entityManger;
        $this->dndOroApiAlexa = $dndOroApiAlexa;
    }

    /**
     * @return mixed
     */
    public function getAllTotalValues()
    {
        /** @var QueryBuilder $qb */
        $qb = $this->entityManger->createQueryBuilder();
        $qb->select('SUM(o.baseTotalValue) as revenue');
        $qb->from('OroOrderBundle:Order', 'o');

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param null $name
     * @return bool
     */
    public function getTotalValueByOrganization($name = null)
    {
        if ($this->dndOroApiAlexa->isValuesNull(func_get_args())) {
            return false;
        }

        /** @var QueryBuilder $qb */
        $qb = $this->entityManger->createQueryBuilder();
        $qb->select('SUM(o.totalValue) as revenue');
        $qb->from('OroOrderBundle:Order', 'o');
        $qb->leftJoin('OroCustomer:Customer', 'c', 'WITH', 'o.customer = c');
        $qb->leftJoin('OrganizationBundle:Organization', 'oz', 'WITH', 'c.organization = oz');
        $qb->where($qb->expr()->eq('oz.name', ':name'));
        $qb->setParameter('name', $name);

        return $qb->getQuery()->getSingleScalarResult();
    }


    /**
     * @param null $name
     * @return bool
     */
    public function getTotalValueByCustomer($name = null)
    {
        if ($this->dndOroApiAlexa->isValuesNull(func_get_args())) {
            return false;
        }
        /** @var QueryBuilder $qb */
        $qb = $this->entityManger->createQueryBuilder();
        $qb->select('SUM(o.totalValue) as revenue');
        $qb->from('OroOrderBundle:Order', 'o');
        $qb->leftJoin('OroCustomer:Customer', 'c', 'WITH', 'o.customer = c');
        $qb->where($qb->expr()->eq('c.name', ':name'));
        $qb->setParameter('name', $name);

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param null $name
     * @return bool
     */
    public function getTotalValueByWebsite($name = null)
    {
        if ($this->dndOroApiAlexa->isValuesNull(func_get_args())) {
            return false;
        }
        /** @var QueryBuilder $qb */
        $qb = $this->entityManger->createQueryBuilder();
        $qb->select('SUM(o.totalValue) as revenue');
        $qb->from('OroOrderBundle:Order', 'o');
        $qb->leftJoin('OroWebsite:Website', 'w', 'WITH', 'o.website = w');
        $qb->where($qb->expr()->eq('w.name', ':name'));
        $qb->setParameter('name', $name);

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param null $year
     * @return bool
     */
    public function getTotalValueForYear($year = null)
    {
        if ($this->dndOroApiAlexa->isValuesNull(func_get_args())) {
            return false;
        }
        /** @var QueryBuilder $qb */
        $qb = $this->entityManger->createQueryBuilder();
        $qb->select('SUM(o.totalValue)  as revenue');
        $qb->from('OroOrderBundle:Order', 'o');
        $qb->where($qb->expr()->eq('YEAR(o.createdAt)', ':year'));
        $qb->setParameter('year', $year);

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param null $date
     * @return bool
     */
    public function getTotalValueFrom($date = null)
    {
        if ($this->dndOroApiAlexa->isValuesNull(func_get_args())) {
            return false;
        }
        /** @var QueryBuilder $qb */
        $qb = $this->entityManger->createQueryBuilder();
        $qb->select('SUM(o.totalValue) as revenue');
        $qb->from('OroOrderBundle:Order', 'o');
        $qb->where($qb->expr()->gte('o.createdAt', ':date'));
        $qb->setParameter('date', $date);

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param null $website
     * @param null $date
     * @return bool
     */
    public function getTotalValueForWebsiteAndDate($website = null, $date = null)
    {
        if ($this->dndOroApiAlexa->isValuesNull(func_get_args())) {
            return false;
        }
        /** @var QueryBuilder $qb */
        $qb = $this->entityManger->createQueryBuilder();
        $qb->select('SUM(o.totalValue) as revenue');
        $qb->from('OroOrderBundle:Order', 'o');
        $qb->leftJoin('OroWebsiteBundle:Website', 'w', 'WITH', 'o.website = w');
        $qb->where($qb->expr()->eq('w.name', ':name'));
        $qb->andWhere($qb->expr()->gte('o.createdAt', ':date'));

        $qb->setParameter('date', $date);
        $qb->setParameter('name', $website);

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param null $customer
     * @param null $date
     * @return bool
     */
    public function getTotalValueForCustomerAndDate($customer = null, $date = null)
    {
        if ($this->dndOroApiAlexa->isValuesNull(func_get_args())) {
            return false;
        }
        /** @var QueryBuilder $qb */
        $qb = $this->entityManger->createQueryBuilder();
        $qb->select('SUM(o.totalValue) as revenue');
        $qb->from('OroOrderBundle:Order', 'o');
        $qb->leftJoin('OroCustomerBundle:Customer', 'c', 'WITH', 'o.customer = c');
        $qb->where($qb->expr()->eq('c.name', ':name'));
        $qb->andWhere($qb->expr()->gte('o.createdAt', ':date'));
        $qb->setParameter('date', $date);
        $qb->setParameter('name', $customer);

        return $qb->getQuery()->getSingleScalarResult();
    }


    /**
     * @param null $name
     * @return bool
     */
    public function countOrderByCustomerName($name = null)
    {
        if ($this->dndOroApiAlexa->isValuesNull(func_get_args())) {
            return false;
        }
        /** @var QueryBuilder $qb */
        $qb = $this->entityManger->createQueryBuilder();
        $qb->select('COUNT(o.id)');
        $qb->from('OroOrderBundle:Order', 'o');
        $qb->leftJoin('OroCustomerBundle:Customer', 'c', 'WITH', 'o.customer = c');
        $qb->where($qb->expr()->eq('c.name', ':name'));
        $qb->setParameter('name', $name);

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param string $operator
     * @param string $status
     * @return bool
     */
    public function getOrderByPaymentStatus($operator = self::NOT_LIKE, $status = PaymentStatusProvider::FULL)
    {
        /** @var string $paymentStatus */
        $paymentStatus = constant('Oro\Bundle\PaymentBundle\Provider\PaymentStatusProvider::'.strtoupper($status));
        if (null == $paymentStatus) {
            return false;
        }

        /** @var QueryBuilder $qb */
        $qb = $this->entityManger->createQueryBuilder();
        $qb->select('o');
        $qb->from('OroOrderBundle:Order', 'o');
        $qb->join('OroPaymentBundle:PaymentStatus', 'ps', 'WITH', 'ps.entityIdentifier = o.id');
        $qb->where($qb->expr()->eq('ps.entityClass', ':class'));
        $qb->andWhere($qb->expr()->$operator('ps.paymentStatus', ':paymentStatus'));
        $qb->setParameter('class', 'Oro\\Bundle\\OrderBundle\\Entity\\Order');
        $qb->setParameter('paymentStatus', $paymentStatus);

        return $qb->getQuery()->getScalarResult();
    }

    /**
     * @return array
     */
    public function getProductsSalesQuantity()
    {
        /** @var string $sql */
        $sql = "SELECT SUM(oli.quantity) as qty, oli.product_sku as sku, oflv.string as names
        FROM oro_order_line_item oli 
        LEFT JOIN oro_product op ON (oli.product_id = op.id) 
        LEFT JOIN oro_product_name opn ON (opn.product_id = op.id) 
        LEFT JOIN oro_fallback_localization_val oflv ON (opn.localized_value_id = oflv.id) 
        GROUP BY oli.product_id ORDER BY qty DESC";
        /** @var array $query */
        $query = $this->entityManger->getConnection()->fetchAssoc($sql);

        return $query;
    }

    /**
     * @param null $date
     * @return array|bool
     */
    public function getProductsSalesQuantityByDateGte($date = null)
    {
        if ($this->dndOroApiAlexa->isValuesNull(func_get_args())) {
            return false;
        }
        /** @var string $sql */
        $sql = "SELECT SUM(oli.quantity) as qty, oli.product_sku as sku, oflv.string as names
        FROM oro_order_line_item oli 
        LEFT JOIN oro_product op ON (oli.product_id = op.id) 
        LEFT JOIN oro_product_name opn ON (opn.product_id = op.id) 
        LEFT JOIN oro_fallback_localization_val oflv ON (opn.localized_value_id = oflv.id) 
        WHERE oli.order_id IN (select o.id from oro_order o where o.created_at >= ?)
        GROUP BY oli.product_id ORDER BY qty DESC";
        /** @var array $query */
        $query = $this->entityManger->getConnection()->fetchAssoc($sql, [$date]);

        return $query;
    }

    /**
     * @param null $date
     * @param null $website
     * @return array|bool
     */
    public function getProductsSalesQuantityByWebsiteAndDateGte($website = null, $date = null)
    {
        if ($this->dndOroApiAlexa->isValuesNull(func_get_args())) {
            return false;
        }
        /** @var string $sql */
        $sql = "SELECT SUM(oli.quantity) as qty, oli.product_sku as sku, oflv.string as names, ow.name as shop
        FROM oro_order_line_item oli 
        LEFT JOIN oro_product op ON (oli.product_id = op.id) 
        LEFT JOIN oro_order o_r ON (oli.order_id = o_r.id)
        LEFT JOIN oro_website ow ON (ow.id = o_r.website_id)
        LEFT JOIN oro_product_name opn ON (opn.product_id = op.id) 
        LEFT JOIN oro_fallback_localization_val oflv ON (opn.localized_value_id = oflv.id) 
        WHERE oli.order_id IN (select o.id from oro_order o where o.created_at >= ?)
        and ow.name = ?
        GROUP BY oli.product_id ORDER BY qty DESC";
        /** @var array $query */
        $query = $this->entityManger->getConnection()->fetchAssoc($sql, [$date, $website]);

        return $query;
    }

    /**
     * @param null $name
     * @param null $date
     * @return bool
     */
    public function getAvgShoppingCartByWebsiteAndDate($name = null, $date = null)
    {
        if ($this->dndOroApiAlexa->isValuesNull(func_get_args())) {
            return false;
        }
        /** @var QueryBuilder $qb */
        $qb = $this->entityManger->createQueryBuilder();
        $qb->select($qb->expr()->avg('o.totalValue'));
        $qb->from('OroOrderBundle:Order', 'o');
        $qb->leftJoin('OroWebsiteBundle:Website', 'w', 'WITH', 'o.website = w');
        $qb->where($qb->expr()->eq('w.name', ':name'));
        $qb->andWhere($qb->expr()->gte('o.createdAt', ':date'));
        $qb->setParameter('name', $name);
        $qb->setParameter('date', $date);

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param null   $date
     * @param string $operator
     * @return bool
     */
    public function getAvgShoppingCartBydDate($date = null, $operator = self::GTE)
    {
        if ($this->dndOroApiAlexa->isValuesNull(func_get_args())) {
            return false;
        }
        /** @var QueryBuilder $qb */
        $qb = $this->entityManger->createQueryBuilder();
        $qb->select($qb->expr()->avg('o.totalValue'));
        $qb->from('OroOrderBundle:Order', 'o');
        $qb->where($qb->expr()->$operator('o.createdAt', ':date'));
        $qb->setParameter('date', $date);

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return array
     */
    public function getAllShop()
    {
        /** @var QueryBuilder $qb */
        $qb = $this->entityManger->createQueryBuilder();
        $qb->select('w.name as name');
        $qb->from('OroWebsiteBundle:Website', 'w');

        return $qb->getQuery()->getArrayResult();
    }
}

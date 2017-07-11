<?php

namespace Dnd\Bundle\DndOroApiConnectorBundle\Controller\Api\Rest;

use Dnd\Bundle\DndOroApiConnectorBundle\Entity\Repository\DndOroApiRepository;
use Dnd\Bundle\DndOroApiConnectorBundle\Services\DndOroApiAlexa;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;
use Oro\Bundle\SoapBundle\Form\Handler\ApiFormHandler;
use Oro\Bundle\SoapBundle\Request\Parameters\Filter\HttpDateTimeParameterFilter;
use Oro\Bundle\SoapBundle\Request\Parameters\Filter\IdentifierToReferenceFilter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\Util\Codes;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\PaymentBundle\Provider\PaymentStatusProvider;
use FOS\RestBundle\Controller\Annotations\QueryParam;

/**
 * Class DndOroApiConnectorController
 *
 * @package   Dnd\Bundle\DndOroApiConnectorBundle\Controller\Api\Rest
 * @author    Auriau Maxime <maxime.auriau@dnd.fr>
 * @copyright Copyright (c) 2017 Agence Dn'D
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      http://www.dnd.fr/
 *
 * @RouteResource("dnd_oro_api")
 * @NamePrefix("oro_api_")
 */
class DndOroApiConnectorController extends RestController implements ClassResourceInterface
{
    /**
     * REST GET list
     *
     * @ApiDoc(
     *      description="Get all orders",
     *      resource=true
     * )
     * @return Response
     */
    public function cgetAction()
    {
        /** @var Request $request */
        $request = $this->container->get('request');
        /** @var  $page */
        $page = $request->get('page', 1);
        /** @var  $limit */
        $limit = $request->get('limit', self::ITEMS_PER_PAGE);

        /** @var HttpDateTimeParameterFilter $dateParamFilter */
        $dateParamFilter = new HttpDateTimeParameterFilter();
        /** @var array $filterParameters */
        $filterParameters = [
            'createdAt' => $dateParamFilter,
            'updatedAt' => $dateParamFilter,
            'ownerId'   => new IdentifierToReferenceFilter($this->getDoctrine(), 'OroOrderBundle:Order'),
        ];
        /** @var array $map */
        $map = array_fill_keys(['ownerId'], 'owner');

        /** @var Criteria $criteria */
        $criteria = $this->getFilterCriteria($this->getSupportedQueryParameters('cgetAction'), $filterParameters, $map);

        return $this->handleGetListRequest($page, $limit, $criteria);
    }

    /**
     * REST GET list
     *
     * @ApiDoc(
     *      description="Return pending orders",
     *      resource=true
     * )
     * @return Response
     */
    public function getPendingOrdersAction()
    {
        /** @var array $orders */
        $orders = $this->getOrderByPaymentStatus(DndOroApiRepository::LIKE, PaymentStatusProvider::PENDING);
        if ($orders) {
            /** @var array $orders */
            $orders = ['orders' => count($orders)];
        }

        return $this->buildResponse(
            $orders ?: '',
            self::ACTION_READ,
            ['result' => $orders],
            $orders ? Codes::HTTP_OK : Codes::HTTP_NOT_FOUND
        );
    }

    /**
     * REST GET list
     *
     * @ApiDoc(
     *      description="Return order not full payed",
     *      resource=true
     * )
     * @return Response
     */
    public function getOrderNotFullPayedAction()
    {
        return $this->getOrderByPaymentStatus(DndOroApiRepository::NOT_LIKE, PaymentStatusProvider::FULL);
    }


    /**
     * REST GET list
     *
     * @ApiDoc(
     *      description="Return total revenue",
     *      resource=true
     * )
     * @return Response
     */
    public function getTotalRevenueAction()
    {
        /** @var DndOroApiRepository $dndOroApiRepository */
        $dndOroApiRepository = $this->getDndOroApiRepository();
        /** @var float $revenue */
        $revenue = $dndOroApiRepository->getAllTotalValues();
        if ($revenue) {
            /** @var array $revenue */
            $revenue = ['revenue' => round($revenue, 2)];
        }

        return $this->buildResponse(
            $revenue ?: '',
            self::ACTION_READ,
            ['result' => $revenue],
            $revenue ? Codes::HTTP_OK : Codes::HTTP_NOT_FOUND
        );
    }

    /**
     * REST GET list
     *
     * @ApiDoc(
     *      description="Return revenue for year",
     *      resource=true
     * )
     * @param string $year
     * @return Response
     */
    public function getRevenueForYearAction($year)
    {
        /** @var DndOroApiRepository $dndOroApiRepository */
        $dndOroApiRepository = $this->getDndOroApiRepository();
        /** @var float $revenue */
        $revenue = $dndOroApiRepository->getTotalValueForYear($year);
        /** @var array $revenue */
        $revenue = ['revenue' => round($revenue, 2)];

        return $this->buildResponse(
            $revenue ?: '',
            self::ACTION_READ,
            ['result' => $revenue],
            $revenue ? Codes::HTTP_OK : Codes::HTTP_NOT_FOUND
        );
    }

    /**
     * REST GET list
     *
     * @ApiDoc(
     *      description="Return revenue from date gte",
     *      resource=true
     * )
     * @param string $date
     * @return Response
     */
    public function getTotalRevenueFromAction($date)
    {
        /** @var DndOroApiRepository $dndOroApiRepository */
        $dndOroApiRepository = $this->getDndOroApiRepository();
        /** @var DndOroApiAlexa $alexa */
        $alexa = $this->getDndOroApiAlexa();
        /** @var string $alexa */
        $date = $alexa->checkAlexaDate($date);
        /** @var float $revenue */
        $revenue = $dndOroApiRepository->getTotalValueFrom($date);
        /** @var array $revenue */
        $revenue = ['revenue' => round($revenue, 2), 'date' => $date];

        return $this->buildResponse(
            $revenue ?: '',
            self::ACTION_READ,
            ['result' => $revenue],
            $revenue ? Codes::HTTP_OK : Codes::HTTP_NOT_FOUND
        );
    }

    /**
     * REST GET list
     *
     * @ApiDoc(
     *      description="Return revenue by website",
     *      resource=true
     * )
     * @param string $website
     * @return Response
     */
    public function getRevenueForWebsiteAction($website)
    {
        /** @var DndOroApiRepository $dndOroApiRepository */
        $dndOroApiRepository = $this->getDndOroApiRepository();
        /** @var float $revenue */
        $revenue = $dndOroApiRepository->getTotalValueByWebsite($website);
        /** @var array $revenue */
        $revenue = ['revenue' => round($revenue, 2)];

        return $this->buildResponse(
            $revenue ?: '',
            self::ACTION_READ,
            ['result' => $revenue],
            $revenue ? Codes::HTTP_OK : Codes::HTTP_NOT_FOUND
        );
    }

    /**
     * REST GET list
     *
     * @ApiDoc(
     *      description="Return revenue by organization",
     *      resource=true
     * )
     * @param string $name
     * @return Response
     */
    public function getRevenueByOrganisationAction($name)
    {
        /** @var DndOroApiRepository $dndOroApiRepository */
        $dndOroApiRepository = $this->getDndOroApiRepository();
        /** @var float $revenue */
        $revenue = $dndOroApiRepository->getTotalValueByOrganization($name);
        /** @var float $revenue */
        $revenue = ['revenue' => round($revenue, 2)];

        return $this->buildResponse(
            $revenue ?: '',
            self::ACTION_READ,
            ['result' => $revenue],
            $revenue ? Codes::HTTP_OK : Codes::HTTP_NOT_FOUND
        );
    }

    /**
     * REST GET list
     *
     * @ApiDoc(
     *      description="Return revenue website and date gte",
     *      resource=true
     * )
     * @param string $website
     * @param string $date
     * @return Response
     */
    public function getRevenueForWebSiteAndDateAction($website, $date)
    {
        /** @var DndOroApiRepository $dndOroApiRepository */
        $dndOroApiRepository = $this->getDndOroApiRepository();
        /** @var DndOroApiAlexa $alexa */
        $alexa = $this->getDndOroApiAlexa();
        /** @var string $alexa */
        $date = $alexa->checkAlexaDate($date);
        /** @var float $revenue */
        $revenue = $dndOroApiRepository->getTotalValueForWebsiteAndDate($website, $date);
        /** @var array $revenue */
        $revenue = ['revenue' => round($revenue, 2), 'date' => $date];

        return $this->buildResponse(
            $revenue ?: '',
            self::ACTION_READ,
            ['result' => $revenue],
            $revenue ? Codes::HTTP_OK : Codes::HTTP_NOT_FOUND
        );
    }


    /**
     * REST GET list
     *
     * @ApiDoc(
     *      description="Return revenue by customer and date gte",
     *      resource=true
     * )
     * @param        $customer
     * @param string $date
     * @return Response
     */
    public function getRevenueForCustomerAndDateAction($customer, $date)
    {
        /** @var DndOroApiRepository $dndOroApiRepository */
        $dndOroApiRepository = $this->getDndOroApiRepository();
        /** @var DndOroApiAlexa $alexa */
        $alexa = $this->getDndOroApiAlexa();
        /** @var string $alexa */
        $date = $alexa->checkAlexaDate($date);
        /** @var float $revenue */
        $revenue = $dndOroApiRepository->getTotalValueForCustomerAndDate($customer, $date);
        /** @var array $revenue */
        $revenue = ['revenue' => round($revenue, 2), 'date' => $date];

        return $this->buildResponse(
            $revenue ?: '',
            self::ACTION_READ,
            ['result' => $revenue],
            $revenue ? Codes::HTTP_OK : Codes::HTTP_NOT_FOUND
        );
    }

    /**
     * REST GET item
     *
     * @param string $id
     *
     * @ApiDoc(
     *      description="Get order item",
     *      resource=true
     * )
     * @AclAncestor("oro_order_view")
     * @return Response
     */
    public function getAction($id = null)
    {
        return $this->handleGetRequest($id);
    }

    /**
     * @param string $operator
     * @param string $status
     */
    private function getOrderByPaymentStatus($operator = DndOroApiRepository::NOT_LIKE, $status = PaymentStatusProvider::FULL)
    {
        /** @var DndOroApiRepository $dndOroApiRepository */
        $dndOroApiRepository = $this->getDndOroApiRepository();
        /** @var array $orders */
        $orders = $dndOroApiRepository->getOrderByPaymentStatus($operator, $status);

        return $orders;
    }

    /**
     * REST GET list
     *
     * @ApiDoc(
     *      description="Get most sales product",
     *      resource=true
     * )
     * @return Response
     */
    public function getTheMostSalesProductAction()
    {
        /** @var DndOroApiRepository $dndOroApiRepository */
        $dndOroApiRepository = $this->getDndOroApiRepository();
        /** @var array $products */
        $products = $dndOroApiRepository->getProductsSalesQuantity();
        /** @var array $product */
        $product = ['products' => $products];

        return $this->buildResponse(
            $product ?: '',
            self::ACTION_READ,
            ['result' => $product],
            $product ? Codes::HTTP_OK : Codes::HTTP_NOT_FOUND
        );
    }

    /**
     * REST GET list
     *
     * @ApiDoc(
     *      description="Get most sales product with a date gte",
     *      resource=true
     * )
     * @return Response
     */
    public function getTheMostSalesProductDateAction($date)
    {
        $dndOroApiRepository = $this->getDndOroApiRepository();
        /** @var DndOroApiAlexa $alexa */
        $alexa = $this->getDndOroApiAlexa();
        /** @var string $alexa */
        $date = $alexa->checkAlexaDate($date);
        /** @var array $products */
        $products = $dndOroApiRepository->getProductsSalesQuantityByDateGte($date);
        /** @var array $product */
        $product = ['products' => $products, 'date' => $date];

        return $this->buildResponse(
            $product ?: '',
            self::ACTION_READ,
            ['result' => $product],
            $product ? Codes::HTTP_OK : Codes::HTTP_NOT_FOUND
        );
    }

    /**
     * REST GET list
     *
     * @ApiDoc(
     *      description="Get most sales product by website and date gte",
     *      resource=true
     * )
     * @param string $website
     * @param string $date
     * @return Response
     */
    public function getTheMostSalesProductWebsiteDateAction($date, $website)
    {
        /** @var DndOroApiRepository $dndOroApiRepository */
        $dndOroApiRepository = $this->getDndOroApiRepository();
        /** @var DndOroApiAlexa $alexa */
        $alexa = $this->getDndOroApiAlexa();
        /** @var string $alexa */
        $date = $alexa->checkAlexaDate($date);
        /** @var array $products */
        $products = $dndOroApiRepository->getProductsSalesQuantityByWebsiteAndDateGte($date, $website);
        /** @var array $product */
        $product = ['products' => $products, 'date' => $date];

        return $this->buildResponse(
            $product ?: '',
            self::ACTION_READ,
            ['result' => $product],
            $product ? Codes::HTTP_OK : Codes::HTTP_NOT_FOUND
        );
    }

    /**
     * REST GET list
     *
     * @ApiDoc(
     *      description="Get average shopping cart by website and date gte",
     *      resource=true
     * )
     * @param string $website
     * @param string $date
     * @return Response
     */
    public function getAverageShoppingCartByWebsiteAndDateAction($website, $date)
    {
        /** @var DndOroApiRepository $dndOroApiRepository */
        $dndOroApiRepository = $this->getDndOroApiRepository();
        /** @var DndOroApiAlexa $alexa */
        $alexa = $this->getDndOroApiAlexa();
        /** @var string $alexa */
        $date = $alexa->checkAlexaDate($date);
        /** @var float $avg */
        $avg = $dndOroApiRepository->getAvgShoppingCartByWebsiteAndDate($website, $date);
        /** @var array $avg */
        $avg = ['avg' => round($avg, 2), 'date' => $date];

        return $this->buildResponse(
            $avg ?: '',
            self::ACTION_READ,
            ['avg' => $avg],
            $avg ? Codes::HTTP_OK : Codes::HTTP_NOT_FOUND
        );
    }

    /**
     * REST GET list
     *
     * @ApiDoc(
     *      description="Get average shopping cart by date gte",
     *      resource=true
     * )
     * @param string $date
     * @return Response
     */
    public function getAverageShoppingCartDateAction($date)
    {
        /** @var DndOroApiRepository $dndOroApiRepository */
        $dndOroApiRepository = $this->getDndOroApiRepository();
        /** @var DndOroApiAlexa $alexa */
        $alexa = $this->getDndOroApiAlexa();
        /** @var string $alexa */
        $date = $alexa->checkAlexaDate($date);
        /** @var float $avg */
        $avg = $dndOroApiRepository->getAvgShoppingCartBydDate($date);
        /** @var array $avg */
        $avg = ['avg' => round($avg, 2), 'date' => $date];

        return $this->buildResponse(
            $avg ?: '',
            self::ACTION_READ,
            ['avg' => $avg],
            $avg ? Codes::HTTP_OK : Codes::HTTP_NOT_FOUND
        );
    }

    /**
     * REST GET list
     *
     * @ApiDoc(
     *      description="Get average shopping cart lifetime",
     *      resource=true
     * )
     * @return Response
     */
    public function getAverageShoppingCartAction()
    {
        /** @var DndOroApiRepository $dndOroApiRepository */
        $dndOroApiRepository = $this->getDndOroApiRepository();
        /** @var array $date */
        $date = date('Y-m-d');
        /** @var float $avg */
        $avg = $dndOroApiRepository->getAvgShoppingCartBydDate($date, 'lte');
        /** @var array $avg */
        $avg = ['avg' => round($avg, 2)];

        return $this->buildResponse(
            $avg ?: '',
            self::ACTION_READ,
            ['avg' => $avg],
            $avg ? Codes::HTTP_OK : Codes::HTTP_NOT_FOUND
        );
    }

    /**
     * REST GET list
     *
     * @ApiDoc(
     *      description="Get all available shop",
     *      resource=true
     * )
     * @return Response
     */
    public function getAvailableShopAction()
    {
        /** @var DndOroApiRepository $dndOroApiRepository */
        $dndOroApiRepository = $this->getDndOroApiRepository();
        /** @var array $shops */
        $shops = $dndOroApiRepository->getAllShop();
        /** @var array $shops */
        $shops = ['shops' => $shops];

        return $this->buildResponse(
            $shops ?: '',
            self::ACTION_READ,
            ['shops' => $shops],
            $shops ? Codes::HTTP_OK : Codes::HTTP_NOT_FOUND
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getManager()
    {
        throw new \BadMethodCallException('Manager is not available.');
    }

    /**
     * {@inheritdoc}
     */
    public function getForm()
    {
        throw new \BadMethodCallException('Form is not available.');
    }

    /**
     * {@inheritdoc}
     */
    public function getFormHandler()
    {
        throw new \BadMethodCallException('FormHandler is not available.');
    }

    /**
     * @return DndOroApiRepository|object
     */
    public function getDndOroApiRepository()
    {
        return $this->container->get('dnd_oro_api.repository');
    }

    /**
     * @return DndOroApiAlexa
     */
    public function getDndOroApiAlexa()
    {
        return $this->container->get('dnd_oro_api.alexa');
    }
}

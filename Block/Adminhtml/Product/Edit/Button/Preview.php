<?php
declare(strict_types=1);

namespace OH\PreviewBtn\Block\Adminhtml\Product\Edit\Button;

use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Url;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Magento\Store\Model\StoreManagerInterface;

class Preview implements ButtonProviderInterface
{
    /**
     * @var RequestInterface
     */
    private RequestInterface $request;

    /**
     * @var ProductRepository
     */
    private ProductRepository $productRepository;

    /**
     * @var UrlInterface
     */
    private UrlInterface $frontendUrlBuilder;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var ResourceConnection
     */
    private ResourceConnection $connection;

    public function __construct(
        ResourceConnection $connection,
        StoreManagerInterface $storeManager,
        ProductRepository $productRepository,
        RequestInterface $request,
        Url $frontendUrlBuilder
    ) {
        $this->connection = $connection;
        $this->productRepository = $productRepository;
        $this->request = $request;
        $this->frontendUrlBuilder = $frontendUrlBuilder;
        $this->storeManager = $storeManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonData(): array
    {
        $id = (int)$this->request->getParam('id');
        $product = $this->getProduct($id);

        if ($product && $this->request->getActionName() != 'new' && $this->canShow($id)) {
            $scopeId = $this->getScopeId();

            if ($url = $this->getUrl($product, $scopeId)) {
                return [
                    'label' => __('Preview as customer'),
                    'on_click' => sprintf("window.open('%s');", $this->getFrontendUrl($url, $scopeId)),
                    'class' => 'action-secondary',
                    'sort_order' => 10
                ];
            }
        }

        return [];
    }

    private function getScopeId()
    {
        $storeId = $this->request->getParam('store');

        if (!$storeId) {
            $storeId = $this->storeManager->getDefaultStoreView()->getId();
        }

        return $storeId;
    }

    private function getProduct($id)
    {
        try {
            return $this->productRepository->getById($id);
        } catch (\Exception $exception) {
            return null;
        }
    }

    /**
     * Preview button only available if product is enabled
     *
     * @param $productId
     * @return bool
     */
    private function canShow($productId): bool
    {
        try {
            $product = $this->productRepository->getById($productId);
            return $product->getStatus() == Status::STATUS_ENABLED && in_array($product->getVisibility(),
                    [Visibility::VISIBILITY_IN_CATALOG, Visibility::VISIBILITY_BOTH]);
        } catch (\Exception $exception) {
            return false;
        }
    }

    private function getUrl($product, $storeId)
    {
        $connec = $this->connection->getConnection();
        $query = sprintf('select request_path from url_rewrite where entity_id = %s and entity_type = "%s" and store_id = %s',
            $product->getEntityId(),
            'product',
            $storeId);
        $result = $connec->fetchOne($query);
        return !empty($result) ? $result : null;
    }

    /**
     * Get frontend url
     *
     * @param string $routePath
     * @param null $scope
     * @param null $store
     * @param null $params
     * @return string
     */
    public function getFrontendUrl(string $routePath, $scope = null, $store = null, $params = null): string
    {
        if ($scope) {
            $this->frontendUrlBuilder->setScope($scope);
            $paramsOrg = [
                '_current' => false,
                '_nosid' => true,
                '_query' => [StoreManagerInterface::PARAM_NAME => $store]
            ];

            $href = $this->frontendUrlBuilder->getUrl(
                $routePath,
                $params ? array_merge($params, $paramsOrg) : $paramsOrg
            );
        } else {
            $paramsOrg = [
                '_current' => false,
                '_nosid' => true
            ];

            $href = $this->frontendUrlBuilder->getUrl(
                $routePath,
                $params ? array_merge($params, $paramsOrg) : $paramsOrg
            );
        }

        return $href;
    }
}

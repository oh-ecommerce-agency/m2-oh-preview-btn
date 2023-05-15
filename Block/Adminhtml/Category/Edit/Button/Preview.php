<?php
declare(strict_types=1);

namespace OH\PreviewBtn\Block\Adminhtml\Category\Edit\Button;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Cms\Api\PageRepositoryInterface;
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
     * @var CategoryRepositoryInterface
     */
    private CategoryRepositoryInterface $categoryRepository;

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
        CategoryRepositoryInterface $categoryRepository,
        RequestInterface $request,
        Url $frontendUrlBuilder
    ) {
        $this->connection = $connection;
        $this->storeManager = $storeManager;
        $this->categoryRepository = $categoryRepository;
        $this->request = $request;
        $this->frontendUrlBuilder = $frontendUrlBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonData(): array
    {
        $id = (int)$this->request->getParam('id');
        $category = $this->getCategory($id);

        //avoid default category
        if ($category && $category->getId() != 2 && $this->request->getActionName() != 'new' && $this->canShow($category)) {
            $scopeId = $this->getScopeId();
            return [
                'label' => __('Preview as customer'),
                'on_click' => sprintf("window.open('%s');", $this->getFrontendUrl($this->getUrl($category, $scopeId) ?: '', $scopeId)),
                'class' => 'action-secondary',
                'sort_order' => 10
            ];
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

    private function getCategory($categoryId)
    {
        try {
            return $this->categoryRepository->get($categoryId);
        } catch (\Exception $exception) {
            return null;
        }
    }

    private function getUrl($category, $storeId)
    {
        $connec = $this->connection->getConnection();
        $query = sprintf('select request_path from url_rewrite where entity_id = %s and entity_type = "%s" and store_id = %s',
            $category->getEntityId(),
            'category',
            $storeId);
        $result = $connec->fetchOne($query);
        return !empty($result) ? $result : null;
    }

    /**
     * Preview button only available if category is active
     *
     * @param $category
     * @return bool
     */
    private function canShow($category): bool
    {
        return $category && $category->getIsActive();
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

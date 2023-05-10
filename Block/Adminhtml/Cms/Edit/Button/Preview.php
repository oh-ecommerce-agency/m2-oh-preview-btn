<?php
declare(strict_types=1);

namespace OH\PreviewBtn\Block\Adminhtml\Cms\Edit\Button;

use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Framework\App\RequestInterface;
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
     * @var PageRepositoryInterface
     */
    private PageRepositoryInterface $pageRepository;

    /**
     * @var UrlInterface
     */
    private UrlInterface $frontendUrlBuilder;

    public function __construct(
        PageRepositoryInterface $pageRepository,
        RequestInterface $request,
        Url $frontendUrlBuilder
    ) {
        $this->pageRepository = $pageRepository;
        $this->request = $request;
        $this->frontendUrlBuilder = $frontendUrlBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonData(): array
    {
        $id = (int)$this->request->getParam('page_id');
        $page = $this->getPage($id);

        if ($page && $this->request->getActionName() != 'new' && $this->canShow($page)) {
            return [
                'label' => __('Preview as customer'),
                'on_click' => sprintf("window.open('%s');", $this->getFrontendUrl($page->getIdentifier() != 'home' ? $page->getIdentifier() : '', $this->getScopeId($page))),
                'class' => 'action-secondary',
                'sort_order' => 10
            ];
        }

        return [];
    }

    private function getScopeId($page)
    {
        $storeIds = $page->getStoreId();
        return reset($storeIds);
    }

    private function getPage($pageId)
    {
        try {
            return $this->pageRepository->getById($pageId);
        } catch (\Exception $exception) {
            return null;
        }
    }

    /**
     * Preview button only available if page is active
     *
     * @param $page
     * @return bool
     */
    private function canShow($page): bool
    {
        return $page && $page->getIsActive();
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

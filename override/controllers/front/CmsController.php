<?php
/**
 * BeautyCMS CmsController Override
 * Extends PrestaShop's CmsController to handle category index pages in breadcrumbs
 */

class CmsController extends CmsControllerCore
{
    public function getBreadcrumbLinks(): array
    {
        $breadcrumb = [];
        $breadcrumb['links'][] = $this->addMyAccountLink();

        if ($this->assignCase == self::CMS_CASE_CATEGORY) {
            $cmsCategory = new CMSCategory($this->cms_category->id_cms_category);
        } else {
            $cmsCategory = new CMSCategory($this->cms->id_cms_category);
        }

        $currentPageId = ($this->assignCase == self::CMS_CASE_PAGE && $this->context->controller instanceof CmsControllerCore)
            ? (int) $this->context->controller->cms->id
            : 0;

        if ($cmsCategory->id_parent != 0) {
            foreach (array_reverse($cmsCategory->getParentsCategories()) as $category) {
                if ($category['active']) {
                    $cmsSubCategory = new CMSCategory($category['id_cms_category']);

                    $indexPageId = $this->getCategoryIndexPage($category['id_cms_category']);

                    if ($indexPageId > 0) {
                        if ($indexPageId === $currentPageId) {
                            continue;
                        }

                        $indexPage = new CMS($indexPageId, $this->context->language->id, $this->context->shop->id);
                        if (Validate::isLoadedObject($indexPage)) {
                            $breadcrumb['links'][] = [
                                'title' => $indexPage->meta_title ?: $cmsSubCategory->getName(),
                                'url' => $this->context->link->getCMSLink($indexPage),
                            ];
                        } else {
                            $breadcrumb['links'][] = [
                                'title' => $cmsSubCategory->getName(),
                                'url' => $this->context->link->getCMSCategoryLink($cmsSubCategory),
                            ];
                        }
                    } else {
                        $breadcrumb['links'][] = [
                            'title' => $cmsSubCategory->getName(),
                            'url' => $this->context->link->getCMSCategoryLink($cmsSubCategory),
                        ];
                    }
                }
            }
        }

        if ($this->assignCase == self::CMS_CASE_PAGE && $this->context->controller instanceof CmsControllerCore) {
            $breadcrumb['links'][] = [
                'title' => $this->context->controller->cms->meta_title,
                'url' => $this->context->link->getCMSLink($this->context->controller->cms),
            ];
        }

        return $breadcrumb;
    }

    /**
     * Get the index page ID for a given category
     *
     * @param int $idCategory
     * @return int
     */
    protected function getCategoryIndexPage($idCategory)
    {
        $row = Db::getInstance()->getRow('
            SELECT id_cms
            FROM `' . _DB_PREFIX_ . 'cms_pretty_category`
            WHERE id_category = ' . (int) $idCategory
        );

        if ($row && isset($row['id_cms'])) {
            return (int) $row['id_cms'];
        }

        return 0;
    }

    /**
     * Add "My Account" link to breadcrumb
     * This method ensures compatibility with the parent class
     *
     * @return array
     */
    protected function addMyAccountLink()
    {
        return [
            'title' => $this->trans('Home', [], 'Shop.Theme.Global'),
            'url' => $this->context->link->getPageLink('index'),
        ];
    }
}

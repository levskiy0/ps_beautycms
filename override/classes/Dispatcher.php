<?php
/**
 * BeautyCMS Dispatcher Override
 * Extends PrestaShop's Dispatcher to add custom CMS page routes
 */

class Dispatcher extends DispatcherCore
{
    /**
     * Load routes with custom CMS pretty URLs
     *
     * @param int|null $id_shop
     */
    protected function loadRoutes($id_shop = null)
    {
        parent::loadRoutes($id_shop);

        if (!Configuration::get('PS_REWRITING_SETTINGS')) {
            return;
        }

        $context = Context::getContext();

        if ($id_shop === null) {
            $id_shop = (int) $context->shop->id;
        }

        $rows = Db::getInstance()->executeS('
            SELECT id_cms, id_lang, pretty_url
            FROM `' . _DB_PREFIX_ . 'cms_pretty_routes`
            WHERE use_pretty_url = 1
              AND pretty_url != ""
        ');

        if (!$rows) {
            return;
        }

        foreach ($rows as $row) {
            $idCms = (int) $row['id_cms'];
            $idLang = (int) $row['id_lang'];
            $prettyUrl = ltrim($row['pretty_url'], '/');

            if (empty($prettyUrl)) {
                continue;
            }

            $routeId = 'cms_pretty_url_' . $idCms . '_' . $idLang;

            $this->addRoute(
                $routeId,
                $prettyUrl,
                'cms',
                $idLang,
                [],
                ['id_cms' => $idCms],
                $id_shop
            );
        }
    }
}

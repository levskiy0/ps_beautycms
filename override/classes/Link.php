<?php
/**
 * BeautyCMS Link Override
 * Extends PrestaShop's Link class to generate custom CMS URLs
 */

class Link extends LinkCore
{
    /**
     * Generate CMS page URL with support for pretty URLs
     *
     * @param mixed $cms CMS object or ID
     * @param string|null $alias
     * @param bool|null $ssl
     * @param int|null $id_lang
     * @param int|null $id_shop
     * @param bool $relative_protocol
     * @return string
     */
    public function getCMSLink($cms, $alias = null, $ssl = null, $id_lang = null, $id_shop = null, $relative_protocol = false)
    {
        if (Configuration::get('PS_REWRITING_SETTINGS')) {
            $id_cms = is_object($cms) ? (int) $cms->id : (int) $cms;

            if ($id_lang === null) {
                $id_lang = (int) Context::getContext()->language->id;
            }

            $row = Db::getInstance()->getRow('
                SELECT use_pretty_url, pretty_url
                FROM `' . _DB_PREFIX_ . 'cms_pretty_routes`
                WHERE id_cms = ' . (int) $id_cms . '
                  AND id_lang = ' . (int) $id_lang
            );

            if (!empty($row) && (int) $row['use_pretty_url'] === 1 && !empty($row['pretty_url'])) {
                $customUrl = ltrim($row['pretty_url'], '/');

                $base = $this->getBaseLink($id_shop, $ssl, $relative_protocol);

                if (Language::countActiveLanguages() > 1) {
                    $lang = new Language($id_lang);
                    if (Validate::isLoadedObject($lang)) {
                        $langIso = $lang->iso_code;
                        return $base . $langIso . '/' . $customUrl;
                    }
                }

                return $base . $customUrl;
            }
        }

        return parent::getCMSLink($cms, $alias, $ssl, $id_lang, $id_shop, $relative_protocol);
    }
}

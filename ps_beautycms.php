<?php
/**
 * BeautyCMS Module
 *
 * @author    40x.Pro@gmail.com
 * @copyright Copyright (c) 2025
 * @license   MIT License
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Ps_BeautyCMS extends Module
{
    protected $dbPrefix;
    private $isResetting = false;

    public function __construct()
    {
        $this->name = 'ps_beautycms';
        $this->tab = 'administration';
        $this->version = '1.0.1';
        $this->author = 'https://github.com/levskiy0';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '8.0.0',
            'max' => _PS_VERSION_
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Beauty CMS');
        $this->description = $this->l('Extends CMS Pages with custom pretty URLs');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

        $this->dbPrefix = _DB_PREFIX_;
    }

    public function install()
    {
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        return parent::install()
            && $this->installDb()
            && $this->registerHook('actionCmsPageFormBuilderModifier')
            && $this->registerHook('actionAfterUpdateCmsPageFormHandler')
            && $this->registerHook('actionAfterCreateCmsPageFormHandler')
            && $this->registerHook('actionAdminCmsControllerSetMedia');
    }

    public function uninstall()
    {
        if ($this->isResetting) {
            return parent::uninstall();
        }

        return parent::uninstall()
            && $this->uninstallDb();
    }

    public function reset()
    {
        $this->isResetting = true;

        if (!$this->uninstall()) {
            $this->isResetting = false;
            return false;
        }

        if (!$this->install()) {
            $this->isResetting = false;
            return false;
        }

        $this->isResetting = false;
        return true;
    }

    protected function installDb()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `{$this->dbPrefix}cms_pretty_routes` (
            `id_cms` INT(10) UNSIGNED NOT NULL,
            `id_lang` INT(10) UNSIGNED NOT NULL,
            `use_pretty_url` TINYINT(1) NOT NULL DEFAULT 0,
            `pretty_url` VARCHAR(255) NOT NULL DEFAULT '',
            PRIMARY KEY (`id_cms`, `id_lang`),
            INDEX `idx_pretty_url` (`use_pretty_url`, `pretty_url`)
        ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        return Db::getInstance()->execute($sql);
    }

    protected function uninstallDb()
    {
        $sql = "DROP TABLE IF EXISTS `{$this->dbPrefix}cms_pretty_routes`";
        return Db::getInstance()->execute($sql);
    }

    public function hookActionCmsPageFormBuilderModifier($params)
    {
        $formBuilder = $params['form_builder'];
        $idCms = (int) $params['id'];

        $languages = Language::getLanguages(false);
        $prettyUrls = [];
        $usePrettyUrl = false;

        if ($idCms > 0) {
            $rows = Db::getInstance()->executeS("
                SELECT id_lang, use_pretty_url, pretty_url
                FROM `{$this->dbPrefix}cms_pretty_routes`
                WHERE id_cms = " . (int) $idCms
            );

            if ($rows) {
                foreach ($rows as $row) {
                    $prettyUrls[$row['id_lang']] = $row['pretty_url'];
                    if ((int) $row['use_pretty_url'] === 1) {
                        $usePrettyUrl = true;
                    }
                }
            }
        }

        $formBuilder->add('use_pretty_url', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', [
            'label' => $this->l('Use pretty URL'),
            'required' => false,
            'data' => $usePrettyUrl,
            'attr' => [
                'class' => 'js-use-pretty-url-toggle'
            ]
        ]);

        $prettyUrlData = [];
        foreach ($languages as $lang) {
            $prettyUrlData[$lang['id_lang']] = $prettyUrls[$lang['id_lang']] ?? '';
        }

        $formBuilder->add('pretty_url', 'PrestaShopBundle\Form\Admin\Type\TranslatableType', [
            'label' => $this->l('Page URL'),
            'required' => false,
            'type' => 'Symfony\Component\Form\Extension\Core\Type\TextType',
            'options' => [
                'attr' => [
                    'class' => 'js-pretty-url-input',
                    'placeholder' => $this->l('e.g., about-us or company/about')
                ]
            ],
            'data' => $prettyUrlData,
            'attr' => [
                'class' => 'js-pretty-url-container'
            ]
        ]);
    }

    public function hookActionAfterUpdateCmsPageFormHandler($params)
    {
        return $this->savePrettyUrls($params);
    }

    public function hookActionAfterCreateCmsPageFormHandler($params)
    {
        return $this->savePrettyUrls($params);
    }

    protected function savePrettyUrls($params)
    {
        $idCms = (int) $params['id'];
        $formData = $params['form_data'];

        $usePrettyUrl = isset($formData['use_pretty_url']) ? (int) $formData['use_pretty_url'] : 0;
        $prettyUrls = isset($formData['pretty_url']) ? $formData['pretty_url'] : [];
        $languages = Language::getLanguages(false);

        Db::getInstance()->delete(
            'cms_pretty_routes',
            'id_cms = ' . (int) $idCms
        );

        $insertData = [];
        foreach ($languages as $lang) {
            $idLang = (int) $lang['id_lang'];
            $prettyUrl = isset($prettyUrls[$idLang]) ? pSQL($prettyUrls[$idLang]) : '';

            $insertData[] = [
                'id_cms' => (int) $idCms,
                'id_lang' => $idLang,
                'use_pretty_url' => $usePrettyUrl,
                'pretty_url' => $prettyUrl
            ];
        }

        if (!empty($insertData)) {
            return Db::getInstance()->insert('cms_pretty_routes', $insertData);
        }

        return true;
    }

    public function hookActionAdminCmsControllerSetMedia($params)
    {
        $this->context->controller->addJS($this->_path . 'views/js/beautycms.js');
    }

    public function getContent()
    {
        $output = '';

        $output .= $this->displayConfirmation($this->l('BeautyCMS module is installed and active.'));
        $output .= '<div class="alert alert-info">';
        $output .= '<h4>' . $this->l('How to use:') . '</h4>';
        $output .= '<ul>';
        $output .= '<li>' . $this->l('Go to Design > Pages') . '</li>';
        $output .= '<li>' . $this->l('Edit any CMS page') . '</li>';
        $output .= '<li>' . $this->l('Check "Use pretty URL" to enable custom URLs') . '</li>';
        $output .= '<li>' . $this->l('Enter your custom URL for each language') . '</li>';
        $output .= '</ul>';
        $output .= '<p><strong>' . $this->l('Note:') . '</strong> ' . $this->l('Make sure "Friendly URL" is enabled in Shop Parameters > Traffic & SEO') . '</p>';
        $output .= '</div>';

        return $output;
    }
}

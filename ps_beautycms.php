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
            && $this->registerHook('actionAdminCmsControllerSetMedia')
            && $this->registerHook('actionCmsPageCategoryFormBuilderModifier')
            && $this->registerHook('actionAfterUpdateCmsPageCategoryFormHandler');
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
        $sql1 = "CREATE TABLE IF NOT EXISTS `{$this->dbPrefix}cms_pretty_routes` (
            `id_cms` INT(10) UNSIGNED NOT NULL,
            `id_lang` INT(10) UNSIGNED NOT NULL,
            `use_pretty_url` TINYINT(1) NOT NULL DEFAULT 0,
            `pretty_url` VARCHAR(255) NOT NULL DEFAULT '',
            PRIMARY KEY (`id_cms`, `id_lang`),
            INDEX `idx_pretty_url` (`use_pretty_url`, `pretty_url`)
        ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $sql2 = "CREATE TABLE IF NOT EXISTS `{$this->dbPrefix}cms_pretty_category` (
            `id_category` INT(10) UNSIGNED NOT NULL,
            `id_cms` INT(10) UNSIGNED DEFAULT NULL,
            PRIMARY KEY (`id_category`),
            INDEX `idx_id_cms` (`id_cms`)
        ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        return Db::getInstance()->execute($sql1)
            && Db::getInstance()->execute($sql2);
    }

    protected function uninstallDb()
    {
        if ($this->isResetting) {
            return true;
        }

        $sql1 = "DROP TABLE IF EXISTS `{$this->dbPrefix}cms_pretty_routes`";
        $sql2 = "DROP TABLE IF EXISTS `{$this->dbPrefix}cms_pretty_category`";
        return Db::getInstance()->execute($sql1)
            && Db::getInstance()->execute($sql2);
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

    public function hookActionCmsPageCategoryFormBuilderModifier($params)
    {
        $formBuilder = $params['form_builder'];
        $idCategory = (int) $params['id'];

        if ($idCategory <= 0) {
            return;
        }

        $currentIndexPage = 0;
        $row = Db::getInstance()->getRow("
            SELECT id_cms
            FROM `{$this->dbPrefix}cms_pretty_category`
            WHERE id_category = " . (int) $idCategory
        );

        if ($row && isset($row['id_cms'])) {
            $currentIndexPage = (int) $row['id_cms'];
        }

        $pages = Db::getInstance()->executeS("
            SELECT c.id_cms, cl.meta_title
            FROM `{$this->dbPrefix}cms` c
            LEFT JOIN `{$this->dbPrefix}cms_lang` cl ON (c.id_cms = cl.id_cms AND cl.id_lang = " . (int) Context::getContext()->language->id . ")
            WHERE c.id_cms_category = " . (int) $idCategory . "
            ORDER BY cl.meta_title ASC
        ");

        $choices = [
            $this->l('No Index Page') => 0
        ];

        if ($pages) {
            foreach ($pages as $page) {
                $title = $page['meta_title'] ?: 'CMS #' . $page['id_cms'];
                $choices[$title] = $page['id_cms'];
            }
        }

        $formBuilder->add('index_page', 'Symfony\Component\Form\Extension\Core\Type\ChoiceType', [
            'label' => $this->l('Index Page'),
            'required' => false,
            'choices' => $choices,
            'data' => $currentIndexPage,
            'help' => $this->l('Select a page to use as the category index page. The category link will redirect to this page.')
        ]);
    }

    public function hookActionAfterUpdateCmsPageCategoryFormHandler($params)
    {
        return $this->saveCategoryIndexPage($params);
    }

    protected function saveCategoryIndexPage($params)
    {
        $idCategory = (int) $params['id'];
        $formData = $params['form_data'];

        if (!isset($formData['index_page'])) {
            return true;
        }

        $idCms = (int) $formData['index_page'];

        Db::getInstance()->delete(
            'cms_pretty_category',
            'id_category = ' . (int) $idCategory
        );

        if ($idCms > 0) {
            return Db::getInstance()->insert('cms_pretty_category', [
                'id_category' => (int) $idCategory,
                'id_cms' => (int) $idCms
            ]);
        }

        return true;
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
        $output .= '<br/>';
        $output .= '<p><strong>' . $this->l('Need help with your Prestashop? Contact with me:') . '</strong> <a href="mailto:40x.Pro@gmail.com">40x.Pro@gmail.com</a>, <a href="https://github.com/levskiy0">GitHub</a> </p>';
        $output .= '</div>';

        return $output;
    }
}

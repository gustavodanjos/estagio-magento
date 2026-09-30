<?php
declare(strict_types=1);

namespace Webjump\Gustavo\ViewModel;

use Magento\Catalog\Helper\Category as CategoryHelper;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Halloween implements ArgumentInterface
{
    private const XML_PATH_HERO_ENABLED = 'webjump_gustavo/halloween_theme/hero_enabled';
    private const XML_PATH_HERO_SHOW_IMAGE = 'webjump_gustavo/halloween_theme/hero_show_image';
    private const XML_PATH_HERO_IMAGE = 'webjump_gustavo/halloween_theme/hero_image';
    private const XML_PATH_HERO_OPACITY = 'webjump_gustavo/halloween_theme/hero_opacity';
    private const XML_PATH_HERO_SHOW_TEXT = 'webjump_gustavo/halloween_theme/hero_show_text';
    private const XML_PATH_HERO_TITLE = 'webjump_gustavo/halloween_theme/hero_title';
    private const XML_PATH_HERO_SUBTITLE = 'webjump_gustavo/halloween_theme/hero_subtitle';
    private const XML_PATH_HERO_SHOW_BUTTON = 'webjump_gustavo/halloween_theme/hero_show_button';
    private const XML_PATH_HERO_BUTTON_TEXT = 'webjump_gustavo/halloween_theme/hero_button_text';
    private const XML_PATH_HERO_BUTTON_COLLECTION = 'webjump_gustavo/halloween_theme/hero_button_collection';
    private const XML_PATH_HERO_BUTTON_LINK = 'webjump_gustavo/halloween_theme/hero_button_link';
    private const XML_PATH_FOOTER_SHOW_IMAGE = 'webjump_gustavo/halloween_theme/footer_show_image';
    private const XML_PATH_FOOTER_IMAGE = 'webjump_gustavo/halloween_theme/footer_image';
    private const XML_PATH_FOOTER_OPACITY = 'webjump_gustavo/halloween_theme/footer_opacity';

    private const HERO_MEDIA_SUBDIR = 'webjump/halloween/hero';
    private const FOOTER_MEDIA_SUBDIR = 'webjump/halloween/footer';

    private const DEFAULT_HERO_IMAGE = 'images/halloween-banner.jpg';
    private const DEFAULT_FOOTER_IMAGE = 'images/footer-halloween.jpg';
    private const DEFAULT_HERO_OPACITY = 0.85;
    private const DEFAULT_FOOTER_OPACITY = 0.35;
    private const DEFAULT_BUTTON_LINK = '/';

    private const THEME_DESIGN_PATH = 'app/design/frontend/Webjump/halloween';
    private const THEME_STATIC_PATH = 'frontend/Webjump/halloween/en_US/';

    private ?string $heroImageUrl = null;
    private ?string $footerImageUrl = null;
    private ?string $heroButtonUrl = null;

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly StoreManagerInterface $storeManager,
        private readonly CategoryFactory $categoryFactory,
        private readonly CategoryHelper $categoryHelper,
        private readonly DirectoryList $directoryList,
        private readonly File $fileDriver,
    ) {
    }

    public function isHeroEnabled(): bool
    {
        return $this->isEnabled(self::XML_PATH_HERO_ENABLED);
    }

    public function isHeroImageVisible(): bool
    {
        return $this->isHeroEnabled() && $this->isEnabled(self::XML_PATH_HERO_SHOW_IMAGE);
    }

    public function isHeroTextVisible(): bool
    {
        return $this->isHeroEnabled() && $this->isEnabled(self::XML_PATH_HERO_SHOW_TEXT);
    }

    public function isFooterImageVisible(): bool
    {
        return $this->isEnabled(self::XML_PATH_FOOTER_SHOW_IMAGE);
    }

    public function getHeroImageUrl(): string
    {
        if ($this->heroImageUrl !== null) {
            return $this->heroImageUrl;
        }

        if (!$this->isHeroImageVisible()) {
            return $this->heroImageUrl = '';
        }

        $uploaded = $this->getStoreValue(self::XML_PATH_HERO_IMAGE);
        if ($uploaded !== '') {
            return $this->heroImageUrl = $this->buildMediaUrl(self::HERO_MEDIA_SUBDIR . '/' . ltrim($uploaded, '/'));
        }

        return $this->heroImageUrl = $this->buildThemeImageUrl(self::DEFAULT_HERO_IMAGE);
    }

    public function getHeroOpacity(): float
    {
        return $this->getOpacity(self::XML_PATH_HERO_OPACITY, self::DEFAULT_HERO_OPACITY);
    }

    public function getHeroTitle(): string
    {
        return $this->getStoreValue(self::XML_PATH_HERO_TITLE) ?: (string)__('Noite Assombrada');
    }

    public function getHeroSubtitle(): string
    {
        return $this->getStoreValue(self::XML_PATH_HERO_SUBTITLE)
            ?: (string)__('A coleção mais aterrorizante do ano chegou');
    }

    public function getHeroButtonText(): string
    {
        return $this->getStoreValue(self::XML_PATH_HERO_BUTTON_TEXT);
    }

    /**
     * Coleção selecionada no admin tem prioridade sobre o link digitado à mão.
     */
    public function getHeroButtonUrl(): string
    {
        if ($this->heroButtonUrl !== null) {
            return $this->heroButtonUrl;
        }

        $collectionUrl = $this->getSelectedCollectionUrl();
        if ($collectionUrl !== '') {
            return $this->heroButtonUrl = $collectionUrl;
        }

        return $this->heroButtonUrl = $this->getStoreValue(self::XML_PATH_HERO_BUTTON_LINK) ?: self::DEFAULT_BUTTON_LINK;
    }

    public function hasHeroButton(): bool
    {
        return $this->isHeroEnabled()
            && $this->isEnabled(self::XML_PATH_HERO_SHOW_BUTTON)
            && $this->getHeroButtonText() !== ''
            && $this->getHeroButtonUrl() !== '';
    }

    public function getFooterImageUrl(): string
    {
        if ($this->footerImageUrl !== null) {
            return $this->footerImageUrl;
        }

        if (!$this->isFooterImageVisible()) {
            return $this->footerImageUrl = '';
        }

        $uploaded = $this->getStoreValue(self::XML_PATH_FOOTER_IMAGE);
        if ($uploaded !== '') {
            return $this->footerImageUrl = $this->buildMediaUrl(self::FOOTER_MEDIA_SUBDIR . '/' . ltrim($uploaded, '/'));
        }

        return $this->footerImageUrl = $this->buildThemeImageUrl(self::DEFAULT_FOOTER_IMAGE);
    }

    public function getFooterOpacity(): float
    {
        return $this->getOpacity(self::XML_PATH_FOOTER_OPACITY, self::DEFAULT_FOOTER_OPACITY);
    }

    private function getSelectedCollectionUrl(): string
    {
        $categoryId = (int)$this->getStoreValue(self::XML_PATH_HERO_BUTTON_COLLECTION);
        if ($categoryId <= 0) {
            return '';
        }

        try {
            $category = $this->categoryFactory->create()->load($categoryId);
        } catch (LocalizedException $e) {
            return '';
        }

        if (!$category->getId()) {
            return '';
        }

        return (string)$this->categoryHelper->getCategoryUrl($category);
    }

    private function isEnabled(string $path): bool
    {
        $value = $this->getStoreValue($path);

        return $value === '' ? true : (bool)(int)$value;
    }

    private function getStoreValue(string $path): string
    {
        return trim((string)$this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE));
    }

    private function getOpacity(string $path, float $default): float
    {
        $value = $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE);

        return $value === null || $value === '' ? $default : min(1.0, max(0.0, (float)$value));
    }

    private function buildMediaUrl(string $path): string
    {
        return $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . $path;
    }

    /**
     * Imagem padrão do tema. Só é publicada se o arquivo existir de fato, para
     * que um arquivo renomeado no tema não vire uma requisição 404 silenciosa.
     */
    private function buildThemeImageUrl(string $path): string
    {
        if (!$this->fileDriver->isExists($this->themeWebDirectory() . '/' . $path)) {
            return '';
        }

        return $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_STATIC)
            . self::THEME_STATIC_PATH
            . $path;
    }

    private function themeWebDirectory(): string
    {
        return $this->directoryList->getPath(DirectoryList::ROOT) . '/' . self::THEME_DESIGN_PATH . '/web';
    }
}

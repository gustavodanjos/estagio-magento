<?php
declare(strict_types=1);

namespace Webjump\Gustavo\ViewModel;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\ScopeInterface;

class HomeBlock implements ArgumentInterface
{
    public const XML_PATH_TITLE = 'webjump_gustavo/general/title';
    public const XML_PATH_WELCOME_MESSAGE = 'webjump_gustavo/general/welcome_message';

    public const DEFAULT_TITLE = 'Webjump Gustavo - Home Block';
    public const DEFAULT_WELCOME_MESSAGE = 'Bloco renderizado na página inicial utilizando a arquitetura de ViewModels do Magento 2.';

    public function __construct(
        private readonly TimezoneInterface $timezone,
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    public function getTitle(): string
    {
        $value = trim((string) $this->scopeConfig->getValue(
            self::XML_PATH_TITLE,
            ScopeInterface::SCOPE_STORE
        ));

        return $value !== '' ? $value : self::DEFAULT_TITLE;
    }

    public function getWelcomeMessage(): string
    {
        $value = trim((string) $this->scopeConfig->getValue(
            self::XML_PATH_WELCOME_MESSAGE,
            ScopeInterface::SCOPE_STORE
        ));

        return $value !== '' ? $value : self::DEFAULT_WELCOME_MESSAGE;
    }

    public function getFormattedCurrentDate(): string
    {
        return $this->timezone->formatDateTime(
            $this->timezone->date(),
            \IntlDateFormatter::MEDIUM,
            \IntlDateFormatter::SHORT
        );
    }

    /**
     * @return string[]
     */
    public function getHighlights(): array
    {
        return [
            'Lógica de apresentação totalmente isolada no ViewModel',
            'Template sem regras de negócio, focado apenas em renderização',
            'Sem dependência de classes customizadas de Block',
            'Toda saída de dados protegida por escape nativo',
            'Estilos visuais carregados via CSS modular próprio',
        ];
    }
}

<?php
declare(strict_types=1);

namespace Webjump\Gustavo\ViewModel;

use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class HomeBlock implements ArgumentInterface
{
    public function __construct(
        private readonly TimezoneInterface $timezone
    ) {
    }

    public function getTitle(): string
    {
        return 'Webjump Gustavo - Home Block';
    }

    public function getWelcomeMessage(): string
    {
        return 'Bloco renderizado na página inicial utilizando a arquitetura de ViewModels do Magento 2.';
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

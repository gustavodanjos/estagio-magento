<?php
declare(strict_types=1);

namespace Webjump\Gustavo\CustomerData;

use Magento\Customer\CustomerData\SectionSourceInterface;
use Magento\Customer\Model\Session;

class ScareCounter implements SectionSourceInterface
{
    public const SESSION_KEY = 'webjump_gustavo_scare_counter';

    public function __construct(
        private readonly Session $session,
    ) {
    }

    public function getSectionData(): array
    {
        return ['count' => (int)$this->session->getData(self::SESSION_KEY)];
    }
}
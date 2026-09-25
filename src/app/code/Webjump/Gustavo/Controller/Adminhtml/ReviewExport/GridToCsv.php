<?php

declare(strict_types=1);

namespace Webjump\Gustavo\Controller\Adminhtml\ReviewExport;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Filesystem;
use Magento\Ui\Component\MassAction\Filter;
use Webjump\Gustavo\Model\Export\ReviewRowMapper;

class GridToCsv extends Action
{
    public const ADMIN_RESOURCE = 'Webjump_Gustavo::review_export';

    public function __construct(
        Context $context,
        private readonly FileFactory $fileFactory,
        private readonly Filter $filter,
        private readonly ReviewRowMapper $rowMapper,
        private readonly Filesystem $filesystem
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $component = $this->filter->getComponent();
        $this->filter->prepareComponent($component);
        $this->filter->applySelectionOnTargetProvider();

        $dataProvider = $component->getContext()->getDataProvider();
        $dataProvider->setLimit(0, 0);

        $rows = $this->rowMapper->getRows($dataProvider->getSearchResult());
        $headers = $this->rowMapper->getHeaders();

        $directory = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $directory->create('export');
        $file = 'export/avaliacoes-' . md5(microtime()) . '.csv';

        $stream = $directory->openFile($file, 'w+');
        $stream->lock();
        $stream->write("\xEF\xBB\xBF");
        $stream->writeCsv($headers);
        foreach ($rows as $row) {
            $stream->writeCsv($row);
        }
        $stream->unlock();
        $stream->close();

        return $this->fileFactory->create(
            'avaliacoes.csv',
            ['type' => 'filename', 'value' => $file, 'rm' => true],
            DirectoryList::VAR_DIR
        );
    }
}
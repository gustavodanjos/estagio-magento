<?php

declare(strict_types=1);

namespace Webjump\Gustavo\Controller\Adminhtml\ReviewExport;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Convert\Excel;
use Magento\Framework\Convert\ExcelFactory;
use Magento\Framework\Filesystem;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Ui\Model\Export\SearchResultIteratorFactory;
use Webjump\Gustavo\Model\Export\ReviewRowMapper;

class GridToXml extends Action
{
    public const ADMIN_RESOURCE = 'Webjump_Gustavo::review_export';

    public function __construct(
        Context $context,
        private readonly FileFactory $fileFactory,
        private readonly Filter $filter,
        private readonly ReviewRowMapper $rowMapper,
        private readonly ExcelFactory $excelFactory,
        private readonly SearchResultIteratorFactory $iteratorFactory,
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

        $iterator = $this->iteratorFactory->create(['items' => $rows]);
        $excel = $this->excelFactory->create([
            'iterator' => $iterator,
            'rowCallback' => static fn (array $row): array => $row,
        ]);
        $excel->setDataHeader($this->rowMapper->getHeaders());

        $directory = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $directory->create('export');
        $file = 'export/avaliacoes-' . md5(microtime()) . '.xml';

        $stream = $directory->openFile($file, 'w+');
        $stream->lock();
        $excel->write($stream, 'Avaliações');
        $stream->unlock();
        $stream->close();

        return $this->fileFactory->create(
            'avaliacoes.xml',
            ['type' => 'filename', 'value' => $file, 'rm' => true],
            DirectoryList::VAR_DIR
        );
    }
}
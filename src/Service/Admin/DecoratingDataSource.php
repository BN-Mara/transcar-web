<?php

namespace App\Service\Admin;

use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\AdminBundle\Exporter\DataSourceInterface;
use Sonata\DoctrineORMAdminBundle\Exporter\DataSource;
use Sonata\Exporter\Source\DoctrineORMQuerySourceIterator;

class DecoratingDataSource implements DataSourceInterface
{
    private DataSource $dataSource;

    public function __construct(DataSource $dataSource)
    {
        $this->dataSource = $dataSource;
    }

    public function createIterator(ProxyQueryInterface $query, array $fields): DoctrineORMQuerySourceIterator
    {
        /** @var DoctrineORMQuerySourceIterator $iterator */
        //$iterator = $this->dataSource->createIterator($query, $fields);
        $iterator = new DoctrineORMQuerySourceIterator($query->getQueryBuilder()->getQuery(), $fields);

        // Inspect the data being exported
        /*foreach ($iterator as $row) {
            dump($row); // Check if 'card.uid' is present and populated
        }*/

        $iterator->setDateTimeFormat('d/m/Y H:i:s');

        return $iterator;
    }
}
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
        $iterator = $this->dataSource->createIterator($query, $fields);

        $iterator->setDateTimeFormat('Y-m-d H:i:s');

        return $iterator;
    }
}
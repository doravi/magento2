<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Sales\Test\Unit\Model\Resource\Order\Status\History;

class CollectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Magento\Sales\Model\Resource\Order\Status\History\Collection
     */
    protected $collection;

    /**
     * @var \Magento\Framework\DB\Adapter\Pdo\Mysql|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $connectionMock;

    /**
     * @var \Magento\Framework\Event\ManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventManagerMock;

    /**
     * @var \Zend_Db_Select|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $selectMock;

    /**
     * @var \Magento\Sales\Model\Order\Status\History|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $historyItemMock;

    /**
     * @var \Magento\Framework\Model\Resource\Db\AbstractDb|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resourceMock;

    /**
     * @var \Magento\Framework\Data\Collection\Db\FetchStrategyInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $fetchStrategyMock;

    /**
     * @var \Magento\Framework\Data\Collection\EntityFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityFactoryMock;
    /**
     * @var \Magento\Sales\Model\Resource\EntitySnapshot|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $entitySnapshotMock;

    public function setUp()
    {
        $this->eventManagerMock = $this->getMock('Magento\Framework\Event\ManagerInterface', [], [], '', false);
        $this->connectionMock = $this->getMock('Magento\Framework\DB\Adapter\Pdo\Mysql', [], [], '', false);
        $this->selectMock = $this->getMock('Zend_Db_Select', [], [], '', false);
        $this->historyItemMock = $this->getMock(
            'Magento\Sales\Model\Order\Status\History',
            ['__wakeup', 'setData'],
            [],
            '',
            false
        );
        $this->resourceMock = $this->getMockForAbstractClass(
            'Magento\Sales\Model\Resource\EntityAbstract',
            [],
            '',
            false,
            true,
            true,
            ['getReadConnection', 'getMainTable', 'getTable', '__wakeup']
        );
        $this->entitySnapshotMock = $this->getMock(
            'Magento\Sales\Model\Resource\EntitySnapshot',
            [],
            [],
            '',
            false
        );
        $this->fetchStrategyMock = $this->getMockForAbstractClass(
            'Magento\Framework\Data\Collection\Db\FetchStrategyInterface'
        );
        $this->entityFactoryMock = $this->getMock('Magento\Framework\Data\Collection\EntityFactory', [], [], '', false);

        $this->resourceMock->expects($this->any())->method('getReadConnection')->will(
            $this->returnValue($this->connectionMock)
        );
        $this->resourceMock->expects($this->any())->method('getTable')->will($this->returnArgument(0));

        $this->connectionMock->expects($this->any())->method('quoteIdentifier')->will($this->returnArgument(0));
        $this->connectionMock->expects($this->atLeastOnce())
            ->method('select')
            ->will($this->returnValue($this->selectMock));

        $data = [['data']];
        $this->historyItemMock->expects($this->once())
            ->method('setData')
            ->with($this->equalTo($data[0]))
            ->will($this->returnValue($this->historyItemMock));

        $this->fetchStrategyMock->expects($this->once())
            ->method('fetchAll')
            ->will($this->returnValue($data));

        $this->entityFactoryMock->expects($this->once())
            ->method('create')
            ->will($this->returnValue($this->historyItemMock));

        $logger = $this->getMock('Psr\Log\LoggerInterface');
        $this->collection = new \Magento\Sales\Model\Resource\Order\Status\History\Collection(
            $this->entityFactoryMock,
            $logger,
            $this->fetchStrategyMock,
            $this->eventManagerMock,
            $this->entitySnapshotMock,
            $this->connectionMock,
            $this->resourceMock
        );
    }

    public function testGetUnnotifiedForInstance()
    {
        $orderId = 100000512;
        $entityType = 'order';

        $order = $this->getMock('Magento\Sales\Model\Order', ['__wakeup', 'getEntityType', 'getId'], [], '', false);
        $order->expects($this->once())
            ->method('getEntityType')
            ->will($this->returnValue($entityType));
        $order->expects($this->once())
            ->method('getId')
            ->will($this->returnValue($orderId));

        $this->connectionMock = $this->collection->getResource()->getReadConnection();
        $this->connectionMock->expects($this->exactly(3))
            ->method('prepareSqlCondition')
            ->will(
                $this->returnValueMap(
                    [
                        ['entity_name', $entityType, 'sql-string'],
                        ['is_customer_notified', 0, 'sql-string'],
                        ['parent_id', $orderId, 'sql-string'],
                    ]
                )
            );
        $result = $this->collection->getUnnotifiedForInstance($order);
        $this->assertEquals($this->historyItemMock, $result);
    }
}

<?php
/**
 * Copyright 2020 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);
namespace PayPal\Braintree\Test\Unit\Gateway\Http\Client;

use Magento\Framework\DataObject;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Model\Method\Logger;
use PayPal\Braintree\Gateway\Http\Client\TransactionSale;
use PayPal\Braintree\Model\Adapter\BraintreeAdapter;
use Psr\Log\LoggerInterface;

class TransactionSaleTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var TransactionSale
     */
    private $model;

    /**
     * @var Logger|\PHPUnit\Framework\MockObject\MockObject
     */
    private $loggerMock;

    /**
     * @var BraintreeAdapter|\PHPUnit\Framework\MockObject\MockObject
     */
    private $adapter;

    /**
     * Set up
     *
     * @return void
     */
    protected function setUp(): void
    {
        $criticalLoggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->loggerMock = $this->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->adapter = $this->getMockBuilder(BraintreeAdapter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->model = new TransactionSale($criticalLoggerMock, $this->loggerMock, $this->adapter);
    }

    /**
     * Run test placeRequest method (exception)
     *
     * @return void
     *
     */
    public function testPlaceRequestException()
    {
        $this->markTestSkipped('Skip this test');
        $this->expectException(\Magento\Payment\Gateway\Http\ClientException::class);
        $this->expectExceptionMessage('Test messages');

        $this->loggerMock->expects($this->once())
            ->method('debug')
            ->with(
                [
                    'request' => $this->getTransferData(),
                    'client' => TransactionSale::class,
                    'response' => []
                ]
            );

        $this->adapter->expects($this->once())
            ->method('sale')
            ->willThrowException(new \Exception('Test messages'));

        /** @var TransferInterface|\PHPUnit\Framework\MockObject\MockObject $transferObjectMock */
        $transferObjectMock = $this->getTransferObjectMock();

        $this->model->placeRequest($transferObjectMock);
    }

    /**
     * Run test placeRequest method
     *
     * @return void
     */
    public function testPlaceRequestSuccess()
    {
        $this->markTestSkipped('Skip this test');
        $response = $this->getResponseObject();
        $this->adapter->expects($this->once())
            ->method('sale')
            ->with($this->getTransferData())
            ->willReturn($response);

        $this->loggerMock->expects($this->once())
            ->method('debug')
            ->with(
                [
                    'request' => $this->getTransferData(),
                    'client' => TransactionSale::class,
                    'response' => ['success' => 1]
                ]
            );

        $actualResult = $this->model->placeRequest($this->getTransferObjectMock());

        $this->assertIsObject($actualResult['object']);
        $this->assertEquals(['object' => $response], $actualResult);
    }

    /**
     * @return TransferInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getTransferObjectMock()
    {
        $transferObjectMock = $this->createMock(TransferInterface::class);
        $transferObjectMock->expects($this->once())
            ->method('getBody')
            ->willReturn($this->getTransferData());

        return $transferObjectMock;
    }

    /**
     * @return DataObject
     */
    private function getResponseObject()
    {
        $obj = new DataObject();
        $obj->success = true;

        return $obj;
    }

    /**
     * @return array
     */
    private function getTransferData()
    {
        return [
            'test-data-key' => 'test-data-value'
        ];
    }
}

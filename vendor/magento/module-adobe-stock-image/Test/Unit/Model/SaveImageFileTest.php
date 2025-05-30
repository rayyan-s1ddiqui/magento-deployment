<?php
/**
 * Copyright 2024 Adobe
 * All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeStockImage\Test\Unit\Model;

use Magento\AdobeStockImage\Model\SaveImageFile;
use Magento\AdobeStockImage\Model\Storage\Save as StorageSave;
use Magento\Framework\Api\AttributeInterface;
use Magento\Framework\Api\Search\Document;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test saving image file.
 */
class SaveImageFileTest extends TestCase
{
    /**
     * @var MockObject|StorageSave
     */
    private $storageSave;

    /**
     * @var SaveImageFile
     */
    private $saveImageFile;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->storageSave = $this->createMock(StorageSave::class);

        $this->saveImageFile = (new ObjectManager($this))->getObject(
            SaveImageFile::class,
            [
                'storageSave' => $this->storageSave
            ]
        );
    }

    /**
     * Test getting save image path.
     *
     * @param \Closure $document
     * @param string $url
     * @param string $destinationPath
     * @dataProvider assetProvider
     */
    public function testExecute(\Closure $document, string $url, string $destinationPath): void
    {
        $document = $document($this);
        $this->storageSave->expects($this->once())
            ->method('execute');

        $this->storageSave->expects($this->once())
            ->method('execute')
            ->with($url, $destinationPath);

        $this->saveImageFile->execute($document, $url, $destinationPath);
    }

    /**
     * Test save image with exception.
     *
     * @param \Closure $document
     * @param string $url
     * @param string $destinationPath
     * @dataProvider assetProvider
     */
    public function testExecuteWithException(
        \Closure $document,
        string $url,
        string $destinationPath
    ): void {
        $document = $document($this);
        $this->storageSave->expects($this->once())
            ->method('execute')
            ->willThrowException(new \Exception('Some Exception'));

        $this->expectException(CouldNotSaveException::class);

        $this->saveImageFile->execute($document, $url, $destinationPath);
    }

    /**
     * Data provider for testExecute
     *
     * @return array
     */
    public static function assetProvider(): array
    {
        return [
            [
                'document' => static fn (self $testCase) => $testCase->getDocument(),
                'url' => 'https://as2.ftcdn.net/jpg/500_FemVonDcttCeKiOXFk.jpg',
                'destinationPath' => 'path'
            ],
            [
                'document' => static fn (self $testCase) => $testCase->getDocument('filepath.jpg'),
                'url' => 'https://as2.ftcdn.net/jpg/500_FemVonDcttCeKiOXFk.jpg',
                'destinationPath' => 'path',
            ],
        ];
    }

    /**
     * Get document
     *
     * @param string|null $path
     * @return MockObject
     */
    protected function getDocument(?string $path = null): MockObject
    {
        $document = $this->createMock(Document::class);
        $pathAttribute = $this->createMock(AttributeInterface::class);
        $pathAttribute->expects($this->once())
            ->method('getValue')
            ->willReturn($path);
        $document->expects($this->once())
            ->method('getCustomAttribute')
            ->with('path')
            ->willReturn($pathAttribute);

        return $document;
    }
}

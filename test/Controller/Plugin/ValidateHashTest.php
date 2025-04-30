<?php

declare(strict_types=1);

namespace FwsDoctrineAuthTest\Controller\Plugin;

use FwsDoctrineAuth\Controller\Plugin\ValidateHash;
use FwsDoctrineAuthTest\Controller\TestAsset\SampleController;
use Laminas\Validator\Csrf;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(ValidateHash::class)]
class ValidateHashTest extends TestCase
{
    protected Csrf|MockObject $csrfMock;
    protected SampleController $controller;
    protected string $testValidHash   = 'test-valid-hash';
    protected string $testInvalidHash = 'test-invalid-hash';

    public function setUp(): void
    {
        parent::setUp();

        $this->csrfMock = $this->getMockBuilder(Csrf::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'isValid',
                'getHash',
            ])
            ->getMock();

        $this->controller = new SampleController();
        $this->controller->getPluginManager()->setService(
            'validateHash',
            new ValidateHash(
                $this->csrfMock
            )
        );
    }

    /**
     * Test validate hash plugin invokes
     *
     * @group controller-plugins
     * @group validate-hash-plugin
     * @return void
     */
    public function testValidateHashInvokes()
    {
        $this->csrfMock->expects($this->never())->method('isValid');
        $this->csrfMock->expects($this->never())->method('getHash');

        $response = $this->controller->validateHash();
        $this->assertInstanceOf(ValidateHash::class, $response);
    }

    /**
     * Test validate hash plugin is valid, empty hash
     *
     * @group controller-plugins
     * @group validate-hash-plugin
     * @return void
     */
    public function testValidateHashIsValidEmptyHash()
    {
        $this->csrfMock->expects($this->never())->method('isValid');
        $this->csrfMock->expects($this->never())->method('getHash');

        $response = $this->controller->validateHash()->isValid('');
        $this->assertFalse($response);
    }

    /**
     * Test validate hash plugin is valid, is not a valid hash
     *
     * @group controller-plugins
     * @group validate-hash-plugin
     * @return void
     */
    public function testValidateHashIsValidInvalidHash()
    {
        $this->csrfMock->expects($this->once())->method('isValid')->with($this->testInvalidHash)->willReturn(false);

        $this->csrfMock->expects($this->never())->method('getHash');

        $response = $this->controller->validateHash()->isValid($this->testInvalidHash);
        $this->assertFalse($response);
    }

    /**
     * Test validate hash plugin is valid, is a valid hash
     *
     * @group controller-plugins
     * @group validate-hash-plugin
     * @return void
     */
    public function testValidateHashIsValidValidHash()
    {
        $this->csrfMock->expects($this->once())->method('isValid')->with($this->testValidHash)->willReturn(true);

        $this->csrfMock->expects($this->never())->method('getHash');

        $response = $this->controller->validateHash()->isValid($this->testValidHash);
        $this->assertTrue($response);
    }

    /**
     * Test validate hash plugin get hash
     *
     * @group controller-plugins
     * @group validate-hash-plugin
     * @return void
     */
    public function testValidateHashGetHash()
    {
        $this->csrfMock->expects($this->once())->method('getHash')->willReturn($this->testValidHash);

        $this->csrfMock->expects($this->never())->method('isValid');

        $response = $this->controller->validateHash()->getHash();
        $this->assertIsString($response);
        $this->assertEquals($this->testValidHash, $response);
    }
}

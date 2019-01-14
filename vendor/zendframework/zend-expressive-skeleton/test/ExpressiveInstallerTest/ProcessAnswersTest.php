<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-skeleton for the canonical source repository
 * @copyright Copyright (c) 2015-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-skeleton/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ExpressiveInstallerTest;

use ExpressiveInstaller\OptionalPackages;
use Prophecy\Argument;

class ProcessAnswersTest extends OptionalPackagesTestCase
{
    use ProjectSandboxTrait;

    /**
     * @var OptionalPackages
     */
    private $installer;

    protected function setUp()
    {
        parent::setUp();
        $this->projectRoot = $this->copyProjectFilesToTempFilesystem();
        $this->installer   = $this->createOptionalPackages($this->projectRoot);
        $this->prepareSandboxForInstallType(OptionalPackages::INSTALL_MINIMAL, $this->installer);
    }

    protected function tearDown()
    {
        parent::tearDown();
        chdir($this->packageRoot);
        $this->recursiveDelete($this->projectRoot);
    }

    public function testInvalidAnswer()
    {
        $this->io->write()->shouldNotBeCalled();

        $config   = $this->getInstallerConfig($this->installer);
        $question = $config['questions']['container'];
        $answer   = 'foobar';
        $result   = $this->installer->processAnswer($question, $answer);

        $this->assertFalse($result);
        $this->assertFileNotExists($this->projectRoot . '/config/container.php');
    }

    public function testAnsweredWithN()
    {
        $this->io->write()->shouldNotBeCalled();

        $config   = $this->getInstallerConfig($this->installer);
        $question = $config['questions']['container'];
        $answer   = 'n';
        $result   = $this->installer->processAnswer($question, $answer);

        $this->assertFalse($result);
        $this->assertFileNotExists($this->projectRoot . '/config/container.php');
    }

    public function testAnsweredWithInvalidOption()
    {
        $this->io->write()->shouldNotBeCalled();

        $config   = $this->getInstallerConfig($this->installer);
        $question = $config['questions']['container'];
        $answer   = 10;
        $result   = $this->installer->processAnswer($question, $answer);

        $this->assertFalse($result);
        $this->assertFileNotExists($this->projectRoot . '/config/container.php');
    }

    public function testAnsweredWithValidOption()
    {
        // Prepare the installer
        $this->installer->removeDevDependencies();

        // @codingStandardsIgnoreStart
        $this->io->write(Argument::containingString('Adding package <info>zendframework/zend-auradi-config</info>'))->shouldBeCalled();
        $this->io->write(Argument::containingString('Copying <info>config/container.php</info>'))->shouldBeCalled();
        // @codingStandardsIgnoreEnd

        $config   = $this->getInstallerConfig($this->installer);
        $question = $config['questions']['container'];
        $answer   = 1;
        $result   = $this->installer->processAnswer($question, $answer);

        $this->assertTrue($result);
        $this->assertFileExists($this->projectRoot . '/config/container.php');
        $this->assertPackage('zendframework/zend-auradi-config', $this->installer);
    }

    public function testAnsweredWithPackage()
    {
        // Prepare the installer
        $this->installer->removeDevDependencies();

        $this->io->write(Argument::containingString('Adding package <info>league/container</info>'))->shouldBeCalled();
        $this->io->write(Argument::containingString('<warning>You need to edit public/index.php'))->shouldBeCalled();

        $config   = $this->getInstallerConfig($this->installer);
        $question = $config['questions']['container'];
        $answer   = 'league/container:2.2.0';
        $result   = $this->installer->processAnswer($question, $answer);

        $this->assertTrue($result);
        $this->assertFileNotExists($this->projectRoot . '/config/container.php');
        $this->assertPackage('league/container', $this->installer);
    }

    public function testPackagesAreAddedToWhitelist()
    {
        // Prepare the installer
        $this->installer->removeDevDependencies();

        $this->io
            ->write(Argument::containingString(
                'Adding package <info>zendframework/zend-expressive-zendviewrenderer</info>'
            ))
            ->shouldBeCalled();
        $this->io
            ->write(Argument::containingString(
                'Whitelist package <info>zendframework/zend-expressive-zendviewrenderer</info>'
            ))
            ->shouldBeCalled();

        $config   = $this->getInstallerConfig($this->installer);
        $question = $config['questions']['template-engine'];
        $answer   = 3;
        $result   = $this->installer->processAnswer($question, $answer);

        $this->assertTrue($result);
        $this->assertPackage('zendframework/zend-expressive-zendviewrenderer', $this->installer);
        $this->assertWhitelisted('zendframework/zend-expressive-zendviewrenderer', $this->installer);
    }
}

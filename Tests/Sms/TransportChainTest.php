<?php
/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticVonageBundle\Tests\Sms;

use Exception;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\MauticVonageBundle\Integration\Vonage\VonageTransport;
use MauticPlugin\MauticVonageBundle\Sms\TransportChain;
use MauticPlugin\MauticVonageBundle\Sms\TransportInterface;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionClass;

class TransportChainTest extends MauticMysqlTestCase
{
    /**
     * @var TransportChain|MockObject
     */
    private $transportChain;

    /**
     * @var TransportInterface|MockObject
     */
    private $VonageTransport;

    /**
     * Call protected/private method of a class.
     *
     * @param object &$object    Instantiated object that we will run method on
     * @param string $methodName Method name to call
     * @param array  $parameters array of parameters to pass into method
     *
     * @throws \ReflectionException
     *
     * @return mixed method return
     */
    public function invokeMethod(&$object, $methodName, array $parameters = [])
    {
        $reflection = new ReflectionClass(get_class($object));
        $method     = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->transportChain = new TransportChain(
            'mautic.test.Vonage.mock',
            $this->container->get('mautic.helper.integration')
        );

        $this->VonageTransport = $this->createMock(VonageTransport::class);

        $this->VonageTransport
            ->method('sendSMS')
            ->will($this->returnValue('lol'));
    }

    public function testAddTransport()
    {
        $count = count($this->transportChain->getTransports());

        $this->transportChain->addTransport('mautic.transport.test', $this->container->get('mautic.sms.Vonage.transport'), 'mautic.transport.test', 'Vonage');

        $this->assertCount($count + 1, $this->transportChain->getTransports());
    }

    public function testSendSms()
    {
        $this->testAddTransport();

        $this->transportChain->addTransport('mautic.test.Vonage.mock', $this->VonageTransport, 'mautic.test.Vonage.mock', 'Vonage');

        $lead = new Lead();
        $lead->setMobile('+123456789');

        try {
            $this->transportChain->sendSms($lead, 'Yeah');
        } catch (Exception $e) {
            $message = $e->getMessage();
            $this->assertEquals('Primary SMS transport is not enabled', $message);
        }
    }
}

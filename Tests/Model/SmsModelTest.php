<?php

namespace MauticPlugin\MauticVonageBundle\Tests\Model;

use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use MauticPlugin\MauticVonageBundle\Entity\SmsRepository;
use MauticPlugin\MauticVonageBundle\Form\Type\SmsType;
use MauticPlugin\MauticVonageBundle\Model\MessagesModel;

class MessagesModelTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test to get lookup results when class name is sent as a parameter.
     */
    public function testGetLookupResultsWhenTypeIsClass()
    {
        $entities       = [['name' => 'Mautic', 'id' => 1, 'language' => 'cs']];
        $repositoryMock = $this->createMock(SmsRepository::class);
        $repositoryMock->method('getSmsList')
            ->with('', 10, 0, true, false)
            ->willReturn($entities);
        // Partial mock, mocks just getRepository
        $messagesModel = $this->getMockBuilder(MessagesModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRepository'])
            ->getMock();
        $messagesModel->method('getRepository')
            ->willReturn($repositoryMock);
        $securityMock = $this->createMock(CorePermissions::class);
        $securityMock->method('isGranted')
            ->with('sms:smses:viewother')
            ->willReturn(true);
        $messagesModel->setSecurity($securityMock);
        $textMessages = $messagesModel->getLookupResults(SmsType::class);
        $this->assertSame('Mautic', $textMessages['cs'][1], 'Mautic is the right text message name');
    }
}

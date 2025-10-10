<?php

namespace Drupal\Tests\mass_specc\admin\Unit;

use Drupal\admin\Service\ReportBuilderService;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\node\Entity\Node;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\DependencyInjection\ContainerInterface;

#[CoversClass(ReportBuilderService::class)]
#[CoversMethod(ReportBuilderService::class, 'downloadReportTemplate')]
#[CoversMethod(ReportBuilderService::class, 'getCustomFields')]
#[CoversMethod(ReportBuilderService::class, 'saveCustomFields')]
#[CoversMethod(ReportBuilderService::class, 'getIdByTemplateName')]
#[Group('admin')]
class ReportBuilderServiceTest extends UnitTestCase
{
    protected $service;
    protected $entityTypeManager;
    protected $nodeStorage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->nodeStorage = $this->createMock(EntityStorageInterface::class);
        $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
        $this->entityTypeManager
            ->method('getStorage')
            ->with('node')
            ->willReturn($this->nodeStorage);

        $this->service = new ReportBuilderService($this->entityTypeManager);
    }

    public function testDownloadReportTemplateReturnsCsvAndFilename(): void
    {
        $node = $this->createMock(Node::class);
        $node->method('get')
            ->with('field_report_config')
            ->willReturn((object) [
                'value' => json_encode([
                    'selected_fields' => ['article:field_name1', 'article:field_name2'],
                    'custom_fields' => [
                        ['name' => 'Custom1', 'tooltip' => 'Tip1', 'selected' => 1],
                        ['name' => 'Custom2', 'selected' => 1],
                    ],
                ])
            ]);
        $node->method('label')->willReturn('Test Template');

        $this->nodeStorage
            ->method('load')
            ->with(1)
            ->willReturn($node);

        $fieldManager = $this->getMockBuilder(\stdClass::class)->addMethods(['getFieldDefinitions'])->getMock();
        $fieldManager->method('getFieldDefinitions')->willReturn([
            'field_name1' => $this->createFieldMock('Name 1', 'Desc1'),
            'field_name2' => $this->createFieldMock('Name 2', 'Desc2'),
        ]);
        \Drupal::setContainer($this->createContainerMock(['entity_field.manager' => $fieldManager]));

        $result = $this->service->downloadReportTemplate(1);

        $this->assertArrayHasKey('csv', $result);
        $this->assertArrayHasKey('filename', $result);
        $this->assertStringContainsString('Test_Template', $result['filename']);
        $this->assertStringContainsString('name1', $result['csv']);
        $this->assertStringContainsString('name2', $result['csv']);
        $this->assertStringContainsString('Custom1', $result['csv']);
        $this->assertStringContainsString('Custom2', $result['csv']);
    }

    private function createFieldMock(string $label, string $description)
    {
        $field = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getLabel', 'getDescription'])
            ->getMock();
        $field->method('getLabel')->willReturn($label);
        $field->method('getDescription')->willReturn($description);
        return $field;
    }

    public function testGetCustomFields(): void
    {
        $customNodes = ['node1', 'node2'];
        $this->nodeStorage
            ->method('loadByProperties')
            ->with(['type' => 'custom_fields', 'status' => 1])
            ->willReturn($customNodes);

        $result = $this->service->getCustomFields();
        $this->assertSame($customNodes, $result);
    }

    public function testSaveCustomFields(): void
    {
        $fields = [
            ['name' => 'CustomA', 'type' => 'text', 'tooltip' => 'TipA'],
            ['name' => 'CustomB', 'type' => 'text'],
            ['name' => '', 'type' => 'text'],
        ];

        $existingNodes = [];
        $this->nodeStorage
            ->method('loadByProperties')
            ->willReturnCallback(fn($props) => $existingNodes);

        $createdNodes = [];
        $this->nodeStorage
            ->method('create')
            ->willReturnCallback(function ($data) use (&$createdNodes) {
                $mockNode = $this->createMock(Node::class);
                $mockNode->expects($this->once())->method('save');
                $createdNodes[] = $data;
                return $mockNode;
            });

        $this->service->saveCustomFields($fields);

        $this->assertCount(2, $createdNodes);
        $this->assertEquals('CustomA', $createdNodes[0]['title']);
        $this->assertEquals('TipA', $createdNodes[0]['field_tooltip']);
        $this->assertEquals('CustomB', $createdNodes[1]['title']);
        $this->assertEquals('', $createdNodes[1]['field_tooltip']);
    }

    public function testGetIdByTemplateName(): void
    {
        $query = $this->getMockBuilder(\Drupal\Core\Entity\Query\QueryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $query->method('condition')->willReturnSelf();
        $query->method('range')->willReturnSelf();
        $query->method('accessCheck')->willReturnSelf();
        $query->method('execute')->willReturn([42]);

        $this->nodeStorage->method('getQuery')->willReturn($query);

        $result = $this->service->getIdByTemplateName('TestName');
        $this->assertSame(42, $result);
    }

    /**
     * Helper to mock Drupal service container.
     */
    private function createContainerMock(array $services): ContainerInterface
    {
        $container = $this->getMockBuilder(ContainerInterface::class)
            ->getMock();

        $container->method('get')
            ->willReturnCallback(fn($id) => $services[$id]);

        $container->method('has')
            ->willReturnCallback(fn($id) => isset($services[$id]));

        return $container;
    }
}

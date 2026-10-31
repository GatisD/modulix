<?php
use Modulix\Mcp\AbilityCatalog;
use Modulix\Mcp\AbilityRegistrar;
use Modulix\Mcp\AccessGate;
use Modulix\Mcp\ArraySchemaRegistry;
use Modulix\Mcp\ReadSource;
use Modulix\Mcp\RequestGate;
use PHPUnit\Framework\TestCase;

final class McpAbilityCatalogTest extends TestCase {
    public function test_every_read_ability_has_schemas_permission_and_execute_callbacks(): void {
        $catalog = new AbilityCatalog(
            new FakeMcpReadSource(),
            new FakeMcpAccessGate(true, [42]),
            new AllowAllMcpRequestGate(),
            new ArraySchemaRegistry([
                'heading' => ['name' => 'heading', 'type' => 'widget', 'controls' => []],
            ])
        );

        $definitions = $catalog->definitions();
        $this->assertSame([
            'modulix/site-overview',
            'modulix/list-documents',
            'modulix/get-document',
            'modulix/widget-schema',
        ], array_keys($definitions));
        foreach ($definitions as $definition) {
            $this->assertIsArray($definition['input_schema']);
            $this->assertIsArray($definition['output_schema']);
            $this->assertIsCallable($definition['permission_callback']);
            $this->assertIsCallable($definition['execute_callback']);
            $this->assertSame('modulix', $definition['category']);
            $this->assertTrue($definition['meta']['mcp']['public']);
        }
    }

    public function test_document_permission_checks_site_and_document_capabilities(): void {
        $allowed = new AbilityCatalog(new FakeMcpReadSource(), new FakeMcpAccessGate(true, [42]), new AllowAllMcpRequestGate(), new ArraySchemaRegistry([]));
        $denied = new AbilityCatalog(new FakeMcpReadSource(), new FakeMcpAccessGate(true, []), new AllowAllMcpRequestGate(), new ArraySchemaRegistry([]));

        $this->assertTrue(($allowed->definitions()['modulix/get-document']['permission_callback'])(['post_id' => 42]));
        $this->assertFalse(($denied->definitions()['modulix/get-document']['permission_callback'])(['post_id' => 42]));
    }

    public function test_read_callbacks_return_source_data_and_widget_schema(): void {
        $catalog = new AbilityCatalog(new FakeMcpReadSource(), new FakeMcpAccessGate(true, [42]), new AllowAllMcpRequestGate(), new ArraySchemaRegistry([
            'heading' => ['name' => 'heading', 'type' => 'widget', 'controls' => []],
        ]));
        $definitions = $catalog->definitions();

        $this->assertSame('1.0.0', ($definitions['modulix/site-overview']['execute_callback'])(null)['modulix_version']);
        $this->assertSame(42, ($definitions['modulix/get-document']['execute_callback'])(['post_id' => 42])['id']);
        $this->assertSame('heading', ($definitions['modulix/widget-schema']['execute_callback'])(['element_type' => 'heading'])['name']);
    }

    public function test_only_explicitly_enabled_abilities_are_selected_for_registration(): void {
        $definitions = [
            'modulix/site-overview' => ['label' => 'Overview'],
            'modulix/get-document' => ['label' => 'Document'],
        ];

        $this->assertSame([
            'modulix/get-document' => ['label' => 'Document'],
        ], AbilityRegistrar::select($definitions, ['modulix/get-document', 'modulix/publish']));
    }

    public function test_execute_callbacks_fail_closed_if_permission_dispatch_is_bypassed(): void {
        $catalog = new AbilityCatalog(
            new FakeMcpReadSource(),
            new FakeMcpAccessGate(false, []),
            new AllowAllMcpRequestGate(),
            new ArraySchemaRegistry([])
        );

        $result = ($catalog->definitions()['modulix/site-overview']['execute_callback'])([]);

        $this->assertSame('forbidden', $result['error']['code']);
    }
}

final class FakeMcpReadSource implements ReadSource {
    public function siteOverview(): array { return ['elementor_version' => '4.3.5', 'modulix_version' => '1.0.0', 'kit' => [], 'theme_templates' => []]; }
    public function listDocuments(int $page, int $perPage): array { return [['id' => 42, 'title' => 'Page', 'type' => 'page', 'status' => 'draft', 'modified' => '2026-10-24 12:00:00']]; }
    public function getDocument(int $id): ?array { return $id === 42 ? ['id' => 42, 'elements' => [], 'page_settings' => []] : null; }
}

final class FakeMcpAccessGate implements AccessGate {
    public function __construct(private bool $site, private array $documents) {}
    public function canReadSite(): bool { return $this->site; }
    public function canReadDocument(int $postId): bool { return in_array($postId, $this->documents, true); }
    public function canEditDocument(int $postId): bool { return in_array($postId, $this->documents, true); }
    public function canCreateDocument(string $postType): bool { return $this->site; }
    public function userId(): int { return 7; }
}

final class AllowAllMcpRequestGate implements RequestGate {
    public function checkRead(int $userId, array $input): ?string { return null; }
    public function checkWrite(int $userId, array $input, int $batchSize): ?string { return null; }
}

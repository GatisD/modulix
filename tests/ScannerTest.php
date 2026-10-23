<?php
use Modulix\Readiness\Document;
use Modulix\Readiness\Scanner;
use PHPUnit\Framework\TestCase;

final class ScannerTest extends TestCase {
    private const PRO = ['widgets' => ['form', 'posts'], 'tags' => ['popup', 'post-title'],
                         'documents' => ['popup', 'header'], 'features' => ['sticky', 'attributes']];
    private const COVERED = ['widgets' => ['posts'], 'tags' => ['post-title'], 'documents' => ['header'], 'features' => ['sticky']];

    private static function widget(string $name): array {
        return ['elType' => 'widget', 'widgetType' => $name];
    }

    public function test_reports_only_pro_items_modulix_lacks(): void {
        $docs = [
            new Document(1, 'Home', 'wp-page', [self::widget('form'), self::widget('posts'), self::widget('heading')], []),
            new Document(2, 'Offer', 'popup', [['elType' => 'container', 'settings' => ['_attributes' => 'data-x|1', 'sticky' => 'top']]], []),
            new Document(3, 'Header', 'header', [self::widget('form')], []),
        ];
        $this->assertSame(
            ['document:popup' => [2], 'feature:attributes' => [2], 'widget:form' => [1, 3]],
            (new Scanner(self::PRO, self::COVERED))->missing($docs)
        );
    }

    public function test_nothing_missing_gives_an_empty_array(): void {
        $docs = [new Document(1, 'Blog', 'header', [self::widget('posts')], [])];
        $this->assertSame([], (new Scanner(self::PRO, self::COVERED))->missing($docs));
    }

    public function test_real_lists_flag_form_and_not_posts(): void {
        $docs = [new Document(5, 'Contact', 'wp-page', [self::widget('form'), self::widget('posts')], [])];
        $this->assertSame(['widget:form' => [5]], Scanner::forModulix()->missing($docs));
    }
}

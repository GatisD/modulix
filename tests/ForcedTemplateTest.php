<?php
use Modulix\ForcedTemplate;
use PHPUnit\Framework\TestCase;

final class ForcedTemplateTest extends TestCase {
    /** ?theme_template_id is a preview parameter for users who can edit the template. */
    public function test_visitor_who_cannot_edit_gets_no_forced_template(): void {
        $this->assertNull(ForcedTemplate::id('13', fn(int $id): bool => false));
    }

    public function test_editor_gets_the_template_as_an_integer(): void {
        $seen = [];
        $this->assertSame(13, ForcedTemplate::id('13', function (int $id) use (&$seen): bool { $seen[] = $id; return true; }));
        $this->assertSame([13], $seen);
    }

    /** @dataProvider junk */
    public function test_non_ids_are_ignored_before_any_capability_check($raw): void {
        $this->assertNull(ForcedTemplate::id($raw, function (): bool { throw new LogicException('capability checked'); }));
    }
    public static function junk(): array {
        return [[null], [''], ['0'], ['-5'], ['13abc'], ['1e3'], [['13']], [' 13']];
    }
}

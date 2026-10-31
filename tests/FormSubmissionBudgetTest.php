<?php
use Modulix\Security\PublicRequestBudget;
use PHPUnit\Framework\TestCase;

final class FormSubmissionBudgetTest extends TestCase {
    public function test_budget_is_bounded_within_a_window_and_resets_after_it(): void {
        $state = null;

        for ($i = 0; $i < 3; $i++) {
            [$allowed, $state] = PublicRequestBudget::consume($state, 100, 3, 60);
            $this->assertTrue($allowed);
        }

        [$allowed, $state] = PublicRequestBudget::consume($state, 110, 3, 60);
        $this->assertFalse($allowed);

        [$allowed, $state] = PublicRequestBudget::consume($state, 161, 3, 60);
        $this->assertTrue($allowed);
        $this->assertSame(['start' => 161, 'count' => 1], $state);
    }

    public function test_invalid_limits_fail_closed(): void {
        [$allowed] = PublicRequestBudget::consume(null, 100, 0, 60);
        $this->assertFalse($allowed);
    }

    public function test_form_handler_checks_the_budget_before_loading_a_document(): void {
        $source = (string) file_get_contents(__DIR__ . '/../modules/forms/classes/ajax-handler.php');
        $budget = strpos($source, 'request_budget_allows( $post_id )');
        $document = strpos($source, 'documents->get( $post_id )');

        $this->assertNotFalse($budget);
        $this->assertNotFalse($document);
        $this->assertLessThan($document, $budget);
    }
}

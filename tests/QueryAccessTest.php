<?php
use Modulix\QueryAccess;
use PHPUnit\Framework\TestCase;

final class QueryAccessTest extends TestCase {
    public function test_user_search_is_built_on_the_server(): void {
        $this->assertSame([
            'fields' => ['ID', 'display_name'],
            'search' => '*adm*',
            'search_columns' => ['user_login', 'user_nicename'],
        ], QueryAccess::user_search_args('adm', false));
    }

    public function test_email_only_for_users_who_may_see_private_data(): void {
        $this->assertSame(['ID', 'display_name', 'user_email'], QueryAccess::user_search_args('a', true)['fields']);
        $this->assertSame(['ID', 'display_name'], QueryAccess::user_titles_args(['1'], false)['fields']);
    }

    public function test_titles_take_only_integer_ids(): void {
        $this->assertSame([1, 7], QueryAccess::user_titles_args(['1', '7x', 0, '7', -3, ['9']], false)['include']);
    }

    public function test_display_text_is_never_the_raw_user_object(): void {
        $user = (object) ['ID' => 1, 'display_name' => 'Gatis', 'user_email' => 'g@x.test', 'user_pass' => 'HASH'];
        $this->assertSame('Gatis', QueryAccess::user_display_text($user, 'raw', true));
        $this->assertSame('Gatis', QueryAccess::user_display_text($user, 'minimal', true));
        $this->assertSame('Gatis (g@x.test)', QueryAccess::user_display_text($user, 'detailed', true));
        $this->assertSame('Gatis', QueryAccess::user_display_text($user, 'detailed', false));
    }
}

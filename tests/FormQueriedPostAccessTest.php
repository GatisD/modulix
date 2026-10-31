<?php
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

final class FormQueriedPostResponse extends RuntimeException {
    public function __construct(public array $payload, public bool $success) {
        parent::__construct('Form response captured.');
    }
}

final class FormQueriedPostState {
    public static array $posts = [];
    public static array $meta = [];
    public static array $editable = [];
    public static int $currentPostId = 0;
    public static ?object $formsModule = null;
}

final class FormQueriedPostDatabase {
    public function switch_to_post(int $postId): void {
        FormQueriedPostState::$currentPostId = $postId;
    }
}

final class FormQueriedPostDocument {
    public function get_elements_data(): array {
        return [['id' => 'form-under-test']];
    }
}

final class FormQueriedPostDocuments {
    public function get(int $postId): ?FormQueriedPostDocument {
        return 100 === $postId ? new FormQueriedPostDocument() : null;
    }
}

final class FormQueriedPostWidget {
    public function get_settings_for_display(): array {
        return [
            'form_fields' => [['custom_id' => 'message']],
            'submit_actions' => ['redirect'],
            'redirect_to' => get_post_meta(get_the_ID(), 'redirect_target', true),
        ];
    }
}

final class FormQueriedPostElementsManager {
    public function create_element_instance(array $form): FormQueriedPostWidget {
        return new FormQueriedPostWidget();
    }
}

final class FormQueriedPostElementor {
    public FormQueriedPostDatabase $db;
    public FormQueriedPostDocuments $documents;
    public FormQueriedPostElementsManager $elements_manager;

    public function __construct() {
        $this->db = new FormQueriedPostDatabase();
        $this->documents = new FormQueriedPostDocuments();
        $this->elements_manager = new FormQueriedPostElementsManager();
    }
}

final class FormQueriedPostActionsRegistrar {
    public function get(): array {
        return [new \Modulix\Modules\Forms\Actions\Redirect()];
    }
}

final class FormQueriedPostModule {
    public FormQueriedPostActionsRegistrar $actions_registrar;

    public function __construct() {
        $this->actions_registrar = new FormQueriedPostActionsRegistrar();
    }

    public function get_component(string $name): mixed {
        return null;
    }
}

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class FormQueriedPostAccessTest extends TestCase {
    protected function setUp(): void {
        $this->bootRuntime();

        FormQueriedPostState::$posts = [
            100 => (object) ['post_status' => 'publish', 'post_password' => ''],
        ];
        FormQueriedPostState::$meta = [];
        FormQueriedPostState::$editable = [];
        FormQueriedPostState::$currentPostId = 0;
        FormQueriedPostState::$formsModule = new FormQueriedPostModule();
        \Modulix\Plugin::$elementor = new FormQueriedPostElementor();
        $_SERVER['REMOTE_ADDR'] = 'fixture-address';
    }

    private function bootRuntime(): void {
        if (!defined('ABSPATH')) {
            define('ABSPATH', __DIR__ . '/');
        }

        eval(<<<'PHP'
namespace Modulix\Core {
    final class Utils {
        public static function _unstable_get_super_global_value(array $source, string $key): mixed {
            return $source[$key] ?? null;
        }
    }
}
namespace Modulix {
    final class Plugin {
        public static ?object $elementor = null;
        public static function elementor(): object { return self::$elementor; }
    }
}
namespace Modulix\Modules\Forms {
    final class Module {
        public static function find_element_recursive(array $elements, string $formId): ?array {
            return $elements[0] ?? null;
        }
        public static function instance(): object { return \FormQueriedPostState::$formsModule; }
    }
}
namespace Modulix\Modules\Forms\Classes {
    abstract class Action_Base {}
    final class Form_Record {
        public function __construct(private array $fields, private array $form) {}
        public function validate(object $handler): bool { return true; }
        public function process_fields(object $handler): void {}
        public function get(string $key): array { return []; }
        public function get_form_settings(string $key): mixed { return $this->form['settings'][$key] ?? null; }
        public function replace_setting_shortcodes(mixed $value, bool $urlEncode = false): mixed { return $value; }
    }
}
namespace Elementor {
    final class Controls_Manager { public const TEXT = 'text'; }
}
namespace Elementor\Modules\DynamicTags {
    final class Module {
        public const POST_META_CATEGORY = 'post-meta';
        public const TEXT_CATEGORY = 'text';
        public const URL_CATEGORY = 'url';
    }
}
namespace {
    function add_action(...$args): void {}
    function apply_filters(string $hook, mixed $value, mixed ...$args): mixed { return $value; }
    function current_user_can(string $capability, mixed ...$args): bool {
        $postId = (int) ($args[0] ?? 0);
        return 'edit_post' === $capability && (FormQueriedPostState::$editable[$postId] ?? false);
    }
    function do_action(...$args): void {}
    function esc_html__(string $text, string $domain): string { return $text; }
    function esc_url_raw(string $url): string { return filter_var($url, FILTER_VALIDATE_URL) ? $url : ''; }
    function get_post(int $postId): ?object { return FormQueriedPostState::$posts[$postId] ?? null; }
    function get_post_meta(int $postId, string $key, bool $single): mixed {
        return FormQueriedPostState::$meta[$postId][$key] ?? '';
    }
    function get_the_ID(): int { return FormQueriedPostState::$currentPostId; }
    function get_transient(string $key): mixed { return false; }
    function set_transient(string $key, mixed $value, int $expiration): bool { return true; }
    function wp_salt(string $scheme): string { return 'fixture-salt'; }
    function wp_send_json_error(array $payload): never { throw new FormQueriedPostResponse($payload, false); }
    function wp_send_json_success(array $payload): never { throw new FormQueriedPostResponse($payload, true); }
}
PHP);

        require_once __DIR__ . '/../modules/forms/actions/redirect.php';
        require_once __DIR__ . '/../modules/forms/classes/ajax-handler.php';
    }

    public function test_anonymous_draft_cannot_supply_a_post_custom_field_redirect(): void {
        $this->assertAnonymousQueryIsRejected(201, 'draft', '');
    }

    public function test_anonymous_private_post_cannot_supply_a_post_custom_field_redirect(): void {
        $this->assertAnonymousQueryIsRejected(202, 'private', '');
    }

    public function test_anonymous_password_protected_post_cannot_supply_a_post_custom_field_redirect(): void {
        $this->assertAnonymousQueryIsRejected(203, 'publish', 'set');
    }

    public function test_anonymous_missing_post_cannot_supply_a_post_custom_field_redirect(): void {
        $response = $this->submit(204);

        $this->assertFalse($response->success);
        $this->assertArrayNotHasKey('redirect_url', $response->payload['data']);
    }

    public function test_authorized_editor_preview_may_use_a_draft_post_custom_field_redirect(): void {
        $target = 'https://' . 'redirect' . '.invalid/editor-preview';
        FormQueriedPostState::$posts[205] = (object) ['post_status' => 'draft', 'post_password' => ''];
        FormQueriedPostState::$meta[205]['redirect_target'] = $target;
        FormQueriedPostState::$editable[205] = true;

        $response = $this->submit(205);

        $this->assertTrue($response->success);
        $this->assertSame($target, $response->payload['data']['redirect_url']);
    }

    public function test_queried_post_is_normalized_and_guarded_before_switching_global_post(): void {
        $source = (string) file_get_contents(__DIR__ . '/../modules/forms/classes/ajax-handler.php');
        $normalize = strpos($source, 'PublicDocumentAccess::normalizeIds( [ $queried_id ], 1 )');
        $load = strpos($source, 'get_post( $queried_id )');
        $guard = strpos($source, 'PublicDocumentAccess::allowed( $queried_post');
        $switch = strpos($source, 'switch_to_post( $queried_id )');

        $this->assertNotFalse($normalize);
        $this->assertNotFalse($load);
        $this->assertNotFalse($guard);
        $this->assertNotFalse($switch);
        $this->assertLessThan($load, $normalize);
        $this->assertLessThan($guard, $load);
        $this->assertLessThan($switch, $guard);
    }

    private function assertAnonymousQueryIsRejected(int $postId, string $status, string $password): void {
        FormQueriedPostState::$posts[$postId] = (object) [
            'post_status' => $status,
            'post_password' => $password,
        ];
        FormQueriedPostState::$meta[$postId]['redirect_target'] = 'https://' . 'redirect' . '.invalid/private-value';

        $response = $this->submit($postId);

        $this->assertFalse($response->success);
        $this->assertArrayNotHasKey('redirect_url', $response->payload['data']);
    }

    private function submit(mixed $queriedId): FormQueriedPostResponse {
        $_POST = [
            'post_id' => 100,
            'queried_id' => $queriedId,
            'form_id' => 'form-under-test',
            'form_fields' => ['message' => 'fixture'],
        ];

        try {
            (new \Modulix\Modules\Forms\Classes\Ajax_Handler())->ajax_send_form();
        } catch (FormQueriedPostResponse $response) {
            return $response;
        }

        $this->fail('The form handler did not emit a response.');
    }
}

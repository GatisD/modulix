<?php
namespace Modulix;

/** Boots a lifted Elementor-style module: its constructor (via instance()) registers its hooks. */
final class ElementorModule implements ModuleInterface {
    /** @param class-string $class */
    public function __construct(private string $class) {}

    public function boot(): void {
        $experimentalData = method_exists($this->class, 'get_experimental_data')
            ? ($this->class)::get_experimental_data()
            : [];
        if ($experimentalData) {
            $experiments = Plugin::elementor()->experiments;
            $experimentalData = self::prepareExperimentalData(
                $experimentalData,
                static fn(string $dependency): bool => $experiments->is_feature_active($dependency)
            );
            if ($experimentalData === null) {
                return;
            }
            $experiments->add_feature($experimentalData);
        }

        // Same rule as Elementor's modules manager: a module that reports itself inactive is not loaded.
        if (method_exists($this->class, 'is_active') && !($this->class)::is_active()) {
            return;
        }
        ($this->class)::instance();
    }

    /**
     * Modulix ships lifted modules as available features, independent of saved Pro experiment choices.
     * A module with an inactive foundation is omitted instead of being registered half-working.
     *
     * @param array<string,mixed> $data
     * @param callable(string):bool $isFeatureActive
     * @return array<string,mixed>|null
     */
    public static function prepareExperimentalData(array $data, callable $isFeatureActive): ?array {
        foreach ($data['dependencies'] ?? [] as $dependency) {
            if (!$isFeatureActive($dependency)) {
                return null;
            }
        }

        $data['default'] = 'active';
        $data['mutable'] = false;
        $data['hidden'] = true;
        return $data;
    }
}

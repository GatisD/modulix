<?php
namespace Modulix;

final class ModuleRegistry {
    /** @var array<string, callable(): ModuleInterface> */
    private array $factories = [];

    /** @param callable(): ModuleInterface $factory */
    public function add(string $id, callable $factory): void {
        if (isset($this->factories[$id])) {
            throw new \LogicException("Duplicate Modulix module: $id");
        }
        $this->factories[$id] = $factory;
    }

    /** @return string[] module ids in boot order */
    public function ids(): array {
        return array_keys($this->factories);
    }

    /** @return string[] ids booted, in order */
    public function bootAll(): array {
        $booted = [];
        foreach ($this->factories as $id => $factory) {
            $factory()->boot();
            $booted[] = $id;
        }
        return $booted;
    }
}

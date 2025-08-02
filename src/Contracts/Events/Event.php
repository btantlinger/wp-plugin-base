<?php

namespace WebMoves\PluginBase\Contracts\Events;

use WebMoves\PluginBase\Contracts\Components\Component;

interface Event extends Component {

	public function get_custom_schedules(): array;

	public function get_hook_name(): string;

	public function is_scheduled(): bool;

	public function is_recurring(): bool;

	public function get_schedule_name(): ?string;

	public function get_next_run(): ?\DateTime;

	public function get_interval_seconds(): ?int;

	public function get_interval_display(): ?string;


	public function schedule(\DateTime|int|null $when = null, ?string $recurrence = null): bool;


	public function unschedule(): bool;

	public function handle_event(): void;

}
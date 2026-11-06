<?php

namespace ArchFixtures\Capabilities\Modules\Orders\Actions;

// Fixture: a class in Actions/ with a public handle() that is not an Action subclass still declares; this one does not.
final class WriteOrderLog
{
    public function handle(): void {}
}

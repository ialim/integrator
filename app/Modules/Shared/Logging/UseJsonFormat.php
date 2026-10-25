<?php

namespace App\Modules\Shared\Logging;

use Illuminate\Log\Logger;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\FormattableHandlerInterface;
use Monolog\Handler\ProcessableHandlerInterface;
use Monolog\Logger as Monolog;

/**
 * Log channel tap: one JSON object per line, each carrying `extra.request_id`.
 */
final class UseJsonFormat
{
    public function __invoke(Logger $logger): void
    {
        $monolog = $logger->getLogger();

        if (! $monolog instanceof Monolog) {
            return;
        }

        foreach ($monolog->getHandlers() as $handler) {
            if ($handler instanceof FormattableHandlerInterface) {
                $handler->setFormatter(new JsonFormatter(JsonFormatter::BATCH_MODE_NEWLINES, true, false, true));
            }

            if ($handler instanceof ProcessableHandlerInterface) {
                $handler->pushProcessor(new RequestIdProcessor);
            }
        }
    }
}

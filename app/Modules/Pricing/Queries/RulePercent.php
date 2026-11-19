<?php

namespace App\Modules\Pricing\Queries;

use App\Modules\Pricing\Actions\Percentage;

/**
 * One percentage rule as other modules read it: a range's Platform Markup or
 * the margin floor, as stored ("25.0000") and in words ("25%"), with the
 * audit subject its History is kept under.
 */
final class RulePercent
{
    public function __construct(
        public readonly string $id,
        public readonly string $subjectType,
        public readonly string $percent,
    ) {}

    /** "27.5%". */
    public function words(): string
    {
        return Percentage::words($this->percent);
    }

    /** "27.5", for a form field. */
    public function plain(): string
    {
        return Percentage::plain($this->percent);
    }

    /**
     * @return array{0: string, 1: string}
     */
    public function auditSubject(): array
    {
        return [$this->subjectType, $this->id];
    }
}

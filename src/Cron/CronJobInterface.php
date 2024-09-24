<?php

declare(strict_types=1);

namespace App\Cron;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Yokai\Batch\Bridge\Symfony\Framework\JobWithStaticNameInterface;
use Yokai\Batch\Job\JobInterface;

#[AutoconfigureTag]
interface CronJobInterface extends JobInterface, JobWithStaticNameInterface
{
    public static function schedule(): string;
}

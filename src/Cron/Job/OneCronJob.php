<?php

declare(strict_types=1);

namespace App\Cron\Job;

use App\Cron\CronJobInterface;
use Yokai\Batch\JobExecution;

final class OneCronJob implements CronJobInterface
{
    public static function getJobName(): string
    {
        return 'cron.one';
    }

    public static function schedule(): string
    {
        return '* * * * *';
    }

    public function execute(JobExecution $jobExecution): void
    {
        $jobExecution->getLogger()->error('Something failed.', ['foo' => 'one']);
        $jobExecution->getSummary()->increment('error');
    }
}

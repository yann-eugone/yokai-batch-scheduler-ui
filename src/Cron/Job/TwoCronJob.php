<?php

declare(strict_types=1);

namespace App\Cron\Job;

use App\Cron\CronJobInterface;
use Yokai\Batch\JobExecution;
use Yokai\Batch\Warning;

final class TwoCronJob implements CronJobInterface
{
    public static function getJobName(): string
    {
        return 'cron.two';
    }

    public static function schedule(): string
    {
        return '*/5 * * * *';
    }

    public function execute(JobExecution $jobExecution): void
    {
        $jobExecution->addWarning(new Warning('Something is strange'));
        $jobExecution->getSummary()->set('done', true);
    }
}

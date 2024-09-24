<?php

declare(strict_types=1);

namespace App\Cron;

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Symfony\Component\Scheduler\Trigger\StaticMessageProvider;
use Symfony\Contracts\Translation\TranslatorInterface;
use Yokai\Batch\Bridge\Symfony\Messenger\LaunchJobMessage;

#[AsSchedule(name: 'cron')]
final readonly class CronScheduleProvider implements ScheduleProviderInterface
{
    public function __construct(
        /** @var iterable<CronJobInterface> */
        #[AutowireIterator(CronJobInterface::class)]
        private iterable $tasks,
        private TranslatorInterface $translator,
    ) {
    }

    public function getSchedule(): Schedule
    {
        $schedule = new Schedule();
        foreach ($this->tasks as $task) {
            $schedule->add(
                RecurringMessage::cron(
                    expression: $task::schedule(),
                    message: new StaticMessageProvider(
                        messages: [new LaunchJobMessage($task::getJobName())],
                        id: $task::getJobName(),
                        description: $this->translator->trans(
                            id: 'job.job_name.' . $task::getJobName(),
                            domain: 'YokaiBatchBundle',
                        ),
                    ),
                ),
            );
        }

        return $schedule;
    }
}

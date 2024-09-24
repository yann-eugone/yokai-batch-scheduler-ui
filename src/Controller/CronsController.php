<?php

declare(strict_types=1);

namespace App\Controller;

use App\Cron\CronScheduleProvider;
use Sonata\AdminBundle\Templating\TemplateRegistryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Clock\Clock;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Yokai\Batch\JobExecution;
use Yokai\Batch\Storage\Query;
use Yokai\Batch\Storage\QueryableJobExecutionStorageInterface;
use Yokai\Batch\Storage\QueryBuilder;

#[Route(path: '/crons')]
final class CronsController extends AbstractController
{
    private const LIMIT = 10;

    public function __construct(
        private readonly TemplateRegistryInterface $sonataTemplates,
        private readonly CronScheduleProvider $cronScheduleProvider,
        private readonly QueryableJobExecutionStorageInterface $jobExecutionStorage,
    ) {
    }

    #[Route(path: '/list', name: 'admin_cron_list')]
    public function list(): Response
    {
        return $this->render(
            'admin/cron/list.html.twig',
            [
                'base_template' => $this->sonataTemplates->getTemplate('layout'),
                'crons' => $this->getCrons(),
            ],
        );
    }

    #[Route(path: '/{name}', name: 'admin_cron_show')]
    public function show(string $name): Response
    {
        $cron = $this->getCrons()[$name] ?? throw $this->createNotFoundException();

        $executions = $this->getExecutions($name, self::LIMIT);

        return $this->render(
            'admin/cron/show.html.twig',
            [
                'base_template' => $this->sonataTemplates->getTemplate('layout'),
                'name' => $name,
                'cron' => $cron,
                'executions' => $executions,
            ],
        );
    }

    /**
     * @return array<string, array{
     *     trigger: string,
     *     description: string,
     *     nextRunDate: \DateTimeImmutable|null,
     *     lastExecution: JobExecution|null,
     * }>
     */
    private function getCrons(): array
    {
        $crons = [];
        foreach ($this->cronScheduleProvider->getSchedule()->getRecurringMessages() as $recurringMessage) {
            $trigger = $recurringMessage->getTrigger();
            $provider = $recurringMessage->getProvider();

            $crons[$provider->getId()] = [
                'trigger' => (string)$trigger,
                'description' => $provider instanceof \Stringable ? (string)$provider : $provider->getId(),
                'nextRunDate' => $trigger->getNextRunDate(Clock::get()->now()),
                'lastExecution' => $this->getExecutions($provider->getId(), 1)[0] ?? null,
            ];
        }

        return $crons;
    }

    /**
     * @return array<JobExecution>
     */
    private function getExecutions(string $name, int $limit): array
    {
        try {
            $query = (new QueryBuilder())
                ->jobs([$name])
                ->sort(Query::SORT_BY_START_DESC)
                ->limit($limit, 0)
                ->getQuery();
        } catch (\Throwable $exception) {
            throw new BadRequestHttpException(previous: $exception);
        }

        $executions = [];
        foreach ($this->jobExecutionStorage->query($query) as $execution) {
            $executions[] = $execution;
        }

        return $executions;
    }
}

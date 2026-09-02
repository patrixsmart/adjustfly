<?php

declare(strict_types=1);

namespace Patrixsmart\Adjustfly\Console;

use Illuminate\Console\Command;
use Patrixsmart\Adjustfly\Models\Adjustment;

/**
 * Prune old adjustments.
 *
 * Laravel's own `model:prune` only auto-discovers models in the application's
 * app/Models directory, so it silently skips the Adjustment model that lives in
 * this package. This command names the configured model explicitly and then
 * hands off to `model:prune`, so chunking, the ModelsPruned event and
 * soft-delete handling all behave exactly as they do for your own models.
 */
class PruneAdjustmentsCommand extends Command
{
    protected $signature = 'adjustfly:prune
                            {--days= : Override the configured retention window}
                            {--chunk=1000 : Number of adjustments to delete per chunk}
                            {--pretend : Report how many adjustments would be deleted, without deleting}';

    protected $description = 'Prune adjustments older than the configured retention window';

    public function handle(): int
    {
        $days = $this->option('days');

        if ($days !== null) {
            if (! is_numeric($days) || (int) $days < 0) {
                $this->components->error('The --days option must be a non-negative number.');

                return self::FAILURE;
            }

            config(['adjustfly.prune_after_days' => (int) $days]);
        }

        $retention = config('adjustfly.prune_after_days');

        if ($retention === null) {
            $this->components->warn(
                'Pruning is disabled: adjustfly.prune_after_days is null. Set it, or pass --days, to prune.'
            );

            return self::SUCCESS;
        }

        $model = config('adjustfly.model', Adjustment::class);

        $this->components->info(sprintf(
            'Pruning adjustments older than %d day(s).',
            (int) $retention
        ));

        $options = [
            '--model' => [$model],
            '--chunk' => (int) $this->option('chunk'),
        ];

        if ($this->option('pretend')) {
            $options['--pretend'] = true;
        }

        return $this->call('model:prune', $options);
    }
}

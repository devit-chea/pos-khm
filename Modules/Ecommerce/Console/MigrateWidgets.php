<?php

namespace Modules\Ecommerce\Console;

use Illuminate\Console\Command;
use Modules\Ecommerce\Entities\PageWidgets;

class MigrateWidgets extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'ecommerce:migrate-widgets';

    /**
     * The console command description.
     */
    protected $description = 'Migrate old page widget sparse columns to the new JSON settings column.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting widget migration...');
        $widgets = PageWidgets::all();
        $count = 0;
        foreach ($widgets as $widget) {
            // The accessor generates the settings array based on old columns if settings is null
            $settings = $widget->settings;
            if (!empty($settings) && empty($widget->getRawOriginal('settings'))) {
                $widget->settings = $settings;
                $widget->save();
                $count++;
            }
        }
        $this->info("Successfully migrated $count widgets.");
    }
}

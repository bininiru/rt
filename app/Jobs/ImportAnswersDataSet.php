<?php

namespace App\Jobs;

use App\Models\Intent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ImportAnswersDataSet implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $answers = DB::connection('sqlite')
            ->table('faq')
            ->select('*')
            ->get();

        $answers->each(function ($item) {
            $intent = new Intent();
            $intent->name = $item->question;
            $intent->save();

            $intent->questions()->create([
                'phrase' => $item->question
            ]);
            $intent->answers()->create([
                'phrase' => $item->answer
            ]);
        });
    }
}

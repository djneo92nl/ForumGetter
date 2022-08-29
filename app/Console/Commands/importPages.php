<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Pages;
use App\Models\Urls;
use App\Services\Parser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class importPages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:pages';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Urls::where('imported','=', 0)->where('url','LIKE', '%forum/%')->orderBy('created_at', 'asc')->chunk(100, function ($urls) {
            foreach($urls as $url) {
                $parser = new Parser('$url->url');
                $parser->grabContent();


                $contentCompresed = base64_encode(gzcompress(preg_replace("/\r|\n/", "", $parser->getContent())));

                $pages = new Pages();
                $pages->content = $contentCompresed ;
                $pages->title = $parser->getTitle() ;
                $pages->uuid = Str::uuid();
                $pages->type = 'page';
                $pages->url_id = $url->id;

                $pages->save();
                $url->imported = 1;
                $url->save();

            }

        });

        return 0;
    }
}

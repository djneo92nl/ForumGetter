<?php


namespace App\Console\Commands;

use App\Models\Urls;
use App\Services\Parser;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class scanDomain extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scan:domain';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan a given domain for urls';

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

        $this->scanUrlsToDB('');

        // This gets the first ID's in the table
        $active = true;
        $scannedId = 0;

        while ($active) {
            $urlsCount = Urls::where('imported','=', 0)->count();
            $scannedId = $this->loopCommand($scannedId);

            $this->line('at : '. $scannedId.' total : '. $urlsCount . 'remainder: '. ($urlsCount-$scannedId));

            if ($scannedId > $urlsCount) {
                $active = false;
            }
        }

        $this->line('done');
        return 0;
    }

    public function loopCommand($id)
    {
        $urls = Urls::where('url', 'LIKE', '%'.''.'%')->where('imported','=', 0)->where('id', '>', $id)->take('1000')->get();

        foreach ($urls as $url)
        {
            $this->scanUrlsToDB($url->url);

            $this->line($url->id);
            $id = $url->id;
        }
        return $id;
    }

    public function scanUrlsToDB($page)
    {
        $parser = new Parser($page);
        $parser->setLinksType('internal');
        $links = $parser->getHrefLinks();

        foreach ($links as $link)
        {
            $cleanedUrl = strtok($link[0], '?');
            //Check if url is known
            $dbLink = Urls::where('url', $cleanedUrl)->first();

            if (Str::length($cleanedUrl) > 250)
            {
                continue;
            }

            if ($dbLink === null) {
                $url = new Urls();
                $url->url = $cleanedUrl;
                $url->imported = false;

                $url->save();
            }
        }
    }
}

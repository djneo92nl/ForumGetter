<?php

namespace App\Console\Commands;

use App\Models\Urls;
use App\Services\Parser;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class scanPdfLinks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scan:pdfLinks';

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

    protected $pdfs = [];
    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Urls::where('imported','=', 0)->orderBy('created_at', 'asc')->chunk(100, function ($urls) {
            foreach($urls as $url) {
                $this->scanPageForPDF(Str::replace('.nl//', '.nl/', $url->url));

            }
        });
        $folder = 'test';
        Storage::makeDirectory($folder);

        foreach ($this->pdfs as $key => $pdf) {
            $this->pdfs[$key]['pages'] = implode(',', $pdf['pages']);
        }
        // Write CSV
        $csvExporter = new \Laracsv\Export();
        $csvString = $csvExporter->build(collect($this->pdfs), array_keys(Arr::first($this->pdfs)))->getWriter()->getContent();
        Storage::disk('local')->put($folder.'/export.csv', $csvString);

    }


    public function scanPageForPDF($page)
    {
        $parser = new Parser($page);
        $parser->setLinksType('external');
        $links = $parser->getHrefLinks();

        foreach ($links as $link)
        {
            $cleanedUrl = strtok($link[0], '?');

            if (Str::endsWith($cleanedUrl, '.pdf' )) {
                $pdfFileUrlParts  = explode('/', $cleanedUrl);
                $pdfFileUrlParts = array_reverse($pdfFileUrlParts);

                if (array_key_exists(Str::slug($pdfFileUrlParts[0]), $this->pdfs)) {
                    $this->pdfs[Str::slug($pdfFileUrlParts[0])]['pages'][] = $page;
                } else {
                    $this->pdfs[Str::slug($pdfFileUrlParts[0])]['pages'][] = $page;
                    $this->pdfs[Str::slug($pdfFileUrlParts[0])]['file'] = $pdfFileUrlParts[0];
                    $this->pdfs[Str::slug($pdfFileUrlParts[0])]['url'] = $cleanedUrl;
                }

            }

        }
    }
}

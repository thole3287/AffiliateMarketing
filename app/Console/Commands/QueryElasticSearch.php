<?php

namespace App\Console\Commands;

use App\Elasticsearch\BaseElastic;
use Illuminate\Console\Command;

class QueryElasticSearch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:query-elastic-search';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        dd(123123123);
        $elasticModel = new BaseElastic();
        // $params = [
        //     'index' => 'products',
        //     'type' => '_search',
        //     'body'  => [
        //         'query' => [
        //             'terms' => [
        //                 '_id' => [1,2,3]
        //             ]
        //         ],
        //         'size'     => 10000,
        //     ]
        // ];
        $params = [
            'index' => 'products',
            'type' => '_doc',
            'id' => 1
        ];

        $products = $elasticModel->getClientBuilder()->delete($params);
        // $products = $elasticModel->getClientBuilder()->index($params);
        dd($products);
    }
}

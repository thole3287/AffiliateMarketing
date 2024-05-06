<?php

namespace App\Console\Commands;

use App\Elasticsearch\BaseElastic;
use App\Models\product\Product;
use Illuminate\Console\Command;
use Elasticsearch\ClientBuilder;


class SyncElasticsearchDataProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-elasticsearch-data-products';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync data from MySQL to Elasticsearch';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $products = Product::where('sync_es', 'no')->get();

        $elasticModel = new BaseElastic();

        foreach ($products as $product) {
		    $params = [
			    'index' => 'products',
			    'type' => '_doc',
                'id' => $product->id,
                'body' => $product->toArray()
		    ];
		    $elasticModel->getClientBuilder()->index($params);
            // $response = $client->update($params);
            // dd( $response );
            // $elasticModel->getClientBuilder()->update($params);
            // Mark the product as synced
            $product->sync_es = 'yes';
            $product->save();
        }

        $this->info('Data synced to Elasticsearch successfully.');
    }
}

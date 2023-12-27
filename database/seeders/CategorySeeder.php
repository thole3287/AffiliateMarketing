<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         // Create parent categories
         $parent1 = Category::create(['name' => 'Parent Category 1']);
         $parent2 = Category::create(['name' => 'Parent Category 2']);
 
         // Create child categories
         $child1 = Category::create(['name' => 'Child Category 1', 'parent_id' => $parent1->id]);
         $child2 = Category::create(['name' => 'Child Category 2', 'parent_id' => $parent1->id]);
         $child3 = Category::create(['name' => 'Child Category 3', 'parent_id' => $parent2->id]);
 
         // You can add more categories as needed
 
         $this->command->info('Categories seeded successfully.');
    }
}

<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\File;
use App\Models\User;
use GuzzleHttp\Promise\Create;
use Illuminate\Console\Command;

class CreateDocumentsForUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-documents-for-user';

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
        $users = User::where('is_active', true)
            ->doesntHave('files')
            ->get();
    
        foreach($users as $user){
            $categories = Category::get();
            foreach($categories as $category){
                File::create([
                    'description' => $category->description,
                    'category_id' => $category->id,
                    'user_id' => $user->id
                ]);
            }
        }
    }
}

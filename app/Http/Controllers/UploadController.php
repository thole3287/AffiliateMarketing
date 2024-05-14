<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Services\UploadService;

class UploadController extends Controller
{
    protected $uploadService;

    public function __construct(UploadService $uploadService){
        $this->uploadService = $uploadService;
    }

    public function uploadFile(Request $request) {
        $imageUrl = $this->uploadService->updateSingleImage($request, 'image_file', null, 'Images', false);
        if(!empty($imageUrl))
        {
            return response()->json([
                'status' => true,
                'message' => 'Upload file success!',
                'data' => [
                    'url' => $imageUrl
                ]
            ], 200);

        }
        // if ($imageUrl->getStatusCode() === 400 && !$imageUrl->getData()->status) {
        //     return response()->json(['error' => $imageUrl->getData()->message], $imageUrl->getStatusCode());
        // }

    }

}

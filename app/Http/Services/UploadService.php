<?php

namespace App\Http\Services;
use Illuminate\Http\Request;


class UploadService
{

    public function uploadMultipleImages(Request $request, $folderName)
    {
        $images = [];

        if($request->hasFile('image_detail')) {
            foreach($request->file('image_detail') as $image) {
                $imageName = time() . '_' . $image->getClientOriginalName();
                $image->storeAs('public/' . $folderName, $imageName);
                $images[] = 'public/' . $folderName . '/' . $imageName; // Thêm đường dẫn vào mảng
            }
        }

        if($request->input('image_detail_url')) {
            foreach ($request->input('image_detail_url') as $image_url) {
                // Kiểm tra xem địa chỉ URL có hợp lệ không trước khi thêm vào mảng
                if(filter_var($image_url, FILTER_VALIDATE_URL)) {
                    $images[] = $image_url;
                }
            }
        }

        return $images; // Trả về mảng đường dẫn của các ảnh đã upload
    }

}
